<?php

namespace Drupal\meta_role\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class MetaRoleEditForm.
 *
 * Provides the edit form for our Robot entity.
 *
 * @ingroup meta_role
 */
class MetaRoleEditForm extends MetaRoleFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update Meta Role');
    return $actions;
  }

}
