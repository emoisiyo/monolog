<?php
//================================
// ログ
//================================
//ログを取るか
ini_set('log_errors','on');
//ログの出力ファイルを指定
ini_set('error_log','error.log');

//================================
// デバッグ
//================================
//デバッグフラグ
$debug_flg = false;
//デバッグログ関数
function debug($str){
  global $debug_flg;
  if(!empty($debug_flg)){
    error_log('デバッグ：'.$str);
  }
}
//================================
// セッション準備・セッション有効期限を延ばす
//================================
//セッションファイルお置き場所を変更
session_save_path("/var/tmp/");
//ガーベージコレクションが削除するセッションの有効期限を設定
ini_set('session.gc_maxlifetime', 60*60*24*30);
//ブラウザを閉じても削除されないようにクッキーの有効期限を延ばす
ini_set('session.cookie_lifetime',60*60*24*30);
//セッションを使う
session_start();
//現在のセッションIDを新しく生成したものと書き換える
session_regenerate_id();

//================================
// 画面処理開始ログ書き出し関数
//================================
function debugLogStart(){
  debug('---------画面表示処理開始---------');
  debug('セッションID：'. session_id());
  debug('セッション変数の中身：'. print_r($_SESSION,true));
  debug('現在日時タイムスタンプ：'. time());
  if(!empty($_SESSION['login_date']) && !empty($_SESSION['login_limit'])){
    debug('ログイン期限日時タイムスタンプ：'.($_SESSION['login_limit'] + $_SESSION['login_limit']));
  }
}

//================================
// 定数（エラーメッセージ）
//================================
define('MSG01','入力必須です');
define('MSG02','Emailの形式で入力してください');
define('MSG03','パスワード（再入力）が合っていません');
define('MSG04','半角英数字のみご利用いただけます');
define('MSG05','6文字以上で入力してください');
define('MSG06','入力文字数がオーバーしています');
define('MSG07','エラーが発生しました。しばらく経ってからやり直してください');
define('MSG08','そのEmailはすでに登録されています');
define('MSG09','メールアドレスまたはパスワードが違います');
define('MSG10','古いパスワードが違います');
define('MSG11','古いパスワードと同じです');
define('MSG12','文字で入力してください');
define('MSG13','正しくありません');
define('MSG14','有効期限が切れています');
define('MSG15','半角数字のみご利用いただけます');
define('MSG16','生年は4桁で入力してください');
define('MSG17','生月は1桁または2桁で入力してください');
define('MSG18','生日は1桁または2桁で入力してください');
define('SUC01','パスワードを変更しました');
define('SUC02','プロフィールを変更しました');
define('SUC03','メールを送信しました');
define('SUC04','レビューを投稿しました');
define('SUC05','レビューを削除しました');
define('SUC06','コメントを書き込みました');

//================================
// グラーバル変数
//================================
//エラーメッセージ格納用の配列
$err_msg = array();

