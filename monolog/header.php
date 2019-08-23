<?php
//POST送信（キーワード検索）されたとき
if(strval($_POST['searchWord']) !== '') {
  debug('POST送信があります');
  //キーワードを変数に格納
    $searchWord = $_POST['searchWord'];
      debug('検索ワード：'.$searchWord);
      debug('検索ワードかた：'.gettype($searchWord));
      if(empty($err_msg)){
        $ReviewData = searchWord($searchWord,$currentMinNum = 1, $listSpan);
        debug('取得したレビューデータ：'. print_r($ReviewData,true));
      }
}

?>
<header class="container-header">
  <div class="inner-header">
    <a href="index.php">
      <img class="logo-title" src="image/logo-title.png" width="140" height="50">
    </a>
    <form class="form-search" action="index.php" method="post">
      <input class="search-box" type="text" name="searchWord" placeholder="タイトルで検索">
      <button type="submit" class="icon-search"><i class="fas fa-search"></i></button>
    </form>
    <div class="wrap-menu_btn">
      <div class="inner-menu_btn">
        <?php
          if(empty($_SESSION['user_id'])){
        ?>
          <a href="signup.php" class="nav-btn signup">会員登録</a>
          <a href="login.php" class="nav-btn login">ログイン</a>
        <?php
      }else{
        ?>
        <a href="mypage.php" class="nav-btn mypage">マイページ</a>
        <a href="logout.php" class="nav-btn logout">ログアウト</a>
        <?php
      }
        ?>
      </div>
      <a href="postReview.php" class="nav-btn review_post">投稿する</a>
    </div>
    <nav class="nav">
      <ul class="nav-ul">
        <li class="nav-list"><a href="index.php?c_t=1" class="color-eat">食べた</a>
          <ul class="nav-list-child-ul">
            <li class="child-list"><a href="index.php?c_id=1" class="color-eat">ごはん</a></li>
            <li class="child-list"><a href="index.php?c_id=2" class="color-eat">スイーツ</a></li>
            <li class="child-list"><a href="index.php?c_id=3" class="color-eat">飲み物</a></li>
          </ul>
        </li>
        <li class="nav-list"><a href="index.php?c_t=2" class="color-read">読んだ</a>
          <ul class="nav-list-child-ul">
            <li class="child-list"><a href="index.php?c_id=4" class="color-read">一般</a></li>
            <li class="child-list"><a href="index.php?c_id=5" class="color-read">雑誌</a></li>
            <li class="child-list"><a href="index.php?c_id=6" class="color-read">漫画</a></li>
          </ul>
        </li>
        <li class="nav-list"><a href="index.php?c_t=3" class="color-watch">観た</a>
          <ul class="nav-list-child-ul">
            <li class="child-list"><a href="index.php?c_id=7" class="color-watch">テレビ</a></li>
            <li class="child-list"><a href="index.php?c_id=8" class="color-watch">映画</a></li>
            <li class="child-list"><a href="index.php?c_id=9" class="color-watch">アニメ</a></li>
          </ul>
        </li>
        <li class="nav-list"><a href="index.php?c_t=4" class="color-went">行った</a>
          <ul class="nav-list-child-ul">
            <li class="child-list"><a href="index.php?c_id=10" class="color-went">国内</a></li>
            <li class="child-list"><a href="index.php?c_id=11" class="color-went">海外</a></li>
          </ul>
        </li>
        <li class="nav-list"><a href="index.php?c_t=5" class="color-listen">聴いた</a>
          <ul class="nav-list-child-ul">
            <li class="child-list color-listen"><a href="index.php?c_id=12" class="color-listen">邦楽</a></li>
            <li class="child-list color-listen"><a href="index.php?c_id=13" class="color-listen">洋楽</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </div>
</header>
