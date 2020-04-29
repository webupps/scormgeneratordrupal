<?php

/**
 * @file
 * Contains \Drupal\session_limit\Form\SessionLimitPage.
 */

namespace Drupal\session_limit\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\SessionManager;
use Drupal\session_limit\Services\SessionLimit;

class SessionLimitForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_limit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var SessionLimit $session_limit */
    $session_limit = \Drupal::service('session_limit');

    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . t('Your active sessions are listed below. You need to choose a session to end.') . '</p>',
    ];

    /** @var SessionManager $session_manager */
    $session_manager = \Drupal::service('session_manager');
    $current_session_id = Crypt::hashBase64($session_manager->getId());

    $user = \Drupal::currentUser();

    $sids = [];

    // @todo get rid of this static db query.
    $result = db_query('SELECT * FROM {sessions} WHERE uid = :uid', [
      ':uid' => $user->id(),
      ]);

    foreach ($result as $obj) {
      $message = $current_session_id == $obj->sid ? t('Your current session.') : '';

      $sids[$obj->sid] = t('<strong>Host:</strong> %host (idle: %time) <b>@message</b>', [
        '%host' => $obj->hostname,
        '@message' => $message,
        '%time' => \Drupal::service("date.formatter")->formatInterval(time() - $obj->timestamp),
      ]);
    }

    $form['sid'] = [
      '#type' => 'radios',
      '#title' => t('Select a session to disconnect.'),
      '#options' => $sids,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Disconnect session'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var SessionManager $session_manager */
    $session_manager = \Drupal::service('session_manager');
    $current_session_id = Crypt::hashBase64($session_manager->getId());

    /** @var SessionLimit $session_limit */
    $session_limit = \Drupal::service('session_limit');
    $sid = $form_state->getValue(['sid']);

    if ($current_session_id == $sid) {
      // @todo the user is not seeing the message below.
      $session_limit->sessionActiveDisconnect(t('You chose to end this session.'));
      $form_state->setRedirect('user.login');
    }
    else {
      $session_limit->sessionDisconnect($sid, t('Your session was deliberately ended from another session.'));
      $form_state->setRedirect('<front>');
    }
  }

}
