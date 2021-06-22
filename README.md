## 소개
PHP 나무마크 렌더러를 만듭니다.

/discard 디렉토리에 [js 나무마크 파서](http://github.com/LiteHell/js-namumark)를 PHP로 포팅하다가 망해서 버린 코드와
[php-namumark](http://github.com/koreapyj/php-namumark)를 수정하다가 망해서 버린 코드가 있습니다.

## 사용법(이었던 것)
```php
// $text 값에 RAW 텍스트를 지정합니다.

$PW = new PlainWikiPage($text);
$NM = new NamuMark($PW);
$tk = $NM->parse($text); // 토큰화 결과
$NM->toHTML($tk); // HTML 변환 결과
```
