<?php

namespace Drupal\google_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\google_secrets\GoogleSecretsStoreException;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'google_calendar_settings';
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
    $googleSecretsStoreManager = \Drupal::service('plugin.manager.google_secrets');

    $store_type = $form_state->getValue('client_secret_type');
    $store_config = $config->get('client_secret_config');
    if (!is_array($store_config)) {
      $store_config = [];
    }
    if ($store_type) {
      /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface $store */
      $store = $googleSecretsStoreManager->createInstance($store_type, $store_config);
      $store_config = $store->submitForm($form, $form_state);
    }

    // Save to persistent settings:
    $config
      ->set('client_secret_type', $form_state->getValue('client_secret_type'))
      ->set('cron_disabled', $form_state->getValue('cron_disabled'))
      ->set('cron_frequency', $form_state->getValue('cron_frequency'))
      ->set('client_secret_config', $store_config)
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
    /* ******************      Service Accounts       ****************** */

    $form['accounts'] = [
      '#type' => 'details',
      '#title' => $this->t('Service Account details'),
      '#open' => TRUE,
    ];

    $googleSecretsStoreManager = \Drupal::service('plugin.manager.google_secrets');
    /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface[] $store_types */
    $store_types = $googleSecretsStoreManager->getDefinitions();
    $secret_type = $config->get('client_secret_type');

    $form['accounts']['client_secret_type'] = [
      '#type' => 'container',
      '#title' => $this->t('How should the Google Account secrets be stored?'),
      '#suffix' => '<strong>' . $this->t('Note: If code in settings.php is controlling this value, changes here will be ineffective.') . '</strong>',
      '#prefix' => $this->t('Select the storage option you wish to use.') . ' ',
      '#required' => TRUE,
    ];
    foreach ($store_types as $store_type => $store_detail) {
      $form['accounts']['client_secret_type'][$store_detail['id']]['radio'] = [
        '#type' => 'radio',
        '#title' => $store_detail['title'],
        '#description' => $store_detail['description'],
        '#return_value' => $store_type,
        '#parents' => ['client_secret_type'],
        '#default_value' => ($secret_type === $store_type) ? $store_type : FALSE,
      ];
    }

    /* ***************************************************************** */
    /* **************        Account Storage Config       ************** */

    $form['accounts']['client_secret_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secrets Storage Settings'),
      '#open' => TRUE,
    ];

    $file_id = '';
    $file_content = NULL;
    $subform = [];
    $markup = [];
    $store_config = $config->get('client_secret_config') ?? [];
    /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface $store */
    $store = $googleSecretsStoreManager->createInstance($secret_type, $store_config);
    $form['accounts']['client_secret_settings'] = $store->buildForm($subform, $form_state);

    try {
      $file_id = $store->getFilePath();
      $file_content = $store->get();
    }
    catch (GoogleSecretsStoreException $ex) {
    }

    if (!empty($file_content) && isset($file_content['type'])) {
      $type = $file_content['type'] === 'service_account' ? t('Service Account') : $file_content['type'];
      $mgmturl = Url::fromUri(
        'https://console.developers.google.com/iam-admin/serviceaccounts?project=' . $file_content['project_id']
      );
      $settingsphp_detail = $store->instructions();

      $mgmturl_markup = Link::fromTextAndUrl('console.developers.google.com/iam-admin/...', $mgmturl);
      $markup = [
        '#type' => 'table',
        '#id' => 'account-information',
        '#attributes' => ['class' => ['account-info']],
        '#no_striping' => TRUE,
        '#rows' => [
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('File Path:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $file_id,
                ],
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Account Type:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $type,
                ],
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Account Email:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $file_content['client_email'],
                ],
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Account Key ID:'),
                ],
              ],
              'value' => [
                'data' => [
                  '#markup' => $file_content['private_key_id'],
                ],
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Google Account Management:'),
                ],
              ],
              'value' => [
                'data' => $mgmturl_markup,
              ],
            ],
          ],
          [
            'data' => [
              'label' => [
                'data' => [
                  '#markup' => $this->t('Settings.php configuration:'),
                ],
              ],
              'value' => [
                'data' => $settingsphp_detail,
              ],
            ],
          ],
        ],
      ];
    }
    $form['accounts']['account_info'] = $markup;

    /* ***************************************************************** */
    /* *****************        Advanced Config        ***************** */

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
    ];
    $form['advanced']['intro'] = [
      '#type' => 'markup',
      '#markup' => $this->t('The advanced settings exist for completeness but should not normally be changed. Do so only if you understand the consequences.'),
    ];

    $form['advanced']['cron_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable cron'),
      '#description' => $this->t('Check this if you want to disable cron processing.'),
      '#default_value' => $config->get('cron_disabled'),
    ];

    $form['advanced']['cron_frequency'] = [
      '#type' => 'number',
      '#min' => 300,
      '#max' => 24 * 86400,
      '#title' => $this->t('Minimum interval between updates'),
      '#description' => $this->t('The minimum interval (in seconds) between API requests to Google to update events. If requests are made more frequently than this, some will be ignored.'),
      '#default_value' => $config->get('cron_frequency'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
