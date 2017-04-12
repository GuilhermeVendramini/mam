<?php

/**
 * @file
 * Contains \Drupal\multisite_manager.
 */

namespace Drupal\multisite_manager;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for Domain Entities.
 *
 * @ingroup Domain
 */
class DomainEntityViewBuilder extends EntityViewBuilder {

  
  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    
    foreach ($entities as $id => $entity) {
      $domain = $entity->get('domain')->__get('value');
      
      $build[$id]['manager'] = array(
        '#type' => 'fieldset',
        '#title' => t('Multisite manager actions'),
        '#prefix' => '<div class="multisite-manager">',
        '#suffix' => '</div>',
      );
      $build[$id]['manager']['form'] =  \Drupal::formBuilder()->getForm('Drupal\multisite_manager\Form\MultisiteManagerForm', $domain);
    }
  }

}