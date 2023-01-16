<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * ConfigurationWizard page 3.
 */
class ConfigurationWizard_3 extends ConfigurationWizardBase {

  public const DEVELOPER_CONSOLE_APIS = 'https://console.developers.google.com/apis/library';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_calendar_configuration_wizard_3';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['intro'] = [
      '#type' => 'details',
      '#title' => $this->t('Step 3: Enable the Google Calendar APIs'),
      '#open' => TRUE,
    ];
    $project_id = $this->store->get('google_project') ? 'project='.$this->store->get('google_project') : '';
    $url = self::DEVELOPER_CONSOLE_APIS . '?' . $project_id . '&supportedpurview=project';
    $output = '<ol>';
    $output .= '<li><p>' . t('Navigate to <a href=":link">API LIbrary: :link</a> using the sidebar item "Library".',
                             [':link' => $url]) . '</p></li>';
    $output .= '<li><p>' . t('In the G-Suite group click on the Calendar tile.') . '</p></li>';
    $output .= '<li><p>' . t('On the Calendar API page, click on the blue "Enable" button.') . '</p></li>';
    $output .= '</ol>';

    $form['intro']['para'] = [
      '#type' => 'markup',
      '#markup' => $output,
    ];

    $form['step_3'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('Google Calendar API Enabled'),
      '#default_value' => $this->store->get('step_3') ?: '',
      '#description' => $this->t('Check this when you have enabled the Google Calendar API.')
    ];

    $form['actions']['previous'] = [
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 0,
      '#url' => Url::fromRoute('google_calendar.config_wizard_two'),
    ];
    $form['actions']['submit']['#value'] = $this->t('Next');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step_3', $form_state->getValue('step_3'));

    $form_state->setRedirect('google_calendar.config_wizard_four');
  }

}
