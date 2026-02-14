<?php
$pdo = new PDO('pgsql:host=127.0.0.1;port=5433;dbname=alumate_testing', 'postgres', 'postgres');
$cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);
