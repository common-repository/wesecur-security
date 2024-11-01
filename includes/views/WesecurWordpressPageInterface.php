<?php

namespace WesecurSecurity\includes\views;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

interface WesecurWordpressPageInterface {

    public function render();

    public function addToMenu();

    public function getName();

    public function getUrl();

    public function initAction();

    public function getLocale();
}