<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('レビュー詳細');
debug('**************');
debugLogStart();


//================================
// 画面処理
//================================

// 画面表示用データ取得
//================================
//ログイン状況を調べる
$login_flg = islogin();

//GETパラメータからレビューIDを取得
$r_id = (!empty($_GET['r_id'])) ? $_GET['r_id'] : '';
//GETパラメータから現在のページ数を取得
$pageNum = (!empty($_GET['p'])) ? $_GET['p'] : '';
//カテゴリーソートされている場合はGETパラメータを取得
if(!empty($_GET['c_t'])){
  $c_id = $_GET['c_t'];
  $ctype_flg = true;
}else if(!empty($_GET['c_id'])){
  $c_id = $_GET['c_id'];
  $ctype_flg = false;
}
//カテゴリー選択状況に応じてGETパラメータを変数に格納
$ctype_id = getParamCtype($ctype_flg,$c_id);

//お気に入りページから遷移した場合はページ数を取得
$favpageNum = (!empty($_GET['fav_p'])) ? $_GET['fav_p'] : '';
//DBからレビューデータを取得
$ReviewData = (!empty($r_id)) ? getReviewOne($r_id) : '';
debug('取得したレビューデータ：'. print_r($ReviewData,true));

//GETパラメータに不正な値が入っているかチェック
if(!empty($r_id) && empty($ReviewData) || empty($r_id)){
  error_log('エラー発生：指定ページに不正な値が入りました');
  header("Location:index.php");
  exit();
}

//DBから投稿者のデータを取得
$reviewer = getUser($ReviewData['user_id']);
debug('取得した投稿者データ：'. print_r($reviewer,true));

//DBからコメントデータを取得
$commentData = getCommentAndUser($r_id);

//================================
// POST送信されたら
//================================
if(!empty($_POST)){
  debug('POST送信があります');

  //バリデーションチェク
  $comment = (isset($_POST['comment'])) ? $_POST['comment'] : '';
  //最大文字数をオーバーしていないかチェック
  validMaxLen($comment,'comment',200);
  //未入力チェック
  validRequired($comment,'comment');

  if(empty($err_msg)){
    debug('バリデーションOKです');

    try {
      $dbh = dbConnect();
      $sql = 'INSERT INTO comment (review_id, from_user, comment, create_date) VALUES (:r_id, :from_user, :comment, :date)';
      $data = array(':r_id' => $r_id, ':from_user' => $_SESSION['user_id'], ':comment' => $comment, ':date' => date('Y-m-d H:i:s'));

      $stmt = queryPost($dbh,$sql,$data);

      if($stmt){
        $_POST = array();
        debug('レビュー詳細ページへ遷移します');
        $_SESSION['msg_success'] = SUC06;
        //自画面に遷移する
        header("Location:" . $_SERVER['PHP_SELF'] . '?r_id=' . $r_id);
        exit();
      }
    } catch (Exception $e) {
      error_log('エラー発生：'. $e->getMessage());;
      $err_msg['common'] = MSG07;
    }
  }
}

debug('---------画面表示処理終了---------');

?>

