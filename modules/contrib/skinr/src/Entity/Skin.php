<?php

/**
 * @file
 * Contains \Drupal\skinr\Entity\Skin.
 */

namespace Drupal\skinr\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\skinr\SkinInterface;

/**
 * Defines the Skin entity.
 *
 * @ConfigEntityType(
 *   id = "skin",
 *   label = @Translation("Skin"),
 *   controllers = {
 *     "access" = "Drupal\skinr\SkinAccessController",
 *   },
 *   config_prefix = "skin",
 *   admin_permission = "administer skinr",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "uuid",
 *     "element_type" = "element_type",
 *     "element" = "element",
 *     "theme" = "theme",
 *     "skin" = "skin",
 *     "status" = "status",
 *   },
 * )
 */
class Skin extends ConfigEntityBase implements SkinInterface {

  /**
   * The element type this skin is applied to.
   *
   * @var string
   */
  public $element_type;

  /**
   * The element this skin is applied to.
   *
   * @var string
   */
  public $element;

  /**
   * The theme this skin is applies to.
   *
   * @var string
   */
  public $theme;

  /**
   * The theme this skin is applies to.
   *
   * @var string
   */
  public $skin;

  /**
   * The skin options.
   *
   * @var array
   */
  protected $options = array();

  /**
   * Overrides \Drupal\Core\Entity\Entity::id();
   */
  public function id() {
    return $this->uuid;
  }

  /**
   * Overrides \Drupal\Core\Entity\Entity::label();
   */
  public function label() {
    return $this->uuid;
  }

  /**
   * Returns the element_type label.
   */
  public function elementTypeLabel() {
    $config = skinr_get_config_info();
    return isset($config[$this->element_type]) ? $config[$this->element_type] : '';
  }

  /**
   * Returns the element label.
   *
   * @see hook_skinr_ui_element_title()
   */
  public function elementLabel() {
    $cache = &drupal_static(__FUNCTION__);

    $key = $this->element_type . '__' . $this->element . '__' . $this->theme;
    if (!isset($cache[$key])) {
      $titles = skinr_invoke_all('skinr_ui_element_title', $this->element_type, $this->element, $this->theme);
      $title = $titles ? reset($titles) : $this->element;
      $cache[$key] = $title;
    }

    return $cache[$key];
  }

  /**
   * Returns the theme label.
   */
  public function themeLabel() {
    $cache = &drupal_static(__FUNCTION__);

    if (!isset($cache[$this->theme])) {
      $theme = \Drupal::service('theme_handler')->getTheme($this->theme);
      $cache[$this->theme] = $theme->info['name'];
    }

    return $cache[$this->theme];
  }

  /**
   * Returns the theme label.
   */
  public function skinLabel() {
    $cache = &drupal_static(__FUNCTION__);

    if (!isset($cache[$this->skin])) {
      $skin_infos = skinr_get_skin_info();
      // Add custom info.
      $skin_infos['_additional'] = array(
        'title' => t('Additional'),
      );
      $cache[$this->skin] = $skin_infos[$this->skin]['title'];
    }

    return $cache[$this->skin];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option) {
    if (!isset($this->options[$option])) {
      return NULL;
    }
    return $this->options[$option];
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($option, $value) {
    $this->options[$option] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    // @todo Do we still need this?
    $default_skins = _skinr_skin_get_defaults();

    $storage = SKINR_STORAGE_IN_DATABASE;
    if (isset($default_skins[$this->uuid])) {
      $default_skin = clone($default_skins[$this->uuid]);

      // Make sure skin has same processing as import.
      _skinr_skin_import($default_skin);

      // API version is only used for export.
      unset($default_skin->api_version);

      // Status shouldn't influence overridden.
      $default_skin->status = $this->status;

      $storage = SKINR_STORAGE_IN_CODE;
      if ($default_skin != $this) {
        // Default was overridden.
        $storage = SKINR_STORAGE_IN_CODE_OVERRIDDEN;
      }
    }
    return $storage;
  }

}
