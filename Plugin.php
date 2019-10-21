<?php namespace KosmosKosmos\EnhancedBackend;

use Backend;
use KosmosKosmos\EnhancedBackend\Widgets\Help\Help;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Backend\Classes\Controller as BackendController;

/**
 * EnhancedBackend Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'EnhancedBackend',
            'description' => 'No description provided yet...',
            'author'      => 'KosmosKosmos',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        BackendController::extend(function($controller) {
            $myWidget = new Help($controller, 'helpWidget');
            $myWidget->bindToController();
        });

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [];
        return [
            'KosmosKosmos\EnhancedBackend\Components\Help' => 'helpComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'kosmoskosmos.enhancedbackend.some_permission' => [
                'tab' => 'system::lang.permissions.name',
                'label' => 'EnhancedBackend'
            ],
        ];
    }

    public function registerSettings(){
        return [
            'settings' => [
                'label'       => 'EnhancedBackend',
                'description' => 'kosmoskosmos.contactme::lang.plugin.description',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-envelope',
                'class'       => 'KosmosKosmos\EnhancedBackend\Models\Menu',
                'order'       => 100,
                'permissions' => ['kosmoskosmos.enhancedbackend.*']
            ]
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
            'enhancedbackend' => [
                'label'       => 'EnhancedBackend',
                'url'         => Backend::url('kosmoskosmos/enhancedbackend/menu'),
                'icon'        => 'icon-leaf',
                'permissions' => ['kosmoskosmos.enhancedbackend.*'],
                'order'       => 500,
            ],
        ];
    }

    public function registerFormWidgets()
    {
        return [
            'KosmosKosmos\EnhancedBackend\FormWidgets\MenuEditor' => 'menueditor',
        ];
    }

}
