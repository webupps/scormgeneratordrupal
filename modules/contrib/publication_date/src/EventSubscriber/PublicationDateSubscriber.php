<?php

namespace Drupal\publication_date\EventSubscriber;

use Drupal\workbench_moderation\Event\WorkbenchModerationEvents;
use Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Publication Date event subscriber.
 */
class PublicationDateSubscriber implements EventSubscriberInterface {

  /**
   * Handle workbench moderation state transition.
   */
  public function onWorkbenchModerationStateTransition(WorkbenchModerationTransitionEvent $event) {
    if ($event->getEntity()->getEntityTypeId() == 'node') {
      $event->getEntity()->get('published_at')->preSave();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    if (class_exists('\Drupal\workbench_moderation\Event\WorkbenchModerationEvents')) {
      $events[WorkbenchModerationEvents::STATE_TRANSITION] = ['onWorkbenchModerationStateTransition'];
    }
    return $events;
  }

}
