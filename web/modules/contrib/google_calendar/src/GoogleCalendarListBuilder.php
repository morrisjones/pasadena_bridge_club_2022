<?php

namespace Drupal\google_calendar;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Google Calendar entities.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarListBuilder extends EntityListBuilder implements EntityHandlerInterface {

  protected $dateFormatter;

  /**
   * GoogleCalendarListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   */
  function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $dateFormatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): GoogleCalendarListBuilder {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Google Calendar ID');
    $header['last_sync'] = $this->t('Last synced');
    $header['number'] = $this->t('Events Loaded');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity \Drupal\google_calendar\Entity\GoogleCalendar */
    $lastsync_ts = $entity->getLastSyncTime();
    if (is_numeric($lastsync_ts)) {
      $last_synced = $this->dateFormatter->format($lastsync_ts, 'medium');
    }
    else {
      $last_synced = $this->t('Never');
    }

    $row['name'] = $entity->label();
    $row['id'] = $entity->getGoogleCalendarId();
    $row['last_sync'] = $last_synced;
    $row['number'] = $entity->getEventsLoaded();
    return $row + parent::buildRow($entity);
  }

}
