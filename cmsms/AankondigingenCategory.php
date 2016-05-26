<?php

namespace Fes\Transfer\Cmsms;

use Fes\Notice\Models\Category as OctoberNoticeCategory;

class AankondigingenCategory extends Base
{

    public $schema = [
    ];

    protected $markdownFields = [
    ];

    protected $excludeFields = [
    ];

    public function __construct()
    {
        $this->model = new OctoberNoticeCategory;
        parent::__construct();
    }

    public function import($limit = 0)
    {
        $count = 0;
        $nest = 0;

        // delete all existing categories
        OctoberNoticeCategory::where('id', '>=', '1')->delete();

        // fetch categories from cmsms
        $categories = $this->db->table('cms_module_listit2cloneaankondigingen_category')
        ->get();

        foreach ($categories as $category) {
            $nest++;

            $in = new OctoberNoticeCategory;
            $in->id = $category->category_id;
            $in->name = $category->category_name;
            $in->slug = str_replace(" ", "-", strtolower($category->category_name));
            $in->nest_left = $nest;
            $nest++;
            $in->nest_right = $nest;
            $in->nest_depth = 0;
            $in->save();

            $count++;
        }

            $nest++;
            $count++;

            $in = new OctoberNoticeCategory;
            $in->id = $count + 1;
            $in->name = 'Algemeen';
            $in->slug = str_replace(" ", "-", strtolower($in->name));
            $in->nest_left = $nest;
            $nest++;
            $in->nest_right = $nest;
            $in->nest_depth = 0;
            $in->save();



        return $count;
    }
}
