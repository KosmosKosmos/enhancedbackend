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


/**
 * Menu Model
 */
class Menu extends Model
{
    public $implement = [
        'System.Behaviors.SettingsModel',
        '@RainLab.Translate.Behaviors.TranslatableModel'
    ];

    // A unique code
    public $settingsCode = 'enhanced.backend.settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    private $currentKey = '';
    private $flatMenu = [];

    public function beforeSave()
    {
        $menu = json_decode($this->value['menuItems'], true);
        $helpData = $this->extractHelpContent($menu);
        $helpData = $this->extractHelpImages($helpData);
        $this->cleanHelpContent($menu);
        $menuYaml = Yaml::render(['menu' => $menu]);

        if (!File::exists(storage_path('eventmanager'))) {
            File::makeDirectory(storage_path('eventmanager'));
        }

        if (!File::exists(storage_path('eventmanager/menu'))) {
            File::makeDirectory(storage_path('eventmanager/menu/'));
        }
        File::put(storage_path('eventmanager/menu/') . 'menu.yaml', $menuYaml);
        $this->sendHelp($helpData);
    }

    private function extractHelpContent($menu) {
        $helpContent = [];
        foreach ($menu as $entry) {
            if (key_exists('data', $entry) && key_exists('help', $entry['data'])) {
                $content = implode('', $entry['data']['help']);
                if ($content != '') {
                    $helpContent[$entry['key']] = $entry['data']['help'];
                }
            }
            if (key_exists('children', $entry)) {
                $helpContent = array_merge($helpContent, $this->extractHelpContent($entry['children']));
            }
        }
        return $helpContent;
    }

    private function extractHelpImages($helpData) {
        $remotePath = 'https://which.andevent.net/centralized/image/';
        $filePath = Config::get('cms.storage.media.path') . '/uploaded-files/';
        $diskPath = base_path() . $filePath;
        $webPath = url($filePath);
        foreach ($helpData as &$help) {
            foreach ($help as &$lang) {
                $path = str_replace('/', '\\/', $webPath);
                preg_match("/src\s*=\s*\"" . $path . "\/(.+?)\"/", $lang, $matches);
                if (count($matches)) {
                    $filename = $matches[1];
                    $this->uploadFileToCentral($diskPath, $filename);
                    $lang = str_replace($matches[0], 'src="' . $remotePath.$filename . '"', $lang);
                }
            }
        }
        return $helpData;
    }

