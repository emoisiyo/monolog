<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('ログインページ');
debug('**************');

debugLogStart();
//ログイン認証
require('auth.php');

if(!empty($_POST)){
  debug('POST送信があります');

  //変数にユーザー情報を代入
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  $pass_save = (!empty($_POST['pass_save'])) ? true : false;

  validEmail($email,'email');
  validMaxLen($email,'email');

  validHalf($pass,'pass');
  validMaxLen($pass,'pass');
  validMinLen($pass,'pass');

  validRequired($email,'email');
  validRequired($pass,'pass');

  if(empty($err_msg)){
    debug('バリデーションOKです');

    try {
      $dbh = dbConnect();
      $sql = 'SELECT password,id FROM users WHERE email = :email AND delete_flg = 0';
      $data = array(':email' => $email);

      $stmt = queryPost($dbh,$sql,$data);

      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      debug('クエリ結果の中身：'.print_r($result,true));

      //パスワードを照合する
      if(!empty($result) && password_verify($pass, array_shift($result))){
        debug('パスワードがマッチしました');

        //ログイン有効期限を設定する
        $sesLimit = 60*60;
        //最終ログインを現在日時にする
        $_SESSION['login_date'] = time();

        //ログイン保持にチェックがあるとき
        if($pass_save){
          debug('ログイン保持にチェックがあります');
          //ログイン有効期限を30日にする
          $_SESSION['login_limit'] = $sesLimit * 24 * 30;
        } else {
          debug('ログイン保持にチェックはありません');
          $_SESSION['login_limit'] = $sesLimit;
        }
        //ユーザーIDをセッション変数に格納
        $_SESSION['user_id'] = $result['id'];

        debug('セッション変数の中身：'. print_r($_SESSION,true));
        debug('マイページへ遷移します');
        header("Location:mypage.php");
        exit();
      } else {
        debug('パスワードがアンマッチです');
        $err_msg['common'] = MSG09;
      }
    } catch(Exception $e) {
      error_log('エラー発生：'. $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('---------画面表示処理終了---------');

?>

<?php
  $siteTitle = 'ログイン';
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
      <h2 class="title-form">ログイン</h2>
      <form action="" method="post" class="form">
        <div class="area-msg">
          <?php
          if(!empty($err_msg['common'])) echo $err_msg['common'];
          ?>
        </div>
        <label>
          <div class="input_items">メールアドレス</div>
          <input type="text" name="email" value="<?php if(!empty($_POST['email'])){ echo $_POST['email']; } ?>" class="<?php if(!empty($err_msg['email'])) echo 'err' ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['email'])) echo $err_msg['email'];
          ?>
        </div>
        <label>
          <div class="input_items">パスワード</div>
          <input type="password" name="pass" value="" class="<?php if(!empty($err_msg['pass'])) echo 'err' ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['pass'])) echo $err_msg['pass'];
          ?>
        </div>
        <label>
          <input type="checkbox" name="pass_save">次回ログインを省略する
        </label>
        <div class="wrap-submit_btn">
          <input type="submit" value="ログイン" class="btn-submit entry">
        </div>
        <a href="passRemindSend.php" class="link-back">パスワードをお忘れの方はこちら</a>
      </form>
    </section>
  </div>
  <!--フッター-->
  <?php
    require('footer.php');
  ?>
