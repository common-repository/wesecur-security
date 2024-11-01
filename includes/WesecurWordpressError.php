<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

class WesecurWordpressError {

    private $message;

    function __construct($message) {
        $this->message = $message;
        add_action( 'admin_notices', array($this, 'render' ));
    }

    public function render() {
        printf( '<div class="error notice"><p>%s</p></div>', $this->message);
    }
}