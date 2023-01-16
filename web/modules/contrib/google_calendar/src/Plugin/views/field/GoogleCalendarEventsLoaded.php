<?php

namespace Drupal\google_calendar\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a node operations bulk form element.
 *
 * @ViewsField("google_calendar_events_loaded")
 */
class GoogleCalendarEventsLoaded extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * We need the table and potentially additional fields.
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity = $this->getEntity($row);
    $num = '-';
    if ($entity) {
      $num = $entity->getEventsLoaded();
    }
    return $num;
  }

}
