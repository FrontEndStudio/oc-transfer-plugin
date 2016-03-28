<?php

namespace Fes\Transfer\Cmsms;

use Illuminate\Support\Facades\DB;
use League\HTMLToMarkdown\HtmlConverter;
use File;

/**
 * Abstract class for importing/exporting cmsms data to and from laravel models
 */

class Base
{

    /**
     * array The schema defines the mapping between cmsms and
     * laravel.  With the key being the cmsms key, and the value
     * being the Laravel key
     */
    public $schema = [
    ];

    /**
     * Handle fields that should be converted to markdown
     */
    protected $markdownFields = [
    ];

    /**
     * Exclude fields from being imported
     */
    protected $excludeFields = [
    ];

    /**
     * A laravel model to represent the data
     */

    public $model = null;
    public $converter = null;

    public function __construct()
    {
        $this->converter = new HtmlConverter(array('strip_tags' => true));
        $this->converter->getConfig()->setOption('italic_style', '*');
        $this->converter->getConfig()->setOption('bold_style', '**');
        $this->db = DB::connection('cmsms');
    }

    /**
     * Imports a cmsms news as an elequent model
     *
     * @param int $limit
     * The number of records to import at once
     *
     * @return $count
     * The number of records that have been imported
     */
    public function import($limit = 0)
    {
        $count = 0;
        return $count;
    }

    /**
     * Converts special fields to the appropriate value type
     *
     * @param $key
     * @param $val
     *
     * @return $val
     * The converted value
     */
    protected function convertValues($key, $val)
    {

        // Fields that start with is_ are booleans
        // And need to be reformated to a real boolean field
        if (substr($key, 0, 3) == 'is_') {
            $val = $this->realBoolean($val);
        }

        // Fields names status are booleans
        // And need to be reformated to a real boolean field
        if ($key == 'published') {
            $val = $this->realBoolean($val);
        }

        switch ($key) {
            case 'time_restriction':
                $val = $this->realTimeRestriction($val);
                break;
            case 'start_time':
            case 'end_time':
            case 'date_begin':
            case 'date_end':
                if (!is_numeric($val)) {
                    $val = strtotime($val);
                }
                $val = $this->epochToTimestamp($val);
                break;
        }

        return $val;
    }

    /**
     * convert cmsms infinite possibility of boolean values into a real boolean value
     *
     * @param $val
     *
     * @return $val
     */
    protected function realBoolean($val)
    {
        $val = strtolower($val);

        switch ($val) {
            case 'published':
                $val = true;
                break;
            case 'draft':
                $val = false;
                break;
        }

        return $val;
    }

    /**
     * Convert an epoch value to the appropriate timestamp
     *
     * @param $val
     *
     * @return $timestamp
     */
    public function epochToTimestamp($val)
    {
        return date('Y-m-d H:i:s', $val);
    }

    /**
    * Generate hashed folder name from filename
    *
    * @param  string
    * @return array
    */
    public static function generateHashedFolderName($filename)
    {
        $folderName[] = substr($filename, 0, 3);
        $folderName[] = substr($filename, 3, 3);
        $folderName[] = substr($filename, 6, 3);
        return $folderName;
    }

    /**
     *  @param string
     *  @return obj
     */
    public function getFileInfo($fileOrg)
    {

        $file = new \stdClass();

        if (File::exists($fileOrg)) {
            $range          = '0123456789abcdefghijklmnopqrstuvwxyz';
            $rangeRandom    = substr(str_shuffle(str_repeat($range, 40)), 0, 40);

            $file->org     = $fileOrg;
            $file->name     = File::name($fileOrg);
            $file->ext      = File::extension($fileOrg);
            $file->size     = File::size($fileOrg);
            $file->get      = File::get($fileOrg);
            $file->mime     = File::mimeType($fileOrg);
            $file->size     = File::size($fileOrg);
            $file->hash     = md5($file->name. '!'. $rangeRandom);
            $file->disk     = $file->hash.'.'.$file->ext;

        }

        return $file;

    }
}
