<?php

namespace Fes\Transfer\Cmsms;

use Illuminate\Support\Facades\DB;
use League\HTMLToMarkdown\HtmlConverter;
use Rainlab\Blog\Models\Post as OctoberBlogPost;

/**
 * Abstract class for importing/exporting cmsms data to and from laravel models
 */

class News extends Base
{

    public $schema = [
        'news_id' => 'id',
        'news_title' => 'title',
        'news_data' => 'content',
        'news_date' => 'published_at',
        'summary' => 'excerpt',
        'status' => 'published',
        'create_date' => 'created_at',
        'modified_date' => 'updated_at',
        'author_id' => 'user_id',
        'value' => 'results'
    ];

    protected $markdownFields = [
        'content',
        'excerpt',
        'results'
    ];

    protected $excludeFields = [
        'news_category_id',
        'start_time',
        'end_time',
        'icon',
        'news_extra',
        'news_url'
    ];

    public function __construct()
    {
        $this->model = new OctoberBlogPost;
        parent::__construct();
    }

    public function import($limit = 0)
    {

        $count  = 0;

        // delete all existing blog posts
        if ($count == 0) {
            OctoberBlogPost::where('id', '>=', '1')->delete();
        }

        // fetch news posts from cmsms
        $news = $this->db->table('cms_module_news')
            ->join('cms_module_news_fieldvals', 'cms_module_news_fieldvals.news_id', '=', 'cms_module_news.news_id')
            ->select('cms_module_news.*', 'cms_module_news_fieldvals.value')
            ->where('cms_module_news_fieldvals.fielddef_id', '=', '1')
            ->get();

        foreach ($news as $item) {

            $model = new $this->model;

            foreach ((array)$item as $key => $val) {

                if (in_array($key, $this->excludeFields)) {

                    if ($key == 'news_category_id') {
                        $category_id = $val;
                    }
                    continue;
                }

                if (in_array($this->schema[$key], $this->markdownFields)) {
                    $val = $this->converter->convert($val);
                }

                if (isset($this->schema[$key])) {
                    $val = $this->convertValues($this->schema[$key], $val);

                    if ($this->schema[$key] == 'title') {
                        $model->slug = str_slug($val, "-");
                    }

                    $model->{$this->schema[$key]} = $val;
                }

            }

            if ($model->forceSave()) {
                // insert blog post category
                $_post = OctoberBlogPost::find($model->id);
                $_post->categories()->sync(array($category_id), false);
                $count++;
            }

        }

        return $count;

    }
}
