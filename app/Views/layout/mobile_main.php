<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?? 'AJIP Mobile' ?></title>
    
    <!-- PWA & Mobile Meta Tags -->
    <meta name="theme-color" content="#0ea5e9" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="InfoAJIP" />
    <link rel="manifest" href="<?= base_url('manifest.json') ?>" />
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/bot-avatar.png') ?>" />

    <!-- CSRF Token -->
    <meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/fonts/boxicons.css" />

    <!-- Core CSS (Sneat) -->
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/css/core.css" />
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendor/css/theme-default.css" />
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1f2937;
            padding-bottom: 90px; /* Space for bottom nav */
        }
        
        /* Mobile Container - Responsive & Adaptive */
        .mobile-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
            background-color: #ffffff;
            overflow-x: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        @media (min-width: 768px) {
            .mobile-container {
                max-width: 540px;
            }
        }

        /* Bottom Navigation Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 100%;
            background: #ffffff;
            display: flex;
            justify-content: space-around;
            align-items: center;
            height: 70px;
            padding-bottom: env(safe-area-inset-bottom, 0);
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
            border-top: 1px solid #f1f5f9;
        }
        @media (min-width: 768px) {
            .bottom-nav {
                max-width: 540px;
            }
        }

        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            flex: 1;
            height: 100%;
            transition: color 0.2s;
        }

        .bottom-nav-item i {
            font-size: 24px;
            margin-bottom: 3px;
        }

        .bottom-nav-item.active {
            color: #0ea5e9;
        }

        .bottom-nav-item:hover {
            color: #0ea5e9;
        }
        
        /* Floating Avatar Button in Center */
        .bottom-nav-item.center-btn {
            flex: 0 0 70px;
            position: relative;
            height: 100%;
        }
        
        .floating-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #ffffff;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.05);
            position: absolute;
            top: -25px; /* Float exactly half above the bar */
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            justify-content: center;
            align-items: center;
            /* Create a fake transparent cutout border effect against the page body */
            border: 5px solid #f8fafc; 
        }
        
        .ai-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #ffffff;
            border: 2px solid #3a7bd5; /* The blue AJIP ring */
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .ai-avatar-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Top App Bar */
        .top-app-bar {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            background: #ffffff;
            color: #1e293b;
            padding-top: max(12px, env(safe-area-inset-top));
            border-bottom: 1px solid #f1f5f9;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .top-app-bar a.back-btn {
            color: #0ea5e9;
            font-size: 24px;
            text-decoration: none;
            margin-right: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #f0f9ff;
        }
        .top-app-bar .page-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: #0f172a;
        }
        
        .white-bg-wrapper {
            background: #f8fafc;
            min-height: calc(100vh - 60px);
            padding: 12px 10px;
        }

        /* Universal Table & UI Adjustments for Mobile */
        .mobile-container .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin: 5px 0 15px 0;
            border: 1px solid #f1f5f9;
            padding: 0;
            overflow: hidden;
            background: #ffffff;
        }
        .mobile-container .card-body {
            padding: 15px;
        }
        .mobile-container .container-p-y {
            padding-top: 0.5rem !important;
            padding-bottom: 1rem !important;
        }
        .mobile-container .container-xxl {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        .mobile-container .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .mobile-container .table-responsive table {
                font-size: 12px !important;
            }
            .mobile-container .table-responsive table th,
            .mobile-container .table-responsive table td {
                padding: 8px 10px !important;
            }
            .mobile-container .table-responsive::-webkit-scrollbar {
                height: 5px;
            }
            .mobile-container .table-responsive::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 4px;
            }
            /* DataTables mobile layout */
            .mobile-container .dataTables_wrapper {
                font-size: 12px;
            }
            .mobile-container .dataTables_wrapper .dataTables_length,
            .mobile-container .dataTables_wrapper .dataTables_filter {
                text-align: left !important;
                float: none !important;
                margin-bottom: 0.5rem;
            }
            .mobile-container .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin-left: 0 !important;
                margin-top: 4px;
            }
            .mobile-container .dataTables_wrapper .dataTables_paginate {
                text-align: center !important;
                float: none !important;
                margin-top: 0.75rem;
            }
            .mobile-container .dataTables_wrapper .dataTables_paginate .pagination {
                justify-content: center !important;
                flex-wrap: wrap;
                gap: 2px;
            }
            .mobile-container .dt-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 0.75rem;
                width: 100%;
            }
            .mobile-container .dt-buttons .btn {
                flex: 1 1 auto;
                font-size: 11px !important;
                padding: 6px 10px !important;
            }
            /* Nav pills touch friendly */
            .mobile-container .nav-pills {
                width: 100%;
                display: flex;
            }
            .mobile-container .nav-pills .nav-item {
                flex: 1;
            }
            .mobile-container .nav-pills .nav-link {
                width: 100%;
                text-align: center;
                padding: 8px 12px;
                font-size: 12px;
            }
        }

        /* Shimmer Loading Animation */
        .shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Hide Default Chatbot Button in Mobile */
        #openChat {
            display: none !important;
        }
        /* Adjust chatbot window for mobile */
        .chatbot-window {
            position: fixed !important;
            width: 100vw !important;
            /* Use 100dvh to fix the sinking issue on mobile browsers */
            height: 100vh !important;
            height: 100dvh !important;
            height: -webkit-fill-available !important;
            bottom: 0 !important;
            right: 0 !important;
            left: 0 !important;
            top: 0 !important;
            border-radius: 0 !important;
            z-index: 100000 !important;
        }
        
        .chatbot-window .chatbot-input-area {
            border-radius: 0 !important;
            /* Add safe area inset to prevent hiding behind iPhone home bar */
            padding-bottom: max(15px, env(safe-area-inset-bottom)) !important;
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>

    <div class="mobile-container">
        <?php 
            $currentPath = uri_string();
            $isHome = ($currentPath === '' || $currentPath === 'dashboard' || $currentPath === 'dashboard/index');
            
            if (!$isHome): 
        ?>
        <!-- Top App Bar for Subpages -->
        <div class="top-app-bar">
            <a href="<?= base_url('dashboard') ?>" onclick="if(window.history.length > 1) { window.history.back(); return false; }" class="back-btn" title="Kembali">
                <i class='bx bx-arrow-back'></i>
            </a>
            <div class="d-flex flex-column">
                <h1 class="page-title"><?= esc($title ?? 'AJIP') ?></h1>
            </div>
        </div>
        
        <!-- Background Wrapper for Subpages content -->
        <div class="white-bg-wrapper">
            <?= $this->renderSection('content') ?>
        </div>
        
        <?php else: ?>
        
        <!-- Main Content (Home Page uses its own structure) -->
        <?= $this->renderSection('content') ?>
        
        <?php endif; ?>

        <!-- Mobile Footer Info -->
        <div class="text-center py-3 px-2 text-muted" style="font-size: 0.8rem; padding-bottom: 90px !important;">
            <div class="fw-semibold text-dark mb-1">
                AJIP <span class="badge bg-label-primary ms-1 font-monospace">Versi <?= get_app_version() ?></span>
            </div>
            <div>
                <?php $inaprocSync = get_inaproc_last_sync(); ?>
                <i class="bx bx-sync text-success me-1"></i>Sinkronisasi Inaproc: 
                <span class="fw-bold font-monospace text-dark"><?= esc($inaprocSync['formatted']) ?></span>
            </div>
        </div>

    </div>

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav">
        <a href="<?= base_url('dashboard') ?>" class="bottom-nav-item <?= $isHome ? 'active' : '' ?>">
            <i class='bx bx-category-alt'></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= base_url('dashboard/monev-proyek') ?>" class="bottom-nav-item <?= current_url() == base_url('dashboard/monev-proyek') ? 'active' : '' ?>">
            <i class='bx bx-list-ul'></i>
            <span>Proyek</span>
        </a>
        <a href="<?= base_url('dashboard/monev') ?>" class="bottom-nav-item <?= strpos($currentPath, 'dashboard/monev') !== false && current_url() != base_url('dashboard/monev-proyek') ? 'active' : '' ?>">
            <i class='bx bx-briefcase'></i>
            <span>Monev V2</span>
        </a>
        
        <a href="javascript:void(0);" onclick="const cw = document.getElementById('chatbotWindow'); if(cw) { cw.classList.toggle('active'); if(cw.classList.contains('active')) { setTimeout(()=>document.getElementById('chatInput').focus(),100); } }" class="bottom-nav-item center-btn">
            <div class="floating-avatar">
                <div class="ai-avatar-inner">
                    <img src="<?= base_url('assets/img/bot-avatar.png') ?>" alt="AJIP">
                </div>
            </div>
        </a>
        
        <?php
            $isLainnya = (strpos($currentPath, 'layanan') !== false || current_url(true)->getSegment(2) == 'layanan' || (!$isHome && current_url() != base_url('dashboard/monev-proyek') && strpos($currentPath, 'dashboard/monev') === false));
        ?>
        <a href="<?= base_url('dashboard/layanan') ?>" class="bottom-nav-item <?= $isLainnya ? 'active' : '' ?>">
            <i class='bx bx-grid-alt'></i>
            <span>Lainnya</span>
        </a>
        <a href="<?= base_url('auth/logout') ?>" class="bottom-nav-item text-danger">
            <i class='bx bx-log-out'></i>
            <span>Keluar</span>
        </a>
    </nav>

    <!-- Core JS -->
    <script src="<?= base_url('assets') ?>/vendor/libs/jquery/jquery.js"></script>
    <script src="<?= base_url('assets') ?>/vendor/libs/popper/popper.js"></script>
    <script src="<?= base_url('assets') ?>/vendor/js/bootstrap.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      $.ajaxSetup({
        headers: {
            '<?= config('Security')->headerName ?>': '<?= csrf_hash() ?>'
        }
      });
    </script>
    <?= $this->renderSection('scripts') ?>
    <?= $this->include('components/chatbot') ?>
</body>
</html>
