<?php

namespace Drupal\iframe\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'iframe_urlwidthheigth' widget.
 *
 * @FieldWidget(
 *  id = "iframe_urlwidthheight",
 *  label = @Translation("URL with width and height"),
 *  field_types = {"iframe"}
 * )
 */
class IframeUrlwidthheightWidget extends IframeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $elements = parent::formElement($items, $delta, $element, $form, $form_state);

    return $elements;
  }

}

