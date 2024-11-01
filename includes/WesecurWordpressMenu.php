<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\adapters\WesecurSmartyTemplateEngineAdapter;


define('MENU_HEADER_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/header.tpl');

/**
 * Class used to render the menu bar
 *
 *
 * @class 	   WesecurWordpressMenu
 * @package    WeSecur Security
 * @subpackage WesecurWordpressMenu
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWordpressMenu {

    const HEADER_TEMPLATE = MENU_HEADER_TEMPLATE;

    protected $pages;
    protected $templateEngine;

    function __construct(array $pages, WesecurTemplateEngineInterface $templateEngine = null) {

        if (is_null($templateEngine)) {
            $templateEngine = new WesecurSmartyTemplateEngineAdapter();
        }

        $this->templateEngine = $templateEngine;
        $this->pages = $pages;
    }

    function render() {
        foreach($this->pages as $page) {
            $page->addToMenu();
        }

        $variables = array(
            'logo' => plugins_url(
                WESECURSECURITY_PLUGIN_FOLDER . '/assets/images/wesecur-logo.png',
                WESECURSECURITY_PLUGIN_PATH
            ),
            'header_template' => self::HEADER_TEMPLATE,
            "pages" => $this->pages,
            "hasMalware" => $GLOBALS["malwareIssues"],
            "need_help_btn_text" => __('Need help to clean your site?', 'wesecur-security'),
            "need_help_url" => WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_HELP_NOW_URL')
        );

        $this->templateEngine->setVariables($variables);
    }
}