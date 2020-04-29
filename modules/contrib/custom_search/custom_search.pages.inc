<?php

/**
 * @file
 * User page callbacks for the custom_search module.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/*
 * Presents links to filter the search results.
 */
function custom_search_preprocess_item_list__search_results(&$variables) {
  $config = Drupal::config('custom_search.settings.results')->getRawData();
  $page_config = FALSE;
  $path = substr(\Drupal::request()->getPathInfo(), 8);

  // Search for the config of this page
  foreach ($config as $c) {
    if (isset($c['path']) && $c['path'] == $path) {
      $page_config = $c;
      break;
    }
  }
  
  $types = array();
  foreach ($variables['items'] as $key => $item) {
    // Render results.
    $variables['items'][$key]['value'] = \Drupal::service('renderer')->render($item['value']);
    // Count results for the filter.
    if ($page_config && $page_config['filter']['position'] != 'disabled') {
      $bundle = $item['value']['#result']['node']->bundle();
      if (!array_key_exists($bundle, $types)) {
        $types[$bundle] = 1;
      }
      else {
        $types[$bundle]++;
      }
    }
  }

  if ($page_config) {

    $variables['filter_position'] = $page_config['filter']['position'];

    // Save # of results for collapsing advanced search.
    \Drupal::state()->set('custom_search.nb_results', count($variables['items']));

    // Generate the filter.
    if ($variables['filter_position'] != 'disabled') {
      // Get search arguments.
      $query = \Drupal::request()->server->get('QUERY_STRING');
      $query = explode('&', $query);
      $keys = array();
      $filters = array();
      foreach ($query as $key) {
        $key_exploded = explode('=', $key);
        // Exclude current types filters.
        if (isset($key_exploded[1])) {
          if (substr(urldecode($key_exploded[0]), 0, 2) == 'f[') {
            if (substr($key_exploded[1], 0, 4) != 'type') {
              $filters[] = urldecode($key_exploded[1]);
            }
          }
          else {
            $keys[$key_exploded[0]] = urldecode($key_exploded[1]);
          }
        }
      }
      for ($f = 0; $f < count($filters); $f++) {
        $keys['f[' . $f . ']'] = $filters[$f];
      }
      // Build menu.
      $node_types = node_type_get_names();
      $filter_items = array();
      $filter_item_url = Url::fromUri('base://search/' . $page_config['path'], array('query' => $keys));
      $filter_items[] = \Drupal::linkGenerator()->generate($page_config['filter']['any'], $filter_item_url);
      foreach ($types as $type => $num_results) {
        $filter_item_url = Url::fromUri('base://search/' . $page_config['path'], array('query' => array_merge($keys, array('f[' . count($filters) . ']' => 'type:' . $type))));
        $filter_items[] = \Drupal::linkGenerator()->generate($node_types[$type], $filter_item_url);
      }
      if (count($filter_items) > 1) {
        $filter_list = array(
          '#theme'      => 'item_list',
          '#title'      => $page_config['filter']['label'],
          '#items'      => $filter_items,
          '#list_type'  => 'ul',
          '#attributes' => array(
            'class' => array(
              'search-filter'
            ),
          ),
        );
        $variables['filter'] = \Drupal::service('renderer')->render($filter_list);
      }
    }

  }
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function custom_search_theme_suggestions_item_list__search_results(array $variables) {
  return array('item_list__custom_search_results__' . $variables['plugin_id']);
}

/*
 * Customisation of the results info.
 */
function custom_search_preprocess_search_result(&$variables) {
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();
  $config = Drupal::config('custom_search.settings.results')->getRawData();
  $page_config = FALSE;
  $current_path = \Drupal::request()->getPathInfo();
  $path = substr($current_path, strpos($current_path, 'search/') + 7);

  // Search for the config of this page
  foreach ($config as $c) {
    if (isset($c['path']) && $c['path'] == $path) {
      $page_config = $c;
      break;
    }
  }

  $result = $variables['result'];
  $variables['url'] = UrlHelper::stripDangerousProtocols($result['link']);
  $variables['title'] = $result['title'];
  if (isset($result['language']) && $result['language'] != $language_interface->getId() && $result['language'] != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $variables['title_attributes']['lang'] = $result['language'];
    $variables['content_attributes']['lang'] = $result['language'];
  }

  $info = array();
  if (!empty($result['plugin_id'])) {
    $info['plugin_id'] = $result['plugin_id'];
  }
  if (!empty($result['user'])) {
    $info['user'] = $result['user'];
  }
  if (!empty($result['date'])) {
    $info['date'] = \Drupal::service('date.formatter')->format($result['date'], 'short');
  }
  if (isset($result['extra']) && is_array($result['extra'])) {
    $info = array_merge($info, $result['extra']);
  }
  // Check for existence. User search does not include snippets.
  $variables['snippet'] = isset($result['snippet']) ? $result['snippet'] : '';
  
  if ($page_config) {
    $info = array();
    if (!empty($result['plugin_id'])) {
      $info['plugin_id'] = Html::escape($result['plugin_id']);
    }
    if (!empty($result['type']) && in_array('type', $page_config['info'], TRUE)) {
      $info['type'] = $result['type'];
    }
    if (!empty($result['user']) && in_array('user', $page_config['info'], TRUE)) {
      $info['user'] = $result['user'];
    }
    if (!empty($result['date']) && in_array('date', $page_config['info'], TRUE)) {
      $info['date'] = format_date($result['date'], 'short');
    }
    if (isset($result['extra']) && is_array($result['extra'])) {
      $info = array_merge($info, $result['extra']);
    }
  }

  // Provide separated and grouped meta information..
  $variables['info_split'] = $info;
  $variables['info'] = array(
    '#type' => 'inline_template',
    '#template' => '{{ info|safe_join(" - ") }}',
    '#context' => array('info' => $info),
  );
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function custom_search_theme_suggestions_search_result(array $variables) {
  return array('custom_search_result__' . $variables['plugin_id']);
}
