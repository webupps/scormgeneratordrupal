<?php

namespace Drupal\views_html_tags\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Contains \Drupal\views_html_tags\Form\ViewsHtmlTagsSettings.
 */
class ViewsHtmlTagsSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_html_tags_settings';
  }

  /**
   * Form to manage views html tags.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['views_html_tags'] = [
      '#type' => 'textarea',
      '#title' => t('HTML tags'),
      '#required' => TRUE,
      '#default_value' => views_html_tags_get_default(),
      '#weight' => 0,
      '#description' => t('Enter HTML tags available in the style settings section of views field configuration, separated by commas.(Example:div,span,p,h1)'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   * Validate function for views_html_tag_settings form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[a-zA-Z0-9,]+$/', $form['views_html_tags']['#value'])) {
      $form_state->setErrorByName('views_html_tags', 'Special characters are not allowed in HTML tags.');
    }
  }

  /**
   * Submit handler for views_html_tag_settings form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['views_html_tags'])) {
      $views_html_tags = trim($form_state->getValue(['views_html_tags']));
      $tags = explode(',', $views_html_tags);
      $vals = [];
      foreach ($tags as $val) {
        $val = trim($val, " ");
        $value = strtolower($val);
        $label = strtoupper($val);
        if ($value) {
          $vals[$value] = $label;
        }
      }
      $config = \Drupal::config('views.settings');
      $config = \Drupal::service('config.factory')->getEditable('views.settings');
      $config->set('field_rewrite_elements', $vals);
      $config->save();
    }
    drupal_set_message(t('The views html tags have been saved.'));
  }

}
