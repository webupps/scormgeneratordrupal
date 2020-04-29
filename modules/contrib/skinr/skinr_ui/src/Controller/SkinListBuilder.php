<?php

/**
 * @file
 * Contains \Drupal\skinr_ui\Controller\SkinListBuilder.
 */

namespace Drupal\skinr_ui\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Returns responses for devel module routes.
 */
class SkinListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
      'data' => $this->t('Machine name'),
      'class' => array('skinr-ui-name'),
    );
    $header['element_type'] = array(
      'data' => $this->t('Type'),
      'class' => array('skinr-ui-type'),
    );
    $header['element'] = array(
      'data' => $this->t('Element'),
      'class' => array('skinr-ui-element'),
    );
    $header['theme'] = array(
      'data' => $this->t('Theme'),
      'class' => array('skinr-ui-theme'),
    );
    $header['label'] = array(
      'data' => $this->t('Skin'),
      'class' => array('skinr-ui-skin'),
    );
    // $header['storage'] = array(
    //   'data' => $this->t('Storage'),
    //   'class' => array('skinr-ui-storage'),
    // );
    $header['status'] = array(
      'data' => $this->t('Status'),
      'class' => array('skinr-ui-status'),
    );

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = array(
      'data' => $this->getLabel($entity),
    );
    $row['element_type'] = array(
      'data' => $entity->elementTypeLabel(),
      'class' => array('skin-table-filter-text-source'),
    );
    $row['element'] = array(
      'data' => $entity->elementLabel(),
      'class' => array('skin-table-filter-text-source'),
    );
    $row['theme'] = array(
      'data' => $entity->themeLabel(),
      'class' => array('skin-table-filter-text-source'),
    );
    $row['skin'] = array(
      'data' => $entity->skinLabel(),
      'class' => array('skin-table-filter-text-source'),
    );
    // $row['storage'] = $entity->getStorage();
    $row['status'] = $entity->status() ? t('Enabled') : t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    // Override edit link.
    // @todo
    if (isset($operations['edit'])) {
      // dpm($operations['edit']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    $list['#type'] = 'container';
    $list['#attributes']['id'] = 'skin-entity-list';

    $list['#attached']['library'][] = 'core/drupal.ajax';
    $list['#attached']['library'][] = 'skinr_ui/skinr_ui.listing';

    $form['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $list['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 40,
      '#placeholder' => $this->t('Filter by view name or description'),
      '#attributes' => array(
        'class' => array('skin-filter-text'),
        'data-table' => '.skin-listing-table',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the skin name or description to filter by.'),
      ),
    );

    $list['enabled']['heading']['#markup'] = '<h2>' . $this->t('Enabled', array(), array('context' => 'Plural')) . '</h2>';
    $list['disabled']['heading']['#markup'] = '<h2>' . $this->t('Disabled', array(), array('context' => 'Plural')) . '</h2>';
    foreach (array('enabled', 'disabled') as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = array('class' => array('skin-list-section', $status));
      $list[$status]['table'] = array(
        '#type' => 'table',
        '#attributes' => array(
          'class' => array('skin-listing-table'),
        ),
        '#header' => $this->buildHeader(),
        '#rows' => array(),
      );
      foreach ($entities as $entity) {
        if ($entity->status() != ($status == 'enabled' ? TRUE : FALSE)) {
          continue;
        }
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    // @todo Use a placeholder for the entity label if this is abstracted to
    // other entity types.
    $list['enabled']['table']['#empty'] = $this->t('There are no enabled skins.');
    $list['disabled']['table']['#empty'] = $this->t('There are no disabled skins.');

    return $list;
  }

}
