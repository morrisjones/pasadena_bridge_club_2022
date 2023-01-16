<?php

namespace Drupal\google_calendar;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_calendar\Entity\GoogleCalendarEvent;
use Drupal\google_calendar\Entity\GoogleCalendarEventInterface;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;
use Drupal\user\Entity\User;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_Events;
USE Google_Service_Calendar_EventDateTime;
use Google_Service_Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use InvalidArgumentException;
use Drupal\google_calendar\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class GoogleCalendarImportEvents.
 *
 * @package Drupal\google_calendar
 */
class GoogleCalendarImportEvents implements GoogleCalendarImportEventsInterface {

  // Handle dates: Google supplies values such as:
  // - "2010-01-09T16:06:35.311Z"
  //
  // ... which is almost but not quite RFC3339/ISO8601: 3 digit fractional
  // seconds is neither RFC3339_EXTENDED nor RFC3339 compatible, although
  // it is a perfectly valid date representation.
  //
  // ISO8601           'Y-m-d\TH:i:sO'  // no : in tz, secs
  // RFC3339           'Y-m-d\TH:i:sP'  // : in tz, secs
  // RFC3339_EXTENDED  'Y-m-d\TH:i:s.vP' // milliseconds
  //
  // Google API:
  // + created timestamp:
  //   - "created": "2010-01-09T16:09:16.000Z",
  // + updated timestamp:
  //   - "updated": "2019-03-29T20:01:48.229Z",
  // + Start date:
  //   - "date": null,
  //   - "dateTime": "2019-04-11T10:45:00+01:00",
  //   - "timeZone": "Europe/London"
  // + End date:
  //   - "date": null,
  //   - "dateTime": "2019-04-11T12:00:00+01:00",
  //   - "timeZone": "Europe/London"
  //
  // Format of dates coming from API:

  /**
   * CRUD is used for created, updated timestamps, and has milliseconds in it.
   */
  protected const DATESTYLE_CRUD = "Y-m-d\TH:i:s.uP";

  /**
   * WHEN is used for start, end times and has second (at most) granularity.
   */
  protected const DATESTYLE_WHEN = "Y-m-d\TH:i:sO";

  /**
   * When TRUE this causes event deletion code to check for other
   * entities with the same Google event id as that being deleted.
   */
  protected const CHECK_DELETE_EVENT = TRUE;

  /**
   * When TRUE this enables calls to debug() which are otherwise ignored.
   */
  protected const VERBOSE_LOGS = FALSE;

  /**
   * @defgroup event update statistics
   * @{
   */

  /**
   * The number of events created during an update.
   *
   * @var int
   */
  protected $createdEvents = 0;

  /**
   * The number of events modified during an update.
   *
   * @var int
   */
  protected $modifyEvents = 0;

  /**
   * The number of events created or modified during an update.
   *
   * @var int
   */
  protected $savedEvents = 0;

  /**
   * The number of events sent by the API during an update.
   *
   * @var int
   */
  protected $newEvents = 0;

  /**
   * The number of Cancel events sent by the API during an update.
   *
   * @var int
   */
  protected $cancelledEvents = 0;

  /**
   * The number of pages of events sent by the API during an update.
   *
   * @var int
   */
  protected $pageCount = 0;

  /**
   * @}
   */

  /**
   * How far into the future should we store events?
   *
   * @var string
   */
  protected $futureHorizon;

  /**
   * How far into the past should changes to events be updated?
   *
   * @var string
   */
  protected $pastHorizon;

  /**
   * How often should we recalculate when 'past' and 'future' are?
   *
   * @var string
   */
  protected $horizonRefresh = 'PT8H';

  /**
   * Defined only during an import(), the calendar entity being imported.
   *
   * @var \Drupal\google_calendar\Entity\GoogleCalendarInterface
   */
  protected $calendarEntity;

  /**
   * Set during import(), the syncToken for the calendar being imported.
   *
   * @var string
   */
  protected $nextSyncToken;

  /**
   * True if, when import() starts, we determine a full resync is required.
   *
   * @var bool
   */
  protected $fullResyncStarted;

  /**
   * Google Calendar API class.
   *
   * @var \Google_Service_Calendar
   */
  protected $service;

  /**
   * Logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Configuration getter.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * How to determine who in Drupal terms is the owner of an event.
   *
   * @var int
   */
  protected $eventOwnership;

  /**
   * Nominated owner of events not having a otherwise-defined owner.
   * How this is used depends on the eventOwnership setting.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $defaultEventOwner;

  /**
   * Owner of events when no mechanism for selecting an owner has been defined.
   *
   * @var int
   */
  protected $anonEventOwner;

