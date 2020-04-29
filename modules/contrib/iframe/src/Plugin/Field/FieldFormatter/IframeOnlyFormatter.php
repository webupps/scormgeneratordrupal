<?php

namespace Drupal\iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldFormatter(
 *  id = "iframe_only",
 *  label = @Translation("Iframe without title"),
 *  field_types = {"iframe"}
 * )
 */
class IframeOnlyFormatter extends IframeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (empty($item->url)) {
        continue;
      }
      if (!isset($item->title)) {
        $item->title = '';
      }
      $elements[$delta] = array(
        '#markup' => IframeDefaultFormatter::iframe_iframe('', $item->url, $item),
        '#allowed_tags' => array('iframe', 'a', 'h3'),
      );
    }
    return $elements;
  }

}
