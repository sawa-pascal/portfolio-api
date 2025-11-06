<?php
require_once '../db.php';

// 入力取得
$input = json_decode(file_get_contents("php://input"), true);

// ユーザーIDをPOSTから取得（なければnull）
$user_id = $input['user_id'] ?? null;

$response = [
    'success' => false,
    'purchase_history' => [],
];

if ($user_id) {
    $sql = "
        SELECT
            s.id AS sale_id,
            s.date,
            i.image_url AS item_image_url,
            i.name AS item_name,
            si.price,
            si.quantity
        FROM sales s
        INNER JOIN sale_items si ON s.id = si.sale_id
        INNER JOIN items i ON si.item_id = i.id
        WHERE s.user_id = ?
        ORDER BY s.date DESC, s.id DESC
    ";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$user_id])) {
        $purchase_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
        $response['purchase_history'] = $purchase_history;
    }
}

header('Content-Type: application/json');
echo json_encode($response);