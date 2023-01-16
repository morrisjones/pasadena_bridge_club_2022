<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Google Calendar Event entity.
 *
 * @ingroup google_calendar
 *
 * @ContentEntityType(
 *   id = "google_calendar_event",
 *   label = @Translation("Google Calendar Event"),
 *   label_plural = @Translation("events"),
 *   label_count = @PluralTranslation(
 *     singular = "@count event",
 *     plural = "@count events",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\google_calendar\GoogleCalendarEventListBuilder",
 *     "views_data" = "Drupal\google_calendar\Entity\GoogleCalendarEventViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\google_calendar\Form\GoogleCalendarEventForm",
 *       "add" = "Drupal\google_calendar\Form\GoogleCalendarEventForm",
 *       "edit" = "Drupal\google_calendar\Form\GoogleCalendarEventForm",
 *       "delete" = "Drupal\google_calendar\Form\GoogleCalendarEventDeleteForm",
 *     },
 *     "access" = "Drupal\google_calendar\GoogleCalendarEventAccessControlHandler",
 *     "route_provider" = {
 *        "html" = "Drupal\google_calendar\GoogleCalendarHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "google_calendar_event",
 *   admin_permission = "administer google calendar events",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/event/{google_calendar_event}",
 *     "add-form" = "/admin/content/calendar_event/add",
 *     "edit-form" = "/admin/content/calendar_event/{google_calendar_event}/edit",
 *     "delete-form" = "/admin/content/calendar_event/{google_calendar_event}/delete",
 *     "collection" = "/admin/content/google_calendar_event",
 *   },
 *   field_ui_base_route = "google_calendar_event.settings"
 * )
 */
class GoogleCalendarEvent extends ContentEntityBase implements EntityPublishedInterface, GoogleCalendarEventInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name): GoogleCalendarEventInterface {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description): GoogleCalendarEventInterface {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    return $this->get('location')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation(string $location): GoogleCalendarEventInterface {
    $this->set('location', $location);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): GoogleCalendarEventInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): UserInterface {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime(): int {
    return $this->get('start_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTime(int $start): GoogleCalendarEventInterface {
    $this->set('start_date', $start);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime(): int {
    return $this->get('end_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTime(int $end): GoogleCalendarEventInterface {
    $this->set('end_date', $end);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEndTimeSpecified(): bool {
    return !$this->get('end_unspecified')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTimeSpecified(bool $specified): GoogleCalendarEventInterface {
    $this->set('end_unspecified', !$specified);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function canGuestsInviteOthers(): bool {
    return (bool) $this->get('guests_invite_others')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGuestsInviteOthers(bool $yesno): GoogleCalendarEventInterface {
    $this->set('guests_invite_others', $yesno ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function canGuestsModifyEvent(): bool {
    return (bool) $this->get('guest_modify')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGuestsModifyEvent(bool $yesno): GoogleCalendarEventInterface {
    $this->set('guest_modify', $yesno ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function canGuestsSeeInvitees(): bool {
    return (bool) $this->get('guests_see_invitees')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGuestsSeeInvitees(bool $yesno): GoogleCalendarEventInterface {
    $this->set('guests_see_invitees', $yesno ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked(): bool {
    return (bool) $this->get('locked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocked($locked): GoogleCalendarEventInterface {
    $this->set('locked', $locked ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(): bool {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   *
   * $published IS IGNORED!
   */
  public function setPublished($published = NULL): GoogleCalendarEventInterface {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished(): GoogleCalendarEventInterface {
    $this->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGoogleLink(): string {
    return $this->get('google_link')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGoogleEventId(): string {
    return $this->get('event_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrenceEventId(): string {
    return $this->get('recurrence_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrenceInfo(): string {
    return $this->get('recurrence')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalendarId(): string {
    return $this->get('calendar')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGoogleICalId(): string {
    return $this->get('ical_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Google Calendar Event entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the event. This field is read-only, and should be changed in Google Calendar.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('Event Location.  This field is read-only, and should be changed in Google Calendar.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event ID'))
      ->setDescription(t('The Google created unique ID for this event. Unique even for recurring instances.'))
      ->setDefaultValue('')
      ->setReadOnly(TRUE);

    $fields['ical_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('iCal ID'))
      ->setDescription(t('The Google created iCal ID for this event. Not unique for recurring instances.'))
      ->setDefaultValue('')
      ->setReadOnly(TRUE);

    $fields['google_link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Google Link'))
      ->setDescription(t('External link to the event in its Google Calendar.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'link',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE);

    $fields['calendar'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Google Calendar'))
      ->setDescription(t('The calendar this event is part of.'))
      ->setSetting('target_type', 'google_calendar')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Long form description of the event.'))
      ->setSettings([
        'max_length' => 2048,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start Date'))
      ->setDescription(t('Event Start Date.  This field is read-only, and should be changed in Google Calendar.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -4,
      ])
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End Date'))
      ->setDescription(t('Event End Date.  This field is read-only, and should be changed in Google Calendar.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -4,
      ])
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_unspecified'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('End Unspecified'))
      ->setDescription(t('A boolean indicating that the end date/time was not specified.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(FALSE);

    $fields['recurrence_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recurrence ID'))
      ->setDescription(t('Recurrence event id'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['recurrence'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recurrence'))
      ->setDescription(t('Recurrence info'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['etag'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tag'))
      ->setDescription(t('A tag value.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['guests_invite_others'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can guests invite others'))
      ->setDescription(t('A boolean indicating that someone other than the owner of the event can invite people.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(FALSE);

    $fields['guests_modify'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can guests edit'))
      ->setDescription(t('A boolean indicating that someone other than the owner of the event change it.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(FALSE);

    $fields['guests_see_invitees'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Can guests view guest list'))
      ->setDescription(t('A boolean indicating that guests can see who else is invited.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(FALSE);

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked status'))
      ->setDescription(t('A boolean indicating that the Google Calendar Event is locked.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(TRUE);

    $fields['transparency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Transparency'))
      ->setSetting('allowed_values', [
        'opaque' => 'Opaque',
        'transparent' => 'Transparent',
      ])
      ->setDescription(t('A boolean indicating whether the event blocks time in the calendar.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(TRUE);

    $fields['visibility'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event visibility'))
      ->setSetting('allowed_values', [
        'default' => 'Default',
        'public' => 'Public',
        'private' => 'Private',
        'confidential' => 'Confidential',
      ])
      ->setDescription(t('Whether this event is public or private.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('default');

    $fields['state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('State'))
      ->setSetting('allowed_values', [
        'tentative' => 'Tentative',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
      ])
      ->setDescription(t('Whether this event is public or private.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Google Calendar Event is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Created'))
      ->setDefaultValue(0)
      ->setDescription(t('The time that the entity was created.'))
      ->setReadOnly(TRUE);

    $fields['creator'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event creator'))
      ->setDescription(t('Name of event creator.'))
      ->setDefaultValue('')
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['creator_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Event creator email'))
      ->setDescription(t('Email of event creator.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['organizer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event organizer'))
      ->setDescription(t('Name of event organizer.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['organizer_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Event organizer email'))
      ->setDescription(t('Email of event organizer.'))
      ->setDefaultValue('')
      ->setReadOnly(TRUE)
      ->setDefaultValue('');

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['updated'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    return $fields;
  }

}
