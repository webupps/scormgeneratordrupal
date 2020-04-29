<?php

/**
 * @file
 * Post update functions for Custom Search.
 */

use \Drupal\block\Entity\Block;

/**
 * @addtogroup updates-8.x.1.0.beta3-to-8.x.1.0.beta4
 * @{
 */

/**
 * Resave all instances of Custom Search blocks.
 */
function custom_search_post_update_resave_custom_search_blocks() {
  // 8.x-1.0-beta3 -> 8.x-1.0-beta4: Apply block settings schema changes.
  $block_ids = \Drupal::entityQuery('block')
    ->condition('plugin', 'custom_search')
    ->execute();
  $blocks = \Drupal::entityManager()
    ->getStorage('block')
    ->loadMultiple($block_ids);
  array_walk($blocks, function(Block $block) {
    $block->save();
  });
}

/**
 * @} End of "addtogroup updates-8.x.1.0.beta3-to-8.x.1.0.beta4".
 */