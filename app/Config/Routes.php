<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Login::login');

$routes->post('login', 'Login::processLogin');
$routes->get('logout', 'Login::logout');

//route untuk products
$routes->group('products', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('', 'Product::index');
    $routes->post('datatable', 'Product::datatable');
    $routes->get('form', 'Product::forms');
    $routes->get('form/(:any)', 'Product::forms/$1');
    $routes->post('add', 'Product::add');
    $routes->post('update/(:any)', 'Product::update/$1');
    $routes->post('delete/(:any)', 'Product::delete/$1');
    $routes->post('categoryList', 'Product::categoryList');
    $routes->get('printPdf', 'Product::printPdf');
    $routes->get('exportExcelChunk', 'Product::exportExcelChunk');
    $routes->post('exportExcel', 'Product::exportExcel');
    $routes->get('exportExcelCount', 'Product::exportExcelCount');
    $routes->add('import', 'Product::import');
    $routes->get('importChunk', 'Product::importChunk');
    $routes->get('downloadTemplate', 'Product::downloadTemplate');
 
});

$routes->group('files', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->post('datatable', 'File::datatable');
    $routes->post('upload', 'File::upload');
    $routes->get('download/(:num)', 'File::download/$1');
    $routes->post('delete/(:num)', 'File::delete/$1');
});


//route untuk category
$routes->get('category', 'Category::index');
$routes->post('category/datatable', 'Category::datatable');
$routes->get('category/form', 'Category::forms');
$routes->get('category/form/(:any)', 'Category::forms/$1');
$routes->post('category/add', 'Category::add');
$routes->post('category/update/(:any)', 'Category::update/$1');
$routes->post('category/delete/(:any)', 'Category::delete/$1');
$routes->get('category/printPdf', 'Category::printPdf');