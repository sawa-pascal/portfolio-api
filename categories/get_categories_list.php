<?php 
require '../db.php'; 
$id = $_GET['id'] ?? null;

if ($id === null || $id ==='' || $id == 0) {
    $stmt = $pdo->prepare('SELECT * FROM categories ORDER BY display_order');
    $stmt->execute();
    $items = $stmt->fetchAll();
}else{
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ? ORDER BY display_order');
    $stmt->execute([$id]);
    $items = $stmt->fetch();
}

if ($items === false) {
    $items = null;
}

echo json_encode(["success" => true, "items" => $items]);