//================================
// バリデーション
//================================
//未入力チェック
function validRequired($str,$key){
  if(empty($str)){
    global $err_msg;
    $err_msg[$key] = MSG01;
  }
}
//Email形式チェック
function validEmail($str,$key){
  if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",$str)){
    debug('email形式ではありません');
    global $err_msg;
    $err_msg[$key] = MSG02;
  }
}
//Email重複チェック
function validEmailDup($email){
  global $err_msg;
  //DB接続
  try {
    $dbh = dbConnect();
    $sql = 'SELECT count(*) FROM users WHERE email = :email AND delete_flg = 0';
    $data = array(':email' => $email);
    $stmt = queryPost($dbh,$sql,$data);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!empty(array_shift($result))){
      $err_msg['email'] = MSG08;
    }
  } catch(Exception $e) {
    error_log('エラー発生' . $e->getMessage());
    $err_msg['common'] = MSG07;
    }
}
//パスワード同値チェック
function validMatch($str1,$str2,$key){
  if($str1 !== $str2){
    global $err_msg;
    $err_msg[$key] = MSG03;
  }
}
//最小文字数チェック
function validMinLen($str,$key,$min = 6){
  if(mb_strlen($str) < $min){
    global $err_msg;
    $err_msg[$key] = MSG05;
  }
}
//最大文字数チェック
function validMaxLen($str,$key,$max = 255){
  if(mb_strlen($str) > $max){
    global $err_msg;
    $err_msg[$key] = MSG06;
  }
}
//半角英数字チェック
function validHalf($str,$key){
  if(!preg_match("/^[a-zA-Z0-9]+$/",$str)){
    global $err_msg;
    $err_msg[$key] = MSG04;
  }
}
//半角数字チェック
function validNumber($str,$key){
  if(!preg_match("/^[0-9]+$/",$str)){
    global $err_msg;
    $err_msg[$key] = MSG15;
  }
}
//固定長チェック
function validLength($str,$key,$len = 8){
  if(mb_strlen($str) !== $len){
    global $err_msg;
    $err_msg[$key] = $len . MSG12;
  }
}
//パスワードチェック
function validPass($str,$key){
  //半角英数字チェック
  validHalf($str,$key);
  //最大文字数チェック
  validMaxLen($str,$key);
  //最小文字数チェック
  validMinLen($str,$key);
}
//セレクトボックスチェック
function validSelect($str,$key){
  if($str === 0){
    global $err_msg;
    $err_msg[$key] = MSG13;
  }
}
//================================
// ログイン認証
//================================
//未ログイン状態ではログインページに飛ばさず、falseを返す
function islogin(){
  if(!empty($_SESSION['login_date'])){
    debug('ログイン済みユーザーです');

    //現在日時が最終ログイン日時＋有効期限を超えていた場合
    if($_SESSION['login_date'] + $_SESSION['login_limit'] < time()){
      debug('ログイン有効期限オーバーです');

      //ログアウトさせる
      session_destroy();
      return false;
    } else {
      debug('ログイン有効期限内です');
      return true;
      }
  }else{
    debug('未ログインユーザーです。');
    return false;
    }
  }



