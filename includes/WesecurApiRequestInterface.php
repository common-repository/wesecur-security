<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

interface WesecurApiRequestInterface {

    public function get($url, $vars = array(), $headers = array());

    public function head($url, $vars = array(), $headers = array());

    public function post($url, $vars = array(), $headers = array());

    public function put($url, $vars = array(), $headers = array());

    public function setUserAgent($userAgent);

    public function verifySSL($verifySSL);
}