<?php

namespace Drupal\multisite_manager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Domain entity edit forms.
 *
 * @ingroup multisite_manager
 */
class DomainEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\multisite_manager\Entity\DomainEntity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Domain entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Domain entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.domain_entity.canonical', ['domain_entity' => $entity->id()]);
  }

}
