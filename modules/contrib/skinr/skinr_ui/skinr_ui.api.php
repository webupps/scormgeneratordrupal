<?php
/**
 * @file
 * This file contains no working PHP code; it exists to provide additional documentation
 * for doxygen as well as to document hooks in the standard Drupal manner.
 */

/**
 * @defgroup skinr_ui Skinr UI API Manual
 *
 * Topics:
 * - @ref skinr_ui_hooks
 */

/**
 * @defgroup skinr_ui_hooks Skinr UIs hooks
 * @{
 * Hooks that can be implemented by other modules in order to implement the
 * Skinr UI API.
 */

/**
 * Provides available element options for a module.
 *
 * These options are used on the 'add skin' form.
 *
 * @param $theme_name
 *   (optional) The name of the theme to provide available options for. If no
 *   theme is given, the current theme is used.
 *
 * @return
 *   An array consting of arrays of element options, keyed by the implementing
 *   module name. Each of these array consists of element name => title pairs.
 *
 * @see skinr_ui_add()
 */
function hook_skinr_ui_element_options($theme_name = NULL) {
  $options['block'] = array(
    'system__main_menu' => 'Main Menu',
    'system__navigation' => 'Navigation',
    'search__form' => 'Search form',
  );

  return $options;
}

/**
 * Returns the human readable title for a given element.
 *
 * @param $module
 *   The module implementing given element.
 * @param $element
 *   An element.
 * @param $theme_name
 *   The name of the theme.
 *
 * @return
 *   A string containing the element's title in human readable form.
 *
 * @see skinr_ui_admin_skins()
 * @see skinr_context_ui_skin_list_subform()
 */
function hook_skinr_ui_element_title($module, $element, $theme_name) {
  if ($module == 'node') {
    $type = node_type_get_type($element);
    return $type->name;
  }
}

/**
 * Alter the list of theme_hooks that are compatible with active skins.
 *
 * @param $skinable_hooks
 *   An array where keys are identical to their value. The value is a theme hook.
 */
function hook_skinr_skinable_hooks_alter(&$skinable_hooks) {
  $skinable_hooks['block__custom'] = 'block__custom';
}

/**
 * Alter skinr administration filters that can be applied to skin configuration objects.
 *
 * @param $filters
 *   An associative array of filters.
 *
 * @todo Functionality no longer exists, but we should re-introduce it.
 */
function hook_skinr_ui_filters_alter(&$filters) {
  // Status filter.
  $filters['status'] = array(
    'title' => t('status'),
    'options' => array(
      '[any]' => t('any'),
      '1' => t('enabled'),
      '0' => t('disabled'),
    ),
  );
}

/**
 * @}
 */
