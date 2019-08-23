<?php

//共通関数読み込み
require('function.php');

debug('**************');
debug('ログアウトページ');
debug('**************');

debugLogStart();

debug('ログアウトします');
session_destroy();
debug('ログインページへ遷移します');
header("Location:login.php");
