<?php
//共通関数読み込み
require('function.php');

debug('**************');
debug('パスワード再発行認証キー入力ページ');
debug('**************');
debugLogStart();

//セッションに認証キーがあるか確認。なければリダイレクト
if(empty($_SESSION['auth_key'])){
  header("Location:passRemindSend.php");  //認証キー送信ページへ遷移
  exit();
}

//================================
// 画面処理
//================================
//POST送信されていたとき
if(!empty($_POST)){
  debug('POST送信があります');
  debug('POST情報：'. print_r($_POST,true));

  //変数に認証キーを代入
  $auth_key = $_POST['token'];

  //未入力チェック
  validRequired($auth_key,'token');

  if(empty($err_msg)){
    debug('未入力チェックOK');

    //固定長チェック
    validLength($auth_key,'token');
    //半角チェック
    validHalf($auth_key,'token');

    if(empty($err_msg)){
      debug('バリデーションOK');

      if($auth_key !== $_SESSION['auth_key']){
        $err_msg['common'] = MSG13;
      }
      if(time() > $_SESSION['auth_key_limit']){
        $err_msg['common'] = MSG14;
      }
      if(empty($err_msg)){
        debug('認証OK');

        //パスワードを生成する
        $pass = makeRandkey();

        try {
          $dbh = dbConnect();
          $sql = 'UPDATE users SET password = :pass WHERE email = :email AND delete_flg = 0';
          $data = array(':email' => $_SESSION['auth_email'], ':pass' => password_hash($pass, PASSWORD_DEFAULT));

          $stmt = queryPost($dbh,$sql,$data);

          if($stmt){
            debug('クエリ成功');
            //メール送信
            $from = 'cocholate.f@gmail.com';
            $to = $_SESSION['auth_email'];
            $subject = '【パスワード再発行完了】｜モノログ';

            $comment = <<<EOF
本メールアドレス宛にパスワードの再発行をいたしました。
下記のURLにて再発行パスワードをご入力いただき、ログインお願いいたします。

ログインページ：http://localhost:8888/monolog/login.php
再発行パスワード：{$pass}
※ログイン後、すみやかにパスワードのご変更をお願いいたします。

/////////////////////////////////////
モノログカスタマーセンター
URL http://localhost:8888/monolog.php
E-mail cocholate.f@gmail.com
/////////////////////////////////////
EOF;
            sendMail($from,$to,$subject,$comment);

            //セッション削除
            session_unset();
            $_SESSION['msg_success'] = SUC01;
            debug('セッション変数の中身：'.print_r($_SESSION,true));

            header("Location:login.php");
          }else{
            debug('クエリに失敗しました');
            $err_msg['common'] = MSG07;
          }

        } catch (Exception $e) {
          error_log('エラー発生：'. $e->getMessage());
          $err_msg['common'] = MSG07;
        }
      }
    }
  }
}
debug('---------画面表示処理終了---------');
?>
<?php
  $siteTitle = '認証キーを入力してください';
  require('head.php');
?>
<body>
<!-- ヘッダー -->
<?php
  require('header.php');
?>
<!--成功メッサージ -->
<p id="js-show-msg" class="msg-slide">
  <?php echo getSessionFlash('msg_success'); ?>
</p>
<!-- メインコンテンツ -->
  <div class="container-contents">
    <section class="wrap-form">
      <h2 class="title-form">認証キーを入力してください</h2>
      <form action="" method="post" class="form">
        <div class="area-msg">
          <?php
          if(!empty($err_msg['common'])) echo $err_msg['common'];
          ?>
        </div>
        <label>
          <div class="input_items">認証キー</div>
          <input type="text" name="token" value="<?php echo getFormData('token'); ?>" >
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['token'])) echo $err_msg['token'];
          ?>
        </div>
        <span class="remind_info">ご登録されたメールアドレスに届いた、認証キーを入力してください。</span>
        <div class="wrap-submit_btn">
          <input type="submit" value="送信する" class="btn-submit entry">
        </div>
      </form>
    </section>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