    private function sendHelp($helpData) {
        $url = 'http://which.andevent.net/centralized/help';
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query(['help' => $helpData]),
                'timeout' => 12
            )
        );
        $context  = stream_context_create($options);
        file_get_contents($url, false, $context);
    }

    private function uploadFileToCentral($filePath, $filename) {
        $multipartBoundary = '--------------------------'.microtime(true);
        $formField = 'uploaded_file';

        $remote_file_url = 'http://which.andevent.net/centralized/uploadimage';
        $header = 'Content-Type: multipart/form-data; boundary='.$multipartBoundary;
        $file_contents = file_get_contents($filePath . $filename);
        $content =  "--".$multipartBoundary."\r\n".
            "Content-Disposition: form-data; name=\"".$formField."\"; filename=\"".basename($filename)."\"\r\n".
            "Content-Type: application/zip\r\n\r\n".
            $file_contents."\r\n";
        $content .= "--".$multipartBoundary."\r\nContent-Disposition: form-data;";
        $content .= "--".$multipartBoundary."--\r\n";
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => $header,
                'content' => $content,
            )
        ));
        file_get_contents($remote_file_url, false, $context);
    }

    private function cleanHelpContent(&$menu) {
        foreach ($menu as &$entry) {
            if (key_exists('data', $entry)) {
                if (key_exists('help', $entry['data'])) {
                    unset($entry['data']['help']);
                }
                if (key_exists('titles', $entry['data']) && count($entry['data']['titles']) == 0) {
                    unset($entry['data']['titles']);
                }
                if (key_exists('tagList', $entry['data']) && count($entry['data']['tagList']) == 0) {
                    unset($entry['data']['tagList']);
                }
                if (key_exists('description', $entry['data']) && count($entry['data']['description']) == 0) {
                    unset($entry['data']['description']);
                }
            }
            if (key_exists('children', $entry)) {
                $this->cleanHelpContent($entry['children']);
            }
        }
    }

    public static function getStoredMenu() {
        $file = storage_path('eventmanager/menu/') . 'menu.yaml';
        $yamlContents = [];
        if (File::exists($file)) {
            $content = file_get_contents($file);
            $yamlContents = Yaml::parse($content)['menu'];
        }
        return is_array($yamlContents) ? $yamlContents : [];
    }

    public static function generateKey($item, $key, $parentKey) {
        $labelKey = key_exists('label', $item) ? $item->label : $key;
        $labelKey = (strpos($labelKey, '::') === false && $parentKey != '') ? $parentKey.'.'.$labelKey : $labelKey;
        return str_replace(' ', '_', $labelKey);
    }

    public static function getOctoberMenu($flat = false) {
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
                    $subSubItems = array_map(function($subItem) use ($subKey, &$keyMap, &$url) {
                        $url = $url ? $url : $subItem->url;
                        $subItem->key = self::generateKey($subItem, '', $subKey);
                        $keyMap[$subItem->key] = $subItem;
                        return $subItem;
                    }, $item);
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

    public static function generateTagListForSearch() {
        $tagList = [];
        $storedMenu = self::getStoredMenu();
        if (count($storedMenu)) {
            $tagList = self::extractTags($storedMenu);
        }
        return $tagList;
    }

    private static function extractTags($menuItems) {
        $tags = [];
        foreach($menuItems as $menuItem) {
            if (key_exists('data', $menuItem) && key_exists('tagList', $menuItem['data']) && count($menuItem['data']['tagList'])) {
                $tags[$menuItem['key']] = $menuItem['data']['tagList'];
            }
            if (key_exists('children', $menuItem)) {
                $tags = array_merge_recursive($tags, self::extractTags($menuItem['children']));
            }
        }
        return $tags;
    }

    public function getMenu() {
//        Cache::forget('flatMenu');
        return $this->getFlatOctoberMenu();
    }

    public function getCurrentKey() {
        return $this->currentKey;
    }

    public function getFlatOctoberMenu($flat = false) {
        if ($flat) {
            return Cache::get('flatMenu');
        }
        $storedMenu = self::getStoredMenu();
        if (count($storedMenu)) {
            $locale = UserPreference::forUser()->get('backend::backend.preferences', ['locale' => 'en'])['locale'];
            $menu = [];
            $ocItems = self::getOctoberMenu(true);
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
            Cache::forever('flatMenu', $this->flatMenu);
            return $menu;
        } else {
            return self::getOctoberMenu();
        }
    }

    private static function getRelatedOctoberMenuItem($key, $ocItems) {
        if (!key_exists($key, $ocItems)) {
            return (object) [
                'key' => $key,
                'label' => $key,
                'isGroup' => true,
                'url' => '',
                'counter' => null,
                'code' => $key,
                'owner' => null,
                'counterLabel' => null,
            ];
        } else {
            $newItem = (object) $ocItems[$key];
            if ($key == 'system::lang.settings.menu_label') {
                $items = SettingsManager::instance()->listItems('system');
                $subMenu = [];
                foreach($items as $item) {
                    $subMenu = array_merge($subMenu, $item);
                }
                $newItem->allItems = $items;
                $newItem->subMenu = $subMenu;
                $newItem->isGrouped = true;
            }
            return $newItem;
        }
    }

    private function combineMenuData($newItem, $storedItem, $ocItems, $locale, &$isActive, &$currentKey) {
        $newItem->label = self::getLabel($storedItem, $locale, $newItem->label);
        $newItem->description = self::getDescription($storedItem, $locale, '');
        $subMenuItems = key_exists('children', $storedItem) ? $storedItem['children'] : [];
        $subMenu = [];
        if (count($subMenuItems)) {
            foreach ($subMenuItems as $subItem) {
                $subActive = false;
                if (key_exists($subItem['key'], $ocItems)) {
                    $subMenuItem = $this->combineMenuData($ocItems[$subItem['key']], $subItem, $ocItems, $locale, $subActive, $currentKey);
                    if ($currentKey == '') {
                        if ($subActive) {
                            $currentKey = $subItem['key'];
                        } else if (BackendMenu::isSideMenuItemActive($subMenuItem)) {
                            $currentKey = $subMenuItem->key;
                        }
                    }
                    $subMenuItem->isActive = BackendMenu::isSideMenuItemActive($subMenuItem) || $subActive;
                    $subMenu[] = $subMenuItem;
                    if ($subMenuItem->isActive) {
                        $isActive = $subMenuItem->code == BackendMenu::getContext()->sideMenuCode
                            || $subMenuItem->code == BackendMenu::getContext()->mainMenuCode;
                    }
                    $this->flatMenu[$subItem['key']] = self::getFlatInfo($subMenuItem);
                }
            }
        }
        if (count($subMenu)) {
            $newItem->subMenu = $subMenu;
        }
        $this->flatMenu[$storedItem['key']] = self::getFlatInfo($newItem);
        return $newItem;
    }

    private static function getFlatInfo($menuItem) {
        return [
            'label' => $menuItem->label,
            'description' => $menuItem->description,
            'url' => $menuItem->url
        ];
    }

    private static function getLabel($storedItem, $locale, $default) {
        $label = key_exists('data', $storedItem) && key_exists('titles', $storedItem['data'])
            ? $storedItem['data']['titles'] : [];
        return key_exists($locale, $label)
            ? $label[$locale]
            : (key_exists('en', $label) ? $label['en'] : Lang::get($default));
    }

    private static function getDescription($storedItem, $locale, $default) {
        $label = key_exists('data', $storedItem) && key_exists('description', $storedItem['data'])
            ? $storedItem['data']['description'] : [];
        return key_exists($locale, $label)
            ? $label[$locale]
            : (key_exists('en', $label) ? $label['en'] : Lang::get($default));
    }

}
