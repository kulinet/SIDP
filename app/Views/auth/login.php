<?php $base = rtrim(base_url(), '/') . '/assets/'; ?><!DOCTYPE html>
<html lang="id" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="<?= $base ?>" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - SIDP Dashboard</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= base_url("favicon.ico") ?>" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?= $base ?>vendor/fonts/boxicons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= $base ?>vendor/css/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="<?= $base ?>vendor/css/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="<?= $base ?>css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?= $base ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    
    <!-- Page Specific CSS -->
    <link rel="stylesheet" href="<?= $base ?>vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="<?= $base ?>vendor/js/helpers.js"></script>
    <!-- Config -->
    <script src="<?= $base ?>js/config.js"></script>
</head>

<body>
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
            <div class="card">
                <div class="card-body">
                    <div class="app-brand justify-content-center mb-4 mt-2">
                        <a href="#" class="app-brand-link gap-2">
                            <span class="app-brand-text demo text-body fw-bolder text-uppercase">SIDP Dashboard</span>
                        </a>
                    </div>
                    <h4 class="mb-2">Selamat Datang! 👋</h4>
                    <p class="mb-4">Silakan masuk ke akun Anda.</p>

                    <?php if(session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger px-3 py-2 border-0 fw-bold">
                        <?= session()->getFlashdata('error') ?>
                    </div>
                    <?php endif; ?>

                    <form id="formAuthentication" class="mb-3" action="<?= base_url('auth/login') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username Anda" autofocus required />
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Password</label>
                            </div>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" required />
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Core JS -->
<script src="<?= $base ?>vendor/libs/jquery/jquery.js"></script>
<script src="<?= $base ?>vendor/libs/popper/popper.js"></script>
<script src="<?= $base ?>vendor/js/bootstrap.js"></script>
<script src="<?= $base ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="<?= $base ?>vendor/js/menu.js"></script>

<!-- Main JS -->
<script src="<?= $base ?>js/main.js"></script>
</body>
</html>

