<?php

namespace WesecurSecurity\includes\adapters;

use WesecurSecurity\includes\views\WesecurSettingsPage;
use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\exceptions\WesecurNotFoundApiException;
use WesecurSecurity\includes\exceptions\WesecurNotAuthorizedApiException;
use WesecurSecurity\includes\exceptions\WesecurForbiddenApiException;
use WesecurSecurity\includes\exceptions\WesecurInternalServerApiException;
use WesecurSecurity\includes\exceptions\WesecurBadRequestApiException;
use WesecurSecurity\includes\exceptions\WesecurTooManyRequestsApiException;
use WesecurSecurity\includes\exceptions\WesecurTimeoutApiException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WesecurGuzzleApiRequestAdapter implements WesecurApiRequestInterface {

    protected $client;

    protected $followRedirects = false;

    protected $verifySSL;

    protected $userAgent;

    function __construct($baseUri) {
        $this->client = new Client(array('base_uri' => $baseUri));
    }

    function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
    }

    function verifySSL($verifySSL) {
        $this->verifySSL = $verifySSL;
    }

    function delete($url, $vars = array(), $headers = array()) {
        $options = array();
        if (is_array($vars) && !empty($vars)) {
            $options['query'] = $vars;
        }

        return $this->request('DELETE', $url, $headers, $options);
    }

    public function get($url, $vars = array(), $headers = array()) {
        $options = array();
        if (is_array($vars) && !empty($vars)) {
            $options['query'] = $vars;
        }
        return $this->request('GET', $url, $headers, $options);
    }

    public function head($url, $vars = array(), $headers = array()) {
        $options = array();
        if (is_array($vars) && !empty($vars)) {
            $options['query'] = $vars;
        }
        return $this->request('HEAD', $url, $headers, $options);
    }

    public function post($url, $vars = array(), $headers = array()) {
         $options = array();
        if (is_array($vars) && !empty($vars)) {
            $options['json'] = $vars;
        }
        return $this->request('POST', $url, $headers, $options);
    }

    public function put($url, $vars = array(), $headers = array()) {
        $options = array();
        if (is_array($vars) && !empty($vars)) {
            $options['json'] = $vars;
        }
        return $this->request('PUT', $url, $headers, $options);
    }

    protected function request($method, $url, $headers, $options = array()) {
        $options['headers']['User-Agent'] = $this->userAgent;
        $options['headers']['Wesecur-Wordpress-Plugin'] = WesecurSettingsPage::getSiteDomainName();
        $options['verify'] = $this->verifySSL;
        $options['connect_timeout'] = 15;
        $options['timeout'] = 65;

        if (is_array($headers) && !empty($headers)) {
            $options['headers'] = array_merge($options['headers'], $headers);
        }

        try {
            $guzzleResponse = $this->client->request($method, $url, $options);
            $response = new WesecurGuzzleApiResponseAdapter($guzzleResponse);
        }catch (RequestException $requestException) {

            if ($requestException->hasResponse()) {
                switch ($requestException->getResponse()->getStatusCode()) {
                    case 400:
                        throw new WesecurBadRequestApiException($requestException->getResponse()->getBody()->getContents());
                        break;
                    case 401:
                        throw new WesecurNotAuthorizedApiException;
                        break;
                    case 403:
                        throw new WesecurForbiddenApiException;
                        break;
                    case 404:
                        throw new WesecurNotFoundApiException;
                        break;
                    case 429:
                        throw new WesecurTooManyRequestsApiException;
                        break;
                    case 500:
                        throw new WesecurInternalServerApiException;
                        break;
                    default:
                        throw new WesecurBadRequestApiException;
                        break;
                }
            }else{
                error_log("Wesecur API error: " . $requestException->getMessage());
                throw new WesecurTimeoutApiException;
            }
        }

        return $response;

    }
}