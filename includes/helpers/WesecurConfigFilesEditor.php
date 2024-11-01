<?php

namespace WesecurSecurity\includes\helpers;

use WesecurSecurity\includes\WesecurBlockDirectAccess;

/**
 * Class used to add or remove text from .htaccess and wp-config.php files
 *
 *
 * @class 	   WesecurConfigFilesEditor
 * @package    WeSecur Security
 * @subpackage WesecurConfigFilesEditor
 * @category   Class
 * @since	   1.0.0
 * @author     Albert Vergés <albert.verges@wesecur.com>
 * @copyright  2016-2020 WeSecur
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GPL3
 * @link       https://wordpress.org/plugins/wesecur-security
 */
class WesecurConfigFilesEditor {

    const PHP_COMMENT = '\/\/';
    const HTACCESS_COMMENT = '#';

    public static function writeSectionToFile($file, $sectionContent, $position = 'top', $fileType = 'php') {

        self::deleteSectionFromHtaccess($file, $fileType);

        @ini_set('auto_detect_line_endings', true);
        $fileContent = explode(PHP_EOL, implode('', file($file)));
        $contentArray = explode(PHP_EOL, $sectionContent);

        if ($position === 'bottom') {
            $contents = array_merge($fileContent, $contentArray);
        }else{
            $contents = array_merge($contentArray, $fileContent);
        }

        $blank = false;

        if ($f = @fopen($file, 'w+')) {
            foreach ($contents as $index => $line) {
                if (trim($line) == '') {
                    if ($blank == false) {
                        fwrite($f, PHP_EOL . trim($line));
                    }
                    $blank = true;
                } else {
                    $blank = false;
                    if ($index > 0) {
                        $line = PHP_EOL . trim($line);
                    }else{
                        $line = trim($line);
                    }
                    fwrite($f, $line);
                }
            }
            @fclose($f);
        }else{
            return false;
        }
        return true;
    }

    public static function deleteSectionFromHtaccess($file, $fileType, $section = 'wesecur.com') {

        if (!is_writeable($file)) {
            throw new WesecurFileIsNotWritableException;
        }

        $fileContents = @file_get_contents($file);

        if ($fileType == 'php') {
            $comment = self::PHP_COMMENT;
        }else{
            $comment = self::HTACCESS_COMMENT;
        }

        $section = str_replace(".", "\.", $section);
        $deleteRegex = sprintf("/%s BEGIN_CONFIG %s.*%s END_CONFIG %s/s", $comment, $section, $comment, $section);
        $fileContents = preg_replace($deleteRegex, '', $fileContents);

        @file_put_contents($file, $fileContents, LOCK_EX);

        return true;
    }
}