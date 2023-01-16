<?php

namespace Drupal\google_secrets;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base implementation for a GoogleSecretsStore plugin.
 *
 * @see \Drupal\google_secrets\GoogleSecretsStoreInterface
 * @see \Drupal\google_secrets\GoogleSecretsStoreManager
 * @see \Drupal\google_secrets\Annotation\GoogleSecretsStore
 * @see plugin_api
 */
abstract class GoogleSecretsStore extends PluginBase implements GoogleSecretsStoreInterface {

  use StringTranslationTrait;

  protected $config_factory;
  protected $config;
  protected $googleSecretsStoreManager;

  /**
   * GoogleSecretsStore constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->googleSecretsStoreManager = \Drupal::service('plugin.manager.google_secrets');
    $this->config_factory = \Drupal::service('config.factory');
    $this->config = $this->config_factory->getEditable($this->getConfigName());
  }

  /**
   * The name of the module or theme providing the help topic.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * The name of the module or theme providing the help topic.
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * The name of the module or theme providing the help topic.
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * The name of the module or theme providing the help topic.
   */
  public function getConfigName() {
    return $this->pluginDefinition['config_name'];
  }

  /**
   * The plugin manager.
   *
   * @return GoogleSecretsStoreManagerInterface
   */
  public function getGoogleSecretsStoreManager(): GoogleSecretsStoreManagerInterface {
    return $this->googleSecretsStoreManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

}
