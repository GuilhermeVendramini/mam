<?php

namespace Drupal\multisite_manager\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Domain entity entities.
 *
 * @ingroup multisite_manager
 */
interface DomainEntityInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Domain entity name.
   *
   * @return string
   *   Name of the Domain entity.
   */
  public function getName();

  /**
   * Sets the Domain entity name.
   *
   * @param string $name
   *   The Domain entity name.
   *
   * @return \Drupal\multisite_manager\Entity\DomainEntityInterface
   *   The called Domain entity entity.
   */
  public function setName($name);

  /**
   * Gets the Domain entity domain.
   *
   * @return string
   *   Domain of the Domain entity.
   */
  public function getDomain();

  /**
   * Sets the Domain entity domain.
   *
   * @param string $domain
   *   The Domain entity domain.
   *
   * @return \Drupal\multisite_dashboard\Entity\DomainEntityInterface
   *   The called Domain entity entity.
   */
  public function setDomain($domain);

  /**
   * Gets the Domain entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Domain entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Domain entity creation timestamp.
   *
   * @param int $timestamp
   *   The Domain entity creation timestamp.
   *
   * @return \Drupal\multisite_manager\Entity\DomainEntityInterface
   *   The called Domain entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Domain entity published status indicator.
   *
   * Unpublished Domain entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Domain entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Domain entity.
   *
   * @param bool $published
   *   TRUE to set this Domain entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\multisite_manager\Entity\DomainEntityInterface
   *   The called Domain entity entity.
   */
  public function setPublished($published);

}
