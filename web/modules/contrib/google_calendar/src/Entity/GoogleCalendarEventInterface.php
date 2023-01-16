<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google Calendar Event entities.
 *
 * @ingroup google_calendar
 */
interface GoogleCalendarEventInterface extends ContentEntityInterface, EntityPublishedInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Google Calendar Event name.
   *
   * @return string
   *   Name of the Google Calendar Event.
   */
  public function getName(): string;

  /**
   * Sets the Google Calendar Event name.
   *
   * @param string $name
   *   The Google Calendar Event name.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setName(string $name): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar Event description.
   *
   * @return string
   *   Description of the Google Calendar Event.
   */
  public function getDescription(): string;

  /**
   * Sets the Google Calendar Event description.
   *
   * @param string $description
   *   The Google Calendar Event description.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setDescription(string $description): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar Event creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar Event.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Google Calendar Event creation timestamp.
   *
   * @param int $timestamp
   *   The Google Calendar Event creation timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setCreatedTime(int $timestamp): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar event Start time.
   *
   * @return int
   *   Creation timestamp of the Google Calendar Event.
   */
  public function getStartTime(): int;

  /**
   * Sets the Google Calendar event Start time.
   *
   * @param int $timestamp
   *   The Google Calendar event Start time.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setStartTime(int $timestamp): GoogleCalendarEventInterface;

  /**
   * Gets the Google Calendar event End timestamp.
   *
   * @return int
   *   Creation timestamp of the Google Calendar Event.
   */
  public function getEndTime(): int;

  /**
   * Sets the Google Calendar event End time.
   *
   * @param int $timestamp
   *   The Google Calendar event End timestamp.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setEndTime(int $timestamp): GoogleCalendarEventInterface;

  /**
   * Return whether the End Time of the event is meaningful.
   *
   * Reflects the Google Calendar "end_time_unspecified" flag.
   *
   * @return bool
   *   Creation timestamp of the Google Calendar Event.
   *
   * @see GoogleCalendarEventInterface::getEndTime()
   */
  public function isEndTimeSpecified() : bool;

  /**
   * Define whether the End time of the event is meaningful.
   *
   * Reflects the Google Calendar "end_time_unspecified" flag.
   *
   * @param bool $specified
   *   True if this event has a defined end time.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setEndTimeSpecified(bool $specified): GoogleCalendarEventInterface;

  /**
   * Can guests of the event modify the event itself -- or just the owner?.
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function canGuestsModifyEvent(): bool;

  /**
   * Set the flag indicating that guests can modify the events.
   *
   * NB This has no effect on Google, and will be overridden the next time the
   * event is updated in a full update. As such it is mostly present for
   * debugging.
   *
   * @param bool $yesno
   *   Flag value.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setGuestsModifyEvent(bool $yesno): GoogleCalendarEventInterface;

  /**
   * Can guests of the event see who else is invited, or only the owner do so?
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function canGuestsSeeInvitees(): bool;

  /**
   * Set the flag indicating that guests can see others invited to the event.
   *
   * NB This has no effect on Google, and will be overridden the next time the
   * event is updated in a full update. As such it is mostly present for
   * debugging.
   *
   * @param bool $yesno
   *   Flag value.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setGuestsSeeInvitees(bool $yesno): GoogleCalendarEventInterface;

  /**
   * Is the event Locked at the Google end?
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function isLocked(): bool;

  /**
   * Set the locked flag.
   *
   * NB This has no effect on Google, and will be overridden the next time the
   * event is updated in a full update. As such it is mostly present for
   * debugging.
   *
   * @param $locked
   *   Flag value.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setLocked($locked): GoogleCalendarEventInterface;

  /**
   * Returns the Google Calendar Event published status indicator.
   *
   * Unpublished Google Calendar Event are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google Calendar Event is published.
   */
  public function isPublished(): bool;

  /**
   * Sets the published status of a Google Calendar Event.
   *
   * @param $published
   *   Ignored.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   *   The called Google Calendar Event entity.
   */
  public function setPublished($published = NULL): GoogleCalendarEventInterface;

  /**
   * Sets the unpublished status of a Google Calendar Event.
   *
   * @return \Drupal\google_calendar\Entity\GoogleCalendarEventInterface
   */
  public function setUnpublished(): GoogleCalendarEventInterface;

  /**
   * Gets an iCal Identifier for the event.
   *
   * @return string
   *   iCal Identifier of the Google Calendar Event.
   */
  public function getGoogleICalId(): string;

  /**
   * Gets the Google Calendar event Start time.
   *
   * @return \Drupal\Core\Url
   *   URL linking to the event on the Google website.
   */
  public function getGoogleLink(): string;

  /**
   * Gets the Google Calendar ID of the event.
   *
   * @return string
   *   Calendar ID of the event.
   */
  public function getGoogleEventId(): string;

  /**
   * Gets the Recurrence Event ID of the event, if set.
   *
   * @return string
   *   Recurrence ID of the event.
   */
  public function getRecurrenceEventId(): string;

  /**
   * Gets the Recurrence settings for the event, if set.
   *
   * @return string
   *   Recurrence settings of the event using JSON.
   */
  public function getRecurrenceInfo(): string;

}
