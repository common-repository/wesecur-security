<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurCmsUtilsInterface;

class WesecurWordpressCmsUtils implements WesecurCmsUtilsInterface {


    protected $wordpressVersionRegex = array(
        "\$wp_version\s*=\s*'([\d.]+)'",
        "'\$wp_version\s*=\s*\'([\d.]+)\-[a-zA-Z]+-([\d.]+)\'",
        "<br /> Version\s*([\d.]+)");


    function __construct() {

    }

    function getCmsVersion() {

        $wpVersion = '';

        if (isset($GLOBALS['wp_version'])) {
            $wpVersion = $GLOBALS['wp_version'];
        }else {

            //TODO
            $matches = array();
            $files = glob(ABSPATH . "/wp-includes/*.php");

            foreach ($files as $file) {
                $fileContent = @file_get_contents($file);
                foreach ($this->wordpressVersionRegex as $regex) {
                    preg_match($regex, $fileContent,$matches);
                    break;
                }
            }
        }

        return $wpVersion;
    }
}