<?php

namespace WesecurSecurity\includes\views;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurGlobalVariables;
use WesecurSecurity\includes\WesecurTemplateEngineInterface;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\controllers\WesecurControllerInterface;
use WesecurSecurity\includes\helpers\WesecurWordpressLocalStorage;
use WesecurSecurity\includes\helpers\WesecurWafProtection;


define('WAF_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/views/waf.tpl');

/**
 * Firewall (WAF) information page
 *
 *
 * @class 	   WesecurWafPage
 * @package    WeSecur Security
 * @subpackage WesecurWafPage
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWafPage extends WesecurWordpressPage {

    const TEMPLATE = WAF_TEMPLATE;
    const PAGE_NAME = 'Firewall (WAF)';
    const PAGE_URL = 'wesecur-waf';
    const NONCE_NAME = 'wesecur_waf';

    protected $wafProtection;

    function __construct(WesecurControllerInterface $controller, WesecurTemplateEngineInterface $templateEngine = null,
                         WesecurApiRequestInterface $apiRequest = null,
                         WesecurWordpressLocalStorage $localStorage = null,
                         WesecurWafProtection $wafProtection = null) {

        parent::__construct($controller, $templateEngine, $apiRequest);

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage();
        }

        if ($wafProtection == null) {
            $wafProtection = new WesecurWafProtection();
        }

        $this->wafProtection = $wafProtection;
    }

    public function render() {

        $commonTemplateVariables = array(
            "page_selected" => true,
            "premium_modal" => TEMPLATE_MODAL_WAF,
            "name" => __('Firewall (WAF)', 'wesecur-security'),
            "waf_banned_title" => __("Banned IPs", 'wesecur-security'),
            "waf_banned_description" => __("List of IP address blocked while doing bruteforce to your login page.", 'wesecur-security'),
            "waf_banned_time" => __("Time", 'wesecur-security'),
            "waf_banned_ip" => __("IP", 'wesecur-security'),
            "waf_title" => __("Web Application Firewall (WAF)", 'wesecur-security'),
            "waf_requests_yaxes_label" => __("Requests", 'wesecur-security'),
            "waf_description" => __("A web application firewall (WAF) protects your website from attacks and breaches originating from the Internet. Below You can see how our WAF protects you from Bad Bots, DDoS, SQLi and XSS", 'wesecur-security'),
            "waf_api_required_description" => __('Free version of the plugin includes only basic bruteforce protection. We hardly recommend you to <a href="#" data-toggle="modal" data-target="#enableWaf">get our Firewall (WAF) license</a> to protect your website from attacks and hackers.', 'wesecur-security'),
            "modal_waf_text" => __('Premium plan includes all these features: <ul class="wesecursecurity-modal-features-list"><li>Cloud website firewall</li><li>Automatic site clean-up</li><li>Database malware scanner</li><li>Notifications about most important security vulnerabilities</li></ul>', 'wesecur-security'),
            "modal_waf_title" => __('Improve the security of your site', 'wesecur-security'),
            "modal_waf_btn_text" => __('Buy for $309.15/year', 'wesecur-security'),
            "modal_waf_url" => WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_PREMIUM_YEARLY_URL')
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
        parent::render();
    }

    public function initAction() {
        $initVars = array(
            "waf_banned_ips" => $this->wafProtection->getBannedIps(),
            "waf_requests" => json_encode(array()),
            "waf_premium_text" => __('Get our Firewall (WAF) license to enable this section.', 'wesecur-security')
        );
        $this->templateEngine->setVariables($initVars);

    }

    protected function loadStyles() {
        wp_enqueue_style('wesecursecurity_vendorbootstraptable');
    }

    protected function loadScripts() {
        wp_enqueue_script('wesecursecurity_vendor-chartjs');
        wp_enqueue_script('wesecursecurity_vendorbootstraptable');
        wp_enqueue_script('wesecursecurity_vendor-bootstrap-table-locale-es');
        wp_enqueue_script('wesecursecurity_vendor-bootstrap-table-locale-en');
    }
}