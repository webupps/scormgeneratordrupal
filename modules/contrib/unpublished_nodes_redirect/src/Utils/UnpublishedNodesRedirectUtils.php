<?php

namespace Drupal\unpublished_nodes_redirect\Utils;

/**
 * Utility class for Unpublished nodes redirect module.
 */
class UnpublishedNodesRedirectUtils {

  /**
   * Helper function to get node types on the site and allow them to be altered.
   *
   * @return array
   *   An array of node types.
   */
  public static function getNodeTypes() {
    // Get all the node types on the site.
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')
      ->loadMultiple();
    $node_types_array = array_keys($node_types);

    // Allow other modules to override this.
    \Drupal::moduleHandler()->alter('unpublished_nodes_redirect_node_types', $node_types_array);

    return $node_types_array;
  }

  /**
   * Gets the node type key used in this module.
   *
   * @param string $node_type
   *   Machine name of content type.
   *
   * @return string
   */
  public static function getNodeTypeKey($node_type) {
    return $node_type . '_unpublished_redirect_path';
  }

  /**
   * Gets the response code key used in this module.
   *
   * @param string $node_type
   *   Machine name of content type.
   *
   * @return string
   */
  public static function getResponseCodeKey($node_type) {
    return $node_type . '_unpublished_redirect_response_code';
  }

  /**
   * Checks that a node meets the criteria for a redirect.
   *
   * @param bool $node_status
   *   Published or Unpublished.
   * @param bool $is_published
   *   A boolean indicating if a user is anonymous.
   * @param string $redirect_path
   *   Path to be used for redirect.
   * @param string $response_code
   *   HTTP response code e.g 301.
   *
   * @return bool
   */
  public static function checksBeforeRedirect($is_published, $is_anonymous, $redirect_path, $response_code) {
    // Node is unpublished, user is not logged in and there is a redirect path
    // and response code.
    if (!$is_published && $is_anonymous && !empty($redirect_path)
        && !empty($response_code) && $response_code != 0) {
      return TRUE;
    }
    return FALSE;
  }

}
