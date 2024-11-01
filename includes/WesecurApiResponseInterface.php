<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

interface WesecurApiResponseInterface {

    public function getHeaders();

    public function getBody();

    public function getStatusCode();

    public function getHeader($header);

}