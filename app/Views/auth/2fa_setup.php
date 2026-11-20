<?php $base = rtrim(base_url(), '/') . '/assets/'; ?><!DOCTYPE html>
<html lang="id" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="<?= $base ?>" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Setup 2FA - AJIP Dashboard</title>

    <link rel="icon" type="image/x-icon" href="<?= base_url("favicon.ico") ?>" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?= $base ?>vendor/fonts/boxicons.css" />
    <!-- Custom Premium Login CSS -->
    <link rel="stylesheet" href="<?= $base ?>css/login-premium.css" />
    
    <style>
        .qr-container {
            background: white;
            padding: 15px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .qr-container svg {
            width: 100%;
            height: auto;
            max-width: 200px;
        }
    </style>
</head>

<body>
    <!-- Background Grid -->
    <div class="bg-grid"></div>

    <!-- Main Login Area -->
    <div class="main-wrapper">
        <div class="auth-card" style="text-align: center;">
            
            <div class="card-title">Setup 2FA</div>
            <div class="card-divider"></div>
            <div class="card-desc">Pindai QR Code berikut menggunakan aplikasi Google Authenticator.</div>

            <?php if(session()->getFlashdata('error')): ?>
            <div class="alert-error">
                <i class='bx bxs-error-circle'></i>
                <?= session()->getFlashdata('error') ?>
            </div>
            <?php endif; ?>
            
            <div class="qr-container">
                <?= $qrCodeImage ?>
            </div>
            
            <p style="color: #a0a0b0; font-size: 0.9rem; margin-bottom: 20px;">
                Secret Key: <strong><?= $secret ?></strong>
            </p>

            <form action="<?= base_url('auth/2fa/setup') ?>" method="POST" style="text-align: left;">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label class="form-label" for="otp">KODE OTP</label>
                    <div class="input-group-custom">
                        <input type="text" class="input-field" id="otp" name="otp" placeholder="123456" autofocus required autocomplete="off" maxlength="6" />
                    </div>
                </div>

                <button type="submit" class="btn-submit">Verifikasi & Simpan</button>
            </form>
            
        </div>
    </div>

    <script src="<?= $base ?>js/login-premium.js"></script>
</body>
</html>
