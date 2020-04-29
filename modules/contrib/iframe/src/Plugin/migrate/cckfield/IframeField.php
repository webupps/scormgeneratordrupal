<?php

namespace Drupal\iframe\Plugin\migrate\cckfield;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "iframe"
 * )
 */
class IframeField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    // See d6_field_formatter_settings.yml and CckFieldPluginBase
    // processFieldFormatter().
    return [
      'iframe_formatter_default' => 'iframe_default',
      'iframe_formatter_iframeonly' => 'iframe_only',
      'iframe_formatter_asurl' => 'iframe_asurl',
      'iframe_formatter_asurl_withuri' => 'iframe_asurlwithuri',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
      $process = [
        'plugin' => 'd6_cck_iframe',
        'source' => $field_name,
      ];
      $migration->mergeProcessOfProperty($field_name, $process);
  }

}
