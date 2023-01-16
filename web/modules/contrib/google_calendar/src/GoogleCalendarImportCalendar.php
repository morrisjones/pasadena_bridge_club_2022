<?php

namespace Drupal\google_calendar;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_calendar\Entity\GoogleCalendar;
use Google_Service_Calendar;
use Google_Service_Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GoogleCalendarImportCalendar.
 */
class GoogleCalendarImportCalendar {

  /**
   * Google Calendar service definition.
   *
   * @var \Google_Service_Calendar
   */
  protected $service;

  /**
   * Logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Settings Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GoogleCalendarImportCalendar constructor.
   *
   * @param \Google_Service_Calendar $googleClient
   *   Google API Client API wrapper.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory for calendar settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity manager to use to create new events and load calendars.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger to write notices.
   */
  public function __construct(Google_Service_Calendar $googleClient,
                              ConfigFactory $config,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->service = $googleClient;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerChannelFactory->get('google_calendar');
    $this->config = $config->get('google_calendar.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $loggerFactory = $container->get('logger.factory');
    $configFactory = $container->get('config.factory');
    $entityManager = $container->get('entityType.manager');

    // Carefully create api because import() is called from cron.
    try {
      // We cannot avoid this throwing exceptions if cannot connect to GAPI.
      $api = \Drupal::service('google_calendar.google_client.calendar');
    }
    catch (\Exception $exception) {
      $loggerFactory->get('google_calendar')
        ->error(t('Unable to connect to Google API: @msg',
                    ['@msg' => $exception->getMessage()]));
      return NULL;
    }
    return new static($api, $configFactory, $entityManager, $loggerFactory);
  }

  /**
   * @param string $calendarId
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import(string $calendarId) {
    $entities = [];
    $calEntity = NULL;

    // Do we already have this calendar? Try using either entity ID or Google
    // Calendar-id (numeric = entity, alpha = GC)?
    if (is_numeric($calendarId)) {
      $entities = $this->entityTypeManager
        ->getStorage('google_calendar')
        ->load($calendarId);

      if ($entities !== NULL) {
        $calEntity = $entities;
        $calendarId = $calEntity->getGoogleCalendarId();
      }
    }
    if (!$entities) {
      $entities = $this->entityTypeManager
        ->getStorage('google_calendar')
        ->loadByProperties(['calendar_id' => $calendarId]);

      if (count($entities)) {
        $calEntity = reset($entities);
        $calendarId = $calEntity->getGoogleCalendarId();
      }
    }

    try {
      $calendar = $this->service->calendars->get($calendarId);
    }
    catch (Google_Service_Exception $e) {
      return FALSE;
    }

    $fields = [
      'calendar_id' => ['value' => $calendarId],
      'name' => ['value' => $calendar->getSummary()],
      'description' => ['value' => $calendar->getDescription()],
      'location' => ['value' => $calendar->getLocation()],
    ];

    if (!$calEntity) {
      $fields['status'] = ['value' => TRUE];
      $fields['sync_result'] = ['value' => GoogleCalendar::SYNC_RESULT_NO_SYNC];
      $fields['last_checked'] = ['value' => time()];
      $fields['latest_event'] = ['value' => time()];

      $calEntity = GoogleCalendar::create($fields);
    }
    else {
      $fields['last_checked'] = ['value' => time()];

      // Update the existing node in place.
      foreach ($fields as $key => $value) {
        $calEntity->set($key, $value);
      }
    }

    if ($calEntity) {
      $calEntity->save();
    }
    return $calEntity;
  }

}
