<?php

namespace Drupal\google_secrets;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Google_Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GoogleClientFactory.
 *
 * @package Drupal\google_secrets
 */
class GoogleClientFactory {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\google_secrets\GoogleSecretsStoreManagerInterface
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * GoogleClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   * @param \Drupal\google_secrets\GoogleSecretsStoreManagerInterface $manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   */
  public function __construct(ConfigFactory $configFactory, GoogleSecretsStoreManagerInterface $manager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->config = $configFactory->get('google_secrets.settings');
    $this->manager = $manager;
    $this->logger = $loggerFactory->get('google_secrets');
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.google_secrets'),
      $container->get('logger.factory')
    );
  }

  /**
   * Return an instance of the named GoogleSecretsStore plugin.
   *
   * @param string $type
   *   The machine name of the storage plugin to use.
   *
   * @param array $config
   *
   * @return \Drupal\google_secrets\GoogleSecretsStoreInterface
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   *   If an exception is thrown from either Drupal or the Google client code.
   */
  public function getSecretStore(string $type, array $config = []): GoogleSecretsStoreInterface {
    try {
      /** @var \Drupal\google_secrets\GoogleSecretsStoreInterface $store */
      $store = $this->manager->createInstance($type, $config);
      return $store;
    }
    catch (\Exception $e) {
      throw new GoogleSecretsStoreException("Plugin type {$type} not found.", 0, $e);
    }
  }

  /**
   * Return a configured Client object.
   *
   * @param string $type
   *   The machine name of the storage plugin to use.
   * @param array $config
   *   Plugin configuration settings.
   * @param string $appname
   *   The application name to pass to Google. If nothing passed, the name is
   *   not set.
   *
   * @return \Google_Client
   *   The constructed Google_Client API interface, or exceptions are thrown.
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException If an exception is thrown from either Drupal or the Google client code.
   */
  public function get(string $type, array $config = [], string $appname = ''): Google_Client {

    $credentials = $this->getSecretStore($type, $config);
    try {
      $secret_file = $credentials->getFilePath();
    }
    catch (\Exception $e) {
      throw new GoogleSecretsStoreException("Unable to access secret storage for {$type}.", 0, $e);
    }

    try {
      $client = new Google_Client();
      $client->setAuthConfig($secret_file);

      if (!empty($appname)) {
        $client->setApplicationName($appname);
      }

      $scopes = [
        \Google_Service_Calendar::CALENDAR,
        \Google_Service_Calendar::CALENDAR_READONLY,
      ];
      $client->setScopes($scopes);

      // Config HTTP client and config timeout:
      $client->setHttpClient(
        new GuzzleHttpClient(
          [
            'timeout' => 10,
            'connect_timeout' => 10,
            'verify' => FALSE,
          ]));
    }
    catch (\Exception $e) {
      if ($e instanceof Google_Exception) {
        throw new GoogleSecretsStoreException(
          "Unable to create Google_Client using auth scheme {$type}", 0, $e);
      }
      if ($e instanceof InvalidArgumentException) {
        throw new GoogleSecretsStoreException(
          "Unable to create Google_Client : unable to access auth, or Guzzle client error ({$type})", 0, $e);
      }
      if ($e instanceof LogicException) {
        throw new GoogleSecretsStoreException(
          "Unable to create Google_Client : json error {$type}", 0, $e);
      }
      /** @noinspection PhpUnhandledExceptionInspection */
      throw $e;
    }
    return $client;
  }
}
