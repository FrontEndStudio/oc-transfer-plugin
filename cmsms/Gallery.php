<?php

namespace Fes\Transfer\Cmsms;

use Fes\Album\Models\Album as OctoberAlbum;
use System\Models\File as FileModel;
use File;

class Gallery extends Base
{

    public $schema = [
        'fileid' => 'id',
        'filename' => 'album_date',
        'title' => 'name',
        'gallery_date' => 'gallery_date'
    ];

    protected $markdownFields = [];

    protected $excludeFields = [
        'fileid',
        'gallery_date'
    ];

    public function __construct()
    {
        $this->model = new OctoberAlbum;
        parent::__construct();
    }

    /**
     *  @param int
     *  @return array
     */
    public function getGalleryFiles($galleryid)
    {

        $files = $this->db->table('cms_module_gallery AS t1')
            ->select('t1.fileid', 't1.filename', 't1.filepath', 't1.galleryid')
            ->where('t1.active', '=', '1')
            ->where('t1.galleryid', '=', $galleryid)
            ->where('t1.filepath', 'NOT LIKE', '%///')
            ->orderBy('t1.filename', 'ASC')
            ->get();

        return $files;

    }

    public function import($limit = 0)
    {

        $count  = 0;
        $sort_order = 1;
        $gallery_id = '';

        // fetch galleries from cmsms
        $galleries = $this->db->table('cms_module_gallery AS t1')
            ->join('cms_module_gallery_fieldvals AS t2', 't1.fileid', '=', 't2.fileid')
            ->select('t1.fileid', 't1.filename', 't1.title', 't2.value AS gallery_date')
            ->where('t1.title', '!=', '')
            ->where('t1.filename', '!=', '')
            ->orderBy('filename', 'ASC')
            ->take(2)
            ->get();

        foreach ($galleries as $item) {

            $model = new $this->model;

            foreach ((array)$item as $key => $val) {

                if (in_array($key, $this->excludeFields)) {

                    if ($this->schema[$key] == 'id') {
                        $val = $this->converter->convert($val);
                        $gallery_id = $val;
                    }

                    continue;
                }

                if (in_array($this->schema[$key], $this->markdownFields)) {
                    $val = $this->converter->convert($val);
                }

                if (isset($this->schema[$key])) {

                    $val = $this->convertValues($this->schema[$key], $val);
                    $model->{$this->schema[$key]} = $val;

                    if ($this->schema[$key] == 'album_date') {
                        $val = str_replace("_", "-", substr($val, 0, 10));

                        $model->{$this->schema[$key]} = $val;
                        $model->status = 1;
                        $model->created_at = $val . ' 00:00:00';
                        $model->updated_at = $val . ' 00:00:00';
                        $model->sort_order = $sort_order;
                    }

                }

            }

            if ($model->forceSave()) {
                $sort_order++;
                $count++;

                // fetch Gallery files from cmsms
                $files = $this->getGalleryFiles($gallery_id);

                foreach ($files as $file) {
                    $filepath = str_replace("/", "", $file->filepath);
                    $filename = $file->filename;
                    $cmsmsUploadPath = '/mnt/web/kombijsport/public/uploads/images/Gallery/';
                    $fileOrg = $cmsmsUploadPath. $filepath. '/'. $filename;

                    $fileInfo = $this->getFileInfo($fileOrg);

                    $uploadFolders = $this->generateHashedFolderName($fileInfo->disk);
                    $uploadFolder = './storage/app/uploads/public/'.
                        $uploadFolders[0].'/'.
                        $uploadFolders[1].'/'.$uploadFolders[2];

                    $fileNew = $uploadFolder.'/'.$fileInfo->disk;

                    File::makeDirectory($uploadFolder, 0755, true, true);

                    if (File::copy($fileOrg, $fileNew)) {
                        $albumImg = new FileModel;
                        $albumImg->disk_name = $fileInfo->disk;
                        $albumImg->file_name = $fileInfo->name.'.'.$fileInfo->ext;
                        $albumImg->file_size = $fileInfo->size;
                        $albumImg->content_type = $fileInfo->mime;
                        $albumImg->field = 'images';
                        $albumImg->attachment_id = $count;
                        $albumImg->attachment_type = 'Fes\Album\Models\Album';
                        $albumImg->is_public = 1;
                        $albumImg->sort_order = 1;
                        $albumImg->save();
                    }

                }

            }

        }

        return $count;

    }
}
