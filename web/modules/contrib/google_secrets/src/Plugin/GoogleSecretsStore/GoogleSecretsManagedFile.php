<?php

namespace Drupal\google_secrets\Plugin\GoogleSecretsStore;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\Entity\File;
use Drupal\google_secrets\GoogleSecretsStore;
use Drupal\google_secrets\GoogleSecretsStoreException;

/**
 * Class GoogleSecretsManagedFile
 *
 * @package Drupal\google_secrets\Plugin\GoogleSecretsStore
 *
 * @GoogleSecretsStore(
 *   id = "managed_file",
 *   title = @Translation("Managed file"),
 *   description = @Translation("Store Google API secrets data in a Drupoal-managed file"),
 *   config_name = "google_secrets_store.managed_file"
 * )
 */
class GoogleSecretsManagedFile extends GoogleSecretsStore {

  public const CONFIG_SECRET_FILE_ID = 'secret_file_id';

  /**
   * Check that the secret file id is valid and fix it if not. Return the fixed ID or FALSE.
   *
   * @return bool|int
   *   FALSE if the ID was not valid, otherwise the ID.
   */
  private function ensureFileIdValid() {
    $secret_id = $this->configuration[self::CONFIG_SECRET_FILE_ID] ?? '';
    if (!is_numeric($secret_id)) {
      return FALSE;
    }
    return (int) $secret_id;
  }

  /**
   * Return the managed file ID of the secrets file.
   *
   * @return bool|int
   */
  public function getFileId() {
    return $this->ensureFileIdValid();
  }

  /**
   * Set the managed file ID of the secrets file.
   *
   */
  public function setFileId($file_id) {
    $this->configuration[self::CONFIG_SECRET_FILE_ID] = $file_id;
  }

  /**
   * @inheritDoc
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function getFilePath(): ?string {
    $secret_id = $this->getFileId();

    if (!is_numeric($secret_id)) {
      throw new GoogleSecretsStoreException('Google Secrets managed file id has invalid value.');
    }
    if ($file = File::load($secret_id)) {
      return \Drupal::service('file_system')->realpath($file->getFileUri());
    }
    throw new GoogleSecretsStoreException('Google Secrets managed file could not be loaded.');
  }

  /**
   * @inheritDoc
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function get() {
    $secret_id = $this->getFileId();

    if ($secret_id === FALSE) {
      throw new GoogleSecretsStoreException('Google Secrets file id has invalid value.');
    }

    if ($file = File::load($secret_id)) {
      $filepath = \Drupal::service('file_system')->realpath($file->getFileUri());

      if (is_readable($filepath)) {
        $json = file_get_contents($filepath);
        $jsondict = json_decode($json, TRUE, 6, JSON_INVALID_UTF8_IGNORE);
        if ($jsondict !== NULL) {
          return $jsondict;
        }
        return FALSE;
      }
      throw new GoogleSecretsStoreException('Unable to read managed Google Secrets file: ' . $filepath);
    }
  }

  /**
   * @inheritDoc
   */
  public function render() {
    $markup['para'] = [
      '#type' => 'markup',
      '#markup' => $this->t('":value"', [':value' => $this->getFilePath()]),
    ];
    return $markup;
  }

  /**
   * @inheritDoc
   */
  public function instructions(): array {
    $form['para'] = [
      '#type' => 'markup',
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#markup' => $this->t('$settings[":configname"][":key"] = ":value"',
        [
          ':configname' => $this->getConfigName(),
          ':key' => self::CONFIG_SECRET_FILE_ID,
          ':value' => $this->getFileId(),
        ]),
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array &$form, FormStateInterface $form_state): array {
    $form = [];

    $private_exists = FALSE;
    try {
      $privatefile_service = \Drupal::service('stream_wrapper.private');
      if ($privatefile_service instanceof StreamWrapperInterface) {
        if ($privatefile_service->getName()) {
          $private_exists = TRUE;
        }
      }
    }
    catch (\Exception $ex) {
    }

    $form['google_secret_type'] = [
      '#type' => 'hidden',
      '#value' => $this->getId(),
    ];

    if (!$private_exists) {
      $form['para'] = [
        '#type' => 'markup',
        '#markup' => $this->t('The Drupal "private:" filesystem <strong>must</strong> be configured for managed files to be uploadable.'),
      ];
    }
    else {
      $form['google_secret_managed'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Upload Client Secret File'),
        '#upload_location' => 'private://google-secrets/',
        '#default_value' => '',
        '#description' => $this->t('Client Secret JSON file.'),
        '#upload_validators' => [
          'file_validate_extensions' => ['json'],
        ],
      ];
      $fileid = $this->getFileId();
      if ($fileid !== NULL && ($file = File::load($fileid))) {
        $form['google_secret_managed']['#default_value'] = ['target_id' => $file->id()];
      }
    }
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): array {
    $file_id = $form_state->getValue('google_secret_managed');
    if (is_array($file_id)) {
      $file_id = reset($file_id);
    }
    $this->setFileId($file_id);
    return [self::CONFIG_SECRET_FILE_ID => $file_id];
  }

}
