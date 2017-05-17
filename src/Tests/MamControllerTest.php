<?php

namespace Drupal\mam\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group mam
 */
class MamControllerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mam'];

  /**
   * A user with permission to Administer Multisite actions manager.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer multisite actions']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the administration page loads with a 200 response.
   */
  public function testMamController() {
    $this->drupalGet(Url::fromRoute('mam.mam_form'));
    $this->assertResponse(200);

    $this->drupalGet(Url::fromRoute('mam.mam_settings_form'));
    $this->assertResponse(200);
  }

}
