<?php 
require '../db.php'; 
$id = $_GET['id'] ?? null;

if ($id === null || $id ==='' || $id == 0) {
    $stmt = $pdo->prepare(
        'SELECT i.id, i.name, i.price, i.description, i.image_url, i.category_id, s.quantity 
        FROM items AS i 
        INNER JOIN stocks AS s 
        ON i.id = s.item_id');

    $stmt->execute();
    $items = $stmt->fetchAll();
}else{
    $stmt = $pdo->prepare(
        'SELECT i.id, i.name, i.price, i.description, i.image_url, i.category_id, s.quantity 
        FROM items AS i 
        INNER JOIN stocks AS s 
        ON i.id = s.item_id 
        WHERE id = ?');

    $stmt->execute([$id]);
    $items = $stmt->fetch();
}

if ($items === false) {
    $items = null;
}

echo json_encode(["success" => true, "items" => $items]);