<?php

namespace App\Controllers;

use App\Models\UserModel;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuth extends BaseController
{
    public function index()
    {
        $tempUserId = session()->get('temp_auth_user_id');
        if (!$tempUserId) {
            return redirect()->to('/auth');
        }

        $userModel = new UserModel();
        $user = $userModel->find($tempUserId);

        if (!$user) {
            return redirect()->to('/auth')->with('error', 'Sesi tidak valid.');
        }

        if (empty($user['google2fa_secret'])) {
            return redirect()->to('/auth/2fa/setup');
        }

        return redirect()->to('/auth/2fa/challenge');
    }

    public function setup()
    {
        $tempUserId = session()->get('temp_auth_user_id');
        if (!$tempUserId) {
            return redirect()->to('/auth');
        }

        $userModel = new UserModel();
        $user = $userModel->find($tempUserId);

        if (!empty($user['google2fa_secret'])) {
            return redirect()->to('/auth/2fa/challenge');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        session()->setTempdata('2fa_secret_setup', $secret, 1800); // 30 minutes

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'AJIP Dashboard',
            $user['username'],
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeImage = $writer->writeString($qrCodeUrl);

        return view('auth/2fa_setup', [
            'secret' => $secret,
            'qrCodeImage' => $qrCodeImage
        ]);
    }

    public function setupSave()
    {
        $tempUserId = session()->get('temp_auth_user_id');
        if (!$tempUserId) {
            return redirect()->to('/auth');
        }

        $secret = session()->getTempdata('2fa_secret_setup');
        $otp = $this->request->getPost('otp');

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $otp);

        if ($valid) {
            $userModel = new UserModel();
            $userModel->update($tempUserId, ['google2fa_secret' => $secret]);
            
            $user = $userModel->find($tempUserId);
            $this->finalizeLogin($user);
            
            $roleModel = new \App\Models\RolePermissionModel();
            $redirectUrl = $roleModel->getFirstAllowedUrl($user['role']) ?? 'dashboard';
            return redirect()->to('/' . $redirectUrl);
        }

        return redirect()->back()->with('error', 'Kode OTP tidak valid.');
    }

    public function challenge()
    {
        $tempUserId = session()->get('temp_auth_user_id');
        if (!$tempUserId) {
            return redirect()->to('/auth');
        }

        return view('auth/2fa_challenge');
    }

    public function verify()
    {
        $tempUserId = session()->get('temp_auth_user_id');
        if (!$tempUserId) {
            return redirect()->to('/auth');
        }

        $userModel = new UserModel();
        $user = $userModel->find($tempUserId);

        $otp = $this->request->getPost('otp');

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user['google2fa_secret'], $otp);

        if ($valid) {
            $this->finalizeLogin($user);
            
            $roleModel = new \App\Models\RolePermissionModel();
            $redirectUrl = $roleModel->getFirstAllowedUrl($user['role']) ?? 'dashboard';
            return redirect()->to('/' . $redirectUrl);
        }

        return redirect()->back()->with('error', 'Kode OTP salah.');
    }

    private function finalizeLogin($user)
    {
        // Regenerate session ID untuk mencegah session fixation
        session()->regenerate(true);

        $authController = new Auth();
        $authController->setUserSession($user);
        
        $this->logActivity('User logged in successfully (2FA)', $user['username']);
        
        session()->remove('temp_auth_user_id');
        session()->removeTempdata('2fa_secret_setup');
    }
}
