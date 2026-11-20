<?php
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$db = Config\Database::connect();
$rows = $db->table('role_permissions')->where('role', 'ppk')->get()->getResultArray();
echo "Total PPK perms in DB: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo $r['menu_key'] . " = " . $r['is_allowed'] . "\n";
}
unlink(__FILE__);
