<?php

/**
 * @file
 * Provide extras views data alter for field_collection.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function field_collection_views_views_data_alter(array &$data) {
  // HostEntityId.
  $data['field_collection_item']['host_entity_id'] = [
    'title' => t('Host Entity ID'),
    'help' => t('The ID of the Host Entity.'),
    'field' => [
      'title' => t('Host Entity ID'),
      'help' => t('The ID of the Host Entity.'),
      'id' => 'field_collection_views_handler_field_host_entity_id',
    ],
  ];
  // HostEntityPath.
  $data['field_collection_item']['host_entity_path'] = [
    'title' => t('Host Entity Path'),
    'help' => t('The Path of the Host Entity.'),
    'field' => [
      'title' => t('Host Entity Path'),
      'help' => t('The Path of the Host Entity.'),
      'id' => 'field_collection_views_handler_field_host_entity_path',
    ],
  ];

  // CustomFieldCollectionItemId.
  $data['field_collection_item']['field_item_id'] = [
    'title' => t('Custom Field Collection Item Id'),
    'help' => t('Custom field collection id.'),
    'field' => [
      'title' => t('Custom Field Collection Item Id'),
      'help' => t('Custom field collection item id.'),
      'id' => 'field_collection_views_handler_field_collection_id',
    ],
  ];

}