//================================
// データベース
//================================
//DB接続関数
function dbConnect(){
  //DBへの接続準備
  $dsn = 'mysql:dbname=****;host=****;charset=utf8';
  $user = '********';
  $password = '********';
  $options = array(
    //SQL失敗時にはエラーコードのみ設定
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //デフォルトフェッチモードを連想配列形式に設定
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    //バッファードクエリを使う
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
  );
  //PDOオブジェクトを生成（DBへ接続）
  $dbh = new PDO($dsn,$user,$password,$options);
  return $dbh;
}
//SQL実行関数
function queryPost($dbh,$sql,$data){
  //クエリー作成
  $stmt = $dbh->prepare($sql);
  //プレースホルダに値をセットし、SQL文を実行
  if(!$stmt->execute($data)){
    debug('クエリに失敗しました');
    debug('失敗したSQL：'.print_r($stmt,true));
    $err_msg['common'] = MSG07;
    return 0;
  }
  debug('クエリ成功');
  return $stmt;
}
//ユーザー情報を取得
function getUser($u_id){
  debug('ユーザー情報を取得します');

  try {
    $dbh = dbConnect();
    $sql = 'SELECT id,username,gender,birth_y,birth_m,birth_d,email,intro,pic,password FROM users WHERE id = :u_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id);

    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else {
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//レビュー件数を取得
function getReviewNum($u_id){
  debug('レビューの数を取得します');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT id FROM review WHERE user_id = :u_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id);

    $stmt = queryPost($dbh,$sql,$data);
    $rst = $stmt->rowCount();
    debug('取得したレビューの数：'. $rst);

    return $rst;
  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//ユーザーの特定のレビュー情報を取得
function getReview($u_id,$r_id){
  debug('レビュー情報を取得します');
  debug('ユーザーID：'.$u_id);
  debug('レビューID：'.$r_id);

  try {
    $dbh = dbConnect();
    $sql = 'SELECT id,title,category_type,category_id,score,comment,pic1,pic2,pic3,user_id FROM review WHERE user_id = :u_id AND id = :r_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id, ':r_id' => $r_id);

    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}

//レビューを取得
function getReviewOne($r_id){
  debug('レビュー情報を取得します');
  debug('レビューID:'. $r_id);

  try {
    $dbh = dbConnect();
    $sql = 'SELECT r.id, r.title, r.category_type, r.category_id, r.score, r.comment, r.pic1, r.pic2, r.pic3, r.user_id, r.create_date, c.category_id, c.name
    FROM review AS r LEFT JOIN category AS c ON r.category_id = c.id WHERE r.id = :r_id AND r.delete_flg = 0 AND c.delete_flg = 0';
    $data = array(':r_id' => $r_id);

    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}

//カテゴリー（大項目）を取得
function getCategory(){
  debug('カテゴリー情報を取得します');

  try {
    $dbh = dbConnect();
    $sql = 'SELECT * FROM category';
    $data = array();

    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      return $stmt->fetchAll();
    }else{
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//IDでカテゴリー大項目）を取得
function getCategoryType($c_id){
  debug('カテゴリータイプを取得します');

  try {
    $dbh = dbConnect();
    $sql = 'SELECT category_id FROM category WHERE id = :id AND delete_flg = 0';
    $data = array(':id' => $c_id);

    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//該当レビューIDのコメントと投稿者データを取得
function getCommentAndUser($r_id){
  $dbh = dbConnect();
  $sql = 'SELECT c.review_id, c.from_user, c.comment, c.create_date, u.id, u.username, u.pic
  FROM comment AS c LEFT JOIN users AS u ON c.from_user = u.id WHERE c.review_id = :r_id AND c.delete_flg = 0 AND u.delete_flg = 0';
  $data = array(':r_id' => $r_id);

  $stmt = queryPost($dbh,$sql,$data);

  if($stmt){
    $result = $stmt->fetchAll();
    debug('取得したメッセージデータ'. print_r($result,true));
    return $result;
  }else{
    return false;
  }
}

//ページング用のレビュー件数とデータとカテゴリを取得
function getReviewList($currentMinNum = 1, $span){
  debug('レビュー件数を取得します');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT id FROM review';
    $data = array();

    $stmt = queryPost($dbh,$sql,$data);
    $result['total'] = $stmt->rowCount(); //総レコード数
    debug('総レコード数'.$result['total']);
    $result['total_page'] = ceil($result['total']/$span); //総ページ数
    debug('総ページ数'.$result['total_page']);
    if(!$stmt){
      return false;
    }
    //ページング用のSQL作成
    $sql = 'SELECT review.id,title,category_type,review.category_id,score,pic1,review.create_date,review.update_date,user_id,name,users.username,users.pic FROM review
    INNER JOIN category
    ON review.category_id = category.id
    INNER JOIN users
    ON review.user_id = users.id
    AND review.delete_flg = 0
    ORDER BY review.update_date DESC';
    $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
    $data = array();

    $stmt =  $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      //クエリ結果の全データレコードを格納
      debug('レビューデータ取得成功');
      $result['data'] = $stmt->fetchAll();
      return $result;
    }else{
      debug('レビューデータ取得失敗');
      return fasle;
    }
    } catch (Exception $e) {
      error_log('エラー発生：'. $e->getMessage());
  }
}

//ユーザーが投稿したレビューを全て取得
function getReviewAll($u_id,$currentMinNum = 1, $span){
  debug('レビュー情報を取得します');
  debug('ユーザーID：'.$u_id);

  try {
    $dbh = dbConnect();
    $sql = 'SELECT id FROM review WHERE user_id = :u_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id);

    $stmt = queryPost($dbh,$sql,$data);

    $result['total'] = $stmt->rowCount(); //総レコード数
    debug('総レコード数'.$result['total']);
    $result['total_page'] = ceil($result['total']/$span); //総ページ数
    debug('総ページ数'.$result['total_page']);
    if(!$stmt){
      return false;
    }
    //ページング用のSQL作成
    $sql = 'SELECT id,title,category_type,category_id,score,comment,pic1,user_id,create_date FROM review WHERE user_id = :u_id AND delete_flg = 0 ORDER BY create_date DESC';
    $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
    $data = array(':u_id' => $u_id);
    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      //クエリ結果の全データレコードを格納
      debug('レビューデータ取得成功');
      $result['data'] = $stmt->fetchAll();
      return $result;
    }else{
      debug('レビューデータ取得失敗');
      return fasle;
    }
  }catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//ユーザーのお気に入りを全て取得
function getFavoriteAll($u_id,$currentMinNum = 1, $span){
  debug('レビュー情報を取得します');
  debug('ユーザーID：'.$u_id);

  try {
    $dbh = dbConnect();
    $sql = 'SELECT id FROM favorite WHERE user_id = :u_id AND delete_flg = 0';
    $data = array(':u_id' => $u_id);

    $stmt = queryPost($dbh,$sql,$data);

    $result['total'] = $stmt->rowCount(); //総レコード数
    debug('総レコード数'.$result['total']);
    $result['total_page'] = ceil($result['total']/$span); //総ページ数
    debug('総ページ数'.$result['total_page']);
    if(!$stmt){
      return false;
    }
    //ページング用のSQL作成
    $sql = 'SELECT f.review_id,f.user_id,f.create_date,r.id,r.title,r.category_type,r.category_id,r.score,r.comment,r.pic1,r.user_id FROM favorite AS f INNER JOIN review AS r ON f.review_id = r.id WHERE f.user_id = :u_id AND f.delete_flg = 0 ORDER BY f.create_date DESC';
    $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
    $data = array(':u_id' => $u_id);
    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      //クエリ結果の全データレコードを格納
      debug('レビューデータ取得成功');
      $result['data'] = $stmt->fetchAll();
      return $result;
    }else{
      debug('レビューデータ取得失敗');
      return fasle;
    }
  }catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}

//カテゴリーごとにレビューを取得
function getReviewbyType($c_id,$currentMinNum = 1, $span,$ctype_flg){
  debug('レビュー情報を取得します');

  try {
    $dbh = dbConnect();
    //大項目を選択した場合と小項目を選択した場合でSQL文を切り替える
    if($ctype_flg){
      $sql = 'SELECT id FROM review WHERE category_type = :c_id AND delete_flg = 0';
    }else{
      $sql = 'SELECT id FROM review WHERE category_id = :c_id AND delete_flg = 0';
    }

    $data = array(':c_id' => $c_id);

    $stmt = queryPost($dbh,$sql,$data);

    $result['total'] = $stmt->rowCount(); //総レコード数
    debug('総レコード数'.$result['total']);
    $result['total_page'] = ceil($result['total']/$span); //総ページ数
    debug('総ページ数'.$result['total_page']);
    if(!$stmt){
      return false;
    }
    //ページング用のSQL作成
    if($ctype_flg){
      $sql = 'SELECT review.id,title,category_type,review.category_id,score,pic1,review.create_date,review.update_date,user_id,name,users.username,users.pic FROM review
      INNER JOIN category
      ON review.category_id = category.id
      INNER JOIN users
      ON review.user_id = users.id
      WHERE review.category_type = :c_id
      AND review.delete_flg = 0
      ORDER BY review.update_date DESC';
      $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
    }else{
      $sql = 'SELECT review.id,title,category_type,review.category_id,score,pic1,review.create_date,user_id,name,users.username,users.pic FROM review
      INNER JOIN category
      ON review.category_id = category.id
      INNER JOIN users
      ON review.user_id = users.id
      WHERE review.category_id = :c_id
      AND review.delete_flg = 0
      ORDER BY review.id DESC';
      $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
    }

    $data = array(':c_id' => $c_id);
    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      //クエリ結果の全データレコードを格納
      debug('レビューデータ取得成功');
      $result['data'] = $stmt->fetchAll();
      return $result;
    }else{
      debug('レビューデータ取得失敗');
      return fasle;
    }
  }catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//キーワード検索結果を取得
function searchWord($searchWord,$currentMinNum = 1, $span){
  debug($searchWord.'に該当するタイトルのレビューを取得します');

  try {
    $dbh = dbConnect();
    $sql = "SELECT id FROM review WHERE title LIKE '%$searchWord%' AND delete_flg = 0";
    $data = array();

    $stmt = queryPost($dbh,$sql,$data);

    $rst['total'] = $stmt->rowCount(); //総レコード数
    debug('総レコード数'.$rst['total']);
    $rst['total_page'] = ceil($rst['total']/$span); //総ページ数
    debug('総ページ数'.$rst['total_page']);
    if(!$stmt){
      debug('該当タイトルはありません');
      return false;
    }
    //ページング用のSQL作成
    $sql = "SELECT r.id,r.title,r.category_type,r.category_id,r.score,r.pic1,r.create_date,r.user_id,c.name,u.username,u.pic
    FROM review AS r
    INNER JOIN category AS c
    ON r.category_id = c.id
    INNER JOIN users AS u
    ON r.user_id = u.id
    WHERE r.title
    LIKE '%$searchWord%'
    AND r.delete_flg = 0
    ORDER BY r.id DESC";

    //総ページ数が2ページ以上の場合はページング処理
    if($rst['total_page'] > 1){
      $sql .= ' LIMIT '.$span.' OFFSET '.$currentMinNum;
    }
    $data = array();
    $stmt = queryPost($dbh,$sql,$data);

    if($stmt){
      //クエリ結果の全データレコードを格納
      debug('レビューデータ取得成功');
      $rst['data'] = $stmt->fetchAll();
      if(!empty($rst['data'])){
        debug('データあり');
      }else{
        debug('データなし');
      }
      return $rst;
    }else{
      debug('レビューデータ取得失敗');
      return fasle;
    }
  }catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}

//================================
// サニタイズ
//================================
function sanitize($str){
  return htmlspecialchars($str,ENT_QUOTES);
}

//================================
// フォーム入力保持
//================================
function getFormData($str,$flg = false){
  if($flg){
    $method = $_GET;
  }else{
    $method = $_POST;
  }
  global $dbFormData;
  //ユーザーデータがある場合
  if(!empty($dbFormData)){
    //フォームにエラーがあるとき
    if(!empty($err_msg[$str])){
      //送信されたデータがある場合
      if(isset($method[$str])){
        return sanitize($method[$str]);
      }else{
        return sanitize($dbFormData[$str]);
      }
    }else{
      //送信されたデータがあり、それがDBと違うとき
      if(isset($method[$str]) && $method[$str] !== $dbFormData[$str]){
        return sanitize($method[$str]);
      }else{
        return sanitize($dbFormData[$str]);
      }
    }
  }else{
    //ユーザーデータがない場合（初回画面表示も含む）
    if(isset($method[$str])){
      return sanitize($method[$str]);
    }
  }
}


//================================
// 画像アップロード
//================================
function uploadImg($file,$key){
  debug('画像アップロード処理開始');
  debug('FILE情報：'. print_r($file,true));

  if(isset($file['error']) && is_int($file['error'])){
    try {
      switch($file['error']){
        case UPLOAD_ERR_OK:
          break;
        case UPLOAD_ERR_NO_FILE:
          throw new RuntimeException('ファイルが選択されていません');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          throw new RuntimeException('ファイルサイズが大きすぎます');
        default:
          throw new RuntimeException('その他のエラーが発生しました');
      }

      $type = @exif_imagetype($file['tmp_name']);
      if(!in_array($type, [IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG], true)) {
        throw new RuntimeException('画像形式が未対応です');
      }
      $path = 'upload/'.sha1_file($file['tmp_name']).image_type_to_extension($type);
      if(!move_uploaded_file($file['tmp_name'],$path)){
        throw new RuntimeException('ファイル保存時にエラーが発生しました');
      }

      chmod($path, 0644);

      debug('ファイルは正常にアップロードされました');
      debug('ファイルパス：'.$path);
      return $path;

    } catch (RuntimeException $e) {
      debug($e->getMessage());
      global $err_msg;
      $err_msg[$key] = $e->getMessage();
    }
  }
}
//画像表示
function showImg($path){
  if(empty($path)){
    return 'image/no-image.png';
  }else{
    return $path;
  }
}
//================================
// メール送信
//================================
function sendMail($from,$to,$subject,$comment){
  if(!empty($to) && !empty($subject) && !empty($comment)){
    //文字化けしないよう設定
    mb_language("Japanese");  //現在使っている言語を設定する
    mb_internal_encoding("UTF-8");  //内部の日本語のエンコーディングを設定

    //メールを送信
    $result = mb_send_mail($to,$subject,$comment,"From:".$from);
    //送信結果を判定
    if($result){
      debug('メールを送信しました');
    }else{
      debug('【エラー発生】メールの送信に失敗しました');
    }
  }
}


//================================
// その他
//================================
function makeRandkey($length = 8){
  $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $str = '';
  for($i = 0; $i < $length; ++$i){
    $str .= $chars[mt_rand(0,61)];
  }
  return $str;
}
//エラーメッセージ表示
function getErrMsg($key){
  global $err_msg;
  if(!empty($err_msg[$key])){
    return $err_msg[$key];
  }
}
//カテゴリーラベル表示
function categoryLabel($c_id){
  switch($c_id){
    case '1':
      echo 'color-eat';
      break;
    case '2':
      echo 'color-read';
      break;
    case '3':
      echo 'color-watch';
      break;
    case '4':
     echo 'color-went';
     break;
    case '5':
      echo 'color-listen';
  }
}
//評価を表示
function showScore($score){
  $star = '';
  for($i = 0; $i < $score; $i++){
    $star .= '★';
  }
  for(; $i < 5; $i++){
    $star .= '☆';
  }
  return $star;
}
//お気に入り登録状況を取得
function isfavorite($u_id,$r_id){
  debug('お気に入り情報があるか確認します');
  debug('ユーザーID：'. $u_id);

  try {
    $dbh = dbConnect();
    $sql = 'SELECT id,review_id,user_id FROM favorite WHERE review_id = :r_id AND user_id = :u_id AND delete_flg = 0';
    $data = array(':r_id' => $r_id, ':u_id' => $u_id);

    $stmt = queryPost($dbh,$sql,$data);

    if($stmt->rowCount()){
      debug('お気に入り登録済みです');
      return true;
    }else{
      debug('お気に入り登録なしです');
      return false;
    }
  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
  }
}
//表示文字数を調整
function shapeText($str, $len = 20){
  if(mb_strlen($str) > $len){
    $rst = mb_substr($str,0,$len-1);
    return $rst.'…';
  }else{
    return $str;
  }
}
//ページング
//$currentPageNum：現在のページ数
//$totalPageNum：総ページ数
//$link：検索用GETパラメータリンク
//$pageColNum：ページネーション表示数（今回は5）
function pagenation($currentPageNum, $totalPageNum, $link = '', $pageColNum = 5){
  global $ctype_id;
  //現在のページが、総ページ数と同じ、かつ総ページ数が表示項目数以上なら、左にリンクを4個出す
  if($currentPageNum == $totalPageNum && $totalPageNum >= $pageColNum){
    $minPageNum = $currentPageNum - 4;
    $maxPageNum = $currentPageNum;
  //現在のページが、総ページ数の1ページ前なら、左に3、右に1個リンクを出す
  }elseif($currentPageNum == ($totalPageNum-1) && $totalPageNum >= $pageColNum){
    $minPageNum = $currentPageNum -3;
    $maxPageNum = $currentPageNum +1;
  //現在のページが2の場合は、左に1、右に3個リンクを出す
  }elseif($currentPageNum == 2 && $totalPageNum >= $pageColNum){
    $minPageNum = $currentPageNum -1;
    $maxPageNum = $currentPageNum +3;
  //現在のページが1の場合は左に何も出さないで右に5個出す
  }elseif ($currentPageNum == 1 && $totalPageNum >= $pageColNum) {
    $minPageNum = $currentPageNum;
    $maxPageNum = 5;
  //総ページ数が表示項目数より少ない場合は、総ページ数をループのMax、ループのMinを1に設定
  }elseif ($totalPageNum < $pageColNum) {
    $minPageNum = 1;
    $maxPageNum = $totalPageNum;
  //それ以外は左に2個出す
  }else{
    $minPageNum = $currentPageNum -2;
    $maxPageNum = $currentPageNum +2;
  }

  echo '<div class="pagenation">';
    echo '<ul class="pagenation-list">';
      if($currentPageNum != 1){
        echo '<li class="list-item"><a href="?p=1'.$link.'">&lt;</a></li>';
      }
      for($i = $minPageNum; $i <= $maxPageNum; $i++){
        echo '<li class="list-item ';
        if($currentPageNum == $i){ echo 'active';}
        echo '"><a href="?p='.$i.$link.$ctype_id.$link.'">'.$i.'</a></li>';
      }
      if($currentPageNum != $maxPageNum && $maxPageNum > 1){
        echo '<li class="list-item"><a href="?p='.$maxPageNum.$link.$ctype_id.$link.'">&gt;</a></li>';
      }
    echo '</ul>';
  echo '</div>';
}

//カテゴリー選択状況に応じてGETパラメータ付与
function getParamCtype($ctype_flg,$c_id){
  if(!empty($c_id)){
    if($ctype_flg){
      $param = '&c_t='.$c_id;
      return $param;
    }else{
      $param = '&c_id='.$c_id;
      return $param;
    }
  }else{
    debug('値は空です');
    return '';
  }
}
//マイページのリクエスト元に合わせてGETパラメータを変更する
function ChangeGetParam($p_id,$c_id,$key){
  global $requestUrl;
  if($requestUrl === 'index.php'){
    $param = '?p='.$p_id.$c_id;
    return $param;
  }else if($requestUrl === 'reviewDetail.php'){
    $param = '?p='.$p_id.'&r_id='.$key.$c_id;
    return $param;
  }
}
//セッションに保存された成功メッセージを1回だけ返す
function getSessionFlash($key){
  if(!empty($_SESSION[$key])){
    $data = $_SESSION[$key];
    $_SESSION[$key] = '';
    return $data;
  }
}





?>
