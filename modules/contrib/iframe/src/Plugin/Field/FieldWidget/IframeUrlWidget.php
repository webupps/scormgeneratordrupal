<?php

namespace Drupal\iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Iframe' widget with URL.
 *
 * @FieldWidget(
 *   id = "iframe_url",
 *   label = @Translation("URL only"),
 *   field_types = {"iframe"}
 * )
 */
class IframeUrlWidget extends IframeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $elements = parent::formElement($items, $delta, $element, $form, $form_state);
    $elements['width']['#type'] = 'value'; # dont show, only save default value
    $elements['height']['#type'] = 'value'; # dont show, only save default value
    unset($element['width']['#required']);
    unset($element['height']['#required']);

    return $elements;
  }
 
}

