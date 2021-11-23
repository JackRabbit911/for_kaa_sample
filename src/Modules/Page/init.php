<?php

use WN\Core\{Route, I18n, View, Request, Helper, Core};
use WN\Page\{TplWrap};

// var_dump(DOMAIN, SUBDOMAIN, DOCROOT);
// exit;

require_once DOCROOT.'vendor/autoload.php';

Route::set('meta', '~tpl(/<file>)')
    ->filter(array('file' => '.+'))
    ->defaults(array(
        // 'namespace'  => 'WN\Page',
        'controller' => 'WN\Page\Controller\Meta',
        'action'     => 'index',
        'file'       => NULL,
    ))
    ->top();

// Route::set('form', '~form/<module>/<method>(/<view>(/<param>))(?<query>)')
// ->defaults(['controller' => 'WN\Page\Controller\FormTemplate']);

Route::set('form', '(<lang>/)~form/<action>(/<view>)(?<query>)', ['lang' => I18n::langs(), 'view' => '[-_\d\w\/.]+'])
    ->defaults(['controller' => 'WN\Page\Controller\FormTemplate']);



Route::set('page', '(<lang>)(/)(<page>)(?<query>)', ['page' => '[-\d\w\/.]+', 'lang' => I18n::langs()])
->defaults([
    // 'namespace' => 'WN\Page',
    // 'controller'=>  'WN\Page\Controller\PageTemplate',
    'controller' => 'WN\Page\Controller\Template',
    // 'controller' => 'WN\Page\Controller\Page',
])
->filter(function($params){
    $request = Request::current();
   
    if($request->method() === 'POST' && $request->is_ajax())
        $params['action'] = 'ajax_post';
    elseif($request->method() === 'GET' && $request->is_ajax())
    {
        if(Helper\Accept::type('application/json') == 1)
            $params['action'] = 'ajax_json';
        else $params['action'] = 'ajax_html';
    }   
    elseif($request->method() === 'GET' && !$request->is_initial())
        $params['action'] = 'sub_request';
    else $params['action'] = 'index';

    return $params;

    // return true;
})
->bottom()
;

// function _form($view)
// {
//     $file = Core::find_file(TplWrap::$path.$view);
//     return TplWrap::hidden_input_action($file);
// }