<?php

namespace Drupal\field_collection_views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\field_collection\Entity\FieldCollectionItem;

/**
 * Field handler to flag the field_collection_views_host_id.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("field_collection_views_handler_field_host_entity_id")
 */
class FieldCollectionViewsHandlerFieldHostEntityId extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options.
   *
   * @return array
   *   It will return an array of options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $host_entity_id = 0;
    $item_id = $values->item_id;
    $field_collection_item = FieldCollectionItem::load($item_id);
    $host_entity_id = $field_collection_item->getHostId();

    return $host_entity_id;
  }

}
