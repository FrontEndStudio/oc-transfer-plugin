<?php

namespace Fes\Transfer\Commands;

use Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Fes\Transfer\Cmsms\Base as Base;
use Fes\Transfer\Cmsms\Gallery as CmsmsGallery;
use Fes\Transfer\Cmsms\News as CmsmsNews;
use Fes\Transfer\Cmsms\NewsCategory as CmsmsNewsCategory;

/**
 * Transfer the data from cmsms into october
 *
 * @package Fes\Classes\Commands
 * @author Arnoud van Susteren
 */
class TransferCmsmsDataCommand extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'transfer:cmsms-data';

    /**
     * @var string The console command description.
     */
    protected $description = 'Transfer cmsms data into October';

    /**
     * @var object Contains the database object when fired
     */
    protected $db = null;

    /**
     * @var Number of records to process per run
     */
    protected $limit = 1000;

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        try {
            $this->db = DB::connection('cmsms');
        } catch (\InvalidArgumentException $e) {
            Log::info('Missing configuration for cmsms migration');
        }

        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $this->info('Initializing Transfer');

        $type = $this->option('type');
        $this->limit = $this->option('limit');

        switch ($type) {
            case 'news':
                $this->transferNews();
                break;
            case 'gallery':
                $this->transferGallery();
                break;
            case 'news-category':
                $this->transferNewsCategory();
                break;
            default:
                $this->transferNewsCategory();
                $this->transferNews();
        }

        $this->info('Transfer complete');
    }

    /**
     * Transfer cmsms gallery with laravel
     * @return int
     */
    protected function transferGallery()
    {
        $gallery = new CmsmsGallery;
        $this->transfer($gallery, 'gallery');
    }

    /**
     * Transfer cmsms news with laravel
     * @return int
     */
    protected function transferNews()
    {
        $news = new CmsmsNews;
        $this->transfer($news, 'news');
    }

    /**
     * Transfer cmsms news with laravel
     * @return int
     */
    protected function transferNewsCategory()
    {
        $category = new CmsmsNewsCategory;
        $this->transfer($category, 'news-category');
    }

    protected function transfer($model, $textType)
    {
        $this->info('Begin transfer of ' . $textType);
        $count = $model->import($this->limit);
        $this->info('Processed ' . $count . ' ' . $textType);
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['type', null, InputOption::VALUE_OPTIONAL, 'Import specific type', null],
            ['limit', null, InputOption::VALUE_OPTIONAL, 'Number of records per type to import', $this->limit]
        ];
    }
}
