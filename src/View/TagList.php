<?php

/**
 * @file
 * Contains \Drupal\dfp\View\TagList.
 */

namespace Drupal\dfp\View;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of DFP tag entities.
 *
 * @see \Drupal\dfp\View\TagList
 */
class TagList extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Ad Slot');
    $header['size'] = [
      'data' => t('Size(s)'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['block'] = t('Block');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\dfp\Entity\TagInterface $entity */
    $row['title']['data'] = $entity->label();
    $row['size']['data'] = $entity->size();
    $row['block']['data'] = $entity->hasBlock() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No DFP tags available. <a href=":link">Add DFP tag</a>.',
      [':link' => Url::fromRoute('entity.dfp_tag.add_form')->toString()]
    );
    return $build;
  }

}
