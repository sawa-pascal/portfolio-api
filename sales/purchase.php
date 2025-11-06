<?php
require_once '../db.php';

// 共通レスポンス関数
function json_response($success, $message, array $extra = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// 入力取得
$input = json_decode(file_get_contents("php://input"), true);

// 入力チェック関数
function assert_required($value, $msg) {
    if ($value === null || $value === '') {
        json_response(false, $msg);
    }
}

// パラメータ取得
$user_id = $input['user_id'] ?? null;
$payment_id = $input['payment_id'] ?? null;
$item_ids = $input['item_ids'] ?? [];
$quantities = $input['quantities'] ?? [];

// パラメータバリデーション
assert_required($user_id, "user_id is required");
assert_required($payment_id, "payment_id is required");
if (!is_array($item_ids) || !is_array($quantities) || count($item_ids) !== count($quantities) || count($item_ids) === 0) {
    json_response(false, "Invalid item_ids or quantities");
}

// トランザクション開始
try {
    $pdo->beginTransaction();

    // 売上テーブル挿入
    $sql_sale = "INSERT INTO sales (user_id, payment_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql_sale);
    $stmt->execute([$user_id, $payment_id]);
    $sale_id = $pdo->lastInsertId();

    // 商品価格取得クエリ
    $stmt_item = $pdo->prepare('SELECT id, price FROM items WHERE id = ?');
    // 売上明細挿入
    $stmt_detail = $pdo->prepare("INSERT INTO sale_items (sale_id, item_id, price, quantity) VALUES (?, ?, ?, ?)");

    foreach ($item_ids as $idx => $item_id) {
        // $idx（foreachの回数=インデックス）を使ってquantitiesから取得
        $quantity = isset($quantities[$idx]) ? (int)$quantities[$idx] : 0;
        if ($quantity <= 0) continue;

        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

        if (!$item || !isset($item['price'])) {
            $pdo->rollBack();
            json_response(false, "Invalid item_id or item not found: {$item_id}");
        }

        $price = $item['price'];
        $stmt_detail->execute([$sale_id, $item_id, $price, $quantity]);
    }

    $pdo->commit();
    json_response(true, "購入処理が完了しました", ['sale_id' => $sale_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(false, "エラーが発生しました: " . $e->getMessage());
}
