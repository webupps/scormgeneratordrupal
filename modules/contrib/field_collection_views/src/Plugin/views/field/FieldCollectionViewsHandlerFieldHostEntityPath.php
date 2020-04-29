<?php

namespace Drupal\field_collection_views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\field_collection\Entity\FieldCollectionItem;

/**
 * Field handler to flag the field_collection_views_host_path.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("field_collection_views_handler_field_host_entity_path")
 */
class FieldCollectionViewsHandlerFieldHostEntityPath extends FieldPluginBase {

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
   *   It will return the array of options.
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

    $host_entity_path = "";
    $item_id = $values->item_id;
    $field_collection_item = FieldCollectionItem::load($item_id);
    $host_entity_id = $field_collection_item->getHostId();
    if (!empty($host_entity_id)) {
      $host_entity_type = $field_collection_item->get('host_type')->value;
      if (!empty($host_entity_type)) {
        $host_entitys = \Drupal::entityManager()
          ->getStorage($host_entity_type)
          ->load($host_entity_id);
        $host_entity_path = $host_entitys->toUrl()->toString();

      }
    }
    return $host_entity_path;
  }

}
