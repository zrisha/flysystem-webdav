<?php

/**
 * @file
 * Contains \Drupal\flysystem_webdav\Flysystem\Webdav.
 */

namespace Drupal\flysystem_webdav\Flysystem;

use Sabre\DAV\Client as DAVClient;
use Sabre\HTTP;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use GuzzleHttp\Psr7\Uri;
use League\Flysystem\WebDAV\WebDAVAdapter as DAVAdapter;
use League\Flysystem\Filesystem;


class WebDAVAdapter extends DAVAdapter{
  private static $metadataFields = [
    '{DAV:}displayname',
    '{DAV:}creationdate',
    '{DAV:}getcontentlength',
    '{DAV:}getcontenttype',
    '{DAV:}isroot',
    '{DAV:}getlastmodified',
    '{SeedDMS:}id',
    '{DAV:}resourcetype',
    '{DAV:}getcontenttype'
  ];

  public function listContents($directory = '', $recursive = false)
  {
      $location = $this->applyPathPrefix($this->encodePath($directory));
      $response = $this->client->propFind($location, self::$metadataFields, 1);

      array_shift($response);
      $result = [];

      foreach ($response as $path => $object) {
          $path = rawurldecode($this->removePathPrefix($path));
          $object = $this->normalizeObject($object, $path);
          $result[] = $object;

          if ($recursive && $object['type'] === 'dir') {
              $result = array_merge($result, $this->listContents($object['path'], true));
          }
      }

      return $result;
  }

  private function isDirectory(array $object)
  {
    if(isset($object['{DAV:}resourcetype'])){
      $val = $object['{DAV:}resourcetype']->getValue();
      return $val[0] === '{DAV:}collection';
    } else{
      return false;
    }
  }
}

class Client extends DAVClient{
  /**
   * Coppied from the DAV lib with small edit due to XML error
   */
  function propFind($url, array $properties, $depth = 0) {

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $root = $dom->createElementNS('DAV:', 'd:propfind');
    $prop = $dom->createElement('d:prop');

    foreach ($properties as $property) {

        list(
            $namespace,
            $elementName
        ) = \Sabre\Xml\Service::parseClarkNotation($property);

        if ($namespace === 'DAV:') {
            $element = $dom->createElement('d:' . $elementName);
        } else {
            $element = $dom->createElementNS($namespace, 'x:' . $elementName);
        }

        $prop->appendChild($element);
    }

    $dom->appendChild($root)->appendChild($prop);
    $body = $dom->saveXML();

    $url = $this->getAbsoluteUrl($url);

    $request = new HTTP\Request('PROPFIND', $url, [
        'Depth'        => $depth,
        'Content-Type' => 'application/xml'
    ], $body);

    $response = $this->send($request);

    if ((int)$response->getStatus() >= 400) {
        throw new HTTP\ClientHttpException($response);
    }

    //EDIT: Removes any leading white space
    $resBody = $response->getBodyAsString();
    $resBody = strstr($resBody, '<?xml');
    $result = $this->parseMultiStatus($resBody);

    // If depth was 0, we only return the top item
    if ($depth === 0) {
        reset($result);
        $result = current($result);
        return isset($result[200]) ? $result[200] : [];
    }

    $newResult = [];
    foreach ($result as $href => $statusList) {

        $newResult[$href] = isset($statusList[200]) ? $statusList[200] : [];

    }
    return $newResult;
  }
}


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