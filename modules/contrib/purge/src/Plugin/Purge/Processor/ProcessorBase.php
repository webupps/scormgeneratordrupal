<?php

namespace Drupal\purge\Plugin\Purge\Processor;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides base implementations for processors.
 */
abstract class ProcessorBase extends PluginBase implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

}
