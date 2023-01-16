<?php

namespace Drupal\Tests\google_calendar\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the accessibility of the module pages.
 *
 * @group google_calendar
 */
class GoogleCalendarAccessTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = ['google_calendar', 'google_secrets'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var string[]
   */
  protected $administer_routes = [
    'google_calendar.settings' => [],
    'google_calendar_event.settings' => [],
    'google_calendar.import_calendars' => [],
    'google_calendar.import_calendar' => ['calendar_id' => 1],
    'google_calendar.sync_all_events' => [],
    'google_calendar.config_wizard' => [],
    'entity.google_calendar.canonical' => ['google_calendar' => 1],
    'entity.google_calendar.edit_form' => ['google_calendar' => 1],
    'entity.google_calendar.delete_form' => ['google_calendar' => 1],
    'entity.google_calendar_event.collection' => [],
    'entity.google_calendar_event.delete_form' => ['google_calendar_event' => 1],
  ];

  /**
   * @var string[]
   */
  protected $editevent_routes = [
    'entity.google_calendar_event.edit_form' => ['google_calendar_event' => 1],
  ];

  /**
   * @var string[]
   */
  protected $syncevent_routes = [
    'google_calendar.sync_all_events' => [],
    'google_calendar.sync_events' => ['google_calendar' => 1, 'resync' => 0],
  ];

  /**
   * Tests access with and without permission.
   */
  public function testAccessUnprivileged() {
    foreach ($this->administer_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
    foreach ($this->editevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
    foreach ($this->syncevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Tests access with and without permission.
   */
  public function testAccessPrivileged_Sync() {
    $this->drupalLogin($this->drupalCreateUser(['manually sync calendar events']));

    foreach ($this->syncevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(200);
    }

    // Edit events doesn't mean edit everything else...
    foreach ($this->editevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
    foreach ($this->administer_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Tests access with and without permission.
   */
  public function testAccessPrivileged_Edit() {
    $this->drupalLogin($this->drupalCreateUser(['edit google calendar events']));

    foreach ($this->editevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(200);
    }

    // Edit events doesn't mean edit everything else...
    foreach ($this->syncevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
    foreach ($this->administer_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Tests access with user that has the correct permission.
   */
  public function testAccessPrivileged_Administer() {
    $this->drupalLogin($this->drupalCreateUser(['administer google calendars']));

    foreach ($this->administer_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(200);
    }

    // Administrators don't automatically get to edit events.
    foreach ($this->syncevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
    foreach ($this->editevent_routes as $route => $params) {
      $this->drupalGet(Url::fromRoute($route, $params));
      $this->assertSession()->statusCodeEquals(403);
    }
  }

}
