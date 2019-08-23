<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('レビュー投稿ページ');
debug('**************');
debugLogStart();

require('auth.php');

//================================
// 画面処理
//================================

// 画面表示用データ取得
//================================
//GETデータを格納
$r_id = (!empty($_GET['r_id'])) ? $_GET['r_id'] : '';
//現在ページを格納
$p = $_GET['p'];
//DBからレビューデータを取得
$dbFormData = (!empty($r_id)) ? getReview($_SESSION['user_id'],$r_id) : '';
//新規登録画面か編集画面か判別用フラグ
$edit_flg = (empty($dbFormData)) ? false : true;
//DBからカテゴリーデータを取得
$dbCategoryData = getCategory();

debug('レビューID：'. $r_id);
debug('フォーム用DBデータ：'. print_r($dbFormData,true));
debug('カテゴリーデータ：'. print_r($dbCategoryData,true));

//================================
// パラメータ改ざんチェック
//================================
//存在しないレビューIDをGET送信してきたときはマイページへ遷移させる
if(!empty($r_id) && empty($dbFormData)){
  debug('存在しないレビューIDが送信されました。マイページへ遷移します');
  header("Location:mypage.php");
  exit();
}

//================================
// POST送信されたら
//================================
if(!empty($_POST)){
  debug('POST送信があります');
  debug('POST情報：'. print_r($_POST,true));
  debug('FILE情報：'. print_r($_FILES,true));

  if(!empty($_POST['delete'])){
    debug('レビューを削除します');
    try {
      $dbh = dbConnect();
      $sql = 'UPDATE review SET delete_flg = 1 WHERE id = :r_id';
      $data = array(':r_id' => $r_id);

      $stmt = queryPost($dbh,$sql,$data);
      if($stmt){
        debug('レビューを削除しました。投稿履歴ページに遷移します');
        $_SESSION['msg_success'] = SUC05;
        debug('セッション変数の中身：'.print_r($_SESSION,true));
        header("Location:reviewRecord.php");
        exit();
      }else{
        debug('クエリに失敗しました');
        $err_msg['common'] = MSG07;
      }
    } catch (Exception $e) {
      error_log('エラー発生：'. $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }


  //変数にユーザー情報を代入する
  $title = $_POST['title'];
  $category_id = $_POST['category_id'];
  $score = $_POST['score'];
  $comment = $_POST['comment'];
  //カテゴリータイプを取得
  debug('POSTされたカテゴリーID：'. $category_id);
  $category_type = getCategoryType($category_id);
  $category_type = $category_type['category_id'];

  debug('取得したカテゴリータイプ：'. $category_type);

  //画像をアップロードしてパスを変数に格納
  $pic1 = (!empty($_FILES['pic1']['name'])) ? uploadImg($_FILES['pic1'],'pic1') : '';
  //すでに画像の登録はあるが、初回画面表示時や画像をPOSTしていない時のためにDBにある画像パスを入れる
  $pic1 = (empty($pic1) && !empty($dbFormData['pic1'])) ? $dbFormData['pic1'] : $pic1;
  $pic2 = (!empty($_FILES['pic2']['name'])) ? uploadImg($_FILES['pic2'],'pic2') : '';
  $pic2 = (empty($pic2) && !empty($dbFormData['pic2'])) ? $dbFormData['pic2'] : $pic2;
  $pic3 = (!empty($_FILES['pic3']['name'])) ? uploadImg($_FILES['pic3'],'pic3') : '';
  $pic3 = (empty($pic3) && !empty($dbFormData['pic3'])) ? $dbFormData['pic3'] : $pic3;

  //編集の場合はDBの情報と入力情報が異なる場合にバリデーションを行う
  if(empty($dbFormData)){
    debug('新規レビューバリデーションチェックします');
    //未入力チェック
    validRequired($title,'title');
    //タイトルが50文字オーバーしていないか
    validMaxLen($title,'title',50);
    //セレクトボックスチェック（カテゴリーが選択されているか）
    validSelect($category_id,'category_id');
    //セレクトボックスチェック（評価が選択されているか）
    validSelect($score,'score');
    //コメントが200文字オーバーしていないか
    validMaxLen($comment,'comment',200);
  }else{
    debug('投稿済みレビュー編集のためバリデーションチェックします');
    if($dbFormData['title'] !== $title){
      validRequired($title,'title');
      //タイトルが50文字オーバーしていないか
      validMaxLen($title,'title',50);
    }
    if($dbFormData['category_id'] !== $category_id){
      validSelect($category_id,'category_id');
    }
    if($dbFormData['score'] !== $score){
      validSelect($score,'score');
    }
    if($dbFormData['comment'] !== $comment){
      validMaxLen($comment,'comment',200);
    }
  }

  if(empty($err_msg)){
    debug('バリデーションOKです');

    try {
      $dbh = dbConnect();
      //編集画面の場合はUPDATE文、新規投稿画面の場合はINSERT文を作成
      if($edit_flg){
        debug('レビューを更新します');
        $sql = 'UPDATE review SET title = :title, category_type = :category_type ,category_id = :category_id, score = :score, comment = :comment, pic1 = :pic1, pic2 = :pic2, pic3 = :pic3 WHERE user_id = :u_id AND id = :r_id';
        $data = array(':title' => $title, ':category_type' => $category_type, ':category_id' => $category_id, ':score' => $score, ':comment' => $comment, ':pic1' => $pic1, ':pic2' => $pic2, ':pic3' => $pic3, ':u_id' => $_SESSION['user_id'], ':r_id' => $r_id);
      }else{
        debug('レビュー新規投稿です');
        $sql = 'INSERT INTO review (title, category_type, category_id, score, comment, pic1, pic2, pic3, user_id, create_date) VALUES (:title, :category_type, :category_id, :score, :comment, :pic1, :pic2, :pic3, :u_id, :n_date)';
        $data = array(':title' => $title, ':category_type' => $category_type, ':category_id' => $category_id, ':score' => $score, ':comment' => $comment, ':pic1' => $pic1, ':pic2' => $pic2, ':pic3' => $pic3, ':u_id' => $_SESSION['user_id'], ':n_date' => date('Y-m-d H:i:s'));
      }
      debug('SQL:'. $sql);
      debug('流し込みデータ：'. print_r($data,true));

      $stmt = queryPost($dbh,$sql,$data);

      if($stmt){
        debug('マイページへ遷移します');
        $_SESSION['msg_success'] = SUC04;
        header("Location:mypage.php");
        exit();
      }

    } catch (Exception $e) {
      error_log('エラー発生：'. $e->getMessage());
      $err_msg['common'] = MSG07;
    }
  }
}
debug('---------画面表示処理終了---------');
?>

  <?php
    $siteTitle = (!$edit_flg) ? 'レビュー投稿' : 'レビュー編集';
    require('head.php');
  ?>
<body>
  <!-- ヘッダー -->
  <?php
    require('header.php');
  ?>
  <!-- メインコンテンツ -->
  <div class="container-contents edit_review">
    <section class="wrap-form edit_review">
      <h2 class="title-form"><?php echo (!$edit_flg) ? 'レビューを投稿する' : 'レビューを編集する'; ?></h2>
      <form action="" method="post" enctype="multipart/form-data" class="form">
        <div class="area-msg">
          <?php
          if(!empty($err_msg['common'])) echo $err_msg['common'];
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['title'])) echo 'err' ?>">
          <div class="input_items">タイトル（50文字以内）<span class="icon-req">必須</span></div>
          <input type="text" name="title" value="<?php echo sanitize(getFormData('title')); ?>">
        </label>
        <div class="area-msg">
          <?php
          echo getErrMsg('title');
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['category_id'])) echo 'err' ?>">
          <div class="input_items">カテゴリー<span class="icon-req">必須</span></div>
          <select name="category_id">
            <option value="0" <?php if(getFormData('category_id') == 0){ echo 'selected';} ?> >選択してください</option>
            <?php
              foreach($dbCategoryData as $key => $val){
            ?>
              <option value="<?php echo $val['id'] ?>" <?php if(getFormData('category_id') == $val['id']){ echo 'selected'; } ?> >
                <?php echo $val['name']; ?>
              </option>
            <?php
              }
            ?>
          </select>
        </label>
        <div class="area-msg">
          <?php
          echo getErrMsg('category_id');
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['score'])) echo 'err' ?>">
          <div class="input_items">評価<span class="icon-req">必須</span></div>
          <select name="score">
            <option value="0" <?php if(getFormData('score') == 0){ echo 'selected';} ?> >選択してください</option>
            <option value="1" <?php if(getFormData('score') == 1){ echo 'selected';} ?> >★☆☆☆☆</option>
            <option value="2" <?php if(getFormData('score') == 2){ echo 'selected';} ?> >★★☆☆☆</option>
            <option value="3" <?php if(getFormData('score') == 3){ echo 'selected';} ?> >★★★☆☆</option>
            <option value="4" <?php if(getFormData('score') == 4){ echo 'selected';} ?> >★★★★☆</option>
            <option value="5" <?php if(getFormData('score') == 5){ echo 'selected';} ?> >★★★★★</option>
          </select>
        </label>
        <div class="area-msg">
          <?php
          echo getErrMsg('score');
          ?>
        </div>
        <label class="<?php if(!empty($err_msg['comment'])) echo 'err'; ?>">
          <div class="input_items">コメント（200文字以内）</div>
          <textarea name="comment" cols="20" rows="10" id="js-count" class="edit_review"><?php echo sanitize(getFormData('comment')); ?></textarea>
        </label>
        <p class="counter-text"><span id="js-count-view">0</span>/200文字</p>
        <div class="area-msg">
          <?php
          if(!empty($err_msg['comment'])) echo $err_msg['comment'];
          ?>
        </div>
        <div style="overflow:hidden;">
          <div class="wrap-area-imgdrop edit_review">
            画像1
            <label class="img-drop <?php if(!empty($err_msg['pic1'])) echo 'err'; ?>">
              <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
              <input type="file" name="pic1" class="area-input-file">
              <img src="<?php echo getFormData('pic1'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic1'))) echo 'display:none;' ?>">
                ドラッグ＆ドロップ
            </label>
            <div class="area-msg">
              <?php
              echo getErrMsg('pic1');
              ?>
            </div>
          </div>
          <div class="wrap-area-imgdrop edit_review">
            画像2
            <label class="img-drop <?php if(!empty($err_msg['pic2'])) echo 'err'; ?>">
              <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
              <input type="file" name="pic2" class="area-input-file">
              <img src="<?php echo getFormData('pic2'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic2'))) echo 'display:none;' ?>">
                ドラッグ＆ドロップ
            </label>
            <div class="area-msg">
              <?php
              echo getErrMsg('pic2');
              ?>
            </div>
          </div>
          <div class="wrap-area-imgdrop edit_review">
            画像3
            <label class="img-drop <?php if(!empty($err_msg['pic3'])) echo 'err'; ?>">
              <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
              <input type="file" name="pic3" class="area-input-file">
              <img src="<?php echo getFormData('pic3'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic3'))) echo 'display:none;' ?>">
                ドラッグ＆ドロップ
            </label>
            <div class="area-msg">
              <?php
              echo getErrMsg('pic3');
              ?>
            </div>
          </div>
        </div>

        <div class="wrap-submit_btn">
          <input type="submit" value="<?php echo (!$edit_flg) ? '投稿する' : '更新する'; ?>" class="btn-submit entry">
        </div>
        <?php
          if($edit_flg){ ?>
            <div class="wrap-submit_btn">
              <input type="submit" name="delete" value="レビューを削除する" class="btn-submit change delete">
            </div>
      <?php }
        ?>
        <a href="<?php echo (!$edit_flg) ? 'mypage.php' : 'reviewRecord.php?p='.$p; ?>" class="link-back"><?php echo (!$edit_flg) ? 'マイページへ戻る' : '投稿履歴へ戻る'; ?></a>
      </form>
    </section>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
