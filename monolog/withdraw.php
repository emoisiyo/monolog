<?php
//共通関数読み込み
require('function.php');

debug('**************');
debug('退会ページ');
debug('**************');
debugLogStart();

require('auth.php');


//================================
// 画面処理
//================================
//POST送信されたとき
if(!empty($_POST)){
  debug('POST送信があります');

  try {
    $dbh = dbConnect();
    $sql1 = 'UPDATE users SET delete_flg = 1 WHERE id = :u_id;';
    $sql2 = 'UPDATE review SET delete_flg = 1 WHERE user_id = :u_id;';
    $sql3 = 'UPDATE favorite SET delete_flg = 1 WHERE user_id = :u_id;';
    $sql4 = 'UPDATE comment SET delete_flg = 1 WHERE from_user = :u_id;';
    $data = array(':u_id' => $_SESSION['user_id']);

    $stmt1 = queryPost($dbh, $sql1, $data);
    $stmt2 = queryPost($dbh, $sql2, $data);
    $stmt3 = queryPost($dbh, $sql3, $data);
    $stmt4 = queryPost($dbh, $sql4, $data);

    //クエリ成功の場合（usersテーブルのみ削除されていればOKと判断）
    if($stmt1){
      //セッション削除
      session_destroy();
      debug('セッション変数の中身：'. print_r($_SESSION,true));
      debug('トップページへ遷移します');
      header("Location:index.php");
    }else{
      debug('クエリが失敗しました');
      $err_msg['common'] = MSG07;
    }

  } catch (Exception $e) {
    error_log('エラー発生：'. $e->getMessage());
    $err_msg['common'] = MSG07;
  }
}
debug('---------画面表示処理終了---------');
?>

<?php
  $siteTitle = '退会する';
  require('head.php');
?>
<body>
<!-- ヘッダー -->
<?php
  require('header.php');
?>
<!-- メインコンテンツ -->
  <div class="container-contents">
    <section class="wrap-form">
      <h2 class="title-form">退会する</h2>
      <form action="" method="post" class="form">
        <div class="area-msg">
          <?php echo getErrMsg('common'); ?>
        </div>
        <span class="remind_info">退会するとそれまでに投稿した全てのレビュー、コメントが削除されます。よろしければ退会ボタンを押してください。</span>
        <div class="wrap-submit_btn">
          <input type="submit" name="submit" value="退会する" class="btn-submit change">
        </div>
        <a href="mypage.php" class="link-back">マイページへ戻る</a>
      </form>
    </section>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
