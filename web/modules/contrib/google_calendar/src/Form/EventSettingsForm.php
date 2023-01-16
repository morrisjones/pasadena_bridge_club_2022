<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_calendar\GoogleCalendarImportEventsInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class SettingsForm.
 */
class EventSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_event_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames(): array {
    return ['google_calendar.settings'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_calendar.settings');

    // Save to persistent settings:
    $config
      ->set('cleanup_policy', $form_state->getValue('cleanup_policy'))
      ->set('entity_ownership', $form_state->getValue('entity_ownership'))
      ->set('default_event_owner', $form_state->getValue('default_event_owner'))
      ->set('horizon_past', $form_state->getValue('horizon_past'))
      ->set('horizon_future', $form_state->getValue('horizon_future'))
      ->set('horizon_refresh', $form_state->getValue('horizon_refresh'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_calendar.settings');

    /* ***************************************************************** */
    /* ******************      Ownership Policy       ****************** */

    $entity_ownership = $config->get('entity_ownership') ?? GoogleCalendarImportEventsInterface::OWNER_BYEMAIL;
    if (!is_string($entity_ownership)) {
      $entity_ownership = GoogleCalendarImportEventsInterface::OWNER_BYEMAIL;
    }
    $form['ownership'] = [
      '#type' => 'details',
      '#title' => $this->t('Event ownership'),
      '#open' => TRUE,
    ];
    $form['ownership']['intro'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Events imported from Google Calendar can be owned by a name and/or email address, while Drupal&apos;s access control system requires a site User ID linked to each. This setting only affects events imported or updated from now on; older events are left alone.'),
    ];
    $form['ownership']['entity_ownership'] = [
      '#type' => 'container',
      '#title' => $this->t('Which User owns imported calendar events'),
      '#required' => TRUE,
    ];
    $form['ownership']['entity_ownership']['fixed']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Use default owner'),
      '#description' => $this->t('Always set to the default owner.'),
      '#return_value' => GoogleCalendarImportEventsInterface::OWNER_FIXED,
      '#parents' => ['entity_ownership'],
      '#default_value' =>
        ($entity_ownership === GoogleCalendarImportEventsInterface::OWNER_FIXED)
          ? GoogleCalendarImportEventsInterface::OWNER_FIXED : FALSE,
    ];
    $form['ownership']['entity_ownership']['by_email']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Use organizer&apos;s email'),
      '#description' => $this->t('Derive from each event organizer&apos;s email address, otherwise use default owner.'),
      '#return_value' => GoogleCalendarImportEventsInterface::OWNER_BYEMAIL,
      '#parents' => ['entity_ownership'],
      '#default_value' =>
        ($entity_ownership === GoogleCalendarImportEventsInterface::OWNER_BYEMAIL)
          ? GoogleCalendarImportEventsInterface::OWNER_BYEMAIL : FALSE,
    ];
    $form['ownership']['entity_ownership']['by_name']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Use organizer&apos;s name'),
      '#description' => $this->t('Derive from each event organizer&apos;s name, otherwise use default owner.'),
      '#return_value' => GoogleCalendarImportEventsInterface::OWNER_BYNAME,
      '#parents' => ['entity_ownership'],
      '#default_value' =>
        ($entity_ownership === GoogleCalendarImportEventsInterface::OWNER_BYNAME)
          ? GoogleCalendarImportEventsInterface::OWNER_BYNAME : FALSE,
    ];

    $form['ownership']['default_event_owner'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default imported event owner'),
      '#description' => $this->t(
        'The event owner set for Fixed ownership, or when the name or email of the event does not match a drupal user account.'),
      '#target_type' => 'user',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#process_default_value' => FALSE,
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#default_value' => '',
    ];
    $default_event_owner = $config->get('default_event_owner');
    if ($default_event_owner !== NULL && $user = User::load($default_event_owner)) {
      $form['ownership']['default_event_owner']['#default_value'] =
        ['target_id' => $user->getAccountName() . ' (' . $user->id() . ')'];
    }

    /* ***************************************************************** */
    /* ******************       Refresh Policy        ****************** */

