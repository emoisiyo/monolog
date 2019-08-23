<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('会員登録ページ');
debug('**************');
debugLogStart();


if(!empty($_POST)){

  //変数にユーザー情報を代入する
  $name = $_POST['name'];
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  $pass_re = $_POST['pass_re'];

  //未入力チェック
  validRequired($name,'name');
  validRequired($email,'email');
  validRequired($pass,'pass');
  validRequired($pass_re,'pass_re');

  if(empty($err_msg)){

    //Emaliの形式チェック
    validEmail($email,'email');
    //Emailの最大文字数チェック
    validMaxLen($email,'email');
    //Emailの重複チェック
    validEmailDup($email);

    //パスワードの半角英数字チェック
    validHalf($pass,'pass');
    //パスワードの最大文字数チェック
    validMaxLen($pass,'pass');
    //パスワードの最小文字数チェック
    validMinLen($pass,'pass');

    if(empty($err_msg)){
      //パスワードが再入力と一致しているかチェック
      validMatch($pass,$pass_re,'pass_re');

      if(empty($err_msg)){
        //バリデーションOKならばユーザー登録処理に入る
        try {
          $dbh = dbConnect();
          $sql = 'INSERT INTO users (username,email,password,login_time,create_date) VALUES(:username,:email,:pass,:login_time,:create_date)';
          $data = array(':username' => $name, ':email' => $email, ':pass' => password_hash($pass,PASSWORD_DEFAULT), ':login_time' => date('Y-m-d H:i:s'), ':create_date' => date('Y-m-d H:i:s'));
          $stmt = queryPost($dbh,$sql,$data);

          //クエリ成功の場合
          if($stmt){
            //ログイン有効期限を設定
            $sesLimit = 60*60;
            //最終ログイン時刻を現在時刻に
            $_SESSION['login_date'] = time();
            $_SESSION['login_limit'] = $sesLimit;
            //ユーザーIDを格納
            $_SESSION['user_id'] = $dbh->lastInsertID();

            debug('セッション変数の中身：'.print_r($_SESSION,true));

            header("Location:mypage.php");
            exit();
          }
        } catch(Exception $e) {
          error_log('エラー発生：'.$e->getMessage());
          $err_msg['common'] = MSG07;
        }
      }
    }
  }
}
?>

  <?php
    $siteTitle = '会員登録';
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
      <h2 class="title-form">会員登録</h2>
      <form action="" method="post" class="form">
        <div class="area-msg">
          <?php
          if(!empty($err_msg['common'])) echo $err_msg['common'];
          ?>
        </div>
        <label>
          <div class="input_items">ニックネーム<span class="icon-req">必須</span></div>
          <input type="text" name="name" value="<?php if(!empty($_POST['name'])) echo $_POST['name']; ?>" placeholder="例）モノログ太郎" class="<?php if(!empty($err_msg['name'])) echo 'err' ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['name'])) echo $err_msg['name'];
          ?>
        </div>
        <label>
          <div class="input_items">メールアドレス<span class="icon-req">必須</span></div>
          <input type="text" name="email" value="<?php if(!empty($_POST['email'])) echo $_POST['email']; ?>" placeholder="PC・携帯どちらも可" class="<?php if(!empty($err_msg['email'])) echo 'err' ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['email'])) echo $err_msg['email'];
          ?>
        </div>
        <label>
          <div class="input_items">パスワード<span class="icon-req">必須</span></div>
          <input type="password" name="pass" value="" placeholder="半角英数6文字以上" class="<?php if(!empty($err_msg['pass'])) echo 'err' ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['pass'])) echo $err_msg['pass'];
          ?>
        </div>
        <label>
          <div class="input_items">パスワード（確認）<span class="icon-req">必須</span></div>
          <input type="password" name="pass_re" value="" placeholder="半角英数6文字以上" class="<?php if(!empty($err_msg['pass_re'])) echo 'err' ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['pass_re'])) echo $err_msg['pass_re'];
          ?>
        </div>
        <div class="wrap-submit_btn">
          <input type="submit" value="登録する" class="btn-submit entry">
        </div>
      </form>
    </section>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
