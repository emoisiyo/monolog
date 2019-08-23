<footer class="footer">
  <div>
    <a href="index.php"><img src="image/logo-title-white.png"></a>
  </div>
  <div class="wrap-footer-links">
    <a href="">お問い合わせ</a>
  </div>
  <small>&copy;2019 もえやん</small>
</footer>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script>
  $(function(){
    //フッターを最下部に固定
    var $ftr = $('.footer');
    if(window.innerHeight > $ftr.offset().top + $ftr.outerHeight()){
      $ftr.attr({'style': 'position:fixed; top:' + (window.innerHeight - $ftr.outerHeight()) +'px' });
    }
    //成功メッセージ
    var $jsShowMsg = $('#js-show-msg');
    var msg = $jsShowMsg.text();
    if(msg.replace(/^[\s　]+|[\s　]+$/g, "").length){
      $jsShowMsg.slideToggle('slow');
      setTimeout(function(){ $jsShowMsg.slideToggle('slow'); }, 5000);
    }

    //文字数カウント
    var $countUp = $('#js-count'),
        $countView = $('#js-count-view');
    $countUp.on('keyup', function(e){
      $countView.html($(this).val().length);
    });

    //画像ライブプレビュー
    var $dropArea = $('.area-imgdrop');
    var $fileInput = $('.area-input-file');
    $dropArea.on('dragover' , function(e){
      e.stopPropagation();
      e.preventDefault();
      $(this).css('border' , '3px #ccc dashed');
    });
    $dropArea.on('dragleave' , function(e){
      e.stopPropagation();
      e.preventDefault();
      $(this).css('border' , 'none');
    });
    $fileInput.on('change' , function(e){
      $dropArea.css('border' , 'none');
      var file = this.files[0],
          $img = $(this).siblings('.prev-img'),
          fileReader = new FileReader();

      fileReader.onload = function(event) {
        $img.attr('src' , event.target.result).show();
      };

      fileReader.readAsDataURL(file);
    });

    //画像切り替え
    var $switchImgsubs = $('.js-switch-img-sub'),
        $switchImgMain = $('#js-switch-img-main');
    $switchImgsubs.on('click',function(e){
      $switchImgMain.attr('src',$(this).attr('src'));
    });

    //お気に入り登録・削除
    var $favorite,
        favoriteReviewId;
    $favorite = $('.js-click-favorite') || null; //nullというのはnull値という値で、「変数の中身は空ですよ」と明示するためにつかう値
    favoriteReviewId = $favorite.data('reviewid') || null;
    // 数値の0はfalseと判定されてしまう。product_idが0の場合もありえるので、0もtrueとする場合にはundefinedとnullを判定する
    if(favoriteReviewId !== undefined && favoriteReviewId !== null){
      $favorite.on('click',function(){
        var $this = $(this);
        $.ajax({
          type: "POST",
          url: "ajaxFavorite.php",
          data: { reviewId : favoriteReviewId}
        }).done(function( data ){
          console.log('Ajax Success');
          // クラス属性をtoggleでつけ外しする
          $this.children('i').toggleClass('active');
        }).fail(function( msg ) {
          console.log('Ajax Error');
        });
      });
    }


  });
</script>
</body>
</html>
