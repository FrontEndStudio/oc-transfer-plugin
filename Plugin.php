<?php namespace Fes\Transfer;

use Backend;
use System\Classes\PluginBase;

use Illuminate\Support\Facades\Event;
use DB;
use Log;

/**
* Transfer Plugin Information File
*/
class Plugin extends PluginBase
{

    /**
    * @var array Plugin dependencies
    */
    public $require = [
        'RainLab.Blog'
    ];

    /**
    * Returns information about this plugin.
    *
    * @return array
    */
    public function pluginDetails()
    {
        return [
            'name'        => 'Transfer',
            'description' => 'No description provided yet...',
            'author'      => 'Fes',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
    * Registers any front-end components implemented in this plugin.
    *
    * @return array
    */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Fes\Transfer\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
    * Registers any back-end permissions used by this plugin.
    *
    * @return array
    */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'fes.transfer.some_permission' => [
                'tab' => 'Transfer',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
    * Registers back-end navigation items for this plugin.
    *
    * @return array
    */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'transfer' => [
                'label'       => 'Transfer',
                'url'         => Backend::url('fes/transfer/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['fes.transfer.*'],
                'order'       => 500,
            ],
        ];
    }

    public function register()
    {
        $db = false;

        try {
            $db = DB::connection('cmsms');
        } catch (\InvalidArgumentException $e) {
            Log::info('Missing configuration for cmsms migration');
        }

        if ($db) {
            $this->registerConsoleCommand('transfer.cmsms-data', 'Fes\Transfer\Commands\TransferCmsmsDataCommand');
        }
    }
}
