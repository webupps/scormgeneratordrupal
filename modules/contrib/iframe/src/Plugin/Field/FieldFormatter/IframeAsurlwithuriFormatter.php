<?php

namespace Drupal\iframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @FieldFormatter(
 *  id = "iframe_asurlwithuri",
 *  label = @Translation("A link with the URI as the title"),
 *  field_types = {"iframe"}
 * )
 */
class IframeAsurlwithuriFormatter extends IframeDefaultFormatter {

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
      $linktext = $item->url;
      $elements[$delta] = array(
        '#markup' =>  Link::fromTextAndUrl($linktext, Url::fromUri($item->url, ['title' => $item->title]))->toString(),
        '#allowed_tags' => array('iframe', 'a', 'h3'),
      );
    }
    return $elements;
  }

}
