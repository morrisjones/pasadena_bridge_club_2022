<?php

namespace Drupal\google_calendar\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\UnstructuredListData;
use Drupal;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;
use Drupal\google_secrets\GoogleSecretsStoreException;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Google_Service_Calendar_Event;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/*
 * For commands that are parts of modules, Drush expects to find command files
 * in __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a
 * drush.services.yml in root of your module like this module does.
 */

/**
 * Class GoogleCalendarCommands.
 *
 * @package Drupal\google_calendar\Commands
 */
class GoogleCalendarCommands extends DrushCommands {

  /**
   * Run the only google calendar cron hook.
   *
   * Use when you want to keep the calendar updated more often than other
   * cron tasks.
   *
   * @param array $options
   *   Command args.
   *
   * @option full
   *   Perform a full resync of the calendars.
   *
   * @command gcal:update
   * @aliases gcal-update, gcal:cron, gcal-cron
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function cron(array $options = ['full' => FALSE]) {

    $full_resync = $options['full'];

    $logger = \Drupal::logger('google_calendar');
    $importer = \Drupal::service('google_calendar.sync_events');

    // Fetch published calendars.
    $foundCalendars = \Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);

    /** @var \Drupal\google_calendar\Entity\GoogleCalendar $calendar */
    foreach ($foundCalendars as $calendar) {
      $this->output()
        ->writeln(dt('Update calendar "@name" (@id).',
                     [
                       '@name' => $calendar->getName(),
                       '@id' => $calendar->id(),
                     ]
                  ));

      $importer->import($calendar, $full_resync);
    }
  }

  /**
   * Show all Calendars visible for the account, using the Google API directly.
   *
   * @command gcal:listCalendars
   * @aliases gcal:lc, gcal-lc
   *
   * @field-labels
   *   id: ID
   *   name: Name
   *   import: Imported
   *   token: Sync token
   *   sync: Sync result
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Table of calendars.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function calendarList(): RowsOfFields {
    try {
      /** @var \Drupal\google_calendar\GoogleCalendarClientFactory $client_factory */
      $client_factory = Drupal::service('google_calendar.google_client.factory');
    }
    catch (GoogleSecretsStoreException $e) {
      $this->output()->writeln(dt('Google Authentication not configured.'));
      return new RowsOfFields();
    }

    $client = $client_factory->getCalendarClient();
    $this
      ->output()
      ->writeln(dt('Using Google Client library: @version', [
        '@version' => $client->getClient()->getLibraryVersion(),
      ]));

    $list = $client->calendarList->listCalendarList();
    $remote_calendars = $list->getItems();

    $entities = Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties();

    $result = [];
    $entity_index = [];
    /** @var \Drupal\google_calendar\GoogleCalendarInterface $entity */
    foreach ($entities as $entity) {
      $entity_index[$entity->getGoogleCalendarId()] = $entity;
    }
    $remote_index = [];
    /** @var \Google_Service_Calendar_CalendarListEntry $remote */
    foreach ($remote_calendars as $remote) {
      $remote_index[$remote->getId()] = $remote;
    }

    // Run through the remote entities to find any that are not local.

    /** @var \Google_Service_Calendar_CalendarListEntry $remote */
    foreach ($remote_calendars as $remote) {
      if (empty($entity_index[$remote->getId()])) {
        // A remote calendar that has not been locally imported.
        $entity_index[$remote->getId()];
        $cal = [
          'id' => $remote->getId(),
          'name' => $remote->getSummary(),
          'desc' => $remote->getDescription(),
          'locn' => $remote->getLocation(),
          'token' => '-',
          'sync' => '-',
          'colour' => $remote->getForegroundColor() . ' on ' . $remote->getBackgroundColor(),
        ];
        $cal['import'] = 'Remote ONLY';
        $result[] = $cal;
      }
    }

    // Now include the local entities -- and report any that were not remote.

    /** @var \Drupal\google_calendar\Entity\GoogleCalendarInterface $entity */
    foreach ($entities as $entity) {
      $cal = [
        'id' => $entity->getGoogleCalendarId(),
        'name' => $entity->getName(),
        'desc' => $entity->getDescription(),
        'locn' => $entity->getLocation(),
        'token' => $entity->getSyncToken() ? 'Yes' : 'No',
        'sync' => $entity->getSyncResult(),
        'colour' => '-',
      ];
      if (empty($remote_index[$entity->getGoogleCalendarId()])) {
        // A local calendar that doesn't exist in the remote account.
        $cal['import'] = 'Local ONLY';
      }
      else {
        // A local calendar also in the remote account.
        $cal['import'] = 'Yes';
      }
      $result[] = $cal;
    }

    return new RowsOfFields($result);
  }

  /**
   * List the events for a Calendar using the Google API directly.
   *
   * @param string $calendar_id
   *   The Google Calendar-ID of the calendar to show events for.
   * @param string $event_id
   *   The event id of the event to display (optional).
   * @param array $options
   *   Command options.
   *
   * @field-labels
   *   id: ID
   *   name: Name
   *   desc: Description
   *   locn: Location
   *   start: Start Date
   *   end: End Date
   * @option raw
   *   Return the data in the form supplied by Google.
   * @option limit
   *   Restrict the output to this many events. If not specified, limit to 20.
   *
   * @command gcal:listEvents
   * @aliases gcal:lev,gcal-lev
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|
   *         \Consolidation\OutputFormatters\StructuredData\UnstructuredListData
   *   Table of events.
   *
   * @throws \Exception
   *   Exception if the Start or End dates are badly defined.
   */
  public function eventList($calendar_id, $event_id = NULL, array $options = [
    'format' => 'table',
    'limit' => 20,
    'raw' => FALSE,
  ]) {
    try {
      /** @var \Drupal\google_calendar\GoogleCalendarClientFactory $client_factory */
      $client_factory = Drupal::service('google_calendar.google_client.factory');
    }
    catch (GoogleSecretsStoreException $e) {
      $this->output()->writeln(dt('Google Authentication not configured.'));
      return new RowsOfFields();
    }

    $client = $client_factory->getCalendarClient($options['store']);
    $this
      ->output()
      ->writeln(dt('Using Google Client library:') . $client->getClient()
                  ->getLibraryVersion());

    $optParams = [
      'maxResults' => is_numeric($options['limit']) ?: 20,
      'orderBy' => 'startTime',
      'singleEvents' => TRUE,
      // Only future events:
      'timeMin' => date('c'),
    ];

    if ($event_id) {
      $event = $client->events->get($calendar_id, $event_id, []);
      if ($options['raw']) {
        return new UnstructuredListData($event);
      }
      $result[] = $this->formatEvent($event);
    }
    else {
      $list = $client->events->listEvents($calendar_id, $optParams);
      /** @var \Google_Service_Calendar_Events[] $items */
      $items = $list->getItems();
      if ($options['raw']) {
        return new UnstructuredListData($items);
      }
      $result = [];
      /** @var \Google_Service_Calendar_Event $event */
      foreach ($items as $event) {
        $result[] = $this->formatEvent($event);
      }
    }
    return new RowsOfFields($result);
  }

  /**
   * Format a Google api calendar event object for console output.
   *
   * @param \Google_Service_Calendar_Event $event
   *   The event to format.
   *
   * @return array
   *   A k-v list of fields extracted from the event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function formatEvent(Google_Service_Calendar_Event $event) {
    $startdate = $event->getStart();
    $start = $startdate->getDateTime() ?? $startdate->getDate();
    $start = new \DateTime($start);
    $enddate = $event->getEnd();
    $end = $startdate->getDateTime() ?? $enddate->getDate();
    $end = new \DateTime($end);

    $ev_id = $event->getId();
    $entities = Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);

    $ev = [
      'id' => $ev_id,
      'name' => $event->getSummary(),
      'desc' => $event->getDescription(),
      'locn' => $event->getLocation(),
      'start' => $start->format('d-m-y h:m'),
      'end' => $end->format('d-m-y h:m'),
    ];
    return $ev;
  }

  /**
   * List the events for a particular calendar.
   *
   * @param string $calendar_id
   *   Google calendar ID for the calendar to update.
   * @param array $options
   *   Command option list.
   *
   * @command gcal:importEvents
   * @aliases gcal:iev,gcal-iev
   *
   * @option full
   *   Perform a full resync of the calendar.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   A list of stats on the import.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Google_Service_Exception
   */
  public function importEvents($calendar_id, array $options = ['full' => FALSE]): PropertyList {
    $pl = [];
    /** @var Drupal\google_calendar\GoogleCalendarImportEventsInterface $importer */
    $importer = Drupal::service('google_calendar.sync_events');

    $full_resync = $options['full'];

    if ($calendar_id) {
      // Import this calendar.
      $entities = Drupal::entityTypeManager()
        ->getStorage('google_calendar')
        ->loadByProperties(['calendar_id' => $calendar_id]);
      $pl['found'] = count($entities);
    }
    else {
      // Import for all active calendars.
      $entities = Drupal::entityTypeManager()
        ->getStorage('google_calendar')
        ->loadByProperties(['status' => 1]);
      $pl['found'] = count($entities);
    }

    if (is_array($entities)) {
      foreach ($entities as $entity) {
        $this->output()->writeln(dt('Updating calendar entity @label(@cal)',
                                    [
                                      '@cal' => $entity->id(),
                                      '@label' => $entity->getName(),
                                    ]));

        $result = $importer->import($entity, $full_resync);
        if ($result) {
          $pl['created'] += $importer->getStatCreatedEvents();
          $pl['updated'] += $importer->getStatModifyEvents();
          $pl['saved'] += $importer->getStatSavedEvents();
        }
        else {
          $this->output()->writeln(dt('... Update failed.'));
          $pl['failed']++;
        }
      }
    }

    return new PropertyList($pl);
  }

  /**
   * Display stored secrets.
   *
   * @command gcal:secrets
   * @aliases gcal-secrets,gcal:sec,gcal-sec
   *
   * @usage drush gcal:secrets
   *   Show what the configured secrets files contain.
   *
   * @return PropertyList
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function secrets(): PropertyList {

    $config = \Drupal::config('google_calendar.settings');
    $store_type = $config->get('client_secret_type') ?? 'static_file';

    /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface $store */
    $store = Drupal::service('google_calendar.google_client.store');
    $filepath = $store->getFilePath();

    $pl = [
      dt('Configured store') => $store_type,
      dt('Storeage Class') => get_class($store),
      dt('File Path') => $filepath,
      dt('File is Readable') => is_readable($filepath) ? 'Yes' : 'No',
    ];

    $secrets = $store->get();
    $pl[dt('Service Secrets')] = $secrets;

    return new PropertyList($pl);
  }

  /**
   * Local function to force a resync by clearing the sync token.
   *
   * @param array $calfilter
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function deleteSyncToken(array $calfilter) {
    $entities = Drupal::entityTypeManager()
      ->getStorage('google_calendar')
      ->loadByProperties(['status' => 1]);

    /** @var \Drupal\google_calendar\Entity\GoogleCalendar $entity */
    foreach ($entities as $entity) {
      $entity->setSyncToken('');
      $entity->setSyncResult(GoogleCalendarInterface::SYNC_RESULT_FORCE_RESYNC);
      $entity->save();
    }
  }

  /**
   * Delete the events for a calendar or for all calendars.
   *
   * @param array $options
   *   Command options.
   *
   * @command gcal:deleteevents
   * @aliases gcal-deleteevents,gcal:devt,gcal-devt
   *
   * @option events ID-LIST
   *   Delete specific events by entity id (whether published or not).
   * @option all
   *   Delete all published events, or when combined with --calendar, all in
   *   that calendar.
   * @option calendar ID
   *   Select the specific calendar to operate on. Use the calendar entity ID.
   * @option dry-run
   *   Indicate what would be deleted but do not delete anything.
   * @option unpublished
   *   As for 'all' but only unpublished events are included. Note: unpublishing
   *   the calendar would not affect whether the event was unpublished.
   *
   * @usage drush gcal-delevents --all
   *   Delete all events for all calendars.
   * @usage drush gcal-devt --unpublished --calendar=2
   *   Delete all unpublished events for the calendar named My Calendar.
   * @usage drush gcal-devt --events=45,220,5212 --calendar=2
   *   Delete the specified events for the calendar 2.
   *
   * @return string
   *   User confirmation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function deleteEvents(array $options = [
    'events' => NULL,
    'calendar' => NULL,
    'unpublished' => FALSE,
    'all' => FALSE,
    'dry-run' => FALSE,
  ]): string {
    $filter = [];
    // Set to Published.
    $filter['status'] = TRUE;

    $storage = Drupal::entityTypeManager()
      ->getStorage('google_calendar');

    if (empty($options['all']) && empty($options['events'])) {
      throw new MissingOptionsException(
        dt('No entities specified, use --events or --all'), ['all', 'events']);
    }

    if (!empty($options['calendar'])) {

      // Delete these specific calendars.
      $callist = explode(',', $options['calendar']);
      $callist = array_map('trim', $callist);
      if (!empty($callist)) {

        // Match simple integers as calendar entity ids, "email" address as
        // google calendar IDs, and anything else as a calendar's entity name.
        $query = $storage->getQuery('OR');
        foreach ($callist as $cal) {
          if (is_numeric($cal)) {
            $query->condition('calendar', $cal);
          }
          elseif (preg_match('/^[-+_\w_]+@[-_\w](\.[-_\w]+)+/', $cal)) {
            $query->condition('calendar_id', $cal);
          }
          else {
            $query->condition('name', $cal);
          }
        }
        $ids = $query->execute();
        $filter['calendar'] = array_values($ids);

        // If the arrays are not the same length, one of the calendar ids
        // didn't match.
        if (count($callist) !== count($ids)) {
          throw new InvalidOptionException(
            dt('One or more Calendar IDs could not be loaded.'));
        }
      }
      else {
        throw new InvalidOptionException(
          dt('Calendar option specified but no calendar IDs found.'));
      }
    }

    // "--all" means all-published (no unpub).
    if (!empty($options['all'])) {
      $filter['status'] = TRUE;
    }

    // "--all --unpublished" means all-unpublished (no pub).
    if (!empty($options['unpublished'])) {
      $filter['status'] = FALSE;
    }

    // --events lists the entity IDs of the events to delete.
    if (!empty($options['events'])) {
      // Delete these specific events:
      $eventlist = explode(',', $options['events']);
      $eventlist = array_map('trim', $eventlist);
      if (!empty($eventlist)) {
        $filter['id'] = $eventlist;
      }
      else {
        throw new InvalidOptionException(
          dt('Events option specified but no event IDs found.'));
      }
    }

    echo 'Loading affected calendar events ... ';
    $entities = Drupal::entityTypeManager()
      ->getStorage('google_calendar_event')
      ->loadByProperties($filter);
    $event_count = count($entities);
    echo "done\n";

    if ($event_count === 0) {
      return dt('There are no calendar events to delete.');
    }

    if (!$this->io()->confirm(
      dt('Are you sure you want to delete @count Calendar events?', ['@count' => $event_count]),
      FALSE)) {
      throw new UserAbortException();
    }

    Drupal::entityTypeManager()
      ->getStorage('google_calendar_event')
      ->delete($entities);

    $calfilter = [];
    if ($filter['calendar']) {
      $calfilter['id'] = $filter['calendar'];
    }
    $this->deleteSyncToken($calfilter);

    return 'Done ' . $event_count;
  }

}
