<?php

namespace Drupal\google_calendar\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\google_calendar\GoogleCalendarImportEventsInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Google Calendar entity.
 *
 * @package \Drupal\google_calendar
 *
 * @ContentEntityType(
 *   id = "google_calendar",
 *   label = @Translation("Google Calendar"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\google_calendar\GoogleCalendarListBuilder",
 *     "views_data" = "Drupal\google_calendar\Entity\GoogleCalendarViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "add" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "edit" = "Drupal\google_calendar\Form\GoogleCalendarForm",
 *       "delete" = "Drupal\google_calendar\Form\GoogleCalendarDeleteForm",
 *     },
 *     "access" = "Drupal\google_calendar\GoogleCalendarAccessControlHandler",
 *     "route_provider" = {
 *        "html" = "Drupal\google_calendar\GoogleCalendarHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "google_calendar",
 *   admin_permission = "administer google calendars",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/google_calendar/{google_calendar}",
 *     "add-form" = "/admin/content/google_calendar/add",
 *     "edit-form" = "/admin/content/google_calendar/{google_calendar}/edit",
 *     "delete-form" =
 *   "/admin/content/google_calendar/{google_calendar}/delete",
 *     "collection" = "/admin/content/google_calendar/",
 *   },
 *   field_ui_base_route = "google_calendar.settings"
 * )
 */
class GoogleCalendar extends ContentEntityBase implements GoogleCalendarInterface {

  use EntityChangedTrait;

  /**
   * Gets the current amount of published events for the calendar.
   */
  public function getEventsLoaded() {
    $storage = $this->entityTypeManager()->getStorage('google_calendar_event');

    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('calendar', $this->id());
    return $query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): ?string {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName(?string $name): GoogleCalendarInterface {
    $this->set('name', $name);
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
  public function setCreatedTime($timestamp): GoogleCalendarInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): int {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): int {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): GoogleCalendarInterface {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): GoogleCalendarInterface {
    $this->set('user_id', $account->id());
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
   */
  public function setPublished($published): GoogleCalendarInterface {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGoogleCalendarId(): ?string {
    return $this->get('calendar_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGoogleCalendarId(?string $id): GoogleCalendar {
    $this->set('calendar_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(?string $desc): GoogleCalendarInterface {
    $this->set('description', $desc);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): ?string {
    return $this->get('location')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation(?string $locn): GoogleCalendarInterface {
    $this->set('location', $locn);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyncResult(): ?string {
    return $this->get('sync_result')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncResult(?string $result): GoogleCalendarInterface {
    $this->set('sync_result', $result);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestEventTime(): int {
    return $this->get('latest_event')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLatestEventTime($timestamp): GoogleCalendarInterface {
    $this->set('latest_event', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSyncTime(): int {
    return $this->get('last_checked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastSyncTime($timestamp): GoogleCalendarInterface {
    $this->set('last_checked', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyncToken(): ?string {
    return $this->get('sync_token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncToken(?string $token): GoogleCalendarInterface {
    $this->set('sync_token', $token);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preResync(string $calendarId,
                            GoogleCalendarImportEventsInterface $calendarImportEvents) {
  }

  /**
   * {@inheritdoc}
   */
  public function preImport(string $calendarId,
                            GoogleCalendarImportEventsInterface $calendarImportEvents) {
  }

  /**
   * {@inheritdoc}
   */
  public function postImport(string $calendarId,
                             GoogleCalendarImportEventsInterface $calendarImportEvents) {
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Google Calendar entity.'))
      ->setSettings(['max_length' => 50, 'text_processing' => 0])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the Google Calendar entity.'))
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

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('The (default) location of the Google Calendar entity.'))
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

    $fields['calendar_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Google Calendar ID'))
      ->setDescription(t('The Google ID of the calendar. This can be obtained from the "Integrate Calendar" section of your calendar\'s settings.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Google Calendar is published.'))
      ->setDefaultValue(TRUE)
      ->setInitialValue(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sync_result'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sync Result'))
      ->setDefaultValue('')
      ->setDescription(t('Report the status of the last sync with Google.'))
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

    $fields['sync_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sync Token'))
      ->setDefaultValue('')
      ->setDescription(t('Token used for incremental sync.'))
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

    $fields['last_checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Checked'))
      ->setDescription(t('The time of the last full & complete sync of the calendar with Google.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp_ago',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['latest_event'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Latest Event'))
      ->setDescription(t('The time that events were last updated during a sync.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp_ago',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the calendar was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the calendar was last edited.'));

    return $fields;
  }

}
