<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurWordpressError;

/**
 * Class used manage cache files and local database files.
 *
 *
 * @class 	   WesecurWordpressLocalStorage
 * @package    WeSecur Security
 * @subpackage WesecurWordpressLocalStorage
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWordpressLocalStorage {

    const CACHE_FILE = 'wesecur-cache.php';

    const CACHE_TYPE_EXTERNAL_MALWARE = 'external_malware';

    const CACHE_TYPE_PLAN_SETTINGS = 'plan_settings';

    const CACHE_TYPE_BLACKLIST = 'blacklist';

    const CACHE_TYPE_SERVER_SIDE = 'server_side_malware';

    protected $filePath;
    protected $uploadDir;

    function __construct() {
        $this->uploadDir = WESECURSECURITY_LOCAL_STORAGE_FOLDER;

        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                $errorMsg = sprintf(__("We couldn't create folder %s. Check permissions", "wesecur-security"), $this->uploadDir);
                $wordpressError = new WesecurWordpressError($errorMsg);
                $wordpressError->render();
                throw new \Exception($errorMsg);
            }
        }
    }

    public function read($filename, $cache = false) {

        $filePath = $this->buildFilePath($filename);

        if (!file_exists($filePath)) {
        }

        if (!is_readable($filePath)) {
        }

        return @file_get_contents($filePath, false, NULL, 18);
    }

    public function save(array $data, $filename, $override = true) {

        $filePath = $this->buildFilePath($filename);

        if ($override || !file_exists($filePath)) {
            @file_put_contents($filePath, "<?php exit(0); ?>\n", LOCK_EX);
        }

        @file_put_contents($filePath, json_encode($data) . "\n", FILE_APPEND | LOCK_EX);
    }

    public function readFromCache($cacheType, $secondsCacheExpired = 7200) {

        $readFromCache = false;
        $cacheFileContent = $this->read(self::CACHE_FILE);
        if ($cacheFileContent !== FALSE) {
            $cacheFileContent = json_decode($cacheFileContent, true);

            if (array_key_exists($cacheType, $cacheFileContent) && time() - intval($cacheFileContent[$cacheType]['time']) < $secondsCacheExpired) {
                $readFromCache = true;
            }
        }
        return $readFromCache;
    }

    public function resetCache() {

        $done = false;
        $cacheFile = $this->buildFilePath(self::CACHE_FILE);
        if (file_exists($cacheFile) && unlink($cacheFile)) {
            $done = true;
        }
        return $done;
    }

    public function saveCache(array $data, $cacheType) {

        $cacheFileContent = $this->read(self::CACHE_FILE);
        if ($cacheFileContent !== FALSE) {
            $cacheFileContent = json_decode($cacheFileContent, true);
            $cacheFileContent[$cacheType] = $data;
        }else{
            $cacheFileContent = array($cacheType => array("time" => time()));
        }

        $this->save($cacheFileContent, self::CACHE_FILE);
    }

    public function buildFilePath($filename) {
        return $this->uploadDir . '/' . $filename;
    }
}