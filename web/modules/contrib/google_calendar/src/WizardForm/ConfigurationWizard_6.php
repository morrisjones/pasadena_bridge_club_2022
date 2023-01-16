<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\google_secrets\GoogleSecretsStoreException;

/**
 * ConfigurationWizard page 6.
 */
class ConfigurationWizard_6 extends ConfigurationWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_6';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = \Drupal::config('google_calendar.settings');

    /** @var \Drupal\google_secrets\GoogleSecretsStoreManager $google_secrets_store_manager */
    $google_secrets_store_manager = \Drupal::service('plugin.manager.google_secrets');

    /** @var \Drupal\google_secrets\GoogleClientFactory $google_secrets_client_factory */
    $google_secrets_client_factory = \Drupal::service('google_secrets.google_client.factory');

    /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface[] $store_types */
    $store_types = $google_secrets_store_manager->getDefinitions();

    $file_id = NULL;
    $file_content = NULL;

    /* *****************************************  */
    /* ******        Introduction         ****** */

    $form['intro'] = [
      '#type' => 'details',
      '#title' => $this->t('Step 6: Import credentials file'),
      '#open' => TRUE,
    ];

    $form['intro']['para_1'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('The credentials file just downloaded from Google must be made available to the website. The supported configuration methods are shown here. Add others by creating a new "google_secrets" module plugin.') . '</p>',
    ];

    $store_type = $this->store->get('client_secret_type') ?? $config->get('client_secret_type') ?? 'static_file';
    $store_config = $this->store->get('client_secret_config') ?? $config->get('client_secret_config') ?? [];

    $form['client_secret_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secrets Storage Selection'),
      '#attributes' => ['class' => ['secrets_configured']],
      '#prefix' => $this->t('Select the storage option you wish to use.') . ' ',
      '#suffix' => '<strong>' . $this->t('Note: If code in settings.php is controlling this value, changes here will be ineffective.') . '</strong>',
      '#required' => TRUE,
    ];
    foreach ($store_types as $store_type => $store_detail) {
      $form['client_secret_types'][$store_detail['id']]['radio'] = [
        '#type' => 'radio',
        '#title' => $store_detail['title'],
        '#description' => $store_detail['description'],
        '#return_value' => $store_type,
        '#parents' => ['client_secret_types'],
        '#default_value' => ($config->get('client_secret_type') === $store_type) ? $store_type : FALSE,
      ];

      $other_store = $google_secrets_client_factory->getSecretStore($store_type, $store_config);
      $subform = [];
      $form['client_secret_configs'][$store_detail['id']]['settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings'),
        '#attributes' => ['class' => ['secrets_configured']],
        '#states' => [
          'visible' => [
            ':input[name="client_secret_types"]' => ['value' => $store_type],
          ],
        ],
      ];
      $form['client_secret_configs'][$store_detail['id']]['settings'][] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'store-' . $store_type],
        $store_type => $other_store->buildForm($subform, $form_state),
      ];
    }

    /* *****************************************  */
    /* ******        Store Config         ****** */

    $form['intro']['para_4'] = [
      '#type' => 'markup',
      '#markup' =>
        '<p>' . $this->t('The secrets store is currently set to: @type',
                ['@type' => $store_type]) . '</p>',
    ];

    $form['step_6'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Google Credentials installed'),
      '#default_value' => $this->store->get('step_6') ?: '',
      '#description' => $this->t('Check this when you have configured the account credentials.'),
    ];

    $form['actions']['previous'] = [
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_five'),
    ];
    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\google_secrets\GoogleSecretsStoreManager $google_secrets_store_manager */
    $google_secrets_store_manager = \Drupal::service('plugin.manager.google_secrets');

    // Ask the store plugin to parse form_state to extract its config.
    $store_type = $form_state->getValue('client_secret_types');
    $store_config = [];
    if ($store_type) {
      /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface $store */
      $store = $google_secrets_store_manager->createInstance($store_type, $store_config);
      if ($store) {
        $store_config = $store->submitForm($form, $form_state);
      }
    }

    // Store the type and config into local storage...
    $this->store->set('client_secret_type', $store_type);
    $this->store->set('client_secret_config', $store_config);
    $this->store->set('step_6', $form_state->getValue('step_6'));

    $this->saveData();

    $form_state->setRedirect('google_calendar.config_wizard_seven');
  }

}
