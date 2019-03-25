<?php

/**
 * @file
 * Contains \Drupal\flysystem_webdav\Flysystem\Webdav.
 */

namespace Drupal\flysystem_webdav\Flysystem;

use Sabre\DAV\Client;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use GuzzleHttp\Psr7\Uri;
use League\Flysystem\WebDAV\WebDAVAdapter;
use League\Flysystem\Filesystem;

/**
 * Drupal plugin for the "Webdav" Flysystem adapter.
 *
 * @Adapter(id = "webdav")
 */
class Webdav implements FlysystemPluginInterface {

  use FlysystemUrlTrait;

 /**
   * The WebDAV client.
   *
   * @var \WebDAV\Client
   */
  protected $client;

  /**
   * The WebDAV base URI.
   *
   * @var string
   */
  protected $baseURI;

  /**
   * The path prefix inside the WebDAV folder.
   *
   * @var string
   */
  protected $prefix;

  /**
   * The WebDAV username.
   *
   * @var string
   */
  protected $userName;

  /**
  * The WebDAV password.
  *
  * @var string
  */
  protected $password;


  /**
   * Constructs a Webdav object.
   *
   * @param array $configuration
   *   Plugin configuration array.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(array $configuration) {
    $this->prefix = isset($configuration['prefix']) ? $configuration['prefix'] : '';
    $this->baseURI = $configuration['base_uri'];
    $this->userName = $configuration['user_name'];
    $this->password = $configuration['password'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    try {
      $adapter = new WebDAVAdapter($this->getClient(), $this->prefix);
    }

    catch (\Exception $e) {
      $adapter = new MissingAdapter();
    }

    return $adapter;
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    try {
      $flysystem = new Filesystem($adapter);
      $files = $flysystem->listContents('/', true);
    }
    catch (\Exception $e) {
      return [[
        'severity' => RfcLogLevel::ERROR,
        'message' => 'The Webdav client failed with: %error.',
        'context' => ['%error' => $e->getMessage()],
      ]];
    }

    return [];
  }

  /**
   * Returns the Webdav client.
   *
   * @return \Webdav\Client
   *   The Webdav client.
   */
  protected function getClient() {
    if (!isset($this->client)) {
      $this->client = new Client($this->token, $this->clientId);
    }

    return $this->client;
  }

}
