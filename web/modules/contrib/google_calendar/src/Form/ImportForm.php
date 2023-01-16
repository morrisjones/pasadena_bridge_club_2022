<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\GoogleCalendarImportCalendar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_calendar\GoogleCalendarImport;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ImportCalendarForm.
 *
 * @package Drupal\google_calendar\Form
 */
class ImportForm extends FormBase {

  /**
   * Google Calendar Importer object.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImport
   */
  protected $calendarImportService;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ImportForm constructor.
   *
   * @param \Drupal\google_calendar\GoogleCalendarImportCalendar $calendarImportService
   *   Importer object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity manager object for google calendars.
   */
  public function __construct(GoogleCalendarImportCalendar $calendarImportService,
                              EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->calendarImportService = $calendarImportService;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_calendar.import'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_import_form';
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
   * Implements callback_batch_operation().
   *
   * Batch process per-item callback function.
   *
   * @param \Drupal\google_calendar\GoogleCalendarImportCalendar $importer
   *   The importer service.
   * @param \Drupal\google_calendar\Entity\GoogleCalendar $calendar
   *   The calendar to import.
   * @param int $total
   *   Total calendars to import.
   * @param array $context
   *   Batch context.
   */
  public static function handleBatchProcess(GoogleCalendarImportCalendar $importer,
                                            GoogleCalendar $calendar,
                                            int $total,
                                            array &$context) {
    $name = $calendar->label();
    $context['message'] = "Imported Calendar: $name";
    try {
      $importer->import($calendar);
    }
    catch (\Exception $ex) {
      \Drupal::messenger()->addMessage(t('Exception caught processing item.'));
    }
  }

  /**
   * Batch process completion callback function.
   */
  public static function batchProcessCallback($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('Finished importing calendar events'));
    }
    else {
      $messenger->addMessage(t('Finished with an error.'));
    }
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
        '\Drupal\google_calendar\Form\ImportForm::handleBatchProcess',
        [
          $this->calendarImportService,
          $calendar,
          $total,
        ],
      ];
    }

    $batch = [
      'title' => t('Importing Calendars'),
      'operations' => $operations,
      'finished' => '\Drupal\google_calendar\Form\ImportForm::batchProcessCallback',
    ];

    return batch_set($batch);
  }

}
