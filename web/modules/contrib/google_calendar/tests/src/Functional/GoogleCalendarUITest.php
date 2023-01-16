<?php

namespace Drupal\Tests\google_calendar\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI of the module.
 *
 * @group google_calendar
 */
class GoogleCalendarUITest extends BrowserTestBase {

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
   * Tests access without permission.
   */
  public function testCalendarListButtons() {
    $this->drupalLogin($this->drupalCreateUser(['administer google calendars']));

    $this->drupalGet(Url::fromRoute('entity.google_calendar.collection'));
    $assert_session = $this->assertSession();

    $assert_session->buttonExists('Add Google Calendar');
    $assert_session->buttonExists('Import Calendars');
    $assert_session->buttonExists('Synchronize All');
    $assert_session->buttonExists('Setup Wizard');
    $assert_session->pageTextContainsOnce(
      'There are no google calendar entities yet.');
  }

}
