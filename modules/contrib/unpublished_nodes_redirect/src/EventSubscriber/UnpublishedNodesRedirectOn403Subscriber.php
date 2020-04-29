<?php

namespace Drupal\unpublished_nodes_redirect\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\unpublished_nodes_redirect\Utils\UnpublishedNodesRedirectUtils as Utils;

/**
 * Unpublished Nodes Redirect On 403 Subscriber class.
 */
class UnpublishedNodesRedirectOn403Subscriber extends HttpExceptionSubscriberBase {

  /**
  * {@inheritdoc}
  */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
  * Fires redirects whenever a 403 meets the criteria for unpublished nodes.
  *
  * @see Utils::checksBeforeRedirect for criteria relating to if a node
  * unpublished node should be redirected.
  *
  * @param GetResponseForExceptionEvent $event
  */
  public function on403(GetResponseForExceptionEvent $event) {
   if ($event->getRequest()->attributes->get('node') != NULL) {
     $nid = \Drupal::routeMatch()->getRawParameter('node');
     $node = \Drupal\node\Entity\Node::load($nid);
     $node_type = $node->getType();
     $is_published = $node->isPublished();
     $config = \Drupal::config('unpublished_nodes_redirect.settings');
     $is_anonymous = \Drupal::currentUser()->isAnonymous();
     // Get the redirect path for this node type.
     $redirect_path = $config->get(Utils::getNodeTypeKey($node_type));
     // Get the response code for this node type.
     $response_code = $config->get(Utils::getResponseCodeKey($node_type));
     if (Utils::checksBeforeRedirect($is_published, $is_anonymous, $redirect_path, $response_code)) {
        $event->setResponse(new RedirectResponse($redirect_path, $response_code));
     }
    }
  }

}
