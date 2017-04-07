<?php

/**
 * @file
 * Contains domain_entity.page.inc.
 *
 * Page callback for Domain entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Domain entity templates.
 *
 * Default template: domain_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_domain_entity(array &$variables) {
  // Fetch DomainEntity Entity Object.
  $domain_entity = $variables['elements']['#domain_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
