<?php

if (!function_exists('encode_id')) {
    function encode_id($id) {
        $salt = 'AJIP_SECURE_';
        $str = base64_encode($salt . $id);
        return str_replace(['+', '/', '='], ['-', '_', ''], $str);
    }
}

if (!function_exists('decode_id')) {
    function decode_id($str) {
        $str = str_replace(['-', '_'], ['+', '/'], $str);
        // Pad to proper base64 length
        $pad = strlen($str) % 4;
        if ($pad > 0) {
            $str .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode($str);
        $salt = 'AJIP_SECURE_';
        if ($decoded && strpos($decoded, $salt) === 0) {
            return substr($decoded, strlen($salt));
        }
        return false;
    }
}
if (!function_exists('format_npwp')) {
    /**
     * Format NPWP 15 digit (XX.XXX.XXX.X-YYY.ZZZ) atau 16 digit (PMK 112/PMK.03/2022)
     */
    function format_npwp($npwp) {
        $raw = trim((string)$npwp);
        $clean = preg_replace('/[^0-9]/', '', $raw);
        
        // NPWP Badan 16 digit memiliki awalan '0' + 15 digit NPWP lama
        if (strlen($clean) === 16 && str_starts_with($clean, '0')) {
            $clean = substr($clean, 1);
        }

        if (strlen($clean) === 15) {
            return substr($clean, 0, 2) . '.' . substr($clean, 2, 3) . '.' . substr($clean, 5, 3) . '.' . substr($clean, 8, 1) . '-' . substr($clean, 9, 3) . '.' . substr($clean, 12, 3);
        } elseif (strlen($clean) === 16) {
            // NIK 16 digit (Orang Pribadi)
            return substr($clean, 0, 6) . '.' . substr($clean, 6, 6) . '.' . substr($clean, 12, 4);
        }
        return !empty($raw) ? $raw : '-';
    }
}

if (!function_exists('extract_kpp_from_npwp')) {
    /**
     * Ekstraksi informasi KPP Pratama dari NPWP 15 atau 16 digit
     */
    function extract_kpp_from_npwp($npwp, $fallbackCode = '') {
        $raw = trim((string)$npwp);
        $clean = preg_replace('/[^0-9]/', '', $raw);
        $code = null;

        if (preg_match('/-([0-9]{3})\./', $raw, $m)) {
            $code = $m[1];
        } elseif (strlen($clean) >= 15 && !str_starts_with($clean, '100000000')) {
            $c15 = substr($clean, -6, 3);
            $mid = substr($clean, 9, 3);
            if (in_array($c15, ['911', '912', '913', '914', '915'])) {
                $code = $c15;
            } elseif (in_array($mid, ['911', '912', '913', '914', '915'])) {
                $code = $mid;
            } else {
                $code = $c15 ?: $mid;
            }
        }

        if ((!$code || $code === '000' || strlen($code) !== 3) && !empty($fallbackCode) && strlen($fallbackCode) === 3 && $fallbackCode !== '-') {
            $code = $fallbackCode;
        }

        if (!$code || $code === '000' || strlen($code) !== 3) {
            return [
                'code' => '-',
                'name' => '-',
                'short' => '-',
                'wilayah' => '-',
                'badge' => '<span class="text-muted small">-</span>',
                'is_lobar' => false,
                'is_ntb' => false
            ];
        }

        $kppDefinitions = [
            // Kanwil DJP Nusa Tenggara (NTB)
            '914' => [
                'name' => 'KPP Pratama Mataram Timur (914)',
                'short' => 'Mataram Timur',
                'wilayah' => 'Kab. Lombok Barat & Kab. Lombok Utara',
                'is_lobar' => true,
                'is_ntb' => true,
                'badge_color' => 'bg-label-success'
            ],
            '911' => [
                'name' => 'KPP Pratama Mataram Barat (911)',
                'short' => 'Mataram Barat',
                'wilayah' => 'Kota Mataram',
                'is_lobar' => false,
                'is_ntb' => true,
                'badge_color' => 'bg-label-primary'
            ],
            '915' => [
                'name' => 'KPP Pratama Praya (915)',
                'short' => 'Praya',
                'wilayah' => 'Kab. Lombok Tengah & Kab. Lombok Timur',
                'is_lobar' => false,
                'is_ntb' => true,
                'badge_color' => 'bg-label-info'
            ],
            '912' => [
                'name' => 'KPP Pratama Raba Bima (912)',
                'short' => 'Raba Bima',
                'wilayah' => 'Kota Bima, Kab. Bima & Kab. Dompu',
                'is_lobar' => false,
                'is_ntb' => true,
                'badge_color' => 'bg-label-info'
            ],
            '913' => [
                'name' => 'KPP Pratama Sumbawa Besar (913)',
                'short' => 'Sumbawa Besar',
                'wilayah' => 'Kab. Sumbawa & Kab. Sumbawa Barat',
                'is_lobar' => false,
                'is_ntb' => true,
                'badge_color' => 'bg-label-info'
            ],
            '910' => [
                'name' => 'KPP Madya Mataram (910)',
                'short' => 'Madya Mataram',
                'wilayah' => 'Wajib Pajak Besar NTB',
                'is_lobar' => false,
                'is_ntb' => true,
                'badge_color' => 'bg-label-warning'
            ],

            // Kanwil DJP Bali
            '901' => ['name' => 'KPP Pratama Denpasar Barat (901)', 'short' => 'Denpasar Barat', 'wilayah' => 'Kota Denpasar (Bali)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '902' => ['name' => 'KPP Pratama Denpasar Timur (902)', 'short' => 'Denpasar Timur', 'wilayah' => 'Kota Denpasar (Bali)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '903' => ['name' => 'KPP Pratama Badung Selatan (903)', 'short' => 'Badung Selatan', 'wilayah' => 'Kab. Badung (Bali)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '904' => ['name' => 'KPP Pratama Badung Utara (904)', 'short' => 'Badung Utara', 'wilayah' => 'Kab. Badung (Bali)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '905' => ['name' => 'KPP Pratama Gianyar (905)', 'short' => 'Gianyar', 'wilayah' => 'Kab. Gianyar, Bangli, Klungkung, Karangasem', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '906' => ['name' => 'KPP Pratama Tabanan (906)', 'short' => 'Tabanan', 'wilayah' => 'Kab. Tabanan & Kab. Jembrana', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '907' => ['name' => 'KPP Pratama Singaraja (907)', 'short' => 'Singaraja', 'wilayah' => 'Kab. Buleleng (Bali)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '909' => ['name' => 'KPP Madya Denpasar (909)', 'short' => 'Madya Denpasar', 'wilayah' => 'Provinsi Bali', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],

            // Kanwil DJP Nusa Tenggara (NTT)
            '908' => ['name' => 'KPP Pratama Kupang (908)', 'short' => 'Kupang', 'wilayah' => 'Kota Kupang & Kab. Kupang (NTT)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '921' => ['name' => 'KPP Pratama Ende (921)', 'short' => 'Ende', 'wilayah' => 'Kab. Ende, Ngada, Nagekeo (NTT)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '922' => ['name' => 'KPP Pratama Ruteng (922)', 'short' => 'Ruteng', 'wilayah' => 'Kab. Manggarai Barat, Manggarai, Manggarai Timur', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '923' => ['name' => 'KPP Pratama Atambua (923)', 'short' => 'Atambua', 'wilayah' => 'Kab. Belu, TTU, Malaka (NTT)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '924' => ['name' => 'KPP Pratama Maumere (924)', 'short' => 'Maumere', 'wilayah' => 'Kab. Sikka, Flores Timur, Lembata', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '925' => ['name' => 'KPP Pratama Waingapu (925)', 'short' => 'Waingapu', 'wilayah' => 'Pulau Sumba (NTT)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],

            // Jawa Timur
            '601' => ['name' => 'KPP Pratama Surabaya Genteng (601)', 'short' => 'Surabaya Genteng', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '602' => ['name' => 'KPP Pratama Surabaya Wonocolo (602)', 'short' => 'Surabaya Wonocolo', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '603' => ['name' => 'KPP Pratama Surabaya Rungkut (603)', 'short' => 'Surabaya Rungkut', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '604' => ['name' => 'KPP Pratama Surabaya Gubeng (604)', 'short' => 'Surabaya Gubeng', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '605' => ['name' => 'KPP Pratama Surabaya Sawahan (605)', 'short' => 'Surabaya Sawahan', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '606' => ['name' => 'KPP Pratama Surabaya Sukomanunggal (606)', 'short' => 'Surabaya Sukomanunggal', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '607' => ['name' => 'KPP Pratama Surabaya Krembangan (607)', 'short' => 'Surabaya Krembangan', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '608' => ['name' => 'KPP Pratama Surabaya Pabean Cantikan (608)', 'short' => 'Surabaya Pabean', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '609' => ['name' => 'KPP Pratama Surabaya Tegalsari (609)', 'short' => 'Surabaya Tegalsari', 'wilayah' => 'Kota Surabaya (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '641' => ['name' => 'KPP Pratama Malang Utara (641)', 'short' => 'Malang Utara', 'wilayah' => 'Kota & Kab. Malang (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '642' => ['name' => 'KPP Pratama Malang Selatan (642)', 'short' => 'Malang Selatan', 'wilayah' => 'Kota & Kab. Malang (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '643' => ['name' => 'KPP Pratama Sidoarjo Barat (643)', 'short' => 'Sidoarjo Barat', 'wilayah' => 'Kab. Sidoarjo (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '644' => ['name' => 'KPP Pratama Sidoarjo Selatan (644)', 'short' => 'Sidoarjo Selatan', 'wilayah' => 'Kab. Sidoarjo (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '645' => ['name' => 'KPP Pratama Sidoarjo Utara (645)', 'short' => 'Sidoarjo Utara', 'wilayah' => 'Kab. Sidoarjo (Jatim)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],

            // Jawa Tengah & DIY
            '501' => ['name' => 'KPP Pratama Semarang Barat (501)', 'short' => 'Semarang Barat', 'wilayah' => 'Kota Semarang (Jateng)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '502' => ['name' => 'KPP Pratama Semarang Timur (502)', 'short' => 'Semarang Timur', 'wilayah' => 'Kota Semarang (Jateng)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '503' => ['name' => 'KPP Pratama Semarang Tengah (503)', 'short' => 'Semarang Tengah', 'wilayah' => 'Kota Semarang (Jateng)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '504' => ['name' => 'KPP Pratama Semarang Selatan (504)', 'short' => 'Semarang Selatan', 'wilayah' => 'Kota Semarang (Jateng)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '508' => ['name' => 'KPP Pratama Semarang Candisari (508)', 'short' => 'Semarang Candisari', 'wilayah' => 'Kota Semarang (Jateng)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '526' => ['name' => 'KPP Pratama Surakarta (526)', 'short' => 'Surakarta', 'wilayah' => 'Kota Surakarta / Solo (Jateng)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '541' => ['name' => 'KPP Pratama Yogyakarta (541)', 'short' => 'Yogyakarta', 'wilayah' => 'Kota Yogyakarta (DIY)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '542' => ['name' => 'KPP Pratama Wates (542)', 'short' => 'Wates', 'wilayah' => 'Kab. Kulon Progo (DIY)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '543' => ['name' => 'KPP Pratama Sleman (543)', 'short' => 'Sleman', 'wilayah' => 'Kab. Sleman (DIY)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '544' => ['name' => 'KPP Pratama Bantul (544)', 'short' => 'Bantul', 'wilayah' => 'Kab. Bantul & Gunungkidul (DIY)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],

            // Jawa Barat & Banten
            '421' => ['name' => 'KPP Pratama Bandung Cibeunying (421)', 'short' => 'Bandung Cibeunying', 'wilayah' => 'Kota Bandung (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '422' => ['name' => 'KPP Pratama Bandung Bojonagara (422)', 'short' => 'Bandung Bojonagara', 'wilayah' => 'Kota Bandung (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '423' => ['name' => 'KPP Pratama Bandung Karees (423)', 'short' => 'Bandung Karees', 'wilayah' => 'Kota Bandung (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '424' => ['name' => 'KPP Pratama Bandung Tegallega (424)', 'short' => 'Bandung Tegallega', 'wilayah' => 'Kota Bandung (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '428' => ['name' => 'KPP Pratama Bandung Cicadas (428)', 'short' => 'Bandung Cicadas', 'wilayah' => 'Kota Bandung (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '403' => ['name' => 'KPP Pratama Bogor (403)', 'short' => 'Bogor', 'wilayah' => 'Kota & Kab. Bogor (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '407' => ['name' => 'KPP Pratama Bekasi Barat (407)', 'short' => 'Bekasi Barat', 'wilayah' => 'Kota Bekasi (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '432' => ['name' => 'KPP Pratama Bekasi Timur (432)', 'short' => 'Bekasi Timur', 'wilayah' => 'Kota Bekasi (Jabar)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '416' => ['name' => 'KPP Pratama Tangerang Barat (416)', 'short' => 'Tangerang Barat', 'wilayah' => 'Tangerang (Banten)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '451' => ['name' => 'KPP Pratama Tangerang Timur (451)', 'short' => 'Tangerang Timur', 'wilayah' => 'Tangerang (Banten)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '401' => ['name' => 'KPP Pratama Serang (401)', 'short' => 'Serang', 'wilayah' => 'Kota & Kab. Serang (Banten)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],

            // DKI Jakarta
            '011' => ['name' => 'KPP Pratama Jakarta Gambir Satu (011)', 'short' => 'Gambir Satu', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '012' => ['name' => 'KPP Pratama Jakarta Gambir Dua (012)', 'short' => 'Gambir Dua', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '014' => ['name' => 'KPP Pratama Jakarta Senen (014)', 'short' => 'Senen', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '015' => ['name' => 'KPP Pratama Jakarta Tanah Abang Satu (015)', 'short' => 'Tanah Abang Satu', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '016' => ['name' => 'KPP Pratama Jakarta Tanah Abang Dua (016)', 'short' => 'Tanah Abang Dua', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '017' => ['name' => 'KPP Pratama Jakarta Tanah Abang Tiga (017)', 'short' => 'Tanah Abang Tiga', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '018' => ['name' => 'KPP Pratama Jakarta Menteng Satu (018)', 'short' => 'Menteng Satu', 'wilayah' => 'Jakarta Pusat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '021' => ['name' => 'KPP Pratama Jakarta Palmerah (021)', 'short' => 'Palmerah', 'wilayah' => 'Jakarta Barat (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '061' => ['name' => 'KPP Pratama Jakarta Kebayoran Baru Satu (061)', 'short' => 'Kebayoran Baru Satu', 'wilayah' => 'Jakarta Selatan (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '062' => ['name' => 'KPP Pratama Jakarta Kebayoran Baru Dua (062)', 'short' => 'Kebayoran Baru Dua', 'wilayah' => 'Jakarta Selatan (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '063' => ['name' => 'KPP Pratama Jakarta Kebayoran Lama (063)', 'short' => 'Kebayoran Lama', 'wilayah' => 'Jakarta Selatan (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '064' => ['name' => 'KPP Pratama Jakarta Setiabudi Satu (064)', 'short' => 'Setiabudi Satu', 'wilayah' => 'Jakarta Selatan (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '071' => ['name' => 'KPP Pratama Jakarta Pasar Rebo (071)', 'short' => 'Pasar Rebo', 'wilayah' => 'Jakarta Timur (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '072' => ['name' => 'KPP Pratama Jakarta Jatinegara (072)', 'short' => 'Jatinegara', 'wilayah' => 'Jakarta Timur (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '041' => ['name' => 'KPP Pratama Jakarta Tanjung Priok (041)', 'short' => 'Tanjung Priok', 'wilayah' => 'Jakarta Utara (DKI Jakarta)', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
            '051' => ['name' => 'KPP Perusahaan Masuk Bursa / BUMN (051)', 'short' => 'KPP BUMN / Bursa', 'wilayah' => 'Wajib Pajak Besar / Nasional', 'is_lobar' => false, 'is_ntb' => false, 'badge_color' => 'bg-label-secondary'],
        ];

        if (isset($kppDefinitions[$code])) {
            $def = $kppDefinitions[$code];
            $badge = '<span class="badge ' . $def['badge_color'] . ' py-1 px-2" title="' . htmlspecialchars($def['name']) . '"><i class="bx bxs-institution me-1"></i>KPP ' . $code . '</span><div class="text-muted small mt-1" style="font-size:0.72rem;">' . htmlspecialchars($def['wilayah']) . '</div>';
            return [
                'code' => $code,
                'name' => $def['name'],
                'short' => $def['short'],
                'wilayah' => $def['wilayah'],
                'badge' => $badge,
                'is_lobar' => $def['is_lobar'],
                'is_ntb' => $def['is_ntb']
            ];
        }

        // Luar NTB KPP (Fallback default)
        $badge = '<span class="badge bg-label-secondary py-1 px-2" title="KPP Luar NTB (Kode ' . htmlspecialchars($code) . ')"><i class="bx bx-buildings me-1"></i>KPP ' . htmlspecialchars($code) . '</span><div class="text-muted small mt-1" style="font-size:0.72rem;">Luar Daerah NTB (Kode ' . htmlspecialchars($code) . ')</div>';
        return [
            'code' => $code,
            'name' => 'KPP Luar NTB (' . $code . ')',
            'short' => 'KPP ' . $code,
            'wilayah' => 'Luar Daerah NTB (Kode ' . $code . ')',
            'badge' => $badge,
            'is_lobar' => false,
            'is_ntb' => false
        ];
    }
}

if (!function_exists('classify_npwp_location')) {
    /**
     * Klasifikasi lokasi perusahaan / penyedia berdasarkan kode KPP pada NPWP atau NIK
     * Kode KPP NTB:
     * - 914: KPP Pratama Mataram Timur (Kab. Lombok Barat & Kab. Lombok Utara)
     * - 911: KPP Pratama Mataram Barat (Kota Mataram)
     * - 915: KPP Pratama Praya (Kab. Lombok Tengah & Kab. Lombok Timur)
     * - 912: KPP Pratama Raba Bima (Kota Bima, Kab. Bima & Kab. Dompu)
     * - 913: KPP Pratama Sumbawa Besar (Kab. Sumbawa & Kab. Sumbawa Barat)
     */
    function classify_npwp_location($npwp, $alamat = '', $prov = '') {
        $raw = trim($npwp ?? '');
        $clean = preg_replace('/[^0-9]/', '', $raw);

        $kppDetected = extract_kpp_from_npwp($raw);
        $kppCodeDefault = $kppDetected['code'];
        $kppNameDefault = $kppDetected['name'];

        // 1. Bersihkan suffix template bawaan portal SPSE (misal ", Lombok Barat (Kab.), Nusa Tenggara Barat" di akhir teks)
        // jika di dalam alamat sebenarnya sudah memuat kota/kabupaten lain.
        $cleanAlamat = $alamat;
        if (preg_match('/\b(JAKARTA|SURABAYA|BANDUNG|SEMARANG|DENPASAR|BALI|MATARAM|CAKRANEGARA|SEKARBELA|AMPENAN|SELONG|LOMBOK TIMUR|PRAYA|LOMBOK TENGAH|BIMA|DOMPU|SUMBAWA)\b/i', $cleanAlamat)) {
            $cleanAlamat = preg_replace('/,\s*Lombok Barat\s*\(Kab\.\)[^,]*$/i', '', $cleanAlamat);
            $cleanAlamat = preg_replace('/,\s*Kab(?:\.|upaten)?\s*Lombok Barat[^,]*$/i', '', $cleanAlamat);
        }

        // Normalisasi alamat dan provinsi
        $upperAlamat = strtoupper($cleanAlamat . ' ' . $prov);

        // Prioritas Deteksi: Cek apakah ada Kabupaten/Kota di NTB selain Lombok Barat
        // Khusus Lombok Timur: cegah Sandubaya di Selong (Lotim) keliru masuk ke Mataram
        $isLotimAddr = (
            strpos($upperAlamat, 'LOMBOK TIMUR') !== false || 
            strpos($upperAlamat, 'SELONG') !== false ||
            strpos($upperAlamat, 'PRINGGABAYA') !== false ||
            strpos($upperAlamat, 'MASBAGIK') !== false ||
            strpos($upperAlamat, 'AIKMEL') !== false ||
            strpos($upperAlamat, 'KERUAK') !== false ||
            strpos($upperAlamat, 'SUKAMULIA') !== false
        );

        $isLotengAddr = (
            strpos($upperAlamat, 'LOMBOK TENGAH') !== false || 
            strpos($upperAlamat, 'PRAYA') !== false ||
            strpos($upperAlamat, 'JONGGAT') !== false ||
            strpos($upperAlamat, 'KOPANG') !== false ||
            strpos($upperAlamat, 'PUJUT') !== false ||
            strpos($upperAlamat, 'PRINGGARATA') !== false ||
            strpos($upperAlamat, 'BATUKLIANG') !== false
        );

        $isMataramAddr = (
            strpos($upperAlamat, 'MATARAM') !== false || 
            strpos($upperAlamat, 'CAKRAN') !== false || 
            strpos($upperAlamat, 'AMPEN') !== false || 
            strpos($upperAlamat, 'SEKARB') !== false || 
            strpos($upperAlamat, 'SELAPA') !== false || 
            (!$isLotimAddr && strpos($upperAlamat, 'SANDUB') !== false) || 
            strpos($upperAlamat, 'MONJOK') !== false || 
            strpos($upperAlamat, 'JEMPONG') !== false || 
            strpos($upperAlamat, 'MAYURA') !== false || 
            strpos($upperAlamat, 'PEJANGGIK') !== false || 
            strpos($upperAlamat, 'PAGESANG') !== false || 
            strpos($upperAlamat, 'PAGUT') !== false || 
            strpos($upperAlamat, 'REMBIG') !== false ||
            strpos($upperAlamat, 'ABIAN TUBUH') !== false ||
            strpos($upperAlamat, 'BERTAIS') !== false ||
            strpos($upperAlamat, 'SWETA') !== false ||
            strpos($upperAlamat, 'TANJUNG KARANG') !== false
        );

        $isLobarAddr = (
            strpos($upperAlamat, 'LOMBOK BARAT') !== false || 
            strpos($upperAlamat, 'GERUNG') !== false || 
            strpos($upperAlamat, 'LABUAP') !== false || 
            strpos($upperAlamat, 'TELAGAWARU') !== false ||
            strpos($upperAlamat, 'NARMAD') !== false ||
            strpos($upperAlamat, 'DASAN TERENG') !== false ||
            strpos($upperAlamat, 'DASAN TAPEN') !== false ||
            strpos($upperAlamat, 'SEKOTO') !== false ||
            strpos($upperAlamat, 'BUWUN MAS') !== false ||
            strpos($upperAlamat, 'BENGKANG') !== false ||
            strpos($upperAlamat, 'KURIP') !== false ||
            strpos($upperAlamat, 'KEDIRI') !== false ||
            strpos($upperAlamat, 'LEMBAR') !== false ||
            strpos($upperAlamat, 'LINGSAR') !== false ||
            strpos($upperAlamat, 'BATULA') !== false ||
            strpos($upperAlamat, 'BATU LAYAR') !== false ||
            strpos($upperAlamat, 'GUNUNGSARI') !== false ||
            strpos($upperAlamat, 'GUNUNG SARI') !== false ||
            strpos($upperAlamat, 'BENGKEL') !== false ||
            strpos($upperAlamat, 'SANDIK') !== false ||
            strpos($upperAlamat, 'SENGGIGI') !== false ||
            strpos($upperAlamat, 'GIRISASAK') !== false
        );

        // Ejaan alternatif Narmada ("Naramada") termasuk Lombok Barat
        if (strpos($upperAlamat, 'NARAMADA') !== false) {
            $isLobarAddr = true;
        }

        // Jika alamat eksplisit menyebut Lombok Barat / kecamatan Lobar, sedangkan Mataram hanya
        // terpicu kata kunci lemah (mis. nama jalan "Sandubaya"), maka Lombok Barat yang menang.
        if ($isLobarAddr && $isMataramAddr && !preg_match('/\b(KOTA\s+MATARAM|MATARAM)\b|CAKRAN|AMPEN|SEKARB|SELAPA|MONJOK|JEMPONG|MAYURA|PEJANGGIK|PAGESANG|PAGUT|REMBIG|ABIAN TUBUH|BERTAIS|SWETA|TANJUNG KARANG/i', $upperAlamat)) {
            $isMataramAddr = false;
        }

        // Jika alamat menyebutkan kecamatan di Mataram (misal Mayura / Cakranegara / Sekarbela)
        if ($isMataramAddr && (strpos($upperAlamat, 'MAYURA') !== false || strpos($upperAlamat, 'CAKRAN') !== false || strpos($upperAlamat, 'JEMPONG') !== false || strpos($upperAlamat, 'MONJOK') !== false || strpos($upperAlamat, 'ABIAN TUBUH') !== false || strpos($upperAlamat, 'SANDUB') !== false || strpos($upperAlamat, 'SEKARB') !== false || strpos($upperAlamat, 'AMPEN') !== false)) {
            $isLobarAddr = false;
        }

        // Cek Luar NTB terlebih dahulu jika eksplisit ada nama kota besar nasional
        $luarCityMap = [
            'JAKARTA TIMUR'     => 'Jakarta Timur',
            'JAKARTA SELATAN'   => 'Jakarta Selatan',
            'JAKARTA BARAT'     => 'Jakarta Barat',
            'JAKARTA PUSAT'     => 'Jakarta Pusat',
            'JAKARTA UTARA'     => 'Jakarta Utara',
            'JAKARTA'           => 'Jakarta',
            'DKI'               => 'Jakarta',
            'SURABAYA'          => 'Surabaya',
            'SIDOARJO'          => 'Sidoarjo',
            'MALANG'            => 'Malang',
            'BANDUNG'           => 'Bandung',
            'SEMARANG'          => 'Semarang',
            'YOGYAKARTA'        => 'Yogyakarta',
            'JOGJA'             => 'Yogyakarta',
            'SLEMAN'            => 'Kab. Sleman',
            'BANTUL'            => 'Kab. Bantul',
            'SURAKARTA'         => 'Surakarta',
            'SOLO'              => 'Surakarta',
            'MAKASSAR'          => 'Makassar',
            'MAKASAR'           => 'Makassar',
            'GORONTALO'         => 'Gorontalo',
            'DENPASAR'          => 'Denpasar',
            'BADUNG'            => 'Badung (Bali)',
            'MEDAN'             => 'Medan',
            'PALEMBANG'         => 'Palembang',
            'PEKANBARU'         => 'Pekanbaru',
            'PADANG'            => 'Padang',
            'PONTIANAK'         => 'Pontianak',
            'BANJARMASIN'       => 'Banjarmasin',
            'SAMARINDA'         => 'Samarinda',
            'BALIKPAPAN'        => 'Balikpapan',
            'MANADO'            => 'Manado',
            'KUPANG'            => 'Kupang',
            'JAYAPURA'          => 'Jayapura'
        ];
        foreach ($luarCityMap as $kw => $cityName) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $upperAlamat)) {
                return [
                    'domisili'      => $cityName,
                    'lokasi'        => $cityName,
                    'flag'          => $cityName,
                    'flag_kategori' => 'luar',
                    'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : "Alamat Fisik ($cityName)"),
                    'kpp_code'      => $kppCodeDefault,
                    'badge'         => '<span class="badge bg-label-secondary"><i class="bx bx-map-pin me-1"></i>' . htmlspecialchars($cityName) . '</span>',
                    'is_lobar'      => ($kppCodeDefault === '914'),
                    'is_ntb'        => ($kppDetected['is_ntb']),
                    'keterangan'    => "Domisili di $cityName (Luar NTB)"
                ];
            }
        }

        // Khusus BALI gunakan word boundary agar "HAMBALI" tidak tertrigger
        if (preg_match('/\bBALI\b/i', $cleanAlamat)) {
            return [
                'domisili'      => 'Bali',
                'lokasi'        => 'Bali',
                'flag'          => 'Bali',
                'flag_kategori' => 'luar',
                'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : "Alamat Fisik (Bali)"),
                'kpp_code'      => $kppCodeDefault,
                'badge'         => '<span class="badge bg-label-secondary"><i class="bx bx-map-pin me-1"></i>Bali</span>',
                'is_lobar'      => ($kppCodeDefault === '914'),
                'is_ntb'        => ($kppDetected['is_ntb']),
                'keterangan'    => "Domisili di Bali (Luar NTB)"
            ];
        }

        if ($isLotimAddr) {
            return [
                'domisili'      => 'Lombok Timur',
                'lokasi'        => 'Lombok Timur',
                'flag'          => 'Lombok Timur',
                'flag_kategori' => 'ntb_lain',
                'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : "Alamat Fisik (Lombok Timur)"),
                'kpp_code'      => $kppCodeDefault,
                'badge'         => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Timur</span>',
                'is_lobar'      => ($kppCodeDefault === '914'),
                'is_ntb'        => true,
                'keterangan'    => "Domisili di Lombok Timur (NTB)"
            ];
        }

        if ($isLotengAddr) {
            return [
                'domisili'      => 'Lombok Tengah',
                'lokasi'        => 'Lombok Tengah',
                'flag'          => 'Lombok Tengah',
                'flag_kategori' => 'ntb_lain',
                'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : "Alamat Fisik (Lombok Tengah)"),
                'kpp_code'      => $kppCodeDefault,
                'badge'         => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Tengah</span>',
                'is_lobar'      => ($kppCodeDefault === '914'),
                'is_ntb'        => true,
                'keterangan'    => "Domisili di Lombok Tengah (NTB)"
            ];
        }

        if ($isMataramAddr) {
            return [
                'domisili'      => 'Mataram',
                'lokasi'        => 'Mataram',
                'flag'          => 'Mataram',
                'flag_kategori' => 'mataram',
                'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : 'Alamat Fisik (Mataram)'),
                'kpp_code'      => $kppCodeDefault,
                'badge'         => '<span class="badge bg-label-primary"><i class="bx bx-buildings me-1"></i>Mataram</span>',
                'is_lobar'      => ($kppCodeDefault === '914'),
                'is_ntb'        => true,
                'keterangan'    => 'Domisili di Mataram'
            ];
        }

        if ($isLobarAddr) {
            return [
                'domisili'      => 'Lombok Barat',
                'lokasi'        => 'Lombok Barat',
                'flag'          => 'Lombok Barat',
                'flag_kategori' => 'lobar',
                'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : 'Alamat Fisik (Lombok Barat)'),
                'kpp_code'      => $kppCodeDefault,
                'badge'         => '<span class="badge bg-label-success"><i class="bx bx-map-pin me-1"></i>Lombok Barat</span>',
                'is_lobar'      => true,
                'is_ntb'        => true,
                'keterangan'    => 'Domisili di Lombok Barat'
            ];
        }

        // Kabupaten Lain di NTB dari Alamat
        $ntbSpecificMap = [
            'LOMBOK UTARA'  => 'Lombok Utara',
            'BAYAN'         => 'Lombok Utara',
            'GANGGA'        => 'Lombok Utara',
            'PEMENANG'      => 'Lombok Utara',
            'SUMBAWA BARAT' => 'Sumbawa Barat',
            'TALIWANG'      => 'Sumbawa Barat',
            'MALUK'         => 'Sumbawa Barat',
            'SUMBAWA'       => 'Sumbawa',
            'BIMA'          => 'Bima',
            'RASANAE'       => 'Bima',
            'DOMPU'         => 'Dompu',
            'WOJA'          => 'Dompu',
        ];
        foreach ($ntbSpecificMap as $kw => $kabName) {
            if (strpos($upperAlamat, $kw) !== false) {
                return [
                    'domisili'      => $kabName,
                    'lokasi'        => $kabName,
                    'flag'          => $kabName,
                    'flag_kategori' => 'ntb_lain',
                    'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : "Alamat Fisik ($kabName)"),
                    'kpp_code'      => $kppCodeDefault,
                    'badge'         => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>' . htmlspecialchars($kabName) . '</span>',
                    'is_lobar'      => ($kppCodeDefault === '914'),
                    'is_ntb'        => true,
                    'keterangan'    => "Domisili di $kabName (NTB)"
                ];
            }
        }

        // Khusus TANJUNG (Lombok Utara) tanpa TANJUNG KARANG
        if (preg_match('/\bTANJUNG\b/i', $upperAlamat) && strpos($upperAlamat, 'TANJUNG KARANG') === false) {
            return [
                'domisili'      => 'Lombok Utara',
                'lokasi'        => 'Lombok Utara',
                'flag'          => 'Lombok Utara',
                'flag_kategori' => 'ntb_lain',
                'kpp'           => ($kppNameDefault !== '-' ? $kppNameDefault : "Alamat Fisik (Lombok Utara)"),
                'kpp_code'      => $kppCodeDefault,
                'badge'         => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Utara</span>',
                'is_lobar'      => ($kppCodeDefault === '914'),
                'is_ntb'        => true,
                'keterangan'    => "Domisili di Lombok Utara (NTB)"
            ];
        }

        // 2. Prioritas 2: KPP Pratama dan NIK KTP dari NPWP Valid
        $kpp = null;
        if (preg_match('/-([0-9]{3})\./', $raw, $m)) {
            $kpp = $m[1];
        } elseif (strlen($clean) >= 15 && !str_starts_with($clean, '100000000')) {
            $c15 = substr($clean, -6, 3);
            if (in_array($c15, ['911', '912', '913', '914', '915'])) {
                $kpp = $c15;
            } elseif (in_array(substr($clean, 9, 3), ['911', '912', '913', '914', '915'])) {
                $kpp = substr($clean, 9, 3);
            } elseif (str_ends_with($clean, '000') || str_ends_with($clean, '001')) {
                $kpp = $c15;
            }
        }

        // Cek format NIK (16 digit yang tidak berakhiran 000)
        $isNik = (strlen($clean) === 16 && !str_ends_with($clean, '000') && !str_starts_with($clean, '100000000'));
        $nikPrefix = $isNik ? substr($clean, 0, 4) : null;

        $kppMap = [
            '914' => [
                'domisili' => 'Lombok Barat',
                'lokasi'   => 'Lombok Barat',
                'flag'     => 'Lombok Barat',
                'flag_kategori' => 'lobar',
                'kpp'      => 'KPP Pratama Mataram Timur (914)',
                'badge'    => '<span class="badge bg-label-success"><i class="bx bx-map-pin me-1"></i>Lombok Barat</span>',
                'is_lobar' => true,
                'is_ntb'   => true
            ],
            '911' => [
                'domisili' => 'Mataram',
                'lokasi'   => 'Mataram',
                'flag'     => 'Mataram',
                'flag_kategori' => 'mataram',
                'kpp'      => 'KPP Pratama Mataram Barat (911)',
                'badge'    => '<span class="badge bg-label-primary"><i class="bx bx-buildings me-1"></i>Mataram</span>',
                'is_lobar' => false,
                'is_ntb'   => true
            ],
            '915' => [
                'domisili' => 'Lombok Tengah',
                'lokasi'   => 'Lombok Tengah',
                'flag'     => 'Lombok Tengah',
                'flag_kategori' => 'ntb_lain',
                'kpp'      => 'KPP Pratama Praya (915)',
                'badge'    => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Tengah</span>',
                'is_lobar' => false,
                'is_ntb'   => true
            ],
            '912' => [
                'domisili' => 'Bima',
                'lokasi'   => 'Bima',
                'flag'     => 'Bima',
                'flag_kategori' => 'ntb_lain',
                'kpp'      => 'KPP Pratama Raba Bima (912)',
                'badge'    => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Bima</span>',
                'is_lobar' => false,
                'is_ntb'   => true
            ],
            '913' => [
                'domisili' => 'Sumbawa',
                'lokasi'   => 'Sumbawa',
                'flag'     => 'Sumbawa',
                'flag_kategori' => 'ntb_lain',
                'kpp'      => 'KPP Pratama Sumbawa Besar (913)',
                'badge'    => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Sumbawa</span>',
                'is_lobar' => false,
                'is_ntb'   => true
            ],
        ];

        $nikMap = [
            '5201' => ['domisili' => 'Lombok Barat', 'lokasi' => 'Lombok Barat', 'flag' => 'Lombok Barat', 'flag_kategori' => 'lobar', 'kpp' => 'KTP Lombok Barat (5201)', 'badge' => '<span class="badge bg-label-success"><i class="bx bx-map-pin me-1"></i>Lombok Barat</span>', 'is_lobar' => true, 'is_ntb' => true],
            '5271' => ['domisili' => 'Mataram', 'lokasi' => 'Mataram', 'flag' => 'Mataram', 'flag_kategori' => 'mataram', 'kpp' => 'KTP Mataram (5271)', 'badge' => '<span class="badge bg-label-primary"><i class="bx bx-buildings me-1"></i>Mataram</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5202' => ['domisili' => 'Lombok Tengah', 'lokasi' => 'Lombok Tengah', 'flag' => 'Lombok Tengah', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Lombok Tengah (5202)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Tengah</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5203' => ['domisili' => 'Lombok Timur', 'lokasi' => 'Lombok Timur', 'flag' => 'Lombok Timur', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Lombok Timur (5203)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Timur</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5204' => ['domisili' => 'Sumbawa', 'lokasi' => 'Sumbawa', 'flag' => 'Sumbawa', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Sumbawa (5204)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Sumbawa</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5207' => ['domisili' => 'Sumbawa Barat', 'lokasi' => 'Sumbawa Barat', 'flag' => 'Sumbawa Barat', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Sumbawa Barat (5207)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Sumbawa Barat</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5208' => ['domisili' => 'Lombok Utara', 'lokasi' => 'Lombok Utara', 'flag' => 'Lombok Utara', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Lombok Utara (5208)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Lombok Utara</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5205' => ['domisili' => 'Dompu', 'lokasi' => 'Dompu', 'flag' => 'Dompu', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Dompu (5205)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Dompu</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5206' => ['domisili' => 'Bima', 'lokasi' => 'Bima', 'flag' => 'Bima', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Bima (5206)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Bima</span>', 'is_lobar' => false, 'is_ntb' => true],
            '5272' => ['domisili' => 'Bima', 'lokasi' => 'Bima', 'flag' => 'Bima', 'flag_kategori' => 'ntb_lain', 'kpp' => 'KTP Kota Bima (5272)', 'badge' => '<span class="badge bg-label-info"><i class="bx bx-map-pin me-1"></i>Bima</span>', 'is_lobar' => false, 'is_ntb' => true],
        ];

        if ($kpp && isset($kppMap[$kpp])) {
            $info = $kppMap[$kpp];
            $info['kpp_code'] = $kpp;
            $info['keterangan'] = "Domisili {$info['domisili']} - {$info['kpp']}";
            return $info;
        }

        if ($isNik && isset($nikMap[$nikPrefix])) {
            $info = $nikMap[$nikPrefix];
            $info['kpp_code'] = $nikPrefix;
            $info['keterangan'] = "Domisili {$info['domisili']} ({$info['kpp']})";
            return $info;
        }

        // Deteksi Wilayah Kota / Provinsi Luar NTB dari Alamat / Provinsi
        $luarCityMap = [
            'JAKARTA TIMUR'     => 'Jakarta Timur',
            'JAKARTA SELATAN'   => 'Jakarta Selatan',
            'JAKARTA BARAT'     => 'Jakarta Barat',
            'JAKARTA PUSAT'     => 'Jakarta Pusat',
            'JAKARTA UTARA'     => 'Jakarta Utara',
            'JAKARTA'           => 'Jakarta',
            'DKI'               => 'Jakarta',
            'SURABAYA'          => 'Surabaya',
            'SIDOARJO'          => 'Sidoarjo',
            'BANDUNG'           => 'Bandung',
            'BEKASI'            => 'Bekasi',
            'BOGOR'             => 'Bogor',
            'TANGERANG SELATAN' => 'Tangerang Selatan',
            'TANGERANG'         => 'Tangerang',
            'DEPOK'             => 'Depok',
            'SEMARANG'          => 'Semarang',
            'YOGYAKARTA'        => 'Yogyakarta',
            'JOGJA'             => 'Yogyakarta',
            'SLEMAN'            => 'Yogyakarta',
            'BANTUL'            => 'Yogyakarta',
            'DENPASAR'          => 'Bali',
            'BADUNG'            => 'Bali',
            'BALI'              => 'Bali',
            'MAKASSAR'          => 'Makassar',
            'MEDAN'             => 'Medan',
            'MALANG'            => 'Malang',
            'PALEMBANG'         => 'Palembang',
            'PEKANBARU'         => 'Pekanbaru',
            'PADANG'            => 'Padang',
            'LAMPUNG'           => 'Lampung',
            'GRESIK'            => 'Jawa Timur',
            'PASURUAN'          => 'Jawa Timur',
            'PROBOLINGGO'       => 'Jawa Timur',
            'KEDIRI'            => (strpos($upperAlamat, 'NTB') !== false ? 'Lombok Barat' : 'Jawa Timur'),
            'BLITAR'            => 'Jawa Timur',
            'MADIUN'            => 'Jawa Timur',
            'CIREBON'           => 'Jawa Barat',
            'KARAWANG'          => 'Jawa Barat',
            'GARUT'             => 'Jawa Barat',
            'CIAMIS'            => 'Jawa Barat',
            'CIMAHI'            => 'Jawa Barat',
            'KLATEN'            => 'Jawa Tengah',
            'SUKOHARJO'         => 'Jawa Tengah',
            'KARANGANYAR'       => 'Jawa Tengah',
            'PEKALONGAN'        => 'Jawa Tengah',
            'BATANG'            => 'Jawa Tengah',
            'PURWOKERTO'        => 'Jawa Tengah',
            'KEBUMEN'           => 'Jawa Tengah',
            'SERANG'            => 'Banten',
            'CILEGON'           => 'Banten',
            'BANJARMASIN'       => 'Kalimantan',
            'BALIKPAPAN'        => 'Kalimantan',
            'SAMARINDA'         => 'Kalimantan',
            'PONTIANAK'         => 'Kalimantan',
            'KUPANG'            => 'NTT',
            'NUSA TENGGARA TIMUR' => 'NTT',
            'JAWA TIMUR'        => 'Jawa Timur',
            'JAWA TENGAH'       => 'Jawa Tengah',
            'JAWA BARAT'        => 'Jawa Barat',
            'BANTEN'            => 'Banten',
            'SUMATERA'          => 'Sumatera',
            'SULAWESI'          => 'Sulawesi',
            'KALIMANTAN'        => 'Kalimantan',
            'PAPUA'             => 'Papua',
            'MALUKU'            => 'Maluku'
        ];

        foreach ($luarCityMap as $kw => $cityName) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $upperAlamat)) {
                return [
                    'domisili'      => $cityName,
                    'lokasi'        => $cityName,
                    'flag'          => $cityName,
                    'flag_kategori' => 'luar',
                    'kpp'           => $kpp ? "KPP $kpp" : 'Alamat Domisili',
                    'kpp_code'      => $kpp ?: '-',
                    'badge'         => '<span class="badge bg-label-secondary"><i class="bx bx-map-pin me-1"></i>' . htmlspecialchars($cityName) . '</span>',
                    'is_lobar'      => false,
                    'is_ntb'        => false,
                    'keterangan'    => "Domisili di $cityName"
                ];
            }
        }

        // 4. Deteksi Wilayah & KPP Luar NTB (Domisili Kota / Kabupaten)
        $luarKppMap = [
            // DKI Jakarta (001 - 099)
            '004' => ['kpp' => 'KPP Pratama Jakarta Menteng Satu', 'domisili' => 'Jakarta Pusat'],
            '007' => ['kpp' => 'KPP Pratama Jakarta Tanah Abang Satu', 'domisili' => 'Jakarta Pusat'],
            '008' => ['kpp' => 'KPP Pratama Jakarta Gambir Tiga', 'domisili' => 'Jakarta Pusat'],
            '012' => ['kpp' => 'KPP Pratama Jakarta Kebayoran Baru Satu', 'domisili' => 'Jakarta Selatan'],
            '013' => ['kpp' => 'KPP Pratama Jakarta Kebayoran Lama', 'domisili' => 'Jakarta Selatan'],
            '016' => ['kpp' => 'KPP Pratama Jakarta Pasar Minggu', 'domisili' => 'Jakarta Selatan'],
            '017' => ['kpp' => 'KPP Pratama Jakarta Cilandak', 'domisili' => 'Jakarta Selatan'],
            '023' => ['kpp' => 'KPP Pratama Jakarta Senen', 'domisili' => 'Jakarta Pusat'],
            '028' => ['kpp' => 'KPP Pratama Jakarta Jatinegara', 'domisili' => 'Jakarta Timur'],
            '029' => ['kpp' => 'KPP Pratama Jakarta Kramat Jati', 'domisili' => 'Jakarta Timur'],
            '032' => ['kpp' => 'KPP Pratama Jakarta Palmerah', 'domisili' => 'Jakarta Barat'],
            '037' => ['kpp' => 'KPP Pratama Jakarta Grogol Petamburan', 'domisili' => 'Jakarta Barat'],
            '038' => ['kpp' => 'KPP Pratama Jakarta Tamansari Dua', 'domisili' => 'Jakarta Barat'],
            '041' => ['kpp' => 'KPP Pratama Jakarta Koja', 'domisili' => 'Jakarta Utara'],
            '046' => ['kpp' => 'KPP Pratama Jakarta Kelapa Gading', 'domisili' => 'Jakarta Utara'],
            '048' => ['kpp' => 'KPP Pratama Jakarta Pluit', 'domisili' => 'Jakarta Utara'],
            '051' => ['kpp' => 'KPP Madya Jakarta Pusat', 'domisili' => 'Jakarta Pusat'],
            '052' => ['kpp' => 'KPP Madya Jakarta Barat', 'domisili' => 'Jakarta Barat'],
            '054' => ['kpp' => 'KPP Pratama Jakarta Tanjung Priok', 'domisili' => 'Jakarta Utara'],
            '063' => ['kpp' => 'KPP Pratama Jakarta Matraman', 'domisili' => 'Jakarta Timur'],
            '073' => ['kpp' => 'KPP Pratama Jakarta Pulogadung', 'domisili' => 'Jakarta Timur'],
            '075' => ['kpp' => 'KPP Pratama Jakarta Pasar Rebo', 'domisili' => 'Jakarta Timur'],
            '077' => ['kpp' => 'KPP Pratama Jakarta Cakung Satu', 'domisili' => 'Jakarta Timur'],
            '086' => ['kpp' => 'KPP Pratama Jakarta Kalideres', 'domisili' => 'Jakarta Barat'],
            '092' => ['kpp' => 'KPP Pratama Jakarta Cakung Dua', 'domisili' => 'Jakarta Timur'],
            '093' => ['kpp' => 'KPP Pratama Jakarta Kebayoran Baru Empat', 'domisili' => 'Jakarta Selatan'],

            // Jawa Barat & Banten (400 - 499)
            '401' => ['kpp' => 'KPP Pratama Bandung Cibeunying', 'domisili' => 'Bandung'],
            '403' => ['kpp' => 'KPP Pratama Bogor', 'domisili' => 'Bogor'],
            '406' => ['kpp' => 'KPP Pratama Cirebon Dua', 'domisili' => 'Cirebon'],
            '409' => ['kpp' => 'KPP Pratama Bandung Karees', 'domisili' => 'Bandung'],
            '411' => ['kpp' => 'KPP Pratama Bekasi Barat', 'domisili' => 'Bekasi'],
            '412' => ['kpp' => 'KPP Pratama Karawang Selatan', 'domisili' => 'Karawang'],
            '415' => ['kpp' => 'KPP Pratama Ciamis', 'domisili' => 'Ciamis'],
            '416' => ['kpp' => 'KPP Pratama Garut', 'domisili' => 'Garut'],
            '421' => ['kpp' => 'KPP Pratama Tangerang Barat', 'domisili' => 'Tangerang'],
            '422' => ['kpp' => 'KPP Pratama Serpong', 'domisili' => 'Tangerang Selatan'],
            '423' => ['kpp' => 'KPP Pratama Pandeglang', 'domisili' => 'Pandeglang'],
            '424' => ['kpp' => 'KPP Pratama Tangerang Timur', 'domisili' => 'Tangerang'],
            '429' => ['kpp' => 'KPP Pratama Cikupa', 'domisili' => 'Tangerang'],
            '432' => ['kpp' => 'KPP Pratama Bekasi Selatan', 'domisili' => 'Bekasi'],
            '436' => ['kpp' => 'KPP Pratama Cibinong', 'domisili' => 'Bogor'],
            '441' => ['kpp' => 'KPP Pratama Pondok Gede', 'domisili' => 'Bekasi'],
            '442' => ['kpp' => 'KPP Pratama Bandung Tegallega', 'domisili' => 'Bandung'],
            '445' => ['kpp' => 'KPP Pratama Cimahi', 'domisili' => 'Cimahi'],
            '451' => ['kpp' => 'KPP Pratama Soreang', 'domisili' => 'Bandung'],

            // Jawa Tengah & DIY (500 - 599)
            '506' => ['kpp' => 'KPP Pratama Semarang Candisari', 'domisili' => 'Semarang'],
            '511' => ['kpp' => 'KPP Pratama Klaten', 'domisili' => 'Klaten'],
            '516' => ['kpp' => 'KPP Pratama Sukoharjo', 'domisili' => 'Sukoharjo'],
            '517' => ['kpp' => 'KPP Pratama Karanganyar', 'domisili' => 'Karanganyar'],
            '526' => ['kpp' => 'KPP Pratama Pekalongan', 'domisili' => 'Pekalongan'],
            '527' => ['kpp' => 'KPP Pratama Batang', 'domisili' => 'Batang'],
            '528' => ['kpp' => 'KPP Pratama Purwokerto', 'domisili' => 'Purwokerto'],
            '532' => ['kpp' => 'KPP Pratama Kebumen', 'domisili' => 'Kebumen'],
            '541' => ['kpp' => 'KPP Pratama Yogyakarta', 'domisili' => 'Yogyakarta'],
            '542' => ['kpp' => 'KPP Pratama Sleman', 'domisili' => 'Sleman'],
            '543' => ['kpp' => 'KPP Pratama Bantul', 'domisili' => 'Bantul'],

            // Jawa Timur (600 - 699)
            '604' => ['kpp' => 'KPP Pratama Surabaya Gubeng', 'domisili' => 'Surabaya'],
            '615' => ['kpp' => 'KPP Pratama Surabaya Rungkut', 'domisili' => 'Surabaya'],
            '618' => ['kpp' => 'KPP Pratama Surabaya Mulyorejo', 'domisili' => 'Surabaya'],
            '619' => ['kpp' => 'KPP Pratama Surabaya Karangpilang', 'domisili' => 'Surabaya'],
            '624' => ['kpp' => 'KPP Pratama Malang Selatan', 'domisili' => 'Malang'],
            '631' => ['kpp' => 'KPP Pratama Gresik Utara', 'domisili' => 'Gresik'],
            '641' => ['kpp' => 'KPP Pratama Madiun', 'domisili' => 'Madiun'],
            '643' => ['kpp' => 'KPP Pratama Blitar', 'domisili' => 'Blitar'],
            '651' => ['kpp' => 'KPP Pratama Pasuruan', 'domisili' => 'Pasuruan'],
            '652' => ['kpp' => 'KPP Pratama Probolinggo', 'domisili' => 'Probolinggo'],

            // Bali (901 - 909)
            '901' => ['kpp' => 'KPP Pratama Denpasar Barat', 'domisili' => 'Denpasar'],
            '903' => ['kpp' => 'KPP Pratama Denpasar Timur', 'domisili' => 'Denpasar'],
            '904' => ['kpp' => 'KPP Pratama Badung Selatan', 'domisili' => 'Badung'],

            // Sumatera (300 - 399)
            '309' => ['kpp' => 'KPP Pratama Palembang Seberang Ulu', 'domisili' => 'Palembang'],
            '347' => ['kpp' => 'KPP Pratama Metro', 'domisili' => 'Lampung'],

            // Sulawesi (800 - 899)
            '822' => ['kpp' => 'KPP Pratama Makassar Selatan', 'domisili' => 'Makassar'],
        ];

        if ($kpp && isset($luarKppMap[$kpp])) {
            $item = $luarKppMap[$kpp];
            $domisili = $item['domisili'];
            return [
                'domisili'      => $domisili,
                'lokasi'        => $domisili,
                'flag'          => $domisili,
                'flag_kategori' => 'luar',
                'kpp'           => "{$item['kpp']} ($kpp)",
                'kpp_code'      => $kpp,
                'badge'         => '<span class="badge bg-label-secondary" title="' . htmlspecialchars($item['kpp']) . '"><i class="bx bx-map-pin me-1"></i>' . htmlspecialchars($domisili) . '</span>',
                'is_lobar'      => false,
                'is_ntb'        => false,
                'keterangan'    => "Domisili {$domisili} - Terdaftar di {$item['kpp']}"
            ];
        }

        // Klasifikasi Wilayah Besar Berdasarkan Blok Angka Kode KPP DJP
        $kppNum = intval($kpp);
        $domisiliLabel = null;
        if ($kppNum >= 1 && $kppNum <= 99) {
            $domisiliLabel = 'Jakarta';
        } elseif ($kppNum >= 100 && $kppNum <= 399) {
            $domisiliLabel = 'Sumatera';
        } elseif ($kppNum >= 400 && $kppNum <= 499) {
            $domisiliLabel = 'Jawa Barat';
        } elseif ($kppNum >= 500 && $kppNum <= 539) {
            $domisiliLabel = 'Jawa Tengah';
        } elseif ($kppNum >= 540 && $kppNum <= 549) {
            $domisiliLabel = 'Yogyakarta';
        } elseif ($kppNum >= 600 && $kppNum <= 699) {
            $domisiliLabel = 'Jawa Timur';
        } elseif ($kppNum >= 700 && $kppNum <= 799) {
            $domisiliLabel = 'Kalimantan';
        } elseif ($kppNum >= 800 && $kppNum <= 899) {
            $domisiliLabel = 'Sulawesi';
        } elseif ($kppNum >= 901 && $kppNum <= 909) {
            $domisiliLabel = 'Bali';
        } elseif ($kppNum >= 920 && $kppNum <= 929) {
            $domisiliLabel = 'NTT';
        }

        if (!$domisiliLabel || $kppNum === 0) {
            return [
                'domisili'      => '-',
                'lokasi'        => '-',
                'flag'          => 'Belum Terdata',
                'flag_kategori' => 'unknown',
                'kpp'           => '-',
                'kpp_code'      => '-',
                'badge'         => '<span class="text-muted small">-</span>',
                'is_lobar'      => false,
                'is_ntb'        => false,
                'keterangan'    => 'Data domisili belum terdata'
            ];
        }

        return [
            'domisili'      => $domisiliLabel,
            'lokasi'        => $domisiliLabel,
            'flag'          => $domisiliLabel,
            'flag_kategori' => 'luar',
            'kpp'           => "KPP $kpp ($domisiliLabel)",
            'kpp_code'      => $kpp ?: '-',
            'badge'         => '<span class="badge bg-label-secondary"><i class="bx bx-map-pin me-1"></i>' . htmlspecialchars($domisiliLabel) . '</span>',
            'is_lobar'      => false,
            'is_ntb'        => false,
            'keterangan'    => 'Domisili di ' . $domisiliLabel
        ];
    }
}
