<?php
/**
 * レスポンスをJSON形式で返して終了
 */
function json_response($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

/**
 * 必須パラメータ取得
 */
function get_required($array, $key) {
    if (!isset($array[$key]) || $array[$key] === '') {
        json_response(false, "Invalid {$key}");
    }
    return $array[$key];
}

/** ディレクトリが空なら削除 */
function try_remove_empty_dir($dir) {
    if (is_dir($dir) && count(glob($dir . '/*')) === 0) {
        rmdir($dir);
    }
}

require_once '../db.php';

$input = json_decode(file_get_contents("php://input"), true);

try {
    $id = get_required($input, 'id');

    // 画像URL取得
    $stmt = $pdo->prepare("SELECT image_url FROM items WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_response(false, "データが見つかりません");
    }

    $image_url = $row['image_url'];
    $image_deleted = false;

    // 画像の削除
    if (!empty($image_url)) {
        $image_path = dirname(__DIR__, 2) . '/images/' . $image_url;
        if (file_exists($image_path) && is_file($image_path)) {
            unlink($image_path);
            $image_deleted = true;
            // 不要になったディレクトリを削除する
            try_remove_empty_dir(dirname($image_path));
        }
    }

    // itemsテーブルからデータ削除
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$id]);

    json_response(true, "アイテムを削除しました", [
        'image_deleted' => $image_deleted
    ]);

} catch (Exception $e) {
    json_response(false, "削除に失敗しました: " . $e->getMessage());
}
