<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('パスワード変更ページ');
debug('**************');
debugLogStart();

require('auth.php');


//================================
// 画面処理
//================================
//DBからユーザー情報を取得
$userData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'. print_r($userData,true));

//POST送信されたとき
if(!empty($_POST)){
  debug('POST送信があります');
  debug('POST情報：'. print_r($_POST,true));

  //変数にユーザー情報を代入
  $pass_old = $_POST['pass_old'];
  $pass_new = $_POST['pass_new'];
  $pass_new_re = $_POST['pass_new_re'];

  //未入力チェック
  validRequired($pass_old,'pass_old');
  validRequired($pass_new,'pass_new');
  validRequired($pass_new_re,'pass_new_re');

  if(empty($err_msg)){
    debug('未入力チェックOK');

    //古いパスワードのチェック
    validPass($pass_old,'pass_old');
    //新しいパスワードのチェック
    validPass($pass_new,'pass_new');

    //古いパスワードとDBのパスワードを照合
    if(!password_verify($pass_old,$userData['password'])){
      $err_msg['pass_old'] = MSG10;
    }
    //新しいパスワードが現在のパスワードと同じでないかチェック
    if($pass_old === $pass_new){
      $err_msg['pass_new'] = MSG11;
    }
    //パスワードが再入力とあっているかチェック
    validMatch($pass_new,$pass_new_re,'pass_new_re');

    if(empty($err_msg)){
      debug('バリデーションOK');

      try {
        $dbh = dbConnect();
        $sql = 'UPDATE users SET password = :pass WHERE id = :id';
        $data = array(':id' => $_SESSION['user_id'], ':pass' => password_hash($pass_new, PASSWORD_DEFAULT));

        $stmt = queryPost($dbh,$sql,$data);

        if($stmt){
          //メールを送信
          $username = ($userData['username']) ? $userData['username'] : 'お客';
          $from = 'cocholate.f@gmail.com';
          $to = $userData['email'];
          $subject = 'パスワード変更のお知らせ｜モノログ';

          $comment = <<<EOF
{$username}　様
パスワードの変更が完了しました。

/////////////////////////////////////
モノログカスタマーセンター
URL http://localhost:8888/monolog.php
E-mail cocholate.f@gmail.com
/////////////////////////////////////
EOF;
          sendMail($from,$to,$subject,$comment);

          header("Location:mypage.php");
          exit();
        }

      } catch (Exception $e) {
        error_log('エラー発生：'. $e->getMessage());
        $err_msg['common'] = MSG07;
      }
    }
  }
}
debug('---------画面表示処理終了---------');
?>

<?php
  $siteTitle = 'パスワード変更';
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
      <h2 class="title-form">パスワード変更</h2>
      <form action="" method="post" class="form">
        <div class="area-msg">
          <?php
          getErrMsg('common');
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['pass_old'])) echo 'err'; ?>">
          <div class="input_items">現在のパスワード</div>
          <input type="password" name="pass_old" value="<?php echo getFormData('pass_old') ?>">
        </label>
        <div class="area-msg">
          <?php
          echo getErrMsg('pass_old');
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['pass_new'])) echo 'err'; ?>">
          <div class="input_items">新しいパスワード</div>
          <input type="password" name="pass_new" value="<?php echo getFormData('pass_new') ?>" placeholder="半角英数6文字以上">
        </label>
        <div class="area-msg">
          <?php
          echo getErrMsg('pass_new');
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['pass_new_re'])) echo 'err'; ?>">
          <div class="input_items">新しいパスワード（確認）</div>
          <input type="password" name="pass_new_re" value="<?php echo getFormData('pass_new_re') ?>" placeholder="半角英数6文字以上">
        </label>
        <div class="area-msg">
          <?php
          echo getErrMsg('pass_new_re');
          ?>
        </div>
        <div class="wrap-submit_btn">
          <input type="submit" value="変更する" class="btn-submit change">
        </div>
        <a href="mypage.php" class="link-back">マイページへ戻る</a>
      </form>
    </section>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
