<?php

namespace Drupal\google_calendar\WizardForm;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigurationWizardBase.
 *
 * @package Drupal\google_calendar\WizardForm
 */
abstract class ConfigurationWizardBase extends FormBase {

  /**
   * The keys we expect to see in the wizard's key store.
   *
   * @var string[]
   */
  protected $form_keys = [
    'google_project',
    'service_account',
    'client_secret_type',
    'client_secret_config',
    'step_1',
    'step_2',
    'step_3',
    'step_4',
    'step_5',
    'step_6',
  ];

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The session manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * Constructs a WizardFormBase.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              SessionManagerInterface $session_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;

    $this->store = $this->tempStoreFactory->get('configuration_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('session_manager')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    if (!isset($_SESSION['config_wizard_holds_session']) && $this->currentUser()->isAnonymous()) {
      $_SESSION['config_wizard_holds_session'] = true;
      $this->sessionManager->start();
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * Saves the data from the multistep form to long-term storage.
   */
  protected function saveData() {
    $config = $this->configFactory()->getEditable('google_calendar.settings');

    $config->set('client_secret_type', $this->store->get('client_secret_type'));
    $config->set('client_secret_config', $this->store->get('client_secret_config'));
    $config->save();

    \Drupal::messenger()->addStatus($this->t('The form has been saved.'));
    $this->deleteStore();
  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the multistep form.
   */
  protected function deleteStore() {
    foreach ($this->form_keys as $key) {
      $this->store->delete($key);
    }
  }
}
