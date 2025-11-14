<?php 
require '../db.php'; 
$id = $_GET['id'] ?? null;
$sales_total = 0;

if ($id === null || $id ==='' || $id == 0) {
    $stmt = $pdo->prepare('SELECT 
        s.id, 
        s.date,
        u.id AS user_id,
        u.name AS user_name,
        s.payment_id
    FROM sales s
    INNER JOIN users u ON s.user_id = u.id
    ORDER BY s.id'
    );

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sales = [];
    foreach ($rows as $row) {
        $sale_id = $row['id'];
        // sale_itemsの合計金額を取得
        $stmt2 = $pdo->prepare('SELECT SUM(price * quantity) AS total FROM sale_items WHERE sale_id = ?');
        $stmt2->execute([$sale_id]);

        $total = $stmt2->fetchColumn() ?: 0;
        $row['total'] = (int)$total;
        $sales_total += $row['total'];
        $sales[] = $row;
    }
}else {
    $stmt = $pdo->prepare('SELECT 
        s.id, 
        s.date,
        u.id AS user_id,
        u.name AS user_name,
        s.payment_id
    FROM sales s
    INNER JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
    ORDER BY s.id'
    );

    $stmt->execute($id);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sales = [];
    foreach ($rows as $row) {
        $sale_id = $row['id'];
        // sale_itemsの合計金額を取得
        $stmt2 = $pdo->prepare('SELECT SUM(price * quantity) AS total FROM sale_items WHERE sale_id = ?');
        $stmt2->execute([$sale_id]);

        $total = $stmt2->fetchColumn() ?: 0;
        $row['total'] = (int)$total;
        $sales_total += $row['total'];
        $sales[] = $row;
    }
}

if ($sales === false) {
    $sales = null;
}

echo json_encode(["success" => true, "sales" => $sales, "sales_total" => $sales_total]);