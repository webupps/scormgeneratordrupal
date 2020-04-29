<?php /**
 * @file
 * Contains \Drupal\skinr_test\Controller\DefaultController.
 */

namespace Drupal\skinr_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the skinr_test module.
 */
class DefaultController extends ControllerBase {

  public function skinr_test_skinr_current_theme() {
    return array('#markup' => 'Current theme is ' . skinr_current_theme() . '.');
  }

  public function skinr_test_skinr_current_theme_admin_exclude() {
    return array('#markup' => 'Current theme is ' . skinr_current_theme(TRUE) . '.');
  }

  public function skinr_test_hook_dynamic_loading() {
    if (skinr_hook('skinr_test', 'skinr_group_info') && function_exists('skinr_test_skinr_group_info')) {
      return array('#markup' => 'success!');
    }
    return array('#markup' => 'failed!');
  }

  public function skinr_test_skinr_implements_api() {
    $extensions = skinr_implements_api();
    $output = '';
    foreach ($extensions as $extension_name => $extension) {
      $output .= $extension_name . '<br />';
    }
    return array('#markup' => $output);
  }

}
