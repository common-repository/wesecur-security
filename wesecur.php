<?php
/**
 * Plugin Name: WeSecur Security
 * Plugin URI: https://wordpress.org/plugins/wesecur-security
 * Author: WeSecur
 * Author URI: https://www.wesecur.com/
 * Description: The <a href="https://www.wesecur.com/" target="_blank">WeSecur</a> plugin audits and protects your WordPress by daily analyzing malware, blacklists and analyzing vulnerabilities that can be exploited to infect your website and blocking attacks and spambots with tools specially designed to protect your WordPress.
 * Version: 1.2.1
 * Text Domain: wesecur-security
 * Domain Path: /languages
 *
 *
 * @package WeSecur
 * @subpackage WesecurSecurity
 * @author  Albert Vergés <albert.verges@wesecur.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link    https://wordpress.org/plugins/wesecur-security
 */


/* check if installation path is available */
if (!defined('ABSPATH')) {
    header('HTTP/1.1 403 Forbidden');
    exit(0);
}

define('WESECURSECURITY_LOADED', true);
define('WESECURSECURITY_PLUGIN_PATH', dirname(__FILE__));
define('WESECURSECURITY_PLUGIN_FOLDER', basename(WESECURSECURITY_PLUGIN_PATH));
define('WESECURSECURITY_TEMPLATES_PATH', WESECURSECURITY_PLUGIN_PATH . '/templates');
define('TEMPLATE_MODAL_SERVERSIDE', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/enable-server-side-scanner.tpl');
define('TEMPLATE_MODAL_WAF', WESECURSECURITY_TEMPLATES_PATH . '/views/modals/enable-waf.tpl');
define('WESECURSECURITY_LOCAL_STORAGE_FOLDER', wp_upload_dir()['basedir'] . '/' . 'wesecur-security');
define('WESECURSECURITY_ASSETS_PATH', WESECURSECURITY_PLUGIN_PATH . '/assets');
define('WESECURSECURITY_VENDOR_PATH', WESECURSECURITY_PLUGIN_PATH . '/vendor');
define('WESECURSECURITY_IS_MULTISITE', (bool)(function_exists('is_multisite') && is_multisite()));
define('WESECURSECURITY_API_USER_AGENT', 'WeSecur Wordpress Plugin (https://wwww.wesecur.com)');
define('WESECURSECURITY_API_VERIFY_SSL', (!defined('VERIFY_SSL')?true:VERIFY_SSL));
define('WESECURSECURITY_API_ENDPOINT', (!defined('ENDPOINT_DASHBOARD')?"https://dashboard.wesecur.com/api":ENDPOINT_DASHBOARD));

require_once(WESECURSECURITY_PLUGIN_PATH . '/vendor/autoload.php');

use WesecurSecurity\includes\WesecurWordpressMenu;
use WesecurSecurity\includes\WesecurSecurityWordpressPlugin;
use WesecurSecurity\includes\adapters\WesecurSmartyTemplateEngineAdapter;
use WesecurSecurity\includes\controllers\WesecurDashboardController;
use WesecurSecurity\includes\views\WesecurDashboardPage;
use WesecurSecurity\includes\views\WesecurAntivirusPage;
use WesecurSecurity\includes\views\WesecurWafPage;
use WesecurSecurity\includes\views\WesecurSettingsPage;

$templateEngine = new WesecurSmartyTemplateEngineAdapter();

$wesecurPages = array(
    new WesecurDashboardPage(new WesecurDashboardController(), $templateEngine),
    new WesecurAntivirusPage(new WesecurDashboardController(), $templateEngine),
    new WesecurWafPage(new WesecurDashboardController(), $templateEngine),
    new WesecurSettingsPage(new WesecurDashboardController(), $templateEngine),
);

$wesecurMenu = new WesecurWordpressMenu($wesecurPages, $templateEngine);


new WesecurSecurityWordpressPlugin($wesecurMenu);