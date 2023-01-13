<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.css" integrity="sha384-zB1R0rpPzHqg7Kpt0Aljp8JPLqbXI3bhnPWROx27a9N0Ll6ZP/+DiW/UqRcLbRjq" crossorigin="anonymous"/>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.js" integrity="sha384-y23I5Q6l+B6vatafAwxRu/0oK/79VlbSz7Q9aiSZUvyWYIYsd+qj+o24G5ZU2zJz" crossorigin="anonymous"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/contrib/auto-render.min.js" integrity="sha384-kWPLUVMOks5AQFrykwIup5lo0m3iMkkHrD0uJ4H5cjeGihAutqP0yW0J6dpFiVkI" crossorigin="anonymous"></script>
<script defer src="./src/namumark.js"></script>
<link rel="stylesheet" href="./src/namumark.css">
<meta charset="UTF-8">
<?php
/** Test Code */
require 'NamuMark.php';
$c = file_get_contents('samples/sample.txt');
$wEngine = new NamuMark();
$wEngine->noredirect = '1';
$wEngine->title = '나무위키:문법 도움말';

?>
<div class="w">
    <?=$wEngine->toHtml($c)?>
    <div class="popper">
        <div class="popper__arrow"></div>
        <div class="popper__inner"></div>
    </div>
</div>