<?php
    $siteTitle = 'レビュー詳細';
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
  <div class="container-contents edit_review">
    <section class="wrap-form edit_review">
      <div class="area-msg-review_detail <?php if(!empty($err_msg['common'])) echo 'err' ?>">
        <?php
        if(!empty($err_msg['common'])) echo $err_msg['common'];
        ?>
      </div>
      <div class="area-msg-review_detail <?php if(!empty($err_msg['comment'])) echo 'err' ?>">
        <?php
        echo getErrMsg('comment');
        ?>
      </div>
      <div class="wrap-review-data">
        <div class="wrap-review-type">
          <span class="label-date"><?php echo date('Y-m-d', strtotime(sanitize($ReviewData['create_date']))); ?></span>
          <span class=" <?php categoryLabel(sanitize($ReviewData['category_type'])) ?> label-category"><?php echo sanitize($ReviewData['name']) ?></span>
        </div>
        <div class="wrap-review-score">
          <span class=" <?php categoryLabel(sanitize($ReviewData['category_type'])) ?> label-score"><?php echo sanitize(showScore($ReviewData['score'])) ?></span>
        </div>
      </div>
      <h2 class="review-title">
        <?php echo sanitize($ReviewData['title']) ?>
      </h2>
      <div class="wrap-reviw-img">
        <div class="img-main">
          <img src="<?php echo showImg(sanitize($ReviewData['pic1'])); ?>" width="500" height="350" id="js-switch-img-main" class="img-review">
        </div>
        <div class="img-sub">
          <img src="<?php echo showImg(sanitize($ReviewData['pic1'])); ?>" width="200" height="150" class="js-switch-img-sub img-review">
          <img src="<?php echo showImg(sanitize($ReviewData['pic2'])); ?>" width="200" height="150" class="js-switch-img-sub img-review">
          <img src="<?php echo showImg(sanitize($ReviewData['pic3'])); ?>" width="200" height="150" class="js-switch-img-sub img-review">
        </div>
      </div>
      <div class="review-comment">
        <?php echo nl2br(sanitize($ReviewData['comment'])) ?>
      </div>
      <div class="wrap-review-data">
        <div class="reviewer">
          <a href="profView.php<?php echo '?p='.$pageNum.'&r_id='.$r_id.$ctype_id; ?>"<span class="reviewer-name"><?php echo sanitize($reviewer['username']) ?></span></a>
          <img src="<?php echo showImg(sanitize($reviewer['pic'])); ?>" width="50" height="50" class="reviewer-avatar img-review">
        </div>
        <?php
          if($login_flg) { ?>
            <div class="wrap-favorite-btn">
              <div class="btn-favorite js-click-favorite" data-reviewid="<?php echo $r_id ?>">
                <i class="fas fa-heart icn-fovorite <?php if(isfavorite($_SESSION['user_id'], $r_id)){ echo 'active'; }?>" ></i>お気に入りに追加
              </div>
            </div>
          <?php } ?>
      </div>
      <a href="<?php echo (!empty($favpageNum)) ? 'favoriteRecord.php?p='.$favpageNum : 'index.php?p='.$pageNum.$ctype_id ;?>" class="link-index"><?php echo (!empty($favpageNum)) ? 'お気に入り一覧へ戻る' : 'レビュー一覧へ戻る' ;?></a>
    </section>
  </div>
  <!--コメント表示エリア-->
  <div class="container-contents msg">
    <h2 class="title-area-comment">コメント</h2>
      <?php
        if(!empty($commentData)){
          foreach($commentData as $key => $val){
            if(!empty($val['from_user']) && $val['from_user'] == $reviewer['id']){
          ?>
              <div class="wrap-comment">
                <div class="comment-name">
                  <?php echo sanitize($val['username']); ?>
                </div>
                <div class="wrap-comment-avatar">
                  <img src="<?php echo sanitize(showImg($val['pic'])); ?>" class="comment-avatar">
                </div>
                <p class="wrap-comment-text">
                  <span class="triangle"></span>
                  <?php echo sanitize($val['comment']); ?>
                </p>
                <div class="comment-date">
                  <?php echo date('Y-m-d', strtotime(sanitize($val['create_date']))) ?>
                </div>
              </div>
        <?php
      }else{
        ?>
        <div class="wrap-comment-left">
          <div class="comment-name-left">
            <?php echo sanitize($val['username']); ?>
          </div>
          <div class="wrap-comment-avatar-left">
            <img src="<?php echo sanitize(showImg($val['pic'])); ?>" class="comment-avatar">
          </div>
          <p class="wrap-comment-text-left">
            <span class="triangle"></span>
            <?php echo sanitize($val['comment']); ?>
          </p>
          <div class="comment-date-left">
            <?php echo date('Y-m-d', strtotime(sanitize($val['create_date']))) ?>
          </div>
        </div>
        <?php
          }
        }
        }
      ?>
  </div>
  <!--コメントフォーム-->
  <div class="container-contents msg">
    <div class="wrap-form msg">
      <?php if($login_flg){ ?>
      <form action="" method="post">
        <textarea name="comment" id= "js-count" class="form-comment" placeholder="200文字以内でご記入ください"></textarea>
        <p class="counter-text"><span id="js-count-view">0</span>/200文字</p>
        <input type="submit" value="コメントする" class="btn-post-comment">
      </form>
    <?php }else{ ?>
      <div class="notice-comment">
        <i class="far fa-comment"></i>
        ログインするとコメントを記入できます
      </div>
    <?php } ?>
    </div>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
