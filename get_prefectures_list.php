<?php
require 'db.php';

function json_response($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'prefectures' => $data,
        'message' => $message
    ]);
    exit;
}

$id = $_GET['id'] ?? null;

try {
    if (empty($id) || $id == 0) {
        $stmt = $pdo->query('SELECT * FROM prefectures');
        $prefectures = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM prefectures WHERE id = ?');
        $stmt->execute([$id]);
        $prefecture = $stmt->fetch();
        $prefectures = $prefecture !== false ? $prefecture : null;
    }
    json_response(true, $prefectures);
} catch (PDOException $e) {
    json_response(false, null, 'DB error: ' . $e->getMessage());
}