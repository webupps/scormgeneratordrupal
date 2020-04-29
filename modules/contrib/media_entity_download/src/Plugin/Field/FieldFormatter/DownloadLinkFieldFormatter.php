<?php

namespace Drupal\media_entity_download\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'media_entity_download_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "media_entity_download_download_link",
 *   label = @Translation("Download link"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class DownloadLinkFieldFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $parent = $items->getParent()->getValue()->id();

    foreach ($items as $delta => $item) {

      $route_parameters = ['media' => $parent];
      if ($delta > 0) {
        $route_parameters['query']['delta'] = $delta;
      }

      $url = Url::fromRoute('media_entity_download.download', $route_parameters);


      // @todo: replace with DI when this issue is fixed: https://www.drupal.org/node/2053415
      $filename = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->load($item->getValue()['target_id'])
        ->getFilename();

      $elements[$delta] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $filename
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() == 'media');
  }

}
