<?php

namespace Drupal\meta_role\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
* Provides a listing of robot entities.
*
* List Controllers provide a list of entities in a tabular form. The base
* class provides most of the rendering logic for us. The key functions
* we need to override are buildHeader() and buildRow(). These control what
* columns are displayed in the table, and how each row is displayed
* respectively.
*
* Drupal locates the list controller by looking for the "list" entry under
* "controllers" in our entity type's annotation. We define the path on which
* the list may be accessed in our module's *.routing.yml file. The key entry
* to look for is "_entity_list". In *.routing.yml, "_entity_list" specifies
* an entity type ID. When a user navigates to the URL for that router item,
* Drupal loads the annotation for that entity type. It looks for the "list"
* entry under "controllers" for the class to load.
*
* @ingroup meta_role
*/
class MetaRoleListBuilder extends ConfigEntityListBuilder {
   
  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'meta_role';
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    /*
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    $row['uuid'] = $entity->uuid;
    $row['roles_target_id_key'] = $entity->roles_target_id_key;
    $row['roles_target_id_value'] = $entity->roles_target_id_value; 
    return $row + parent::buildRow($entity);
    */
    $header['label'] = $this->t('Meta Role');
    $header['machine_name'] = $this->t('Machine Name');
    $header['roles_target_id_key'] = $this->t('roles_target_id_key');
    $header['roles_target_id_value'] = $this->t('roles_target_id_value');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['roles_target_id_key'] = $entity->roles_target_id_key;
    $row['roles_target_id_value'] = $entity->roles_target_id_value;
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * Adds some descriptive text to our entity list.
   *
   * Typically, there's no need to override render(). You may wish to do so,
   * however, if you want to add markup before or after the table.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build[] = parent::render();
    return $build;
  }

}
