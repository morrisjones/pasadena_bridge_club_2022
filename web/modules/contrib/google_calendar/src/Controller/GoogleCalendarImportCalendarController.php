<?php

namespace Drupal\google_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_calendar\GoogleCalendarImportCalendar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class GoogleCalendarImportCalendarController.
 *
 * @package \Drupal\google_calendar
 */
class GoogleCalendarImportCalendarController extends ControllerBase {

  /**
   * The Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface The logger to use for status messages.
   */
  protected $logger;

  /**
   * Google Calendar Importer object.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportCalendar
   */
  protected $calendarImport;

  /**
   * Importer for Calendars.
   *
   * @param \Drupal\google_calendar\GoogleCalendarImportCalendar $google_calendar_import
   *   Constructs a new GoogleCalendarImportEventsController object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Factory for logger notices.
   */
  public function __construct(GoogleCalendarImportCalendar $google_calendar_import, LoggerChannelFactoryInterface $loggerFactory) {
    $this->calendarImport = $google_calendar_import;
    $this->logger = $loggerFactory->get('google_calendar');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.import_calendar'),
      $container->get('logger.factory')
    );
  }

  /**
   * Import a Calendar from Google as a new Entity.
   *
   * @param string $calendar_id
   *   Google ID of the calendar to import.
   *
   *   The code understands entity-id (pure numeric) and google-id
   *   (alphanumeric) because when we create a cal from Google when there
   *   is no entity. The code can also be run to update the local entity
   *   data from Google, hence allowing an entity id at all.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The next page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importCalendar(string $calendar_id): RedirectResponse {

    $google_calendar = $this->calendarImport->import($calendar_id);

    if ($google_calendar) {
      $this->logger->notice(
        $this->t('The <strong>@calendar</strong> Calendar has been imported successfully!',
                 ['@calendar' => $google_calendar->getName()])
      );
    }
    else {
      $this->logger->notice($this->t('The Calendar has not been imported successfully!'));
    }
    return $this->redirect('entity.google_calendar.collection');
  }

}
