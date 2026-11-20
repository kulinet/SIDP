<?php $base = rtrim(base_url(), '/') . '/assets/'; ?><!DOCTYPE html>
<html lang="id" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="<?= $base ?>" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login - AJIP Dashboard</title>

    <link rel="icon" type="image/x-icon" href="<?= base_url("favicon.ico") ?>" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?= $base ?>vendor/fonts/boxicons.css" />
    <!-- Custom Premium Login CSS -->
    <link rel="stylesheet" href="<?= $base ?>css/login-premium.css" />
</head>

<body>
    <!-- Background Grid -->
    <div class="bg-grid"></div>

    <!-- Main Login Area -->
    <div class="main-wrapper">
        <div class="auth-card">
            
            <div class="card-title"> A J I P</div>
            <div class="card-subtitle">Aplikasi Jendela Informasi Pengadaan</div>
            <div class="card-divider"></div>
            <div class="card-desc">Silakan masuk ke akun Anda.</div>

            <?php if(session()->getFlashdata('error')): ?>
            <div class="alert-error">
                <i class='bx bxs-error-circle'></i>
                <?= session()->getFlashdata('error') ?>
            </div>
            <?php endif; ?>

            <form action="<?= base_url('auth/login') ?>" method="POST">
                <?= csrf_field() ?>
                
                <!-- Username Input -->
                <div class="form-group">
                    <label class="form-label" for="username">USERNAME</label>
                    <div class="input-group-custom">
                        <input type="text" class="input-field" id="username" name="username" placeholder="username" autofocus required autocomplete="off" />
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label class="form-label" for="password">PASSWORD</label>
                    <div class="input-group-custom">
                        <input type="password" class="input-field" id="password" name="password" placeholder="••••••••" required />
                        <i class='bx bx-hide password-toggle' id="togglePassword"></i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">Sign in</button>
            </form>
            
        </div>
    </div>

    <script src="<?= $base ?>js/login-premium.js"></script>
</body>
</html>
