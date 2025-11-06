<?php
/**
 * CORS設定
 */
$allowed_origins = [
    'http://localhost:4200',
    'http://localhost:8080',
    'http://localhost:54551'
];

// === CORS（クロスオリジンリソースシェアリング）に関するヘッダ設定 ===
//
// 1. 許可されたオリジンからのリクエストであれば「Access-Control-Allow-Origin」ヘッダを返す。
//    これにより、フロントエンド（例: Angularアプリ/localhost:4200 など）からAPIへのアクセスが許可される。
//    $_SERVER['HTTP_ORIGIN'] はリクエスト元のスキーム＋ホスト:ポートを指す。
//    in_array(..., $allowed_origins) で、事前に許可リストされているオリジンのみ通す。
//    オリジンが無効な場合はこのヘッダは付与しない（CORSエラーとなり、意図しない外部からはアクセスがブロックされる）。
if (!empty($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}

// 2. このAPIで許可するHTTPメソッドを指定（ここではGET, POST, OPTIONS）。
//    - GET/POST: 通常のAPIリクエスト。
//    - OPTIONS: CORSプリフライトリクエストで使われる。
//    これを明示しておくことで、フロントのブラウザが安心してこれらのメソッドを使える。
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// 3. APIアクセス時に送信できるリクエストヘッダを追加で許可する。
//    - デフォルト以外（例: Content-Type, Authorization）を使うには明示が必要。
//    例えばトークン認証やJSON送信時などで必要。
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 4. レスポンスのContent-Type（MIMEタイプ）をapplication/json; charset=utf-8で返す。
//    これで返却値がJSON形式であり、かつUTF-8エンコーディングだと明示する。
//    APIとフロント間のデータの取り扱いに誤解が生じにくくなる。
header('Content-Type: application/json; charset=utf-8');

/**
 * データベース接続情報
 */
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_NAME = 'stationery_shop';
const DB_HOST = 'localhost:3306';
const DB_DSN = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
    // 必要に応じて例外モードの設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // JSONでエラー返却
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB接続エラー: ' . $e->getMessage()
    ]);
    exit;
}