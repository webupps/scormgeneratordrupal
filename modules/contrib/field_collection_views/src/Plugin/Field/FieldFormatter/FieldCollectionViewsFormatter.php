<?php

namespace Drupal\field_collection_views\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_collection\Plugin\Field\FieldFormatter\FieldCollectionLinksFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'views_field_collection_items' formatter.
 *
 * @FieldFormatter(
 *   id = "views_field_collection_items",
 *   label = @Translation("Views field-collection items"),
 *   field_types = {
 *     "field_collection"
 *   }
 * )
 */
class FieldCollectionViewsFormatter extends FieldCollectionLinksFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'name' => 'field_collection_view',
      'display_id' => 'default',
      'add' => t('Add'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $this->getSetting('name'),
      '#description' => t('The machine name of the view to embed.'),
    ];

    $element['display_id'] = [
      '#type' => 'textfield',
      '#title' => t('Display id'),
      '#default_value' => $this->getSetting('display_id'),
      '#description' => t('The display id to embed.'),
    ];

    $element['add'] = [
      '#type' => 'textfield',
      '#title' => t('Add link title'),
      '#default_value' => $this->getSetting('add'),
      '#description' => t('Leave the title empty, to hide the link.'),
    ];
    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $args = '';
    $i = 1;
    foreach ($items as $item) {
      if ($i == 1) {
        $args .= $item->value;
      }
      else {
        $args .= ',' . $item->value;
      }
      $i++;
    }

    $view_name = !empty($this->getSetting('name')) ? $this->getSetting('name') : 'field_collection_view';

    $display_id = !empty($this->getSetting('display_id')) ? $this->getSetting('display_id') : 'default';

    $content = views_embed_view($view_name, $display_id, $args);

    $render = \Drupal::service('renderer')->renderPlain($content)->__toString();

    $element[0] = [
      '#type' => 'markup',
      '#markup' => $render,
    ];
    return $element;
  }

}
