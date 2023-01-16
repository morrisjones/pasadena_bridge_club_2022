<?php

namespace Drupal\google_secrets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Declare a worker class for interacting with google api secrets data.
 *
 * Plugin Namespace: Plugin\GoogleSecretsStore
 *
 * For a working example, see
 * Drupal\google_secrets\Plugin\GoogleSecretsStore\GoogleSecretsManagedFile.
 *
 * @see \Drupal\Core\Queue\GoogleSecretsStoreInterface
 * @see \Drupal\Core\Queue\GoogleSecretsStore
 * @see \Drupal\Core\Queue\GoogleSecretsStoreManager
 * @see plugin_api
 *
 * @Annotation
 */
class GoogleSecretsStore extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable title of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * A human-readable description of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the config channel to use.
   */
  public $config_name;

}
