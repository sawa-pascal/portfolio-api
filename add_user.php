<?php
require_once 'db.php';

function getPostData(): array {
    // JSONとしてPOSTされた場合の処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            return array_merge($_POST, $input);
        }
    }
    return $_POST;
}

function isEmailRegistered($pdo, string $email): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return (bool)$stmt->fetchColumn();
}

function registerUser($pdo, string $name, string $email, string $tel, int $prefecture_id, string $address, string $password): bool {
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, hashed_password, tel, prefecture_id, address) VALUES (?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([$name, $email, $password, $tel, $prefecture_id, $address]);
}

$data = getPostData();

$name         = $data['name']         ?? '';
$email        = $data['email']        ?? '';
$tel          = $data['tel']          ?? '';
$prefecture_id= $data['prefecture_id']?? '';
$address      = $data['address']      ?? '';
$password     = $data['password']     ?? '';

$response = ["success" => false, "message" => ""];

if ($name && $email && $tel && $prefecture_id && $address && $password) {
    if (!isEmailRegistered($pdo, $email)) {
        if (registerUser($pdo, $name, $email, $tel, $prefecture_id, $address, $password)) {
            $response["success"] = true;
            $response["message"] = "新規会員登録しました";
        } else {
            $response["message"] = "ユーザ登録に失敗しました";
        }
    } else {
        $response["message"] = "同じメールアドレスですで登録されているアカウントが存在します";
    }
} else {
    $response["message"] = "必須項目が入力されていません";
}

header('Content-Type: application/json');
echo json_encode($response);