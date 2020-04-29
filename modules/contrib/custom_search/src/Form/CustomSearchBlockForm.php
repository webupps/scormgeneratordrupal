<?php

namespace Drupal\custom_search\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\search\SearchPageRepositoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Builds the search form for the search block.
 */
class CustomSearchBlockForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The search page repository.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**   * Constructs a new SearchBlockForm.
   *
   * @param \Drupal\search\SearchPageRepositoryInterface $search_page_repository
   *   The search page repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module handler object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(
    SearchPageRepositoryInterface $search_page_repository,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository
  ) {
    $this->searchPageRepository = $search_page_repository;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search.search_page_repository'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_search_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = func_get_arg(2);
    $form['#method'] = 'post';

    // Popup.
    $form['popup'] = array(
      '#type'       => 'fieldset',
      '#weight'     => 1 + $config['search_box']['weight'],
      '#attributes' => array('class' => array('custom_search-popup')),
    );

    // Search box.
    $form['keys'] = array(
      '#type'           => 'search',
      '#title'          => Html::escape($config['search_box']['label']),
      '#title_display'  => $config['search_box']['label_visibility'] ? 'before' : 'invisible',
      '#size'           => $config['search_box']['size'],
      '#maxlength'      => $config['search_box']['max_length'],
      '#default_value'  => '',
      '#placeholder'    => array('title' => Html::escape($config['search_box']['placeholder'])),
      '#attributes'     => array(
        'title' => Html::escape($config['search_box']['title']),
        'class' => array('custom_search-keys'),
      ),
      '#weight'         => $config['search_box']['weight'],
    );

    // Content.
    $toptions = [];
    $types = array_keys(array_filter($config['content']['types']));
    if (count($types)) {
      $names = node_type_get_names();
      if (count($types) > 1 || $config['content']['any']['force']) {
        $toptions['c-all'] = $config['content']['any']['text'];
      }
      foreach ($types as $type) {
        $toptions['c-' . $type] = $names[$type];
      }
    }
    $options = [];
    // Other searches.
    $others = !empty($config['content']['other']) ? array_keys(array_filter($config['content']['other'])) : [];
    // If content types and other searches are combined, make an optgroup.
    if (count($others) && count($toptions) && $config['content']['selector']['type'] == 'select') {
      $search_page = $this->entityTypeManager->getStorage('search_page')->load($config['content']['page']);
      $options[Html::escape($search_page->label())] = $toptions;
    }
    else {
      $options = $toptions;
    }
    if (count($others)) {
      $other_pages = $this->entityTypeManager->getStorage('search_page')->loadMultiple($others);
      foreach ($others as $other) {
        $options['o-' . Html::escape($other_pages[$other]->id())] = Html::escape($other_pages[$other]->label());
      }
    }
    if (count($options)) {
      $selector_type = $config['content']['selector']['type'];
      if ($selector_type == 'selectmultiple') {
        $selector_type = 'select';
        $multiple = TRUE;
      }
      else {
        $multiple = FALSE;
      }
      $form['types'] = array(
        '#type'           => $selector_type,
        '#multiple'       => $multiple,
        '#title'          => Html::escape($config['content']['selector']['label']),
        '#title_display'  => $config['content']['selector']['label_visibility'] ? 'before' : 'invisible',
        '#options'        => $options,
        '#default_value'  => ($selector_type == 'checkboxes') ? array('c-all') : 'c-all',
        '#attributes'     => array('class' => array('custom-search-selector', 'custom-search-types')),
        '#weight'         => $config['content']['weight'],
        '#validated'      => TRUE,
      );
      // If there's only one type, hide the selector.
      if (count($others) + count($types) == 1 && !$config['content']['any']['force']) {
        $form['custom_search_types']['#type'] = 'hidden';
        $form['custom_search_types']['#default_value'] = key(array_slice($options, count($options) - 1));
      }
      else if ($config['content']['region'] == 'popup') {
        $form['popup']['types'] = $form['types'];
        unset($form['types']);
      }

    }

    // Taxonomy.
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    if (count($config['taxonomy']) > 0) {
      $taxonomy_term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');

      foreach ($vocabularies as $voc) {
        $vid = $voc->id();
        if ($config['taxonomy'][$vid]['type'] != 'disabled') {
          $options = array();
          $options['c-all'] = $config['taxonomy'][$vid]['all_text'];
          $vocabulary_depth = (!$config['taxonomy'][$vid]['depth']) ? NULL : $config['taxonomy'][$vid]['depth'];
          $terms = $taxonomy_term_storage->loadTree($vid, 0, $vocabulary_depth, TRUE);
          foreach ($terms as $term) {
            $termName = Html::escape($this->entityRepository->getTranslationFromContext($term)->label());
            $options['c-' . $term->id()] = (Unicode::substr($config['taxonomy'][$vid]['type'], 0, 6) == 'select') ? str_repeat('-', $term->depth) . ' ' . $termName : $termName;
          }
          $selector_type = $config['taxonomy'][$vid]['type'];
          if ($selector_type == 'selectmultiple') {
            $selector_type = 'select';
            $multiple = TRUE;
          }
          else {
            $multiple = FALSE;
          }
          $form['vocabulary_' . $vid] = array(
            '#type'           => $selector_type,
            '#multiple'       => $multiple,
            '#title'          => Html::escape($config['taxonomy'][$vid]['label']),
            '#title_display'  => $config['taxonomy'][$vid]['label_visibility'] ? 'before' : 'invisible',
            '#options'        => $options,
            '#default_value'  => ($selector_type == 'checkboxes') ? array('c-all') : 'c-all',
            '#attributes'     => array('class' => array('custom-search-selector', 'custom-search-vocabulary')),
            '#weight'         => $config['taxonomy'][$vid]['weight'],
          );

          if ($config['taxonomy'][$vid]['region'] == 'popup') {
            $form['popup']['vocabulary_' . $vid] = $form['vocabulary_' . $vid];
            unset($form['vocabulary_' . $vid]);
          }
        }
      }
    }

    // Languages.
    $options = array();
    $languages = array_keys(array_filter($config['languages']['languages']));
    if (count($languages)) {
      if (count($languages) > 1 || $config['languages']['any']['force']) {
        $options['c-all'] = $config['languages']['any']['text'];
      }
      $current_language = \Drupal::languageManager()->getCurrentLanguage();
      $current_language_id = $current_language->getId();
      foreach ($languages as $language) {
        switch ($language) {
          case 'current':
            $options['c-' . $language] = t('- Current language (@current) -', array('@current' => $current_language->getName()));
            break;
          case Language::LANGCODE_NOT_SPECIFIED:
            $options['c-' . $language] = t('- Not specified -');
            break;
          case Language::LANGCODE_NOT_APPLICABLE:
            $options['c-' . $language] = t('- Not applicable -');
            break;
          default:
            if ($language != $current_language_id || ($language != $current_language_id && !array_key_exists('c-' . $language, $options))) {
              $options['c-' . $language] = \Drupal::languageManager()->getLanguageName($language);
            }
        }
      }
    }
    if (count($options)) {
      $selector_type = $config['languages']['selector']['type'];
      if ($selector_type == 'selectmultiple') {
        $selector_type = 'select';
        $multiple = TRUE;
      }
      else {
        $multiple = FALSE;
      }
      $form['languages'] = array(
        '#type'           => $selector_type,
        '#multiple'       => $multiple,
        '#title'          => Html::escape($config['languages']['selector']['label']),
        '#title_display'  => $config['languages']['selector']['label_visibility'] ? 'before' : 'invisible',
        '#options'        => $options,
        '#default_value'  => ($selector_type == 'checkboxes') ? array('c-all') : 'c-all',
        '#attributes'     => array('class' => array('custom-search-selector', 'custom-search-language')),
        '#weight'         => $config['languages']['weight'],
        '#validated'      => TRUE,
      );
      // If there's only one type, hide the selector.
      if (count($languages) == 1 && !$config['languages']['any']['force']) {
        $form['languages']['#type'] = 'hidden';
        $form['languages']['#default_value'] = key(array_slice($options, count($options) - 1));
      }
      else if ($config['languages']['region'] == 'popup') {
        $form['popup']['languages'] = $form['languages'];
        unset($form['languages']);
      }
    }

    // Custom Paths.
    $paths = $config['paths']['list'];
    if ($paths != '') {
      $options = array();
      $lines = explode("\n", $paths);
      foreach ($lines as $line) {
        $temp = explode('|', $line);
        $options[$temp[0]] = (count($temp) >= 2) ? t($temp[1]) : '';
      }
      if (count($options) == 1) {
        $form['paths'] = array(
          '#type'           => 'hidden',
          '#default_value'  => key($options),
        );
      }
      else {
        $form['paths'] = array(
          '#type'           => $config['paths']['selector']['type'],
          '#title'          => Html::escape($config['paths']['selector']['label']),
          '#title_display'  => $config['paths']['selector']['label_visibility'] ? 'before' : 'invisible',
          '#options'        => $options,
          '#default_value'  => key($options),
          '#weight'         => $config['paths']['weight'],
        );
        if ($config['paths']['region'] == 'popup') {
          $form['popup']['paths'] = $form['paths'];
          unset($form['paths']);
        }
      }
    }

    // Criteria.
    $criteria = array('or', 'phrase', 'negative');
    foreach ($criteria as $c) {
      if ($config['criteria'][$c]['display']) {
        $form['criteria_' . $c] = array(
          '#type'       => 'textfield',
          '#title'      => Html::escape($config['criteria'][$c]['label']),
          '#size'       => 15,
          '#maxlength'  => 255,
          '#weight'     => $config['criteria'][$c]['weight'],
        );
        if ($config['criteria'][$c]['region'] == 'popup') {
          $form['popup']['criteria_' . $c] = $form['criteria_' . $c];
          unset($form['criteria_' . $c]);
        }
      }
    }

    // Actions.
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type'   => 'submit',
      '#value'  => Html::escape($config['submit']['text']),
      // Prevent op from showing up in the query string.
      '#name'   => '',
      '#weight' => $config['submit']['weight'],
    );
    if ($config['submit']['image_path'] != '') {
      $form['actions']['submit']['#type'] = 'image_button';
      $form['actions']['submit']['#src'] = $config['submit']['image_path'];
      $form['actions']['submit']['#attributes'] = array(
        'alt'   => Html::escape($config['submit']['text']),
        'class' => array('custom-search-button'),
      );
      unset($form['actions']['submit']['#value']);
    }
    elseif ($form['actions']['submit']['#value'] == '') {
      $form['actions']['submit']['#attributes'] = array('style' => 'display:none;');
    }

    // If nothing has been added to the popup, don't output any markup.
    if (!count(Element::children($form['popup']))) {
      unset($form['popup']);
    }

    // Add attributes.
    $form['#attributes']['role'] = 'search';

    // Passes the config for later use.
    $form_state->set('config', $config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->get('config');
    $filters = array();

    // Keywords.
    $keys = trim($form_state->getValue('keys'));

    // Filter Types.
    $types = $form_state->hasValue('types') ? $form_state->getValue('types') : array();
    if (!is_array($types)) {
      $types = array($types);
    }

    // Check if we're using another search (ie. Users).
    $first_type = current($types);
    if (substr($first_type, 0, 2) == 'o-') {
      $search_page_id = substr($first_type, 2);
      $search_pages = $this->entityTypeManager->getStorage('search_page')->loadMultiple(array($search_page_id));
      if (!empty($search_pages)) {
        $route = 'search.view_' . $search_page_id;
      }
    }
    else {
      // Build route.
      $route = 'search.view_' . $config['content']['page'];
      // Types filters.
      $types = array_map(function($val) { return $this->filterKeys($val);}, array_filter($types));
      $excluded = array_map(function($val) { return $this->filterKeys($val);}, array_filter($config['content']['excluded']));
      if (count($types)) {
        if (in_array('all', $types)) {
          // If - Any - is set to restrict the search, grab the content types.
          if ($config['content']['any']['restricts']) {
            $types = array_keys(array_filter($config['content']['types']));
          }
          // If exclusion has to be made, specify all the other types
          if (!empty($excluded)) {
            $types = array_keys(node_type_get_names());
          }
          $types = array_diff($types, $excluded);
          if (!in_array('all', $types)) {
            foreach ($types as $type) {
              $filters[] = 'type:' . $type;
            }
          }
        }
        // If it's not - Any -, search for that type.
        else {
          $types = array_diff($types, $excluded);
          foreach ($types as $type) {
            $filters[] = 'type:' . $type;
          }
        }
      }
      // If there's no type selector but exclusion has to be made, specify all the other types.
      elseif (!empty($excluded)) {
        $types = array_diff(array_keys(node_type_get_names()), $excluded);
        foreach ($types as $type) {
          $filters[] = 'type:' . $type;
        }
      }
      // Taxonomy filters.
      if ($this->moduleHandler->moduleExists('taxonomy')) {
        $terms = array();
        $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
        foreach ($vocabularies as $voc) {
          $vid = $voc->id();
          if ($form_state->hasValue('vocabulary_' . $vid)) {
            $vterms = $form_state->getValue('vocabulary_' . $vid);
            if (!is_array($vterms)) {
              $vterms = array($vterms);
            }
            $terms = array_merge($terms, $vterms);
          }
        }
        // Uses array_values() to filter here to get numerical index,
        // so we can splice the array later if needed (see line below the array_map()).
        $terms = array_map(function($val) { return $this->filterKeys($val);}, array_values(array_filter($terms)));
        // If one or more -Any- is selected, delete them.
        while (($index = array_search('all', $terms)) !== FALSE) {
          array_splice($terms, $index, 1);
        }
        if (count($terms)) {
          foreach ($terms as $term) {
            $filters[] = 'term:' . $term;
          }
        }
      }
      // Criteria filters.
      if ($form_state->hasValue('criteria_or') && trim($form_state->getValue('criteria_or')) != '') {
        $keys .= ' ' . str_replace(' ', ' OR ', trim($form_state->getValue('criteria_or')));
      }
      if ($form_state->hasValue('criteria_negative') && trim($form_state->getValue('criteria_negative')) != '') {
        $keys .= ' -' . str_replace(' ', ' -', trim($form_state->getValue('criteria_negative')));
      }
      if ($form_state->hasValue('criteria_phrase') && trim($form_state->getValue('criteria_phrase')) != '') {
        $keys .= ' "' . trim($form_state->getValue('criteria_phrase')) . '"';
      }
      // Language filters.
      $languages = $form_state->hasValue('languages') ? $form_state->getValue('languages') : array();
      if (!is_array($languages)) {
        $languages = array($languages);
      }
      $languages = array_map(function($val) { return $this->filterKeys($val);}, array_filter($languages));
      if (count($languages)) {
        // If - Any - is selected and - Any - is set to restrict the search, grab the languages.
        if (in_array('all', $languages) && $config['languages']['any']['restricts']) {
          $languages = array_keys(array_filter($config['languages']['languages']));
        }
        // If it's not - Any -, search for that language.
        if (!in_array('all', $languages)) {
          foreach ($languages as $language) {
            if ($language == 'current') {
              $filters[] = 'language:' . \Drupal::languageManager()->getCurrentLanguage()->getId();
            }
            else {
              $filters[] = 'language:' . $language;
            }
          }
        }
      }
    }

    // Build a custom path if needed.
    if ($form_state->hasValue('paths') && $form_state->getValue('paths') != '') {
      $route = $form_state->getValue('paths');
      $route = str_replace('[current_path]', \Drupal::service('path.current')->getPath(), $route);
      $route = str_replace('[key]', $keys, $route);
      if (strpos($route, '[types]') !== FALSE) {
        $route = str_replace('[types]', (isset($types) && count($types)) ? implode($config['paths']['separator'], $types) : '', $route);
      }
      if (strpos($route, '[terms]') !== FALSE) {
        $route = str_replace('[terms]', (isset($terms) && count($terms)) ? implode($config['paths']['separator'], $terms) : '', $route);
      }
      // Check for a query string.
      $query = array();
      $route_query_position = strpos($route, '?');
      if ($route_query_position !== FALSE) {
        $query_tmp = substr($route, 1 + $route_query_position);
        $query_tmp = str_replace('&amp;', '&', $query_tmp);
        $query_tmp = explode('&', $query_tmp);
        foreach ($query_tmp as $param) {
          $param_exploded = explode('=', $param);
          $query[$param_exploded[0]] = $param_exploded[1];
        }
        $route = substr($route, 0, $route_query_position);
      }
      // If not an external URL, add the base url scheme.
      if (substr($route, 0, 4) != 'http') $route = 'base://' . $route;
      // Generate the final url.
      $url = Url::fromUri($route, array('query' => $query));
      // Redirect.
      if ($url->isExternal()){
        $form_state->setResponse(new TrustedRedirectResponse($url->toUriString(), 302));
      } else {
        $form_state->setRedirectUrl($url);
      }

    }
    else {
      $query['keys'] = $keys;
      if (count($filters)) $query['f'] = $filters;
      $form_state->setRedirect(
        $route,
        array(),
        array('query' => $query)
      );
    }

  }

  /*
   * Helper functions.
   */
  private static function filterKeys($val) {
    return (strlen($val) > 2 && $val[1] == '-') ? Unicode::substr($val, 2) : $val;
  }

}