  /**
   * EntityTypeManager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Array of geventid => bool values used to record which events we saw during
   * an update. Although it is always filled in, it is only really useful for a
   * full update when it is used to determine which events can be cleaned.
   *
   * @var array
   *   geventid => bool values used to record which events we saw
   */
  protected $touchedEvents;

  /**
   * Policy determining if and how events are cleaned up.
   *
   * @var string
   */
  protected $cleanupPolicy;

  /**
   * Drupal's event dispatcher
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface;
   */
  protected $eventDispatcher;

  /**
   * GoogleCalendarImport constructor.
   *
   * @param \Google_Service_Calendar $googleClient
   *   The Client API interface.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logging factory.
   */
  public function __construct(Google_Service_Calendar $googleClient,
                              ConfigFactory $config,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory,
                              EventDispatcherInterface $eventDispatcher) {

    $this->service = $googleClient;
    $this->config = $config->get('google_calendar.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('google_calendar');
    $this->eventDispatcher = $eventDispatcher;

    $this
      ->resetStats()
      ->setDefaultEventOwner($this->config->get('default_event_owner') ?? '1')
      ->setEventHorizons($this->config->get('horizon_past') ?? '-1 hour',
                         $this->config->get('horizon_future') ?? '1 year 1 day')
      ->setEventHorizonRefresh($this->config->get('horizon_refresh') ?? 'PT8H')
      ->setEventOwnership($this->config->get('entity_ownership') ?? GoogleCalendarImportEventsInterface::OWNER_BYEMAIL)
      ->setCleanupPolicy($this->config->get('cleanup_policy') ?? GoogleCalendarImportEventsInterface::CLEANUP_UNPUB_ALL);

    // Internal:
    $this->fullResyncStarted = FALSE;
    $this->nextSyncToken = NULL;
    $this->touchedEvents = [];
    $this->calendarEntity = NULL;
    $this->anonEventOwner = User::getAnonymousUser()->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.google_client.calendar'),
      $container->get('config.factory'),
      $container->get('entityType.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function setEventHorizons(string $past, string $future): GoogleCalendarImportEventsInterface {
    $this->futureHorizon = $future;
    $this->pastHorizon = $past;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getEventHorizons() {
    return [$this->futureHorizon, $this->pastHorizon];
  }

  /**
   * {@inheritDoc}
   */
  public function setEventHorizonRefresh(string $intv): GoogleCalendarImportEventsInterface {
    $this->horizonRefresh = $intv;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getEventHorizonRefresh() {
    return $this->horizonRefresh;
  }

  /**
   * {@inheritDoc}
   */
  public function setCleanupPolicy(string $policy): GoogleCalendarImportEventsInterface {
    $this->cleanupPolicy = $policy;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getCleanupPolicy() {
    return $this->cleanupPolicy;
  }

  /**
   * {@inheritDoc}
   */
  public function setEventOwnership(string $policy): GoogleCalendarImportEventsInterface {
    $this->eventOwnership = $policy;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getEventOwnership() {
    return $this->eventOwnership;
  }

  /**
   * {@inheritDoc}
   */
  public function setDefaultEventOwner(string $owner): GoogleCalendarImportEventsInterface {
    $this->defaultEventOwner = $owner;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultEventOwner() {
    return $this->defaultEventOwner;
  }

  /**
   * {@inheritDoc}
   */
  public function resetStats(): GoogleCalendarImportEventsInterface {
    $this->newEvents = 0;
    $this->cancelledEvents = 0;
    $this->modifyEvents = 0;
    $this->savedEvents = 0;
    $this->createdEvents = 0;
    $this->pageCount = 0;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getStatNewEvents(): int {
    return $this->newEvents;
  }

  /**
   * {@inheritDoc}
   */
  public function getStatCancelledEvents(): int {
    return $this->cancelledEvents;
  }

  /**
   * {@inheritDoc}
   */
  public function getStatModifyEvents(): int {
    return $this->modifyEvents;
  }

  /**
   * {@inheritDoc}
   */
  public function getStatCreatedEvents(): int {
    return $this->createdEvents;
  }

  /**
   * {@inheritDoc}
   */
  public function getStatSavedEvents(): int {
    return $this->savedEvents;
  }

  /**
   * {@inheritDoc}
   */
  public function getPageCount(): int {
    return $this->pageCount;
  }

  /**
   * {@inheritDoc}
   */
  public function getSyncToken(GoogleCalendarInterface $calendar,
                               bool $ignoreSyncToken): ?string {
    $nextSyncToken = NULL;

    if (!$ignoreSyncToken) {
      /* last sync time is the timestamp of the last full reload */
      $lastSyncTime = $calendar->getLastSyncTime();
      $nextResync = $now = new DateTimeImmutable('now');
      if ($lastSyncTime) {
        $lastSyncTime = new DateTimeImmutable("@$lastSyncTime", NULL);
        $nextResync = $lastSyncTime->add(new DateInterval($this->horizonRefresh));
      }

      // $this->debug(
      // 'Check Resync: time is @now, resync time @resync (@last + @int)',
      // [
      //   '@resync' => $nextResync->format('Y.m.d H:i'),
      //   '@last' => $lastSyncTime->format('Y.m.d H:i'),
      //   '@now' => $now->format('Y.m.d H:i'),
      //   '@int' => $this->horizonRefresh,
      // ]);

      if (!$lastSyncTime || $now > $nextResync) {
        // Force a resync - too long since last time.
        $ignoreSyncToken = TRUE;
        $this->debug(
          'Resync forced: current time is after resync time @resync (@last + @int)',
          [
            '@resync' => !$lastSyncTime ? $now->format('r') : $nextResync->format('r'),
            '@last' => !$lastSyncTime ? 'never' : $lastSyncTime->format('r'),
            '@int' => $this->horizonRefresh,
          ]);
      }
    }

    if (!$ignoreSyncToken) {
      $nextSyncToken = $calendar->getSyncToken();
    }

    return $nextSyncToken;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteSyncToken(GoogleCalendarInterface $entity): int {
    $this->debug('Resync: SyncToken set to NULL.');
    $this->nextSyncToken = NULL;
    $entity->setSyncToken(NULL);
    $entity->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_FORCE_RESYNC);
    return $entity->save();
  }

  /**
   * {@inheritDoc}
   */
  public function import(GoogleCalendarInterface $calendar, $ignoreSyncToken = FALSE): GoogleCalendarInterface {

    // Simple lock to avoid a new sync while already in one.
    if ($calendar->isSyncing()) {
      return $calendar;
    }
    $calendar
      ->setSyncing(TRUE)
      ->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_SYNCING)
      ->save();

    $this->calendarEntity = $calendar;
    $nextPageToken = NULL;
    $calendarId = $calendar->getGoogleCalendarId();
    $restAPICalendar = $this->service->calendars->get($calendarId);

    $this->nextSyncToken = $this->getSyncToken($this->calendarEntity, $ignoreSyncToken);

    $this->fullResyncStarted = empty($this->nextSyncToken);
    if ($this->fullResyncStarted) {
      $this->debug('Full synchronise.');
    }
    else {
      $this->debug('Incremental updates.');
    }

    $this->resetStats();

    // Record of which events we update or delete.
    $this->touchedEvents = [];
    $calendar->preImport($calendarId, $this);

    do {
      $page = $this->getPage($calendarId, $this->nextSyncToken, $nextPageToken);

      if (!$page instanceof Google_Service_Calendar_Events) {
        if ($page === 410) {
          // Gone: Server is requesting we do a full resync.
          $this->doForceResync($calendar);
          $nextPageToken = NULL;
          continue;
        }
        if ($page === 401 || $page === 403 || $page === 407) {
          // Unauthorized.
          $this->calendarEntity
            ->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_AUTH_ERROR);
          break;
        }
        // $page === 408 or $page >= 500: Net or server error.
        $this->calendarEntity
          ->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_NET_ERROR);
        break;
      }

      /** @var \Google_Service_Calendar_Events $items */
      $items = $page->getItems();
      if (count($items) > 0) {
        $this->syncEvents($items, $calendar, $restAPICalendar->getTimeZone());
        $this->pageCount++;
      }

      $nextPageToken = $page->getNextPageToken();
    } while ($nextPageToken);

    // If we got a valid page, see if we got a sync token too (and store it).
    // [ $page could also be scalar == http code if getPage() fails.]
    if ($page instanceof Google_Service_Calendar_Events) {

      $this->nextSyncToken = $page->getNextSyncToken();
      if ($this->nextSyncToken) {

        // Save the token for next time.
        $this->calendarEntity->setSyncToken($this->nextSyncToken);

        // If we started with a full resync, set the time so we know when
        // to restart it.
        if ($this->fullResyncStarted) {
          $this->calendarEntity->setLastSyncTime(time());
        }
      }
    }
    else {
      $this->calendarEntity->setSyncToken(NULL);
    }
    $page = NULL;

    $this->doCleanup($calendar);

    // Update sync result if it's not already been set e.g. by an error.
    if ($this->calendarEntity->getSyncResult() === GoogleCalendarInterface::SYNC_RESULT_SYNCING) {

      // Did we import event updates?
      if ($this->getStatSavedEvents()) {
        $this->calendarEntity->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_EVENTS_IMPORTED);
      }
      else {
        $this->calendarEntity->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_NO_CHANGES);
      }
    }

    $this->info('Sync "@calendar": Read:@new_events New:@created_events Update:@modify_events.',
                [
                  '@calendar' => $calendar->getName(),
                  '@new_events' => $this->getStatNewEvents(),
                  '@modify_events' => $this->getStatModifyEvents(),
                  '@created_events' => $this->getStatCreatedEvents(),
                ]);

    // We always note when we last did something, and the above changes must
    // be saved to persistent storage.
    $this->calendarEntity
      ->setLatestEventTime(time())
      ->setSyncing(FALSE)
      ->save();

    $calendar->postImport($calendarId, $this);
    $this->calendarEntity = NULL;

    return $calendar;
  }

  /**
   * Request a page of calendar events for a calendar-id.
   *
   * @param string $calendarId
   *   Calendar identifier.
   * @param string $syncToken
   *   Token obtained from the nextSyncToken field returned on the last page of
   *   results from the previous list request. Setting this NULL causes a full
   *   resync of all events to be performed.
   * @param string $pageToken
   *   Token specifying which result page to return. Optional.
   *
   * @return int|\Google_Service_Calendar_Events
   *   Either the API data from Google, or an HTTP error code, or FALSE if an
   *   exception was thrown by the API code.
   *
   * @see https://developers.google.com/calendar/v3/reference/events/list
   */
  private function getPage($calendarId, $syncToken = NULL, $pageToken = NULL) {

    if ($syncToken) {
      // "There are several query parameters that cannot be specified together
      //  with nextSyncToken to ensure consistency of the client state."
      //
      // 'showDeleted', 'q', 'timeMin', 'timeMax', 'timeZone', 'updatedMin',
      // 'maxResults'.
      //
      // Essentially, using syncToken bakes in these query values, so if
      // they need to change, you must abandon that ST and restart.
      //
      // We are using timeMax because we expect unlimited recurring events and
      // without it we never complete a single update. Given this, timeMax
      // must change over time for us to see more events in the future. So
      // we need to regularly throw away the ST and restart with a new timeMax.
      $opts = ['syncToken' => $syncToken];
    }
    else {
      // We set singleEvents so to avoid manually processing the exclusions
      // that would be required otherwise; setting maxResults sets how many in
      // a page. timeMin and timeMax define the period we're interested in: if
      // timeMax is left out and a calendar has an unlimited recurring event,
      // there are an unlimited number of events to download!
      $opts = [
        'singleEvents' => TRUE,
        'showDeleted' => TRUE,
        'maxResults' => 128,
        'timeMin' => date(DateTime::RFC3339, strtotime($this->pastHorizon)),
        'timeMax' => date(DateTime::RFC3339, strtotime($this->futureHorizon)),
      ];
    }
    if ($pageToken) {
      $opts['pageToken'] = $pageToken;
    }

    try {
      $response = $this->service->events->listEvents($calendarId, $opts);
    }
    catch (Google_Service_Exception $e) {

      $this->debug('Google exception: gcid: ' . $calendarId
                   . '  code:' . $e->getCode() . '  msg:' . $e->getMessage());

      /*
       * From API Docs: "Sometimes sync tokens are invalidated by the server,
       * for various reasons including token expiration, or changes in related
       * ACLs.
       *
       * In such cases, the server will respond to an incremental request with
       * response code 410. This should trigger a full wipe of the client’s
       * store and a new full sync."
       */
      if ($e->getCode() !== 200) {
        return $e->getCode();
      }
      $response = FALSE;
    }

    return $response;
  }

  /**
   * Given a list of events, add or update the corresponding Calendar Entities.
   *
   * @param array $events
   *   The array of events returned from the Calendar API.
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar entity we are updating.
   * @param string $timezone
   *   The timezone to manupulate event dates in.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function syncEvents(array $events, GoogleCalendarInterface $calendar, string $timezone): void {

    $this->newEvents += count($events);
    $indexedEvents = $this->getIndexedEvents($calendar);

    // Iterate over incoming events and update Drupal entities accordingly.
    /** @var \Google_Service_Calendar_Event $event */
    foreach ($events as $event) {

      // Get the old entity, if it exists.
      /** @var \Drupal\google_calendar\Entity\GoogleCalendarEventInterface $eventEntity */
      $eventEntity = $indexedEvents[$event->getId()] ?? NULL;

      // If the API now states the event was cancelled, delete the entity.
      if ($event->getStatus() === 'cancelled') {
        $this->doDeleteEvent($eventEntity, $event);
        unset($indexedEvents[$event->getId()]);
        $this->cancelledEvents++;
        // Touch = delete.
        $this->touchedEvents[$event->getId()] = FALSE;
        continue;
      }
      // Touch = create/update.
      $this->touchedEvents[$event->getId()] = TRUE;

      // Parse the CRUD event meta-dates.
      $createdDate = $this->parseCRUDDate($event->getCreated());
      $updatedDate = $this->parseCRUDDate($event->getUpdated());

      // Parse event start and end dates.
      $startDate = $this->parseAPIDate($timezone, $event->getStart());
      $endDate = $this->parseAPIDate($timezone, $event->getEnd());
      $user_id = $this->determineEventOwner($event);

      $fields = [
        'user_id' => [
          [
            'target_id' => $user_id,
          ],
        ],
        'name' => [
          [
            'value' => trim($event->getSummary()),
          ],
        ],
        'event_id' => [
          [
            'value' => $event->getId(),
          ],
        ],
        'ical_id' => [
          [
            'value' => $event->getICalUID(),
          ],
        ],
        'calendar' => [
          [
            'target_id' => $calendar->id(),
          ],
        ],
        'start_date' => [
          [
            'value' => $startDate,
          ],
        ],
        'end_date' => [
          [
            'value' => $endDate,
          ],
        ],
        'end_unspecified' => [
          [
            'value' => (bool) $event->getEndTimeUnspecified(),
          ],
        ],
        'google_link' => [
          [
            'uri' => trim($event->getHtmlLink()),
            'title' => trim($event->getSummary()),
            'options' => [],
          ],
        ],
        'description' => [
          [
            'value' => trim($event->getDescription()),
            'format' => !empty($event->getDescription()) ? 'basic_html' : '',
          ],
        ],
        'recurrence_id' => [
          [
            'value' => trim($event->getRecurringEventId()),
          ],
        ],
        'recurrence' => [
          [
            'value' => json_encode($event->getRecurrence()),
          ],
        ],
        'location' => [
          [
            'value' => trim($event->getLocation()),
          ],
        ],
        'locked' => [
          [
            'value' => (bool) $event->getLocked(),
          ],
        ],
        'transparency' => [
          [
            'value' => (bool) $event->getTransparency(),
          ],
        ],
        'visibility' => [
          [
            'value' => (bool) $event->getVisibility(),
          ],
        ],
        'guests_invite_others' => [
          [
            'value' => (bool) $event->getGuestsCanInviteOthers(),
          ],
        ],
        'guests_modify' => [
          [
            'value' => (bool) $event->getGuestsCanModify(),
          ],
        ],
        'guests_see_invitees' => [
          [
            'value' => (bool) $event->getGuestsCanSeeOtherGuests(),
          ],
        ],
        'state' => [
          [
            'value' => $event->getStatus(),
          ],
        ],
        'organizer' => [
          [
            'value' => trim($event->getOrganizer()->getDisplayName()),
          ],
        ],
        'organizer_email' => [
          [
            'value' => trim($event->getOrganizer()->getEmail()),
          ],
        ],
        'creator' => [
          [
            'value' => trim($event->getCreator()->getDisplayName()),
          ],
        ],
        'creator_email' => [
          [
            'value' => trim($event->getCreator()->getEmail()),
          ],
        ],
        'created' => [
          [
            'value' => $createdDate,
          ],
        ],
        'updated' => [
          [
            'value' => $updatedDate,
          ],
        ],
      ];

      if ($eventEntity) {
        // Override all calendar fields with new data
        // None of this should be preserved as Google Calendar is the
        // authoratitative entity.
        foreach ($fields as $key => $value) {
          $eventEntity->set($key, $value);
        }
      }
      else {
        $eventEntity = GoogleCalendarEvent::create($fields);
      }

      // Save it!
      $rc = $eventEntity->save();

      $sync_event = new CalendarEvent($eventEntity, $event);
      $this->eventDispatcher->dispatch($sync_event, CalendarEvent::ENTITY_SYNC);

      if ($rc === SAVED_UPDATED) {
        $this->debug(
          'Modified @eid: @gid "@status" on @date : summary @gsum',
          [
            '@eid' => $eventEntity ? $eventEntity->id() : '-null-',
            '@gid' => $event->getId(),
            '@date' => date('dMy-H:i', $startDate),
            '@gsum' => $event->getSummary(),
            '@status' => $event->getStatus(),
          ]);
        $this->modifyEvents++;
        $this->savedEvents++;
      }
      elseif ($rc === SAVED_NEW) {
        $this->debug(
          'Created @eid: @gid "@status" on @date : summary @gsum',
          [
            '@eid' => $eventEntity ? $eventEntity->id() : '-null-',
            '@gid' => $event->getId(),
            '@date' => date('dMy-H:i', $startDate),
            '@gsum' => $event->getSummary(),
            '@status' => $event->getStatus(),
          ]);
        $this->createdEvents++;
        $this->savedEvents++;
      }
    }
  }

  /**
   * Return the events stored for the given calendar, keyed by their Google id.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar to list.
   *
   * @return array
   *   k-v array of event entities for the given calendar.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getIndexedEvents(GoogleCalendarInterface $calendar): array {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager
      ->getStorage('google_calendar_event');

    // Query to get list of existing events
    $query = $storage
      ->getQuery()
      ->condition('calendar', $calendar->id());
    $eventIds = $query->execute();

    /** @var \Drupal\google_calendar\Entity\GoogleCalendarEventInterface[] $drupalEvents */
    $drupalEvents = GoogleCalendarEvent::loadMultiple($eventIds);

    // Index the existing event nodes by Google Calendar Id for easier lookup.
    $indexedEvents = [];
    foreach ($drupalEvents as $eventEntity) {
      $indexedEvents[$eventEntity->getGoogleEventId()] = $eventEntity;
    }
    return $indexedEvents;
  }

  /**
   * Parse the user event dates.
   *
   * For start and end the 'date' value is set only when there is no time
   * component for the event, so check 'date' first, then if not set get
   * both date and time from 'dateTime'.
   *
   * @param string $timezone
   *   A timezone specifier in a form suitable for \DateTimeZone().
   * @param \Google_Service_Calendar_EventDateTime $event
   *   The data structure holding the date.
   *
   * @return int
   *   Timestamp of the parsed date as Unix epoch seconds, UTC.
   *
   * @throws \InvalidArgumentException
   *   If the date cannot be converted.
   */
  private function parseAPIDate(string $timezone, Google_Service_Calendar_EventDateTime $event): int {
    try {
      if ($event->getDate()) {
        $theDate = new DateTime($event->getDate(), new DateTimeZone($timezone));
      }
      else {
        $theDate = DateTime::createFromFormat(self::DATESTYLE_WHEN, $event->getDateTime());
      }
      $theDate = $theDate->setTimezone(new DateTimeZone('UTC'))
        ->getTimestamp();
    }
    catch (\Exception $e) {
      throw new InvalidArgumentException('Unable to parse date from event: ' . serialize($event), 0, $e);
    }
    return $theDate;
  }

  /**
   * Parse the CRUD event meta-dates.
   *
   * The check for 1970 is because sometimes small integers are seen here,
   * resulting in entity dates in 1970, which really messes things up later.
   *
   * @param string $event
   *   The string holding the date.
   *
   * @return int
   *   Timestamp of the parsed date as Unix epoch seconds, UTC.
   *
   * @throws \InvalidArgumentException
   *   If the date cannot be converted.
   */
  private function parseCRUDDate(string $event): int {
    try {
      $createdDate = DateTime::createFromFormat(self::DATESTYLE_CRUD, $event);
      if (is_object($createdDate) && $createdDate->format('Y') > 1970) {
        $theDate = $createdDate->getTimestamp();
      }
      else {
        $theDate = 0;
      }
    }
    catch (\Exception $e) {
      throw new InvalidArgumentException('Unable to parse date from event: ' . serialize($event), 0, $e);
    }

    return $theDate;
  }

  /**
   * Force a resync of this calendar at the next opportunity.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar to resync.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function doForceResync(GoogleCalendarInterface $calendar) {
    $this->debug('Server requested resync: SyncToken set to NULL.');
    $this->resetStats();
    $this->deleteSyncToken($this->calendarEntity);
    $this->nextSyncToken = NULL;
    $calendar->preResync($calendar->getGoogleCalendarId(), $this);
  }

  /**
   * Determine which Drupal uid to use for this event.
   *
   * Use the eventOwnership control, the defaultOwner config, and the event's
   * owner name and owner email to select the appropriate user-id to return.
   *
   * To save on lookups previously looked up emails and names are cached over
   * multiple calls on the same request.
   *
   * @param \Google_Service_Calendar_Event $event
   *   The Google Event structure to be examined.
   *
   * @return int
   *   The owner selected, or the anonEventOwner if nothing matched.
   */
  private function determineEventOwner(Google_Service_Calendar_Event $event): int {
    static $printed = 0;
    static $useremails = [];
    static $usernames = [];
    static $user_anon = NULL;
    static $user_fixed = NULL;

    // If possible, assign the drupal owner of this entity from the organiser
    // email. Use $useremails,$usernames as a temporary cache.
    if ($user_anon === NULL) {
      /** @var \Drupal\user\Entity\User $user_anon */
      $user_anon = User::load($this->anonEventOwner);
    }
    $user_id = $user_anon->id();
    $name = $user_anon->getEmail();
    $email = $user_anon->getAccountName();
    if ($user_fixed === NULL) {
      /** @var \Drupal\user\Entity\User $user_fixed */
      $user_fixed = User::load($this->defaultEventOwner);
    }

    switch ($this->eventOwnership) {

      case GoogleCalendarImportEventsInterface::OWNER_FIXED:
        [$user_id, $name, $email] =
          $this->assignUserOrDefault(NULL, $user_fixed, $user_anon);
        break;

      case GoogleCalendarImportEventsInterface::OWNER_BYEMAIL:
        $email = $event->getOrganizer()->getEmail() ?? '';
        // Avoid repeated user_load* calls.
        if (array_key_exists($email, $useremails)) {
          $user_id = $useremails[$email];
          $name = $usernames[$user_id];
        }
        else {
          /** @var \Drupal\user\Entity\User $user_email */
          $user_email = NULL;
          if ($email && ($user = user_load_by_mail($email))) {
            $user_email = $user;
          }
          [$user_id, $name, $email] =
            $this->assignUserOrDefault($user_email, $user_fixed, $user_anon);
          $useremails[$email] = $user_id;
          $usernames[$user_id] = $name;
        }
        break;

      case GoogleCalendarImportEventsInterface::OWNER_BYNAME:
        $name = $event->getOrganizer()->getDisplayName() ?? '';
        // Avoid repeated user_load* calls.
        if (array_key_exists($name, $usernames)) {
          $user_id = $usernames[$name];
          $email = $useremails[$user_id];
        }
        else {
          /** @var \Drupal\user\Entity\User $user_name */
          $user_name = NULL;
          if ($name && ($user = user_load_by_name($name))) {
            $user_name = $user;
          }
          [$user_id, $name, $email] =
            $this->assignUserOrDefault($user_name, $user_fixed, $user_anon);
          $usernames[$name] = $user_id;
          $useremails[$user_id] = $email;
        }
        break;
    }

    if ($user_id && $printed < 3) {
      $printed++;
      $this->debug(
        'Associated user-id @uid with event @eid for "@name <@email>"',
        [
          '@eid' => $event->getId(),
          '@uid' => $user_id,
          '@name' => $name,
          '@email' => $email,
        ]);
    }
    return $user_id;
  }

  /**
   * Delete the event entity.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarEventInterface|null $eventEntity
   *   When not NULL, the event entity corresponding to $event.
   * @param \Google_Service_Calendar_Event $event
   *   The API event structure we want to delete.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function doDeleteEvent(?GoogleCalendarEventInterface $eventEntity,
                                 Google_Service_Calendar_Event $event) {

    // Belt-n-braces check that there is only one event ID for this entity ID.
    if (self::CHECK_DELETE_EVENT) {
      $evs = $this->entityTypeManager
        ->getStorage('google_calendar_event')
        ->loadByProperties(['event_id' => $event->getId()]);

      if (is_array($evs)) {
        $evids = [];
        /** @var \Drupal\google_calendar\Entity\GoogleCalendarEventInterface $ev */
        foreach ($evs as $id => $ev) {
          $evids[] = "$id ({$ev->getGoogleEventId()})";
        }

        if (count($evids) > 1) {
          // || $ev->id() !== $eventEntity->id()
          $this->debug('Deleting event @id also found @other.',
                       [
                         '@id' => $event->getId(),
                         '@other' => implode(', ', $evids),
                       ]);
        }
      }
    }

    if ($eventEntity) {
      $rc = $eventEntity->delete();
      $this->debug("Deleted cancelled event {$event->getId()} ({$event->getSummary()}) => {$rc}.");
    }
  }

  /**
   * Return TRUE if a Drupal event exists with the indicated Google event id.
   *
   * NB This function is potentially quite expensive because there is
   * a database lookup.
   *
   * @param string|null $eventId
   *   The Google Calendar Event Id string for the event being checked.
   *
   * @return bool
   *   TRUE if there is a local event, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function eventIdExists(?string $eventId) {
    if ($eventId === NULL || $eventId === '') {
      return FALSE;
    }
    $query = $this->entityTypeManager
      ->getStorage('google_calendar_event')->getQuery();
    $query->condition('event_id', $eventId);
    $res = $query->count()->execute();
    return ($res > 0);
  }

  /**
   * Return the account id, name and email of the owner account.
   *
   * Pick the first of the three provided accounts to return.
   *
   * @param \Drupal\user\Entity\User|null $user
   *   A particular user owning this event.
   * @param \Drupal\user\Entity\User|null $user_fixed
   *   The "fixed user" account, the default user.
   * @param \Drupal\user\Entity\User $user_anon
   *   The anonymous user - last gasp. Not expected to be used.
   *
   * @return array
   *   The account id, name and email to use.
   */
  private function assignUserOrDefault(?User $user, ?User $user_fixed, User $user_anon): array {
    if ($user !== NULL) {
      $user_id = $user->id();
      $name = $user->getAccountName();
      $email = $user->getEmail();
    }
    elseif ($user_fixed !== NULL) {
      $user_id = $user_fixed->id();
      $name = $user_fixed->getAccountName();
      $email = $user_fixed->getEmail();
    }
    else {
      $user_id = $user_anon->id();
      $name = $user_anon->getEmail();
      $email = $user_anon->getAccountName();
    }
    return [$user_id, $name, $email];
  }

  /**
   * Clean up Calendar events that expire but are not cancelled by the API.
   *
   * The showDeleted option in the page flags results in cancellation events
   * where the original calendar has changed, but not when the event has passed.
   * This algorithm uses the touchedEvents array (which notes which events are
   * seen in a download) to ensure that only events Google knows about remain
   * locally.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar entity to check.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function doCleanup(GoogleCalendarInterface $calendar): void {

    /*
     * Only on resync can we use touched events to say something about
     * the events not mentioned in an update, and let's check we got a
     * resync token as well (i.e. Google thinks we got a good download).
     */
    if ($this->fullResyncStarted && $this->nextSyncToken) {
      // Get the list of event entities still associated with this calendar:
      $indexedEvents = $this->getIndexedEvents($calendar);
      $iec = count($indexedEvents);

      foreach ($this->touchedEvents as $touchedEventId => $state) {
        if ($state === FALSE && !empty($indexedEvents[$touchedEventId])) {
          // Error; we thought event was deleted but on reload it's back!
          $this->logger->error('Event @eid marked as deleted but is back! Trying again.',
                               ['@eid' => $touchedEventId]);
          $indexedEvents[$touchedEventId]->delete();
        }
        if ($state === TRUE && empty($indexedEvents[$touchedEventId])) {
          // Error: marked as present, but not actually found.
          $this->logger->error('Event @eid marked as updated but now is not found!',
                               ['@eid' => $touchedEventId]);
        }
        // The real point of this: unset in index the events saw in the update.
        unset($indexedEvents[$touchedEventId]);
      }

      $this->info('Cleanup starts with @iec events, Saw @tou events (@can Cancelled), @ind events to clean.',
                  [
                    '@tou' => count($this->touchedEvents),
                    '@ind' => count($indexedEvents),
                    '@iec' => $iec,
                    '@can' => $this->getStatCancelledEvents(),
                  ]);

      // What is left, therefore, are events not mentioned in the update. As it
      // was supposed to be a full update, these are the ones we should clean.
      $past = strtotime($this->pastHorizon);

      /** @var \Drupal\google_calendar\Entity\GoogleCalendarEventInterface $eventDetail */
      foreach ($indexedEvents as $eventId => $eventDetail) {
        if ($eventDetail->getEndTime() < $past) {
          switch ($this->getCleanupPolicy()) {
            case GoogleCalendarImportEventsInterface::CLEANUP_DEL_OLD:
              $this->debug('Event @eid deleted: OLD cleanup policy (@end < @past)', [
                '@eid' => $eventId,
                '@end' => $eventDetail->getStartTime(),
                '@past' => $past,
              ]);
              $eventDetail->delete();
              break;

            case GoogleCalendarImportEventsInterface::CLEANUP_UNPUB_OLD:
              $this->debug('Event @eid unpublished: OLD cleanup policy (@end < @past)', [
                '@eid' => $eventId,
                '@end' => $eventDetail->getStartTime(),
                '@past' => $past,
              ]);
              $eventDetail->setUnpublished()->save();
              break;

            case GoogleCalendarImportEventsInterface::CLEANUP_NONE:
              $this->debug('Event @eid ignored: NONE cleanup policy', ['@eid' => $eventId]);
              break;
          }
        }
        else {
          $eventDetail->delete();
        }
      }

      $this->touchedEvents = [];
    }
  }

  /**
   * Write an info() log message.
   *
   * @param string $message
   *   The message to print.
   * @param array $context
   *   Context variables.
   */
  protected function info($message, array $context = []): void {
    $this->logger->info($message, $context);
  }

  /**
   * Write a debug() log message only if VERBOSE_LOGS has been set TRUE.
   *
   * @param string $message
   *   The message to print.
   * @param array $context
   *   Context variables.
   */
  protected function debug($message, array $context = []): void {
    if (self::VERBOSE_LOGS) {
      $this->logger->debug($message, $context);
    }
  }

}
