<!doctype html>
<html>
    <head>
        <title>file selector</title>
        <link rel="stylesheet" href="/monad/default.css">
    </head>
    <body>
        <div class="container-fluid clearfix">
            <form class="row" method="post" action="" enctype="multipart/form-data"><fieldset class="text-center">
                <input type="file" style="display: inline" name="file"> <button class="btn btn-success" type="submit">upload</button>
            </fieldset></form>
            <br><br>
            <div class="row">
<?php foreach ($medias as $media) { ?>
                <a href class="thumbnail col-md-1">
                    <img src="/img/slug.<?=$media['id']?>.120x120.jpg">
                </a>
<?php } ?>
            </div>
        </div>
        <script src="/js/libraries.js"></script>
        <script>
            $(document).ready(function() {
                $('a').click(function() {
                    var a = $(this);
                    var url = a.find('img').attr('src');
                    url = url.replace(/\d+x\d+\.(jpg|png)$/, '1024.$1');
                    window.opener.CKEDITOR.tools.callFunction(<?=$_GET['CKEditorFuncNum']?>, url);
                    window.close();
                    return false;
                });
            });
<?php if (isset($id)) { ?>
            window.opener.CKEDITOR.tools.callFunction(<?=$_GET['CKEditorFuncNum']?>, '/img/slug.<?=$id?>.1024.jpg');
            window.close();
<?php } ?>
        </script>
    </body>
</html>

