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
    $stmt_sale = $pdo->prepare("INSERT INTO sales (user_id, payment_id) VALUES (?, ?)");
    $stmt_sale->execute([$user_id, $payment_id]);
    $sale_id = $pdo->lastInsertId();

    // 商品情報・在庫取得・明細・在庫更新 クエリを事前用意
    $stmt_item   = $pdo->prepare(
        'SELECT i.id, i.price, s.quantity FROM items AS i JOIN stocks AS s ON i.id = s.item_id WHERE i.id = ?'
    );
    $stmt_detail = $pdo->prepare(
        "INSERT INTO sale_items (sale_id, item_id, price, quantity) VALUES (?, ?, ?, ?)"
    );
    $stmt_stock  = $pdo->prepare(
        "UPDATE stocks SET quantity = ? WHERE item_id = ?"
    );

    foreach ($item_ids as $idx => $item_id) {
        $quantity = isset($quantities[$idx]) ? (int)$quantities[$idx] : 0;
        if ($quantity <= 0) {
            continue; // 数量不正はスキップ
        }

        // 商品・在庫取得
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch(PDO::FETCH_ASSOC);

        if (!$item || !isset($item['price'])) {
            $pdo->rollBack();
            json_response(false, "Invalid item_id or item not found: {$item_id}");
        }

        if (!isset($item['quantity']) || $item['quantity'] < $quantity) {
            $pdo->rollBack();
            json_response(false, "在庫がない: {$item_id}");
        }

        // 売上明細・在庫更新
        $stmt_detail->execute([$sale_id, $item_id, $item['price'], $quantity]);
        $new_quantity = $item['quantity'] - $quantity;
        $stmt_stock->execute([$new_quantity, $item_id]);
    }

    $pdo->commit();
    json_response(true, "購入処理が完了しました", ['sale_id' => $sale_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(false, "エラーが発生しました: " . $e->getMessage());
}
