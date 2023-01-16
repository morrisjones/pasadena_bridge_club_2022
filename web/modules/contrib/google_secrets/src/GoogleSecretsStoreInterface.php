<?php

namespace Drupal\google_secrets;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

interface GoogleSecretsStoreInterface extends PluginInspectionInterface {
  /**
   * Return the filename of the JSON Secrets file.
   *
   * If the data is not/cannot be stored in a file, returns NULL, although
   * this means the GAPI setAuthConfig() cannot be used to configure the
   * API client.
   *
   * @return string
   */
  public function getFilePath(): ?string;

  /**
   * Get the secret file JSON from the JSON file from Google.
   *
   * @return bool|mixed
   *   A JSON array if the file was loaded and parsed ok, otherwise FALSE if
   *   no file has been set or the JSON was missing/invalid.
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   *   If the secrets file config is not valid, or the file could not be opened.
   */
  public function get();

  /**
   * Return post-save instructions, if necessary.
   *
   * For example, return the settings.php code lines which could be added to
   * configure this store.
   *
   * @return array
   *   An empty array [] if no instructions are necessary, or a render array snippet
   *   containing the tailored instructions to the user.
   */
  public function instructions(): array;

  /**
   * Get a form render array snippet to update the secrets data.
   *
   * For example, a Text field...
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   */
  public function buildForm(array &$form, FormStateInterface $form_state): array;

  /**
   * Return the current secrets data as a render array that prints the current value.
   */
  public function render();

  /**
   * Store detail from a submitted form so as to update secrets data.
   *
   * For example, store form element for Config.
   *
   * Implementations should not assume the config provided in the constructor
   * is complete; values returned should only be from the form.
   *
   * @param array $form
   *   Standard Drupal form array.
   * @param FormStateInterface $form_state
   *   Standard Drupal form state array containing the submitted data.
   *
   * @return array
   *   Array of values that can be stored as sub-config items by the caller.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): array;
}
