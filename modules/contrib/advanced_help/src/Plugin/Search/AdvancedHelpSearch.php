<?php

namespace Drupal\advanced_help\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\advanced_help\AdvancedHelpManager;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\Core\Config\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes a keyword search for Advanced Help against the {advanced_help} topic pages.
 *
 * @SearchPlugin(
 *   id = "advanced_help_search",
 *   title = @Translation("Advanced Help")
 * )
 */
class AdvancedHelpSearch extends SearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Advanced Help Manager.
   * @var \Drupal\advanced_help\AdvancedHelpManager
   */
  protected $advancedHelp;

  /**
   * A config object for 'search.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;


  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('database'),
      $container->get('plugin.manager.advanced_help'),
      $container->get('current_user'),
      $container->get('config.factory')->get('search.settings'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a UserSearch object.
   *
   * @param Connection $database
   *   The database connection.
   * @param \Drupal\advanced_help\AdvancedHelpManager $advanced_help
   *   The advanced Help manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param array $configuration
   * @param \Drupal\Core\Config\Config $search_settings
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(Connection $database, AdvancedHelpManager $advanced_help, AccountInterface $current_user, Config $search_settings, array $configuration, $plugin_id, $plugin_definition) {
    $this->database = $database;
    $this->advancedHelp = $advanced_help;
    $this->currentUser = $current_user;
    $this->searchSettings = $search_settings;

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addCacheTags(['user_list']);
  }

  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf(!empty($account) && $account->hasPermission('access user profiles'))->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Gets search id for each topic.
   *
   * Get or create an sid (search id) that correlates to each topic for
   * the search system.
   * @param array $topics
   * @return array
   */
  private function getSids($topics) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $result = $this->database->select('advanced_help_index', 'ahi')
      ->fields('ahi', ['sid', 'module', 'topic', 'langcode'])
      ->condition('langcode', $language)
      ->execute();
    foreach ($result as $sid) {
      if (empty($topics[$sid->module][$sid->topic])) {
        $this->database->query("DELETE FROM {advanced_help_index} WHERE sid = :sid", [':sid' => $sid->sid]);
      }
      else {
        $topics[$sid->module][$sid->topic]['sid'] = $sid->sid;
      }
    }
    return $topics;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->isSearchExecutable()) {
      $keys = $this->keywords;

      // Build matching conditions.
      $query = $this->database
        ->select('search_index', 'i', ['target' => 'replica'])
        ->extend('Drupal\search\SearchQuery')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->join('advanced_help_index', 'ahi', 'ahi.sid = i.sid');
      $query->join('search_dataset', 'sd', "ahi.sid = sd.sid AND sd.type = '{$this->pluginId}'");
      $query->searchExpression($keys, $this->getPluginId());

      $find = $query
        ->fields('i', ['langcode'])
        ->fields('ahi', ['module', 'topic'])
        ->fields('sd', ['data'])
        ->groupBy('i.langcode, ahi.module, ahi.topic, sd.data')
        ->limit(10)
        ->execute();

      $results = [];
      foreach ($find as $key => $item) {
        $result = [
          'link' => '/help/ah/' . $item->module . '/' . $item->topic,
          'title' => $item->topic,
          'score' => $item->calculated_score,
          'snippet' => search_excerpt($keys, $item->data, $item->langcode),
          'langcode' => $item->langcode,
        ];
        $results[] = $result;
      }

      return $results;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Interpret the cron limit setting as the maximum number of nodes to index
    // per cron run.
    $limit = (int)$this->searchSettings->get('index.cron_limit');
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $topics = $this->getSids($this->advancedHelp->getTopics());

    // If we got interrupted by limit, this will contain the last module
    // and topic we looked at.
    $last = \Drupal::state()->get($this->getPluginId() . '.last_cron', ['time' => 0]);
    $count = 0;
    foreach ($topics as $module => $module_topics) {
      // Fast forward if necessary.
      if (!empty($last['module']) && $last['module'] != $module) {
        continue;
      }

      foreach ($module_topics as $topic => $info) {
        // Fast forward if necessary.
        if (!empty($last['topic']) && $last['topic'] != $topic) {
          continue;
        }

        //If we've been looking to catch up, and we have, reset so we
        // stop fast forwarding.
        if (!empty($last['module'])) {
          unset($last['topic']);
          unset($last['module']);
        }

        $file = $this->advancedHelp->getTopicFileName($module, $topic);
        if ($file && (empty($info['sid']) || filemtime($file) > $last['time'])) {
          if (empty($info['sid'])) {
            $info['sid'] = $this->database->insert('advanced_help_index')
              ->fields([
                'module' => $module,
                'topic' => $topic,
                'langcode' => $language
              ])
              ->execute();
          }
        }

        // Update index, using search index "type" equal to the plugin ID.
        search_index($this->getPluginId(), $info['sid'], $language, file_get_contents($file));
        $count++;
        if ($count >= $limit) {
          $last['module'] = $module;
          $last['topic'] = $topic;
          \Drupal::state()->set($this->getPluginId() . '.last_cron', $last);
          return;
        }
      }
    }
    \Drupal::state()->set($this->getPluginId() . '.last_cron', ['time' => time()]);
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    search_index_clear($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $topics = $this->advancedHelp->getTopics();
    $total = 0;
    foreach ($topics as $module => $module_topics) {
      foreach ($module_topics as $topic => $info) {
        $file = $this->advancedHelp->getTopicFileName($module, $topic);
        if ($file) {
          $total++;
        }
      }
    }
    $last_cron = \Drupal::state()->get($this->getPluginId() . '.last_cron', ['time' => 0]);
    $indexed = 0;
    if ($last_cron['time'] != 0) {
      $indexed = $this->database->select('search_dataset', 'sd')
        ->fields('sd', ['sid'])
        ->condition('type', $this->getPluginId())
        ->condition('reindex', 0)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    return ['remaining' => $total - $indexed, 'total' => $total];
  }
}