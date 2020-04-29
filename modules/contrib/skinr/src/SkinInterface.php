<?php

/**
 * @file
 * Contains \Drupal\skinr\SkinInterface.
 */

namespace Drupal\skinr;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Example entity.
 */
interface SkinInterface extends ConfigEntityInterface {

  /**
   * Indicates the skin is stored in database.
   */
  const SKINR_STORAGE_IN_DATABASE = 0;

  /**
   * Indicates the skin is stored in code.
   */
  const SKINR_STORAGE_IN_CODE = 1;

  /**
   * Indicates the skin is overridden.
   */
  const SKINR_STORAGE_IN_CODE_OVERRIDDEN = 2;

  /**
   * Returns the skin options.
   *
   * @return array
   */
  public function getOptions();

  /**
   * Returns a skin option value.
   *
   * @param string $option
   *   Option to retrieve.
   *
   * @return mixed
   *   The option value.
   */
  public function getOption($option);

  /**
   * Sets the skin options.
   *
   * @param array $options
   *   The array of options.
   *
   * @return $this
   */
  public function setOptions($options);

  /**
   * Sets a skin option value.
   *
   * @param string $option
   *   Option to set.
   * @param mixed $value
   *   Value to set the option to.
   */
  public function setOption($option, $value);

}
