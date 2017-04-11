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
        '#title' => t('Multisite Manager Actions'),
        '#prefix' => '<div class="multisite-manager">',
        '#suffix' => '</div>',
      );
      $build[$id]['manager']['form'] =  \Drupal::formBuilder()->getForm('Drupal\multisite_manager\Form\MultisiteManagerForm', $domain);
    }
  }
  
  // public function enabledModules($domain) {
  //   exec("drush pm-list --type=Module --status=enabled -l $domain --format=list", $output);
  //   $header = array($this->t('Modules'));
  //   $data = $this->tableStyle($header, $output);
    
  //   return($data);
  // }
  
  // public function notInstalledModules($domain) {
  //   exec("drush pm-list --type=Module --status='not installed' -l $domain --format=list", $output);
  //   $header = array($this->t('Modules'));
  //   $data = $this->tableStyle($header, $output);
    
  //   return($data);
  // }
  
  // public function tableStyle($header, $data){

  //   foreach ($data as $module) {
  //     $row[] = (array) $module;
  //   }
    
  //   $output = ['#type' => 'table',
  //     '#header' => $header,
  //     '#rows' => $row,
  //   ];
 
  //   return drupal_render($output);
  // }

}