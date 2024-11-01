<?php

namespace WesecurSecurity\includes\views;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\adapters\WesecurGuzzleApiRequestAdapter;
use WesecurSecurity\includes\adapters\WesecurSmartyTemplateEngineAdapter;
use WesecurSecurity\includes\WesecurTemplateEngineInterface;
use WesecurSecurity\includes\controllers\WesecurControllerInterface;
use WesecurSecurity\includes\WesecurWordpressError;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\exceptions\WesecurNotAuthorizedApiException;
use WesecurSecurity\includes\exceptions\WesecurForbiddenApiException;
use WesecurSecurity\includes\exceptions\WesecurInternalServerApiException;
use WesecurSecurity\includes\exceptions\WesecurTimeoutApiException;
use WesecurSecurity\includes\WesecurWordpressNotification;


define('PAGE_MAIN_TEMPLATE', WESECURSECURITY_TEMPLATES_PATH . '/main.tpl');


/**
 * Abstract class used to build new pages
 *
 * @class 	   WesecurWordpressPage
 * @package    WeSecur Security
 * @subpackage WesecurWordpressPage
 * @category   Abstract Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2015-2018 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
abstract class WesecurWordpressPage implements WesecurWordpressPageInterface {

    const MAIN_TEMPLATE = PAGE_MAIN_TEMPLATE;

    protected $controller;
    protected $templateEngine;
    protected $pageUrl;
    protected $apiRequest;
    protected $locale;
    protected $isPageSelected;
    protected $forbiddenMethods;

    function __construct(WesecurControllerInterface $controller, WesecurTemplateEngineInterface $templateEngine = null,
                         WesecurApiRequestInterface $apiRequest = null) {

        if (is_null($templateEngine)) {
            $templateEngine = new WesecurSmartyTemplateEngineAdapter();
        }

        if (is_null($apiRequest)) {
            $apiRequest = new WesecurGuzzleApiRequestAdapter(WESECURSECURITY_API_ENDPOINT . '/');
            $apiRequest->setUserAgent(WESECURSECURITY_API_USER_AGENT);
            $apiRequest->verifySSL(WESECURSECURITY_API_VERIFY_SSL);
        }

        $this->forbiddenMethods = array('__construct', '__destruct', '__call', '__callStatic', '__get', '__set', '__isset',
            '__unset', '__sleep', '__wakeup', '__toString', '__invoke', '__set_state', '__clone', '__debugInfo');

        $this->validatePageConfiguration();

        $this->controller = $controller;
        $this->templateEngine = $templateEngine;
        $this->apiRequest = $apiRequest;
        $this->locale = $this->getLocale();
        $this->isPageSelected = false;
    }

    abstract public function initAction();

    abstract protected function loadStyles();

    abstract protected function loadScripts();

    private function validatePageConfiguration() {
        if (empty(static::PAGE_URL) || empty(static::PAGE_NAME) || empty(static::TEMPLATE)) {
            throw new \Exception('Page Object is not configured');
        }
    }

    public function addToMenu() {
        add_submenu_page(
            'wesecur-dashboard',
            $this->getPageTitle($this->getName()),
            $this->getName(),
            'manage_options',
            static::PAGE_URL,
            array($this, 'render')
        );
    }

    /**
     * Returns the page name
     *
     * @return string
     */
    public function getName() {
        return __(static::PAGE_NAME, 'wesecur-security');
    }


    public function getUrl() {
        return $this->getAdminPageUrl(static::PAGE_URL);
    }

    /**
     * Returns if the page is selected or not
     *
     * @return boolean
     */
    public function isPageSelected() {
        return $this->isPageSelected;
    }

    /**
     * Render the template with all the required variables
     */
    public function render() {
        $this->isPageSelected = true;
        $action = '';

        if (array_key_exists('action', $_REQUEST)) {
            $action = $_REQUEST['action'];
        }

        $this->executeAction($action, $_REQUEST);
        $this->loadStyles();
        $this->loadScripts();

        $templateVariables = array(
            "page_url" => static::PAGE_URL,
            "template_body" => static::TEMPLATE,
            "nonce_fields" => wp_nonce_field( static::NONCE_NAME),
            "table_locale" => str_replace('_', '-', $this->locale)
        );
        $this->templateEngine->setVariables($templateVariables);
        $this->templateEngine->render(self::MAIN_TEMPLATE);
    }


    public function getLocale() {
        return get_locale();
    }

    /**
     * Execute an action from de view
     *
     * @param string $action Name of the method to be executed
     * @param array $data Params to be used passed to the method $action
     */
    protected function executeAction($action, $data) {
        $action = $this->setDefaultActionIfEmpty($action);
        $action = $this->formatActionName($action);
        $this->validateAction($action);

        try {
            $this->$action($data);
        }catch (WesecurNotAuthorizedApiException $notAuthorized) {
            WesecurWordpressNotification::warning(__("Your API Key is not valid or has expired. Contact Us.", 'wesecur-security'));
        }catch (WesecurForbiddenApiException $badRequest) {
            WesecurWordpressNotification::warning(__("You don't have permission to access this resource. Contact Us.", 'wesecur-security'));
        }catch (WesecurInternalServerApiException $serverError) {
            WesecurWordpressNotification::error(__("Ooops! It seems that we are having some technical problems. Try again later or if the problem persist, contact us.", 'wesecur-security'));
        }catch (WesecurTimeoutApiException $timeout) {

        }
    }

    /**
     * Set default action
     *
     * @param string $action Name of the method to be executed
     * @return string
     */
    protected function setDefaultActionIfEmpty($action) {
        if (empty($action)) {
            $action = 'init';
        }
        return $action;
    }

    /**
     * Add "Action" suffix
     *
     * @param string $action Name of the method to be executed
     * @return string
     */
    protected function formatActionName($action) {
        return sprintf("%sAction", $action);
    }

    /**
     * Validates an action name
     *
     * @param string $action Name of the method to be executed
     */
    protected function validateAction($action) {

        if ($action !== 'initAction') {
            check_admin_referer(static::NONCE_NAME);
        }

        if (!preg_match('/[A-Za-z]{4,50}/', $action)) {
            $error = __('Invalid action name', 'wesecur-security');
            $this->addAdminError($error);
        }

        if (in_array($action, $this->forbiddenMethods)) {
            $error = __('Protected action', 'wesecur-security');
            $this->addAdminError($error);
        }

        if (!method_exists($this, $action)) {
            $error = __('Action does not exist', 'wesecur-security');
            $this->addAdminError($error);
        }
    }

    protected function addAdminError($errorMsg) {
        $wordpressError = new WesecurWordpressError($errorMsg);
        $wordpressError->render();
        throw new \Exception($errorMsg);
    }

    protected function sanitizeParams() {
    }

    /**
     * Returns the page URL from the admin dashboard
     *
     * @param string $page Admin dashboard page name
     * @return string URL from the admin dashboard
     */
    protected function getAdminPageUrl($page = '') {
        return $this->getAdminUrl('admin.php?page=' . $page);
    }

    /**
     * Returns an URL from the admin dashboard.
     *
     * @param  string $url Optional trailing of the URL.
     * @return string URL from the admin dashboard.
     */
    protected function getAdminUrl($url = '') {
        $url = admin_url($url);

        if (WESECURSECURITY_IS_MULTISITE) {
            $url =  network_admin_url($url);
        }

        return $url;
    }

    /**
     * Builds the page title
     *
     * @param string $pageName Page name
     * @return string Admin dashboard page Title
     */
    protected function getPageTitle($pageName) {
        return 'WeSecur Security - ' . $pageName;
    }
}