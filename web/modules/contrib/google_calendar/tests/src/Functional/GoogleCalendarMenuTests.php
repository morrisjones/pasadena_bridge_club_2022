<?php

namespace Drupal\Tests\google_calendar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the google_calendar module.
 *
 * @group google_calendar
 */
class GoogleCalendarMenuTests extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'google_calendar',
    'google_secrets',
  ];

  /**
   * A simple user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access administration pages',
    ));
  }

  /**
   * Tests that the Settings page can be reached.
   */
  public function testConfigPagesExist(): void {
    // Login.
    $this->drupalLogin($this->drupalCreateUser(['administer google calendars']));

    $this->drupalGet('admin/config/google_calendar');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/google_calendar/calendars');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/google_calendar/calendars/import');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the Settings page can be reached.
   */
  public function testContentPagesExist(): void {
    // Login.
    $this->drupalLogin($this->drupalCreateUser(['administer google calendars']));

    $this->drupalGet('admin/content/google_calendar');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/content/google_calendar/add');
    $this->assertSession()->statusCodeEquals(200);
  }
}
