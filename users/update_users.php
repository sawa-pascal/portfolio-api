<?php
require_once '../db.php';

/**
 * 必須入力値チェック & 取得
 */
function get_required($array, $key) {
    if (!isset($array[$key]) || $array[$key] === '') {
        echo json_encode(["success" => false, "message" => "Invalid {$key}"]);
        exit;
    }
    return $array[$key];
}

$input = json_decode(file_get_contents("php://input"), true);

$id              = get_required($input, 'id');
$name            = get_required($input, 'name');
$email           = get_required($input, 'email');
$hashed_password = get_required($input, 'hashed_password');
$tel             = get_required($input, 'tel');
$prefecture_id   = get_required($input, 'prefecture_id');
$address         = get_required($input, 'address');

try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = ?, email = ?, hashed_password = ?, tel = ?, prefecture_id = ?, address = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        $email,
        $hashed_password,
        $tel,
        $prefecture_id,
        $address,
        $id
    ]);
    echo json_encode([
        "success" => true,
        "message" => "ユーザーを更新しました"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "ユーザー更新に失敗しました: " . $e->getMessage()
    ]);
}
