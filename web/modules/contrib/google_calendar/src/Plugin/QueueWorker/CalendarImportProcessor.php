<?php

namespace Drupal\google_calendar\Plugin\QueueWorker;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Drupal\google_calendar\GoogleCalendarImportEventsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CalendarImportProcessor class.
 *
 * @QueueWorker(
 *   id = "google_calendar_import_processor",
 *   title = "Google Calendar Import Processor",
 *   cron = {"time" = 60}
 * )
 */
class CalendarImportProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger to use for status messages.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Storage for the class which will actually process each item.
   *
   * @var \Drupal\google_calendar\GoogleCalendarImportEventsInterface
   */
  protected $calendarImport;

  /**
   * Constructs a CalendarImportProcessor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\google_calendar\GoogleCalendarImportEventsInterface $calendar_import
   *   Class to perform the item processing.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              GoogleCalendarImportEventsInterface $calendar_import) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->calendarImport = $calendar_import;
    $this->logger = \Drupal::logger('google_calendar');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_calendar.sync_events')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($calendarEntityId) {

    /** @var \Drupal\google_calendar\Entity\GoogleCalendar $calendar */
    $calendar = GoogleCalendar::load($calendarEntityId);
    if ($calendar) {
      try {
        $this->calendarImport->import($calendar);
      }
      catch(\Google\Service\Exception $e) {
        $this->logger->error(
          'Errors with calendar @label: @errors.',
          [
            '@label' => $calendar->label(),
            '@errors' => JSon::encode($e->getErrors()),
          ]
        );
        return NULL;
      }
    }
    else {
      $this->logger->error('Sync task -- unable to load calendar @id',
                           ['@id' => $calendarEntityId]);
    }
  }

}
