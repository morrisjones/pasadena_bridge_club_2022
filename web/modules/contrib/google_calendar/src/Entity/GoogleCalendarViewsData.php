<?php

namespace Drupal\google_calendar\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Google Calendar entities.
 */
class GoogleCalendarViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData(): array {
    $data = parent::getViewsData();

    $data['google_calendar']['events_loaded'] = [
      'title' => $this->t('Events Loaded'),
      'help' => $this->t("Simple query that gets the published event's count of the calendar."),
      'field' => [
        'id' => 'google_calendar_events_loaded',
        'click sortable' => FALSE,
      ],
    ];

    return $data;
  }

}
