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
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
    />

    <title>Dashboard - Analytics | Sneat - Bootstrap 5 HTML Admin Template - Pro</title>

    <meta name="description" content="" />

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
          <div class="app-brand demo">
            <a href="<?= base_url('dashboard') ?>" class="app-brand-link">
              <span class="app-brand-text demo menu-text fw-bolder ms-2 text-uppercase">SIDP</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
              <i class="bx bx-chevron-left bx-sm align-middle"></i>
            </a>
          </div>

          <div class="menu-inner-shadow"></div>

          <ul class="menu-inner py-1">
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Data RUP</span>
            </li>
            <li class="menu-item <?= (current_url() == base_url('dashboard')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
              </a>
            </li>
            <li class="menu-item <?= (current_url() == base_url('dashboard/comparisons')) ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/comparisons') ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div data-i18n="Perbandingan Tahunan">Perbandingan Tahunan</div>
              </a>
            </li>
            
            <li class="menu-item <?= (current_url() == base_url('dashboard/penyedia')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/penyedia') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-table"></i>
                    <div data-i18n="Penyedia">Penyedia</div>
                </a>
            </li>
            
            <li class="menu-item <?= (current_url() == base_url('dashboard/swakelola')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/swakelola') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-table"></i>
                    <div data-i18n="Swakelola">Swakelola</div>
                </a>
            </li>
            
            <li class="menu-item <?= (current_url() == base_url('dashboard/rup-opd')) ? 'active' : '' ?>">
                <a href="<?= base_url('dashboard/rup-opd') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-buildings"></i>
                    <div data-i18n="RUP OPD">RUP OPD</div>
                </a>
            </li>

            <?php 
                $role = session()->get('role');
                if(in_array($role, ['admin', 'operator'])): 
            ?>
        <!-- Data Pengadaan Category -->
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">Data Pengadaan</span>
        </li>
        <!-- Dashboard LPSE -->
        <li class="menu-item <?= str_contains(current_url(), 'dashboard-lpse') ? 'active' : '' ?>">
          <a href="<?= base_url('dashboard/dashboard-lpse') ?>" class="menu-link">
            <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
            <div data-i18n="Dashboard LPSE">Dashboard LPSE</div>
          </a>
        </li>
        <!-- Data LPSE -->
        <li class="menu-item <?= str_contains(current_url(), '/lpse/') ? 'active open' : '' ?>">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-collection"></i>
            <div data-i18n="Data LPSE">Data LPSE <?= date('Y') ?></div>
          </a>
          <ul class="menu-sub">
            <li class="menu-item <?= str_contains(current_url(), '/lpse/tender') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/lpse/tender') ?>" class="menu-link">
                <div data-i18n="Tender">Tender</div>
              </a>
            </li>
            <li class="menu-item <?= str_contains(current_url(), '/lpse/nontender') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/lpse/nontender') ?>" class="menu-link">
                <div data-i18n="Non Tender">Non Tender</div>
              </a>
            </li>
            <li class="menu-item <?= str_contains(current_url(), '/lpse/pencatatan-nontender') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/lpse/pencatatan-nontender') ?>" class="menu-link">
                <div data-i18n="Pencatatan Non Tender">Pencatatan Non Tender</div>
              </a>
            </li>
            <li class="menu-item <?= str_contains(current_url(), '/lpse/pencatatan-swakelola') ? 'active' : '' ?>">
              <a href="<?= base_url('dashboard/lpse/pencatatan-swakelola') ?>" class="menu-link">
                <div data-i18n="Pencatatan Swakelola">Pencatatan Swakelola</div>
              </a>
            </li>
          </ul>
        </li>

        

        <!-- Data E-Purchasing -->
        <li class="menu-item <?= str_contains(current_url(), 'epurchasing') ? 'active' : '' ?>">
          <a href="<?= base_url('dashboard/epurchasing') ?>" class="menu-link">
            <i class="menu-icon tf-icons bx bx-cart"></i>
            <div data-i18n="Dashboard E-Purchasing">Dashboard E-Purchasing</div>
          </a>
        </li>

        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">Aplikasi</span>
        </li>
            <?php if($role == 'admin'): ?>
            <li class="menu-item <?= (current_url() == base_url('user')) ? 'active' : '' ?>">
                <a href="<?= base_url('user') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div data-i18n="Manage User">Manage User</div>
                </a>
            </li>
            <li class="menu-item <?= (current_url() == base_url('user/chatbot-settings')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/chatbot-settings') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bot"></i>
                    <div data-i18n="Chatbot Settings">Chatbot Settings</div>
                </a>
            </li>
            <li class="menu-item <?= (current_url() == base_url('user/api-settings')) ? 'active' : '' ?>">
                <a href="<?= base_url('user/api-settings') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-key"></i>
                    <div data-i18n="API Security">API Security</div>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if(in_array($role, ['admin', 'operator'])): ?>
            <li class="menu-item <?= (current_url() == base_url('development') || current_url() == base_url('development/charts')) ? 'active' : '' ?>">
                <a href="<?= base_url('development') ?>" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div data-i18n="Master Menu">Master Menu</div>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= base_url('dashboard/clear-cache') ?>" class="menu-link text-danger" data-confirm="Apakah Anda yakin ingin menghapus seluruh cache data? Hal ini akan memaksa sistem menarik ulang data terbaru dari API.">
                    <i class="menu-icon tf-icons bx bx-trash text-danger"></i>
                    <div data-i18n="Clear Cache">Clear Cache</div>
                </a>
            </li>
            <?php endif; ?>
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
                      <img src="<?= base_url('assets') ?>/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username')) ?>&background=random'" />
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="<?= base_url('assets') ?>/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username')) ?>&background=random'" />
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

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  ©
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  
                  
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





