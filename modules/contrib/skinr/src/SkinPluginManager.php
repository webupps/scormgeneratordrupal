<?php

/**
 * @file
 * Contains \Drupal\skinr\SkinPluginManager.
 */

namespace Drupal\skinr;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\skinr\Plugin\Discovery\SkinYamlDirectoryDiscovery;

/**
 * Manages plugins for configuration translation mappers.
 */
class SkinPluginManager extends DefaultPluginManager {

  const PLUGIN_PATH = 'skins';

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new SkinPluginManager.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      // Look at all themes and modules.
      // @todo If the list of installed modules and themes is changed, new
      //   definitions are not picked up immediately and obsolete definitions
      //   are not removed, because the list of search directories is only
      //   compiled once in this constructor. The current code only works due to
      //   coincidence: The request that installs (for instance, a new theme)
      //   does not instantiate this plugin manager at the beginning of the
      //   request; when routes are being rebuilt at the end of the request,
      //   this service only happens to get instantiated with the updated list
      //   of installed themes.
      $directories = array();
      foreach ($this->moduleHandler->getModuleList() as $name => $module) {
        $directories[$name] = $module->getPath() . '/' . self::PLUGIN_PATH;
      }
      foreach ($this->themeHandler->listInfo() as $theme) {
        $directories[$theme->getName()] = $theme->getPath() . '/' . self::PLUGIN_PATH;
      }

      // Check skins directories for *.yml files in module/theme roots.
      $this->discovery = new SkinYamlDirectoryDiscovery($directories, 'skin_plugins', 'id');
      $this->discovery->addTranslatableProperty('title');

      // $this->discovery = new InfoHookDecorator($this->discovery, 'skinr_info');
      // $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * Prepare the default status for a skin.
   *
   * @param $skin
   *   Information about a registered skin.
   *
   * @return array
   *   An array of default statuses for each enabled theme.
   */
  protected function addStatusDefaults($skin) {
    $status = array();
    // Retrieve the explicit default status of the registering theme for itself.
    $base_theme_status = NULL;
    if (isset($skin['status'][$skin['source']['name']])) {
      $base_theme_status = $skin['status'][$skin['source']['name']];
    }
    // Retrieve the sub themes of the base theme that registered the skin.
    $sub_themes = array();
    if (isset($skin['source']['sub themes'])) {
      $sub_themes = $skin['source']['sub themes'];
    }
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->listInfo();
    foreach ($themes as $name => $theme) {
      if (!$theme->status) {
        continue;
      }
      // If this theme is a sub theme of the theme that registered the skin, check
      // whether we need to inherit the status of the base theme to the sub theme.
      // This is the case when a skin of a base theme enables itself for the base
      // theme (not knowing about potential sub themes).
      if (isset($base_theme_status) && isset($sub_themes[$name])) {
        $status[$name] = $base_theme_status;
      }
      // Apply global default.
      $status += array($name => $skin['default status']);
    }
    // Lastly, apply all explicit defaults.
    $status = array_merge($status, $skin['status']);

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (!isset($definition['skins']) && !isset($definition['groups'])) {
      throw new InvalidPluginDefinitionException($plugin_id, "The plugin definition of the mapper '$plugin_id' does not contain any groups or skins.");
    }

    // Set defaults for groups.
    if (!empty($definition['groups'])) {
      foreach ($definition['groups'] as &$group) {
        $group += [
          'title' => t('Untitled'),
          'description' => '',
          'weight' => 0,
        ];
      }
    }

    // Set defaults for skins.
    if (!empty($definition['skins'])) {
      /** @var \Drupal\Core\Extension\Extension $extension */
      $extension = $this->moduleHandler->getModule($definition['provider']);
      $extension_info = system_get_info($extension->getType(), $extension->getName());

      foreach ($definition['skins'] as $skin_name => &$skin) {
        $skin += [
          'name' => $skin_name,
          'type' => 'checkboxes',
          'group' => 'general',
          'title' => $skin_name,
          'description' => '',
          'theme hooks' => [],
          'attached' => [],
          'options' => [],
          'default status' => 0,
          'status' => [],
          'weight' => 0,
          'source' => [],
        ];

        // Add source information.
        $skin['source'] = [
          'type' => 'module',
          'name' => $extension->getName(),
          'path' => dirname($definition['path']),
          'pathname' => $definition['path'],
          'version' => $extension_info['version'],
        ];
        /* @todo
        if ($extension->getType() == 'theme') {
          $skin['source'] += [
            'base themes' => $this->themeHandler->getBaseThemes($this->themeHandler->listInfo(), $extension->getName()),
            'sub themes' => isset($sub_themes[$name]) ? $sub_themes[$name] : array(),
          ];
        }
        */

        // Merge in default status for all themes.
        $skin['status'] = $this->addStatusDefaults($skin);

        // Validate skin options.
        foreach ($skin['options'] as $option_name => $option) {
          // Validate class by running it through Html::getClass().
          if (!is_array($skin['options'][$option_name]['class'])) {
            // Raise an error.
            \Drupal::logger('skinr')->warning('The class for option %option in skin %skin needs to be an array.', array('%option' => $option_name, '%skin' => $skin_name));
            // Reset to array to prevent errors.
            $skin['options'][$option_name]['class'] = array();
          }
          foreach ($skin['options'][$option_name]['class'] as $key => $class) {
            $skin['options'][$option_name]['class'][$key] = Html::getClass($class);
          }
        }
      }
    }
  }

}
