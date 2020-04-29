<?php

namespace Drupal\advanced_help;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Serialization\Yaml;

/**
 * AdvancedHelp plugin manager.
 */
class AdvancedHelpManager extends DefaultPluginManager {
  use StringTranslationTrait;

  /**
   * Constructs an AdvancedHelpManager instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Theme handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, CacheBackendInterface $cache_backend, TranslationInterface $string_translation) {
    $this->module_handler = $module_handler;
    $this->theme_handler = $theme_handler;
    $this->setStringTranslation($string_translation);
    $this->alterInfo('advanced_help');
    $this->setCacheBackend($cache_backend, 'advanced_help', ['advanced_help']);
  }

  /**
   * Get the modules/theme list.
   * @todo cache
   */
  public function getModuleList() {
    $modules = $this->module_handler->getModuleList();
    $themes = $this->theme_handler->listInfo();
    $result = [];

    foreach ($modules + $themes  as $name => $data) {
      $result[$name] = $this->module_handler->getName($name);
    }
    return $result;
  }

  /**
   * Get the information for a single help topic.
   * @param $module
   * @param $topic
   * @return string|bool
   */
  function getTopic($module, $topic) {
    $topics = $this->getTopics();
    if (!empty($topics[$module][$topic])) {
      return $topics[$module][$topic];
    }
    return FALSE;
  }

  /**
   * Return the name of the module.
   * @param string $module
   * @return string
   */
  function getModuleName($module) {
    return $this->module_handler->getName($module);
  }

  /**
   * Search the system for all available help topics.
   * @todo check visibility of the method.
   */
  public function getTopics() {
    $ini = $this->parseHelp();
    return $ini['topics'];
  }

  /**
   * Returns advanced help settings.
   * @todo check visibility of the method.
   */
  public function getSettings() {
    $ini = $this->parseHelp();
    return $ini['settings'];
  }


  /**
   * Function to parse yml / txt files.
   *
   * @todo implement cache
   * @todo check visibility of the method.
   */
  public function parseHelp() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    static $ini = NULL;

    $cache = $this->cacheGet('advanced_help_ini_' . $language);
    if ($cache) {
      $ini = $cache->data;
    }

    if (!isset($ini)) {
      $ini = ['topics' => [], 'settings' => []];

      foreach ($this->module_handler->getModuleList() + $this->theme_handler->listInfo() as $plugin_name => $extension) {
        $module = $plugin_name;
        $module_path = $extension->getPath();
        $info = [];

        if (file_exists("$module_path/help/$module.help.yml")) {
          $path = "$module_path/help";
          $info =  Yaml::decode(file_get_contents("$module_path/help/$module.help.yml"));
        }
        elseif (!file_exists("$module_path/help")) {
          // Look for one or more README files.
          $files = file_scan_directory("./$module_path",
            '/^(readme).*\.(txt|md)$/i', ['recurse' => FALSE]);
          $path = "./$module_path";
          foreach ($files as $name => $fileinfo) {
            $info[$fileinfo->filename] = [
              'line break' => TRUE,
              'readme file' => TRUE,
              'file' => $fileinfo->filename,
              'title' => $fileinfo->name,
            ];
          }
        }

        if (!empty($info)) {
          // Get translated titles:
          $translation = [];
          if (file_exists("$module_path/translations/help/$language/$module.help.yml")) {
            $translation = Yaml::decode(file_get_contents("$module_path/translations/help/$language/$module.help.yml"));
          }

          $ini['settings'][$module] = [];
          if (!empty($info['advanced help settings'])) {
            $ini['settings'][$module] = $info['advanced help settings'];
            unset($info['advanced help settings']);

            // Check translated strings for translatable global settings.
            if (isset($translation['advanced help settings']['name'])) {
              $ini['settings']['name'] = $translation['advanced help settings']['name'];
            }
            if (isset($translation['advanced help settings']['index name'])) {
              $ini['settings']['index name'] = $translation['advanced help settings']['index name'];
            }

          }

          foreach ($info as $name => $topic) {
            // Each topic should have a name, a title, a file and path.
            $file = !empty($topic['file']) ? $topic['file'] : $name;
            $ini['topics'][$module][$name] = [
              'name' => $name,
              'module' => $module,
              'ini' => $topic,
              'title' => !empty($translation[$name]['title']) ? $translation[$name]['title'] : $topic['title'],
              'weight' => isset($topic['weight']) ? $topic['weight'] : 0,
              'parent' => isset($topic['parent']) ? $topic['parent'] : 0,
              'popup width' => isset($topic['popup width']) ? $topic['popup width'] : 500,
              'popup height' => isset($topic['popup height']) ? $topic['popup height'] : 500,
              // Require extension.
              'file' => isset($topic['readme file']) ? $file : $file . '.html',
              // Not in .ini file.
              'path' => $path,
              'line break' => isset($topic['line break']) ? $topic['line break'] : (isset($ini['settings'][$module]['line break']) ? $ini['settings'][$module]['line break'] : FALSE),
              'navigation' => isset($topic['navigation']) ? $topic['navigation'] : (isset($ini['settings'][$module]['navigation']) ? $ini['settings'][$module]['navigation'] : TRUE),
              'css' => isset($topic['css']) ? $topic['css'] : (isset($ini['settings'][$module]['css']) ? $ini['settings'][$module]['css'] : NULL),
              'readme file' => isset($topic['readme file']) ? $topic['readme file'] : FALSE,
            ];
          }
        }
      }
      // drupal_alter('advanced_help_topic_info', $ini);
      $this->cacheSet('advanced_help_ini_' . $language, $ini);
    }
    return $ini;
  }

  /**
   * Load and render a help topic.
   *
   * @todo allow the theme override the help.
   * @param $module.
   * @param $topic.
   * @return array.
  */
  public function getTopicFileInfo($module, $topic) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $info = $this->getTopic($module, $topic);
    if (empty($info)) {
      return FALSE;
    }

    $path_type = (preg_match('/themes/', $info['path'])) ? 'theme' : 'module';
    // Search paths:
    $paths = [
//      // Allow theme override.
//      path_to_theme() . '/help',
      // Translations.
      drupal_get_path($path_type, $module) . "/translations/help/$language",
      // In same directory as .inc file.
      $info['path'],
    ];

    foreach ($paths as $path) {
      if (file_exists("$path/$info[file]")) {
        return ['path' => $path, 'file' => $info['file']];
      }
    }

    return FALSE;
  }

  public function getTopicFileName($module, $topic) {
    $info = $this->getTopicFileInfo($module, $topic);
    if ($info) {
      return "./{$info['path']}/{$info['file']}";
    }
  }

}
