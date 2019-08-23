<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('トップページ');
debug('**************');
debugLogStart();

/*========================================
  画面処理
========================================*/
//現在のページ（デフォルトは1ページ目）
$currentPageNum = (!empty($_GET['p'])) ? $_GET['p'] : 1;
//パラメータに不正な値が入っていないかチェック
if(is_int((int)$currentPageNum) && empty((int)$currentPageNum)){
  debug('パラメータに不正な値が入りました。トップページに遷移します');
  header("Location:index.php");
  exit();
}
//GETパラメータにカテゴリーIDが入っていれば取得する
if(!empty($_GET['c_t'])){
  $c_id = $_GET['c_t'];
  $ctype_flg = true;
  debug('大カテゴリー：'.$c_id);
}else if(!empty($_GET['c_id'])){
  $c_id = $_GET['c_id'];
  $ctype_flg = false;
  debug('小カテゴリー：'.$c_id);
}
//カテゴリー選択状況に応じてGETパラメータを変数に格納
if(!empty($c_id)){
  $ctype_id = getParamCtype($ctype_flg,$c_id);
}else{
  $ctype_id = '';
}


//表示件数
$listSpan = 20;
//現在の表示レコードの先頭を算出
$currentMinNum = (($currentPageNum-1)*$listSpan);

//DBからレビューデータを取得
if(!empty($c_id)){
  debug('カテゴリーごとにレビューを表示します');
  $ReviewData = getReviewbyType($c_id,$currentMinNum, $listSpan,$ctype_flg);
}else{
  debug('全レビューを表示します');
  $ReviewData = getReviewList($currentMinNum,$listSpan);
}

//パラメータに不正な値が入っていないかチェック（総ページ数より多い値を入れてきたとき）
if($ReviewData['total_page'] < $currentPageNum){
  debug('パラメータに不正な値が入りました。トップページに遷移します');
  header("Location:index.php");
  exit();
}

debug('取得したレビューデータ：'. print_r($ReviewData,true));

debug('現在のページ数：'. $currentPageNum);

debug('---------画面表示処理終了---------');
?>
<?php
    $siteTitle = 'トップページ';
    require('head.php');
?>
<body>
  <!-- ヘッダー -->
  <?php
    require('header.php');
  ?>
  <!-- メインコンテンツ -->
  <div class="container-contents-wide">
    <div class="wrap-img-top">
      <img src="image/top_image_b.png">
    </div>
    <!--レビュー一覧表示-->
    <div class="wrap-review">
      <?php
        foreach($ReviewData['data'] as $key => $val):
      ?>
      <div class="wrap-panel <?php categoryLabel(sanitize($val['category_type'])) ?>">
        <div class="panel-head">
          <a href="reviewDetail.php<?php echo '?p='.$currentPageNum.'&r_id='.$val['id'].$ctype_id; ?>"><img src="<?php echo showImg(sanitize($val['pic1'])) ?>" width="200" height="130"></a>
        </div>
        <div class="panel-body">
          <div class="panel-score <?php categoryLabel(sanitize($val['category_type'])) ?>">
            <?php echo sanitize(showScore($val['score'])) ?>
          </div>
          <div class="panel-category_label">
            <span class="panel-category <?php categoryLabel(sanitize($val['category_type'])) ?>"><?php echo sanitize($val['name']) ?></span>
            <span><?php echo date('Y-m-d', strtotime(sanitize($val['create_date']))); ?></span>
          </div>
          <a href="reviewDetail.php<?php echo '?p='.$currentPageNum.'&r_id='.$val['id'].$ctype_id ; ?>" class="panel-title">
            <?php echo sanitize(shapeText($val['title'])) ?>
          </a>
          <div class="panel-user_label">
            <img src="<?php echo showImg(sanitize($val['pic'])) ?>" width="50" height="50">
            <a href="profView.php<?php echo '?p='.$currentPageNum.'&u_id='.$val['user_id'].$ctype_id ; ?>" class="panel-user_name"><?php echo sanitize($val['username']) ?></a>
          </div>
        </div>
      </div>
      <?php
        endforeach;
      ?>
    </div>
    <?php pagenation($currentPageNum,$ReviewData['total_page']); ?>
  </div>

  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
