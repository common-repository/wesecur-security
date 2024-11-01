<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

class WesecurWordpressNotification {

    private $message;

    function __construct($message) {
        $this->message = $message;

    }

    public static function warning($message, $defaultStyle = false) {
        static::render($message, 'alert-warning', $defaultStyle);
    }

    public static function success($message, $defaultStyle = false) {
        static::render($message, 'alert-success', $defaultStyle);
    }

    public static function error($message, $defaultStyle = false) {
        static::render($message, 'alert-danger', $defaultStyle);
    }

    public static function render($message, $notificationTypeClass, $defaultStyle) {

        if ($defaultStyle) {
            printf('<div class="notice notice-error">
                          <p><strong>WeSecur Security</strong> - %s</p>
                          </div>',
                $message);

        }else{
            printf( '<div class="bootstrap-wrapper wesecur-notifications-box row">
                            <div class="col-md-12">
                                <div class="alert alert-dismissible fade show %s" role="alert">
                                <strong>WeSecur Security</strong> - %s
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                </div>
                            </div>
                        </div>',
                $notificationTypeClass,
                $message);
        }

    }

}