<?php

namespace Drupal\google_calendar;

use Drupal\google_calendar\Entity\GoogleCalendarInterface;

/**
 * Class GoogleCalendarImportEvents.
 */
interface GoogleCalendarImportEventsInterface {
  public const CLEANUP_NONE = 'no cleanup';
  public const CLEANUP_DEL_OLD = 'delete past events';
  public const CLEANUP_DEL_ALL = 'delete all events';
  public const CLEANUP_UNPUB_OLD = 'unpublish past events';
  public const CLEANUP_UNPUB_ALL = 'unpublish all events';

  public const OWNER_FIXED = 'fixed';
  public const OWNER_BYEMAIL = 'by_email';
  public const OWNER_BYNAME = 'by_name';

  /**
   * How far into the past or into the future should event changes be noted?
   *
   * Default of -1 hour allows for last minute changes to an event to be
   * recorded, should that be needed. 'now' and 'today' are also good options.
   *
   * Default of 1 year 1 day allows for convenience of booking annual events.
   *
   * @param string $past
   *   The relative date for the historical event cutoff, before which events
   *   are eligible for cleanup..
   * @param string $future
   *   The relative date in the future beyond which events are not loaded.
   *
   * @return \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   *   Reference to the current entity.
   */
  public function setEventHorizons(string $past, string $future): GoogleCalendarImportEventsInterface;

  /**
   * How far into the past or into the future should event changes be noted?
   *
   * @see setEventHorizonRefresh()
   */
  public function getEventHorizons();

  /**
   * How often should we recalculate when 'past' and 'future' are?
   *
   * "Recalculating" means refreshing all existing events, rather than relying
   * on an incremental update, and is hence slow.
   *
   * Setting 1 day ("P1D") is normal; reduce ("PT8H") when 'future' is not very
   * far away and hence changes are of greater importance; increase ("P7D") to
   * reduce bandwidth requirements at the expense of ignoring some changes.
   *
   * Values must be formatted as a DateInterval().
   *
   * @param string $intv
   *   The interval, as a DateInterval string, at which to recalculate horizons.
   *
   * @return \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   *   Reference to the current entity.
   */
  public function setEventHorizonRefresh(string $intv): GoogleCalendarImportEventsInterface;

  /**
   * How often should we recalculate when 'past' and 'future' are?
   *
   * @see setEventHorizonRefresh()
   */
  public function getEventHorizonRefresh();

  /**
   * Policy value determining how events are cleaned up during an import.
   *
   * @param string $policy
   *   The cleanup policy string, one of the self::CLEANUP_* values.
   *
   * @return \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   *   Reference to the current entity.
   */
  public function setCleanupPolicy(string $policy): GoogleCalendarImportEventsInterface;

  /**
   * Policy value determining how events are cleaned up during an import.
   */
  public function getCleanupPolicy();

  /**
   * How to determine who in Drupal terms is the owner of an event.
   *
   * One of the values in the OWNER_* constants:
   *   - fixed: the 'default owner' is always used.
   *   - by_name: the owner name from the event is looked up in the Drupal
   *     user table. If not found, the default owner is used.
   *   - by_email: the email addr from the event is looked up in the Drupal
   *     user table. If not found, the default owner is used.
   *
   * @param string $policy
   *   The ownership policy, one of the self::OWNER_* values.
   *
   * @return \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   *   Reference to the current entity.
   */
  public function setEventOwnership(string $policy): GoogleCalendarImportEventsInterface;

  /**
   * How to determine who in Drupal terms is the owner of an event.
   *
   * @see setEventOwnership()
   */
  public function getEventOwnership();

  /**
   * Nominated owner of events not having a otherwise-defined owner.
   *
   * How this is used depends on the eventOwnership setting.
   *
   * @param string $owner
   *   The owner username.
   *
   * @return \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   *   Reference to the current entity.
   */
  public function setDefaultEventOwner(string $owner): GoogleCalendarImportEventsInterface;

  /**
   * Nominated owner of events not having a otherwise-defined owner.
   *
   * @see setDefaultEventOwner()
   */
  public function getDefaultEventOwner();

  /**
   * Reset event update statistics for a new import.
   *
   * @return \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   *   Reference to the current entity.
   */
  public function resetStats(): GoogleCalendarImportEventsInterface;

  /**
   * Return number of events reported by Google.
   *
   * @return int
   *   A number of events.
   */
  public function getStatNewEvents(): int;

  /**
   * Return number of events updated this time.
   *
   * @return int
   *   A number of events.
   */
  public function getStatModifyEvents(): int;

  /**
   * Return number of events not seen before.
   *
   * @return int
   *   A number of events.
   */
  public function getStatCreatedEvents(): int;

  /**
   * Return number of events actually saved.
   *
   * @return int
   *   A number of events.
   */
  public function getStatSavedEvents(): int;

  /**
   * Return number of events explicitly cancelled by API.
   *
   * @return int
   *   A number of events.
   */
  public function getStatCancelledEvents(): int;

  /**
   * Return number of pages of events read from API.
   *
   * @return int
   *   A number of events.
   */
  public function getPageCount(): int;

  /**
   * Return a Sync Token, or NULL if no token should be used.
   *
   * A token will be returned unless either 'ignoresynctoken' is set
   * or the calendar resync schedule has been reached.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar for which the token is needed.
   * @param bool $ignoreSyncToken
   *   Flag forcing the function to return NULL.
   *
   * @return null|string
   *   Return the sync token for this calendar, accounting for module
   *   settings. Returns NULL if a full reload is required or needed.
   */
  public function getSyncToken(GoogleCalendarInterface $calendar, bool $ignoreSyncToken): ?string;

  /**
   * Mark the Sync Token invalid, forcing an full update on the next update.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $entity
   *   The calendar entity that should be modified.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteSyncToken(GoogleCalendarInterface $entity): int;

  /**
   * Import changes from Google Calendar to the local event entities.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar entity that should be imported.
   * @param bool $ignoreSyncToken
   *   TRUE if the import should ignore any sync token, so causing a full
   *   reload of the calendar. FALSE to use any token that might be set, thus
   *   using an incremental update if possible.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The entity being updated. Inspect it for status information.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Google_Service_Exception
   */
  public function import(GoogleCalendarInterface $calendar, $ignoreSyncToken = FALSE): GoogleCalendarInterface;

}
