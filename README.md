## 소개
PHP 나무마크 렌더러를 만듭니다.

/discard 디렉토리에 [js 나무마크 파서](http://github.com/LiteHell/js-namumark)를 PHP로 포팅하다가 망해서 버린 코드와
[php-namumark](http://github.com/koreapyj/php-namumark)를 수정하다가 망해서 버린 코드가 있습니다.

## 사용법
```php
// $text 값에 RAW 텍스트를 지정합니다.

$PW = new PlainWikiPage($text);
$NM = new NamuMark($PW);
$tk = $NM->parse($text); // 토큰화 결과
$NM->toHTML($tk); // HTML 변환 결과
$NM->category; // 분류 목록
```

## 참고사항
### 수식
파서 상에는 수식 처리 영역이 없어 수식(TeX) 문법 지원을 위해서는 [KaTeX](https://github.com/Khan/KaTeX) 라이브러리가 필요합니다.

다음 코드를 HTML 영역의 <head> 태그 안에 붙여 넣으시면 됩니다.
```html
<meta charset="UTF-8">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.css" integrity="sha384-zB1R0rpPzHqg7Kpt0Aljp8JPLqbXI3bhnPWROx27a9N0Ll6ZP/+DiW/UqRcLbRjq" crossorigin="anonymous"/>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.js" integrity="sha384-y23I5Q6l+B6vatafAwxRu/0oK/79VlbSz7Q9aiSZUvyWYIYsd+qj+o24G5ZU2zJz" crossorigin="anonymous"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/contrib/auto-render.min.js" integrity="sha384-kWPLUVMOks5AQFrykwIup5lo0m3iMkkHrD0uJ4H5cjeGihAutqP0yW0J6dpFiVkI" crossorigin="anonymous" onload="renderMathInElement(document.body);"></script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    renderMathInElement(document.body, {
      delimiters: [
        { left: "[math(", right: ")]", display: false },
      ],
    });
  });
</script>
```


### 목차
목차 접힘을 구현하기 위해서는 HTML 상에 다음 코드가 필요합니다.
```html
<script>
document.querySelectorAll('.nm-cb').forEach(c => {
    c.addEventListener('click', (e) => e.cancelBubble=true);
});
document.querySelectorAll('.hidden-trigger').forEach(h => {
    h.addEventListener('click', () => {
        var at = h.getAttribute('id');
        var c = document.getElementById('content-'+at);
        if(document.getElementById(at).getAttribute('data-pressdo-toc-fold') == 'hide'){
            document.getElementById(at).setAttribute('data-pressdo-toc-fold', 'show');
            c.setAttribute('data-pressdo-toc-fold', 'show');
        }else{
            document.getElementById(at).setAttribute('data-pressdo-toc-fold', 'hide');
            c.setAttribute('data-pressdo-toc-fold', 'hide');
        }
    })
});
</script>
<style>
[data-pressdo-navfunc][data-pressdo-toc-fold=hide]{
  display:none;
}
.wiki-heading[data-pressdo-toc-fold=hide]{
  opacity:.5;
}
</style>
```