    $entity_refresh = $config->get('horizon_refresh');
    $form['refresh'] = [
      '#type' => 'details',
      '#title' => $this->t('Event refresh'),
      '#open' => TRUE,
    ];
    $form['refresh']['intro'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Calendar updates are normally incremental, with Google providing only updates to the events since the last sync. When doing a full sync, the importer uses past and future cutoff times between which events updates are fetched from the calendar. A full resync is requested at least as often as the Full refresh interval, and possibly more often, at which point this setting is used to calculate a new time window.'),
    ];
    $refresh_options = [
      'PT1H' => '1 hour',
      'PT6H' => '6 hours',
      'PT12H' => '12 hours',
      'P1D' => '1 day',
      'P2D' => '2 days',
      'P4D' => '4 days',
      'P7D' => '7 days',
    ];
    $form['refresh']['horizon_refresh'] = [
      '#type' => 'select',
      '#options' => $refresh_options,
      '#title' => t('Full refresh interval'),
      '#default_value' => $entity_refresh,
      '#description' => $this->t(
        'How often should a full Calendar resync be performed. "1 Day" is the suggested value.'
      ),
    ];

    $entity_future = $config->get('horizon_future') ?? '6 months';
    $future_horizon_options = [
      '2 weeks' => '2 weeks',
      '1 month' => '1 month',
      '2 months' => '2 months',
      '3 months' => '3 months',
      '6 months' => '6 months',
      '9 months' => '9 months',
      '1 year' => '1 year',
      '2 years' => '2 years',
      '5 years' => '5 years',
    ];
    $form['refresh']['horizon_future'] = [
      '#type' => 'select',
      '#options' => $future_horizon_options,
      '#title' => t('Future horizon'),
      '#default_value' => $entity_future,
      '#description' => $this->t(
        'Sets the point in the future up to which calendar events are imported. Far future periods extend the resync process and increase network, database storage and cpu usage.'
      ),
    ];

    $entity_past = $config->get('horizon_past') ?? '1 minute ago';
    $past_horizon_options = [
      '1 minute ago' => '1 minute',
      '1 hour ago' => '1 hour',
      '4 hours ago' => '4 hours',
      '8 hours ago' => '8 hours',
      '1 day ago' => '1 day',
      '2 days ago' => '2 days',
      '1 month ago' => '1 month',
      '2 months ago' => '2 months',
      '3 months ago' => '3 months',
      '6 months ago' => '6 months',
      '9 months ago' => '9 months',
      '1 year ago' => '1 year',
      '2 years ago' => '2 years',
      '5 years ago' => '5 years',
    ];

    $form['refresh']['horizon_past'] = [
      '#type' => 'select',
      '#options' => $past_horizon_options,
      '#title' => t('Past horizon'),
      '#default_value' => $entity_past,
      '#description' => $this->t(
        'Sets the point in the past before which calendar events are not imported, and before which events can be included in the cleanup. See the "Event Cleanup Policy" settings for more information.'
      ),
    ];

    /* ***************************************************************** */
    /* ******************        Cleanup Policy       ****************** */

    $entity_cleanup_policy = $config->get('cleanup_policy');
    $form['cleanup'] = [
      '#type' => 'details',
      '#title' => $this->t('Event Cleanup Policy'),
      '#open' => TRUE,
    ];
    $form['cleanup']['cleanup_policy'] = [
      '#type' => 'container',
      '#title' => $this->t('What should the system do with old events?'),
      '#prefix' => $this->t(
        'Select the policy you wish for events that are older than the past horizon. Unpublished events remain in the database and can still be displayed; filter them out as required.'
      ),
      '#suffix' => '<strong>' . $this->t('Note: The calendar events table will grow in size indefinitely unless one of the "delete" options is used. You will need to prune it manually.') . '</strong>',
      '#required' => TRUE,
    ];
    $form['cleanup']['cleanup_policy']['none']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Do nothing'),
      '#return_value' => GoogleCalendarImportEventsInterface::CLEANUP_NONE,
      '#parents' => ['cleanup_policy'],
      '#default_value' => $entity_cleanup_policy,
    ];
    $form['cleanup']['cleanup_policy']['del_old']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Delete past events'),
      '#return_value' => GoogleCalendarImportEventsInterface::CLEANUP_DEL_OLD,
      '#parents' => ['cleanup_policy'],
      '#default_value' => $entity_cleanup_policy,
    ];
    $form['cleanup']['cleanup_policy']['unpub_old']['radio'] = [
      '#type' => 'radio',
      '#title' => $this->t('Unpublish past events'),
      '#return_value' => GoogleCalendarImportEventsInterface::CLEANUP_UNPUB_OLD,
      '#parents' => ['cleanup_policy'],
      '#default_value' => $entity_cleanup_policy,
    ];

    return parent::buildForm($form, $form_state);
  }

}
