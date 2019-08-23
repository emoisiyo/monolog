<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('パスワード再発行メール送信ページ');
debug('**************');
debugLogStart();

//================================
// 画面処理
//================================
//POST送信されていたとき
if(!empty($_POST)){
  debug('POST送信があります');
  debug('POST情報：'. print_r($_POST,true));

  //変数にPOST情報を代入
  $email = $_POST['email'];

  //未入力チェック
  validRequired($email,'email');

  if(empty($err_msg)){
    debug('未入力チェックOK');

    //Emailの形式チェック
    validEmail($email,'email');
    //Emailの最大文字数チェック
    validMaxLen($email,'email');

    if(empty($err_msg)){
      debug('バリデーションOK');

      try {
        $dbh = dbConnect();
        $sql = 'SELECT count(id) FROM users WHERE email = :email AND delete_flg = 0';
        $data = array(':email' => $email);

        $stmt = queryPost($dbh,$sql,$data);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //EmailがDBに登録されているとき
        if($stmt && array_shift($result)){
          debug('クエリ成功。DB登録あり');
          //認証キー生成
          $auth_key = makeRandkey();

          //メールを送信する
          $from = 'cocholate.f@gmail.com';
          $to = $email;
          $subject = '【パスワード再発行認証】｜モノログ';

          $comment = <<<EOT
本メールアドレス宛にパスワード再発行のご依頼がありました。
下記のURLにて認証キーをご入力いただくと、パスワードが再発行されます。

パスワードの再発行認証キー入力ページ：http://localhost:8888/monolog/passRemindRecieve.php
認証キー：{$auth_key}
※認証キーの有効期限は30分です

認証キーを再発行されたい場合は、下記ページより再度発行をお願いいたします。
http://localhost:8888/monolog/passRemindSend.php

/////////////////////////////////////
モノログカスタマーセンター
URL http://localhost:8888/monolog.php
E-mail cocholate.f@gmail.com
/////////////////////////////////////
EOT;
          sendMail($from,$to,$subject,$comment);

          //認証に必要な情報をセッションへ保存
          $_SESSION['auth_key'] = $auth_key;
          $_SESSION['auth_email'] = $email;
          $_SESSION['auth_key_limit'] = time()+(60*30);   //現在時刻より30分後のUNIXタイムスタンプを入れる
          $_SESSION['msg_success'] = SUC03;
          debug('セッション変数の中身：'.print_r($_SESSION,true));

          header("Location:passRemindReceive.php");
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
  $siteTitle = 'パスワードをお忘れの方';
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
      <h2 class="title-form">パスワードをお忘れの方</h2>
      <form action="" method="post" class="form">
        <div class="area-msg">
          <?php
          if(!empty($err_msg['common'])) echo $err_msg['common'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>">
          <div class="input_items">メールアドレスを入力して下さい</div>
          <input type="text" name="email" value="<?php echo getFormData('email'); ?>" placeholder="ご登録のメールアドレス">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['email'])) echo $err_msg['email'];
          ?>
        </div>
        <span class="remind_info">ご登録されたメールアドレスに、パスワード再設定のご案内が届きます。</span>
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
