<?php
require '../db.php';

$id = $_GET['id'] ?? null;

/**
 * ユーザー一覧または単一ユーザーを取得する関数
 */
function fetch_users($pdo, $id) {
    if (empty($id) || $id == 0) {
        $stmt = $pdo->query('SELECT * FROM users');
        $result = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
    }

    return $result === false ? null : $result;
}

$users = fetch_users($pdo, $id);

echo json_encode([
    "success" => true,
    "users" => $users
]);