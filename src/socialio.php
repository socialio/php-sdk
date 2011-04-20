<?php

/**
 *
 * Copyright 2011 Platogo Interactive Entertainment GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 */

if (!function_exists('curl_init')) {
  throw new Exception('Social.io needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Social.io needs the JSON PHP extension.');
}

/**
 * Provides access to the Social.io Rest API.
 * 
 * @author Christoph Atteneder <christoph@platogo.com>
 * @author Günter Glück <guenter@platogo.com>
 */
class Socialio {

  /**
   * Version of this API.
   */
  const VERSION = '0.0.1';

  /**
   * Default options for curl.
   */
  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'socialio-php-0.0.1',
    CURLOPT_VERBOSE        => true,
    CURLOPT_HTTPHEADER     => array("Accept: application/json"),
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
  );

  public static $DOMAIN_MAP = array(
    'api'	=> 'http://localhost:8080/',
  );

  /**
   * The Client ID.
   */
  protected $clientId;

  /**
   * The Client password.
   */
  protected $password;

  /**
   * The access token.
   */
  protected $accessToken;

  /**
   * The Basic Authentication secret to use.
   */
  protected $secret;

  /**
   * The Social.io Application Name.
   */
  protected $appName;
  
  /**
   * The message that should be shown while a user is redirected during the connect process.
   */
  protected $redirectMessage;

  /**
   * The Social.io User ID.
   */
  protected $userId;

  /**
   * The Social.io User Token.
   */
  protected $userToken;
  
  /**
   * The incoming Social Network request.
   */
  protected $incomingRequest;

  /**
   * The incoming Social Network request.
   */
  protected $body;

  /**
   * Initialize the Social.io SDK.
   * 
   * The configuration:
   * - clientId: the client ID
   * - password: the client password
   * - appName: the Social.io Application ID
   * - redirectMessage: the message that should be shown while a user is redirected during the connect process
   * - incomingRequest: the incoming request from a supported Social Network
   * 
   * The initialization of the Social.io SDK also retrieves a Social.io access token for later use
   * 
   * @param Array $config the Social.io SDK configuration
   */
  public function __construct($config) {
    $this->setClientId($config['clientId']);
    $this->setPassword($config['password']);
    $this->setAppName($config['appName']);
    if (isset($config['redirectMessage'])) {
      $this->setRedirectMessage($config['redirectMessage']);
    } else {
      $this->setRedirectMessage("Loading application...");
    }
    $this->setAccessToken($this->requestToken());
    $this->setIncomingRequest($_REQUEST);
    $this->setBody(@file_get_contents('php://input'));
  }

  /**
   * Connects the actual Social Network User to your Social.io Application.
   * This process may need a redirect that is transparently taken care of for you.
   */
  public function connect() {
    $plain_response = $this->postRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppName()."/user", $this->getIncomingRequest(), $this->getBody());

    $response = json_decode($plain_response, true);

    $this->redirectIfRequiredAndExit($response);

    $this->setUserId($response["user_id"]);
    $this->setUserToken($response["user_token"]);
  }

  /**
   * Calls a Social.io REST API url.
   * Your specified path parameter is prefixed with the current user's url-path.
   * 
   * @param String $path the url-path without the user's url-prefix. (default: the user's profile)
   * @param Array $params the parameters for this api call. (default: null)
   * @param String $method the HTTP-method to use. POST|PUT|DELETE (default: null = GET)
   */
   public function api($path = "", $params = null, $method = null) {
    if (!isset($path) || (isset($path) && (($path == "/me") || ($path == "/")))) {
      $path = "";
    }

    if(isset($method)) {
      if(isset($params)) {
        $params["_method"] = $method;
      } else {
        $params = array("_method" => $method);
      }
    }

    $plain_response = $this->makeRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppName()."/user/".$this->getUserId().$path, $params, null, isset($method));
    return json_decode($plain_response, true);
  }
  
  public function getUserProfile($fields) {
    return $this->api("/", array("fields" => $fields));
  }

  public function getFriends($fields) {
    return $this->api("/friends", array("fields" => $fields));
  }

  public function getUserParams() {
    return $this->api("/param");
  }

  public function deleteParams() {
    return $this->api("/param", null, "DELETE");
  }

  public function getUserRequests() {
    return $this->api("/request");
  }

  public function getUserRequest($requestId) {
    return $this->api("/request/".$requestId);
  }

  public function deleteRequest($requestId) {
    return $this->api("/request/".$requestId, null, "DELETE");
  }

  public function deleteRequests() {
    return $this->api("/request", null, "DELETE");
  }

  //////////////////////////////////////////////////////////////////////////////
  // Private functions
  //////////////////////////////////////////////////////////////////////////////

  private function redirectIfRequiredAndExit($response) {
    if(!isset($response["execute"])) {
      return;
    }
    
    if(strcmp($response["execute"], "redirect") == 0) {
      Header( "HTTP/1.1 301 Moved Permanently" );
      Header( "Location: " . $response["uri"]);
      exit();
    } else if(strcmp($response["execute"], "output") == 0) {
        $headers = $response["headers"];
        foreach($headers as $key => $value) {
            Header($key.': '.$value);            
        }
        $cookies = $response["cookies"];
        foreach($cookies as $key => $value) {
            setcookie($key, $value);
        }
        echo($response["content"]);
        exit();
    } else if(strcmp($response["execute"], "nothing") == 0) {
      throw new SocialIoApiException($response);
    }
  }

  private function setClientId($clientId) {
    $this->clientId = $clientId;
    return $this;
  }

  public function getClientId() {
    return $this->clientId;
  }

  private function setPassword($password) {
    $this->password = $password;
    return $this;
  }

  public function getPassword() {
    return $this->password;
  }

  private function setAppName($appName) {
    $this->appName = $appName;
    return $this;
  }

  public function getAppName() {
    return $this->appName;
  }

  private function setRedirectMessage($redirectMessage) {
    $this->redirectMessage = $redirectMessage;
    return $this;
  }

  public function getRedirectMessage() {
    return $this->redirectMessage;
  }

  private function setAccessToken($token) {
    $this->accessToken = $token;
    $this->setSecret($token);
    return $this;
  }

  private function getAccessToken() {
    return $this->accessToken;
  }

  private function setUserId($connected) {
    $this->userId = $connected;
    return $this;
  }

  public function getUserId() {
    return $this->userId;
  }

  private function setSecret($secret) {
    $this->secret = $secret;
    return $this;
  }

  private function getSecret() {
    return $this->secret;
  }

  private function setIncomingRequest($incomingRequest) {
    $this->incomingRequest = $incomingRequest;
    return $this;
  }

  private function getIncomingRequest() {
    return $this->incomingRequest;
  }

  private function setBody($body) {
    $this->body = $body;
    return $this;
  }

  private function getBody() {
    return $this->body;
  }

  private function setUserToken($userToken) {
    $this->userToken = $userToken;
    return $this;
  }

  public function getUserToken() {
    return $this->userToken;
  }

  private function requestToken() {
    $this->setSecret($this->getPassword());
    $response = json_decode($this->getRequest(self::$DOMAIN_MAP['api']."token", array()), true);
    return $response["token"];
  }

  private function makeRequest($url, $params, $ch=null, $post=null, $body=null) {
    if (!$ch) {
      $ch = curl_init();
    }

    $url = empty($params) ? $url : $url."?".http_build_query($params, null, '&');


    $opts = self::$CURL_OPTS;
    $opts[CURLOPT_URL] = $url;
    
    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }

    $opts[CURLOPT_USERPWD] = $this->getClientId().":".$this->getSecret();

    if($post) {
      $opts[CURLOPT_POST] = true;
      $query_string = http_build_query($params, null, '&');
      // bug in php5 http_build_query method causes nested arrays to be converted into arr[0]= instead of arr[]=
      $query_string = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '[]=', $query_string);
      if(!empty($body)) {
        $existing_headers = $opts[CURLOPT_HTTPHEADER];
        $existing_headers[] = 'Content-Type: application/json'; //content type is not being set automatically for some reason
        $opts[CURLOPT_HTTPHEADER] = $existing_headers;
        $opts[CURLOPT_POSTFIELDS] = $body;
      } 
      else
        $opts[CURLOPT_POSTFIELDS] = $query_string;
    }

    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
  }

  private function postRequest($url, $params = null, $body = null, $ch = null) {
    return $this->makeRequest($url, $params, $ch, true, $body);
  }

  private function getRequest($url, $params = null, $ch = null) {
    return $this->makeRequest($url, $params, $ch, false, null);
  }
}

/**
 * Thrown when an API call returns an exception.
 *
 * @author Günter Glück <guenter@platogo.com>
 */
class SocialIoApiException extends Exception
{
  /**
   * The result from the API server that represents the exception information.
   */
  protected $result;

  /**
   * Make a new API Exception with the given result.
   *
   * @param Array $result the result from the API server
   */
  public function __construct($result) {
    $this->result = $result;

    $code = isset($result['error_code']) ? $result['error_code'] : 0;

    if (isset($result['reason'])) {
      $msg = $result['reason'];
    } else {
      $msg = 'Unknown Error. Check getResult()';
    }

    parent::__construct($msg, $code);
  }

  /**
   * Return the associated result object returned by the API server.
   *
   * @returns Array the result from the API server
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * This will default to 'Exception' for now.
   *
   * @return String
   */
  public function getType() {
    return 'Exception';
  }

  /**
   * To make debugging easier.
   *
   * @returns String the string representation of the error
   */
  public function __toString() {
    $str = $this->getType() . ': ';
    if ($this->code != 0) {
      $str .= $this->code . ': ';
    }
    return $str . $this->message;
  }
}

?>
