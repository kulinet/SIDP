<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::index');
$routes->get('auth', 'Auth::index');
$routes->post('auth/login', 'Auth::login');
$routes->get('auth/logout', 'Auth::logout');

$routes->group('dashboard', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('comparisons', 'Dashboard::comparisons');
    $routes->get('penyedia', 'Dashboard::penyedia');
    $routes->get('swakelola', 'Dashboard::swakelola');
    $routes->get('rup-opd', 'Dashboard::rupOpd');
    $routes->get('getData/(:any)', 'Dashboard::getData/$1');
    $routes->get('getDashboardSummary/(:any)', 'Dashboard::getDashboardSummary/$1');
    $routes->get('getDatatables/(:any)', 'Dashboard::getDatatables/$1');
    
    // Data Pengadaan Routes
    $routes->get('lpse/(:any)', 'Dashboard::lpse/$1');
    $routes->get('epurchasing', 'Dashboard::epurchasing');
    $routes->get('table-epurchasing', 'Dashboard::tableEpurchasing');
    $routes->get('dashboard-lpse', 'Dashboard::dashboardLpse');
    $routes->get('getDashboardLpseData', 'Dashboard::getDashboardLpseData');
    $routes->get('getEpurchasingDashboardData', 'Dashboard::getEpurchasingDashboardData');
    $routes->get('getLpseDatatables/(:any)', 'Dashboard::getLpseDatatables/$1');
    $routes->get('getEpurchasingDatatables', 'Dashboard::getEpurchasingDatatables');
    $routes->get('getChartData/(:num)', 'Dashboard::getChartData/$1');
    $routes->get('export/(:any)/(:num)', 'Dashboard::export/$1/$2');
    $routes->get('export-rup-opd', 'Dashboard::exportRupOpd');
    $routes->get('clear-cache', 'Dashboard::clearCache');
});


$routes->group('user', ['filter' => ['auth', 'admin']], function($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('create', 'UserController::create');
    $routes->post('store', 'UserController::store');
    $routes->get('edit/(:num)', 'UserController::edit/$1');
    $routes->post('update/(:num)', 'UserController::update/$1');
    $routes->get('delete/(:num)', 'UserController::delete/$1');
    
    // Chatbot Settings
    $routes->get('chatbot-settings', 'SettingsController::chatbot');
    $routes->post('chatbot-settings/save', 'SettingsController::saveChatbot');
    
    // API Security Settings
    $routes->get('api-settings', 'SettingsController::api');
    $routes->post('api-settings/generate', 'SettingsController::generateToken');
});

$routes->group('development', ['filter' => ['auth', 'operator']], function($routes) {
    $routes->get('/', 'Development::index');
    $routes->get('create', 'Development::create');
    $routes->post('store', 'Development::store');
    $routes->get('edit/(:num)', 'Development::edit/$1');
    $routes->post('update/(:num)', 'Development::update/$1');
    $routes->get('delete/(:num)', 'Development::delete/$1');
    $routes->post('addColumn/(:num)', 'Development::addColumn/$1');
    $routes->get('deleteColumn/(:num)', 'Development::deleteColumn/$1');
    $routes->post('fetchApiKeys', 'Development::fetchApiKeys');
    
    // Charts
    $routes->get('charts', 'Development::charts');
    $routes->post('storeChart', 'Development::storeChart');
    $routes->post('updateChart/(:num)', 'Development::updateChart/$1');
    $routes->get('deleteChart/(:num)', 'Development::deleteChart/$1');
});

$routes->group('dynamic', ['filter' => ['auth', 'dynamic_access']], function($routes) {
    $routes->get('getDatatables/(:any)', 'DynamicData::getDatatables/$1');
    $routes->get('(:any)', 'DynamicData::view/$1');
});

// Chatbot Route
$routes->post('chatbot/ask', 'Chatbot::ask');

/**
 * Mobile App API Routes
 * Accessed via /api/...
 */
$routes->group('api', function($routes) {
    $routes->post('login', 'Api::login');
    $routes->get('summary', 'Api::getSummary');
    $routes->get('lpse/(:segment)', 'Api::getLpse/$1');
    $routes->get('rup-opd', 'Api::getRupOpd');
    $routes->post('chatbot/ask', 'Chatbot::ask'); // Also accessible via API
});
