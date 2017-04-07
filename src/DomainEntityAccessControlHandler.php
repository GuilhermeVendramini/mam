<?php

namespace Drupal\multisite_manager;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Domain entity entity.
 *
 * @see \Drupal\multisite_manager\Entity\DomainEntity.
 */
class DomainEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\multisite_manager\Entity\DomainEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished domain entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published domain entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit domain entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete domain entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add domain entity entities');
  }

}
