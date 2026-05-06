<?php
$db = new PDO('mysql:host=localhost;dbname=sidp_db', 'root', '');
$stmt = $db->query("SELECT slug, api_url FROM dynamic_menus");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('log.txt', print_r($menus, true));
