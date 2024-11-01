<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;
use WesecurSecurity\includes\WesecurCmsUtilsInterface;
use WesecurSecurity\includes\WesecurApiRequestInterface;
use WesecurSecurity\includes\adapters\WesecurGuzzleApiRequestAdapter;
use WesecurSecurity\includes\exceptions\WesecurFileNotExistException;
use WesecurSecurity\includes\exceptions\WesecurIntegrityHashException;
use WesecurSecurity\includes\exceptions\WesecurIgnoreFileException;
use WesecurSecurity\includes\exceptions\WesecurNotFoundApiException;
use WesecurSecurity\includes\exceptions\WesecurBadRequestApiException;
use WesecurSecurity\includes\exceptions\WesecurTooManyRequestsApiException;
use WesecurSecurity\includes\WesecurWordpressNotification;


define('INTEGRITY_PATH_WP_ADMIN', ABSPATH . 'wp-admin');
define('INTEGRITY_PATH_WP_INCLUDES', ABSPATH . 'wp-includes');

/**
 * Class used to check authenticity of WordPress core files
 *
 *
 * @class 	   WesecurWordpressIntegrityChecker
 * @package    WeSecur Security
 * @subpackage WesecurWordpressIntegrityChecker
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurWordpressIntegrityChecker {

    const WORDPRESS_FILES_URL = 'https://raw.githubusercontent.com/WordPress/WordPress';
    const PATH_WP_ADMIN = INTEGRITY_PATH_WP_ADMIN;
    const PATH_WP_INCLUDES = INTEGRITY_PATH_WP_INCLUDES;
    const DB_INTEGRITY = 'wesecur-integrity.php';
    const DB_INTEGRITY_IGNORE = 'wesecur-integrity-ignore.php';

    protected $cmsUtils;

    protected $wpVersion;

    protected $apiRequest;

    protected $localStorage;

    protected $regexChecksumIgnoreFiles;

    protected $regexRemovedIgnoreFiles;

    protected $i18nFiles;

    function __construct(WesecurApiRequestInterface $apiRequest = null,
                         WesecurCmsUtilsInterface $cmsUtils = null,
                         WesecurWordpressLocalStorage $localStorage = null) {

        if (is_null($cmsUtils)) {
            $cmsUtils = new WesecurWordpressCmsUtils();
        }

        if (is_null($apiRequest)) {
            $apiRequest = new WesecurGuzzleApiRequestAdapter(WESECURSECURITY_API_ENDPOINT . '/');
            $apiRequest->setUserAgent(WESECURSECURITY_API_USER_AGENT);
            $apiRequest->verifySSL(WESECURSECURITY_API_VERIFY_SSL);
        }

        if ($localStorage == null) {
            $localStorage = new WesecurWordpressLocalStorage();
        }

        $this->regexChecksumIgnoreFiles = array(
            //'/^([^\/]*)\.(pdf|css|txt|jpg|gif|png|jpeg)$/',
            '/^wp-content\/(themes|plugins)\/.+/',
            '/^google[0-9a-z]{16}\.html$/',
            '/^pinterest-[0-9a-z]{5}\.html$/',
            '/^wp-content\/languages\/.+\.mo$/',
            '/^wp-content\/languages\/.+\.po$/',
        );

        $this->regexRemovedIgnoreFiles = array(
            '/^wp-content\/(themes|plugins)\/.+/',
            '/^wp-config-sample\.php$/',
            '/^readme\.html$/'
        );

        $this->i18nFiles = array(
            'wp-includes/version.php',
            'wp-config-sample.php',
            'readme.html'
        );

        $this->localStorage = $localStorage;
        $this->apiRequest = $apiRequest;
        $this->cmsUtils = $cmsUtils;
        $this->wpVersion = $this->cmsUtils->getCmsVersion();
    }

    public function hasIntegrityIssues() {
        $hasIssues = false;

        if (count($this->getIntegrityIssues())>0) {
            $hasIssues = true;
        }
        return $hasIssues;
    }


    public function getIntegrityIssues() {
        $integrityFiles = $this->localStorage->read(self::DB_INTEGRITY);

        if ($integrityFiles === FALSE) {
            $integrityFiles = '[]';
        }

        return json_decode($integrityFiles, true);
    }

    public function check() {

        $ignoredFiles = $this->getIgnoredFiles();

        $filesToCheck = array_merge(
            $this->getDirectoryFiles(self::PATH_WP_ADMIN),
            $this->getDirectoryFiles(self::PATH_WP_INCLUDES)
        );

        $filesToCheck = $this->removeIgnoredFiles($ignoredFiles, $filesToCheck);

        $integrityResult = array();
        $addedFiles = array();

        try {
            $apiResponse = $this->apiRequest->get(
                'analysis/checksums/wordpress',
                array("version"=>$this->wpVersion)
            );

            $officialHashList = json_decode($apiResponse->getBody());
            if (is_array($officialHashList->files)) {
                foreach ($officialHashList->files as $fileHash) {
                    try {
                        if (!in_array($fileHash->file, $ignoredFiles)) {
                            $this->checkHash($fileHash);
                        }
                    } catch (WesecurFileNotExistException $notFoundError) {

                        if (!$this->ignoreRemovedFile($fileHash->file)) {
                            $fileInfo = $this->getFileInfo($fileHash->file);
                            $fileInfo['fixable'] = true;
                            $fileInfo['status'] = 'deleted';
                            $integrityResult[] = $fileInfo;
                        }

                    } catch (WesecurIntegrityHashException $checksumError) {
                        $fileInfo = $this->getFileInfo($fileHash->file);
                        $fileInfo['status'] = 'modified';
                        $integrityResult[] = $fileInfo;
                    }
                }
                $addedFiles = $this->lookForAddedFiles($filesToCheck, $officialHashList->files);
            }else{
                WesecurWordpressNotification::error(__("Your WordPress version is not compatible with our Integrity Check algorithm. Try again in a few days.", 'wesecur-security'));
            }

        }catch (WesecurBadRequestApiException $badRequest) {
            WesecurWordpressNotification::error(__("If this error persist, contact us.", 'wesecur-security'));
        }catch (WesecurNotFoundApiException $notFound) {
            WesecurWordpressNotification::error(__("Your WordPress version is not compatible with our Integrity Check algorithm. Try again in a few days.", 'wesecur-security'));
        }catch (WesecurTooManyRequestsApiException $notFound) {
            WesecurWordpressNotification::warning(__("We are already checking your site's malware. Please, wait until it is done.", 'wesecur-security'));
        }

        return array_merge(
            $integrityResult,
            $addedFiles
        );
    }

    public function getIgnoredFiles() {
        $integrityIgnoredFiles = $this->localStorage->read(self::DB_INTEGRITY_IGNORE);
        $ignoredFiles = json_decode($integrityIgnoredFiles, true);
        $ignoredFiles = !is_array($ignoredFiles)?array():$ignoredFiles;
        return $ignoredFiles;
    }

    public function replaceFile($file) {
        @file_put_contents(
            ABSPATH . '/' . $file,
            $this->getOriginalFile($file),
            LOCK_EX
        );
    }

    public function deleteFile($file) {
        @unlink(ABSPATH . '/' . $file);
    }

    protected function removeIgnoredFiles($ignoredFiles, $filesToCheck) {
        foreach ($ignoredFiles as $ignoredFile) {
            if (($key = array_search(ABSPATH . $ignoredFile, $filesToCheck)) !== false) {
                unset($filesToCheck[$key]);
            }
        }
        return $filesToCheck;
    }

    protected function getOriginalFile($file) {
        $apiResponse = $this->apiRequest->get(self::WORDPRESS_FILES_URL . '/' . $this->wpVersion . '/' . $file);
        return $apiResponse->getBody();
    }

    protected function checkHash(\stdClass $fileHash) {

        $fileAbsPath = ABSPATH . $fileHash->file;

        if (!file_exists($fileAbsPath)) {
            throw new WesecurFileNotExistException();
        }

        if (!$this->ignoreFileChecksum($fileHash->file)) {
            if ($fileHash->hash !== @md5_file($fileAbsPath)) {
                throw new WesecurIntegrityHashException();
            }
        }
    }

    protected function ignoreFileChecksum($file) {
        $ignoreFile = false;

        try {
            $this->ignoreFileI18N($file);
            $this->ignoreFileChecksumPatterns($file);
        }catch (WesecurIgnoreFileException $e){
            $ignoreFile = true;
        }

        return $ignoreFile;
    }

    protected function ignoreFileChecksumPatterns($file) {
        foreach ($this->regexChecksumIgnoreFiles as $pattern) {
            if (@preg_match($pattern, $file)) {
                throw new WesecurIgnoreFileException();
            }
        }
        return false;
    }

    protected function ignoreFileI18N($file) {
        if (@$GLOBALS['wp_local_package'] != 'en_US' && in_array($file, $this->i18nFiles)) {
            throw new WesecurIgnoreFileException();
        }
        return false;
    }

    protected function ignoreRemovedFile($file) {

        $ignoreFile = false;

        foreach ($this->regexRemovedIgnoreFiles as $pattern) {
            if (@preg_match( $pattern, $file)) {
                $ignoreFile = true;
                break;
            }
        }
        return $ignoreFile;
    }

    protected function getFileFullPath($file) {
        return ABSPATH . $file;
    }

    protected function removeFileFullPath($file) {
        return str_replace(ABSPATH, '', $file);
    }

    protected function getFileInfo($file) {

        $fileAbsPath = $this->getFileFullPath($file);

        return array (
            'file' => $file,
            'modified_at' => @filemtime($fileAbsPath),
            'size' => @filesize($fileAbsPath),
            'fixable' => is_writeable($fileAbsPath)
        );
    }

    protected function getDirectoryFiles($directory) {

        $flags = \FilesystemIterator::KEY_AS_PATHNAME;
        $flags |= \FilesystemIterator::CURRENT_AS_FILEINFO;
        $flags |= \FilesystemIterator::SKIP_DOTS;
        $flags |= \FilesystemIterator::UNIX_PATHS;

        $filesObject = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, $flags),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        $directoryFiles = array();

        foreach ($filesObject as $file) {

            try {
                if ($file->isFile()) {
                    $directoryFiles[] = $file->getRealPath();
                }
            } catch (RuntimeException $e) {
                die($e);
            }
        }

        sort($directoryFiles);

        return $directoryFiles;
    }

    protected function compareString($a, $b) {
        return strcmp($a->file, $b->file);
    }

    protected function lookForAddedFiles($localFileList, $officialFileList) {

        usort($officialFileList, array($this, "compareString"));
        $originalFileIndex = 0;
        $originalFileLength = count($officialFileList);
        $addedFiles = array();

        foreach ($localFileList as $file) {

            while ($originalFileIndex < $originalFileLength &&
                strcmp($file, $this->getFileFullPath($officialFileList[$originalFileIndex]->file)) > 0) {

                $originalFileIndex++;
            }

            if ($originalFileIndex < $originalFileLength) {
                if (strcmp($file, $this->getFileFullPath($officialFileList[$originalFileIndex]->file)) < 0) {
                    $fileInfo = $this->getFileInfo($this->removeFileFullPath($file));
                    $fileInfo['status'] = 'added';
                    $addedFiles[] = $fileInfo;
                }else{
                    $originalFileIndex++;
                }
            }else {
                $fileInfo = $this->getFileInfo($this->removeFileFullPath($file));
                $fileInfo['status'] = 'added';
                $addedFiles[] = $fileInfo;
            }
        }

        return $addedFiles;

    }

}
