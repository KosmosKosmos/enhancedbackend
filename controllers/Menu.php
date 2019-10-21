<?php namespace KosmosKosmos\EnhancedBackend\Controllers;

use Log;
use Backend\Classes\Controller;

/**
 * Menu Back-end Controller
 */
class Menu extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('KosmosKosmos.EnhancedBackend', 'enhancedbackend', 'menu');
    }

}
