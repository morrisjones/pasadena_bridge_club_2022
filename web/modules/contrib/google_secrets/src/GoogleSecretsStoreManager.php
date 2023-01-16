<?php

namespace Drupal\google_secrets;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\google_secrets\Annotation\GoogleSecretsStore;

/**
 * Defines the Google secrets store manager.
 *
 * @see \Drupal\google_secrets\GoogleSecretsStoreInterface
 * @see \Drupal\google_secrets\GoogleSecretsStore
 * @see \Drupal\google_secrets\Annotation\GoogleSecretsStore
 * @see plugin_api
 */
class GoogleSecretsStoreManager extends DefaultPluginManager implements GoogleSecretsStoreManagerInterface {

  /**
   * Constructs a GoogleSecretsStoreManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GoogleSecretsStore',
      $namespaces,
      $module_handler,
      GoogleSecretsStoreInterface::class,
      GoogleSecretsStore::class);
    $this->alterInfo('google_secrets_store');
    $this->setCacheBackend($cache_backend, 'google_secrets_store');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\google_secrets\GoogleSecretsStoreInterface
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return parent::createInstance($plugin_id, $configuration);
  }

}
