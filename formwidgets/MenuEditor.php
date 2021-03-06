<?php namespace KosmosKosmos\EnhancedBackend\FormWidgets;

use Backend\FormWidgets\RichEditor;
use Log;
use Yaml;
use Lang;
use Cache;
use BackendMenu;
use October\Rain\Support\Facades\File;
use RainLab\Translate\Models\Locale;
use \KosmosKosmos\EnhancedBackend\Models\Menu;
use System\Classes\SettingsManager;
use Backend\Classes\FormWidgetBase;

/**
 * MenuEditor Form Widget
 */
class MenuEditor extends RichEditor
{

    use \RainLab\Translate\Traits\MLControl;

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'menueditor';
    protected $defaultLocale;
    public $originalAssetPath;
    public $originalViewPath;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->defaultLocale = Locale::getDefault();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        $this->prepareVars();
        $this->vars['richeditor'] = $parentContent;
        return $this->makePartial('menueditor');
    }

    private function extractStructure($menu, $isGrouped = false) {
        $structure = [];
        foreach($menu as $key => $item) {
            $entry = [
                'title' => Lang::get((isset($item->label) && $item->label) ? $item->label : $key),
                'key' => $item->key,
            ];
            if (isset($item->subMenu) && $item->subMenu && count($item->subMenu) > 1) {
                $entry['children'] = $this->extractStructure($item->subMenu, isset($item->isGrouped) && $item->isGrouped);
                $entry['isExpanded'] = false;
            } else {
                $entry['isLeaf'] = true;
                $entry['hasJs'] = isset($item->hasJs) && $item->hasJs;
            }
            $structure[] = $entry;
        }
        return $structure;
    }

    private function extractKeys($menu, $withChildren = false) {
        $keys = [];
        foreach($menu as $index => $item) {
            $keys[$item['key']] = $item;
            if ($withChildren && isset($item['children']) && $item['children']) {
                $keys = array_merge($keys, $this->extractKeys($item['children']));
            }
        }
        return $keys;
    }

    private function addHelpContent(&$menu) {
        $minutes = 5;
        $yamlContents = Cache::remember('centralizedHelp', $minutes, function() {
            $options = array(
                'http' => array(
                    'header' => "User-Agent:MyAgent/1.0\r\n",
                    'method'  => 'GET',
                    'timeout' => 12
                )
            );
            $url = 'http://which.andevent.net/centralized/help';
            $context  = stream_context_create($options);
            $content = file_get_contents($url, false, $context);

            if ($content) {
                $content = json_decode($content, true);
            }
            return is_array($content) ? $content['help'] : [];
        });

        if ($yamlContents && count($yamlContents)) {
            foreach ($menu as &$entry) {
                if (key_exists($entry['key'], $yamlContents)) {
                    $entry['data'] = key_exists('data', $entry) ? $entry['data'] : [];
                    $entry['data']['help'] = $yamlContents[$entry['key']];
                }
                if (key_exists('children', $entry)) {
                    $this->addHelpContent($entry['children']);
                }
            }
        }
    }

    private function getMergedMenu() {
        $octoberMenu = $this->extractStructure(Menu::getOctoberMenu());
        $loadedMenu = Menu::getStoredMenuArray();
        $octoberKeys = $this->extractKeys($octoberMenu);
        $loadedKeys = $this->extractKeys($loadedMenu, true);
//        $this->getDifference($octoberKeys, $loadedKeys);
        $newKeys = array_diff(array_keys($octoberKeys), array_keys($loadedKeys));
        foreach($newKeys as $key) {
            $item = $octoberKeys[$key];
            $item['data'] = key_exists('data', $item) ? $item['data'] : [];
            $item['data']['isNew'] = true;
            $loadedMenu[] = $item;
        }
        $this->addHelpContent($loadedMenu);
        return $loadedMenu;
    }

    private function getDifference($octoberKeys, $loadedKeys) {
        dd([$octoberKeys, $loadedKeys]);
    }


    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        parent::prepareVars();
        $this->vars['defaultLocale'] = $this->defaultLocale->code;
        $this->vars['locales'] = Locale::listAvailable();
        $this->vars['name'] = $this->formField->getName();
        $this->vars['menu'] = $this->getMergedMenu();
        $this->vars['model'] = $this->model;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->actAsParent();
        parent::loadAssets();
        $this->actAsParent(false);

        $this->addCss('menueditor.css', 'KosmosKosmos.EnhancedBackend');
        $this->addJs('menueditor.js', 'KosmosKosmos.EnhancedBackend');
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentViewPath()
    {
        return base_path().'/modules/backend/formwidgets/richeditor/partials';
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentAssetPath()
    {
        return '/modules/backend/formwidgets/richeditor/assets';
    }

/*    public function onUpdateNode() {
        $input = $_POST;
        return $input;
    }*/

}
