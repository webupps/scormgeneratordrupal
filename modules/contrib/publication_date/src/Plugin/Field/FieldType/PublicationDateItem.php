<?php

/**
 * @file
 * Contains \Drupal\publication_date\Plugin\Field\FieldType\PublicationDateItem.
 */

namespace Drupal\publication_date\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'published_at' entity field type.
 *
 * Based on a field of this type, entity types can easily implement the
 * EntityChangedInterface.
 *
 * @FieldType(
 *   id = "published_at",
 *   label = @Translation("Publication date"),
 *   description = @Translation("An entity field containing a UNIX timestamp of when the entity has been last updated."),
 *   no_ui = TRUE,
 *   default_widget = "publication_date_timestamp",
 *   default_formatter = "timestamp",
 *   list_class = "\Drupal\Core\Field\ChangedFieldItemList"
 * )
 *
 * @see \Drupal\Core\Entity\EntityChangedInterface
 */
class PublicationDateItem extends ChangedItem {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    $this->setValue(['value' => NULL, 'published_at_or_now' => REQUEST_TIME], $notify);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['published_at_or_now'] = DataDefinition::create('timestamp')
      ->setLabel(t('Published at or now'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\publication_date\PublishedAtOrNowComputed')
      ->setSetting('source', 'value');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // If no publication date has been set and the entity is unpublished then
    // store the default publication date.
    if (!$this->isPublished() && !$this->value) {
      $this->value = PUBLICATION_DATE_DEFAULT;
    }
    // If the default publication date is set and the entity is published then
    // store the current date.
    elseif ($this->isPublished() && $this->value == PUBLICATION_DATE_DEFAULT) {
      $this->value = REQUEST_TIME;
    }

    // Set the timestamp to request time if it is not set.
    if (!$this->value) {
      $this->value = REQUEST_TIME;
    }
  }

  protected function isPublished() {
    $entity = $this->getEntity();
    return ($entity instanceof EntityPublishedInterface) ? $entity->isPublished() : FALSE;
  }

  /**
   * @inheritDoc
   */
  public function isEmpty() {
    return FALSE;
  }

}
