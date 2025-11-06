<?php
require_once '../db.php';

/**
 * 必須入力値のバリデーション＆取得
 */
function get_required($array, $key) {
    if (!isset($array[$key]) || $array[$key] === '') {
        echo json_encode(["success" => false, "message" => "Invalid {$key}"]);
        exit;
    }
    return $array[$key];
}

$input = json_decode(file_get_contents("php://input"), true);

$name           = get_required($input, 'name');
$email          = get_required($input, 'email');
$hashed_password= get_required($input, 'hashed_password');
$tel            = get_required($input, 'tel');
$prefecture_id  = get_required($input, 'prefecture_id');
$address        = get_required($input, 'address');

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, hashed_password, tel, prefecture_id, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed_password, $tel, $prefecture_id, $address]);
    echo json_encode([
        "success" => true,
        "message" => "ユーザーを追加しました"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "ユーザー追加に失敗しました: " . $e->getMessage()
    ]);
}
