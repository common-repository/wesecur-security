<?php

namespace WesecurSecurity\includes\views;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurGlobalVariables;
use WesecurSecurity\includes\WesecurTemplateEngineInterface;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\WesecurWordpressNotification;
use WesecurSecurity\includes\controllers\WesecurControllerInterface;
use WesecurSecurity\includes\exceptions\WesecurNotFoundApiException;
use WesecurSecurity\includes\exceptions\WesecurBadRequestApiException;
use WesecurSecurity\includes\exceptions\WesecurTooManyRequestsApiException;
use WesecurSecurity\includes\helpers\WesecurWordpressIntegrityChecker;
use WesecurSecurity\includes\helpers\WesecurWordpressLocalStorage;
use WesecurSecurity\includes\helpers\WesecurMalwareChecker;

//
define('ANTIVIRUS_TEMPLATE_SECTION_INTEGRITY', WESECURSECURITY_TEMPLATES_PATH . '/views/sections/integrity.tpl');
define('ANTIVIRUS_TEMPLATE_SECTION_MALWARE', WESECURSECURITY_TEMPLATES_PATH . '/views/sections/malware.tpl');
define('ANTIVIRUS_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/views/antivirus.tpl');
define('ANTIVIRUS_TEMPLATE_MODAL_REMOTE_SCAN', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/remote-scan.tpl');
define('SETTINGS_TEMPLATE_MODAL_ANTIVIRUS_PREMIUM_REQUIRED', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/antivirus-premium.tpl');


/**
 * Antivirus information page
 *
 *
 * @class 	   WesecurAntivirusPage
 * @package    WeSecur Security
 * @subpackage WesecurAntivirusPage
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurAntivirusPage extends WesecurWordpressPage {

    const TEMPLATE_SECTION_INTEGRITY = ANTIVIRUS_TEMPLATE_SECTION_INTEGRITY;
    const TEMPLATE_SECTION_MALWARE = ANTIVIRUS_TEMPLATE_SECTION_MALWARE;
    const TEMPLATE = ANTIVIRUS_TEMPLATE;
    const TEMPLATE_MODAL_REMOTE_SCAN = ANTIVIRUS_TEMPLATE_MODAL_REMOTE_SCAN;
    const TEMPLATE_MODAL_PREMIUM_REQUIRED = SETTINGS_TEMPLATE_MODAL_ANTIVIRUS_PREMIUM_REQUIRED;
    const DB_EXTERNAL_MALWARE = 'wesecur-external-malware.php';
    const DB_SERVER_MALWARE = 'wesecur-malware.php';
    const DB_INTEGRITY = 'wesecur-integrity.php';
    const DB_INTEGRITY_IGNORE = 'wesecur-integrity-ignore.php';

    const PAGE_NAME = 'Antivirus';
    const PAGE_URL = 'wesecur-antivirus';
    const NONCE_NAME = 'wesecur_antivirus';

    protected $integrityChecker;
    protected $localStorage;

    function __construct(WesecurControllerInterface $controller, WesecurTemplateEngineInterface $templateEngine = null,
                         WesecurApiRequestInterface $apiRequest = null,
                         WesecurWordpressIntegrityChecker $integrityChecker = null,
                         WesecurWordpressLocalStorage $localStorage = null,
                         WesecurMalwareChecker $malwareChecker = null) {

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

        $this->integrityChecker = $integrityChecker;
        $this->malwareChecker = $malwareChecker;
        $this->localStorage = $localStorage;
    }

    public function render() {
        $commonTemplateVariables = array(
            "page_selected" => "true",
            "integrity_template" => self::TEMPLATE_SECTION_INTEGRITY,
            "malware_template" => self::TEMPLATE_SECTION_MALWARE,
            "remote_scan_modal" => self::TEMPLATE_MODAL_REMOTE_SCAN,
            "antivirus_premium_modal" => TEMPLATE_MODAL_SERVERSIDE,
            "antivirus_action_dropdown_title" => __('Actions', 'wesecur-security'),
            "modal_serverside_text" => __('Essential plan includes all these features: <ul class="wesecursecurity-modal-features-list"><li>Automatic site clean-up</li><li>Database malware scanner</li><li>Notifications about most important security vulnerabilities</li></ul>', 'wesecur-security'),
            "modal_serverside_title" => __('Improve the security of your site', 'wesecur-security'),
            "modal_serverside_btn_text" => __('Buy for $94.33/year', 'wesecur-security'),
            "modal_serverside_url" => WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_ESSENTIAL_YEARLY_URL'),
            "malware_api_required_description" => __('Free version of the plugin includes only remote scanner. We hardly recommend you to use <a href="#" data-toggle="modal" data-target="#enableServerSideScanner">Server Side Scanner</a> to analyze all your website files.', 'wesecur-security'),
            "malware_title" => __('Server Side Scanner', 'wesecur-security'),
            "malware_description" => __('Server Side Scanner analyzes all the files in your website looking for virus and malware. Any file listed below must be cleaned.', 'wesecur-security'),
            "malware_start_button_title" => __('Start Malware Check', 'wesecur-security'),
            "malware_antivirus_disclaimer" => '',
            "malware_danger_description" => __('Your website is infected. This is the list of your site’s files with malware. Please review them and clean your site as soon as possible. Try to find the root cause to avoid reinfections. If you need any help, please contact us.', 'wesecur-security'),
            "malware_ok_description" => __('Congratulations! There is no malware in your website', 'wesecur-security'),
            "malware_ignore_file" => __('Ignore file', 'wesecur-security'),
            "malware_restore_file" => __('Restore file', 'wesecur-security'),
            "malware_delete_file" => __('Delete file', 'wesecur-security'),
            "malware_fix_file" => __('Fix file', 'wesecur-security'),
            "malware_last_analysis_text" => __('Last analysis:', 'wesecur-security'),
            "external_malware_title" => __('Remote Scanner', 'wesecur-security'),
            "external_malware_description" => __('Remote scanner will check your website for known malware and blacklisting status. Please notice this scanner ONLY have access to what is visible on the browser so we do not guarantee 100% accuracy. This scanner DOES NOT detect malware on your website files, phising or backdoors. So, if there is no issue detected you may have a more complex and hidden problem. You can update to premium or contact us for a complete website malware analysis.', 'wesecur-security'),
            "external_malware_danger_description" => __('We identified malware. That might indicate a hack. Please use the Server Side Scanner for a complete analysis.', 'wesecur-security'),
            "external_malware_ok_description" => __('We inspect your WordPress files and look for malware.', 'wesecur-security'),
            "external_malware_urls_scanned" => __('Urls Scanned', 'wesecur-security'),
            "external_malware_urls_malware" => __('Urls with malware', 'wesecur-security'),
            "external_malware_javascript_scanned" => __('Javascript Files Scanned', 'wesecur-security'),
            "external_malware_start_button_title" => __('Start Remote Scanner', 'wesecur-security'),
            "external_malware_website_text" => __('Website', 'wesecur-security'),
            "external_malware_info_text" => __('Info', 'wesecur-security'),
            "external_malware_modal_title" => __('Remote Scanner details', 'wesecur-security'),
            "external_malware_modal_scan_text" => __('Scan', 'wesecur-security'),
            "external_malware_modal_urls_text" => __('Malware urls', 'wesecur-security'),
            "external_malware_modal_javascript_text" => __('Javascript files', 'wesecur-security'),
            "external_malware_modal_btn_close_text" => __('Close', 'wesecur-security'),
            "external_malware_modal_server_text" => __('Server', 'wesecur-security'),
            "external_malware_modal_technology_text" => __('Technology', 'wesecur-security'),
            "external_malware_more_details" => __('View more details', 'wesecur-security'),
            "blacklists_title" => __('Blacklists', 'wesecur-security'),
            "blacklists_description" => __('If your site is blacklisted means Google or other authorities warn users that your site is deceptive or dangerous.', 'wesecur-security'),
            "blacklists_ok_description" => __('Your site does not appear to be blacklisted. If you still see security warnings on your site, contact us for a more complete scan', 'wesecur-security'),
            "integrity_title" => __('WordPress Integrity', 'wesecur-security'),
            "integrity_description" => __('We check if there are changes in your WordPress core files comparing them with WordPress.org files. Any change might be caused by a hack.', 'wesecur-security'),
            "integrity_start_button_title" => __('Start Integrity Check', 'wesecur-security'),
            "integrity_apply_button_title" => __('Apply', 'wesecur-security'),
            "integrity_restore_file" => __('Restore file', 'wesecur-security'),
            "integrity_delete_file" => __('Delete file', 'wesecur-security'),
            "integrity_title_status" => __('File Status', 'wesecur-security'),
            "integrity_title_size" => __('Size', 'wesecur-security'),
            "integrity_title_modified" => __('Modified Date', 'wesecur-security'),
            "integrity_title_file" => __('File', 'wesecur-security'),
            "integrity_fix_file" => __('Fix file', 'wesecur-security'),
            "integrity_alert" => false,
            "integrity_ignore_file" => __('Ignore file', 'wesecur-security'),
            "integrity_danger_description" => sprintf(__('Your core files were modified which could mean your site has been hacked. We hardly recommend you to use our <a href="%s" target="_blank">Server Side Scanner</a> to check if there are any malware issues in your site.', 'wesecur-security'), WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_WP_PLUGIN_URL')),
            "integrity_ok_description" => __('Your site integrity is OK', 'wesecur-security'),
            "integrity_disclaimer" => __('I understand that this operation can not be reverted.', 'wesecur-security'),
            "integrity_modal_title" => __('Fix integrity issue', 'wesecur-security'),
            "integrity_apply_action" => __('Apply action', 'wesecur-security'),
            "integrity_close_action" => __('Close', 'wesecur-security')
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
        parent::render();
    }

    public function initAction() {
        $this->loadDataTables();
    }

    protected function loadDataTables() {
        $apiKey = WesecurSettingsPage::getApiKey();

        $this->loadIntegrity($apiKey);
        $this->loadExternalMalware($apiKey);
        $this->loadMalwareFromApi($apiKey);
    }

    public function fixIntegrityAction($data) {

        $files = json_decode(str_replace("\\", "", $data['selectedFiles']));

        foreach ($files as $file) {

            if ($file->fixable) {
                switch ($file->status) {
                    case "modified":
                    case "deleted":
                        $this->integrityChecker->replaceFile($file->file);
                        break;
                    case "added":
                        $this->integrityChecker->deleteFile($file->file);
                        break;
                }
            }
        }

        $this->checkIntegrityAction();
    }

    public function loadIntegrity($apiKey) {
        if (!empty($apiKey)) {
            //$this->loadIntegrityFromApi($apiKey);
            $this->loadIntegrityFromDatabase();
        }else{
            $this->loadIntegrityFromDatabase();
        }
    }

    public function loadExternalMalware($apiKey) {
        if (!empty($apiKey)) {
            $malwareFiles = $this->loadExternalMalwareFromApi($apiKey);
        }else{
            $malwareFiles = $this->loadExternalMalwareFromDatabase();
        }

        $templateVariables = array(
            "blacklists" => $malwareFiles->blacklists,
            "external_malware_files" => $malwareFiles->malware,
            "external_malware_javascript_files" => $malwareFiles->js_links,
            "external_malware_domain" => $malwareFiles->domain,
            "external_malware_num_urls" => $malwareFiles->urls_scanned,
            "external_malware_page_scanned" => $malwareFiles->pages_scanned,
            "external_malware_server_type" => $malwareFiles->server->type,
            "external_malware_server_tech" => $malwareFiles->server->x_powered_by,
        );

        $this->templateEngine->setVariables($templateVariables);
    }

    public function loadMalwareFromApi($apiKey) {

        $malwareResults = json_decode('{"num_malware_files":0, "malware_files":[], "scanned_at": ""}');
        $malwareApiRequired = true;

        if (!empty($apiKey)) {
            $malwareApiRequired = false;
            try {
                $malwareResults = $this->malwareChecker->getServerSideMalwareFromApi($apiKey);
            }catch (WesecurBadRequestApiException $badRequest) {
                WesecurWordpressNotification::error(__("Malware results are not available right now. Please try again in a few minutes. If it happens again, please contact us.", 'wesecur-security'));
            }catch (WesecurNotFoundApiException $notFound) {
                //
            }
        }

        $templateVariables = array(
            "malware_files" => json_encode($malwareResults->malware_files),
            "malware_alert" =>  $malwareResults->num_malware_files > 0,
            "malware_api_required" => $malwareApiRequired,
            "malware_scanned_at" => $malwareResults->scanned_at
        );

        $this->templateEngine->setVariables($templateVariables);
    }

    public function loadExternalMalwareFromApi($apiKey) {

        $malwareResults = '{"malware":[], "blacklists": [], "js_links": [], "urls_scanned": 0}';
        $malwareResults = json_decode($malwareResults);

        if (!empty($apiKey)) {
            try {
                $malwareResults = $this->malwareChecker->getRemoteMalwareFromApi($apiKey);
            } catch (WesecurBadRequestApiException $badRequest) {
                WesecurWordpressNotification::error(__("Remote Malware results are not available right now. Please try again in a few minutes. If it happens again, please contact us.", 'wesecur-security'));
            } catch (WesecurNotFoundApiException $notFound) {
                //
            }

            try {
                $blacklistResults = $this->malwareChecker->getBlacklistsFromApi($apiKey);
                $malwareResults->blacklists = $blacklistResults->blacklists;
            }catch (WesecurBadRequestApiException $badRequest) {
                WesecurWordpressNotification::error(__("Blacklist results are not available right now. Please try again in a few minutes. If it happens again, please contact us.", 'wesecur-security'));
            }catch (WesecurNotFoundApiException $notFound) {
                //
            }
        }

        return $malwareResults;
    }

    public function loadIntegrityFromApi($apiKey) {

    }

    public function loadExternalMalwareFromDatabase() {
        return $this->malwareChecker->getRemoteMalwareIssuesFromDatabase();
    }

    public function loadIntegrityFromDatabase() {

        $integrityFiles = $this->integrityChecker->getIntegrityIssues();

        $templateVariables = array(
            "integrity_files" => json_encode($integrityFiles),
            "integrity_alert" => count($integrityFiles) > 0
        );

        $this->templateEngine->setVariables($templateVariables);
    }

    public function checkRemoteMalwareAction() {
        try {
            $apiResponse = $this->malwareChecker->checkRemoteMalware('false');
            $malwareScanResult = json_decode($apiResponse->getBody());
            $this->localStorage->save((array)$malwareScanResult, self::DB_EXTERNAL_MALWARE);
            WesecurWordpressNotification::success(__("Remote Scanner finished successfully. Check your results.", 'wesecur-security'));
        }catch (WesecurBadRequestApiException $badRequest) {
            WesecurWordpressNotification::error(__("Remote Scanner is not available right now. Please try again in a few minutes. If it happens again, please contact us.", 'wesecur-security'));
        }catch (WesecurNotFoundApiException $notFound) {
            //
        }catch (WesecurTooManyRequestsApiException $notFound) {
            WesecurWordpressNotification::warning(__("We are already checking your site's malware. Please, wait until it is done.", 'wesecur-security'));
        }catch (\Exception $e) {
            WesecurWordpressNotification::error(sprintf(__('Your website is not compatible with our Remote Scanner. Please <a href="%s" target="_blank">contact us.</a>', 'wesecur-security'), WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_CONTACT_URL')));
        }

        $this->loadDataTables();
    }


    public function checkMalwareAction() {

        $apiKey = WesecurSettingsPage::getApiKey();

        if (!empty($apiKey)) {
            try {
                $this->malwareChecker->checkServerSideMalware($apiKey);
                WesecurWordpressNotification::success(__("Server Side Malware scanner has started. Refresh page in a few minutes to see the results.", 'wesecur-security'));
            }catch (WesecurBadRequestApiException $badRequest) {
                WesecurWordpressNotification::error(__("Server Side Malware Scanner is not available right now. Please try again in a few minutes. If it happens again, please contact us.", 'wesecur-security'));
            }catch (WesecurNotFoundApiException $notFound) {
                WesecurWordpressNotification::error(__("We couldn't find any Plan associated at this domain name. Please, contact us.", 'wesecur-security'));
            }catch (WesecurTooManyRequestsApiException $notFound) {
                WesecurWordpressNotification::warning(__("We are already checking your site's malware. Please, wait until it is done.", 'wesecur-security'));
            }
        }else{
            WesecurWordpressNotification::warning(__("You need to configure an API KEY to use this action. Check our Plans.", 'wesecur-security'));
        }

        $this->loadDataTables();
    }

    protected function loadSections() {

    }

    protected function checkIntegrityAction() {
        $integrityResult = $this->integrityChecker->check();
        $this->localStorage->save($integrityResult, self::DB_INTEGRITY);
        $this->loadDataTables();
    }

    protected function ignoreIntegrityAction($data) {
        $files = json_decode(str_replace("\\", "", $data['selectedFiles']));

        $ignoredFiles = array();
        foreach ($files as $file) {
            $ignoredFiles[] = $file->file;
        }

        $integrityIgnoredFiles = $this->localStorage->read(self::DB_INTEGRITY_IGNORE);

        if ($integrityIgnoredFiles === FALSE) {
            $integrityIgnoredFiles = '[]';
        }

        $integrityIgnoredFiles = json_decode($integrityIgnoredFiles, true);
        $ignoredFiles = array_merge($integrityIgnoredFiles, $ignoredFiles);

        $this->localStorage->save($ignoredFiles, self::DB_INTEGRITY_IGNORE);
        $this->checkIntegrityAction();
    }

    protected function deleteIntegrityAction($data) {
        $files = json_decode(str_replace("\\", "", $data['selectedFiles']));
        foreach ($files as $file) {
            $this->integrityChecker->deleteFile($file->file);
        }

        $this->checkIntegrityAction();
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