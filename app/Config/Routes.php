<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::index');
$routes->get('auth', 'Auth::index');
$routes->post('auth/login', 'Auth::login', ['filter' => 'login_throttle']);
$routes->get('auth/logout', 'Auth::logout');
$routes->get('auth/2fa', 'TwoFactorAuth::index');
$routes->get('auth/2fa/setup', 'TwoFactorAuth::setup');
$routes->post('auth/2fa/setup', 'TwoFactorAuth::setupSave');
$routes->get('auth/2fa/challenge', 'TwoFactorAuth::challenge');
$routes->post('auth/2fa/challenge', 'TwoFactorAuth::verify');

$routes->group('dashboard', ['filter' => ['auth', 'rbac']], function($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('layanan', 'Dashboard::layanan');
    $routes->get('statistik', 'Dashboard::statistik');
    $routes->get('comparisons', 'Dashboard::comparisons');
    $routes->get('penyedia', 'Dashboard::penyedia');
    $routes->get('swakelola', 'Dashboard::swakelola');
    $routes->get('rup-opd', 'Dashboard::rupOpd');
    $routes->get('rup-itkp', 'Dashboard::rupItkp');
    $routes->get('getRupItkpDatatables', 'Dashboard::getRupItkpDatatables');
    $routes->get('getRupItkpDashboardData', 'Dashboard::getRupItkpDashboardData');
    $routes->get('export-rup-itkp', 'Dashboard::exportRupItkp');
    $routes->get('kajiulang-rup-data', 'Dashboard::getKajiUlangRupData');
    $routes->get('monev-proyek', 'Dashboard::monevProyek');
    
    // Monev V2 Workflow Routes
    $routes->get('monev-v2/template-active', 'Dashboard::getMonevActiveTemplate');
    $routes->post('monev-v2/start', 'MonevV2Controller::start');
    $routes->get('monev-v2/period/(:num)', 'MonevV2Controller::periodDetail/$1');
    $routes->post('monev-v2/visit/save', 'MonevV2Controller::saveVisit');
    $routes->post('monev-v2/assessment/save', 'MonevV2Controller::saveAssessment');
    $routes->post('monev-v2/finding/save', 'MonevV2Controller::addFinding');
    $routes->post('monev-v2/finding/status', 'MonevV2Controller::updateFindingStatus');
    $routes->post('monev-v2/followup/save', 'MonevV2Controller::saveFollowup');
    $routes->post('monev-v2/followup/verify', 'MonevV2Controller::verifyFollowup');
    $routes->post('monev-v2/evidence/upload', 'MonevV2Controller::uploadEvidence');
    $routes->post('monev-v2/evidence/delete/(:num)', 'MonevV2Controller::deleteEvidence/$1');
    $routes->get('monev-v2/period/(:num)/readiness', 'MonevV2Controller::finalizationReadiness/$1');
    $routes->post('monev-v2/submit', 'MonevV2Controller::submit');
    $routes->post('monev-v2/review', 'MonevV2Controller::review');
    $routes->post('monev-v2/finalize', 'MonevV2Controller::finalize');
    $routes->post('monev-v2/reopen', 'MonevV2Controller::reopen');
    $routes->get('monev-v2/proyek/(:num)/periods', 'MonevV2Controller::getProjectPeriods/$1');
    $routes->get('monev-v2/proyek/(:num)/context', 'MonevV2Controller::projectContext/$1');

    // Monev V2 UI Pages (Step 7)
    $routes->get('monev', 'MonevV2Controller::index');
    $routes->get('monev/project/(:num)', 'MonevV2Controller::projectWorkspace/$1');
    $routes->get('monev/project/(:num)/(:num)', 'MonevV2Controller::projectWorkspace/$1/$2');
    $routes->get('monev/project/(:num)/(:num)/(:segment)', 'MonevV2Controller::projectWorkspace/$1/$2/$3');
    $routes->get('monev/project/(:num)/create', 'MonevV2Controller::createPeriodForProject/$1');
    $routes->get('monev/(:num)', 'MonevV2Controller::periodWorkspace/$1');
    $routes->get('monev/(:num)/assessment', 'MonevV2Controller::assessmentView/$1');
    $routes->get('monev/(:num)/findings', 'MonevV2Controller::findingsView/$1');
    $routes->get('monev/(:num)/evidence', 'MonevV2Controller::evidenceView/$1');
    $routes->get('monev/(:num)/review', 'MonevV2Controller::reviewView/$1');
    $routes->get('monev/(:num)/history', 'MonevV2Controller::historyView/$1');

    $routes->get('monev-summary', 'Dashboard::monevSummary');
    $routes->get('export-monev-summary', 'Dashboard::exportMonevSummary');
    $routes->get('getData/(:any)', 'Dashboard::getData/$1');
    $routes->get('getDashboardSummary/(:any)', 'Dashboard::getDashboardSummary/$1');
    $routes->get('getDatatables/(:any)', 'Dashboard::getDatatables/$1');
    $routes->get('getFilterOptions/(:any)', 'Dashboard::getFilterOptions/$1');
    
    // Data Pengadaan Routes
    $routes->get('lpse/(:any)', 'Dashboard::lpse/$1');
    $routes->get('epurchasing', 'Dashboard::epurchasing');
    $routes->get('table-epurchasing', 'Dashboard::tableEpurchasing');
    $routes->get('itkp', 'Dashboard::itkp');
    $routes->get('getItkpDatatables', 'Dashboard::getItkpDatatables');
    $routes->get('export-itkp', 'Dashboard::exportItkp');
    $routes->get('dashboard-lpse', 'Dashboard::dashboardLpse');
    $routes->get('getDashboardLpseData', 'Dashboard::getDashboardLpseData');
    $routes->get('getEpurchasingDashboardData', 'Dashboard::getEpurchasingDashboardData');
    $routes->get('getLpseDatatables/(:any)', 'Dashboard::getLpseDatatables/$1');
    $routes->get('getEpurchasingDatatables', 'Dashboard::getEpurchasingDatatables');
    
    // Monev OPD Routes
    $routes->get('monev-opd', 'Dashboard::monevOpd');
    $routes->get('getMonevOpdData', 'Dashboard::getMonevOpdData');
    $routes->get('getMonevProgresData', 'Dashboard::getMonevProgresData');
    $routes->get('export-monev-progres', 'Dashboard::exportMonevProgresSpse');
    $routes->get('getChartData/(:num)', 'Dashboard::getChartData/$1');
    $routes->get('export/(:any)/(:num)', 'Dashboard::export/$1/$2');
    $routes->get('export-rup-opd', 'Dashboard::exportRupOpd');
    $routes->get('export-rup-detail', 'Dashboard::exportRupDetail');
    $routes->get('export-non-pengadaan', 'Dashboard::exportNonPengadaan');
    $routes->get('clear-cache', 'Dashboard::clearCache');
    // Penyedia Katalog (Database)
    $routes->get('penyedia-katalog', 'Dashboard::penyediaKatalog');
    $routes->get('getPenyediaKatalog', 'Dashboard::getPenyediaKatalog');
    $routes->get('exportPenyediaKatalog', 'Dashboard::exportPenyediaKatalog');
    
    // Detail & Dashboard Penyedia
    $routes->get('dashboard-penyedia', 'Dashboard::dashboardPenyedia');
    $routes->get('getDashboardPenyediaData', 'Dashboard::getDashboardPenyediaData');
    $routes->get('exportDashboardRekanan', 'Dashboard::exportDashboardRekanan');
    $routes->get('detail-penyedia', 'Dashboard::detailPenyedia');
    $routes->get('getDetailPenyedia', 'Dashboard::getDetailPenyedia');
    $routes->get('exportDetailPenyedia', 'Dashboard::exportDetailPenyedia');
    $routes->get('getTransaksiPenyedia', 'Dashboard::getTransaksiPenyedia');
    $routes->get('getProfilPenyedia', 'Dashboard::getProfilPenyedia');
    $routes->match(['get', 'post'], 'detectDomisiliAi', 'Dashboard::detectDomisiliAi');
    $routes->get('fetchApiPenyedia/(:any)', 'Dashboard::fetchApiPenyedia/$1');

    // Scrape Data Perusahaan & Sinkronisasi Bot SIKaP
    $routes->get('scrape-perusahaan', 'Dashboard::scrapePerusahaan');
    $routes->post('do-scrape-perusahaan', 'Dashboard::doScrapePerusahaan');
    $routes->post('do-sync-sikap-bot', 'Dashboard::doSyncSikapBot');
    $routes->get('scrape-perusahaan-status', 'Dashboard::scrapePerusahaanStatus');
    $routes->get('getScrapeContractorsDatatables', 'Dashboard::getScrapeContractorsDatatables');
    $routes->get('exportContractors', 'Dashboard::exportContractors');
    $routes->post('do-launch-bot-lpjk', 'Dashboard::doLaunchBotLpjk');
    $routes->get('get-bot-lpjk-status', 'Dashboard::getBotLpjkStatus');
    $routes->post('stop-bot-lpjk', 'Dashboard::stopBotLpjk');
    $routes->post('do-launch-bot-spse', 'Dashboard::doLaunchBotSpse');
    $routes->get('get-bot-spse-status', 'Dashboard::getBotSpseStatus');
    $routes->post('stop-bot-spse', 'Dashboard::stopBotSpse');
    $routes->get('get-contractor-lpjk-detail/(:num)', 'Dashboard::getContractorLpjkDetail/$1');

    // PPK Claiming Routes
    $routes->get('get-rup-details/(:segment)', 'Dashboard::getRupDetails/$1');
    $routes->post('add-monev-paket', 'Dashboard::addMonevPaket');
    $routes->post('remove-monev-paket/(:segment)', 'Dashboard::removeMonevPaket/$1');

    // PPK Monev Proyek Routes
    $routes->post('monev/setup-proyek', 'Dashboard::saveMonevProyek');
    $routes->post('monev/save-jadwal', 'Dashboard::saveMonevJadwal');
    $routes->post('monev/add-harian', 'Dashboard::saveMonevHarian');
    $routes->post('monev/verify-harian/(:num)', 'Dashboard::verifyMonevHarian/$1');
    $routes->post('monev/upload-dokumen', 'Dashboard::saveMonevDokumen');
    $routes->get('monev/get-chart-data/(:num)', 'Dashboard::getMonevChartData/$1');
    $routes->post('monev/delete-proyek/(:num)', 'Dashboard::deleteMonevProyek/$1');
    $routes->post('monev/weekly-ai-analysis', 'Dashboard::generateWeeklyAiAnalysis');
    $routes->post('analyze-anomaly', 'Dashboard::analyzeAnomaly');
    $routes->post('analyze-epurchasing', 'Dashboard::analyzeEpurchasing');
    $routes->post('verify-non-pengadaan-ai', 'Dashboard::verifyNonPengadaanAi');
});
// Profile Routes
$routes->get('profile', 'Profile::index', ['filter' => 'auth']);
$routes->post('profile/update', 'Profile::update', ['filter' => 'auth']);
$routes->get('alter-db', 'Dashboard::alterDb', ['filter' => ['auth', 'admin']]);

