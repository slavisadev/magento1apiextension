<?php

include_once __DIR__ . '/../../config.php';

abstract class ApiExtension_Magento_Base extends PHPUnit_Framework_TestCase
{
    protected $headers = array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    );

    private $oauthClient;

    public function setUp()
    {
        Mage::app('default');

        $this->oauthClient = new OAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
        $this->oauthClient->setToken(TOKEN, SECRET);
    }

    protected function get($path, $data = array())
    {
        try {
            $this->oauthClient->fetch($this->getApiUrl($path), $data, OAUTH_HTTP_METHOD_GET, $this->headers);
        } catch (OAuthException $e) {
            print_r($e->lastResponse);
            throw $e;
        }

        return json_decode($this->oauthClient->getLastResponse(), true);
    }

    protected function post($path, $data)
    {
        try {
            $this->oauthClient->fetch($this->getApiUrl($path), json_encode($data), OAUTH_HTTP_METHOD_POST, $this->headers);

        } catch (OAuthException $e) {
            print_r($e->lastResponse);
            throw $e;
        }

        return $this->oauthClient->getLastResponseInfo();
    }

    protected function put($path, $data)
    {
        try {
            $this->oauthClient->fetch($this->getApiUrl($path), json_encode($data), OAUTH_HTTP_METHOD_PUT, $this->headers);
        } catch (OAuthException $e) {
            print_r($e->lastResponse);
            throw $e;
        }

        return $this->oauthClient->getLastResponseInfo();
    }

    protected function delete($path)
    {
        try {
            $this->oauthClient->fetch($this->getApiUrl($path), array(), OAUTH_HTTP_METHOD_DELETE, $this->headers);
        } catch (OAuthException $e) {
            print_r($e->lastResponse);
            throw $e;
        }

        return json_decode($this->oauthClient->getLastResponse(), true);
    }

    private function getApiUrl($path)
    {
        return API_URL . $path;
    }
}
