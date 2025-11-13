<?php
require_once '../db.php';

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

/**
 * カテゴリ名をカテゴリIDから取得
 */
function fetch_category_name(PDO $pdo, $category_id) {
    $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
    $stmt->execute([$category_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['name'] : null;
}

/**
 * パスからファイル名部分だけを取得
 */
function extract_file_name($path) {
    // 正規表現 '#^[^/]+/#' の解説:
    // # ... # で囲まれた部分が正規表現パターンです。区切り文字は / ではなく # です。
    // ^   ... 文字列の先頭を意味します。
    // [^/]+ ... スラッシュ以外の文字が1文字以上（1つ以上連続）という意味です。
    // /   ... リテラルのスラッシュ（区切り用のスラッシュ）。
    // だから、最初の「スラッシュで区切られるまでの文字列＋スラッシュ」を意味します。
    // 例: "category_name/filename.png" → "filename.png" になります。
    return preg_replace('#^[^/]+/#', '', $path);
}

/**
 * ディレクトリが空なら削除
 */
function try_remove_empty_dir($dir) {
    if (is_dir($dir) && count(glob($dir . '/*')) === 0) {
        rmdir($dir);
    }
}

/**
 * 画像の移動とクリーンアップ処理
 * - image_urlが一時(tmp_image)にある場合は新カテゴリ名でimagesへ移動
 * - そうでなくてもカテゴリ変更時はimages配下でのパス変更（移動）を行う
 */
function handle_image_move(&$image_url, $category_id, $origin_image_url, PDO $pdo) {
    if (empty($image_url)) return;

    $category_name = fetch_category_name($pdo, $category_id);
    if (!$category_name) json_response(false, "Category not found");

    $root_dir = dirname(__DIR__, 2);
    $tmp_image_dir = $root_dir . '/tmp_image/';
    $dest_image_dir = $root_dir . '/images/';

    $image_file = extract_file_name($image_url);
    $new_rel_path = $category_name . '/' . $image_file;
    $new_full_path = $dest_image_dir . $new_rel_path;
    $new_parent = dirname($new_full_path);

    // 画像がtmp_imageディレクトリにある場合は今まで通りtmp→images移動
    $old_tmp_path = $tmp_image_dir . $image_url;
    if (file_exists($old_tmp_path) && is_file($old_tmp_path)) {
        // 画像保存先のカテゴリディレクトリがなければ作る
        if (!is_dir($new_parent) && !mkdir($new_parent, 0777, true)) {
            json_response(false, "画像ディレクトリの作成に失敗しました");
        }
        if (!rename($old_tmp_path, $new_full_path)) {
            json_response(false, "画像のパス移動に失敗しました");
        }

        // 古い画像の削除（origin_image_urlと異なる場合）
        if (!empty($origin_image_url)) {
            $old_image_for_delete = $dest_image_dir . $origin_image_url;
            if (file_exists($old_image_for_delete) && is_file($old_image_for_delete)) {
                unlink($old_image_for_delete);
                try_remove_empty_dir(dirname($old_image_for_delete));
            }
        }
        try_remove_empty_dir(dirname($old_tmp_path));
        $image_url = $new_rel_path;
    } else {
        // 画像がimagesディレクトリ内の古いカテゴリにある場合（パス上 category_id 変更時）、categoryが変わったかどうかチェック
        $prev_category = strpos($image_url, '/') !== false ? explode('/', $image_url)[0] : '';
        if ($prev_category && $prev_category !== $category_name) {
            $old_image_path = $dest_image_dir . $image_url;
            // 古い画像ファイル(同一名)が既に新カテゴリに無ければ移動
            if (file_exists($old_image_path) && is_file($old_image_path)) {
                if (!is_dir($new_parent) && !mkdir($new_parent, 0777, true)) {
                    json_response(false, "画像ディレクトリの作成に失敗しました");
                }
                if (!rename($old_image_path, $new_full_path)) {
                    json_response(false, "画像のカテゴリ間移動に失敗しました");
                }
                try_remove_empty_dir(dirname($old_image_path));
                $image_url = $new_rel_path;
            } else {
                // 万一存在しなければ参照パスのみ変更
                $image_url = $new_rel_path;
            }
            // origin_image_url（古い画像）は自分自身なので、削除不要。上記で十分
        }
        // 同じカテゴリ内 or 既に新しいカテゴリ名の場合はパス変更不要（image_urlそのまま）
    }
}

// -------- メイン実行 --------
$input = json_decode(file_get_contents("php://input"), true);

try {
    // 必須パラメータ取得
    $id             = get_required($input, 'id');
    $name           = get_required($input, 'name');
    $price          = get_required($input, 'price');
    $stock          = get_required($input, 'stock');
    $category_id    = get_required($input, 'category_id');
    $description    = isset($input['description']) ? $input['description'] : '';
    $image_url      = isset($input['image_url']) ? $input['image_url'] : '';
    $origin_image_url = isset($input['origin_image_url']) ? $input['origin_image_url'] : '';

    // 画像処理（必要に応じて画像移動・パス調整）
    if (!empty($image_url)) {
        handle_image_move($image_url, $category_id, $origin_image_url, $pdo);
    }

    // トランザクション開始
    $pdo->beginTransaction();

    try {
        // itemsテーブル更新
        $stmt = $pdo->prepare("
            UPDATE items 
            SET name = ?, price = ?, description = ?, image_url = ?, category_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $price, $description, $image_url, $category_id, $id]);

        // stocksテーブル更新
        if ($stock !== null) {
            $stmt_stocks = $pdo->prepare("UPDATE stocks SET quantity = ? WHERE item_id = ?");
            $stmt_stocks->execute([$stock, $id]);
        }

        $pdo->commit();
        json_response(true, "商品を更新しました");
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(false, "更新に失敗しました: " . $e->getMessage());
    }
} catch (Exception $e) {
    json_response(false, "更新に失敗しました: " . $e->getMessage());
}
