<?php

namespace WesecurSecurity\includes\views;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurGlobalVariables;
use WesecurSecurity\includes\WesecurTemplateEngineInterface;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\controllers\WesecurControllerInterface;
use WesecurSecurity\includes\helpers\WesecurWordpressIntegrityChecker;
use WesecurSecurity\includes\helpers\WesecurWordpressLocalStorage;
use WesecurSecurity\includes\helpers\WesecurWordpressEvents;
use WesecurSecurity\includes\helpers\WesecurWordpressHardening;
use WesecurSecurity\includes\helpers\WesecurMalwareChecker;
use WesecurSecurity\includes\helpers\WesecurPlanSettings;


define('DASHBOARD_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/views/dashboard.tpl');

/**
 * Dashboard information page
 *
 *
 * @class 	   WesecurDashboardPage
 * @package    WeSecur Security
 * @subpackage WesecurDashboardPage
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurDashboardPage extends WesecurWordpressPage {

    const TEMPLATE = DASHBOARD_TEMPLATE;
    const PAGE_NAME = 'Dashboard';
    const PAGE_URL = 'wesecur-dashboard';
    const NONCE_NAME = 'wesecur_dashboard';


    protected $wesecurSettings;
    protected $wordpressHardening;
    protected $planSettings;

    function __construct(WesecurControllerInterface $controller, WesecurTemplateEngineInterface $templateEngine = null,
                         WesecurApiRequestInterface $apiRequest = null,
                         WesecurWordpressIntegrityChecker $integrityChecker = null,
                         WesecurWordpressLocalStorage $localStorage = null,
                         WesecurMalwareChecker $malwareChecker = null,
                         WesecurWordpressEvents $wordpressEvents = null,
                         WesecurWordpressHardening $wordpressHardening = null,
                         WesecurPlanSettings $planSettings = null) {

        parent::__construct($controller, $templateEngine, $apiRequest);

        if ($integrityChecker == null) {
            $integrityChecker = new WesecurWordpressIntegrityChecker($apiRequest);
        }

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage();
        }

        if ($malwareChecker == null) {
            $malwareChecker = new WesecurMalwareChecker($apiRequest);
        }

        if ($wordpressEvents == null) {
            $wordpressEvents = new WesecurWordpressEvents();
        }

        if ($wordpressHardening == null) {
            $wordpressHardening = new WesecurWordpressHardening();
        }

        if (is_null($planSettings)) {
            $planSettings = new WesecurPlanSettings();
        }

        $this->wordpressHardening = $wordpressHardening;
        $this->integrityChecker = $integrityChecker;
        $this->malwareChecker = $malwareChecker;
        $this->localStorage = $localStorage;
        $this->wordpressEvents = $wordpressEvents;
        $this->planSettings = $planSettings;
    }

    public function render() {
        $commonTemplateVariables = array(
            "page_selected" => true,
            "dashboard_strength_server_side_modal" => TEMPLATE_MODAL_SERVERSIDE,
            "dashboard_strength_waf_modal" => TEMPLATE_MODAL_WAF,
            "dashboard_auditlogs_title" => __('Audit Logs', 'wesecur-security'),
            "dashboard_strength_title" => __('Security Strength Meter', 'wesecur-security'),
            "dashboard_strength_description" => __('Website security indicator', 'wesecur-security'),
            "dashboard_strength_enable_bf" => sprintf("Firewall - <a href='%s'>%s</a>", '?page=wesecur-settings', __('Enable Bruteforce protection', 'wesecur-security')),
            "dashboard_info_malware_text" => __('Malware', 'wesecur-security'),
            "dashboard_info_malware_found_text" => __('We identified malware in your website. Please go to Antivirus section to see more details', 'wesecur-security'),
            "dashboard_info_malware_notfound_text" => __('Your website is free of malware', 'wesecur-security'),
            "dashboard_info_blacklist_found_text" => __('Your website is blacklisted. Please go to Antivirus section to see more details', 'wesecur-security'),
            "dashboard_info_blacklist_notfound_text" => __('Your website is not blacklisted.', 'wesecur-security'),
            "dashboard_info_integrity_found_text" => __('WordPress core files were modified. Please go to Antivirus section to see more details', 'wesecur-security'),
            "dashboard_info_integrity_notfound_text" => __('WordPress core files integrity is correct', 'wesecur-security'),
            "dashboard_info_blacklists_text" => __('Blacklists', 'wesecur-security'),
            "dashboard_info_integrity_text" => __('Files Integrity', 'wesecur-security'),
            "dashboard_info_vulnerabilities_text" => __('Vulnerabilities', 'wesecur-security'),
            "dashboard_info_vulnerabilities_found_text" => __('Vulnerabilities', 'wesecur-security'),
            "dashboard_info_vulnerabilities_notfound_text" => __('Vulnerabilities', 'wesecur-security'),
            "dashboard_info_vulnerabilities_enable" => __('Do you want to know if your website has vulnerabilities? Enable Server Side Scanner', 'wesecur-security'),
            "dashboard_info_auditlogs_text" => __('List of actions that are happening in your website. Check that there is no suspicious activity', 'wesecur-security'),
            "dashboard_auditlog_has_activated_plugin" => __('has activated plugin', 'wesecur-security'),
            "dashboard_auditlog_has_deactivated_plugin" => __('has deactivated plugin', 'wesecur-security'),
            "dashboard_auditlog_has_created_user" => __('has created a new user', 'wesecur-security'),
            "dashboard_auditlog_new_account_text" => __('New user account created with name', 'wesecur-security'),
            "dashboard_auditlog_new_account_roles_text" => __('and roles', 'wesecur-security'),
            "dashboard_auditlog_new_account_with_roles_text" => __('with roles', 'wesecur-security'),
            "dashboard_auditlog_user_text" => __('User', 'wesecur-security'),
            "dashboard_auditlog_bruteforce_attempt" => __('Bruteforce login attempt stopped for user', 'wesecur-security'),
            "dashboard_auditlog_authentication_succeeded_text" => __('authentication succeeded', 'wesecur-security'),
            "dashboard_auditlog_has_failed_login" => __('has failed login', 'wesecur-security'),
            "dashboard_auditlog_time_text" => __('Time', 'wesecur-security'),
            "dashboard_auditlog_event_text" => __('Event', 'wesecur-security'),
            "dashboard_auditlog_ip_text" => __('IP', 'wesecur-security'),
            "dashboard_strength_security_label" => __('security', 'wesecur-security'),
            "dashboard_strength_fix_admin_username" => sprintf("Hardening - <a href='%s'>%s</a>", '?page=wesecur-settings', __('Change Admin username', 'wesecur-security')),
            "dashboard_strength_fix_editor" => sprintf("Hardening - <a href='%s'>%s</a>", '?page=wesecur-settings', __('Disable Theme editor', 'wesecur-security')),
            "dashboard_strength_fix_xmlrpc" => sprintf("Hardening - <a href='%s'>%s</a>", '?page=wesecur-settings', __('Protect XML-RPC', 'wesecur-security')),
            "dashboard_strength_fix_php_execution" => sprintf("Hardening - <a href='%s'>%s</a>", '?page=wesecur-settings', __('Disable PHP in sensitive folders', 'wesecur-security')),
            "dashboard_strength_fix_wp_version" => sprintf("Hardening - <a href='%s'>%s</a>", '?page=wesecur-settings', __('Hide WordPress version', 'wesecur-security')),
            "dashboard_strength_fix_enable_scanner" => sprintf("Antivirus - <input type='button' style='font-size: 14px;' class='btn btn-warning' data-toggle='modal' data-target='#enableServerSideScanner' value='%s'>", __('Enable Server Side Scanner', 'wesecur-security')),
            "dashboard_strength_fix_enable_waf" => sprintf("Firewall - <input type='button' style='font-size: 14px;' class='btn btn-warning' data-toggle='modal' data-target='#enableWaf' value='%s'>", __('Enable Web Application Firewall Protection', 'wesecur-security')),
            "dashboard_strength_fix_integrity_error" => sprintf("Antivirus - <a href='%s'>%s</a>", '?page=wesecur-antivirus', __('Fix integrity errors', 'wesecur-security')),
            "dashboard_strength_fix_malware_error" => sprintf("Antivirus - <a href='%s'>%s</a>", '?page=wesecur-antivirus', __('Fix malware issues', 'wesecur-security')),
            "dashboard_strength_recommendations" => __('Increase your security strength by applying this recommendations!', 'wesecur-security'),
            "modal_serverside_text" => __('Essential plan includes all these features: <ul class="wesecursecurity-modal-features-list"><li>Automatic site clean-up</li><li>Database malware scanner</li><li>Notifications about most important security vulnerabilities</li></ul>', 'wesecur-security'),
            "modal_serverside_title" => __('Improve the security of your site', 'wesecur-security'),
            "modal_serverside_btn_text" => __('Buy for $94.33/year', 'wesecur-security'),
            "modal_serverside_url" => WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_ESSENTIAL_YEARLY_URL'),
            "modal_waf_text" => __('Premium plan includes all these features: <ul class="wesecursecurity-modal-features-list"><li>Cloud website firewall</li><li>Automatic site clean-up</li><li>Database malware scanner</li><li>Notifications about most important security vulnerabilities</li></ul>', 'wesecur-security'),
            "modal_waf_title" => __('Improve the security of your site', 'wesecur-security'),
            "modal_waf_btn_text" => __('Buy for $309.15/year', 'wesecur-security'),
            "modal_waf_url" => WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_PREMIUM_YEARLY_URL')
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
        parent::render();
    }

    public function addToMenu() {
        add_menu_page(
            $this->getPageTitle($this->getName()),
            'WeSecur Security',
            'manage_options',
            self::PAGE_URL,
            array($this, 'render'),
            plugins_url(
                WESECURSECURITY_PLUGIN_FOLDER . '/assets/images/menuicon.png',
                WESECURSECURITY_PLUGIN_PATH
            )
        );

        parent::addToMenu();
    }

    public function initAction() {

        $strengthPoints = 0;
        $apiKey = WesecurSettingsPage::getApiKey();
        $isBruteForceEnabled = (WesecurSettingsPage::isBruteforceProtectionEnabled() == 'true');
        $isWordPressVersionHiden = WesecurSettingsPage::hideWordPressVersion();
        $hasIntegrityIssues = $this->integrityChecker->hasIntegrityIssues();
        $hasVulnerabilities = False;
        $hasMalwareIssues = $this->malwareChecker->hasMalwareIssues($apiKey);
        $hasBlacklistIssues = $this->malwareChecker->hasBlacklistIssues($apiKey);
        $existsAdminUsername = $this->wordpressHardening->existsAdminUsername();
        $isFileEditorDisabled = $this->wordpressHardening->isFileEditorDisabled();
        $isXmlRpcDisabled = $this->wordpressHardening->isXmlrpcDisabled();
        $isPhpExecutionDisabled = $this->wordpressHardening->isPhpExecutionDisabled();

        $hasEssentialOrPremiumPlan = false;
        $hasWafPlan = false;

        if (!empty($apiKey)) {
            try {
                $hasEssentialOrPremiumPlan = $this->planSettings->isPlanConfigured($apiKey);
            }catch (\Exception $exception) {
            }
        }

        $strengthPoints += ($isBruteForceEnabled)?9:0;
        $strengthPoints += (!$hasIntegrityIssues)?7:0;
        $strengthPoints += (!$hasMalwareIssues)?17:0;
        $strengthPoints += (!$hasBlacklistIssues)?4:0;
        $strengthPoints += ($isXmlRpcDisabled)?2:0;
        $strengthPoints += ($isPhpExecutionDisabled)?2:0;
        $strengthPoints += (!$existsAdminUsername)?5:0;
        $strengthPoints += ($isFileEditorDisabled)?3:0;
        $strengthPoints += ($isWordPressVersionHiden)?1:0;
        $strengthPoints += ($hasEssentialOrPremiumPlan)?25:0;
        $strengthPoints += ($hasWafPlan)?25:0;

        if ($strengthPoints < 0) {
            $strengthPoints = 0;
        }

        $auditLogs = $this->wordpressEvents->readAuditLogs();
        $templateVariables = array(
            "dashboard_auditlogs" => $auditLogs,
            "dashboard_bf_enabled" => $isBruteForceEnabled,
            "dashboard_integrity_issues" => $hasIntegrityIssues,
            "dashboard_vulnerabilities_issues" => $hasVulnerabilities,
            "dashboard_malware_issues" => $hasMalwareIssues,
            "dashboard_blacklist_issues" => $hasBlacklistIssues,
            "dashboard_admin_username" => $existsAdminUsername,
            "dashboard_editor_disabled" => $isFileEditorDisabled,
            "dashboard_xmlrpc_disabled" => $isXmlRpcDisabled,
            "dashboard_phpexecution_disabled" => $isPhpExecutionDisabled,
            "dashboard_wp_version_hiden" => $isWordPressVersionHiden,
            "dashboard_strength_points" => $strengthPoints,
            "dashboard_vulnerabilities_enabled" => $hasEssentialOrPremiumPlan,
            "dashboard_waf_enabled" => $hasWafPlan
        );

        $this->templateEngine->setVariables($templateVariables);
    }

    protected function loadStyles() {
        wp_enqueue_style('wesecursecurity_flags');
        wp_enqueue_style('wesecursecurity_vendorbootstraptable');
        wp_enqueue_style('wesecursecurity_vendor-c3js');
    }

    protected function loadScripts() {
        wp_enqueue_script('wesecursecurity_vendorbootstraptable');
        wp_enqueue_script('wesecursecurity_vendor-bootstrap-table-locale-es');
        wp_enqueue_script('wesecursecurity_vendor-bootstrap-table-locale-en');
        wp_enqueue_script('wesecursecurity_vendor-d3js');
        wp_enqueue_script('wesecursecurity_vendor-c3js');
    }
}
