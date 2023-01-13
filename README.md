## 소개
PHP 나무마크 렌더러를 만듭니다.

[php-namumark](http://github.com/koreapyj/php-namumark)를 수정했습니다.

## 사용법
```php
// $text 값에 RAW 텍스트를 지정합니다.
require 'NamuMark.php';

$nm = new NamuMark();
$nm->noredirect = '1'; // 리다이렉트 문서일 경우 페이지를 이동할지의 여부
$nm->title = '문서 제목';

$nm->toHtml($text); // HTML 렌더링 결과

$nm->toc; // 문서 목차
$nm->category; // 분류 목록
```

## 참고사항
### CSS
동봉된 src/namumark.css를 불러오시면 됩니다.

렌더링된 HTML이 'w' class를 가진 요소 안에 있어야 합니다. 
```html
<!--예시-->
<div class="w">
  <!--렌더링 결과-->
</div>
```

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
추가 예정
```

### 토론
토론에서도 나무마크를 사용할 수 있지만, 일부 문법은 사용이 불가합니다.
```php
$nm = new NamuMark();
// ...
$nm->inThread = true;
// ...
echo $nm->toHtml();
```
토론 상에서 사용 가능한 문법만을 처리하려는 경우 위 값을 지정하면 됩니다.

다음 문법은 토론에서 사용할 수 없습니다.
- 분류
- 리다이렉트
- 사진
- 동영상
- 일부 매크로
- TeX 문법
- HTML

## 주의사항
AGPL 3.0 라이선스에 따라 사용 시 소스코드를 공개하여야 합니다. 또한 기여자는 코드에 대해 책임을 지거나 보증하지 않습니다.

## 오픈 소스 라이선스
- php-namumark (koreapyj)
- Ionicons (ionic-team)