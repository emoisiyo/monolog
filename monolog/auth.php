<?php

//================================
// ログイン認証・自動ログアウト
//================================
//ログインしている場合
if(!empty($_SESSION['login_date'])){
  debug('ログイン済みユーザーです');

  //現在日時が最終ログイン日時＋有効期限を超えていた場合
  if($_SESSION['login_date'] + $_SESSION['login_limit'] < time()){
    debug('ログイン有効期限オーバーです');

    //ログアウトさせる
    session_destroy();
    header("Location:login.php");
    exit();
  } else {
    debug('ログイン有効期限内です');
    debug('セッション変数の中身'.print_r($_SESSION,true));
    //最終ログイン日時を現在時刻に更新
    $_SESSION['login_date'] = time();

    if(basename($_SERVER['PHP_SELF']) === 'login.php'){
      debug('マイページへ遷移します。');
      header("Location:mypage.php"); //マイページへ
      exit();
    }

  }

}else{
  debug('未ログインユーザーです。');
  if(basename($_SERVER['PHP_SELF']) !== 'login.php'){
     header("Location:login.php"); //ログインページへ
     exit();
  }
}
