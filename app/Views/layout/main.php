<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================

* Product Page: https://themeselection.com/products/sneat-bootstrap-html-admin-template/
* Created by: ThemeSelection
* License: You must have a valid license purchased in order to legally use the theme for your project.
* Copyright ThemeSelection (https://themeselection.com)

=========================================================
 -->
<!-- beautify ignore:start -->
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="<?= base_url('assets') ?>/"
  data-template="vertical-menu-template-free"
>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title><?= $title ?? 'AJIP' ?></title>

    <meta name="description" content="" />
    <meta name="theme-color" content="#0ea5e9" />
    <link rel="manifest" href="<?= base_url('manifest.json') ?>" />
    
    <!-- CSRF Token -->
    <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets') ?>/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />

    <!-- Icons. Uncomment required icon fonts -->
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="<?= base_url('assets') ?>/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="<?= base_url('assets') ?>/vendor/js/helpers.js"></script>

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="<?= base_url('assets') ?>/js/config.js"></script>
    
    <!-- Custom Sidebar Theme -->
    <style>
      .bg-menu-custom {
        background-color: #1e253c !important; /* Premium Dark Navy */
        color: #a2b0d3 !important;
      }
      .bg-menu-custom .menu-link {
        color: #a2b0d3 !important;
      }
      .bg-menu-custom .menu-link:hover {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.08) !important;
      }
      .bg-menu-custom .menu-inner > .menu-item.active > .menu-link {
        color: #ffffff !important;
        background-color: #696cff !important;
        box-shadow: 0 2px 6px 0 rgba(105, 108, 255, 0.4);
      }
      .bg-menu-custom .app-brand-text {
        color: #ffffff !important;
      }
      .bg-menu-custom .menu-header-text {
        color: #7280a5 !important;
      }
      .bg-menu-custom .menu-icon {
        color: #8c9ebf !important;
      }
      .bg-menu-custom .menu-item.active > .menu-link .menu-icon {
        color: #ffffff !important;
      }
      .layout-menu.bg-menu-custom .menu-inner-shadow {
        background: linear-gradient(#1e253c 41%, rgba(30, 37, 60, 0.11) 95%, rgba(30, 37, 60, 0));
      }
      /* Ensure text is visible in sub-menus if added in the future */
      .bg-menu-custom .menu-sub > .menu-item > .menu-link {
        color: #8c9ebf !important;
      }
      
      /* Global Text Contrast Improvement */
      body, .layout-page, .content-wrapper, .card, .table, .form-control, .form-select, .text-muted {
        color: #111111 !important;
      }
      .text-muted {
        color: #444444 !important;
      }
      .fw-bold, .card-title, h1, h2, h3, h4, h5, h6 {
        color: #000000 !important;
      }
      th {
        color: #000000 !important;
        font-weight: 700 !important;
      }
    </style>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-custom">
          <div class="app-brand demo d-flex flex-column align-items-start">
            <a href="<?= base_url('dashboard') ?>" class="app-brand-link text-decoration-none">
                <h4 class="app-brand-text fw-bold text-uppercase text-white mb-0">AJIP</h4>
            </a>
            <p class="app-brand-text mb-0 mt-1 text-white" style="opacity:0.8;">Aplikasi Jendela Informasi Pengadaan</p>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <?php 
                $permModel = new \App\Models\RolePermissionModel();
                $role = session()->get('role');
            ?>

            <!-- Data RUP Category -->
            <?php 
                $showDataRUP = has_permission('dashboard') || 
                               has_permission('comparisons') || 
                               has_permission('penyedia') || 
                               has_permission('swakelola') || 
                               has_permission('rup-opd') ||
                               has_permission('rup_itkp');
                
                if($showDataRUP): 
            ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Data RUP</span>
            </li>
            <?php endif; ?>
            <?php if(has_permission('dashboard')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('comparisons')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/comparisons')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/comparisons') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div data-i18n="Perbandingan Tahunan">Perbandingan Tahunan</div>
              </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('penyedia')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/penyedia')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/penyedia') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-table"></i>
                    <div data-i18n="Penyedia">Penyedia</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('swakelola')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/swakelola')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/swakelola') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-table"></i>
                    <div data-i18n="Swakelola">Swakelola</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('rup-opd')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/rup-opd')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/rup-opd') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-buildings"></i>
                    <div data-i18n="RUP OPD">RUP OPD</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('rup_itkp') || has_permission('itkp') || has_permission('dashboard')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/rup-itkp')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/rup-itkp') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                    <div data-i18n="Data ITKP">Data ITKP</div>
                </a>
            </li>
            <?php endif; ?>

            <!-- Data Pengadaan Category -->
            <?php 
                $showLpseMenu = has_permission('lpse_tender') || 
                                has_permission('lpse_nontender') || 
                                has_permission('lpse_pencatatan_nontender') || 
                                has_permission('lpse_pencatatan_swakelola');
                
                if(has_permission('dashboard-lpse') || $showLpseMenu || has_permission('epurchasing') || has_permission('itkp') || has_permission('monev_opd')): 
            ?>
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Data Pengadaan</span>
            </li>
            
            <?php if(has_permission('dashboard-lpse')): ?>
            <li class="menu-item <?= str_contains(current_url(), 'dashboard-lpse') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/dashboard-lpse') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div data-i18n="Dashboard LPSE">Dashboard LPSE</div>
              </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('itkp')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/itkp')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/itkp') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-layer"></i>
                <div data-i18n="Data ITKP">Data ITKP Realisasi</div>
              </a>
            </li>
            <?php endif; ?>

            <?php if($showLpseMenu): ?>
            <li class="menu-item <?= str_contains(current_url(), '/lpse/') ? 'active open' : '' ?>">
              <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-collection"></i>
                <div data-i18n="Data LPSE">Data LPSE <?= date('Y') ?></div>
              </a>
              <ul class="menu-sub">
                <?php if(has_permission('lpse_tender')): ?>
                <li class="menu-item <?= str_contains(current_url(), '/lpse/tender') ? 'active' : '' ?>">
                  <a href="<?= base_url('dashboard/lpse/tender') ?>" class="menu-link">
                    <div data-i18n="Tender">Tender</div>
                  </a>
                </li>
                <?php endif; ?>
                
                <?php if(has_permission('lpse_nontender')): ?>
                <li class="menu-item <?= str_contains(current_url(), '/lpse/nontender') ? 'active' : '' ?>">
                  <a href="<?= base_url('dashboard/lpse/nontender') ?>" class="menu-link">
                    <div data-i18n="Non Tender">Non Tender</div>
                  </a>
                </li>
                <?php endif; ?>
                
                <?php if(has_permission('lpse_pencatatan_nontender')): ?>
                <li class="menu-item <?= str_contains(current_url(), '/lpse/pencatatan-nontender') ? 'active' : '' ?>">
                  <a href="<?= base_url('dashboard/lpse/pencatatan-nontender') ?>" class="menu-link">
                    <div data-i18n="Pencatatan Non Tender">Pencatatan Non Tender</div>
                  </a>
                </li>
                <?php endif; ?>
                
                <?php if(has_permission('lpse_pencatatan_swakelola')): ?>
                <li class="menu-item <?= str_contains(current_url(), '/lpse/pencatatan-swakelola') ? 'active' : '' ?>">
                  <a href="<?= base_url('dashboard/lpse/pencatatan-swakelola') ?>" class="menu-link">
                    <div data-i18n="Pencatatan Swakelola">Pencatatan Swakelola</div>
                  </a>
                </li>
                <?php endif; ?>
              </ul>
            </li>
            <?php endif; ?>

            <?php if(has_permission('epurchasing')): ?>
            <li class="menu-item <?= str_contains(current_url(), 'epurchasing') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/epurchasing') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cart"></i>
                <div data-i18n="Dashboard E-Purchasing">Dashboard E-Purchasing</div>
              </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('monev_opd')): ?>
            <li class="menu-item <?= str_contains(current_url(), 'monev-opd') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/monev-opd') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bar-chart"></i>
                <div data-i18n="Monev OPD">Monev OPD</div>
              </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- DATABASE Category -->
            <?php if(has_permission('dashboard_penyedia') || has_permission('penyedia-katalog') || has_permission('detail_penyedia')): ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">DATABASE</span>
            </li>
            <?php if(has_permission('dashboard_penyedia')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/dashboard-penyedia')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/dashboard-penyedia') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-map-pin"></i>
                <div data-i18n="Dashboard Penyedia">Dashboard Penyedia</div>
              </a>
            </li>
            <?php endif; ?>
            <?php if(has_permission('penyedia-katalog')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/penyedia-katalog')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/penyedia-katalog') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-store-alt"></i>
                <div data-i18n="Penyedia Katalog">Penyedia Katalog</div>
              </a>
            </li>
            <?php endif; ?>
            <?php if(has_permission('detail_penyedia')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/detail-penyedia')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/detail-penyedia') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-detail"></i>
                <div data-i18n="Detail Penyedia">Detail Penyedia</div>
              </a>
            </li>
            <?php if(session()->get('role') === 'admin'): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/scrape-perusahaan')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/scrape-perusahaan') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cloud-download"></i>
                <div data-i18n="Scrape Perusahaan">Scrape &amp; Referensi Penyedia</div>
              </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Summary Category -->
            <?php if(has_permission('report')): ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Summary</span>
            </li>
            <li class="menu-item <?= (current_url() == base_url('report')) ? 'active' : '' ?>">
              <a href="<?= base_url('report') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-spreadsheet"></i>
                <div data-i18n="Laporan Realisasi">Laporan Realisasi</div>
              </a>
            </li>
            <?php endif; ?>
            
            <!-- MONEV Category -->
            <?php if( (has_permission('monev') && !in_array($role, ['opd', 'satker'])) || has_permission('monev_summary') ): ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">MONEV</span>
            </li>
            <?php endif; ?>
            
            <?php if(has_permission('monev') && !in_array($role, ['opd', 'satker'])): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/monev-proyek')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/monev-proyek') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-list-ul"></i>
                <div data-i18n="Daftar Proyek">Daftar Proyek</div>
              </a>
            </li>
            <li class="menu-item <?= (current_url() == base_url('dashboard/monev') || str_contains(current_url(), 'monev/project')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/monev') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-briefcase"></i>
                <div data-i18n="Dashboard V2">Dashboard V2</div>
              </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('monev_summary')): ?>
            <li class="menu-item <?= (current_url() == base_url('dashboard/monev-summary')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/monev-summary') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div data-i18n="Summary Progres">Summary Progres</div>
              </a>
            </li>
            <?php endif; ?>

            <!-- Aplikasi Category -->
            <?php 
                $showAplikasi = has_permission('master_anggaran') ||
                                has_permission('user_management') || 
                                ($role == 'admin') || 
                                has_permission('chatbot_settings') || 
                                has_permission('chatbot_knowledge') || 
                                has_permission('api_settings') || 
                                has_permission('sync_data') || 
                                has_permission('master_menu') ||
                                has_permission('patch_app') ||
                                has_permission('user_logs') ||
                                in_array($role, ['admin', 'operator']);
                
                if($showAplikasi): 
            ?>
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Aplikasi</span>
            </li>
            <?php endif; ?>
            
            <?php if(has_permission('master_anggaran')): ?>
            <li class="menu-item <?= (current_url() == base_url('master-anggaran')) ? 'active' : '' ?>">
                <a href="<?= base_url('master-anggaran') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calculator"></i>
                    <div data-i18n="Master Anggaran">Master Anggaran</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('user_management')): ?>
            <li class="menu-item <?= (current_url() == base_url('user')) ? 'active' : '' ?>">
                <a href="<?= base_url('user') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div data-i18n="Manage User">Manage User</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if($role == 'admin'): ?>
            <li class="menu-item <?= (current_url() == base_url('user/rbac')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/rbac') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                    <div data-i18n="Manage RBAC">Manage RBAC</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('user_logs')): ?>
            <li class="menu-item <?= (current_url() == base_url('user/logs')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/logs') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-list-ol"></i>
                    <div data-i18n="Activity Logs">Activity Logs</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('chatbot_settings')): ?>
            <li class="menu-item <?= (current_url() == base_url('user/chatbot-settings')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/chatbot-settings') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bot"></i>
                    <div data-i18n="Chatbot Settings">Chatbot Settings</div>
                </a>
            </li>
            <?php endif; ?>
            <?php if(has_permission('chatbot_knowledge')): ?>
            <li class="menu-item <?= (current_url() == base_url('user/chatbot-knowledge')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/chatbot-knowledge') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-brain"></i>
                    <div data-i18n="Library Kepintaran AI">Library Kepintaran AI</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('api_settings')): ?>
            <li class="menu-item <?= (current_url() == base_url('user/api-settings')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/api-settings') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-transfer-alt"></i>
                    <div data-i18n="AGI Integrator">AGI Integrator</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('sync_data')): ?>
            <li class="menu-item <?= (current_url() == base_url('user/sync')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/sync') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-sync"></i>
                    <div data-i18n="Sinkronisasi Data">Sinkronisasi Data</div>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if(has_permission('master_menu')): ?>
            <li class="menu-item <?= (current_url() == base_url('development') || current_url() == base_url('development/charts')) ? 'active' : '' ?>">
                <a href="<?= base_url('development') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div data-i18n="Master Menu">Master Menu</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(has_permission('patch_app')): ?>
            <li class="menu-item <?= str_contains(current_url(), 'development/patch') ? 'active' : '' ?>">
                <a href="<?= base_url('development/patch') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-package"></i>
                    <div data-i18n="System Patcher">System Patcher</div>
                </a>
            </li>
            <?php endif; ?>

            <?php if(in_array($role, ['admin', 'operator'])): ?>
            <li class="menu-item">
                <a href="<?= base_url('dashboard/clear-cache') ?>" class="menu-link text-danger" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
                    <i class="menu-icon tf-icons bx bx-trash text-danger"></i>
                    <div data-i18n="Clear Cache">Clear Cache</div>
                </a>
            </li>
            <?php endif; ?>

            <?php 
                try {
                    $menuModel = new \App\Models\DynamicMenuModel();
                    $dynamicMenus = $menuModel->findAll() ?? [];
                } catch (\Throwable $e) {
                    $dynamicMenus = [];
                }
                
                // Hide dynamic menus for OPD role
                if(!empty($dynamicMenus) && session()->get('role') !== 'opd'):
            ?>
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Menu Dinamis</span>
            </li>
            <?php foreach($dynamicMenus as $menu): ?>
            <li class="menu-item <?= (current_url() == base_url('dynamic/'.$menu['slug'])) ? 'active' : '' ?>">
                <a href="<?= base_url('dynamic/'.$menu['slug']) ?>" class="menu-link">
                    <i class="menu-icon tf-icons <?= $menu['icon'] ?? 'bx bx-circle' ?>"></i>
                    <div><?= $menu['name'] ?></div>
                </a>
            </li>
            <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
            id="layout-navbar"
          >
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

                        <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                      <?php
                          $avatarUrl = session()->get('avatar') 
                              ? base_url('uploads/avatars/' . session()->get('avatar')) 
                              : 'https://ui-avatars.com/api/?name=' . urlencode(session()->get('username') ?? 'U') . '&background=random';
                      ?>
                      <img src="<?= $avatarUrl ?>" alt class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username') ?? 'U') ?>&background=random'" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="<?= $avatarUrl ?>" alt class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username') ?? 'U') ?>&background=random'" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block"><?= session()->get('nama_lengkap') ?></span>
                            <small class="text-muted"><?= strtoupper(session()->get('role')) ?></small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="<?= base_url('profile') ?>">
                        <i class="bx bx-user me-2"></i>
                        <span class="align-middle">My Profile</span>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                    <li>
                      <a class="dropdown-item" href="<?= base_url('auth/logout') ?>">
                        <i class="bx bx-power-off me-2"></i>
                        <span class="align-middle">Log Out</span>
                      </a>
                    </li>
                  </ul>
                </li>
                <!--/ User -->
              </ul>
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
              <?= $this->renderSection('content') ?>
            </div>
            <!-- / Content -->

            <footer class="content-footer footer bg-footer-theme border-top">
              <div class="container-xxl d-flex flex-wrap justify-content-between align-items-center py-2 flex-md-row flex-column gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1 mb-md-0">
                  <span class="text-muted">
                    © <script>document.write(new Date().getFullYear());</script>
                    <span class="fw-bold text-primary">AJIP</span> - Aplikasi Jendela Informasi Pengadaan
                  </span>
                  <span class="badge bg-label-primary font-monospace px-2 py-1" style="font-size: 0.75rem;">
                    <i class="bx bx-tag-alt me-1"></i>Versi <?= get_app_version() ?>
                  </span>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 text-muted small">
                  <?php $inaprocSync = get_inaproc_last_sync(); ?>
                  <div class="d-inline-flex align-items-center bg-white border rounded px-2 py-1 shadow-xs" title="Waktu terakhir database disinkronkan dengan data Inaproc">
                    <i class="bx bx-sync text-success me-1 fs-6"></i>
                    <span class="me-1">Sinkronisasi Inaproc:</span>
                    <strong class="text-dark font-monospace"><?= esc($inaprocSync['formatted']) ?></strong>
                  </div>
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

   

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="<?= base_url('assets') ?>/vendor/libs/jquery/jquery.js"></script>
    <script src="<?= base_url('assets') ?>/vendor/libs/popper/popper.js"></script>
    <script src="<?= base_url('assets') ?>/vendor/js/bootstrap.js"></script>
    <script src="<?= base_url('assets') ?>/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="<?= base_url('assets') ?>/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->

    <!-- Main JS -->
    <script src="<?= base_url('assets') ?>/js/main.js"></script>

    <!-- Page JS -->

    <!-- Place this tag in your head or just before your close body tag. -->
    <script async defer src="https://buttons.github.io/buttons.js"></script>

    <?= $this->renderSection('scripts') ?>

    <script>
      // Global SweetAlert Helpers
      window.showAlert = (title, text, icon = 'info') => {
        return Swal.fire({
          title: title,
          text: text,
          icon: icon,
          buttonsStyling: false,
          customClass: {
            confirmButton: 'btn btn-primary',
            popup: 'rounded-3 shadow-lg',
            title: 'fw-bold'
          }
        });
      };

      window.showConfirm = (text, confirmText = 'Yes, delete it!', title = 'Are you sure?') => {
        return Swal.fire({
          title: title,
          text: text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: confirmText,
          cancelButtonText: 'Cancel',
          buttonsStyling: false,
          customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-label-secondary',
            popup: 'rounded-3 shadow-lg',
            title: 'fw-bold text-danger'
          }
        });
      };

      // Auto-show FlashData Alerts
      <?php if(session()->getFlashdata('success')): ?>
        showAlert('Success!', '<?= session()->getFlashdata('success') ?>', 'success');
      <?php endif; ?>
      <?php if(session()->getFlashdata('error')): ?>
        showAlert('Error!', '<?= session()->getFlashdata('error') ?>', 'error');
      <?php endif; ?>
      <?php if(session()->getFlashdata('errors')): ?>
        showAlert('Validation Error', '<?= implode('\n', array_map('addslashes', session()->getFlashdata('errors'))) ?>', 'error');
      <?php endif; ?>

      // Global AJAX CSRF & Error Handler
      $.ajaxSetup({
        headers: {
            '<?= config('Security')->headerName ?>': '<?= csrf_hash() ?>'
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if (jqXHR.status === 403) {
                Swal.fire({
                    title: 'Sesi Berakhir',
                    text: 'Sesi Anda telah berakhir karena terlalu lama tidak ada aktivitas. Halaman akan dimuat ulang.',
                    icon: 'warning',
                    confirmButtonText: 'Muat Ulang',
                    allowOutsideClick: false,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        popup: 'rounded-3 shadow-lg',
                        title: 'fw-bold'
                    }
                }).then(() => {
                    window.location.reload();
                });
            }
        }
      });

      // Handle custom confirmation attributes
      document.addEventListener('click', function(e) {
        const confirmBtn = e.target.closest('[data-confirm]');
        if (confirmBtn) {
          e.preventDefault();
          const message = confirmBtn.getAttribute('data-confirm') || 'Are you sure?';
          const href = confirmBtn.getAttribute('href');
          
          showConfirm(message).then((result) => {
            if (result.isConfirmed) {
              window.location.href = href;
            }
          });
        }

        // Loading indicator for RUP OPD menu
        const rupOpdLink = e.target.closest('a[href$="rup-opd"]');
        if (rupOpdLink && !rupOpdLink.classList.contains('active')) {
          Swal.fire({
            title: 'Menganalisis RUP...',
            html: 'Sistem sedang menarik data terbaru dari SIRUP dan melakukan pemetaan program. Mohon tunggu sebentar.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
        }
      });
    </script>
    <?= $this->include('components/chatbot') ?>
  </body>
</html>





