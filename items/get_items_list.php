<?php 
require '../db.php'; 
$id = $_GET['id'] ?? null;

if ($id === null || $id ==='' || $id == 0) {
    $stmt = $pdo->prepare('SELECT * FROM items');
    $stmt->execute();
    $items = $stmt->fetchAll();
}else{
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = ?');
    $stmt->execute([$id]);
    $items = $stmt->fetch();
}

if ($items === false) {
    $items = null;
}

echo json_encode(["success" => true, "items" => $items]);