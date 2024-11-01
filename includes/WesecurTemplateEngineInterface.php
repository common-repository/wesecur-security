<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

interface WesecurTemplateEngineInterface {

    public function setVariables($variables);

    public function render($template);

}