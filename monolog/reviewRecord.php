<?php
//共通関数読み込み
require('function.php');

debug('**************');
debug('投稿履歴ページ');
debug('**************');
debugLogStart();

require('auth.php');
/*========================================
  GETパラメータを取得
========================================*/
//現在のページ（デフォルトは1ページ目）

$currentPageNum = (!empty($_GET['p'])) ? $_GET['p'] : 1;
//パラメータに不正な値が入っていないかチェック
if(is_int((int)$currentPageNum) && empty((int)$currentPageNum)){
  debug('パラメータに不正な値が入りました。トップページに遷移します');
  header("Location:index.php");
  exit();
}
//表示件数
$listSpan = 20;
//現在の表示レコードの先頭を算出
$currentMinNum = (($currentPageNum-1)*$listSpan);
//DBからレビューデータを取得
$ReviewData = getReviewAll($_SESSION['user_id'],$currentMinNum,$listSpan);

//パラメータに不正な値が入っていないかチェック（総ページ数より多い値を入れてきたとき）
if($ReviewData['total_page'] > 0 && $ReviewData['total_page'] < $currentPageNum){
  debug('パラメータに不正な値が入りました。トップページに遷移します');
  header("Location:index.php");
  exit();
}
debug('取得したレビューデータ：'. print_r($ReviewData,true));

debug('現在のページ数：'. $currentPageNum);

debug('---------画面表示処理終了---------');

?>
<?php
    $siteTitle = '投稿履歴';
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
  <div class="container-contents-wide">
    <!--レビュー一覧表示-->
    <h2 class="title-form">レビュー投稿履歴</h2>
    <div class="wrap-review record_list">
      <?php
        if(!empty($ReviewData['data'])){
          foreach($ReviewData['data'] as $key => $val):
      ?>
      <a href="postReview.php<?php echo '?r_id='.$val['id'].'&p='.$currentPageNum ?>" class="box-review_records">
        <div class="img-review_records">
          <img src="<?php echo showImg(sanitize($val['pic1'])); ?>" width="80" height="80">
        </div>
        <div class="data-review_records">
          <span><?php echo date('Y-m-d', strtotime(sanitize($val['create_date']))); ?></span>
          <span class="<?php categoryLabel(sanitize($val['category_type'])) ?>"><?php echo sanitize(showScore($val['score'])); ?></span>
        </div>
        <div class="title-review_records">
          <?php echo sanitize($val['title']); ?>
        </div>
        <div class="comment-review_records">
          <?php echo sanitize(shapeText(($val['comment']),45)); ?>
        </div>
      </a>
      <?php endforeach; ?>
      <?php } ?>
    </div>
    <?php if(empty($ReviewData['data'])){ ?>
          <p class="msg-none"><i class="far fa-frown-open"></i>投稿はありません</p>
        <?php } ?>
    <a href="mypage.php" class="link-back">マイページへ戻る</a>
    <?php pagenation($currentPageNum,$ReviewData['total_page']); ?>
  </div>

  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
