<?php

namespace Drupal\google_secrets\Tests;

use Drupal\google_secrets\GoogleSecretsStoreInterface;
use Drupal\google_secrets\GoogleSecretsStoreManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests manual password reset.
 *
 * @group password_policy
 */
class GoogleSecretsTest extends BrowserTestBase {

  public static $modules = ['google_secrets'];

  /**
   * Test that we have a service manager.
   */
  public function testServiceManagerExists() {
    $service = $this->container->get('plugin.manager.google_secrets');
    $this->assertInstanceOf(GoogleSecretsStoreManagerInterface::class, $service);
  }

  /**
   * Test that we have a service manager.
   */
  public function testServiceExists() {
    /** @var GoogleSecretsStoreManagerInterface $service */
    $service = $this->container->get('plugin.manager.google_secrets');
    $plugin = $service->createInstance('static_file');
    $this->assertInstanceOf(GoogleSecretsStoreInterface::class, $plugin);
  }

  /**
   * Test something
   */
  public function testYYY() {
  }

}
