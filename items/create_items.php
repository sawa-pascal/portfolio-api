<?php
// Q: このCORSのプリフライト対応はdb.phpに含めず各API(ここ)で書いたほうがよいですか？理由は？
// 
// ⇒ 基本的には「各APIエンドポイントファイルの冒頭で」OPTIONS対応を書くのが安全です。
// 
// 【理由】
// - db.phpは「データベース接続共通」用途なので、単純にrequire_onceすればOPTIONSでも必ずdb接続やJSON出力/exit等が走る。
//   これだと「本体の業務処理」が不要なプリフライトリクエストにもDBへの無駄な接続や副作用が生じる。
// - もし全APIでCORSのOPTIONSだけを抜き出して一元化する場合、全ての入口で意図せず「exit」されてしまうなど困る場合がある。
// - 各APIファイルで書くことで、「POSTやGET、OPTIONSでやりたいこと」を個別に柔軟制御できる（APIによってプリフライトの必要要件や認可Headerなど異なる場合に柔軟）。
// 
// したがって、「CORSプリフライト用OPTIONSメソッドにだけ反応し、本体処理をスキップしてHTTP 200+必要なヘッダだけ返してexit」
// をこの create_items.php の最初で記述するのがベストプラクティスです。
$allowed_origins = [
    'http://localhost:4200',
    'http://localhost:8080',
    'http://localhost:54551',
];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (!empty($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
    
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

require_once '../db.php';

// エラー出力を抑制（JSONパースエラー回避のため）
error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * レスポンスをJSONで返して終了
 */
function json_response($success, $message, array $extra = []): void {
    $response = array_merge(['success' => $success, 'message' => $message], $extra);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit;
}

/**
 * JSON POST body のパラメータ取得 & 必須チェック
 */
function get_required_input(array $input, string $key) {
    if (empty($input[$key]) && $input[$key] !== "0") {
        json_response(false, "Invalid {$key}");
    }
    return $input[$key];
}

/**
 * tmp_imageディレクトリからimagesディレクトリへ画像ファイルを移動し、
 * データベース用の画像パス（category_name/filename）を返す
 *
 * @param string|null $image_url 一時画像のパス（"category_name/filename" 形式）
 * @return string|null 保存後の画像パス、または移動不要の場合は元の値
 */
function move_uploaded_image(?string $image_url): ?string {
    // image_urlが空、もしくは/が含まれていない・..が含まれている場合は安全のため何もしない
    if (!$image_url || strpos($image_url, '/') === false || strpos($image_url, '..') !== false) {
        return $image_url;
    }

    $document_root = dirname(__DIR__, 2);
    $tmp_image_path = $document_root . '/tmp_image/' . $image_url;

    // image_urlからカテゴリ名とファイル名を分離
    $parts = explode('/', $image_url, 2);
    if (count($parts) !== 2) return $image_url; // パースできなければそのまま返す

    $category_name = $parts[0];
    $basename = basename($image_url);
    $images_dir = $document_root . '/images/' . $category_name . '/';
    $new_image_path = $images_dir . $basename;
    $bind_image_url = $category_name . '/' . $basename;

    // 一時画像が存在する場合のみ移動処理実施
    if (file_exists($tmp_image_path)) {
        // 保存先ディレクトリが無いなら作成
        if (!is_dir($images_dir) && !mkdir($images_dir, 0777, true)) {
            json_response(false, "画像保存ディレクトリの作成に失敗しました");
        }
        // 画像ファイルをimagesへ移動
        if (!rename($tmp_image_path, $new_image_path)) {
            json_response(false, "画像の保存に失敗しました");
        }

        // tmp_image内のカテゴリディレクトリが空なら削除
        $tmp_category_dir = dirname($tmp_image_path);
        if (is_dir($tmp_category_dir) && count(glob($tmp_category_dir . '/*')) === 0) {
            rmdir($tmp_category_dir);
        }
        // 新しい画像パス（category/filename）を返す
        return $bind_image_url;
    }
    // 一時画像が存在しない場合は移動せずそのまま返す
    return $image_url;
}

try {
    $input = json_decode(file_get_contents("php://input"), true);

    $name = get_required_input($input, 'name');
    $price = get_required_input($input, 'price');
    $category_id = get_required_input($input, 'category_id');
    $description = $input['description'] ?? null;
    $image_url = $input['image_url'] ?? null;

    $image_url = move_uploaded_image($image_url);

    $stmt = $pdo->prepare(
        "INSERT INTO items (name, price, description, image_url, category_id) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $price, $description, $image_url, $category_id]);

    json_response(true, "商品を追加しました");
} catch (Exception $e) {
    json_response(false, "予期しないエラー: " . $e->getMessage());
}
