<?php namespace KosmosKosmos\EnhancedBackend;

use Event;
use BackendMenu;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use KosmosKosmos\EnhancedBackend\Widgets\Help\Help;
use Backend\Classes\Controller as BackendController;

class Plugin extends PluginBase
{
    public function pluginDetails() : array {
        return [
            'name'        => 'EnhancedBackend',
            'description' => 'No description provided yet...',
            'author'      => 'KosmosKosmos',
            'icon'        => 'icon-leaf'
        ];
    }

    public function register() {
        BackendController::extend(function($controller) {
            $myWidget = new Help($controller, 'helpWidget');
            $myWidget->bindToController();
        });
    }

    public function boot() {
    }

    public function registerPermissions() : array {
        return [
            'kosmoskosmos.enhancedbackend.some_permission' => [
                'tab' => 'system::lang.permissions.name',
                'label' => 'EnhancedBackend'
            ],
        ];
    }

    public function registerSettings() : array {
        return [
            'settings' => [
                'label'       => 'EnhancedBackend',
                'description' => 'kosmoskosmos.enhancedbackend::lang.settings.description',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-envelope',
                'class'       => 'KosmosKosmos\EnhancedBackend\Models\Menu',
                'order'       => 100,
                'permissions' => ['kosmoskosmos.enhancedbackend.*']
            ]
        ];
    }


    public function registerFormWidgets() : array {
        return ['KosmosKosmos\EnhancedBackend\FormWidgets\MenuEditor' => 'menueditor'];
    }

}
