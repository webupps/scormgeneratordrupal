<?php
/**
 * @file
 * Implements Skinr hooks for node.module.
 */

/**
 * Implements hook_skinr_config_info().
 */
function node_skinr_config_info() {
  return array('node' => t('Content'));
}

/**
 * Implements hook_skinr_ui_element_options().
 */
function node_skinr_ui_element_options($theme_name = NULL) {
  $options = array('node' => array());

  $types = \Drupal\node\Entity\NodeType::loadMultiple();
  foreach ($types as $type) {
    $options['node'][$type->id()] = $type->label();
  }
  asort($options['node']);

  return $options;
}

/**
 * Implements hook_skinr_ui_element_title().
 */
function node_skinr_ui_element_title($element_type, $element, $theme_name) {
  if ($element_type == 'node') {
    $type = node_type_load($element);
    return $type->label();
  }
}

/**
 * Implements hook_skinr_theme_hooks().
 */
function node_skinr_theme_hooks($element_type, $element) {
  $theme_hooks = array();

  if ($element_type == 'node') {
    $theme_hooks = array(
      'node__' . $element,
      'node',
    );
  }

  return $theme_hooks;
}

/**
 * Implements hook_skinr_elements().
 */
function node_skinr_elements($variables, $hook) {
  $elements = array();
  if ($hook == 'node') {
    $elements['node'] = array($variables['node']->bundle());
  }
  return $elements;
}
