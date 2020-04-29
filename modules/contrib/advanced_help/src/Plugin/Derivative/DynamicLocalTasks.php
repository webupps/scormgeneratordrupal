<?php

namespace Drupal\advanced_help\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    if (\Drupal::moduleHandler()->moduleExists('help')) {
      $this->derivatives['advanced_help.help'] = $base_plugin_definition;
      $this->derivatives['advanced_help.help']['title'] = "Help";
      $this->derivatives['advanced_help.help']['route_name'] = 'help.main';
      $this->derivatives['advanced_help.help']['base_route'] = 'help.main';

      $this->derivatives['help.main'] = $base_plugin_definition;
      $this->derivatives['help.main']['title'] = "Advanced Help";
      $this->derivatives['help.main']['route_name'] = 'advanced_help.main';
      $this->derivatives['help.main']['base_route'] = 'help.main';

      return $this->derivatives;
    }
  }
}
?>