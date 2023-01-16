<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google Calendar entities.
 *
 * @package Drupal\google_calendar
 */
interface GoogleCalendarInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /* string events were updated */
  public const SYNC_RESULT_EVENTS_IMPORTED = "events imported";

  /* string the API does not expose this calendar (any more) */
  public const SYNC_RESULT_NO_CALENDAR = "no calendar";

  /* string sync successful but no change seen */
  public const SYNC_RESULT_NO_CHANGES = "no changes";

  /* string sync not yet attempted */
  public const SYNC_RESULT_NO_SYNC = "no sync";

  /* string some sort of network error */
  public const SYNC_RESULT_NET_ERROR = "net error";

  /* string some sort of authentication error */
  public const SYNC_RESULT_AUTH_ERROR = "auth error";

  /* string a complete resync has been forced */
  public const SYNC_RESULT_FORCE_RESYNC = "forced resync";

  /* string sync process has started */
  public const SYNC_RESULT_SYNCING = "syncing";

  /**
   * Gets the Google Calendar name.
   *
   * @return string
   *   Name of the Google Calendar.
   */
  public function getName(): ?string;

  /**
   * Get the ID assigned this event by Google's API.
   *
   * @return string|null
   *   The Google Calendar's Google ID string.
   */
  public function getGoogleCalendarId(): ?string;

  /**
   * Set the ID assigned this event by Google's API.
   *
   * @param string|null $id
   *   The Google Calendar's Google ID string.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setGoogleCalendarId(?string $id);

  /**
   * Sets the Google Calendar name.
   *
   * @param string|null $name
   *   The Google Calendar name.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setName(?string $name): GoogleCalendarInterface;

  /**
   * Gets the Google Calendar Description.
   *
   * @return string
   *   Description of the Google Calendar.
   */
  public function getDescription(): ?string;

  /**
   * Sets the Google Calendar Description.
   *
   * @param string|null $desc
   *   The Google Calendar Description.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setDescription(?string $desc): GoogleCalendarInterface;

  /**
   * Gets the Google Calendar name.
   *
   * @return string
   *   Name of the Google Calendar.
   */
  public function getLocation(): ?string;

  /**
   * Sets the Google Calendar Location.
   *
   * @param string|null $locn
   *   The Google Calendar Location.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setLocation(?string $locn): GoogleCalendarInterface;

  /**
   * Gets the result of the most recent sync with Google.
   *
   * @return string
   *   Result, one of the SYNC_RESULT_* constants.
   */
  public function getSyncResult(): ?string;

  /**
   * Sets the Google Calendar Sync result.
   *
   * @param string|null $result
   *   The Google Calendar sync result: one of the SYNC_RESULT_* constants.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setSyncResult(?string $result): GoogleCalendarInterface;

  /**
   * Gets the time that the last event was imported from Google.
   *
   * @return int
   *   LatestEvent timestamp for the Google Calendar.
   */
  public function getLatestEventTime(): int;

  /**
   * Sets the time that the last event was imported from Google.
   *
   * @param int $timestamp
   *   The Google Calendar LatestEvent timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setLatestEventTime($timestamp): GoogleCalendarInterface;

  /**
   * Gets the time that the last full sync started (i.e. no syncToken).
   *
   * @return int
   *   Sync timestamp of the Google Calendar.
   */
  public function getLastSyncTime(): int;

  /**
   * Sets the Google Calendar last sync timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar Sync timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setLastSyncTime($timestamp): GoogleCalendarInterface;

  /**
   * Gets the most recent syncToken.
   *
   * @return string
   *   Sync timestamp of the Google Calendar.
   */
  public function getSyncToken(): ?string;

  /**
   * Sets the current syncToken.
   *
   * @param string|null $timestamp
   *   The new token value.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setSyncToken(?string $timestamp): GoogleCalendarInterface;

  /**
   * Gets the Google Calendar creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Google Calendar creation timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setCreatedTime($timestamp): GoogleCalendarInterface;

  /**
   * Returns the Google Calendar published status indicator.
   *
   * Unpublished Google Calendar are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar is published.
   */
  public function isPublished(): bool;

  /**
   * Sets the published status of a Google Calendar.
   *
   * @param bool $published
   *   TRUE to set this Google Calendar to published, FALSE to set
   *   it to unpublished.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarInterface
   *   The called Google Calendar entity.
   */
  public function setPublished($published): GoogleCalendarInterface;

}
