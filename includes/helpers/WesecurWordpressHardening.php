<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\exceptions\WesecurFileNotExistException;
use WesecurSecurity\includes\exceptions\WesecurFileIsNotWritableException;

define('HARDENING_WP_CONFIG_PATH', ABSPATH . 'wp-config.php');
define('HARDENING_HTACCESS_PATH', ABSPATH . '.htaccess');

/**
 * Class used to apply hardening options in WordPress
 *
 *
 * @class 	   WesecurWordpressHardening
 * @package    WeSecur Security
 * @subpackage WesecurWordpressHardening
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWordpressHardening {

    const WP_CONFIG_PATH = HARDENING_WP_CONFIG_PATH;
    const HTACCESS_PATH = HARDENING_HTACCESS_PATH;
    const HTACCESS_PHP = 'phpExecution';
    const HTACCESS_XMLRPC = 'xmlRpc';

    public function changeThemePluginEditor($disableThemePluginEditor) {

        $changed = false;
        if (!is_writeable(self::WP_CONFIG_PATH)) {
            throw new WesecurFileIsNotWritableException;
        }

        $configFileContent = @file_get_contents( self::WP_CONFIG_PATH);
        if ($configFileContent === FALSE) {
            throw new WesecurFileNotExistException;
        }

        $searchActualThemeEditOption = ($disableThemePluginEditor==='true')?'false':'true';
        $count = 0;

        $content = preg_replace(
            '/(?<=[\', \"]DISALLOW_FILE_EDIT[\',\"],)(?:\s*)('.$searchActualThemeEditOption.')(?:\s*)(?=\)\;)/',
            $disableThemePluginEditor,
            $configFileContent,
            1,
            $count
        );

        if ($count <= 0) {
            $sectionContent = "// BEGIN_CONFIG wesecur.com" . PHP_EOL . $this->getAllWpConfigHardening() . PHP_EOL . "// END_CONFIG wesecur.com" . PHP_EOL;
            if (WesecurConfigFilesEditor::writeSectionToFile(self::WP_CONFIG_PATH, $sectionContent, 'bottom')) {
                $changed = true;
            }
        }else{
            @file_put_contents(
                self::WP_CONFIG_PATH,
                $content,
                LOCK_EX
            );
            $changed = true;
        }

        return $changed;
    }

    public function isFileEditorDisabled() {
        $isDisabled = false;
        if (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
            $isDisabled = true;
        }
        return $isDisabled;
    }

    public function changeXmlRpc($disableXmlRpc) {

        $changed = false;
        if (!is_writeable(self::HTACCESS_PATH)) {
            throw new WesecurFileIsNotWritableException;
        }

        $configFileContent = @file_get_contents( self::HTACCESS_PATH);
        if ($configFileContent === FALSE) {
            throw new WesecurFileNotExistException;
        }

        if (($disableXmlRpc && !$this->isXmlrpcDisabled()) || (!$disableXmlRpc && $this->isXmlrpcDisabled())) {
            $sectionContent = "# BEGIN_CONFIG wesecur.com" . PHP_EOL . $this->getAllHtacessHardening("xmlRpc");
            if ($disableXmlRpc) {
                $sectionContent .= $this->readConfigFile(self::HTACCESS_XMLRPC);
            }
            $sectionContent .= PHP_EOL . "# END_CONFIG wesecur.com" . PHP_EOL;
            if (WesecurConfigFilesEditor::writeSectionToFile(self::HTACCESS_PATH, $sectionContent, 'top', '.htaccess')) {
                $changed = true;
            }
        }

        return $changed;
    }

    public function changePhpExecutionSensitiveFolders($disablePhpExecution) {

        $changed = false;
        if (!is_writeable(self::HTACCESS_PATH)) {
            throw new WesecurFileIsNotWritableException;
        }

        $configFileContent = @file_get_contents( self::HTACCESS_PATH);
        if ($configFileContent === FALSE) {
            throw new WesecurFileNotExistException;
        }

        if (($disablePhpExecution && !$this->isPhpExecutionDisabled()) || (!$disablePhpExecution && $this->isPhpExecutionDisabled())) {
            $sectionContent = "# BEGIN_CONFIG wesecur.com" . PHP_EOL . $this->getAllHtacessHardening("phpExecution");
            if ($disablePhpExecution) {
                $sectionContent .= $this->readConfigFile(self::HTACCESS_PHP);
            }
            $sectionContent .= PHP_EOL . "# END_CONFIG wesecur.com" . PHP_EOL;
            if (WesecurConfigFilesEditor::writeSectionToFile(self::HTACCESS_PATH, $sectionContent, 'top', '.htaccess')) {
                $changed = true;
            }
        }

        return $changed;
    }

    protected function readConfigFile($configFile) {
        $config = @file_get_contents(WESECURSECURITY_PLUGIN_PATH . '/assets/configs/' . $configFile);
        if ($config === FALSE) {
            throw new WesecurFileNotExistException('asset config file not found');
        }
        return $config;
    }

    public function isPhpExecutionDisabled() {
        $isDisabled = false;

        $configFileContent = @file_get_contents( self::HTACCESS_PATH);
        if ($configFileContent === FALSE) {
            throw new WesecurFileNotExistException;
        }

        preg_match('/#wesecur disable php/', $configFileContent, $matches);
        if (count($matches) > 0) {
            $isDisabled = true;
        }

        return $isDisabled;
    }

    public function isXmlrpcDisabled() {
        $isDisabled = false;

        $configFileContent = @file_get_contents( self::HTACCESS_PATH);
        if ($configFileContent === FALSE) {
            throw new WesecurFileNotExistException;
        }

        preg_match('/#wesecur disable xmlrpc|<files xmlrpc\.php>/', $configFileContent, $matches);
        if (count($matches) > 0) {
            $isDisabled = true;
        }
        return $isDisabled;
    }

    public function getDisablePhpExecutionConfig() {
        $content = "";
        if ($this->isPhpExecutionDisabled()) {
            $content = $this->readConfigFile(self::HTACCESS_PHP);
        }
        return $content;
    }

    public function getDisableXmlrpcConfig() {
        $content = "";
        if ($this->isXmlrpcDisabled()) {
            $content = $this->readConfigFile(self::HTACCESS_XMLRPC);
        }
        return $content;
    }

    public function getFileEditorConfig() {
        $textDisabled = 'true';
        if ($this->isFileEditorDisabled()) {
           $textDisabled = 'false';
        }
        return sprintf("define('DISALLOW_FILE_EDIT',%s);", $textDisabled);
    }

    public function existsAdminUsername() {
        global $wpdb;

        $exists = false;

        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}users INNER JOIN {$wpdb->prefix}usermeta ON ({$wpdb->prefix}usermeta.user_id={$wpdb->prefix}users.ID) WHERE user_login=%s AND {$wpdb->prefix}usermeta.meta_key = 'wp_capabilities' AND {$wpdb->prefix}usermeta.meta_value like '%administrator%'", 'admin');
        $adminUser = $wpdb->get_var($query);
        if (intval($adminUser) > 0) {
            $exists = true;
        }

        return $exists;
    }

    public function changeAdminUsername($newUsername) {
        global $wpdb;

        $updated = false;
        $query = $wpdb->prepare("UPDATE {$wpdb->prefix}users INNER JOIN {$wpdb->prefix}usermeta ON ({$wpdb->prefix}usermeta.user_id={$wpdb->prefix}users.ID) set user_login=%s WHERE user_login=%s AND {$wpdb->prefix}usermeta.meta_key = 'wp_capabilities' AND {$wpdb->prefix}usermeta.meta_value like '%administrator%'", $newUsername, 'admin');
        $result = $wpdb->query($query);
        if ($result >0) {
            $updated = true;
        }
        return $updated;
    }

    public function getAllWpConfigHardening() {
        $configContent = $this->getFileEditorConfig();
        return $configContent;
    }

    public function getAllHtacessHardening($action) {

        $configContent = '';
        if ($action !== 'xmlRpc') {
            $configContent = $this->getDisableXmlrpcConfig();
        }

        if ($action !== 'phpExecution') {
            $configContent .= $this->getDisablePhpExecutionConfig();
        }

        return $configContent;
    }

    public function hideWordPressVersion($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }elseif (strpos($src, 'v=')) {
            $src = remove_query_arg('v', $src);
        }
        return $src;
    }
}
