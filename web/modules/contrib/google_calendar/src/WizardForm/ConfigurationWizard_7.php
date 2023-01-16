<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\google_secrets\GoogleSecretsStoreException;

/**
 * ConfigurationWizard page 7.
 */
class ConfigurationWizard_7 extends ConfigurationWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_7';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = \Drupal::config('google_calendar.settings');

    $google_secrets_store_manager = \Drupal::service('plugin.manager.google_secrets');
    $file_id = NULL;
    $file_content = '';
    $store = NULL;
    $client_factory = NULL;

    /* *****************************************  */
    /* ******        Introduction         ****** */

    $form['intro'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Step 7: Credentials Summary'),
      '#open' => TRUE,
    ];

    // Should have been set by page 6...
    $store_type = $this->store->get('client_secret_type') ?? $config->get('client_secret_type') ?? 'static_file';
    $store_config = $this->store->get('client_secret_config') ?? $config->get('client_secret_config') ?? [];

    if ($store_type) {
      /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface $store */
      $store = $google_secrets_store_manager->createInstance($store_type, $store_config);
    }

    /* *****************************************  */
    /* ******        Store Config         ****** */

    $form['intro']['client_secret_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secrets Storage Status'),
      '#attributes' => ['class' => ['secrets_configured']]
    ];
    $form['intro']['client_secret_container']['store_type'] = [
      '#type' => 'markup',
      '#markup' =>
        '<p>' . $this->t('The secrets store is currently set to: <code>@type</code>',
                ['@type' => $store_type]) . '</p>',
    ];

    /* *****************************************  */
    /* ******        Current Secrets      ****** */

    if ($store) {
      try {
        $file_content = $store->get();
      }
      catch (GoogleSecretsStoreException $ex) {
      }
    }

    if (!empty($file_content) && isset($file_content['type'])) {
      $form['intro']['client_secret_container']['#attributes']['class'][] = 'success';
      $form['intro']['client_secret_container']['client_secret_info'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('The secrets have been defined for account: <code>@account</code>',
                              ['@account' => $file_content['client_email']]) . '</p>',
      ];
    }
    else {
      $form['intro']['client_secret_container']['#attributes']['class'][] = 'failure';
      $form['intro']['client_secret_container']['client_secret_info'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('The secrets have not been configured.') . '</p>',
      ];
    }

    /* *****************************************  */
    /* ******           Next Steps        ****** */

    $include_import = FALSE;
    $message_class = '';
    $message = '';
    if ($store) {
      $google_secrets_factory = \Drupal::service('google_secrets.google_client.factory');
      $store = $this->store->get('client_secret_type') ?? 'static_file';
      $config = $this->store->get('client_secret_config') ?? [];
      $app = 'Site Calendar';

      $client = NULL;
      try {
        $client = new \Google_Service_Calendar($google_secrets_factory->get($store, $config, $app));

        if (!$client) {
          $message = '<p>' . $this->t('Could not access the Google API. No further information is available.') . '</p>';
          $message_class = 'error';
        }
        else {
          $message = '<p>' . $this->t('Using Google Client library: @ver',
                              ['@ver' => $client->getClient()->getLibraryVersion()]) . '</p>';

          $list = $client->calendarList->listCalendarList();
          $remote_calendars = $list->getItems();

          if (count($remote_calendars) === 0) {
            $message .= '<p>' . $this->t('The supplied credentials are accepted by Google, but no calendars are associated with this account. Have you told Google to share any calendars with the Service Account?') . '</p>';
            $message_class = 'warning';
          }
          elseif (count($remote_calendars) > 0) {
            $message .= '<p>' . $this->t('The supplied credentials are accepted by Google, and there are calendars shared with the account. Use the <a href="@link">Import Calendars</a> page to select which of those calendars to import.',
                                 ['@link' => Url::fromRoute('google_calendar.import_calendars')]) . '</p>';
            $message_class = 'info';
            $include_import = TRUE;
          }
        }
      }
      catch (\Exception $ex) {
        $message = '<p>' . $this->t('Google Calendar could not be used with the supplied credentials: @msg',
          ['@msg' => $ex->getMessage()]) . '</p>';
        $message_class = 'error';
      }
    }

    $form['intro']['calendar_status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Calendar Status'),
      '#attributes' => ['class' => ['secrets_configured']],
    ];
    $form['intro']['calendar_status']['para_3'] = [
      '#type' => 'markup',
      '#prefix' => "<div class=\"$message_class\">",
      '#suffix' => '</div>',
      '#markup' => $message,
    ];

    $form['actions']['previous'] = [
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_six'),
    ];
    if ($include_import) {
      $form['actions']['importer'] = [
        '#type' => 'link',
        '#title' => $this->t('Import Calendars'),
        '#attributes' => [
          'class' => ['button'],
        ],
        '#weight' => 1,
        '#url' => Url::fromRoute('google_calendar.import_calendars'),
      ];
    }
    $form['actions']['submit']['#value'] = $this->t('Done');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_state->setRedirect('entity.google_calendar.collection');
  }

}
