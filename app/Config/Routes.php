<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('hal_login', 'Login::login');
$routes->post('login', 'Login::processLogin');
$routes->get('logout', 'Login::logout');

$routes->get('products', 'Product::index');
$routes->post('products/datatable', 'Product::datatable');
$routes->get('products/form', 'Product::forms');
$routes->get('products/form/(:any)', 'Product::forms/$1');
$routes->post('products/add', 'Product::add');
$routes->post('products/update/(:any)', 'Product::update/$1');
$routes->post('products/delete/(:any)', 'Product::delete/$1');
$routes->post('products/categoryList', 'Product::categoryList');


