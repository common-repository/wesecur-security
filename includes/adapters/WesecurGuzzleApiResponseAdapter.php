<?php

namespace WesecurSecurity\includes\adapters;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurApiResponseInterface;

class WesecurGuzzleApiResponseAdapter implements WesecurApiResponseInterface {

    protected $response;

    function __construct($response) {
        $this->response = $response;
    }

    public function getStatusCode() {
        return intval($this->response->getStatusCode());
    }
    public function getHeaders() {
        return $this->response->getHeaders();
    }

    public function getHeader($header) {
        return $this->response->getHeader($header);
    }

    public function getBody() {
        return $this->response->getBody();
    }

    public function getError() {

    }

}



