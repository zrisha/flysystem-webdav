<?php

/**
 * @file
 * Contains \Drupal\flysystem_webdav\Flysystem\Webdav.
 */

namespace Drupal\flysystem_webdav\Flysystem;

use Drupal\flysystem_webdav\Flysystem\Client\Client;
use Sabre\HTTP;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem_webdav\Flysystem\Adapter\WebDAVAdapter;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use GuzzleHttp\Psr7\Uri;
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
  protected $baseUri;

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
  * The WebDAV path.
  *
  * @var string
  */
  protected $path;

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
    $this->baseUri = $configuration['base_uri'];
    $this->userName = $configuration['user_name'];
    $this->password = $configuration['password'];
    $this->path = $configuration['path'];
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
      $flysystem = new Filesystem($this->getAdapter());
      $files = $flysystem->has('');
      $flysystem->listContents('');
    }
    catch (\Exception $e) {
      error_log(print_r($e->getTraceAsString(), 1));
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
      $this->client = new Client(array(
        'baseUri' => $this->baseUri,
        'userName'=> $this->userName,
        'password' => $this->password,
      ), $this->path);
    }

    return $this->client;
  }
  
}  