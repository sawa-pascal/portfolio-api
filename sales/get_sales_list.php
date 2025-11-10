<?php 
require '../db.php'; 
$id = $_GET['id'] ?? null;

if ($id === null || $id ==='' || $id == 0) {
    $stmt = $pdo->prepare('SELECT * FROM sales');
    $stmt->execute();
    $sales = $stmt->fetchAll();
}else{
    $stmt = $pdo->prepare('SELECT * FROM sales WHERE id = ?');
    $stmt->execute([$id]);
    $sales = $stmt->fetch();
}

if ($sales === false) {
    $sales = null;
}

echo json_encode(["success" => true, "sales" => $sales]);