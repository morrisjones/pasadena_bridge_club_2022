<?php

namespace Drupal\google_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;

use Drupal\google_calendar\GoogleCalendarImportEventsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class GoogleCalendarImportEventsController.
 *
 * @package \Drupal\google_calendar
 */
class GoogleCalendarImportEventsController extends ControllerBase {

  /**
   * The logger to use for status messages.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The importer itself.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   */
  protected $googleCalendarImport;

  /**
   * Constructs a new GoogleCalendarImportEventsController object.
   *
   * @param \Drupal\google_calendar\GoogleCalendarImportEventsInterface $google_calendar_import
   *   The importer.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory for messages.
   */
  public function __construct(GoogleCalendarImportEventsInterface $google_calendar_import, LoggerChannelFactoryInterface $loggerFactory) {
    $this->googleCalendarImport = $google_calendar_import;
    $this->logger = $loggerFactory->get('google_calendar');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.sync_events'),
      $container->get('logger.factory')
    );
  }

  /**
   * Synchronize one Calendar.
   *
   * That is: update any events that have changed since the last update,
   * expire (for example, delete) old or deleted events, and create any
   * events newly added to the calendar.
   *
   * An event 'horizon' is used to determine which events are stored by
   * Drupal, by event date; those too old are expired; those too far in the
   * future are not requested from Google and so not created.
   *
   * The manner of event expiry is considered to be a per-calendar policy
   * decision which can be edited in the calendar entity settings.
   *
   * An incremental update is performed many times a day, to keep events
   * current. Changes noted by Google, such as newly created or deleted events
   * and event updates, are actioned immediately. It is incremental because
   * Google's API _only_ sends the changes to events. It is thus normal for an
   * incremental update to report 0 events received from the API.
   *
   * Google's API requires that filter settings, such as the date range for
   * which events are requested, remain constant when performing incremental
   * updates. This means that a full update must be performed reasonably often
   * in order to advance the horizon dates. A Full update is requested by
   * setting the 'resync' flag TRUE, and during this, all events for that
   * calendar and within the time horizons will be downloaded. During the
   * subsequent processing Drupal's copy of all of them is updated. This is a
   * relatively expensive process in DB I/O bandwidth and in CPU time, so the
   * module permits the frequency to be customised. However, without a full
   * update, no events beyond the current future horizon date will be
   * loaded.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $google_calendar
   *   The calendar entity being synchronized.
   * @param bool $resync
   *   TRUE if a full resync should be performed for this calendar, FALSE
   *   otherwise (i.e. do an incremental sync).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The next page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Google_Service_Exception
   */
  public function synchronizeCalendar(GoogleCalendarInterface $google_calendar, $resync = FALSE): RedirectResponse {

    $this->logger->info('Importing calendar @id: @name',
             [
               '@id' => $google_calendar->id(),
               '@name' => $google_calendar->getName(),
             ]);

    $this->googleCalendarImport->import($google_calendar, $resync);

    $this->logger->info('Events for calendar @id: @name Calendar have been imported successfully',
             [
               '@id' => $google_calendar->id(),
               '@name' => $google_calendar->getName(),
               '@result' => $google_calendar->getSyncResult(),
             ]);

    return $this->redirect('entity.google_calendar.collection');
  }

  /**
   * Synchronize all currently imported Calendars.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The next page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Google_Service_Exception
   */
  public function synchronizeCalendars(): RedirectResponse {

    $foundCalendars = \Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);

    foreach ($foundCalendars as $calendar) {
      $this->logger->info('Importing calendar @id: @name',
               [
                 '@id' => $calendar->id(),
                 '@name' => $calendar->getName(),
               ]);

      $this->googleCalendarImport->import($calendar);

      $this->logger->info('Events for calendar @id: @name Calendar have been imported successfully',
               [
                 '@id' => $calendar->id(),
                 '@name' => $calendar->getName(),
                 '@result' => $calendar->getSyncResult(),
               ]);
    }

    return $this->redirect('entity.google_calendar.collection');
  }

}
