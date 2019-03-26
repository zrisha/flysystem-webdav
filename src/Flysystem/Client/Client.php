<?php

namespace Drupal\flysystem_webdav\Flysystem\Client;

use Sabre\DAV\Client as DAVClient;
use Sabre\HTTP;

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