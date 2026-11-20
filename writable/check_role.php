<?php
define('FCPATH', __DIR__ . '/../public/');
chdir(__DIR__ . '/../');
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';
$db = Config\Database::connect();
$rows = $db->table('role_permissions')->get()->getResultArray();
foreach ($rows as $r) {
    echo "{$r['id']} | {$r['role']} | {$r['menu_key']} | {$r['is_allowed']}\n";
}
unlink(__FILE__);
