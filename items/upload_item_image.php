<?php
// エラー出力を無効化（JSONパースエラーを防ぐため）
error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * レスポンスをJSON形式で出力して終了する
 */
function json_response($success, $message, $extra = []) {
    $response = ['success' => $success, 'message' => $message] + $extra;
    echo json_encode($response);
    exit;
}

/**
 * POSTまたはFILESのパラメータ取得&必須チェック
 */
function require_post_param($key) {
    if (!isset($_POST[$key]) || $_POST[$key] === '') {
        json_response(false, "Invalid {$key}");
    }
    return $_POST[$key];
}

/** ディレクトリが空なら削除 */
function cleanup_dir_if_empty($dir) {
    if (is_dir($dir) && count(glob($dir . '/*')) === 0) {
        rmdir($dir);
    }
}

/**
 * 画像ファイルのアップロード処理
 */
function handle_upload($image_url, $category_name) {
    if (empty($_FILES['upfile']['name'])) {
        json_response(false, "ファイルが選択されていません");
    }

    // ファイル名生成
    // 日付＋ユニークID
    $image = date("Y-m-d-H-i-s-") . uniqid(mt_rand(), false);
    $ext = pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION);
    if (!$ext) {
        $ext = 'png'; // デフォルト拡張子
    }
    $image .= '.' . $ext;

    // 保存先ディレクトリ
    $images_dir = dirname(__DIR__, 2) . '/tmp_image/' . $category_name . '/';
    if (!is_dir($images_dir) && !mkdir($images_dir, 0777, true)) {
        json_response(false, "アップロード用ディレクトリ作成失敗");
    }

    $server_path = $images_dir . $image;

    if (!move_uploaded_file($_FILES['upfile']['tmp_name'], $server_path)) {
        json_response(false, "ファイルの保存に失敗しました");
    }

    // 古い画像削除
    if (!empty($image_url)) {
        $old_image_path = dirname(__DIR__, 2) . '/tmp_image/' . $image_url;
        if (file_exists($old_image_path) && is_file($old_image_path)) {
            unlink($old_image_path);

            // 空ディレクトリクリーン
            cleanup_dir_if_empty(dirname($old_image_path));
        }
    }

    // アップロード結果が画像かチェック
    if (!exif_imagetype($server_path)) {
        unlink($server_path);
        json_response(false, "画像ファイルではありません");
    }

    $bind_image_url = $category_name . '/' . $image;
    json_response(true, "画像をアップロードしました", ['image_url' => $bind_image_url]);
}

try {
    require_once '../db.php';
    $category_name = require_post_param('category_name');
    $image_url = $_POST['image_url'] ?? null;

    handle_upload($image_url, $category_name);

} catch (Exception $e) {
    json_response(false, "予期しないエラー: " . $e->getMessage());
}
