<?php
require_once '../db.php';

// 入力取得
$input = json_decode(file_get_contents("php://input"), true);

$sale_id = $input['sale_id'] ?? null;

$response = [
    'success' => false,
    'sale_items' => [],
];

if ($sale_id) {
    $sql = "
        SELECT
            si.id,
            s.date,
            s.user_id,
            i.image_url AS item_image_url,
            i.name AS item_name,
            si.price,
            si.quantity
        FROM sale_items si
        INNER JOIN sales s ON si.sale_id = s.id
        INNER JOIN items i ON si.item_id = i.id
        WHERE si.sale_id = ?
    ";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$sale_id])) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
        $response['sale_items'] = $rows;
    }
}

header('Content-Type: application/json');
echo json_encode($response);