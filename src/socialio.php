<?php

if (!function_exists('curl_init')) {
  throw new Exception('Social.io needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Social.io needs the JSON PHP extension.');
}

class Socialio {

    const VERSION = '0.0.1';

    protected $clientId;

    protected $password;

    protected $accessToken;

    protected $secret;

    protected $appId;

    protected $userId;

    protected $userToken;

    public static $DOMAIN_MAP = array(
        'api'	=> 'http://www.social.io/'
    );

    public function __construct($config) {
        $this->setClientId($config['clientId']);
        $this->setPassword($config['password']);
        $this->setAppId($config['appId']);
        $this->setAccessToken($this->requestToken());

        $this->connect($config['get']);
    }

    public function connect($GET_Params) {
        $plain_response = $this->postRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user", $GET_Params);

        $response = json_decode($plain_response, true);

        $this->redirectIfRequiredAndExit($response);

        $this->setUserId($response["user_id"]);
        $this->setUserToken($response["user_token"]);

    }

    public function getUserProfile() {
        $fields = array("fields" => "name,picture");
        $plain_response = $this->getRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId(), $fields);
        return json_decode($plain_response, true);
    }

    public function getFriends() {
        $fields = array("fields" => "name,picture,user_id");
        $plain_response = $this->getRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/friends", $fields);
        return json_decode($plain_response, true);
    }

    public function getUserParams() {
        $plain_response = $this->getRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/param");
        return json_decode($plain_response, true);
    }

    public function getUserRequests() {
        $plain_response = $this->getRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/request");
        return json_decode($plain_response, true);
    }

    public function getUserRequest($requestId) {
        $plain_response = $this->getRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/request/".$requestId);
        return json_decode($plain_response, true);
    }

    public function deleteRequest($requestId) {
        $plain_response = $this->postRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/request/".$requestId, array("_method" => "DELETE"));
        return json_decode($plain_response, true);
    }

    public function deleteRequests() {
        $plain_response = $this->postRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/request", array("_method" => "DELETE"));
    }

    public function deleteParams() {
        $plain_response = $this->postRequest(self::$DOMAIN_MAP['api']."app/".$this->getAppId()."/user/".$this->getUserId()."/params", array("_method" => "DELETE"));
    }

    private function redirectIfRequiredAndExit($response) {
        if(strcmp($response["execute"], "redirect") == 0) {
            Header( "HTTP/1.1 301 Moved Permanently" );
            Header( "Location: " . $response["uri"]);
            exit();
        } else if(strcmp($response["execute"], "output") == 0) {
            echo("loading application...");
            echo($response["content"]);
            exit();
        } else if(strcmp($response["execute"], "nothing") == 0) {
            $this->showErrorAndExit($response);
        }
    }

    private function showErrorAndExit($plain_response) {
        echo("Connection refused.");
        echo("Response from Social.io: " . $plain_response['reason'] . " END OF RESPONSE");
        exit();
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

    private function setAppId($appId) {
        $this->appId = $appId;
        return $this;
    }

    public function getAppId() {
        return $this->appId;
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

    private function makeRequest($url, $GET_Params, $ch=null, $post=null) {
        if (!$ch) {
            $ch = curl_init();
        }

        $url = empty($GET_Params) ? $url : $url."?".http_build_query($GET_Params, null, '&');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->getClientId().":".$this->getSecret());
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        if($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            $query_string = http_build_query($GET_Params, null, '&');
            // bug in php5 http_build_query method causes nested arrays to be converted into arr[0]= instead of arr[]=
            $query_string = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '[]=', $query_string);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        }

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    private function postRequest($url, $GETParams, $ch=null) {
        return $this->makeRequest($url, $GETParams, $ch, true);
    }

    private function getRequest($url, $params=null, $ch=null) {
        return $this->makeRequest($url, $params, $ch, false);
    }
}

?>
