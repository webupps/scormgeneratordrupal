<?php

namespace Drupal\meta_role\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Meta role entity entity.
 *
 * @ingroup meta_role
 * @ConfigEntityType(
 *   id = "meta_role",
 *   label = @Translation("Meta role entity"),
 *   handlers = {
 *     "access" = "Drupal\meta_role\MetaRoleAccessController",
 *     "list_builder" = "Drupal\meta_role\Controller\MetaRoleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\meta_role\Form\MetaRoleAddForm",
 *       "edit" = "Drupal\meta_role\Form\MetaRoleEditForm",
 *       "delete" = "Drupal\meta_role\Form\MetaRoleDeleteForm"
 *     },
 *   },
 *   config_prefix = "meta_role",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "roles_target_id_key" = "roles_target_id_key",
 *     "roles_target_id_value" = "roles_target_id_value"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/meta-role/{meta_role}",
 *     "add-form" = "/admin/structure/meta-role/add",
 *     "edit-form" = "/admin/structure/meta-role/{meta_role}/edit",
 *     "delete-form" = "/admin/structure/meta-role/{meta_role}/delete",
 *     "collection" = "/admin/structure/meta-role"
 *   }
 * )
 */
class MetaRole extends ConfigEntityBase {

  /**
   * The Meta role entity ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Meta role entity label.
   *
   * @var string
   */
  public $label;

  /**
   * The UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The roles_target_id_key
   *
   * @var string
   */
  public $roles_target_id_key;

  /**
   * The roles_target_id_value
   *
   * @var string
   */
  public $roles_target_id_value;

}
