<?php namespace KosmosKosmos\EnhancedBackend\Widgets\Help;

use Andosto\EventManager\Models\Participant;
use Dompdf\Exception;
use KosmosKosmos\EnhancedBackend\Models\Menu as MenuModel;
use MongoDB\BSON\Regex;
use RainLab\User\Models\User;
use Yaml;
use Cache;
use Log;
use Backend\Classes\WidgetBase;
use Backend\Models\UserPreference;
use October\Rain\Support\Facades\File;

class Help extends WidgetBase
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'helpWidget';

    public function __construct($controller, $alias, $readOnly = false)
    {
        parent::__construct($controller, []);
    }

    public function onGetHelpPage() {
        $locale = UserPreference::forUser()->get('backend::backend.preferences', ['locale' => 'en'])['locale'];
        $pageKey = post()['page'];
        try {
            $file = 'http://which.andevent.net/centralized/help/'.$locale.'/'. $pageKey;
            $content = @file_get_contents($file);
        } catch (Exception $e) {
            $content = '';
        }
        return htmlspecialchars_decode($content);
    }

    public function onSearchPage() {
        $searchString = explode(' ', post()['search']);
        $locale = UserPreference::forUser()->get('backend::backend.preferences', ['locale' => 'en'])['locale'];

        $searchTagList = Cache::rememberForever('searchTags', function() {
            return MenuModel::generateTagListForSearch();
        });
        $menuModel = new MenuModel();
        $menuStructure = $menuModel->getFlatOctoberMenu(true);
        $result = [];
        foreach($searchTagList as $menuKey => $searchTags) {
            if (key_exists($locale, $searchTags)) {
                foreach($searchString as $search) {
                    if ($search && strpos(strtolower($searchTags[$locale]), strtolower($search)) !== false) {
                        $result[$menuKey] = key_exists($menuKey, $result)
                            ? $result[$menuKey] : ['score' => 0, 'info' => $menuStructure[$menuKey]];
                        $result[$menuKey]['score'] += 1;
                    }
                }
            }
        }

        $searchColumns = ['$and' => []];
        foreach($searchString as $search) {
            $or = ['$or' => []];
            $or['$or'][] = ['firstname' => new Regex($search, 'i')];
            $or['$or'][] = ['lastname' => new Regex($search, 'i')];
            $or['$or'][] = ['email' => new Regex($search, 'i')];
            $searchColumns['$and'][] = $or;
        }
        $collection = Participant::getMongoConnection();
        $queryResults = $collection->find($searchColumns);
        foreach ($queryResults as $queryResult) {
            if ($queryResult->offsetExists('user_id')) {
                $user = User::find($queryResult['user_id']);
                if ($user) {
                    $userString = 'mit dem User <a href="/backend/rainlab/user/users/preview/' . $user['id'] . '">' . $user['username'] . '</a>';
                    $result[] = [
                        'score' => 1,
                        'info' => [
                            'label' => $queryResult['firstname'] . ' ' . $queryResult['lastname'],
                            'url' => '/backend/andosto/eventmanager/participants/update/' . $queryResult['participant_id'],
                            'description' => 'EventManager Teilnehmer' . $userString
                        ]
                    ];
                }
            }
        }
        $result = array_values($result);
        $result = array_sort($result, function($a, $b) {
            return $a['score'] > $b['score'] ? 1 : ($a['score'] > $b['score'] ? -1 : 0);
        });
        return json_encode(array_map(function($item) {return $item['info'];}, $result));
    }
}
