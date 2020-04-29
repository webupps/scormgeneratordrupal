<?php

/**
 * @file
 * Preprocessors and theme functions for the Skinr UI.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Returns HTML for the skin info listing form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function template_preprocess_skinr_ui_library_details(&$variables) {
  $form = $variables['form'];

  $variables['skin_infos'] = [];
  // Iterate through all the skin_infos, which are children of this element.
  foreach (Element::children($form) as $skin_name) {
    // Stick the key into $skinset for easier access.
    $skin_info = $form[$skin_name];
    unset($skin_info['enable']['#title']);
    // Add the checkbox to allow enabling new skins and to show the
    // status of the skin.
    $skin_info['checkbox'] = $skin_info['enable'];

    // Add the module label and expand/collapse functionality.
    $id = Html::getUniqueId('skin-' . $skin_name);
    $skin_info['id'] = $id;
    $skin_info['enable_id'] = $skin_info['enable']['#id'];

    // @todo Remove early rendering and use safe_join in the Twig template once
    //   https://www.drupal.org/node/2579091 is fixed.
    $renderer = \Drupal::service('renderer');
    $machine_name_render = [
      '#prefix' => '<span dir="ltr" class="table-filter-text-source">',
      '#plain_text' => $skin_name,
      '#suffix' => '</span>',
    ];
    $skin_info['machine_name'] = $renderer->render($machine_name_render);

    if (!empty($skin_info['version'])) {
      $skin_info['version'] = $renderer->render($skin_info['version']);
    }
    if (!empty($skin_info['source'])) {
      $skin_info['source'] = $renderer->render($skin_info['source']);
    }
    if (!empty($skin_info['#theme_hooks'])) {
      $theme_hooks = [
        '#theme' => 'item_list',
        '#items' => $skin_info['#theme_hooks'],
        '#context' => ['list_style' => 'comma-list'],
      ];
      $skin_info['theme_hooks'] = $renderer->render($theme_hooks);
    }

    $skin_info['attributes'] = new Attribute($skin_info['#attributes']);
    $variables['skin_infos'][] = $skin_info;
  }
}
