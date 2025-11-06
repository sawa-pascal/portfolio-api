<?php
require_once '../db.php';

// paymentテーブルを全件取得
$stmt = $pdo->prepare('SELECT * FROM payment');
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'payments' => $payments]);