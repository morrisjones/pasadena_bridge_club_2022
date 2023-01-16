<?php

namespace Drupal\google_calendar\Form;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_calendar\Controller\GoogleCalendarImportEventsController;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\Entity\GoogleCalendarInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImportCalendarForm.
 *
 * @package Drupal\google_calendar\Form
 */
class GoogleCalendarImportEventsForm extends FormBase {

  /**
   * Drupal\google_calendar\GoogleCalendarImport definition.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   */
  protected $calendarService;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GoogleCalendarImportEventsForm constructor.
   *
   * @param \Drupal\google_calendar\Controller\GoogleCalendarImportEventsController $google_calendar_service
   *   The import service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   */
  public function __construct(GoogleCalendarImportEventsController $google_calendar_service,
                              EntityTypeManagerInterface $entityTypeManager) {
    $this->calendarService = $google_calendar_service;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.sync_events'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_sync_events_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Synchronize Events'),
    ];

    return $form;
  }

  /**
   * Batch API Callback function to import events for a calendar.
   *
   * @param \Drupal\google_calendar\Entity\GoogleCalendarInterface $calendar
   *   The calendar entity.
   * @param int $total
   *   Callback param.
   * @param array $context
   *   Callback context.
   */
  public static function handleBatchProcess(GoogleCalendarInterface $calendar,
                                            int $total,
                                            &$context) {
    $name = $calendar->label();
    $context['message'] = "Imported Calendar: $name";
    Drupal::service('google_calendar.sync_events')->import($calendar);
  }

  /**
   * Callback for batch callback.
   *
   * @param bool $success
   *   True if the batch process completed successfully, False otherwise.
   * @param $results
   *   Not used.
   * @param $operations
   *   Not used.
   */
  public static function batchProcessCallback(bool $success,
                                              $results,
                                              $operations) {
    if ($success) {
      $message = t('Successfully imported calendar events');
    }
    else {
      $message = t('Failed to import all calendar events.');
    }
    Drupal::messenger()->addMessage($message);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $query = $this->entityTypeManager
      ->getStorage('google_calendar')
      ->getQuery()
      ->condition('status', 1);

    $calendarIds = $query->execute();

    $foundCalendars = GoogleCalendar::loadMultiple($calendarIds);
    $operations = [];
    $total = count($foundCalendars);
    foreach ($foundCalendars as $calendar) {
      $operations[] = [
        '\Drupal\google_calendar\Form\GoogleCalendarImportEventsForm::handleBatchProcess', [$calendar, $total],
      ];
    }

    $batch = [
      'title' => t('Importing Calendars'),
      'operations' => $operations,
      'finished' => '\Drupal\google_calendar\Form\GoogleCalendarImportEventsForm::batchProcessCallback',
    ];

    return batch_set($batch);
  }

}
