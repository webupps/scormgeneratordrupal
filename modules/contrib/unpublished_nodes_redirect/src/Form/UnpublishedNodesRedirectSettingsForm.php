<?php

namespace Drupal\unpublished_nodes_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\unpublished_nodes_redirect\Utils\UnpublishedNodesRedirectUtils as Utils;

/**
 * Configure example settings for this site.
 */
class UnpublishedNodesRedirectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unpublished_nodes_redirect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'unpublished_nodes_redirect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('unpublished_nodes_redirect.settings');

    // Setup a form input for each node type.
    $content_types = Utils::getNodeTypes();
    foreach ($content_types as $key => $type_name) {
      // Fieldset.
      $form[$type_name] = array(
        '#type' => 'fieldset',
        // @todo does the title need to be check plained????
        '#title' => $this->t($type_name),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );

      // Redirect path text input.
      $key_name = Utils::getNodeTypeKey($type_name);
      $form[$type_name][$key_name] = array(
        '#type' => 'textfield',
        '#title' => $this->t('@type internal redirect path', array('@type' => $type_name)),
        '#description' => $this->t('Enter an internal redirect path for the @type content type.', array('@type' => $type_name)),
        '#default_value' => !empty($config->get($key_name)) ? $config->get($key_name) : '',
      );

      // Redirect response code.
      $key_name = Utils::getResponseCodeKey($type_name);
      $form[$type_name][$key_name] = array(
        '#type' => 'select',
        '#title' => t('@type response code', array('@type' => $type_name)),
        '#description' => t('Select a HTTP Response code for the redirect.'),
        '#options' => array(
          0 => t('- Please select a response code -'),
          301 => t('301 - Moved Permanently'),
          302 => t('302 - Found'),
          307 => t('307 - Temporary Redirect'),
        ),
        '#default_value' => !empty($config->get($key_name)) ? $config->get($key_name) : '',
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $content_types = Utils::getNodeTypes();
    foreach ($content_types as $key => $type_name) {
      // Validate whether the provided redirect paths are all internal.
      $element_name = Utils::getNodeTypeKey($type_name);
      $submitted_redirect_path = $form_state->getValue($element_name);
      $test = UrlHelper::isExternal($submitted_redirect_path);

      if (!empty($submitted_redirect_path) && UrlHelper::isExternal($submitted_redirect_path)) {
        $form_state->setErrorByName($element_name, $this->t('The path provided needs to be an internal path.'));
      }

      // If a path is provided, make sure a response code is selected.
      $element_name = Utils::getResponseCodeKey($type_name);
      $submitted_response_code = $form_state->getValue($element_name);
      if (!empty($submitted_redirect_path) && $submitted_response_code == 0) {
        $form_state->setErrorByName($element_name,
          $this->t('Please select a response code for the @type content type.',
            array('@type' => $type_name)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('unpublished_nodes_redirect.settings');
    $content_types = Utils::getNodeTypes();
    foreach ($content_types as $key => $type_name) {
      // Validate whether the provided redirect paths are all internal.
      $element_name = Utils::getNodeTypeKey($type_name);
      $submitted_redirect_path = $form_state->getValue($element_name);
      $config->set($element_name, $submitted_redirect_path);

      // If a path is provided, make sure a response code is selected.
      $element_name = Utils::getResponseCodeKey($type_name);
      $submitted_response_code = $form_state->getValue($element_name);
      $config->set($element_name, $submitted_response_code);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
