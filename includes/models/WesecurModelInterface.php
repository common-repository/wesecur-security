<?php

namespace WesecurSecurity\includes\models;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

interface WesecurModelInterface {

    public function get();

    public function post();

    public function put();

    public function delete();

}