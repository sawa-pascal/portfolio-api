<?php
require_once 'db.php';

// 入力取得
$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? null;
$name = $input['name'] ?? null;
$email = $input['email'] ?? null;
$tel = $input['tel'] ?? null;
$prefecture_id = $input['prefecture_id'] ?? null;
$address = $input['address'] ?? null;

// バリデーション
if ($id === null || $id === '') {
    echo json_encode(["success" => false, "message" => "Invalid id"]);
    exit;
}
if (empty($name)) {
    echo json_encode(["success" => false, "message" => "名前が必要です"]);
    exit;
}
if (empty($email)) {
    echo json_encode(["success" => false, "message" => "メールアドレスが必要です"]);
    exit;
}
if (empty($tel)) {
    echo json_encode(["success" => false, "message" => "電話番号が必要です"]);
    exit;
}
if ($prefecture_id === null || $prefecture_id === '') {
    echo json_encode(["success" => false, "message" => "都道府県IDが必要です"]);
    exit;
}
if (empty($address)) {
    echo json_encode(["success" => false, "message" => "住所が必要です"]);
    exit;
}

// 更新処理
$stmt = $pdo->prepare("
    UPDATE users 
    SET name = ?, email = ?, tel = ?, prefecture_id = ?, address = ?
    WHERE id = ?
");
$success = $stmt->execute([
    $name,
    $email,
    $tel,
    $prefecture_id,
    $address,
    $id
]);

if ($success) {
    echo json_encode(["success" => true, "message" => "ユーザー情報を更新しました"]);
} else {
    echo json_encode(["success" => false, "message" => "ユーザー情報の更新に失敗しました"]);
}