$routes->group('report', ['filter' => ['auth', 'rbac']], function($routes) {
    $routes->get('/', 'Report::index');
    $routes->get('getSummaryData', 'Report::getSummaryData');
});

$routes->group('master-anggaran', ['filter' => ['auth', 'rbac']], function($routes) {
    $routes->get('/', 'MasterAnggaranController::index');
    $routes->post('update', 'MasterAnggaranController::update');
    $routes->get('history/(:num)', 'MasterAnggaranController::history/$1');
    $routes->get('export', 'MasterAnggaranController::export');
});


$routes->group('user', ['filter' => ['auth', 'admin']], function($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('create', 'UserController::create');
    $routes->post('store', 'UserController::store');
    $routes->get('edit/(:any)', 'UserController::edit/$1');
    $routes->post('update/(:any)', 'UserController::update/$1');
    $routes->get('delete/(:any)', 'UserController::delete/$1');
    $routes->get('reset-2fa/(:any)', 'UserController::reset2fa/$1');
    
    // Custom Permissions per User
    $routes->get('permissions/(:any)', 'UserPermissionController::index/$1');
    $routes->post('permissions/save/(:any)', 'UserPermissionController::save/$1');
    $routes->post('permissions/reset/(:any)', 'UserPermissionController::reset/$1');
    $routes->get('logs', 'UserLogController::index');
    $routes->get('logs/getDatatables', 'UserLogController::getDatatables');
    $routes->post('logs/clear', 'UserLogController::clear');
    
    // Chatbot Settings
    $routes->get('chatbot-settings', 'SettingsController::chatbot');
    $routes->post('chatbot-settings/save', 'SettingsController::saveChatbot');
    $routes->post('chatbot-settings/clear-logs', 'SettingsController::clearChatbotLogs');
    
    // Chatbot Knowledge RAG
    $routes->get('chatbot-knowledge', 'ChatbotKnowledge::index');
    $routes->post('chatbot-knowledge/upload', 'ChatbotKnowledge::upload');
    $routes->post('chatbot-knowledge/delete', 'ChatbotKnowledge::delete');
    
    // AGI Integrator & API Settings
    $routes->get('api-settings', 'SettingsController::api');
    $routes->post('api-settings/generate', 'SettingsController::generateToken');
    $routes->post('api-settings/save-agi', 'SettingsController::saveAgiSettings');
    $routes->post('api-settings/test-connection', 'SettingsController::testAgiConnection');
    $routes->post('api-settings/import-config', 'SettingsController::importAgiConfig');
    $routes->post('api-settings/add-endpoint', 'SettingsController::addManualEndpoint');
    $routes->post('api-settings/delete-endpoint', 'SettingsController::deleteEndpoint');
    $routes->get('api-settings/preview-endpoint/(:any)', 'SettingsController::previewEndpoint/$1');

    // Sync Management
    $routes->get('sync', 'SettingsController::sync');
    $routes->get('do-sync/(:any)', 'SettingsController::doSync/$1');

    // RBAC Management
    $routes->get('rbac', 'RbacController::index');
    $routes->post('rbac/save', 'RbacController::save');
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

    // System Patcher
    $routes->get('patch', 'PatchController::index');
    $routes->post('patch/upload', 'PatchController::upload');
    $routes->get('patch/rollback/(:any)', 'PatchController::rollback/$1');
    $routes->get('patch/delete-backup/(:any)', 'PatchController::deleteBackup/$1');
    $routes->get('patch/generate', 'PatchController::generate');
    $routes->post('patch/download', 'PatchController::downloadPatch');
});

