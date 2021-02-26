<?php namespace KosmosKosmos\EnhancedBackend\Models;

use Andosto\EventManager\Plugin;
use Backend\Models\UserPreference;
use Config;
use Cache;
use Model;
use Lang;
use Log;
use RainLab\Translate\Classes\Translator;
use System\Classes\SettingsManager;
use Yaml;
use Session;
use BackendMenu;
use October\Rain\Support\Facades\File;

class MenuManager {

    public static function getMenu() : array {
        return Cache::rememberForever('backend-menu', function() {
            return self::getBackendMenu();
        });
    }

    private function getBackendMenu() : array {
/*        if (count($storedMenu = self::getStoredMenu())) {
            $locale = self::getUserLang();
            $menu = [];
            foreach ($storedMenu as $storedMenuItem) {
                $isActive = false;
                $newItem = self::getRelatedOctoberMenuItem($storedMenuItem['key'], $ocItems);
                $newItem = $this->combineMenuData($newItem, $storedMenuItem, $ocItems, $locale, $isActive, $this->currentKey);
                if (BackendMenu::isMainMenuItemActive($newItem)) {
                    $this->currentKey = $newItem->key;
                }
                $newItem->isActive = BackendMenu::isMainMenuItemActive($newItem) || $isActive;
                $menu[] = $newItem;
            }
            return $menu;
        } else {
            return self::getOctoberMenu();
        }*/
    }

    private static function getStoredMenu() : array {
        $menuSettingsFile = storage_path('eventmanager/menu/menu.yaml');
        $menuSettings = [];
        if (File::exists($menuSettingsFile)) {
            $menuSettings = Yaml::parse(file_get_contents($menuSettingsFile))['menu'];
        }
        return is_array($menuSettings) ? $menuSettings : [];
    }

    private static function getOctoberMenu($flat) : array {
        $mainMenuItems = BackendMenu::listMainMenuItems();
        $menu = [];
        $keyMap = [];
        foreach($mainMenuItems as $key => $menuItem) {
            $menuItem->key = self::generateKey($menuItem, $key, '');
            $keyMap[$menuItem->key] = $menuItem;
            $sideMenuItems = BackendMenu::listSideMenuItems($menuItem->owner, $menuItem->code);
            $menuItem->subMenu = count($sideMenuItems) > 1 ? $sideMenuItems : [];
            if (strpos($key, 'OCTOBER.SYSTEM.SYSTEM') !== false) {
                $url = null;
                $items = SettingsManager::instance()->listItems('system');
                $subMenu = [];
                foreach($items as $settingsKey => $item) {
                    $subKey = self::generateKey([], $settingsKey, $menuItem->key);
                    $subSubItems = [];
                    foreach ($item as $subItem) {
                        $subItem->key = self::generateKey($subItem, '', $subKey);
                        $subItem->url = $url ? $url : $subItem->url;
                        $subItem->counter = 0;
                        $subItem->counterLabel = null;
                        $subItem->owner = 'null';
                        $subSubItems[] = $subItem;
                        $keyMap[$subItem->key] = $subItem;

                    }
                    /*                    $subSubItems = array_map(function($subItem) use ($subKey, &$keyMap, &$url) {
                                            $url = $url ? $url : $subItem->url;
                                            $subItem->key = self::generateKey($subItem, '', $subKey);
                                            $keyMap[$subItem->key] = $subItem;
                                            return $subItem;
                                        }, $item);*/
                    $subMenu[] = (object) [
                        'key' => $subKey,
                        'label' => $settingsKey,
                        'subMenu' => $subSubItems
                    ];
                }
                $menuItem->subMenu = $subMenu;
                $menuItem->url = $url;
            } else {
                foreach($menuItem->subMenu as $subKey => &$subItem) {
                    $subItem->key = self::generateKey($subItem, $subKey, $menuItem->key);
                    $keyMap[$subItem->key] = $subItem;
                    if (key_exists('data-menu-item', $subItem->attributes)) {
                        $menuItem->hasJs = true;
                        if ($menuItem->url != url()->current()) {
                            $subItem->url = $menuItem->url.'#'.$subItem->attributes['data-menu-item'];
                            $menuItem->noSideNavJs = true;
                        } else {
                            $menuItem->sideNavJs = true;
                        }
                    }
                }
            }
            $menu[] = $menuItem;
        }
        return $flat ? $keyMap : $menu;
    }

    private static function getUserLang() : string {
        $preferences = UserPreference::forUser()->get('backend::backend.preferences');
        return $preferences && isset($preferences['locale']) ? $preferences['locale'] : 'de';
    }

    private static function generateKey($item, $key, $parentKey) {
        $labelKey = isset($item->label) ? $item->label : $key;
        $labelKey = (strpos($labelKey, '::') === false && $parentKey != '') ? $parentKey.'.'.$labelKey : $labelKey;
        return str_replace(' ', '_', $labelKey);
    }


}
