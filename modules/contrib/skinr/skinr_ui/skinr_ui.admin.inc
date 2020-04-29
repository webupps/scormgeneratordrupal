<?php

/**
 * @file
 * Admin page callbacks for the Skinr UI module.
 */

/**
 * Implements hook_skinr_ui_operations().
 */
function skinr_ui_skinr_ui_operations() {
  $operations = array(
    'enable' => array(
      'label' => t('Enable selected skin configuration'),
      'callback' => 'skinr_ui_mass_update',
      'callback arguments' => array('updates' => array('status' => 1)),
    ),
    'disable' => array(
      'label' => t('Disable selected skin configuration'),
      'callback' => 'skinr_ui_mass_update',
      'callback arguments' => array('updates' => array('status' => 0)),
    ),
    'delete' => array(
      'label' => t('Delete selected skin configuration'),
      'callback' => NULL,
    ),
  );
  return $operations;
}

/**
 * List skinr administration filters that can be applied.
 *
 * @return
 *   An array of filters.
 */
function skinr_ui_filters() {
  // Theme filter.
  $theme_handler = \Drupal::service('theme_handler');
  $themes = $theme_handler->listInfo();
  ksort($themes);

  $options = array('[any]' => t('any'));
  foreach ($themes as $theme) {
    if (!$theme->status) {
      continue;
    }
    $options[$theme->name] = $theme->info['name'];
  }

  $filters['theme'] = array(
    'title' => t('theme'),
    'options' => $options,
  );

  // Type filter.
  $filters['module'] = array(
    'title' => t('type'),
    'options' => skinr_get_config_info(),
  );

  // Element filter.
  $elements = db_query("SELECT module, element, theme FROM {skinr_skins} GROUP BY element, module, theme");

  $options = array('[any]' => t('any'));
  foreach ($elements as $element) {
    $skin = entity_create('skin', array('element_type' => $element_type, 'element' => $element, 'theme' => $theme));
    $options[$element->element] = $skin->elementLabel();
  }

  $filters['element'] = array(
    'title' => t('element'),
    'options' => $options,
  );

  // Skin filter.
  $skin_infos = skinr_get_skin_info();

  $options = array('[any]' => t('any'), '_additional' => t('Additional classes'));
  foreach ($skin_infos as $skin_name => $skin_info) {
    $options[$skin_name] = $skin_info['title'];
  }

  $filters['skin'] = array(
    'title' => t('skin'),
    'options' => $options,
  );

  // Status filter.
  $filters['status'] = array(
    'title' => t('status'),
    'options' => array(
      '[any]' => t('any'),
      '1' => t('enabled'),
      '0' => t('disabled'),
    ),
  );

  // Allow modules to add filters.
  drupal_alter('skinr_ui_filters', $filters);

  return $filters;
}

/**
 * Apply filters for skin configuration administration filters based on session.
 *
 * @param $query
 *   A SelectQuery to which the filters should be applied.
 */
function skinr_ui_build_filter_query(SelectQueryInterface $query) {
  // Build query
  $filter_data = isset($_SESSION['skinr_ui_filters']) ? $_SESSION['skinr_ui_filters'] : array();
  foreach ($filter_data as $index => $filter) {
    list($key, $value) = $filter;
    $query->condition('s.' . $key, $value);
  }
}

/**
 * Form builder for the Skinr administration filters form.
 *
 * @ingroup forms
 */
function skinr_ui_filter_form($filters_callback, &$form_state) {
  $form_state['#callback'] = $filters_callback;

  $session = &$_SESSION[$filters_callback];
  $session = is_array($session) ? $session : array();
  $filters = $filters_callback();

  $i = 0;
  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => t('Show only items where'),
    '#theme' => 'exposed_filters__skinr',
  );

  foreach ($session as $filter) {
    list($type, $value) = $filter;
    $value = $filters[$type]['options'][$value];
    $t_args = array('%property' => $filters[$type]['title'], '%value' => $value);

    if ($i++) {
      $form['filters']['current'][] = array('#markup' => t('and where %property is %value', $t_args));
    }
    else {
      $form['filters']['current'][] = array('#markup' => t('where %property is %value', $t_args));
    }
    // Remove the option if it is already being filtered on.
    unset($filters[$type]);
  }

  $form['filters']['status'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('clearfix')),
    '#prefix' => ($i ? '<div class="additional-filters">' . t('and where') . '</div>' : ''),
  );
  $form['filters']['status']['filters'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('filters')),
   );

  foreach ($filters as $key => $filter) {
    $names[$key] = $filter['title'];
    $form['filters']['status']['filters'][$key] = array(
      '#type' => 'select',
      '#title' => $filter['title'],
      '#options' => $filter['options'],
      '#default_value' => '[any]',
    );
  }

  $form['filters']['status']['actions'] = array(
    '#type' => 'actions',
    '#id' => 'skinr-exposed-filters',
    '#attributes' => array('class' => array('container-inline')),
  );
  if (count($filters)) {
    $form['filters']['status']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => count($session) ? t('Refine') : t('Filter'),
    );
  }
  if (count($session)) {
    $form['filters']['status']['actions']['undo'] = array('#type' => 'submit', '#value' => t('Undo'));
    $form['filters']['status']['actions']['reset'] = array('#type' => 'submit', '#value' => t('Reset'));
  }

  drupal_add_js('misc/form.js');

  return $form;
}

