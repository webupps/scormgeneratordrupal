<?php

/**
 * @file
 * Contains \Drupal\skinr_ui\Plugin\Derivative\SkinrContextualLinks.
 */

namespace Drupal\skinr_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links to edit Skinr settings.
 *
 * @see \Drupal\content_translation\Plugin\Menu\ContextualLink\ContentTranslationContextualLinks
 */
class SkinrContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.skinr_ui.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // @todo Get this to work somehow.
    foreach (array('block', 'node', 'view') as $entity_type_id) {
      $route_name = 'skinr_ui.edit.' . $entity_type_id;

      $this->derivatives[$route_name] = $base_plugin_definition;
      $this->derivatives[$route_name]['route_name'] = 'entity.skin.edit.' . $entity_type_id;
      $this->derivatives[$route_name]['route_parameters'] = array('element_type' => $entity_type_id, 'element' => 'placeholder');
      // @todo Contextual groups do not map to entity types in a predictable
      //   way. See https://drupal.org/node/2134841 to make them predictable.
      $this->derivatives[$route_name]['group'] = $entity_type_id;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
