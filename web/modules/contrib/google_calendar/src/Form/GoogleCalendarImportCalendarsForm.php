<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for Google Calendar import forms.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarImportCalendarsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_import_calendars_form';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $index = [];
    $imported = [];
    $toimport = [];
    $orphaned = [];
    $unpublished = [];

    // Fetch the list of current calendar entities from Drupal.
    //
    // Unpublished entities are included because otherwise they could lead
    // to someone believing they were not present, and then failing in
    // trying to re-add them.
    try {
      $entities = \Drupal::entityTypeManager()
        ->getStorage('google_calendar')
        ->loadMultiple();
    }
    catch (\Exception $exception) {
      $this->messenger()->addError(t("Unable to load Drupal Calendar entities.\n<br>Additional Information: @msg",
                                     ['@msg' => $exception->getMessage()]));
      $entities = [];
    }

    /** @var \Drupal\google_calendar\Entity\GoogleCalendarInterface $entity */
    foreach ($entities as $entity) {
      $index[$entity->getGoogleCalendarId()] = $entity;
    }

    // Fetch the list of currently visible calendars from Google.
    try {
      $service = \Drupal::service('google_calendar.google_client.calendar');
      $list = $service->calendarList->listCalendarList();
      $items = $list->getItems();
    }
    catch (\Exception $exception) {
      $this->messenger()->addError(t("Unable to Google Calendars from the Google API.\n<br>Is Google API authentication configured?\n<br>Additional Information: @msg",
                                     ['@msg' => $exception->getMessage()]));
      $items = [];
    }

    /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
    foreach ($items as $calendar) {
      if (array_key_exists($calendar->getId(), $index)) {
        $imported[] = $calendar->getId();
      }
      else {
        $toimport[] = $calendar->getId();
      }
    }

    // Check to see if any current entities are no longer visible in
    // the calendar api (e.g. they have been unshared).
    /** @var \Drupal\google_calendar\Entity\GoogleCalendarInterface $entity */
    foreach ($entities as $entity) {
      $eid = $entity->getGoogleCalendarId();

      $found = FALSE;
      /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
      foreach ($items as $calendar) {
        if ($eid === $calendar->getId()) {
          $found = TRUE;
          if (!$entity->isPublished()) {
            $unpublished[] = $calendar->getId();
          }
        }
      }
      if (!$found) {
        $orphaned[] = $eid;
      }
    }

    // Build the list of calendars and their status: imported / toimport / etc.
    //
    // An imported calendar gets a button to do an incremental sync, for
    // convenience, but the main interface for this is on the calendar list
    // form.
    $rows = [];
    foreach ($items as $calendar) {
      $id = $calendar->getId();
      /* Build Status */
      if (in_array($id, $unpublished, TRUE)) {
        $status = $this->t('Unpublished as @name', [
          '@name' => $index[$id]->toLink()->toString(),
        ]);
      }
      elseif (in_array($id, $imported, TRUE)) {
        $status = $this->t('Imported as @name', [
          '@name' => $index[$id]->toLink()->toString(),
        ]);
      }
      elseif (in_array($id, $toimport, TRUE)) {
        $status = $this->t('Not imported');
      }
      elseif (in_array($id, $orphaned, TRUE)) {
        $status = $this->t('Not longer available');
      }
      else {
        $status = $this->t('Unknown');
      }

      /* Build links */
      $links = [];
      if (in_array($id, $toimport, TRUE)) {
        $links['import'] = [
          'title' => $this->t('Import Calendar'),
          'url' => Url::fromRoute('google_calendar.import_calendar', [
            'calendar_id' => $id,
          ]),
        ];
      }
      elseif (in_array($id, $unpublished, TRUE)) {
        $links['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('entity.google_calendar.edit_form', [
            'google_calendar' => $index[$id]->id(),
          ]),
        ];
      }
      elseif (in_array($id, $imported, TRUE)) {
        $links['sync'] = [
          'title' => $this->t('Sync Events'),
          'url' => Url::fromRoute('google_calendar.sync_events', [
            'google_calendar' => $index[$id]->id(),
            'resync' => '0',
          ]),
        ];
      }

      // Build the table row.
      $row = [];

      /* Cell: Name */
      $row['name']['data'] = [
        '#type' => 'markup',
        '#markup' => $calendar->getSummary(),
      ];

      /* Cell: Description */
      $row['desc']['data'] = [
        '#type' => 'markup',
        '#markup' => mb_strimwidth($calendar->getDescription(), 0, 40, '...'),
      ];

      /* Cell: Status */
      $row['status']['data'] = [
        '#type' => 'markup',
        '#markup' => $status,
      ];

      /* Cell: Operations */
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }
    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Status'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No calendars are available.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.google_calendar.collection');
  }

}