/**
 * Mass update skin configurations, updating all skin configurations in the
 * $skins array with the field values in $updates.
 *
 * IMPORTANT NOTE: This function is intended to work when called
 * from a form submit handler. Calling it outside of the form submission
 * process may not work correctly.
 *
 * @param array $skins
 *   Array of skin configuration sids to update.
 * @param array $updates
 *   Array of key/value pairs with skin configuration field names and the
 *   value to update that field to.
 */
function skinr_ui_mass_update($skins, $updates) {
  // We use batch processing to prevent timeout when updating a large number
  // of skins.
  if (count($skins) > 10) {
    $batch = array(
      'operations' => array(
        array('_skinr_ui_mass_update_batch_process', array($skins, $updates))
      ),
      'finished' => '_skinr_ui_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'skinr_ui') . '/skinr_ui.admin.inc',
    );
    batch_set($batch);
  }
  else {
    foreach ($skins as $sid) {
      _skinr_ui_mass_update_helper($sid, $updates);
    }
    drupal_set_message(t('The update has been performed.'));
  }
}

/**
 * Helper function for skin configuration mass updates.
 */
function _skinr_ui_mass_update_helper($sid, $updates) {
  drupal_static_reset('skinr_skin_load_multiple');
  $skin = entity_load('skin', $sid);
  // For efficiency manually store the original skin configuration before
  // applying any changes.
  $skin->original = clone $skin;
  foreach ($updates as $name => $value) {
    $skin->$name = $value;
  }
  skinr_skin_save($skin);
  return $skin;
}

/**
 * Batch operation for skin configuration mass updates.
 */
function _skinr_ui_mass_update_batch_process($skins, $updates, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($skins);
    $context['sandbox']['skins'] = $skins;
  }

  // Process skins in groups of 5.
  $count = min(5, count($context['sandbox']['skins']));
  for ($i = 1; $i <= $count; $i++) {
    // For each sid, load the skin configuration, reset the values, and save it.
    $sid = array_shift($context['sandbox']['skins']);
    $skin = _skinr_ui_mass_update_helper($sid, $updates);

    // Store result for post-processing in the finished callback.
    $context['results'][] = $skin->skin;

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Batch 'finished' callback for skin configuration mass updates.
 */
function _skinr_ui_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The update has been performed.'));
  }
  else {
    drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
    $message = format_plural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $message .= theme('item_list', array('items' => $results));
    drupal_set_message($message);
  }
}

/**
 * Form submission handler for skinr_ui_filter_form().
 */
function skinr_ui_filter_form_submit($form, &$form_state) {
  $filters_callback = $form_state['#callback'];
  $filters = $filters_callback();
  switch ($form_state['values']['op']) {
    case t('Filter'):
    case t('Refine'):
      // Apply every filter that has a choice selected other than 'any'.
      foreach ($filters as $filter => $options) {
        if (isset($form_state['values'][$filter]) && $form_state['values'][$filter] != '[any]') {
          // Flatten the options array to accommodate hierarchical/nested options.
          $flat_options = form_options_flatten($filters[$filter]['options']);
          // Only accept valid selections offered on the dropdown, block bad input.
          if (isset($flat_options[$form_state['values'][$filter]])) {
            $_SESSION[$filters_callback][] = array($filter, $form_state['values'][$filter]);
          }
        }
      }
      break;
    case t('Undo'):
      array_pop($_SESSION[$filters_callback]);
      break;
    case t('Reset'):
      $_SESSION[$filters_callback] = array();
      break;
  }
}

