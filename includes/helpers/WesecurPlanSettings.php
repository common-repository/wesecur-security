<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\exceptions\WesecurBadRequestApiException;
use WesecurSecurity\includes\views\WesecurSettingsPage;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\WesecurWordpressNotification;
use WesecurSecurity\includes\adapters\WesecurGuzzleApiRequestAdapter;
use WesecurSecurity\includes\exceptions\WesecurNotFoundApiException;


/**
 * Class to check Wesecur Plan status
 *
 *
 * @class 	   WesecurPlanSettings
 * @package    WeSecur Security
 * @subpackage WesecurPlanSettings
 * @category   Class
 * @since	   1.0.3
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurPlanSettings {

    const DB_PLAN_CONFIG = 'wesecur-plan-settings.php';

    /** @var WesecurApiRequestInterface */
    protected $apiRequest;

    /** @var WesecurWordpressLocalStorage */
    protected $localStorage;

    function __construct(WesecurApiRequestInterface $apiRequest = null,
                         WesecurWordpressLocalStorage $localStorage = null) {

        if (is_null($apiRequest)) {
            $apiRequest = new WesecurGuzzleApiRequestAdapter(WESECURSECURITY_API_ENDPOINT . '/');
            $apiRequest->setUserAgent(WESECURSECURITY_API_USER_AGENT);
            $apiRequest->verifySSL(WESECURSECURITY_API_VERIFY_SSL);
        }

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage();
        }

        $this->localStorage = $localStorage;
        $this->apiRequest = $apiRequest;
    }

    public function testCredentials($apiKey, $params) {
        $headers = array('WESECUR-API-KEY' => $apiKey);
        $params['port'] = intval($params['port']);
        $params['tls'] = ($params['tls']=='true');

        $apiResponse = $this->apiRequest->post(
            'ftp/test/credentials',
            $params,
            $headers
        );
        return json_decode($apiResponse->getBody());
    }

    public function testConnection($apiKey, $params) {
        $headers = array('WESECUR-API-KEY' => $apiKey);
        $params['port'] = intval($params['port']);
        $params['tls'] = ($params['tls']=='true');

        $apiResponse = $this->apiRequest->post(
            'ftp/test/wordpress/connection',
            $params,
            $headers
        );
        return json_decode($apiResponse->getBody());
    }

    public function getFtpFolders($apiKey, $params) {
        $headers = array('WESECUR-API-KEY' => $apiKey);
        $params['port'] = intval($params['port']);
        $params['tls'] = ($params['tls']=='true');

        $apiResponse = $this->apiRequest->post(
            'ftp/list/directory',
            $params,
            $headers
        );

        return json_decode($apiResponse->getBody());
    }

    public function savePlanConfiguration($apiKey, $params) {
        try{
            $headers = array('WESECUR-API-KEY' => $apiKey);
            $apiResponse = $this->apiRequest->put(
                'plans/essential',
                $params,
                $headers
            );

            $result = json_decode($apiResponse->getBody());

            $this->localStorage->save((array)$params, self::DB_PLAN_CONFIG);
            $this->localStorage->saveCache(array("time" => time()), WesecurWordpressLocalStorage::CACHE_TYPE_PLAN_SETTINGS);

        }catch (\Exception $exception) {
            throw $exception;
        }
    }

    public function getPlanConfiguration($apiKey) {

        if ($this->localStorage->readFromCache(WesecurWordpressLocalStorage::CACHE_TYPE_PLAN_SETTINGS)) {
            $planConfig = $this->localStorage->read(self::DB_PLAN_CONFIG);

            if ($planConfig === FALSE) {
                $planConfig = sprintf('{"domain":"","created_at":"","web_type":"","connection":{"type":"","port":21,"tls":false,"host":"","path":"%s","username":""}}', ABSPATH);
            }

            $planConfig = json_decode($planConfig);
            if (!is_object($planConfig) || !$planConfig->domain) {
                throw new WesecurNotFoundApiException();
            }

        }else{
            try{
                $headers = array('WESECUR-API-KEY' => $apiKey);
                $apiResponse = $this->apiRequest->get(
                    sprintf('plans/%s', WesecurSettingsPage::getSiteDomainName()),
                    array(),
                    $headers
                );

                $planConfig = json_decode($apiResponse->getBody());

                $this->localStorage->save((array)$planConfig, self::DB_PLAN_CONFIG);
                $this->localStorage->saveCache(array("time" => time()), WesecurWordpressLocalStorage::CACHE_TYPE_PLAN_SETTINGS);

            }catch (\Exception $exception) {
                $this->localStorage->save((array)json_decode($planConfig), self::DB_PLAN_CONFIG);
                $this->localStorage->saveCache(array("time" => time()), WesecurWordpressLocalStorage::CACHE_TYPE_PLAN_SETTINGS);
                throw $exception;
            }

        }

        if (!property_exists($planConfig->connection, 'host')) {
            $planConfig->connection->{'host'} = '';
        }

        if (!property_exists($planConfig->connection, 'type')) {
            $planConfig->connection->{'type'} = '';
        }

        if (!property_exists($planConfig->connection, 'username')) {
            $planConfig->connection->{'username'} = '';
        }

        if (!property_exists($planConfig->connection, 'path')) {
            $planConfig->connection->{'path'} = '';
        }

        if (!property_exists($planConfig->connection, 'port')) {
            $planConfig->connection->{'port'} = 21;
        }

        return $planConfig;
    }

    public function isPlanConfigured($apiKey) {

        $isPlanConfigured = false;

        try {
            $planConfig = $this->getPlanConfiguration($apiKey);
        }catch (\Exception $exception) {
            $planConfig = sprintf('{"domain":"","created_at":"","web_type":"","connection":{"type":"","port":21,"tls":false,"host":"","path":"%s","username":""}}', ABSPATH);
        }

        if (is_object($planConfig) && $planConfig->connection && !empty($planConfig->connection->host) && !empty($planConfig->connection->username)) {
            $isPlanConfigured = true;
        }

        return $isPlanConfigured;
    }
}