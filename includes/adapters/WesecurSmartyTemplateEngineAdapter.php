<?php

namespace WesecurSecurity\includes\adapters;

use WesecurSecurity\includes\WesecurTemplateEngineInterface;
use Smarty as WesecurSmarty;

class WesecurSmartyTemplateEngineAdapter implements WesecurTemplateEngineInterface {

    protected $smarty;

    function __construct(Smarty $smarty = null, $caching = false) {

        if ($smarty == null) {
            $smarty = new WesecurSmarty();
        }
        //$smarty->setCaching($caching);
        $smarty->setCompileDir(WESECURSECURITY_LOCAL_STORAGE_FOLDER . '/templates_cache');
        $this->smarty = $smarty;
    }

    public function setVariables($variables) {
        foreach($variables as $key => $value) {
            if (is_array($value) && array_key_exists('assignByRef', $value)) {
                $this->smarty->assignByRef($key, $value['assignByRef']);
            }else{
                $this->smarty->assign($key, $value);
            }
        }
    }

    public function render($template) {
        $this->smarty->display($template);
    }
}
