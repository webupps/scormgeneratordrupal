<?php

/**
 * @file
 * Implements Skinr hooks for views.module.
 */

use Drupal\views\Views;

/**
 * Implements hook_skinr_config_info().
 */
function views_skinr_config_info() {
  return array('view' => t('View'));
}

/**
 * Implements hook_skinr_ui_element_options().
 */
function views_skinr_ui_element_options($theme_name = NULL) {
  $options = array('view' => array());

  $views = \Drupal\views\Views::getEnabledViews();
  // Load all enabled blocks.
  foreach ($views as $view) {
    foreach ($view->get('display') as $display_id => $display) {
      if (empty($display)) {
        continue;
      }
      $name = $view->id() . '__' . $display_id;
      // Fake indentation for view sub-items for better visual result.
      $options['view'][$view->label()][$name] = $display['display_title'];
    }
  }
  ksort($options['view']);

  return $options;
}

/**
 * Implements hook_skinr_ui_element_title().
 */
function views_skinr_ui_element_title($element_type, $element, $theme_name) {
  if ($element_type == 'view') {
    list($name, $display_id) = explode('__', $element, 2);
    if ($view = Views::getView($name)) {
      $view->setDisplay($display_id);
      return t('!view: !display', array('!view' => $view->storage->label(), '!display' => $view->getDisplay()->display['display_title']));
    }
  }
}

/**
 * Implements hook_skinr_theme_hooks().
 */
function views_skinr_theme_hooks($element_type, $element) {
  $theme_hooks = array();

  if ($element_type == 'view') {
    list($name, $display_id) = explode('__', $element, 2);
    if ($view = Views::getView($name)) {
      $view->executeDisplay($display_id);

      // Create list of suggested templates.
      $theme_hooks = $view->buildThemeFunctions('views_view');

      // @todo Determine whether below code is still relevant.
      /*
      // Fetch additional style based suggested templates.
      $additional_hooks = views_theme_functions($display->display_plugin, $view, $display);
      $theme_hooks = array_merge($additional_hooks, $theme_hooks);
      */
    }
    else {
      $theme_hooks[] = 'views_view';
    }
  }

  return $theme_hooks;
}

/**
 * Implements hook_skinr_elements().
 */
function views_skinr_elements($variables, $hook) {
  $elements = array();
  if ($hook == 'views_view') {
    $elements['view'] = array($variables['id'] . '__' . $variables['display_id']);
  }
  return $elements;
}
