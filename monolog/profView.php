<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('プロフィール閲覧ページ');
debug('**************');
debugLogStart();

//画面処理
//GETパラメータからデータを取得
//ページ数
$p_id = (!empty($_GET['p'])) ? $_GET['p'] : '';
//レビューID
$r_id = (!empty($_GET['r_id'])) ? $_GET['r_id'] : '';
//カテゴリーソートされている場合はキーと値を取得
if(!empty($_GET['c_t'])){
  $c_id = $_GET['c_t'];
  $ctype_flg = true;
  //カテゴリーのGETパラメータを生成
  $category_id = getParamCtype($ctype_flg,$c_id);
}else if(!empty($_GET['c_id'])){
  $c_id = $_GET['c_id'];
  $ctype_flg = false;
  //カテゴリーのGETパラメータを生成
  $category_id = getParamCtype($ctype_flg,$c_id);
}
//インデックスからのリクエストのときに、GETパラメータのユーザーIDを取得
$user_id = (!empty($_GET['u_id'])) ? $_GET['u_id'] : '';
//ユーザーID
$u_id = (!empty($_SESSION['user_id'])) ? $_SESSION['user_id'] : '';

//アクセス元がどこのページか判断する
//リクエスト元のURLを取得
$requestUrl = basename($_SERVER['HTTP_REFERER']);
debug('リクエスト元URL：'.$requestUrl);

//リクエスト元がマイページならtrueを入れる
$requestUrl_flg = ($requestUrl === 'mypage.php') ? true : false;

$accessIndex = 'index';
$accessReview = 'reviewDetail';

//DBからレビューデータを取得
if($requestUrl_flg){
  debug('マイページからのリクエストです');
  $userData = getUser($_SESSION['user_id']);
  debug('取得したユーザーデータ：'. print_r($userData,true));
}

if(strpos($requestUrl, $accessIndex) === false ){
    debug('インデックス以外からのリクエストです');
  }else{
    //アクセス元がインッデクスのとき
    debug('インデックスページからのリクエストです');
    $userData = getUser($user_id);
    debug('取得したユーザーデータ：'. print_r($userData,true));
    $nameBackLink = 'レビュー一覧へ戻る';
  }

if(strpos($requestUrl, $accessReview) === false ){
  debug('レビュー詳細以外からのリクエストです');
}else{
  debug('レビュー詳細ページからのリクエストです');
  $Data = getReviewOne($r_id);
  $userData = getUser($Data['user_id']);
  debug('取得したユーザーデータ：'. print_r($userData,true));
  $nameBackLink = 'レビューページへ戻る';
}

//レビューの件数を数える
$reviewNum = getReviewNum($userData['id']);

debug('---------画面表示処理終了---------');
?>

  <?php
    $siteTitle = 'プロフィール';
    require('head.php');
  ?>
<body>
  <!-- ヘッダー -->
  <?php
    require('header.php');
  ?>
  <div class="container-contents">
    <div class="wrap-form">
      <h2 class="title-form">
        <?php echo showImg(sanitize($userData['username'])); ?>さんのプロフィール
      </h2>
      <div class="wrap-prof_avatar">
        <img src="<?php echo showImg(sanitize($userData['pic'])); ?>" width="300" height="300" class="img-avatar prof">
      </div>
      <div class="title-form">
        <?php echo sanitize($userData['username']); ?><br>
        投稿数<span><?php echo $reviewNum; ?></span>件<br>
      </div>
      <table>
        <?php if(!empty($userData['gender'])){ ?>
        <tr>
          <td>性別</td>
          <td><?php echo $userData['gender']; ?></td>
        </tr>
        <?php } ?>
        <?php if(!empty($userData['birth_y'])){ ?>
        <tr>
          <td>生年月日</td>
          <td><?php echo sanitize($userData['birth_y']); ?>年<?php echo sanitize($userData['birth_m']); ?>月<?php echo sanitize($userData['birth_d']); ?>日</td>
        </tr>
        <?php } ?>
      </table>
      <div class="intro">
        <?php echo nl2br(sanitize($userData['intro'])); ?>
      </div>
      <?php
        if($requestUrl_flg){
          echo '<a href="mypage.php" class="link-index">マイページへ戻る</a>';
        }else{
          echo '<a href="'.$requestUrl.ChangeGetParam($p_id,$category_id,$r_id).'" class="link-index">'.$nameBackLink.'</a>';
        }
      ?>

    </div>
  </div>

  <!-- フッター -->
  <?php
    require('footer.php');
  ?>
