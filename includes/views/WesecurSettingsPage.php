<?php

namespace WesecurSecurity\includes\views;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\exceptions\WesecurBadRequestApiException;
use WesecurSecurity\includes\helpers\WesecurPlanSettings;
use WesecurSecurity\includes\helpers\WesecurWordpressLocalStorage;
use WesecurSecurity\includes\WesecurGlobalVariables;
use WesecurSecurity\includes\WesecurTemplateEngineInterface;
use WesecurSecurity\includes\WesecurWordpressNotification;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\controllers\WesecurControllerInterface;
use WesecurSecurity\includes\helpers\WesecurWordpressHardening;
use WesecurSecurity\includes\exceptions\WesecurTimeoutApiException;
use WesecurSecurity\includes\exceptions\WesecurFileNotExistException;
use WesecurSecurity\includes\exceptions\WesecurFileIsNotWritableException;


define('SETTINGS_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/views/settings.tpl');
define('SETTINGS_TEMPLATE_MODAL_ADMIN_HARDENING', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/admin-hardening.tpl');
define('SETTINGS_TEMPLATE_MODAL_FTP_FOLDER', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/ftp-folder.tpl');
define('SETTINGS_TEMPLATE_MODAL_APIKEY', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/get-apikey.tpl');

/**
 * Settings configuration page
 *
 *
 * @class 	   WesecurSettingsPage
 * @package    WeSecur Security
 * @subpackage WesecurSettingsPage
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurSettingsPage extends WesecurWordpressPage {

    const TEMPLATE = SETTINGS_TEMPLATE;
    const TEMPLATE_MODAL_ADMIN_HARDENING = SETTINGS_TEMPLATE_MODAL_ADMIN_HARDENING;
    const TEMPLATE_MODAL_FTP_FOLDER = SETTINGS_TEMPLATE_MODAL_FTP_FOLDER;
    const TEMPLATE_MODAL_APIKEY = SETTINGS_TEMPLATE_MODAL_APIKEY;
    const PAGE_NAME = 'Settings';
    const PAGE_URL = 'wesecur-settings';
    const DEFAULT_LOGIN_ATTEMPTS = 9;
    const DEFAULT_LOGIN_RESET_TIME = 30;
    const DEFAULT_BLOCK_DURTION = 120;
    const DEFAULT_BF_ENABLED = 'false';
    const DEFAULT_HIDE_WP_VERSION = 'false';
    const NONCE_NAME = 'wesecur_settings';

    /** @var WesecurWordpressHardening */
    protected $hardeningUtil;

    /** @var WesecurPlanSettings */
    protected $planSettings;

    /** @var WesecurWordpressLocalStorage */
    protected $localStorage;

    function __construct(WesecurControllerInterface $controller, WesecurTemplateEngineInterface $templateEngine = null,
                         WesecurApiRequestInterface $apiRequest = null,
                         WesecurWordpressHardening $hardeningUtil = null,
                         WesecurPlanSettings $planSettings = null,
                         WesecurWordpressLocalStorage $localStorage = null) {

        parent::__construct($controller, $templateEngine, $apiRequest);

        if ($hardeningUtil == null) {
            $hardeningUtil = new WesecurWordpressHardening();
        }

        if (is_null($planSettings)) {
            $planSettings = new WesecurPlanSettings();
        }

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage();
        }

        $this->hardeningUtil = $hardeningUtil;
        $this->planSettings = $planSettings;
        $this->localStorage = $localStorage;

        add_action('wp_ajax_wesecur_security_ajax_test_credentials', array($this, 'wpAjaxWesecurCredentials'));
        add_action('wp_ajax_wesecur_security_ajax_get_ftp_folders', array($this, 'wpAjaxWesecurGetFtpFolders'));
        add_action('wp_ajax_wesecur_security_ajax_test_connection', array($this, 'wpAjaxWesecurTestConnection'));
    }

    public function wpAjaxWesecurGetFtpFolders() {

        if (!wp_verify_nonce( $_POST['_nonce'], self::NONCE_NAME . 'ftp_ajax' )) {
            wp_send_json_error(array(
                'message' => 'Nonce is not valid'
            ));
        }

        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            wp_send_json_error(array(
                'message' => 'API key not found. Setup your API key before configure the FTP settings.'
            ));
        }

        $apiGetFolderParams = $_POST['payload'];

        try{
            $folderList = $this->planSettings->getFtpFolders($apiKey, $apiGetFolderParams);
            asort($folderList);
            $tree = array();
            foreach ($folderList as $folder) {
                array_push($tree, array("text"=>$folder));
            }
            wp_send_json_success(array(
                'message' => $tree
            ), 200);
        }catch(WesecurBadRequestApiException $badRequest){
            wp_send_json_error(array(
                'message' => "WordPress installation folder not valid"
            ));
        }
    }

    public function wpAjaxWesecurTestConnection() {

        if (!wp_verify_nonce( $_POST['_nonce'], self::NONCE_NAME . 'ftp_ajax' )) {
            wp_send_json_error(array(
                'message' => 'Nonce is not valid'
            ));
        }

        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            wp_send_json_error(array(
                'message' => 'API key not found. Setup your API key before configure the FTP settings.'
            ));
        }

        $apiGetFolderParams = $_POST['payload'];
        $connectionResult = $this->planSettings->testConnection($apiKey, $apiGetFolderParams);

        wp_send_json_success(array(
            'message' => $connectionResult
        ), 200);
    }

    public function wpAjaxWesecurCredentials() {

        if (!wp_verify_nonce( $_POST['_nonce'], self::NONCE_NAME . 'ftp_ajax' )) {
            wp_send_json_error(array(
                'message' => 'Nonce is not valid'
            ));
        }
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            wp_send_json_error(array(
                'message' => 'API key not found. Setup your API key before configure the FTP settings.'
            ));
        }

        $apiGetFolderParams = $_POST['payload'];
        $apiGetFolderParams['path'] = '/';

        unset($_POST['payload']['path']);

        try {
            $this->planSettings->testCredentials($apiKey, $_POST['payload']);
        }catch (WesecurBadRequestApiException $badRequest) {
            $responseMessage = json_decode($badRequest->getMessage(), true);
            wp_send_json_error(array(
                'message' => $responseMessage['message']
            ));
        }catch (WesecurTimeoutApiException $timeout) {
            wp_send_json_error(array(
                'message' => 'Invalid host or port'
            ));
        }catch (\Exception $exception) {
            wp_send_json_error(array(
                'message' => 'Error checking credentials'
            ));
        }

        $folderList = $this->planSettings->getFtpFolders($apiKey, $apiGetFolderParams);

        asort($folderList);

        $tree = array();
        foreach ($folderList as $folder) {
            array_push($tree, array("text"=>$folder));
        }

        wp_send_json_success(array(
            'message' => $tree
        ), 200);
    }

    public function render() {
        $commonTemplateVariables = array(
            "page_selected" => true,
            "admin_hardening" => self::TEMPLATE_MODAL_ADMIN_HARDENING,
            "ftp_folder" => self::TEMPLATE_MODAL_FTP_FOLDER,
            "settings_apikey_modal" => self::TEMPLATE_MODAL_APIKEY,
            "endpoint_ajax" => sprintf("%s%s", get_admin_url(), "admin-ajax.php"),
            "loading_image" => plugins_url( '../../vendor/jquery/jquery-loader/images/loading32x32.gif', __FILE__),
            "loading_text" => __("Loading...", 'wesecur-security'),
            "settings_modal_apikey_text" => __('An API key is required to activate some additional tools available in this plugin. The key is used to authenticate HTTP requests sent by the plugin to an API service managed by WeSecur. In order to configure it, you need to upgrade the current plan.', 'wesecur-security'),
            "settings_modal_apikey_title" => __('Improve the security of your site','wesecur-security'),
            "settings_modal_apikey_btn_text" => __('Upgrade your plan','wesecur-security'),
            "settings_apikey_title" => __('API Key', 'wesecur-security'),
            "settings_apikey_description" => __("You can get the API key at https://www.wesecur.com/detect-clean-plan/. This API key is required to enable some additional functions available in this plugin. The key is used to authenticate the HTTP requests sent by the plugin to our API service at Wesecur.", 'wesecur-security'),
            "settings_api_required_description" => __("Setup an API key to enable this section", 'wesecur-security'),
            "settings_apikey_notfound_text" => __("API key not found. Setup your API key before configure the FTP settings.", 'wesecur-security'),
            "settings_timeout_error_text" => __("Timeout error, try again in a few minutes. If the problem persist increase your server timeout directive.", 'wesecur-security'),
            "settings_apikey_btn_text" => __('Save', 'wesecur-security'),
            "settings_apikey_default_text" => __('API Key', 'wesecur-security'),
            "settings_apikey_premium_text" => __('Get an API key', 'wesecur-security'),
            "settings_apikey_premium_url" => WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_ESSENTIAL_YEARLY_URL'),
            "settings_waf_title" => __('Firewall (WAF) Settings', 'wesecur-security'),
            "settings_waf_description" => __('This section helps you to configure \'Brute Force Login Protection\'.', 'wesecur-security'),
            "settings_hardening_title" => __('Hardening Settings', 'wesecur-security'),
            "settings_hardening_description" => __('These actions will protect you from known attacks.', 'wesecur-security'),
            "settings_waf_bruteforce_btn_text" => __('Save', 'wesecur-security'),
            "settings_waf_bf_description" => __('Enable Brute Force Protection to limit login attempts on your site.', 'wesecur-security'),
            "settings_waf_bf_ban_text" => __('Ban duration', 'wesecur-security'),
            "settings_waf_bf_text" => __('Bruteforce protection', 'wesecur-security'),
            "settings_waf_bf_attempts_text" => __('Allowed login attempts before blocking IP', 'wesecur-security'),
            "settings_waf_bf_interval_text" => __('Minutes before resetting login attempts count', 'wesecur-security'),
            "settings_waf_bf_ban_description" => __('Minutes that banned IP will be blocked', 'wesecur-security'),
            "settings_waf_bf_minutes_text" => __('Minutes', 'wesecur-security'),
            "settings_hardening_default_user_text" => __("Default Admin User", 'wesecur-security'),
            "settings_hardening_default_user_description" => __("Change the default admin username to a more secure word. You have to avoid using 'admin' as your username."),
            "settings_hardening_default_theme_text" => __("Theme and Plugin Editor", 'wesecur-security'),
            "settings_hardening_default_theme_description" => __("Disable Theme & Plugin editor to prevent errors and configuration issues.", 'wesecur-security'),
            "settings_hardening_xmlrpc_text" => __("XML-RPC attacks", 'wesecur-security'),
            "settings_hardening_xmlrpc_description" => __("Disable XML-RPC to prevent login bruteforce and DoS attacks. Plugins like JetPack and WordPress mobile app will not work.", 'wesecur-security'),
            "settings_hardening_apply_hardening" => __("Apply hardening", 'wesecur-security'),
            "settings_hardening_hide_version_text" => __("Hide WordPress version", 'wesecur-security'),
            "settings_hardening_hide_version_description" => __("Hide your WordPress version so the attackers do not know information about what version you use. It also prevents vulnerability scanners from finding known vulnerabilities in order to exploit them.", 'wesecur-security'),
            "settings_hardening_revert_hardening" => __("Revert hardening", 'wesecur-security'),
            "settings_hardening_php_execution_text" => __("Block PHP execution in sensitive folders", 'wesecur-security'),
            "settings_hardening_php_execution_description" => __("Block PHP files execution in sensitive folders like 'uploads'. This feature protects your website against some common malware behavior. Check your website has no issues after enabling this feature, as some plugins need to execute PHP in this folders to work properly", 'wesecur-security'),
            "settings_ftp_title" => __("FTP Settings", 'wesecur-security'),
            "settings_ftp_description" => __("We need you to setup FTP settings to allow our malware analysis system work.", 'wesecur-security'),
            "settings_ftp_type_text" => __("Connection type", 'wesecur-security'),
            "settings_ftp_type_ftp_text" => __("FTP (File Transfer Protocol)", 'wesecur-security'),
            "settings_ftp_type_ftps_text" => __("FTPS (Secure File Transfer Protocol)", 'wesecur-security'),
            "settings_ftp_type_sftp_text" => __("SFTP (SSH File Transfer Protocol", 'wesecur-security'),
            "settings_ftp_host_text" => __("Host", 'wesecur-security'),
            "settings_ftp_port_text" => __("Port", 'wesecur-security'),
            "settings_ftp_username_text" => __("Username", 'wesecur-security'),
            "settings_ftp_password_text" => __("Password", 'wesecur-security'),
            "settings_ftp_save_btn_text" => __("Save", 'wesecur-security'),
            "settings_ftp_folder_text" => __("WordPress installation folder", 'wesecur-security'),
            "settings_ftp_folder_description" => __("Select your WordPress installation folder by clicking on the input field", 'wesecur-security'),
            "settings_ftp_selected_folder" => __("Selected folder", 'wesecur-security'),
            "settings_ftp_error_installation_folder_invalid" => __("WordPress installation folder invalid", 'wesecur-security'),
            "settings_ftp_error_invalid_host" => __("Invalid host or IP", 'wesecur-security'),
            "settings_ftp_error_host_required" => __("Please specify one host or IP", 'wesecur-security'),
            "settings_ftp_error_port_required" => __("Please specify FTP port", 'wesecur-security'),
            "settings_ftp_error_username_required" => __("Please specify FTP username", 'wesecur-security'),
            "settings_ftp_error_invalid_username_password" => __("Invalid username or password", 'wesecur-security'),
            "settings_ftp_error_password_required" => __("Please specify FTP password", 'wesecur-security'),
            "settings_ftp_error_folder_required" => __("Please specify WordPress installation path", 'wesecur-security'),
            "settings_ftp_error_invalid_type" => __("Invalid FTP connection type", 'wesecur-security'),
            "settings_ftp_ajax_nonce" => wp_create_nonce(self::NONCE_NAME . 'ftp_ajax'),
            "modal_ftp_folder_title" => __("Select your wordpress installation folder", 'wesecur-security'),
            "modal_ftp_folder_btn_save_text" => __("Select", 'wesecur-security'),
            "modal_admin_hardening_title" => __("Change admin username", 'wesecur-security'),
            "modal_admin_hardening_username" => __("Username", 'wesecur-security'),
            "modal_admin_hardening_placeholder" => __("Type new admin username", 'wesecur-security'),
            "modal_admin_hardening_disclaimer" => __("I understand that this operation can not be reverted.", 'wesecur-security'),
            "modal_admin_hardening_btn_close_text" => __("Close", 'wesecur-security'),
            "modal_admin_hardening_btn_apply_text" => __("Apply", 'wesecur-security')
        );

        $this->templateEngine->setVariables($commonTemplateVariables);
        parent::render();
    }

    public function addToMenu() {
        add_submenu_page(
            'wesecur-dashboard',
            'WeSecur Security' . ' ' . __('Settings', 'wesecur-security'),
            '<b style="color:#6bbc5b">' . __('Settings', 'wesecur-security') . '</b>',
            'manage_options',
            self::PAGE_URL,
            array($this, 'render'));
    }

    public function initAction() {
        $planConfig = $this->getFtpConfiguration();
        $ftpPassword = '';
        if (!empty($planConfig->connection->username)) {
            $ftpPassword = '********';
        }

        try{
            $isXmlRpcDisabled = $this->hardeningUtil->isXmlrpcDisabled();
        }catch (WesecurFileNotExistException $fileNotFoundException){
            $isXmlRpcDisabled = false;
        }

        try{
            $isPhpExecutionDisabled = $this->hardeningUtil->isPhpExecutionDisabled();
        }catch (WesecurFileNotExistException $fileNotFoundException){
            $isPhpExecutionDisabled = false;
        }

        $apiKeyRequired = true;
        $apiKey = self::getApiKey();
        if (!empty($apiKey)) {
            $apiKeyRequired = false;
        }

        $commonTemplateVariables = array(
           "settings_waf_bf_enabled" => self::isBruteforceProtectionEnabled(),
           "settings_waf_bf_attempts" => self::getMaxLoginAttempts(),
           "settings_waf_bf_interval" => self::getLoginInterval(),
           "settings_waf_bf_ban_time" => self::getLoginBanTime(),
           "settings_apikey_value" => $apiKey,
           "settings_ftp_folder" => $planConfig->connection->path,
           "settings_ftp_username" => $planConfig->connection->username,
           "settings_ftp_port" => $planConfig->connection->port,
           "settings_ftp_host" => $planConfig->connection->host,
           "settings_ftp_type" => $planConfig->connection->type,
           "settings_ftp_password" => $ftpPassword,
           "settings_hardening_exist_admin_user" => $this->hardeningUtil->existsAdminUsername(),
           "settings_hardening_editor_disabled" => $this->hardeningUtil->isFileEditorDisabled(),
           "settings_hardening_xmlrpc_disabled" => $isXmlRpcDisabled,
           "settings_hardening_php_execution_disabled" => $isPhpExecutionDisabled,
           "settings_hardening_hide_version" => self::hideWordPressVersion(),
           "settings_api_required" => $apiKeyRequired
        );

        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function disableXmlrpcAction($data){
        $disableXmlRpc = true;
        try{
            $isPropertyChanged = $this->hardeningUtil->changeXmlRpc($disableXmlRpc);
            if ($isPropertyChanged) {
                WesecurWordpressNotification::success(__("XML-RPC disabled successfully", 'wesecur-security'));
            }
        }catch(WesecurFileIsNotWritableException $notWritable) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Check .htaccess file permisions. It  must be writable to apply this hardening.", 'wesecur-security'));
        }catch(WesecurFileNotExistException $notFound) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Couldn't find .htaccess file. The file should be in WordPress root folder.", 'wesecur-security'));
        }

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_xmlrpc_disabled" => $isPropertyChanged,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function enableXmlrpcAction($data) {
        $disableXmlRpc = false;

        try{
            $isPropertyChanged = $this->hardeningUtil->changeXmlRpc($disableXmlRpc);
            if ($isPropertyChanged) {
                WesecurWordpressNotification::success(__("XML-RPC enabled successfully", 'wesecur-security'));
            }
        }catch(WesecurFileIsNotWritableException $notWritable) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Check .htaccess file permisions. It  must be writable to apply this hardening.", 'wesecur-security'));
        }catch(WesecurFileNotExistException $notFound) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Couldn't find .htaccess file. The file should be in WordPress root folder.", 'wesecur-security'));
        }

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_xmlrpc_disabled" => ($isPropertyChanged)?false:true,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function disablePhpExecutionAction($data) {
        $disablePhpExecution = true;
        try{
            $isPropertyChanged = $this->hardeningUtil->changePhpExecutionSensitiveFolders($disablePhpExecution);
            if ($isPropertyChanged) {
                WesecurWordpressNotification::success(__("PHP execution disabled successfully", 'wesecur-security'));
            }
        }catch(WesecurFileIsNotWritableException $notWritable) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Check .htaccess file permisions. It  must be writable to apply this hardening.", 'wesecur-security'));
        }catch(WesecurFileNotExistException $notFound) {
            $isPropertyChanged = false;
            if ($notFound->getMessage() === 'asset config file not found') {
                WesecurWordpressNotification::error(__("Couldn't find an important plugin file. Try reinstalling the plugin to fix it.", 'wesecur-security'));
            }else {
                WesecurWordpressNotification::error(__("Couldn't find .htaccess file. The file should be in WordPress root folder.", 'wesecur-security'));
            }
        }

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_php_execution_disabled" => $isPropertyChanged,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function enablePhpExecutionAction($data) {
        $disablePhpExecution = false;

        try{
            $isPropertyChanged = $this->hardeningUtil->changePhpExecutionSensitiveFolders($disablePhpExecution);
            if ($isPropertyChanged) {
                WesecurWordpressNotification::success(__("PHP execution enabled successfully", 'wesecur-security'));
            }
        }catch(WesecurFileIsNotWritableException $notWritable) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Check .htaccess file permisions. It  must be writable to apply this hardening.", 'wesecur-security'));
        }catch(WesecurFileNotExistException $notFound) {
            $isPropertyChanged = false;
            if ($notFound->getMessage() === 'asset config file not found') {
                WesecurWordpressNotification::error(__("Couldn't find an important plugin file. Try reinstalling the plugin to fix it.", 'wesecur-security'));
            }else{
                WesecurWordpressNotification::error(__("Couldn't find .htaccess file. The file should be in WordPress root folder.", 'wesecur-security'));
            }
        }

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_php_execution_disabled" => ($isPropertyChanged)?false:true,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function disableThemePluginEditorAction($data) {
        $disableThemePluginEditor = 'true';

        try{
            $isPropertyChanged = $this->hardeningUtil->changeThemePluginEditor($disableThemePluginEditor);
            if ($isPropertyChanged) {
                WesecurWordpressNotification::success(__("Theme & Plugin editor disabled successfully", 'wesecur-security'));
            }
        }catch(WesecurFileIsNotWritableException $notWritable) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Check wp-config.php file permisions. It  must be writable to apply this hardening.", 'wesecur-security'));
        }catch(WesecurFileNotExistException $notFound) {
            $isPropertyChanged = false;
            WesecurWordpressNotification::error(__("Couldn't find wp-config.php file. The file should be in WordPress root folder.", 'wesecur-security'));
        }

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_editor_disabled" => $isPropertyChanged,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function enableThemePluginEditorAction($data) {
        //check_admin_referer( 'wesecur_settings' );

        $disableThemePluginEditor = 'false';
        $isPropertyChanged = $this->hardeningUtil->changeThemePluginEditor($disableThemePluginEditor);
        if ($isPropertyChanged) {
            WesecurWordpressNotification::success(__("Theme & Plugin editor enabled successfully", 'wesecur-security'));
        }else{
            WesecurWordpressNotification::error(__("Check wp-config.php file permisions. It  must be writeable to apply this hardening.", 'wesecur-security'));
        }

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_editor_disabled" => ($isPropertyChanged)?false:true,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function hideWordPressVersionAction($data) {

        if (get_option('wesecur_hide_wp_version', '') === '') {
            add_option('wesecur_hide_wp_version', 'true', '', 'no');
        }else{
            update_option('wesecur_hide_wp_version', 'true');
        }

        WesecurWordpressNotification::success(__("Hide WordPress version enabled successfully", 'wesecur-security'));

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_hide_version" => true,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function showWordPressVersionAction($data) {

        if (get_option('wesecur_hide_wp_version', '') === '') {
            add_option('wesecur_hide_wp_version', 'false', '', 'no');
        }else{
            update_option('wesecur_hide_wp_version', 'false');
        }

        WesecurWordpressNotification::success(__("Hide WordPress version disabled successfully", 'wesecur-security'));

        $this->initAction();

        $commonTemplateVariables = array(
            "settings_hardening_hide_version" => false,
        );
        $this->templateEngine->setVariables($commonTemplateVariables);
    }

    public function changeAdminUsernameAction($data) {
        if ($this->hardeningUtil->changeAdminUsername($data['username'])) {
            WesecurWordpressNotification::success(__("Admin username changed successfully", 'wesecur-security'));
        }else{
            WesecurWordpressNotification::error(__("Unable to change Admin username", 'wesecur-security'));
        }
        $this->initAction();
    }

    public function saveFtpDataAction($data) {

        $apiKey = self::getApiKey();
        if (!empty($apiKey)) {
            $planConfig = $this->getFtpConfiguration();

            $planConfig->connection->type = $data['settings_ftp_type'];
            $planConfig->connection->host = $data['settings_ftp_host'];
            $planConfig->connection->port = $data['settings_ftp_port'];
            $planConfig->connection->username = $data['settings_ftp_username'];
            $planConfig->connection->password = $data['settings_ftp_password'];
            $planConfig->connection->path = $data['settings_ftp_path'];

            try{
                $this->planSettings->savePlanConfiguration($apiKey, (array)$planConfig);
                WesecurWordpressNotification::success(__("FTP configuration saved successfully", 'wesecur-security'));
            }catch (\Exception $exception) {
                WesecurWordpressNotification::error(__("Unable to save ftp configuration", 'wesecur-security'));
            }
        }

        $this->initAction();
    }

    public function saveApiKeyAction($data) {
        $apiKey = preg_replace('/\s+/', '', $data['settings_apikey']);

        if (empty($apiKey)) {
            $apiKey = '';
        }

        $apiKeySaved = true;

        if (!add_option('wesecur_apikey', $apiKey, '', 'no')) {
            if (!update_option('wesecur_apikey', $apiKey)) {
                $apiKeySaved = false;
            }
        }

        if ($apiKeySaved) {
            $this->localStorage->resetCache();
            WesecurWordpressNotification::success(__("API Key saved successfully", 'wesecur-security'));
        }else{
            WesecurWordpressNotification::error(__("We couldn't save the API Key", 'wesecur-security'));
        }

        $this->initAction();
    }

    public function saveBruteforceOptionsAction($data) {

        $enableBruteforce = $data['settings_waf_bf_enabled'];
        $bruteforceAttempts = intval($data['settings_waf_bf_attempts']);
        $bruteforceInterval = intval($data['settings_waf_bf_interval']);
        $bruteforceBanTime = intval($data['settings_waf_bf_ban_time']);

        if ( $bruteforceAttempts === 0) {
            $bruteforceAttempts = self::DEFAULT_LOGIN_ATTEMPTS;
        }

        if ( $bruteforceInterval === 0) {
            $bruteforceInterval = self::DEFAULT_LOGIN_RESET_TIME;
        }

        if ( $bruteforceBanTime === 0) {
            $bruteforceBanTime = self::DEFAULT_BLOCK_DURTION;
        }

        if (get_option('wesecur_bf_enabled', '') === '') {
            add_option('wesecur_bf_enabled', $enableBruteforce, '', 'no');
        }else{
            update_option('wesecur_bf_enabled', $enableBruteforce);
        }

        if (!get_option('wesecur_login_attempts', false)) {
            add_option('wesecur_login_attempts', $bruteforceAttempts, '', 'no');
        }else{
            update_option('wesecur_login_attempts', $bruteforceAttempts);
        }

        if (!get_option('wesecur_login_interval', false)) {
            add_option('wesecur_login_interval', $bruteforceInterval, '', 'no');
        }else{
            update_option('wesecur_login_interval', $bruteforceInterval);
        }

        if (!get_option('wesecur_login_ban_time', false)) {
            add_option('wesecur_login_ban_time', $bruteforceBanTime, '', 'no');
        }else{
            update_option('wesecur_login_ban_time', $bruteforceBanTime);
        }

        WesecurWordpressNotification::success(__("Bruteforce settings changed successfully", 'wesecur-security'));

        $this->initAction();
    }

    public static function uninstall() {
        delete_option('wesecur_bf_enabled');
        delete_option('wesecur_login_attempts');
        delete_option('wesecur_login_interval');
        delete_option('wesecur_login_ban_time');
        delete_option('wesecur_hide_wp_version');
        delete_option('wesecur_apikey');
    }

    public static function getMaxLoginAttempts() {
        return intval(get_option('wesecur_login_attempts', self::DEFAULT_LOGIN_ATTEMPTS));
    }

    public static function getLoginInterval() {
        return intval(get_option('wesecur_login_interval', self::DEFAULT_LOGIN_RESET_TIME));
    }

    public static function getLoginBanTime() {
        return intval(get_option('wesecur_login_ban_time', self::DEFAULT_BLOCK_DURTION));
    }

    public function getFtpConfiguration() {
        $planConfig = json_decode('{"domain":"","created_at":"","web_type":"","connection":{"type":"","port":21,"tls":false,"host":"","path":"","username":""}}');

        $apiKey = self::getApiKey();
        if (!empty($apiKey)) {
            try {
                $planConfig = $this->planSettings->getPlanConfiguration($apiKey);
            }catch (\Exception $exception) {
            }
        }

        return $planConfig;
    }

    public static function getApiKey() {
        return get_option('wesecur_apikey', '');
    }

    public static function getSiteDomainName() {
        $domain = str_replace('https://', '', get_site_url());
        return str_replace('http://', '', $domain);
    }

    public static function isBruteforceProtectionEnabled() {
        return get_option('wesecur_bf_enabled', self::DEFAULT_BF_ENABLED);
    }

    public static function hideWordPressVersion() {
        return (get_option('wesecur_hide_wp_version', self::DEFAULT_HIDE_WP_VERSION) === 'true');
    }

    protected function loadStyles() {
        wp_enqueue_style('wesecursecurity_vendor-bootstrap-treeview');
        wp_enqueue_style('wesecursecurity_vendor-jquery-loader');
    }

    protected function loadScripts() {
        wp_enqueue_script('wesecursecurity_vendor-bootstrap-treeview');
        wp_enqueue_script('wesecursecurity_vendor-jquery-validate');
        wp_enqueue_script('wesecursecurity_vendor-jquery-additional-methods');
        wp_enqueue_script('wesecursecurity_vendor-jquery-loader');
    }
}
