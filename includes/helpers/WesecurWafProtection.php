<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\views\WesecurSettingsPage;
use WesecurSecurity\includes\exceptions\WesecurJsonDecodeException;
use WesecurSecurity\includes\adapters\WesecurSmartyTemplateEngineAdapter;

define('WAF_ALERT_BRUTEFORCE_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/alert_bruteforce.tpl');

/**
 * Class used to get basic WAF protection
 *
 *
 * @class 	   WesecurWafProtection
 * @package    WeSecur Security
 * @subpackage WesecurWafProtection
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWafProtection {

    const DB_FAILED_LOGINS = 'wesecur-failedlogins.php';
    const DB_BRUTEFORCE = 'wesecur-bruteforce.php';
    const ALERT_BRUTEFORCE_TEMPLATE = WAF_ALERT_BRUTEFORCE_TEMPLATE;

    protected $localStorage;
    protected $templateEngine;
    protected $wordpressEvents;

    function __construct(WesecurWordpressLocalStorage $localStorage = null,
                         WesecurSmartyTemplateEngineAdapter $templateEngine = null,
                         WesecurWordpressEvents $wordpressEvents = null) {

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage();
        }

        if ($templateEngine == null) {
            $templateEngine = new WesecurSmartyTemplateEngineAdapter();
        }

        if ($wordpressEvents == null) {
            $wordpressEvents = new WesecurWordpressEvents();
        }

        $this->localStorage = $localStorage;
        $this->templateEngine = $templateEngine;
        $this->wordpressEvents = $wordpressEvents;
    }

    public function onAuthenticate($data) {
        $ip = $data['ip'];

        if (WesecurSettingsPage::isBruteforceProtectionEnabled() === 'true') {
            if (!empty($data['fakeField'])) {
                $this->wordpressEvents->recordBruteforceAttempt($data['username']);
                $this->recordBruteforce($ip);
            }

            $bruteforce = $this->localStorage->read(self::DB_BRUTEFORCE);
            if ($bruteforce !== FALSE && trim($bruteforce) != '') {
                $bruteforce = json_decode($bruteforce, true);
                if ($bruteforce !== null && json_last_error() === JSON_ERROR_NONE) {
                    if (array_key_exists($ip, $bruteforce)) {
                        $this->showLoginBruteforceAlert();
                    }
                }
            }
        }
    }

    public function onFailedLogin($data) {
        if (WesecurSettingsPage::isBruteforceProtectionEnabled() === 'true') {
            $ip = $data['ip'];
            $username = $data['username'];

            /*if (!in_array($ip, $whitelist)) {
            }*/

            $failedLogins = $this->localStorage->read(self::DB_FAILED_LOGINS);
            $failedLogins = json_decode($failedLogins, true);
            if ($failedLogins === null && json_last_error() !== JSON_ERROR_NONE) {
                $failedLogins = json_decode('[]', true);
            }

            //sleep($this->__options['login_failed_delay']);

            $blockIp = false;
            if ($ip && isset($failedLogins[$ip]) && $failedLogins[$ip]['time'] > (time() - (WesecurSettingsPage::getLoginInterval() * 60))) {
                $failedLogins[$ip]['attempts']++;
                if ($failedLogins[$ip]['attempts'] >= WesecurSettingsPage::getMaxLoginAttempts()) {
                    $blockIp = true;
                    $this->wordpressEvents->recordBruteforceAttempt($username);
                    $this->recordBruteforce($ip);
                    unset($failedLogins[$ip]);
                } else {
                    $failedLogins[$ip]['time'] = time();
                }
            } else {
                $failedLogins[$ip]['attempts'] = 1;
                $failedLogins[$ip]['time'] = time();
            }

            $this->localStorage->save($failedLogins, self::DB_FAILED_LOGINS);

            if ($blockIp) {
                $this->showLoginBruteforceAlert();
            }
        }
    }

    private function showLoginBruteforceAlert() {
        header('HTTP/1.0 403 Forbidden');
        $templateVariables = array(
            "home_url" => get_site_url(),
            "bruteforce_page_try_again_text" => __("Too many failed login attempts. Try again in a few minutes", "wesecur-security"),
            "bruteforce_page_return_text" => __("Return to home", "wesecur-security"),
            "bruteforce_title_text" => __("Too many failed login attempts | 403 - Access Denied", "wesecur-security")
        );
        $this->templateEngine->setVariables($templateVariables);
        $this->templateEngine->render(self::ALERT_BRUTEFORCE_TEMPLATE);
        die();
    }

    protected function recordBruteforce($ip) {
        $bruteforce = $this->localStorage->read(self::DB_BRUTEFORCE);
        if ($bruteforce === FALSE || trim($bruteforce) === '') {
            $bruteforce = '[]';
        }

        $bruteforce = json_decode($bruteforce, true);
        if ($bruteforce !== null && json_last_error() === JSON_ERROR_NONE) {
            $bruteforce[$ip] = time();
            $this->localStorage->save($bruteforce, self::DB_BRUTEFORCE);
        }
    }

    public function removeBruteforceIps() {
        $bruteforce = $this->localStorage->read(self::DB_BRUTEFORCE);
        if ($bruteforce !== FALSE && trim($bruteforce) != '') {
            $bruteforce = json_decode($bruteforce, true);
            if ($bruteforce !== null && json_last_error() === JSON_ERROR_NONE) {
                foreach($bruteforce as $ip => $time) {
                    if ($time < (time() - (WesecurSettingsPage::getLoginBanTime() * 60))) {
                        unset($bruteforce[$ip]);
                    }
                }
                $this->localStorage->save($bruteforce, self::DB_BRUTEFORCE);
            }else{
                $this->localStorage->save(array(), self::DB_BRUTEFORCE);
            }
        }
    }

    public function getBannedIps() {
        //check waf api key

        $bannedIps = $this->localStorage->read(self::DB_BRUTEFORCE);
        if ($bannedIps !== FALSE && trim($bannedIps) != '') {
            $bannedIps = json_decode($bannedIps, true);
            if ($bannedIps === null || json_last_error() !== JSON_ERROR_NONE) {
                $bannedIps = [];
            }
        }
        return $bannedIps;
    }

    public function addFakeFieldLoginForm() {
        echo '<input id="user_passwd" class="input" type="text" name="user_passwd" style="display: none;" value=""/>';
    }
}