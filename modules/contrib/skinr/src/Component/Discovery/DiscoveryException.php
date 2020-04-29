<?php

/**
 * @file
 * Contains \Drupal\skinr\Component\Discovery\SkinDiscoveryException.
 */

namespace Drupal\skinr\Component\Discovery;

/**
 * Exception thrown during discovery if the data is invalid.
 *
 * Once https://www.drupal.org/node/2671034 goes into core we can eliminate this.
 */
class SkinDiscoveryException extends \RuntimeException {
}
