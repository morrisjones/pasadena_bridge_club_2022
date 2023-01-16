<?php

namespace Drupal\google_secrets\Commands;

use Drupal;

use Drupal\google_calendar\GoogleClientFactory;
use Drupal\google_secrets\GoogleSecretsStoreInterface;
use Drush\Commands\DrushCommands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\UnstructuredListData;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Commands, and the namespace is Drupal/__MODULE__/Commands.
 *
 * In addition to a commandfile like this one, you need to add a drush.services.yml
 * in root of your module like this module does.
 */
class GoogleSecretsCommands extends DrushCommands {

  protected $container;

  public function __construct($container) {
    parent::__construct();
    $this->container = $container;
  }

  /**
   * @return mixed
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * List the events for a Calendar using the Google API directly.
   *
   * @command gsec:status
   *
   * @throws \Exception
   *   Exception if the Start or End dates are badly defined.
   */
  public function eventList($options = []) {
    /** @var GoogleClientFactory $client_factory */
    $client_factory = Drupal::service('google_secrets.google_client.factory');
    $client = $client_factory->get();

    /** @var \Drupal\google_secrets\GoogleSecretsStoreManager $service */
    $service = $this->container->get('plugin.manager.google_secrets');
    $plugin = $service->createInstance('static_file');
  }
}
