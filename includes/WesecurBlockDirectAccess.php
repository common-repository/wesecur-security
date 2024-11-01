<?php

namespace WesecurSecurity\includes;

if (!defined('WESECURSECURITY_LOADED')) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit(0);
}