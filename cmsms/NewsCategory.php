<?php

namespace Fes\Transfer\Cmsms;

use RainLab\Blog\Models\Category as OctoberBlogCategory;

class NewsCategory extends Base
{

    public $schema = [
    ];

    protected $markdownFields = [
    ];

    protected $excludeFields = [
    ];

    public function __construct()
    {
        $this->model = new OctoberBlogCategory;
        parent::__construct();
    }

    public function import($limit = 0)
    {
        $count = 0;
        $nest = 0;

        // delete all existing categories
        OctoberBlogCategory::where('id', '>=', '1')->delete();

        // fetch categories from cmsms
        $categories = $this->db->table('cms_module_news_categories')
        ->get();

        foreach ($categories as $category) {
            $nest++;
            $yyyy = preg_replace("/[^0-9]/", "", $category->news_category_name);
            $at = $yyyy. "-01-01 00:00:00";

            $in = new OctoberBlogCategory;
            $in->id = $category->news_category_id;
            $in->name = $category->news_category_name;
            $in->slug = str_replace(" ", "-", strtolower($category->news_category_name));
            $in->nest_left = $nest;
            $nest++;
            $in->nest_right = $nest;
            $in->nest_depth = 0;
            $in->created_at = $at;
            $in->updated_at = $at;

            $in->save();

            $count++;
        }

        return $count;
    }
}