/**
 * Form validation handler for skinr_ui_list().
 *
 * Check if any skinr settings have been selected to perform the chosen
 * 'Update option' on.
 */
function skinr_ui_admin_skins_validate($form, &$form_state) {
  // Error if there are no items to select.
  if (!is_array($form_state['values']['skins']) || !count(array_filter($form_state['values']['skins']))) {
    form_set_error('', t('No items selected.'));
  }
}

/**
 * Form submission handler for skinr_ui_list().
 *
 * Execute the chosen 'Update option' on the selected skinr settings.
 */
function skinr_ui_admin_skins_submit($form, &$form_state) {
  $operations = module_invoke_all('skinr_ui_operations');
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked nodes
  $skins = array_filter($form_state['values']['skins']);
  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($skins), $operation['callback arguments']);
    }
    else {
      $args = array($skins);
    }
    call_user_func_array($function, $args);

    cache_clear_all();
  }
  else {
    // We need to rebuild the form to go to a second step. For example, to
    // show the confirmation form for the deletion of nodes.
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Form builder for the confirmation form when deleting multiple Skinr settings.
 *
 * @param $skins
 *   An array of skins to delete.
 *
 * @ingroup forms
 */
function skinr_ui_multiple_delete_confirm($form, &$form_state, $skins) {
  $theme_handler = \Drupal::service('theme_handler');
  $themes = $theme_handler->listInfo();

  $form['skins'] = array('#prefix' => '<ul>', '#suffix' => '</ul>', '#tree' => TRUE);
  // array_filter returns only elements with TRUE values
  $original_skins = entity_load_multiple('skin', array_keys($skins));
  foreach ($skins as $sid => $value) {
    $form['skins'][$sid] = array(
      '#type' => 'hidden',
      '#value' => $sid,
      '#prefix' => '<li>',
      '#suffix' => t('Skin %skin for element %element of type %type for the %theme theme', array('%skin' => $original_skins[$sid]->skin, '%element' => $original_skins[$sid]->element, '%type' => $original_skins[$sid]->module, '%theme' => isset($themes[$original_skins[$sid]->theme]->info['name']) ? $themes[$original_skins[$sid]->theme]->info['name'] : $original_skins[$sid]->theme)) ."</li>\n",
    );
  }
  $form['operation'] = array('#type' => 'hidden', '#value' => 'delete');
  $form['#submit'][] = 'skinr_ui_multiple_delete_confirm_submit';
  $confirm_question = format_plural(count($skins),
                                  'Are you sure you want to delete this item?',
                                  'Are you sure you want to delete these items?');
  return confirm_form($form,
                    $confirm_question,
                    'admin/structure/skinr', t('This action cannot be undone.'),
                    t('Delete'), t('Cancel'));
}

/**
 * Form submission handler for skinr_ui_multiple_delete_confirm().
 */
function skinr_ui_multiple_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    entity_delete_multiple('skin', array_keys($form_state['values']['skins']));
    $count = count($form_state['values']['skins']);
    if ($count == 1) {
      \Drupal::logger('skinr')->notice('Deleted 1 skin configuration.');
    }
    else {
      \Drupal::logger('skinr')->notice('Deleted @count skin configurations.', array('@count' => $count));
    }
    drupal_set_message(format_plural($count, 'Deleted 1 skin configuration.', 'Deleted @count skin configurations.'));
  }
  $form_state['redirect'] = 'admin/structure/skinr';
}

function skinr_ui_get_element_title($module, $element, $theme = NULL) {
  static $current_theme;
  static $themes;

  if (empty($theme)) {
    if (!isset($current_theme)) {
      $current_theme = skinr_current_theme(TRUE);
    }
    $theme = $current_theme;
  }

  $title = skinr_invoke_all('skinr_ui_element_title', $module, $element, $theme);
  $title = reset($title);

  if (!$title) {
    if (!isset($themes)) {
      $theme_handler = \Drupal::service('theme_handler');
      $themes = $theme_handler->listInfo();
    }

    foreach ($themes as $t) {
      if (!empty($t->info['hidden'])) {
        continue;
      }

      if ($t->name == $theme) {
        // Already tried this one.
        continue;
      }

      if ($title = skinr_invoke_all('skinr_ui_element_title', $module, $element, $t->name)) {
        $title = reset($title);
        break;
      }
    }
  }

  return $title;
}
