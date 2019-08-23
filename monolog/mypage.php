<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('マイページ');
debug('**************');
debugLogStart();

require('auth.php');

//ログインユーザー情報を取得
$userData = getUser($_SESSION['user_id']);

$reviewNum = getReviewNum($userData['id']);

debug('取得したユーザーデータ：'.print_r($userData,true));
debug('取得したレビュー数：'.$reviewNum);

debug('---------画面表示処理終了---------');

?>

<?php
  $siteTitle = 'マイページ';
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
<!--メインコンテンツ-->
  <div class="container-mypage">
    <div class="wrap-user_data">
      <a href="profView.php">
        <img src="<?php echo showImg(sanitize($userData['pic'])); ?>" class="img-avatar">
      </a>
        <div><a href="profView.php"><?php echo sanitize($userData['username']); ?></a></div>
        <div>投稿数<?php echo $reviewNum; ?>件</div>
    </div>
    <div class="wrap-mypage_menu">
      <a href="postReview.php" class="items-mypage">
        <div>
          <i class="far fa-comment-dots"></i>
          レビュー投稿
        </div>
      </a>
      <a href="reviewRecord.php" class="items-mypage">
        <div>
          <i class="far fa-clock"></i>
          投稿履歴
        </div>
      </a>
      <a href="profEdit.php" class="items-mypage">
        <div>
          <i class="far fa-user"></i>
          プロフィール編集
        </div>
      </a>
      <a href="passEdit.php" class="items-mypage">
        <div>
          <i class="far fas fa-key"></i>
          パスワード変更
        </div>
      </a>
      <a href="favoriteRecord.php" class="items-mypage">
        <div>
          <i class="far fa-heart"></i>
          お気に入り一覧
        </div>
      </a>
      <a href="withdraw.php" class="items-mypage">
        <div>
          <i class="far fas fa-sign-out-alt"></i>
          退会
        </div>
      </a>
    </div>
  </div>
  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
