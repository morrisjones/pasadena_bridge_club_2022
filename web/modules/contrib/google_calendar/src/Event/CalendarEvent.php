<?php

namespace Drupal\google_calendar\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;
use Google_Service_Calendar_Event;

/**
 * Calendar event for event listeners.
 */
class CalendarEvent extends Event {

  const ENTITY_SYNC = 'google_calendar.entity.sync';

  /**
   * Node entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Google_Service_Calendar_Event class.
   *
   * @var \Google_Service_Calendar_Event
   */
  protected $event;

  /**
   * Constructs a node insertion demo event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(EntityInterface $entity, Google_Service_Calendar_Event $event) {
    $this->entity = $entity;
    $this->event = $event;
  }

  /**
   * Get the inserted entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  public function getEvent() {
    return $this->event;
  }

}
