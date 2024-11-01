<?php

namespace WesecurSecurity\includes;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\helpers\WesecurWordpressHardening;
use WesecurSecurity\includes\helpers\WesecurWordpressEvents;
use WesecurSecurity\includes\helpers\WesecurWafProtection;
use WesecurSecurity\includes\views\WesecurSettingsPage;
use WesecurSecurity\includes\helpers\WesecurMalwareChecker;
use WesecurSecurity\includes\helpers\WesecurPlanSettings;
use WesecurSecurity\includes\helpers\WesecurWordpressIntegrityChecker;

/**
 * Main Plugin class
 *
 *
 * @class 	   WesecurSecurityWordpressPlugin
 * @package    WeSecur Security
 * @subpackage WesecurSecurityWordpressPlugin
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurSecurityWordpressPlugin {

    protected $menu;

    protected $wordpressEvents;

    protected $wafProtection;

    protected $planSettings;

    function __construct(WesecurWordpressMenu $menu,
                         WesecurWordpressEvents $wordpressEvents = null,
                         WesecurWafProtection $wafProtection = null,
                         WesecurWordpressIntegrityChecker $integrityChecker = null,
                         WesecurMalwareChecker $malwareChecker = null,
                         WesecurPlanSettings $planSettings = null,
                         WesecurWordpressHardening $hardeningUtil = null) {

        if (is_null($wafProtection)) {
            $wafProtection = new WesecurWafProtection();
        }

        if (is_null($wordpressEvents)) {
            $wordpressEvents = new WesecurWordpressEvents();
        }

        if (is_null($integrityChecker)) {
            $integrityChecker = new WesecurWordpressIntegrityChecker();
        }

        if (is_null($malwareChecker)) {
            $malwareChecker = new WesecurMalwareChecker();
        }

        if (is_null($planSettings)) {
            $planSettings = new WesecurPlanSettings();
        }

        if ($hardeningUtil == null) {
            $hardeningUtil = new WesecurWordpressHardening();
        }

        $this->menu = $menu;
        $this->wafProtection = $wafProtection;
        $this->wordpressEvents = $wordpressEvents;
        $this->integrityChecker = $integrityChecker;
        $this->malwareChecker = $malwareChecker;
        $this->planSettings = $planSettings;
        $this->hardeningUtil = $hardeningUtil;

        $this->wordpressEvents->registerEvents();
        $this->setObservers();

        $hasIntegrityIssues = $this->integrityChecker->hasIntegrityIssues();
        $hasMalwareIssues = $this->malwareChecker->hasMalwareIssues(WesecurSettingsPage::getApiKey());

        $GLOBALS["malwareIssues"] = ($hasMalwareIssues || $hasIntegrityIssues);

        add_action('plugins_loaded', array($this, 'loadPluginTextdomain'));
        add_action('admin_menu', array($this->menu, 'render' ));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts' ), 1);
        add_action('wesecur_scheduled_truncate_logs', array($this->wordpressEvents, 'truncateFiles'));
        add_action('wesecur_scheduled_remove_bruteforce_ips', array($this->wafProtection, 'removeBruteforceIps'));
        add_action('wesecur_scheduled_external_scan', array($this->malwareChecker, 'checkRemoteMalware'));
        add_action('admin_notices', array($this, 'checkConfiguration'));
        add_filter('cron_schedules', array($this, 'addSchedule'));

        if (WesecurSettingsPage::isBruteforceProtectionEnabled() === 'true') {
            add_action('login_form', array($this->wafProtection, 'addFakeFieldLoginForm'));
        }

        if (WesecurSettingsPage::hideWordPressVersion()) {
            add_filter('style_loader_src', array($this->hardeningUtil, 'hideWordPressVersion'), 9999);
            add_filter('script_loader_src', array($this->hardeningUtil, 'hideWordPressVersion'), 9999);
        }

        register_activation_hook( trailingslashit(WESECURSECURITY_PLUGIN_PATH).'wesecur.php', array($this, 'install' ));
        register_deactivation_hook( trailingslashit(WESECURSECURITY_PLUGIN_PATH).'wesecur.php', array($this, 'uninstall' ));
    }

    public function checkConfiguration() {
        $apiKey = WesecurSettingsPage::getApiKey();
        if (!empty($apiKey)) {

            try {
                if (!$this->planSettings->isPlanConfigured($apiKey)) {
                    $message = sprintf(__("The Wesecur Security Premium setup is almost complete. <a href=\"%s\" target=\"_blank\">Click here to configure FTP options and finish the setup.</a>", 'wesecur-security'), WesecurGlobalVariables::get('WESECURSECURITY_DASHBOARD_URL'));
                    WesecurWordpressNotification::error($message, true);
                }
            }catch (\Exception $exception) {
                $message = sprintf(__("We couldn't find any plan associated with the domain %s. Please <a href=\"%s\" target=\"_blank\">contact us.</a>", 'wesecur-security'), WesecurSettingsPage::getSiteDomainName(), WesecurGlobalVariables::get('WESECURSECURITY_ECOMMERCE_CONTACT_URL'));
                WesecurWordpressNotification::warning($message, true);
            }
        }
    }

    protected function setObservers() {
        $this->wordpressEvents->subscribe('onFailedLogin', $this->wafProtection);
        $this->wordpressEvents->subscribe('onAuthenticate', $this->wafProtection);
    }

    public function loadPluginTextdomain() {
        if ( function_exists( 'determine_locale' ) ) {
            $locale = determine_locale();
        } else {
            //TODO Remove when start supporting WP 5.0 or later.
            $locale = is_admin() ? get_user_locale() : get_locale();
        }

        $locale = apply_filters( 'plugin_locale', $locale, 'wesecur-security' );
        unload_textdomain( 'wesecur-security' );
        load_textdomain( 'wesecur-security', WESECURSECURITY_PLUGIN_PATH . '/languages/wesecur-security-' . $locale . '.mo' );
        load_plugin_textdomain( 'wesecur-security',false, WESECURSECURITY_PLUGIN_FOLDER . '/languages/' );
    }

    public function enqueueScripts() {
        wp_register_style(
            'wesecursecurity',
            plugins_url( '../assets/css/style.css', __FILE__),
            array()
        );
        wp_register_style(
            'wesecursecurity_flags',
            plugins_url( '../assets/css/flags.css', __FILE__),
            array()
        );
        wp_register_style(
            'wesecursecurity_vendorbootstraptable',
            plugins_url('../vendor/bootstrap-table/css/bootstrap-table.min.css', __FILE__),
            array()
        );
        wp_register_style(
            'wesecursecurity_vendor-fontawesome',
            plugins_url('../vendor/fontawesome/css/font-awesome.min.css', __FILE__),
            array()
        );
        wp_register_style(
            'wesecursecurity_vendor-bootstrap',
            plugins_url('../vendor/bootstrap/css/bootstrap.css', __FILE__),
            array()
        );
        wp_register_style(
            'wesecursecurity_vendor-bootstrap-treeview',
            plugins_url('../vendor/bootstrap-treeview/bootstrap-treeview.min.css', __FILE__),
            array()
        );
        wp_register_style(
            'wesecursecurity_vendor-c3js',
            plugins_url('../vendor/c3js/c3.min.css', __FILE__),
            array()
        );

        wp_register_style(
            'wesecursecurity_vendor-jquery-loader',
            plugins_url('../vendor/jquery/jquery-loader/style.css', __FILE__),
            array()
        );

        wp_enqueue_style('wesecursecurity');
        wp_enqueue_style('wesecursecurity_vendor-fontawesome');
        wp_enqueue_style('wesecursecurity_vendor-bootstrap');

        wp_register_script(
            'wesecursecurity_scripts',
            plugins_url('../assets/js/script.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-chartjs',
            plugins_url('../vendor/chartjs/Chart.bundle.min.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendorbootstrap',
            plugins_url('../vendor/bootstrap/js/bootstrap.min.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendorbootstraptable',
            plugins_url('../vendor/bootstrap-table/js/bootstrap-table.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-bootstrap-table-locale-es',
            plugins_url('../vendor/bootstrap-table/js/locale/bootstrap-table-es-ES.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-bootstrap-table-locale-en',
            plugins_url('../vendor/bootstrap-table/js/locale/bootstrap-table-en-US.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-bootstrap-treeview',
            plugins_url('../vendor/bootstrap-treeview/bootstrap-treeview.min.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-popperjs',
            plugins_url('../vendor/popper.js/popper.min.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-c3js',
            plugins_url('../vendor/c3js/c3.min.js', __FILE__),
            array()
        );
        wp_register_script(
            'wesecursecurity_vendor-d3js',
            plugins_url('../vendor/d3js/d3.v5.min.js', __FILE__),
            array()
        );

        wp_register_script(
            'wesecursecurity_vendor-jquery-validate',
            plugins_url('../vendor/jquery/jquery.validate.min.js', __FILE__),
            array('jquery')
        );

        wp_register_script(
            'wesecursecurity_vendor-jquery-additional-methods',
            plugins_url('../vendor/jquery/additional-methods.min.js', __FILE__),
            array('jquery')
        );

        wp_register_script(
            'wesecursecurity_vendor-jquery-loader',
            plugins_url('../vendor/jquery/jquery-loader/jquery-loader.js', __FILE__),
            array('jquery')
        );

        wp_enqueue_script('jquery');
        wp_enqueue_script('wesecursecurity_vendor-popperjs');
        wp_enqueue_script('wesecursecurity_scripts');
        wp_enqueue_script('wesecursecurity_vendorbootstrap');

    }

    /**
     * Actions to perform on activation of plugin
     */
    public function install() {

        if (wp_next_scheduled('wesecur_scheduled_truncate_logs')) {
            wp_clear_scheduled_hook('wesecur_scheduled_truncate_logs');
        }

        wp_schedule_event(
            current_time('timestamp'),
            'daily',
            'wesecur_scheduled_truncate_logs');

        if (wp_next_scheduled('wesecur_scheduled_remove_bruteforce_ips')) {
            wp_clear_scheduled_hook('wesecur_scheduled_remove_bruteforce_ips');
        }

        wp_schedule_event(
            current_time( 'timestamp') + 120,
            'hourly',
            'wesecur_scheduled_remove_bruteforce_ips');

        if (wp_next_scheduled('wesecur_scheduled_external_scan', array('true'))) {
            wp_clear_scheduled_hook('wesecur_scheduled_external_scan', array('true'));
        }

        wp_schedule_event(
            current_time( 'timestamp') + 300,
            '3_days',
            'wesecur_scheduled_external_scan',
            array('true'));

        $this->createDefaultFolders();

        wp_redirect(admin_url('wesecur.php?page=wesecur-dashboard'));
    }

    protected function createDefaultFolders() {
        $templatesCacheFolder = WESECURSECURITY_LOCAL_STORAGE_FOLDER . '/templates_cache';
        if (!file_exists($templatesCacheFolder)) {
            if (!mkdir($templatesCacheFolder, 0755, true)) {
                $message = sprintf(__("We couldn't create folder %s. Check permissions", "wesecur-security"), $templatesCacheFolder);
                WesecurWordpressNotification::error($message, true);
                return false;
            }
        }

        $htaccessFile = WESECURSECURITY_LOCAL_STORAGE_FOLDER . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            if (false === @file_put_contents($htaccessFile, "<IfModule !mod_authz_core.c>" . PHP_EOL . "  Order allow,deny" . PHP_EOL . "  Deny from all" . PHP_EOL . "</IfModule>" . PHP_EOL . "<IfModule mod_authz_core.c>" . PHP_EOL . "  Require all denied" . PHP_EOL . "</IfModule>", LOCK_EX)) {
                $message = sprintf(__("File %s must be writeable. Check file permissions", 'wesecur-security'), $htaccessFile);
                WesecurWordpressNotification::error($message, true);
                return false;
            }
        }

        $indexFile = WESECURSECURITY_LOCAL_STORAGE_FOLDER . '/index.php';
        if (!file_exists($indexFile)) {
            if (false === @file_put_contents($indexFile, "<?php" . PHP_EOL . "header( 'HTTP/1.0 403 Forbidden' );", LOCK_EX)) {
                $message = sprintf(__("File %s must be writeable. Check file permissions", 'wesecur-security'), $indexFile);
                WesecurWordpressNotification::error($message, true);
                return false;
            }
        }

        $indexFile = WESECURSECURITY_LOCAL_STORAGE_FOLDER . '/templates_cache/index.php';
        if (!file_exists($indexFile)) {
            if (false === @file_put_contents($indexFile, "<?php" . PHP_EOL . "header( 'HTTP/1.0 403 Forbidden' );", LOCK_EX)) {
                $message = sprintf(__("File %s must be writeable. Check file permissions", 'wesecur-security'), $indexFile);
                WesecurWordpressNotification::error($message, true);
                return false;
            }
        }
    }

    /**
     * Actions to perform on de-activation of plugin
     */
    public function uninstall() {
        $this->wordpressEvents->purgeFiles();
        WesecurSettingsPage::uninstall();
        wp_clear_scheduled_hook('wesecur_scheduled_truncate_logs');
        wp_clear_scheduled_hook('wesecur_scheduled_remove_bruteforce_ips');
        wp_clear_scheduled_hook('wesecur_scheduled_external_scan', array('true'));
    }

    public function addSchedule($schedules) {
        $schedules['3_days'] = array(
            'interval' => 259200,
            'display' => __( 'Every 3 days', 'wesecur-security')
        );
        return $schedules;
    }

    function run() {
    }
}