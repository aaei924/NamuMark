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

### JS
일부 문법의 원활한 적용을 위해서는 src/namumark.js가 적용되어야 합니다.

대상 문법:
- 수식
- 문단 접기/펼치기
- folding 문법

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

### 날짜 매크로
현재 시각은 서버에 설치된 php의 기본 시간대를 기준으로 표시됩니다. 변경을 원하는 경우 php.ini에서 date.timezone 값을 수정해 주시기 바랍니다.

사용 가능한 시간대 목록은 [여기](https://www.php.net/manual/en/timezones.php)를 참고해주세요. 본 라이브러리를 PressDo에서 사용하는 경우 PressDo 설정에서 시간대 지정이 가능합니다.

## 주의사항
AGPL 3.0 라이선스에 따라 사용 시 소스코드를 공개하여야 합니다. 또한 기여자는 코드에 대해 책임을 지거나 보증하지 않습니다.

PHP 8 이상 환경에서만 작동합니다.

## 오픈 소스 라이선스
- php-namumark (koreapyj)
- Ionicons (ionic-team)