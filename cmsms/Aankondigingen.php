<?php

namespace Fes\Transfer\Cmsms;

use Illuminate\Support\Facades\DB;
use League\HTMLToMarkdown\HtmlConverter;
use System\Models\File as FileModel;
use File;
use Fes\Notice\Models\Record as OctoberNoticeRecord;

class Aankondigingen extends Base
{

    public $schema = [
        'item_id' => 'id',
        'category_id' => 'category_id',
        'title' => 'title',
        'kalender_datum' => 'date',
        'nieuws_afbeelding' => 'image',
        'bestand_01' => 'file',
        'nieuws_bericht' => 'message',
        'active' => 'published'
    ];

    protected $markdownFields = [
        'message'
    ];

    protected $excludeFields = [
        'kalender',
        'nieuws',
        'link',
        'downloads'
    ];

    public function __construct()
    {
        $this->model = new OctoberNoticeRecord;
        parent::__construct();
    }

    public function getPivotData($active = 1)
    {

        $query = "SELECT DISTINCT(alias) AS alias, fielddef_id
                    FROM cms_module_listit2cloneaankondigingen_fielddef
                    WHERE alias NOT IN ('kalender_titel', 'bestand_01_omschrijving')";

        $rows = $this->db->select(DB::connection('cmsms')->raw($query));

        $cols="t1.active AS active, \n
                t1.category_id AS category_id, \n
                t1.title AS title";

        $joins="";

        foreach($rows AS $key => $row) {

            $alias = "p{$key}";

            if (preg_match('/start|einde|bestand_02|bestand_03|bestand_04/', $row->alias)) {
                continue;
            }

            $cols.=",\n {$alias}.value AS $row->alias";
            $joins.="\n LEFT JOIN cms_module_listit2cloneaankondigingen_fieldval AS {$alias} ON ".
                    "(t1.item_id = {$alias}.item_id AND ".
                    "{$alias}.fielddef_id = '{$row->fielddef_id}') ";

        }

        $pivotsql ="SELECT $cols \nFROM cms_module_listit2cloneaankondigingen_item t1 $joins WHERE t1.active = :active \nGROUP BY (t1.item_id)";

        $data = $this->db->select( $this->db->raw($pivotsql), array('active' => $active));

        return $data;

    }

    public function import($limit = 0)
    {

        $count  = 0;
        $sort_order = 1;

        $sections = array(
            'kalender' => 'calendar',
            'nieuws' => 'news',
            'link' => 'news',
            'downloads' => 'download'
        );

        // delete all existing notice records
        if ($count == 0) {
            OctoberNoticeRecord::where('id', '>=', '1')->delete();
        }

        $records = $this->getPivotData(1);

        // transform records data
        // assign to model and save

        foreach ($records as $item) {

            $model = new $this->model;

            $model->sort_order = $sort_order;

            foreach ((array)$item as $key => $val) {

                if (in_array($key, $this->excludeFields)) {

                    if (preg_match('/kalender|nieuws|link|downloads/', $key)) {
                        if ($val == 1) {
                            $section = $sections[$key];
                            $model->section = $section;
                        }
                    }

                }

                if (array_key_exists($key, $this->schema)) {
                    if (in_array($this->schema[$key], $this->markdownFields)) {
                        $val = $this->converter->convert($val);
                    }
                }

                if (isset($this->schema[$key])) {
                    $val = $this->convertValues($this->schema[$key], $val);

                    if ($this->schema[$key] == 'image' || $this->schema[$key] == 'file' ) {

                        if (!isset($val)) {
                            continue;
                        }

                        $cmsmsUploadPath = '/mnt/web/kombijsport/public/uploads/';
                        $fileOrg = $cmsmsUploadPath. $val;
                        $fileInfo = $this->getFileInfo($fileOrg);

                        $uploadFolders = $this->generateHashedFolderName($fileInfo->disk);
                        $uploadFolder = './storage/app/uploads/public/'.
                            $uploadFolders[0].'/'.
                            $uploadFolders[1].'/'.$uploadFolders[2];

                        $fileNew = $uploadFolder.'/'.$fileInfo->disk;

                        File::makeDirectory($uploadFolder, 0755, true, true);

                        if (File::copy($fileOrg, $fileNew)) {
                            $fileType = new FileModel;
                            $fileType->disk_name = $fileInfo->disk;
                            $fileType->file_name = $fileInfo->name.'.'.$fileInfo->ext;
                            $fileType->file_size = $fileInfo->size;
                            $fileType->content_type = $fileInfo->mime;
                            $fileType->attachment_id = $sort_order;

                            if ($this->schema[$key] == 'image') {
                                $fileType->field = 'image';
                                $fileType->attachment_type = 'Fes\Notice\Models\Record';

                                $mediaNew = './storage/app/media/aankondigingen/'. $fileType->file_name;
                                $mediaModelNew = '/aankondigingen/'. $fileType->file_name;

                                if (File::copy($fileOrg, $mediaNew)) {
                                    $model->media = $mediaModelNew;
                                }

                            } else {
                                $fileType->field = 'files';
                                $fileType->attachment_type = 'Fes\Notice\Models\Record';
                            }

                            $fileType->is_public = 1;
                            $fileType->sort_order = 1;
                            $fileType->save();
                        }

                    } else {
                        if (isset($val)) {
                            $model->{$this->schema[$key]} = $val;
                        }
                    }

                }

            }

            if ($model->forceSave()) {
                $count++;
                $sort_order++;
            }

        }

        return $count;

    }
}
