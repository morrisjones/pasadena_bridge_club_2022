<?php

namespace Drupal\google_secrets\Plugin\GoogleSecretsStore;

use Drupal\Core\Form\FormStateInterface;
use Drupal\google_secrets\GoogleSecretsStore;
use Drupal\google_secrets\GoogleSecretsStoreException;

/**
 * Class GoogleSecretsStaticFile
 *
 * @package Drupal\google_secrets\Plugin\GoogleSecretsStore
 *
 * @GoogleSecretsStore(
 *   id = "static_file",
 *   title = @Translation("Static file"),
 *   description = @Translation("Store Google API Secrets data in a static file."),
 *   config_name = "google_secrets_store.static_file"
 * )
 */
class GoogleSecretsStaticFile extends GoogleSecretsStore {

  public const CONFIG_SECRET_FILE_NAME = 'secret_file_name';

  /**
   * @inheritDoc
   */
  public function getFilePath(): ?string {
    return $this->configuration[self::CONFIG_SECRET_FILE_NAME] ?? '';
  }

  /**
   * @inheritDoc
   */
  public function setFilePath(?string $path) {
    $this->configuration[self::CONFIG_SECRET_FILE_NAME] = $path;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function get() {
    $secrets_file = $this->getFilePath();

    if (!$secrets_file) {
      throw new GoogleSecretsStoreException('Secrets file name has invalid value.');
    }

    if (is_readable($secrets_file)) {
      $json = file_get_contents($secrets_file);
      $jsondict = json_decode($json, TRUE, 6, JSON_INVALID_UTF8_IGNORE);
      if ($jsondict !== NULL) {
        return $jsondict;
      }
      return FALSE;
    }
    throw new GoogleSecretsStoreException('Unable to read static secrets file: ' . $secrets_file);
  }

  /**
   * @inheritDoc
   */
  public function instructions(): array {
    $form['para'] = [
      '#type' => 'markup',
      '#prefix' => '<pre>' . $this->t(''),
      '#suffix' => '</pre>',
      '#markup' => $this->t('$settings[":configname"][":configkey"] = ":value"',
                     [
                       ':configname' => $this->getConfigName(),
                       ':configkey' => self::CONFIG_SECRET_FILE_NAME,
                       ':value' => $this->getFilePath(),
                     ]),
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array &$form, FormStateInterface $form_state): array {
    $form = [];

    $form['google_secret_type'] = [
      '#type' => 'hidden',
      '#value' => $this->getId(),
    ];
    $form['google_secret_static'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret File path'),
      '#default_value' => $this->getFilePath(),
      '#maxlength' => 255,
      '#size' => 60,
      '#description' => $this->t('Server path to the file, either relative to Drupal root or absolute.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function render() {
    $markup['para'] = [
      '#type' => 'markup',
      '#markup' => $this->tt('":value"', [':value' => $this->getFilePath()]),
    ];
    return $markup;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): array {
    $file_path = $form_state->getValue('google_secret_static');
    $this->setFilePath($file_path);
    return [self::CONFIG_SECRET_FILE_NAME => $file_path];
  }
}
