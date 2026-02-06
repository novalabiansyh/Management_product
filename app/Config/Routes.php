<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Login::login');

$routes->post('login', 'Login::processLogin');
$routes->get('logout', 'Login::logout');

//route untuk products
$routes->get('products', 'Product::index');
$routes->post('products/datatable', 'Product::datatable');
$routes->get('products/form', 'Product::forms');
$routes->get('products/form/(:any)', 'Product::forms/$1');
$routes->post('products/add', 'Product::add');
$routes->post('products/update/(:any)', 'Product::update/$1');
$routes->post('products/delete/(:any)', 'Product::delete/$1');
$routes->post('products/categoryList', 'Product::categoryList');
$routes->get('products/printPdf', 'Product::printPdf');
$routes->get('products/exportExcelChunk', 'Product::exportExcelChunk');
$routes->post('products/exportExcel', 'Product::exportExcel');
$routes->get('products/exportExcelCount', 'Product::exportExcelCount');
$routes->add('products/import', 'Product::import');
$routes->get('products/importChunk', 'Product::importChunk');

//route untuk category
$routes->get('category', 'Category::index');
$routes->post('category/datatable', 'Category::datatable');
$routes->get('category/form', 'Category::forms');
$routes->get('category/form/(:any)', 'Category::forms/$1');
$routes->post('category/add', 'Category::add');
$routes->post('category/update/(:any)', 'Category::update/$1');
$routes->post('category/delete/(:any)', 'Category::delete/$1');
$routes->get('category/printPdf', 'Category::printPdf');