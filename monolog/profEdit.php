<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('プロフィール編集ページ');
debug('**************');
debugLogStart();

require('auth.php');

//画面処理
//DBからユーザーデータを取得
$dbFormData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'. print_r($dbFormData,true));

//POST送信があるとき
if(!empty($_POST)){
  debug('POST送信があります');
  debug('POST情報：'. print_r($_POST,true));
  debug('FILE情報：'. print_r($_FILES,true));

  //変数にユーザー情報を代入する
  $username = $_POST['username'];
  $gender = $_POST['gender'];
  $birth_y = (!empty($_POST['birth_y'])) ? $_POST['birth_y'] : 0;
  $birth_m = (!empty($_POST['birth_m'])) ? $_POST['birth_m'] : 0;
  $birth_d = (!empty($_POST['birth_d'])) ? $_POST['birth_d'] : 0;
  $email = $_POST['email'];
  $intro = $_POST['intro'];
  $pic = $_POST['pic'];

  //画像をアップロードしてパスを格納
  $pic = (!empty($_FILES['pic']['name'])) ? uploadImg($_FILES['pic'],'pic') : '';
  //画像をPOSTしていないが、すでに登録されている場合は保存されている画像のパスを入れる
  $pic = (empty($pic) && !empty($dbFormData['pic'])) ? $dbFormData['pic'] : $pic;

  //DBの情報と入力情報が異なる場合にバリデーションを行う

  //名前のチェック
  if($dbFormData['username'] !== $username){
    validMaxLen($username,'username',20);
    validRequired($username,'username');
  }
  //emailのチェック
  if($dbFormData['email'] !== $email){
    //最大文字数チェック
    validMaxLen($email,'email');
    if(empty($err_msg['email'])){
      //重複チェック
      validEmailDup($email,'email');
    }
    //形式チェック
    validEmail($email,'email');
    //未入力チェック
    validRequired($email,'email');
  }

  //生年月日のチェック
  if(empty($dbFormData['birth_y'])){
    $dbFormData['birth_y'] = 0;
    $dbFormData['birth_m'] = 0;
    $dbFormData['birth_d'] = 0;
  }

  //生年が異なっていればバリデーションを行う
  if($dbFormData['birth_y'] !== $birth_y){
    validNumber($birth_y,'birth_y');
    if(empty($err_msg['birth_y'])){
      if(strlen($birth_y) !== 4){
        $err_msg['birth_y'] = MSG16;
      }
    }
  }
  //生月が異なっていればバリデーションを行う
  if($dbFormData['birth_m'] !== $birth_m){
    validNumber($birth_m,'birth_m');
    if(empty($err_msg['birth_m'])){
      if(strlen($birth_m) > 2 || $birth_m > 12 || $birth_m == 0){
        $err_msg['birth_m'] = MSG17;
      }
    }
  }
  //生日が異なっていればバリデーションを行う
  if($dbFormData['birth_d'] !== $birth_d){
    validNumber($birth_d,'birth_d');
    if(empty($err_msg['birth_d'])){
      if(strlen($birth_d) > 2 || $birth_d > 31 || $birth_d == 0){
        $err_msg['birth_d'] = MSG18;
      }
    }
  }
  //月と日が1桁の場合は0をつける
  if(empty($err_msg['birth_m']) && empty($err_msg['birth_d'])){
    //月と日の桁数を取得する
    $birth_m_num = strlen($birth_m);
    $birth_d_num = strlen($birth_d);
    if($birth_m_num == 1){
      debug('月の桁数'.$birth_m_num);
      $birth_m = sprintf('%02d',$birth_m);
    }
    if($birth_d_num == 1){
      debug('日の桁数'.$birth_d_num);
      $birth_d = sprintf('%02d',$birth_d);
    }
  }
  //自己紹介の文字数チェック
  validMaxLen($intro,'intro',200);

  if(empty($err_msg)){
    debug('バリデーションOKです');

    try {
      $dbh = dbConnect();
      $sql = 'UPDATE users SET username = :u_name, gender = :gender, birth_y = :birth_y, birth_m = :birth_m, birth_d = :birth_d, email = :email, intro = :intro, pic = :pic WHERE id = :u_id';
      $data = array(':u_name' => $username , ':gender' => $gender , ':birth_y' => $birth_y , ':birth_m' => $birth_m , ':birth_d' => $birth_d , ':email' => $email , ':intro' => $intro , ':pic' => $pic , ':u_id' => $dbFormData['id']);

      $stmt = queryPost($dbh,$sql,$data);

      if($stmt){
        $_SESSION['msg_success'] = SUC02;
        debug('マイページへ遷移します');
        header("Location:mypage.php");
        exit();
      }
    } catch (Exception $e) {
      error_log('エラー発生：' . $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('---------画面表示処理終了---------');
?>

  <?php
    $siteTitle = 'プロフィール編集';
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
      <h2 class="title-form">プロフィール編集</h2>
      <form action="" method="post" enctype="multipart/form-data" class="form">
        <div class="area-msg">
          <?php
          if(!empty($err_msg['common'])) echo $err_msg['common'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['username'])) echo 'err' ?>">
          <div class="input_items">ニックネーム（20文字以内）<span class="icon-req">必須</span></div>
          <input type="text" name="username" value="<?php echo sanitize(getFormData('username')); ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['username'])) echo $err_msg['username'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['gender'])) echo 'err' ?>">
          <div class="input_items">性別</div>
          <div class="wrap-form-gender">
            <input type="radio" name="gender" value="男性" <?php if(getFormData('gender') === '男性'){ echo 'checked'; } ?> >男性
            <input type="radio" name="gender" value="女性" <?php if(getFormData('gender') === '女性'){ echo 'checked'; } ?>>女性
          </div>
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['gender'])) echo $err_msg['gender'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['birth_y']) || !empty($err_msg['birth_m']) || !empty($err_msg['birth_d'])) echo 'err' ?>">
          <div class="input_items">生年月日</div>
          <input type="text" name="birth_y" value="<?php if( !empty(getFormData('birth_y')) ){ echo getFormData('birth_y'); } ?>" class="form-birth" placeholder="例）1980"><span class="birth">年</span>
          <input type="text" name="birth_m" value="<?php if( !empty(getFormData('birth_m')) ){ echo getFormData('birth_m'); } ?>" class="form-birth-md" placeholder="12"><span class="birth">月</span>
          <input type="text" name="birth_d" value="<?php if( !empty(getFormData('birth_d')) ){ echo getFormData('birth_d'); } ?>" class="form-birth-md" placeholder="1"><span class="birth">日</span>
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['birth_y'])) echo $err_msg['birth_y'];
          ?>
        </div>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['birth_m'])) echo $err_msg['birth_m'];
          ?>
        </div>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['birth_d'])) echo $err_msg['birth_d'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['email'])) echo 'err' ?>">
          <div class="input_items">メールアドレス<span class="icon-req">必須</span></div>
          <input type="text" name="email" value="<?php echo sanitize(getFormData('email')); ?>">
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['email'])) echo $err_msg['email'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['intro'])) echo 'err'; ?>">
          <div class="input_items">自己紹介（200文字以内）</div>
          <textarea name="intro" cols="20" rows="10" id="js-count"><?php echo sanitize(getFormData('intro')); ?></textarea>
        </label>
        <p class="counter-text"><span id="js-count-view">0</span>/200文字</p>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['intro'])) echo $err_msg['intro'];
          ?>
        </div>
        <div class="input_items">プロフィール画像</div>
        <label class="img-drop <?php if(!empty($err_msg['pic'])) echo 'err'; ?>">
          <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
          <input type="file" name="pic" class="area-input-file">
          <img src="<?php echo getFormData('pic'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic'))) echo 'display:none;' ?>">
            ドラッグ＆ドロップ
        </label>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['pic'])) echo $err_msg['pic'];
          ?>
        </div>
        <div class="wrap-submit_btn">
          <input type="submit" value="登録する" class="btn-submit entry">
        </div>
      </form>
      <a href="mypage.php" class="link-back">マイページへ戻る</a>
    </section>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
