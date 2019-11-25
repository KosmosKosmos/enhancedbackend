<?php namespace KosmosKosmos\EnhancedBackend\FormWidgets;

use Backend\FormWidgets\RichEditor;
use Log;
use Yaml;
use Lang;
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
                'title' => Lang::get(key_exists('label', $item) ? $item->label : $key),
                'key' => $item->key,
            ];
            if (key_exists('subMenu', $item) && count($item->subMenu) > 1) {
                $entry['children'] = $this->extractStructure($item->subMenu, key_exists('isGrouped', $item));
                $entry['isExpanded'] = false;
            } else {
                $entry['isLeaf'] = true;
                $entry['hasJs'] = key_exists('hasJs', $item);
            }
            $structure[] = $entry;
        }
        return $structure;
    }

    private function extractKeys($menu, $withChildren = false) {
        $keys = [];
        foreach($menu as $index => $item) {
            $keys[$item['key']] = $item;
            if ($withChildren && key_exists('children', $item)) {
                $keys = array_merge($keys, $this->extractKeys($item['children']));
            }
        }
        return $keys;
    }

    private function addHelpContent(&$menu) {
        $file = 'http://which.andevent.net/centralized/help';
        $content = @file_get_contents($file);
        if ($content) {
            $yamlContents = json_decode($content, true)['help'];
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
        $loadedMenu = Menu::getStoredMenu();
        $octoberKeys = $this->extractKeys($octoberMenu);
        $loadedKeys = $this->extractKeys($loadedMenu, true);
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
