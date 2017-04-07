<?php

namespace Drupal\multisite_manager\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Domain entity entities.
 */
class DomainEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
