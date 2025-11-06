<?php
require_once '../db.php';

/**
 * 必須キーの取得とバリデーション
 */
function get_required($array, $key) {
    if (!isset($array[$key]) || $array[$key] === '') {
        echo json_encode(["success" => false, "message" => "Invalid {$key}"]);
        exit;
    }
    return $array[$key];
}

$input = json_decode(file_get_contents("php://input"), true);
$id = get_required($input, 'id');

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode([
        "success" => true,
        "message" => "ユーザーを削除しました"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "ユーザー削除に失敗しました: " . $e->getMessage()
    ]);
}
