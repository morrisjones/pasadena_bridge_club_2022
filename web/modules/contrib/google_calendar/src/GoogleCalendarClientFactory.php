<?php

namespace Drupal\google_calendar;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\google_secrets\GoogleClientFactory;
use Drupal\google_secrets\GoogleSecretsStoreInterface;
use Google_Client;
use Google_Service_Calendar;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GoogleCalendarClientFactory.
 *
 * @package Drupal\google_calendar
 */
class GoogleCalendarClientFactory {

  /**
   * Configuration settings for auth client.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The Google_Client factory to use.
   *
   * @var \Drupal\google_secrets\GoogleClientFactory
   */
  protected $clientFactory;

  /**
   * Logger interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * GoogleCalendarClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration factory.
   * @param \Drupal\google_secrets\GoogleClientFactory $client_factory
   *   Factory for Google_Client objects.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory.
   */
  public function __construct(ConfigFactoryInterface $config, GoogleClientFactory $client_factory, LoggerChannelFactoryInterface $loggerFactory) {
    $this->clientFactory = $client_factory;
    $this->config = $config->get('google_calendar.settings');
    $this->logger = $loggerFactory->get('google_calendar');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('google_secrets.google_client.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Get a Google_Service_Calendar using the auth set up for google_calendar.
   *
   * @return \Google_Service_Calendar
   *   A new, authenticated instance of the Calendar API object.
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function getCalendarClient(): Google_Service_Calendar {
    $client = $this->getClient();
    return new Google_Service_Calendar($client);
  }

  /**
   * Get a Google_Client using the authentication set up for google_calendar.
   *
   * @return \Google_Client
   *   A new, authenticated instance of the basic Google API object.
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function getClient(): Google_Client {
    $store = $this->config->get('client_secret_type') ?? 'static_file';
    $config = $this->config->get('client_secret_config') ?? [];
    $app = $this->config->get('client_app_name') ?? 'Site Calendar';

    $client = $this->clientFactory->get($store, $config, $app);
    return $client;
  }

  /**
   * Get the Secrets Store object configured for calendar directly.
   *
   * Normally, there is no need to access the store directly because the
   * access can be resolved completely inside getClient().
   *
   * @return \Drupal\google_secrets\GoogleSecretsStoreInterface
   *   The GoogleSecretsStore object.
   *
   * @throws \Drupal\google_secrets\GoogleSecretsStoreException
   */
  public function getStore(): GoogleSecretsStoreInterface {
    $store = $this->config->get('client_secret_type') ?? 'static_file';
    $config = $this->config->get('client_secret_config') ?? [];

    /** @var \Google_Client $client */
    return $this->clientFactory->getSecretStore($store, $config);
  }

}