$routes->group('dynamic', ['filter' => ['auth', 'dynamic_access']], function($routes) {
    $routes->get('getDatatables/(:any)', 'DynamicData::getDatatables/$1');
    $routes->get('(:any)', 'DynamicData::view/$1');
});

// Top-Level Monev Alias Routes (Protected by auth and rbac)
$routes->group('dashboard/monev', ['filter' => ['auth', 'rbac']], function($routes) {
    $routes->get('/', 'MonevV2Controller::index');
    $routes->get('project/(:num)', 'MonevV2Controller::projectWorkspace/$1');
    $routes->get('project/(:num)/(:num)', 'MonevV2Controller::projectWorkspace/$1/$2');
    $routes->get('project/(:num)/(:num)/(:segment)', 'MonevV2Controller::projectWorkspace/$1/$2/$3');
    $routes->get('project/(:num)/create', 'MonevV2Controller::createPeriodForProject/$1');
    $routes->get('(:num)', 'MonevV2Controller::periodWorkspace/$1');
    $routes->get('(:num)/assessment', 'MonevV2Controller::assessmentView/$1');
    $routes->get('(:num)/findings', 'MonevV2Controller::findingsView/$1');
    $routes->get('(:num)/evidence', 'MonevV2Controller::evidenceView/$1');
    $routes->get('(:num)/review', 'MonevV2Controller::reviewView/$1');
    $routes->get('(:num)/history', 'MonevV2Controller::historyView/$1');
});

// Chatbot Route — autentikasi & rate limiting diperlukan
$routes->post('chatbot/ask', 'Chatbot::ask', ['filter' => ['auth', 'api_throttle']]);

/**
 * Mobile App API Routes
 * Diakses via /api/...
 */
// Public API Login Endpoint (rate limited)
$routes->post('api/login', 'Api::login', ['filter' => 'api_throttle']);

// Protected API Endpoints (Bearer token + rate limiting)
$routes->group('api', ['filter' => ['api_auth', 'api_throttle']], function($routes) {
    $routes->get('summary', 'Api::getSummary');
    $routes->get('monev-summary', 'Api::getMonevSummary');
    $routes->get('lpse/(:segment)', 'Api::getLpse/$1');
    $routes->get('rup-opd', 'Api::getRupOpd');
    $routes->post('chatbot/ask', 'Chatbot::ask');
});
