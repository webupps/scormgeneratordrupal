<?php
/**
 * @file
 * Implements Skinr hooks for block.module.
 */

use Drupal\block\BlockInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Extension\Extension;

/**
 * Implements hook_skinr_config_info().
 */
function block_skinr_config_info() {
  return array('block' => t('Block'));
}

/**
 * Implements hook_skinr_ui_element_options().
 */
function block_skinr_ui_element_options($theme_name = NULL) {
  $options = array('block' => array());

  $theme_handler = \Drupal::service('theme_handler');
  /** @var Extension[]|\stdClass[] $themes */
  $themes = $theme_handler->listInfo();

  $used_themes = array();
  if ($theme_name) {
    // Only process the given theme.
    $used_themes[] = $theme_name;
  }
  else {
    // Process all enabled themes.
    foreach ($themes as $theme) {
      if (!$theme->status) {
        continue;
      }
      $used_themes[] = $theme->getName();
    }
  }

  // Load all enabled blocks.
  foreach ($used_themes as $theme) {
    $blocks = _block_skinr_load_blocks($theme);

    $theme_title = isset($themes[$theme]->info['name']) ? $themes[$theme]->info['name'] : $theme;

    foreach ($blocks as $block_id => $block) {
      $options['block'][$theme_title][$block_id] = $block->label();
    }
  }

  return $options;
}

/**
 * Implements hook_skinr_ui_element_title().
 */
function block_skinr_ui_element_title($element_type, $element, $theme_name) {
  if ($element_type == 'block') {
    $blocks = _block_skinr_load_blocks($theme_name);
    if (isset($blocks[$element])) {
      return $blocks[$element]->label();
    }
  }
}

/**
 * Returns a list of enabled blocks for a given theme.
 *
 * Based on _block_rehash(), but without the blocks table rebuild part.
 *
 * @var string $theme
 *
 * @return Block[]
 *   An array of blocks.
 *
 * @see _block_rehash()
 */
function _block_skinr_load_blocks($theme) {
  $cache = &drupal_static(__FUNCTION__, array());

  if (!isset($cache['blocks'][$theme])) {
    $cache['blocks'][$theme] = array();

    $regions = system_region_list($theme);
    /** @var Block[] $blocks */
    $blocks = \Drupal::entityManager()
      ->getStorage('block')
      ->loadByProperties(array('theme' => $theme));
    foreach ($blocks as $block_id => $block) {
      // Remove any invalid block from the list.
      // @todo Remove this check as part of https://drupal.org/node/1776830.
      if (!$block->getPlugin()) {
        unset($blocks[$block_id]);
        continue;
      }
      $region = $block->get('region');
      $status = $block->status();
      // Skip blocks in invalid regions.
      if (!empty($region) && $region != BlockInterface::BLOCK_REGION_NONE && !isset($regions[$region]) && $status) {
        continue;
      }
      // Skip if not enabled.
      if (!$status) {
        continue;
      }
      $cache['blocks'][$theme][$block_id] = $block;
    }
    asort($cache['blocks'][$theme]);
  }

  return $cache['blocks'][$theme];
}

/**
 * Implements hook_skinr_theme_hooks().
 */
function block_skinr_theme_hooks($module, $element) {
  $theme_hooks = array();

  if ($module == 'block') {
    if ($block = Block::load($element)) {
      $settings = $block->getPlugin()->getConfiguration();
      $theme_hooks[] = 'block__' . $settings['provider'];
    }
    $theme_hooks[] = 'block';
  }

  return $theme_hooks;
}

/**
 * Implements hook_skinr_elements().
 */
function block_skinr_elements($variables, $hook) {
  $elements = array();
  if ($hook == 'block') {
    $elements['block'] = array($variables['elements']['#id']);
  }
  return $elements;
}
