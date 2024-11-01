<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\exceptions\WesecurJsonDecodeException;
use MaxMind\Db\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;


define('WESECURSECURITY_GEOLITE2', WESECURSECURITY_ASSETS_PATH . '/db/GeoLite2-Country.mmdb');

/**
 * Class used to record Wordpress events
 *
 *
 * @class 	   WesecurWordpressEvents
 * @package    WeSecur Security
 * @subpackage WesecurWordpressEvents
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWordpressEvents
{
    const DB_AUDIT_LOG = 'wesecur-auditlog.php';
    const GEOLITE_DB_PATH = WESECURSECURITY_GEOLITE2;

    /** @var WesecurWordpressLocalStorage */
    protected $localStorage;

    /** @var array */
    protected $observers;

    /** @var MaxMind\Db\Reader */
    protected $geoLiteReader;

    function __construct(WesecurWordpressLocalStorage $localStorage = null) {

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage('');
        }

        $this->observers = array();
        $this->localStorage = $localStorage;
        try {
            $this->geoLiteReader = new Reader(self::GEOLITE_DB_PATH);
        }catch (\Exception $e) {
        }
    }

    public function registerEvents() {
        add_action('wp_login', array($this, 'recordLastLogin'), 50, 5);
        add_action('wp_login_failed', array($this, 'recordFailedLogin'), 50, 5);
        add_action('wp_authenticate', array($this, 'recordAuthenticate') );
        add_action('activated_plugin', array($this, 'recordActivatedPlugin'), 60, 2);
        add_action('deactivated_plugin', array($this, 'recordDeactivatedPlugin'), 60, 2);
        add_action('user_register', array($this, 'recordNewUser'), 50, 5);
    }

    public function getRemoteAddressInfo($ip) {
        try{
            $info = $this->geoLiteReader->get($ip);
            $result = array('ip'=>$ip, 'country_code'=>strtolower($info['country']['iso_code']), 'country_name'=>$info['country']['names']['en']);
        }catch(\Exception $geoLiteException){
            $result = array('ip'=>$ip, 'country_code'=>'', 'country_name'=>'');
        }
        return $result;
    }

    public function recordNewUser($userId) {
        $currentUser = wp_get_current_user();
        $newUserData = get_userdata($userId);
        if ($newUserData) {
            $newUserLogin = $newUserData->user_login;
            $newUserEmail = $newUserData->user_email;
            $newUserRoles = @implode(', ', $newUserData->roles);
        }

        $remoteAddress = $this->getRemoteAddress();
        $event = array(
            'type' => 'newuser',
            'time' => current_time('mysql'),
            'remote_addr' => $this->getRemoteAddressInfo($remoteAddress),
            'user_id' => $currentUser->ID,
            'user_login' => $currentUser->user_login,
            'user_hostname' => @gethostbyaddr($remoteAddress),
            'extra' => array(
                'new_user_roles' => $newUserRoles,
                'new_user_login' => $newUserLogin,
                'new_user_email' => $newUserEmail)
        );
        $this->localStorage->save($event, self::DB_AUDIT_LOG, false);
    }

    public function recordLastLogin($userLogin = '') {

        $user = get_user_by('login', $userLogin);
        $remoteAddress = $this->getRemoteAddress();
        $event = array(
            'type' => 'lastlogin',
            'time' => current_time('mysql'),
            'remote_addr' => $this->getRemoteAddressInfo($remoteAddress),
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'user_hostname' => @gethostbyaddr($remoteAddress)
        );

        $this->localStorage->save($event, self::DB_AUDIT_LOG, false);
    }

    public function recordAuthenticate($username) {
        $remoteAddress = $this->getRemoteAddress();
        $fakeFieldValue = null;
        if (array_key_exists('user_passwd', $_POST)) {
            $fakeFieldValue = $_POST['user_passwd'];
        }
        $this->notify('onAuthenticate', array("fakeField"=>$fakeFieldValue, "username"=>$username, "ip"=>$remoteAddress));
    }

    public function recordActivatedPlugin($plugin = '', $networkActivation = '') {
        $this->recordPluginEvent('activatedplugin', $plugin, $networkActivation);
    }

    public function recordDeactivatedPlugin($plugin = '', $networkActivation = '') {
        $this->recordPluginEvent('deactivatedplugin', $plugin, $networkActivation);
    }

    public function recordPluginEvent($event, $plugin, $networkActivation) {
        $user = wp_get_current_user();
        $remoteAddress = $this->getRemoteAddress();
        $filename = WP_PLUGIN_DIR . '/' . $plugin;

        $name = 'Unknown Plugin';
        $version = '0.0.0';

        if (file_exists($filename)) {
            $info = get_plugin_data($filename);
            if (!empty($info['Name'])) {
                $name = esc_attr($info['Name']);
            }

            if (!empty($info['Version'])) {
                $version = esc_attr($info['Version']);
            }
        }

        $event = array(
            'type' => $event,
            'time' => current_time('mysql'),
            'remote_addr' => $this->getRemoteAddressInfo($remoteAddress),
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'user_hostname' => @gethostbyaddr($remoteAddress),
            'extra' => array(
                'plugin_name' => $name,
                'version' => $version,
                'network' => $networkActivation)
        );
        $this->localStorage->save($event, self::DB_AUDIT_LOG, false);
    }


    public function recordFailedLogin($username) {

        $remoteAddress = $this->getRemoteAddress();

        $this->notify('onFailedLogin', array("username"=>$username, "ip"=>$remoteAddress));

        $event = array(
            'type' => 'failedlogin',
            'time' => current_time('mysql'),
            'remote_addr' => $this->getRemoteAddressInfo($remoteAddress),
            'user_id' => 0,
            'user_login' => $username,
            'user_hostname' => @gethostbyaddr($remoteAddress),
            'extra' => array()
        );
        $this->localStorage->save($event, self::DB_AUDIT_LOG, false);
    }

    public function recordBruteforceAttempt($username) {

        $remoteAddress = $this->getRemoteAddress();

        $event = array(
            'type' => 'bruteforceattempt',
            'time' => current_time('mysql'),
            'remote_addr' => $this->getRemoteAddressInfo($remoteAddress),
            'user_id' => 0,
            'user_login' => $username,
            'user_hostname' => @gethostbyaddr($remoteAddress),
            'extra' => array()
        );
        $this->localStorage->save($event, self::DB_AUDIT_LOG, false);
    }

    public function readAuditLogs() {
        $auditLogs = $this->localStorage->read(self::DB_AUDIT_LOG);
        if ($auditLogs === FALSE) {
            $auditLogs = '[{"type":"", "time":"", "remote_addr": {"ip":"","country_code":"","country_name":""}, "user_id": "", "user_login":"", "user_hostname":"", "extra":{}}]';
        } else {
            $auditLogs = '[' . str_replace("\n", ',', $auditLogs);
            $auditLogs = rtrim($auditLogs, ",") . ']';
        }

        $auditLogs = json_decode($auditLogs);
        if ($auditLogs === null && json_last_error() !== JSON_ERROR_NONE) {
            $auditLogFilenpath = $this->localStorage->buildFilePath(self::DB_AUDIT_LOG);
            @unlink($auditLogFilenpath);
            $auditLogs = '[{"type":"", "time":"", "remote_addr": {"ip":"","country_code":"","country_name":""}, "user_id": "", "user_login":"", "user_hostname":"", "extra":{}}]';
            $auditLogs = json_decode($auditLogs);
        }

        return array_reverse($auditLogs);
    }

    protected function getRemoteAddress() {

        $ipaddress = '';
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!$ipaddress || $ipaddress === '::1' || !$this->isValidIP($ipaddress)) {
            $ipaddress = '127.0.0.1';
        }

        return $ipaddress;
    }

    protected function isValidIP($ipAddress = '') {
        return (bool)@filter_var($ipAddress, FILTER_VALIDATE_IP);
    }

    public function truncateFiles() {
        $auditLogFilenpath = $this->localStorage->buildFilePath(self::DB_AUDIT_LOG);
        if (is_readable($auditLogFilenpath)) {
            $fileSize = @filesize($auditLogFilenpath);
            if ($fileSize >= 1500000) {
                $fileContent = file($auditLogFilenpath);
                $fileContent = array_splice($fileContent, 8700, count($fileContent));
                $overrideFile = true;
                foreach ($fileContent as $key => $eventLine) {
                    $eventJsonLine = json_decode($eventLine, true);
                    if ($eventJsonLine === null && json_last_error() !== JSON_ERROR_NONE) {
                        $line = '{"type":"","time":"","remote_addr":{"ip":"","country_code":"","country_name":""},"user_id":0,"user_login":"","user_hostname":"","extra":[]}';
                        $eventJsonLine = json_decode($line, true);
                    }
                    $this->localStorage->save($eventJsonLine, self::DB_AUDIT_LOG, $overrideFile);
                    $overrideFile = false;
                }
            }
        }

        $failedLoginsFilepath = $this->localStorage->buildFilePath(WesecurWafProtection::DB_FAILED_LOGINS);
        if (is_readable($failedLoginsFilepath)) {
            $fileSize = @filesize($failedLoginsFilepath);
            if ($fileSize >= 500000) {
                $failedLogins = $this->localStorage->read(WesecurWafProtection::DB_FAILED_LOGINS);
                $failedLogins = json_decode($failedLogins, true);
                if ($failedLogins === null && json_last_error() !== JSON_ERROR_NONE) {
                    unlink($failedLoginsFilepath);
                }else{
                    $latestFailedLogins = $failedLogins;
                    foreach ($failedLogins as $ip => $failedLogin) {
                        if ($failedLogin['time'] < (time() - (2 * 24 * 60 * 60))) {
                            unset($latestFailedLogins[$ip]);
                        }
                    }
                    $this->localStorage->save($latestFailedLogins, WesecurWafProtection::DB_FAILED_LOGINS);
                }
            }
        }
    }

    public function purgeFiles() {

        if (file_exists(self::DB_AUDIT_LOG)) {
            unlink(self::DB_AUDIT_LOG);
        }

        if (file_exists(WesecurWafProtection::DB_FAILED_LOGINS)) {
            unlink(WesecurWafProtection::DB_FAILED_LOGINS);
        }

        if (file_exists(WesecurWafProtection::DB_BRUTEFORCE)) {
            unlink(WesecurWafProtection::DB_BRUTEFORCE);
        }

        if (file_exists(WesecurMalwareChecker::DB_EXTERNAL_MALWARE)) {
            unlink(WesecurMalwareChecker::DB_EXTERNAL_MALWARE);
        }

        if (file_exists(WesecurWordpressIntegrityChecker::DB_INTEGRITY)) {
            unlink(WesecurWordpressIntegrityChecker::DB_INTEGRITY);
        }

        if (file_exists(WesecurWordpressIntegrityChecker::DB_INTEGRITY_IGNORE)) {
            unlink(WesecurWordpressIntegrityChecker::DB_INTEGRITY_IGNORE);
        }
    }

    public function subscribe($eventType, $observer) {
        array_push($this->observers, array($eventType => $observer));
    }


    public function unsubscribe($eventType, $observer) {
        if(($key = array_search(array($eventType => $observer), $this->observers, true)) !== FALSE) {
            unset($this->observers[$key]);
        }
    }

    public function notify($event, $data) {
        foreach ($this->observers as  $key => $observer) {
            if (isset($observer[$event])) {
                $observer[$event]->$event($data);
            }
        }
    }
}