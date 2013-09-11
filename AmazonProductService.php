<?php

/*

 Copyright 2013 Christopher Mancini <chris@cmancini.com>

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.

 */

class AmazonProductService {

  private $_associate_tag;
  private $_access_key;
  private $_secret_key;

  const API_BASE_URI = "http://webservices.amazon.com/onca/xml";
  const API_VERSION = "2011-08-01";

  /**
   * Initializes class.
   * $params needed:
   *      'associate_tag' => $params['associate_tag'],
   *      'access' => $params['access'],
   *      'secret' => $params['secret']
   * @param array $params
   */
  public function __construct($access_key, $secret_key, $associate_tag) {
    if(!empty($access_key)) {
      $this->_access_key = $access_key;
    }
    if(!empty($secret_key)) {
      $this->_secret_key = $secret_key;
    }
    if(!empty($associate_tag)) {
      $this->_associate_tag = $associate_tag;
    }
  }

  /**
   * Function for consuming the API via GET method.
   * @param string $uri
   * @return array
   */
  public function get($method, $parameters = array()) {

    $parameters['Service'] = 'AWSECommerceService';
    $parameters['AssociateTag'] = $this->_associate_tag;
    $parameters['AWSAccessKeyId'] = $this->_access_key;
    $parameters['Operation'] = $method;
    $parameters['Version'] = self::API_VERSION;
    $parameters['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');

    $canonical_query  = $this->_buildCanonicalQuery($parameters);

    $signature = $this->_buildSignature($canonical_query);

    $service_url = self::API_BASE_URI . "?" . implode("&", $canonical_query) . "&Signature={$signature}";

    // get response
    $response = file_get_contents($service_url);

    // parse xml string to object
    $parsed_xml = simplexml_load_string($response);

    return $parsed_xml;
  }

  protected function _buildSignature($canonical_query) {

    // sort elements a - z
    asort($canonical_query);

    // connect string with ampersands
    $canonical_string = implode("&", $canonical_query);

    // prepend canonical string with three lines and hash
    $string_to_sign = "GET\nwebservices.amazon.com\n/onca/xml\n{$canonical_string}";

    // calculate HMAC with SHA256 and base64-encoding
    $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $this->_secret_key, TRUE));
    
    // encode the signature for the request
    $signature = str_replace('%7E', '~', rawurlencode($signature));

    return $signature;
  }

  protected function _buildCanonicalQuery($parameters) {
    $canonical_query = array();

    // iterate over parameters making urlencoded key value string pairs
    foreach($parameters as $key => $value) {
      $canonical_query[] = str_replace('%7E', '~', rawurlencode($key)) . "=" . str_replace('%7E', '~', rawurlencode($value));
    }

    return $canonical_query;
  }
}
