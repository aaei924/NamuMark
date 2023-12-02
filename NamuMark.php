<?php
/**
 * namumark.php - Namu Mark Renderer
 * Copyright (C) 2015 koreapyj koreapyj0@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author koreapyj, 김동동(1st edited)
 * @author aaei924(2nd edited)
 * 
 * 코드 설명 주석 추가: PRASEOD-
 * 설명 주석이 +로 시작하는 경우 PRASEOD-의 2차 수정 과정에서 추가된 코드입니다.
 */
require 'HTMLRenderer.php';

class WikiPage {
    // 평문 데이터 호출
    public $lastchanged;
    function __construct(public $text) {
        $this->lastchanged = time();
    }

    function pageExists($target) {
        return false;
    }
    
    function includePage($target) {
        return '[include 된 문서]';
    }
}

class NamuMark {

    /**
     * 엔진에 표시되는 문서 제목
     */
    public string $title;

    /**
     * include 문법으로 포함되는 문서인지의 여부 (기본값 false)
     */
    public bool $included = false;
    
    /**
     * 리다이렉트 문법에서 페이지를 이동시킬지의 여부
     * ----
     * 0일 경우 이동함 (기본값), 1일 경우 이동하지 않음
     */
    public int $noredirect = 0;

    /**
     * 토론문법 여부
     * ----
     * true일 경우 토론에서 허용되는 문법만 처리됨. (기본값 false)
     */
    public bool $inThread = false;
    
    /**
     * 이름공간별 페이지 수가 모여 있는 배열. 허용할 매크로 인수를 키로, 페이지 수를 값으로 함.
     */
    public array $pageCount = [];

    /**
     * 유효하지 않은 pagecount() 인수에 대해 기본값으로 표출할 전체 페이지 수
     */
    public $totalPageCount = 0;
    
    public $intable, $category = [], $firstlinebreak = false, $fromblockquote = false;
    private $WikiPage, $wikitextbox = false, $imageAsLink, $linenotend, $htr;

    private static $list_tags = [
        '*' => 'wiki-ul',
        '1.' => 'wiki-list',
        'A.' => 'wiki-list wiki-list-upper-alpha',
        'a.' => 'wiki-list wiki-list-alpha',
        'I.' => 'wiki-list wiki-list-upper-roman',
        'i.' => 'wiki-list wiki-list-roman'
    ];

    private static $brackets = [
        [
            'open'    => '{{{',
            'close' => '}}}',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '{{{',
            'close' => '}}}',
            'multiline' => true,
            'processor' => 'renderProcessor'
        ],
        [
            'open'    => '[[',
            'close' => ']]',
            'multiline' => false,
            'processor' => 'linkProcessor'
        ],
        [
            'open'    => '[',
            'close' => ']',
            'multiline' => false,
            'processor' => 'macroProcessor'
        ],

        [
            'open'    => '\'\'\'',
            'close' => '\'\'\'',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '\'\'',
            'close' => '\'\'',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '**',
            'close' => '**',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '~~',
            'close' => '~~',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '--',
            'close' => '--',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '__',
            'close' => '__',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => '^^',
            'close' => '^^',
            'multiline' => false,
            'processor' => 'textProcessor'
        ],
        [
            'open'    => ',,',
            'close' => ',,',
            'multiline' => false,
            'processor' => 'textProcessor'
        ]
    ];

    private static $cssColors = [
        'black','gray','grey','silver','white','red','maroon','yellow','olive','lime','green','aqua','cyan','teal','blue','navy','magenta','fuchsia','purple',
        'dimgray','dimgrey','darkgray','darkgrey','lightgray','lightgrey','gainsboro','whitesmoke',
        'brown','darkred','firebrick','indianred','lightcoral','rosybrown','snow','mistyrose','salmon','tomato','darksalmon','coral','orangered','lightsalmon',
        'sienna','seashell','chocolate','saddlebrown','sandybrown','peachpuff','peru','linen','bisque','darkorange','burlywood','anaatiquewhite','tan','navajowhite',
        'blanchedalmond','papayawhip','moccasin','orange','wheat','oldlace','floralwhite','darkgoldenrod','goldenrod','cornsilk','gold','khaki','lemonchiffon',
        'palegoldenrod','darkkhaki','beige','ivory','lightgoldenrodyellow','lightyellow','olivedrab','yellowgreen','darkolivegreen','greenyellow','chartreuse',
        'lawngreen','darkgreen','darkseagreen','forestgreen','honeydew','lightgreen','limegreen','palegreen','seagreen','mediumseagreen','springgreen','mintcream',
        'mediumspringgreen','mediumaquamarine','aquamarine','turquoise','lightseagreen','mediumturquoise','azure','darkcyan','darkslategray','darkslategrey',
        'lightcyan','paleturquoise','darkturquoise','cadetblue','powderblue','lightblue','deepskyblue','skyblue','lightskyblue','steelblue','aliceblue','dodgerblue',
        'lightslategray','lightslategrey','slategray','slategrey','lightsteelblue','comflowerblue','royalblue','darkblue','ghostwhite','lavender','mediumblue',
        'midnightblue','slateblue','darkslateblue','mediumslateblue','mediumpurple','rebeccapurple','blueviolet','indigo','darkorchid','darkviolet','mediumorchid',
        'darkmagenta','plum','thistle','violet','orchid','mediumvioletred','deeppink','hotpink','lavenderblush','palevioletred','crimson','pink','lightpink'
    ];

    private static $videoURL = [
        'youtube' => '//www.youtube.com/embed/',
        'kakaotv' => '//tv.kakao.com/embed/player/cliplink/',
        'nicovideo' => '//embed.nicovideo.jp/watch/',
        'navertv' => '//tv.naver.com/embed/',
        'vimeo' => '//player.vimeo.com/video/'
    ];

    private static $syntaxList = [
        'basic','cpp','csharp','css','erlang','go','html','java','javascript','json','kotlin','lisp','lua','markdown',
        'objectivec','perl','php','powershell','python','ruby','rust','sh','sql','swift','typescript','xml'
    ];

    private $macro_processors = [], $toc = [], $fn = [], $fn_overview = [], $links = [], $fnset = [];
    private $fn_names = [], $fn_cnt = 0, $ind_level = 0, $lastfncount = 0, $bqcount = 0;

    function __construct()
    {
        // 문법 데이터 생성
        $this->imageAsLink = false;
        $this->noredirect = 0; // redirect
        $this->inThread = false; // 토론창 여부
        $this->linenotend = false; // lineParser 개행구분용
    }

    /**
     * 나무마크 텍스트를 HTML로 렌더링합니다.
     * @param string $wtext 나무마크 문자열
     * @return string 변환된 HTML
     */
    public function toHtml(string $wtext): string
    {
        // 문법을 HTML로 변환하는 함수
        $token = $this->htmlScan($wtext);
        if(empty($this->htr)){
            $this->htr = new HTMLRenderer();
        }
        $this->htr->toc = $this->toc;
        $this->htr->fn_overview = $this->fn_overview;
        $this->htr->fn = $this->fn;
        $this->htr->fnset = $this->fnset;
        unset($wtext);
        return ($this->wikitextbox?$this->htr->render($token):'<div id="content-s-0" class="wiki-heading-content">'.$this->htr->render($token).'</div>');
    }

    /**
     * 나무마크 텍스트를 배열 형태의 토큰으로 변환합니다.
     * @param string $text 나무마크 문자열
     * @return array 토큰화된 나무마크
     */
    private function htmlScan(string $text): array
    {
        $result = [];
        $len = strlen($text);
        $now = '';
        $line = '';
        $bracketEntry = false;

        // 리다이렉트 문법
        if(str_starts_with($text, '#') && preg_match('/^#(redirect|넘겨주기) (.+)$/im', $text, $target) && $this->inThread !== false) {
            $rd = 1;
            $html = $this->linkProcessor($text, '[[', $rd);
            array_push($this->links, ['target' => $rd['target'], 'type'=>'redirect']); // backlink
            if(!in_array('not-exist', $rd['class']) && $this->noredirect == 0)
                header('Location: /w/'.$target[1]); // redirect only valid link
            $result = array_merge($result, $html);
        }

        // 문법 처리 순서: 리스트 > 인용문 > 삼중괄호 > 표 >
        for($i=0;$i<$len;self::nextChar($text,$i)) {
            $now = self::getChar($text,$i);
            
            //+ 백슬래시 문법
            if($now == "\\"){
                $line .= $now;
                ++$i;
                $line .= self::getChar($text,$i);
                continue;
            }
            
            // 리스트
            if(!$this->linenotend && $line == '' && $now == ' ' && (!$this->wikitextbox || $this->firstlinebreak) && $inner = $this->listParser($text, $i)) {
                array_push($result, $inner);
                $line = '';
                $now = '';
                continue;
            }

            // 인용문
            if($line == '' && str_starts_with(substr($text,$i), '>') && (!$this->wikitextbox || $this->firstlinebreak) && $inner = $this->bqParser($text, $i)) {
                if($inner !== true){
                    array_push($result, $inner);
                    $line = '';
                    $now = '';
                }
                continue;
            }

            // 사실상 삼중괄호전용
            foreach(self::$brackets as $bracket) {
                if(str_starts_with(substr($text,$i), $bracket['open']) && $bracket['multiline'] === true && $inner = $this->bracketParser($text, $i, $bracket)) {
                    $this->linenotend = true;
                    $result = array_merge($result, $this->lineParser($line), $inner);
                    $line = '';
                    $now = '';
                    $bracketEntry = true;
                    continue 2;
                }
            }

            // 표
            if($line == '' && str_starts_with(substr($text,$i), '|',) && $inner = $this->tableParser($text, $i)) {
                array_push($result, $inner);
                $line = '';
                $now = '';
                continue;
            }

            //+ 빈 줄 삽입 오류 수정
            if($now == "\n" && $line == '' && !$bracketEntry){
                // empty line
                $this->linenotend = false;
                if($this->wikitextbox && !$this->firstlinebreak){
                    $this->firstlinebreak = true;
                    continue;
                }
                $this->firstlinebreak = true;
                array_push($result, ['type' => 'plaintext', 'text' => '<br>']);
            }elseif($now == "\n") {
                // something in line
                $this->firstlinebreak = true;
                $this->linenotend = false;
                $line .= $now;
                $result = array_merge($result, $this->lineParser($line));
                $line = '';
            }else
                $line.= $now; //+ Anti-XSS
        }
        if($line != '')
            $result = array_merge($result, $this->lineParser($line));
        if($this->wikitextbox !== true){
            array_push($this->fnset, $this->fn_names);
            array_push($result, ['type' => 'footnotes', 'from' => $this->lastfncount, 'until' => $this->fn_cnt]);
        }

        // 분류 모음
        if(!empty($this->category) && $this->inThread !== false) {
            array_push($result, ['type' => 'categories']);
            /* .= '<div id="categories" class="wiki-categories">
                            <h2>분류</h2>
                            <ul>';
            foreach($this->category as $category) {
                $result .= '<li class="wiki-categories">'.$this->linkProcessor(':분류:'.$category.'|'.$category, '[[').'</li>';
            }
            $result .= '</ul></div>';*/
        }
        unset($line, $text, $inner);
        return $result;
    }

    //+ Rebuilt by PRASEOD-
    private function bqParser($text, &$offset): array|false
    {
        $len = strlen($text);
        $linestart = true;
        $eol = false;
        $innerstr = '';
        $inEscape = 0;

        if($this->bqcount > 7)
            return true;

        $this->bqcount++;
        

        for($i=$offset;$i<$len;self::nextChar($text, $i)) {
            $now = self::getChar($text, $i);

            if($linestart && $now == '>'){
                $linestart = false;
                if($eol){
                    $innerstr .= "\n";
                    $eol = false;
                }
                continue;
            }elseif($linestart && $inEscape < 1)
                break;
            
            if(str_starts_with(substr($text,$i), '{{{')){
                $i += 2;
                $innerstr .= '{{{';
                $inEscape++;
                continue;
            }
            if(str_starts_with(substr($text,$i), '}}}') && $inEscape > 0){
                $i += 2;
                $innerstr .= '}}}';
                $inEscape--;
                continue;
            }
            
            if($linestart)
                $linestart = false;

            if($now == "\n"){
                $linestart = $eol = true;
            }

            $innerstr .= $now;
        }

        $innerhtml = $this->blockParser($innerstr, true, true);
        //var_dump($innerstr, htmlspecialchars($innerhtml));
        $offset = $i-1;
        $this->bqcount--;
        return ['type' => 'blockquote', 'html' => $innerhtml];
    }

    //+ Rebuilt by PRASEOD-
    private function tableParser($text, &$offset): array|false
    {   
        $token = ['type' => 'table', 'class' => [], 'caption' => null, 'colstyle' => [], 'rows' => []];
        $tableinit = true;
        $tableAttr = [];
        $tableCls = [];
        $tdAttr = [];
        $tableattrinit = [];
        $tdInnerStr = '';
        $unloadedstr = '';
        $len = strlen($text);
        $i = $offset;
        $emptytd = true;
        $inEscape = 0;
        $brInEscape = false;
        $intd = true;
        $rowIndex = 0;
        $colIndex = 0;
        $rowspan = 0;
        $colspan = 0;

        // caption 파싱은 처음에만
        if(str_starts_with(substr($text,$i), '|') && !str_starts_with(substr($text,$i), '||') && $tableinit === true) {
            $caption = explode('|', substr($text,$i));
            if(count($caption) < 3)
                return false;
            $token['caption'] = $this->blockParser($caption[1]);
            $hasCaption = true;
            $tableinit = false;
            //   (|)   (caption content)   (|)
            $i += 1 + strlen($caption[1]) + 1;
        }elseif(str_starts_with(substr($text,$i), "||||\n")){
            array_push($token['rows'], []);
            $i += 5;
            $tableinit = false;
            $hasCaption = false;
        }elseif(str_starts_with(substr($text,$i), '||') && $tableinit === true){
            $i += 2;
            $hasCaption = false;
            $tableinit = false;
        }elseif($tableinit === true)
            return false;

        // text 변수에는 표 앞부분의 ||가 제외된 상태의 문자열이 있음
        for($i; $i<$len; self::nextChar($text,$i)){
            $now = self::getChar($text,$i);

            //+ 백슬래시 문법
            if($now == "\\" && $inEscape < 1 && !$brInEscape){
                $emptytd = false;
                $unloadedstr .= $now;
                $unloadedstr .= self::nextChar($text, $i);
                continue;
            }elseif($inEscape < 1 && !$intd && str_starts_with(substr($text,$i), '##')){
                $i = strpos($text,"\n",$i);
                //continue;
            }
            
            if(str_starts_with(substr($text,$i), '{{{') && strpos(substr($text, $i), '}}}') !== false){
                // 삼중괄호 안 ||를 무효화 처리하기 위한 부분
                $emptytd = false;
                $brInEscape = false;
                $unloadedstr .= '{{{';
                $inEscape++;
                $i += 2;
                continue;
            }elseif(str_starts_with(substr($text,$i), '}}}') && $inEscape > 0){
                $unloadedstr .= '}}}';
                $inEscape--;
                $i += 2;
                if($inEscape === 0)
                    $brInEscape = false;
                continue;
            }elseif(str_starts_with(substr($text,$i), '||')) {
                if($inEscape > 0){
                    $unloadedstr .= '||';
                    $i++;
                    continue;
                }
                if($intd == true && $tdInnerStr == '' && $unloadedstr == '' && $emptytd){
                    if($colspan > 0){
                        ++$colspan;
                    }else{
                        $colspan = 2;
                    }
                    ++$i;
                    continue;
                }elseif($intd === true){
                    //td end and new td start
                    if(!isset($tdAttr['text-align'])){
                        // 공백으로 정렬
                        $start = (substr($unloadedstr, 0, 1) === ' ');
                        $end = (substr($unloadedstr, -1, 1) === ' ');
                        if($start && $end){
                            $tdAttr['text-align'] = 'center';
                            $unloadedstr = substr($unloadedstr, 1, strlen($unloadedstr) - 2);
                        }elseif($start && !$end){
                            $tdAttr['text-align'] = 'right';
                            $unloadedstr = substr($unloadedstr, 1, strlen($unloadedstr) - 1);
                        }elseif(!$start && $end){
                            $tdAttr['text-align'] = 'left';
                            $unloadedstr = substr($unloadedstr, 0, strlen($unloadedstr) - 1);
                        }
                    }
                    
                    $tdInnerStr .= $this->blockParser($unloadedstr);
                    $unloadedstr = '';
                    $token['rows'][$rowIndex]['cols'][$colIndex] = ['text' => $tdInnerStr, 'style' => $tdAttr];
                    $tdAttr = [];
                    if($rowspan > 0){
                        $token['rows'][$rowIndex]['cols'][$colIndex]['rowspan'] = $rowspan;
                        $rowspan = 0;
                    }
                    if($colspan > 0){
                        $token['rows'][$rowIndex]['cols'][$colIndex]['colspan'] = $colspan;
                        $colspan = 0;
                    }
                    $tdInnerStr = '';
                    ++$colIndex;
                    ++$i;
                    $emptytd = true;
                    continue;
                }elseif($intd === false){
                    // new td start
                    $intd = true;
                    $emptytd = true;
                    ++$i;
                    continue;
                }
                continue;
            }elseif($tdInnerStr == '' && $unloadedstr == '' && str_starts_with(substr($text,$i), '<') && preg_match('/^((<(tablealign|table align|tablebordercolor|table bordercolor|tablecolor|table color|tablebgcolor|table bgcolor|tablewidth|table width|rowbgcolor|rowcolor|colbgcolor|colcolor|width|height|color|bgcolor)=[^>]+>|<(-[0-9]+|\|[0-9]+|\^\|[0-9]+|v\|[0-9]+|\:|\(|\)|(#?[0-9A-Za-z]+))>)+)/', strtolower(substr($text,$i, self::seekStr($text, "\n", $i) - $i)), $match) && (empty($match[5]) || self::chkColor($match[5]))){
                $attrs = explode('><', substr($match[1], 1, strlen($match[1])-2));
                $emptytd = false;
                foreach ($attrs as $attr){
                    $attr = strtolower($attr);
                    if(preg_match('/^([^=]*)=([^=]*)$/', $attr, $tbattr)){
                        $attrxs = str_replace(' ', '', $tbattr[1]);
                        // 속성은 최초 설정치가 적용됨
                        if(
                            !in_array($attrxs, $tableattrinit) && (
                                (in_array($tbattr[1], ['tablealign', 'table align']) && in_array($tbattr[2], ['center', 'left', 'right'])) ||
                                (in_array($tbattr[1], ['tablewidth', 'table width']) && preg_match('/^-?[0-9.]*(px|%|)$/', $tbattr[2], $tbw)) || 
                                (in_array($tbattr[1], ['tablebgcolor', 'table bgcolor', 'tablecolor', 'table color', 'tablebordercolor', 'table bordercolor']) &&
                                self::chkColor($tbattr[2]))
                            )
                        ){
                            // 표 속성
                            $i += strlen($tbattr[0]) + 2;
                            array_push($tableattrinit, $attrxs);
                            switch($attrxs){
                                case 'tablebgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                case 'tablecolor':
                                    $tbAttrNm = 'color';
                                    break;
                                //case 'tablebordercolor':
                                //    $tbAttrNm = 'border-color';
                                //    break;
                                case 'tablebgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                case 'tablewidth':
                                    $tbAttrNm = 'width';
                                    if($tbw[1] == '')
                                        $tbattr[2] .= 'px';
                                    break;
                                default:
                                    $tbAttrNm = $attrxs;
                            }
                            if($attrxs == 'tablealign' && ($tbattr[2] == 'center' || $tbattr[2] == 'right'))
                                array_push($tableCls, 'table-'.$tbattr[2]);
                            else
                                $tableAttr[$tbAttrNm] = $tbattr[2];
                        }elseif(
                            // 개별 행 속성
                            in_array($tbattr[1], ['rowbgcolor', 'rowcolor']) && 
                            self::chkColor($tbattr[2])
                        ){
                            $i += strlen($tbattr[0]) + 2;
                            switch($tbattr[1]){
                                case 'rowbgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                case 'rowcolor':
                                    $tbAttrNm = 'color';
                                    break;
                                default:
                                    $tbAttrNm = $tbattr[1];
                            }
                            $token['rows'][$rowIndex]['style'][$tbAttrNm] = $tbattr[2];
                        }elseif(
                            // 개별 열 속성
                            in_array($tbattr[1], ['colbgcolor', 'colcolor']) && 
                            self::chkColor($tbattr[2])
                        ){
                            $i += strlen($tbattr[0]) + 2;
                            switch($tbattr[1]){
                                case 'colbgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                case 'colcolor':
                                    $tbAttrNm = 'color';
                                    break;
                                default:
                                    $tbAttrNm = $tbattr[1];
                            }
                            $token['colstyle'][$colIndex][$rowIndex][$tbAttrNm] = $tbattr[2];
                            $tdAttr[$tbAttrNm] = $tbattr[2];
                        }elseif(
                            // 개별 셀 속성
                            (in_array($tbattr[1], ['width', 'height']) && preg_match('/^-?[0-9.]*(px|%)?$/', $tbattr[2])) ||
                            (in_array($tbattr[1], ['color', 'bgcolor']) && self::chkColor($tbattr[2]))
                        ){
                            $i += strlen($tbattr[0]) + 2;
                            switch($tbattr[1]){
                                case 'bgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                default:
                                    $tbAttrNm = $tbattr[1];
                            }
                            $tdAttr[$tbAttrNm] = $tbattr[2];
                        }
                    }elseif(preg_match('/^(-|\|)([0-9]+)$/', $attr, $tbspan)){ // 
                        $i += strlen($tbspan[0]) + 2;
                        // <|n>
                        if($tbspan[1] == '-')
                            $colspan = $tbspan[2];
                        elseif($tbspan[1] == '|')
                            $rowspan = $tbspan[2];
                    }elseif(preg_match('/^(\^|v|)\|([0-9]+)$/', $attr, $tbalign)){ // 수직정렬 및 colspan
                        $i += strlen($tbalign[0]) + 2;
                        // <^|n>
                        if($tbalign[1] == '^')
                            $tdAttr['vertical-align'] = 'top';
                        elseif($tbalign[1] == 'v')
                            $tdAttr['vertical-align'] = 'bottom';
                        
                        $colspan = $tbalign[2];
                    }elseif($attr == ':' || $attr == '(' || $attr == ')'){ // 수평정렬
                        switch($attr){
                            case ':':
                                $tdAttr['text-align'] = 'center';
                                $i += 3;
                                break;
                            case '(':
                                $tdAttr['text-align'] = 'left';
                                $i += 3;
                                break;
                            case ')':
                                $tdAttr['text-align'] = 'right';
                                $i += 3;
                        }
                    }else{
                        $i += strlen($attr) + 2;
                        $tdAttr['background-color'] = $attr;
                    }
                }
                --$i;

                // 정체불명의 무한루프 방지
                if(isset($last_temp) && $last_temp === $i)
                    ++$i;
                $last_temp = $i;
                continue;
            }elseif(str_starts_with(substr($text,$i), "\n")){
                if(!$this->firstlinebreak)
                    $this->firstlinebreak = true;
                
                if(str_starts_with(substr($text,$i), "\n||") && ($unloadedstr == '' && $tdInnerStr == '') && $emptytd) {
                    // 행갈이
                    ++$rowIndex;
                    $colIndex = 0;
                    $intd = false;
                }elseif(str_starts_with(substr($text,$i), "\n") && self::getChar($text,$i+1) !== '|' && !str_starts_with(substr($text,$i+1),'##') && 
                    ($unloadedstr == '' && $tdInnerStr == '' && $emptytd) && 
                    (($rowIndex !== 0 || $colIndex !== 0))
                ) {
                    // end of table
                    ++$i;
                    break;
                }else //if(str_starts_with(substr($text,$i), "\n"))
                {
                    // just breaking line
                    $unloadedstr .= $now;
                }
            }else{
                // other string
                $unloadedstr.=$now;
                $emptytd = false;
            }
        }

        $token['class'] = $tableCls;
        $token['style'] = $tableAttr;
        $offset = $i-1;
        return $token;
    }

    //+ Rebuilt by PRASEOD-
    private function listParser($text, &$offset): array|false
    {
        $len = strlen($text);
        $list = [];
        $linestart = true;
        $emptyline = false;
        $skipbreak = false;
        $eol = false;
        $listdata = ['type' => 'list', 'lists' => []];
        $html = '';
        $inEscape = 0;
        ++$offset;
        ++$this->ind_level;
        
        for($i=$offset; $i<$len; self::nextChar($text,$i)){
            $now = self::getChar($text, $i);

            if($eol && $now !== ' ' && $inEscape < 1){
                $list['html'] = $this->blockParser($html, true);
                $html = '';
                array_push($listdata['lists'], $list);
                $list = [];
                break; // 리스트 끝
            }elseif($eol){
                // 현재 들여쓰기 칸만큼 스킵
                //if(!$skipbreak)  리스트 개행 간격
                $now = self::nextChar($text,$i);
            }
            
            if(str_starts_with(substr($text,$i), '{{{') && strpos(substr($text,$i), '}}}') !== false){
                $html .= '{{{';
                $i += 2;
                ++$inEscape;
                continue;
            }elseif(str_starts_with(substr($text,$i), '}}}') && $inEscape > 0){
                $html .= '}}}';
                $i += 2;
                --$inEscape;
                continue;
            }elseif(
                ($linestart && preg_match('/^((\*|1\.|a\.|A\.|I\.|i\.)(#[^ ]*)? ?)(.*)/', substr($text,$i), $match))
            ){
                // 리스트
                if(!empty($html)){
                    $list['html'] = $this->blockParser($html, ($listdata['listtype'] == 'wiki-indent'));
                    $html = '';
                    array_push($listdata['lists'], $list);
                    $list = [];
                }
                $listdata['listtype'] = self::$list_tags[$match[2]];
                if(!isset($listdata['start']))
                    $listdata['start'] = substr($match[3], 1);
                
                if(!is_numeric($listdata['start']) || $listdata['start'] < 0) // #숫자 여부 + 음수 아님
                    $listdata['start'] = 1;

                $i += strlen($match[1]) - 1;

                $emptyline = false;
                if($eol)
                    $eol = false;
                if($linestart)
                    $linestart = false;
                continue;
            }elseif($linestart && !$eol){ //  && !$this->fromblockquote
                // 들여쓰기
                $listdata['listtype'] = 'wiki-indent';
            }elseif($eol){
                $html .= "\n";
                $eol = false;
            }

            if($linestart){
                $linestart = false;
            }
            
            if($now == "\n" && $inEscape < 1){
                // 공백 처리
                //if($emptyline)
                //    $skipbreak = true;
                //$emptyline = true;
                $eol = true;
                $linestart = true;
                continue;
            }

            $html .= $now;
            //$emptyline = false;
        }

        if(!empty($html)){
            $list['html'] = $this->blockParser($html, ($listdata['listtype'] == 'wiki-indent'));
            array_push($listdata['lists'], $list);
            $list = [];
        }

        $offset = $i - 1;
        --$this->ind_level;
        unset($text);
        if(empty($listdata['lists']))
            return false;

        //var_dump($listdata);    
        return $listdata;
    }

    private function lineParser($line): array|false
    {
        $result = [];
        $token = [];

        //+ 공백 있어서 안 되는 오류 수정
        if(str_starts_with($line, '##')) {
            // 주석
            $line = '';
        }elseif(str_starts_with($line, '=') && preg_match('/^(=+) (.*?) (=+) *(\n)?$/', trim($line), $match) && $match[1]===$match[3]) {
            // 문단
            $level = strlen($match[1]);
            $innertext = $match[2];

            //+ 접힌문단 기능 추가
            if (preg_match('/^# (.*) #$/', $innertext, $ftoc)) {
                $folded = true;
                $innertext = $ftoc[1];
            }else{
                $folded = false;
            }

            $id = $this->tocInsert($this->toc, $innertext, $level);
            $token = ['type' => 'heading', 'level' => $level, 'section' => $id, 'folded' => $folded ];
            

            
            if(preg_match('/\[anchor\((.*)\)\]/', $innertext, $anchor)){
                $RealContent = str_replace($anchor[0], '', $innertext);
                //$result .= '<a id="'.$anchor[1].'"></a><span id="'.trim($RealContent).'">'.$RealContent;
            }else{
                $RealContent = $innertext;
                //$result .= '<span id="'.strip_tags($innertext).'">'.$innertext;
            }

            $token['id'] = $RealContent;
            $token['text'] = $this->blockParser($innertext);
            array_push($result, $token);
            
            $line = '';
        }

        //+ 수평줄 문제 개선
        if(preg_match('/^[ ]*(-{4,9})$/', $line)){
            array_push($result, ['type' => 'plaintext', 'text' => '<hr class="wiki-hr">']);
            $line = '';
        }

        // 행에 뭐가 있을때
        if($line != '') {
            $line = $this->formatParser($line);
            //if(!$this->linenotend)
            //    array_push($result, ['type' => 'plaintext', 'text' => '<br>']); //+ {{{#!wiki}}} 문법 앞에서 개행되는 문제 수정
            //else
                $result = array_merge($result, $line);
        }

        unset($line, $token, $innertext);

        return $result;
    }

    private function blockParser($block, $isWikiTextBox = false, $isBlockQuote = false): string|null
    {
        $defaultw = $this->wikitextbox;
        $defaultb = $this->firstlinebreak;
        $defaultq = $this->fromblockquote;
        $defaultl = $this->linenotend;
        $this->wikitextbox = true;
        $this->linenotend = false;
        $this->firstlinebreak = ($isWikiTextBox);
        $this->fromblockquote = ($isBlockQuote);
        $content = $this->toHtml($block);
        $this->wikitextbox = $defaultw;
        $this->linenotend = $defaultl;
        $this->firstlinebreak = $defaultb;
        $this->fromblockquote = $defaultq;

        return $content;
    }

    private function bracketParser($text, &$now, $bracket): array|false
    {
        $len = strlen($text);
        $cnt = 0;
        $unloadedstr = '';
        $loadedstr = [];
        $openlen = strlen($bracket['open']);

        if(!isset($bracket['strict']))
            $bracket['strict'] = true;

        // 한글자씩 스캔
        for($i=$now+$openlen;$i<$len;self::nextChar($text,$i)) {
            $char = self::getChar($text, $i);
            //+ 백슬래시 문법 지원
            /*if($char == "\\" && !$isEscape) {
                // {{{ }}} 구문이 아닌 경우 \ 처리
                ++$i;
                $unloadedstr .= self::getChar($text, $i);
                $char = '';
                continue;
            }else*/
            if(str_starts_with(substr($text,$i), $bracket['open']) && $bracket['open'] !== $bracket['close']) {
                // 중첩구간처리
                $cnt++;
                $i += strlen($bracket['open']) - 1;
                $unloadedstr .= $bracket['open'];
                continue;
            }elseif(str_starts_with(substr($text,$i), $bracket['close']) && $cnt > 0){
                $cnt--;
                $i += strlen($bracket['close']) - 1;
                $unloadedstr .= $bracket['close'];
                continue;
            }elseif(str_starts_with(substr($text,$i), $bracket['close']) && $cnt === 0) {
                // 닫는괄호
                if($bracket['strict'] && $bracket['multiline'] && strpos($unloadedstr, "\n")===false)
                    return false;
                
                $argarray = [$unloadedstr];
                if($bracket['processor'] == 'textProcessor')
                    array_push($argarray, $bracket['open']);

                $loadedstr = array_merge($loadedstr, call_user_func_array([$this, $bracket['processor']],$argarray));
                $now = $i+strlen($bracket['close'])-1;
                return $loadedstr;
            }elseif(!$bracket['multiline'] && $char == "\n")
                return false; // 개행금지 문법에서 개행 발견
            elseif($char == "\n")
                $this->firstlinebreak = true;
            
            $unloadedstr .= $char;
        }
        return false;
    }

    //+ 역슬래시 지원
    private function formatParser($line): array|false
    {
        $line_len = strlen($line);
        $result = [];
        $inline = '';
        for($j=0;$j<$line_len;self::nextChar($line,$j)) {
            $now = self::getChar($line,$j);
            if($now == "\\"){
                ++$j;
                $inline .= htmlspecialchars(self::getChar($line,$j));
                continue;
            }elseif($now == "\n"){
                array_push($result, ['type' => 'plaintext', 'text' => $inline]);
                array_push($result, ['type' => 'plaintext', 'text' => '<br>']);
                $inline = '';
                continue;
            }else {
                foreach(self::$brackets as $bracket) {
                    $nj=$j;
                    if(str_starts_with(substr($line,$j), $bracket['open']) && $bracket['multiline'] === false && $inner = $this->bracketParser($line, $nj, $bracket)) {
                        array_push($result, ['type' => 'plaintext', 'text' => $inline]);
                        $result = array_merge($result, $inner);
                        $inline = '';
                        $j = $nj;
                        continue 2;
                    }
                }
                $inline .= htmlspecialchars($now);
            }

            // 외부이미지
            /*if(str_starts_with(substr($line,$j), 'http') && preg_match('/(https?:\/\/[^ ]+\.(jpg|jpeg|png|gif))(?:\?([^ ]+))?/i', $line, $match, 0, $j)) {
                if($this->imageAsLink)
                    $innerstr = '<span class="alternative">[<a class="external" target="_blank" href="'.$match[1].'">image</a>]</span>';
                else {
                    $paramtxt = '';
                    $csstxt = '';
                    if(!empty($match[3])) {
                        preg_match_all('/[&?]?([^=]+)=([^\&]+)/', htmlspecialchars_decode($match[3]), $param, PREG_SET_ORDER);
                        foreach($param as $pr) {
                            // 이미지 크기속성
                            switch($pr[1]) {
                                case 'width':
                                    if(preg_match('/^[0-9]+$/', $pr[2]))
                                        $csstxt .= 'width: '.$pr[2].'px; ';
                                    else
                                        $csstxt .= 'width: '.$pr[2].'; ';
                                    break;
                                case 'height':
                                    if(preg_match('/^[0-9]+$/', $pr[2]))
                                        $csstxt .= 'height: '.$pr[2].'px; ';
                                    else
                                        $csstxt .= 'height: '.$pr[2].'; ';
                                    break;
                                case 'align':
                                    if($pr[2]!='center')
                                        $csstxt .= 'float: '.$pr[2].'; ';
                                    break;
                                default:
                                    $paramtxt.=' '.$pr[1].'="'.$pr[2].'"';
                            }
                        }
                    }
                    $paramtxt .= ($csstxt!=''?' style="'.$csstxt.'"':'');
                    $innerstr = '<img src="'.$match[1].'"'.$paramtxt.'>';
                }
                $line = substr($line, 0, $j).$innerstr.substr($line, $j+strlen($match[0]));
                $line_len = strlen($line);
                $j+=strlen($innerstr)-1;
                continue;
            }elseif(str_starts_with(substr($line,$j), 'attachment') && preg_match('/attachment:([^\/]*\/)?([^ ]+\.(?:jpg|jpeg|png|gif|svg))(?:\?([^ ]+))?/i', $line, $match, 0, $j) && $this->inThread !== false) {
                // 파일
                if($this->imageAsLink)
                    $innerstr = '<span class="alternative">[<a class="external" target="_blank" href="https://attachment.namu.wiki/'.($match[1]?($match[1]=='' || substr($match[1], 0, -1)==''?'':substr($match[1], 0, -1).'__'):rawurlencode($this->WikiPage->title).'__').$match[2].'">image</a>]</span>';
                else {
                    $paramtxt = '';
                    $csstxt = '';
                    if(!empty($match[3])) {
                        preg_match_all('/([^=]+)=([^\&]+)/', $match[3], $param, PREG_SET_ORDER);
                        foreach($param as $pr) {
                            switch($pr[1]) {
                                case 'width':
                                    if(preg_match('/^[0-9]+$/', $pr[2]))
                                        $csstxt .= 'width: '.$pr[2].'px; ';
                                    else
                                        $csstxt .= 'width: '.$pr[2].'; ';
                                    break;
                                case 'height':
                                    if(preg_match('/^[0-9]+$/', $pr[2]))
                                        $csstxt .= 'height: '.$pr[2].'px; ';
                                    else
                                        $csstxt .= 'height: '.$pr[2].'; ';
                                    break;
                                case 'align':
                                    if($pr[2]!='center')
                                        $csstxt .= 'float: '.$pr[2].'; ';
                                    break;
                                default:
                                    $paramtxt.=' '.$pr[1].'="'.$pr[2].'"';
                            }
                        }
                    }
                    $paramtxt .= ($csstxt!=''?' style="'.$csstxt.'"':'');
                    $innerstr = '<img src="https://attachment.namu.wiki/'.($match[1]?($match[1]=='' || substr($match[1], 0, -1)==''?'':substr($match[1], 0, -1).'__'):rawurlencode($this->WikiPage->title).'__').$match[2].'"'.$paramtxt.'>';
                }
                $line = substr($line, 0, $j).$innerstr.substr($line, $j+strlen($match[0]));
                $line_len = strlen($line);
                $j+=strlen($innerstr)-1;
                continue;
            }*/ 
        }
        if(strlen($inline) > 0)
            array_push($result, ['type' => 'plaintext', 'text' => $inline]);
        
        unset($line, $inline);
        return $result;
    }

    private function renderProcessor($text): array
    {
        // + 대소문자 구분 반영
        if(str_starts_with($text, '#!html')) {
            if($this->inThread)
                return [['type' => 'void']];
            return [['type' => 'inline-html', 'text' => substr($text, 7)]];
        } elseif(preg_match('/^#!wiki(.*)\n/', $text, $match)) {
            // + {{{#!wiki }}} 문법
            $divattr = '';
            if(preg_match('/style=\".*\"/', $match[1], $_dattr))
                $divattr = $_dattr[0];
             
            $text = substr($text, strlen($match[0]));
            return [['type' => 'wikitext', 'attr' => $divattr, 'text' => $this->blockParser($text, true)]];
        } elseif(preg_match('/^#!folding(.*)\n/', $text, $match)){
            if(strlen($match[1]) < 1)
                $str = 'More';
            else
                $str = $match[1];
            
            $text = substr($text, strlen($match[0]));
            return [['type' => 'folding', 'text' => $str, 'html' => $this->blockParser($text, true)]];    
        } elseif(preg_match('/^#!syntax ('.implode('|', self::$syntaxList).')/', $text, $match)) {
            // 구문
            return [['type' => 'syntax', 'lang' => $match[1], 'text' => substr($text, strlen($match[0]))]];
        } elseif(preg_match('/^\+([1-5]) (.*)/', $text, $size)) {
            // {{{+큰글씨}}}
            return [['type' => 'wiki-size', 'size' => 'up-'.$size[1], 'text' => $this->blockParser(substr($text, strlen($size[1]) + 1))]];
        } elseif(preg_match('/^\-([1-5]) (.*)/', $text, $size)) {
            // {{{-작은글씨}}}
            return [['type' => 'wiki-size', 'size' => 'down-'.$size[1], 'text' => $this->blockParser(substr($text, strlen($size[1]) + 1))]];
        } elseif(preg_match('/^(#[A-Fa-f0-9]{3}|#[A-Fa-f0-9]{6}|#([A-Za-z]+)) (.*)/', $text, $color) && (self::chkColor($color[1]) || self::chkColor($color[2]))) {
            //if(empty($color[1]))
            //    return [['type' => 'plaintext', 'text' => $text]];
            return [['type' => 'colortext', 'color' => (empty($color[2])?$color[1]:$color[2]), 'text' => $this->blockParser(substr($text, strlen($color[1])))]];
        } else {
            return [['type' => 'rawtext', 'text' => htmlspecialchars($text)]];
            // 문법 이스케이프
        }
    }

    //+ rebuilt by PRASEOD-
    private function linkProcessor($text, &$rd=0): array
    {
        $target = null;
        $display = null;
        $sharp = '';
        $inSharp = '';
        $href = null;
        $unloadedstr = '';
        $classList = [];
        $imgAttr = '';
        $len = strlen($text);
        $exception = '';
        /*
        * img attr
        align != center:
            align> style="..."
            wrapper> style="a:100"
        align=center:
            wrapper> style="..."

        */

        if(strpos($text, '파일:') === 0){
            $linkpart = explode('|', $text);
            //if(!$this->WikiPage->pageExists($linkpart[0])){
            //    return ['type' => 'link', 'linktype' => 'file', 'class' => ['wiki-link-internal', 'not-exist'], 'href' => '/w/'.$linkpart[0], 'text' => $linkpart[0]];
            //}
            $imgAlign = 'normal';
            $changed = '';
            $preserved = '';
            $wrapTag = '';
            $bgcolor = '';
            if(count($linkpart) > 1){
                $options = explode('&', $linkpart[1]);
                foreach($options as $option){
                    $opt = explode('=', $option);

                    if(($opt[0] == 'height' || $opt[0] == 'width') && !preg_match('/^[0-9]/', $opt[1]) || ($opt[0] == 'align' && !in_array($opt[1], ['left', 'center', 'right','middle','bottom','top'])) || !self::chkColor($opt[1])){
                        // invalid format
                        continue;
                    }elseif(($opt[0] == 'height' || $opt[0] == 'width') && preg_match('/%$/', $opt[1]))
                        $opt[1] = intval($opt[1]).'%';
                    else
                        $opt[1] = intval($opt[1]).'px';

                    switch($opt[0]){
                        case 'width':
                            $changed .= 'width: '.$opt[1].';';
                            $preserved .= 'width: 100%;';
                            $wrapTag .= ' width="100%"';
                            break;
                        case 'height':
                            $changed .= 'height: '.$opt[1].';';
                            $preserved .= 'height: 100%;';
                            $wrapTag .= ' height="100%"';
                            break;
                        case 'align':
                            $imgAlign = $opt[1];
                            break;
                        case 'bgcolor':
                            $bgcolor .= 'background-color:'.$opt[1].';';
                    }
                }
            }
            
            if($imgAlign == 'center'){
                $attr_align = '';
                $attr_wrapper = ' style="'.$imgAttr.$bgcolor.'"';
            }else{
                $attr_align = ' style="'.$changed.'"';
                $attr_wrapper = ' style="'.$preserved.$bgcolor.'"';
            }

            $fileName = substr($linkpart[0], strlen('파일:'));
            if(strpos($fileName, '.') !== false){
                $fnexp = explode('.', $fileName);
                $fnWithoutExt = implode('.', array_slice($fnexp, 0, count($fnexp) - 1));
            }else{
                $fnWithoutExt = $fileName;
            }
            
            $href = '/file/'.hash('sha256', $fileName);
            if($rd === 0) array_push($this->links, ['target'=>$linkpart[0], 'type'=>'file']);
            
            if($rd !== 0) $rd = ['target' => $linkpart[0], 'class' => $classList];
            return [['type' => 'link', 'linktype' => 'file', 'class' => ['wiki-link-internal'], 'href' => $href, 'text' => $linkpart[0], 'imgalign' => $imgAlign, 'attralign' => $attr_align, 'attrwrapper' => $attr_wrapper, 'wraptag' => $wrapTag, 'fnwithouttext' => $fnWithoutExt]];
        }elseif(strpos($text, '분류:') === 0){
            array_push($this->category, substr($text, strlen('분류:')));
            if($rd === 0) array_push($this->links, ['target'=>$target, 'type'=>'category']);
            if($rd !== 0) $rd = ['target' => $text, 'class' => $classList];
            return [['type' => 'void']];
        }

        for($i=0; $i<$len; self::nextChar($text,$i)){
            $now = self::getChar($text,$i);
            if($now == "\\"):
                ++$i;
                if($target !== null)
                    $unloadedstr .= self::getChar($text,$i);
                else
                    $inSharp .= self::getChar($text,$i);
                continue;
            elseif($now == '#' && $target === null):
                $unloadedstr .= $inSharp;
                $inSharp = '#';
                continue;
            elseif($now == '|' && $target === null):
                if($unloadedstr == '')
                    $target = $inSharp;
                else
                    $target = $unloadedstr;
                //$inSharp = '';
                $unloadedstr = '';
                continue;
            elseif($target !== null):
                // url target 지정 시 영향받지 않음
            endif;
            if($target !== null)
                $unloadedstr .= $now;
            else
                $inSharp .= $now;
        }
        
        if($target === null){
            if($inSharp[0] == '#' && $unloadedstr == '')
                $target = $inSharp;
            elseif($inSharp[0] == '#')
                $target = $unloadedstr;
            else
                $target = $unloadedstr.$inSharp;
        }else
            $display = $this->blockParser($unloadedstr);
        
        if($sharp == '' && $inSharp[0] == '#')
            $sharp = $inSharp;
        $unloadedstr = '';
        

        if($display === null){
            $display = $target;
        }

        if(preg_match('@^https?://([^\.]+\.)+[^\.]{2,}$@', $target, $domain)){
            $href = $target;
            array_push($classList, 'wiki-link-external');
        }else{
            // ../와 /로 시작하는 것은 이스케이프 문자열을 무시함.
            if(strpos($target, '../') === 0){
                if(strlen($target) > 3)
                    $restpart = substr($target, 2);
                else
                    $restpart = '';
                $exptar = explode('/', $this->title);
                if(count($exptar) > 1)
                    $target = implode('/', array_slice($exptar, 0, count($exptar) - 1)).$restpart;
                else
                    $target = $this->title;
            }elseif(strpos($target, '/') === 0)
                $target = $this->title.$target;
            elseif(strpos($target, ':파일:') === 0 || strpos($target, ':분류:') === 0){
                $target = substr($target, strpos($target, ':분류:') + 1);
                
                //$display = $target;
            }
            
            if(strpos($target, '#') === 0){
                $href = '';
                $target = '';
            }

            if($target == $this->title && $sharp == '')
                array_push($classList, 'wiki-self-link');
            elseif($rd === 0){
                array_push($this->links, ['target'=>$target, 'type'=>'link']);
                array_push($classList, 'wiki-link-internal');
            }
            if($href === null)
                $href = '/w/'.$target.$exception;
        }

        //if(in_array('wiki-link-internal', $classList) && !$this->WikiPage->pageExists($target))
        //    array_push($classList, 'not-exist');
        
        if($rd !== 0) $rd = ['target' => $target.$exception, 'class' => $classList];
        
        return [['type' => 'link', 'linktype' => 'general', 'class' => $classList, 'target' => $target, 'href' => $href.$sharp, 'text' => $display]];
    }

    // 대괄호 문법
    private function macroProcessor($text): array
    {
        $macroName = strtolower($text);
        if(!empty($this->macro_processors[$macroName]))
            return $this->macro_processors[$macroName]();
        
        switch($macroName) {
            case 'br':
                return [['type' => 'plaintext', 'text' => '<br>']];
            case 'date':
            case 'datetime':
                return [['type' => 'plaintext', 'text' => date('Y-m-d H:i:sO')]];
            case '목차':
            case 'tableofcontents':
                return [['type' => 'toc']];
            case '각주':
            case 'footnote':
                $from = $this->lastfncount;
                $this->lastfncount = $this->fn_cnt;
                array_push($this->fnset, $this->fn_names);
                $this->fn_names = [];
                return [['type' => 'footnotes', 'from' => $from, 'until' => $this->fn_cnt]];
            case 'clearfix':
                //+ clearfix
                return [['type' => 'plaintext', 'text' => '<div style="clear:both;"></div>']];
            case 'pagecount':
                return [['type' => 'plaintext', 'text' => $this->totalPageCount]];
            default:
                if(preg_match('/^include\((.*)\)$/i', $text, $include) && $include = $include[1]) {
                    if($this->included || $this->inThread)
                        return [['type' => 'void']];

                    $include = explode(',', $include);
                    array_push($this->links, array('target'=>$include[0], 'type'=>'include'));
                    /*if(($page = $this->WikiPage->getPage($include[0])) && !empty($page->text)) {
                        foreach($include as $var) {
                            $var = explode('=', ltrim($var));
                            if(empty($var[1]))
                                $var[1]='';
                            $page->text = str_replace('@'.$var[0].'@', $var[1], $page->text);
                            // 틀 변수
                        }
                        $child = new NamuMark($page);
                        $child->imageAsLink = $this->imageAsLink;
                        $child->included = true;
                        return $child->toHtml();
                    }*/
                    return [['type' => 'wikitext', 'attr' => '', 'text' => '']];
                }
                elseif(preg_match('/^(youtube|nicovideo|kakaotv|vimeo|navertv)\((.*)\)$/i', $text, $include)) {
                    if($this->inThread)
                        return [['type' => 'void']];
                    elseif($include[1] == 'nicovideo' && !preg_match('/(sm)?([0-9]+)?/', $include[2], $m)){
                        if(empty($m[2]))
                            return [['type' => 'void']];
                        elseif(empty($m[2]))
                            $include[2] = 'sm'.$include[2];
                    }
                    
                    $components = explode(',', $include[2]);
                    $var = array();
                    $urlvararr = [];
                    foreach($components as $v) {
                        $v = explode('=', $v);
                        if(empty($v[1]))
                            $v[1]='';
                        $var[$v[0]] = $v[1];

                        if($include[1] == 'youtube' && ($v[0] == 'start' || $v[0] == 'end'))
                            array_push($urlvararr, implode('=',$v));
                    }

                    return [['type' => 'video', 
                    'width' => (!empty($var['width'])?$var['width']:'640'), 
                    'height' => (!empty($var['height'])?$var['height']:'360'), 
                    'src' => self::$videoURL[$include[1]].$components[0].(!empty($urlvararr)?'?'.implode('&', $urlvararr):'')], ];
                    
                }
                elseif(preg_match('/^age\((.*)\)$/i', $text, $include)) {
                    $invalid = false;
                    if($this->inThread)
                        return [['type' => 'void']];

                    if(count(explode('-', $include[1])) !== 3)
                        $invalid = true;
                    
                    if(!preg_match('/([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $include[1], $match) || strtotime(date("Y-m-d", time())) - strtotime($include[1]) <= 0)
                        $invalid = true;

                    if($invalid)
                        return [['type' => 'plaintext', 'text' => 'invalid date']];
                    
                    list($dump, $year, $month, $day) = $match;

                    if($year < 100)
                        $year += 1900;

                    $age = (date("md", date("U", mktime(0, 0, 0, $day, $month, $year))) > date("md")
                        ? ((date("Y") - $year) - 1)
                        : (date("Y") - $year));
                    return [['type' => 'plaintext', 'text' => $age]];
                }
                elseif(preg_match('/^anchor\((.*)\)$/i', $text, $include) && $include = $include[1]) {
                    return [['type' => 'anchor', 'text' => $include]];
                }
                elseif(preg_match('/^dday\((.*)\)$/i', $text, $include) && $include = $include[1]) {
                    $invalid = false;
                    if($this->inThread)
                        return [['type' => 'void']];
                    
                    $nDate = date("Y-m-d");

                    if(!preg_match('/([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $include, $match))
                        $invalid = true;

                    if($invalid)
                        return [['type' => 'plaintext', 'text' => 'invalid dday']];
                        
                    list($dump, $year, $month, $day) = $match;

                    if($year < 100)
                        $year += 1900;

                    if(strtotime($nDate)==strtotime($include))
                        $return = " 0";
                    else
                        $return = (strtotime($nDate)-strtotime($include)) / 86400;
                    return [['type' => 'plaintext', 'text' => $return]];
                }
                elseif(preg_match('/^ruby\((.*)\)$/i', $text, $include)) {
                    if($this->inThread)
                        return [['type' => 'void']];

                    $ruby = explode(',', $include[1]);
                    foreach(array_slice($ruby, 1) as $a){
                        $split = explode('=', $a);
                        if($split[0] == 'ruby'){
                            $rb = $split[1];
                        }elseif($split[0] == 'color' && self::chkColor($split[1])){
                            $color = $split[1];
                        }
                    }
                    
                    if(strlen($rb) > 0 && strlen($ruby[0]) > 0)
                        return [['type' => 'ruby', 'ruby' => $rb, 'text' => $ruby[0], 'color' => $color]];
                    else
                        return [['type' => 'void']];
                }
                elseif(preg_match('/^pagecount\((.*)\)$/i', $text, $include) && $include = $include[1]) {
                    if($this->inThread)
                        return [['type' => 'void']];

                    if(isset($this->pageCount[$include]))
                        return [['type' => 'plaintext', 'text' => $this->pageCount[$include]]];
                    else
                        return [['type' => 'plaintext', 'text' => $this->totalPageCount]];
                }
                /* elseif(preg_match('/^math\((.*)\)$/i', $text, $include) && $include = $include[1]) {
                    if($this->inThread)
                        return [['type' => 'void']];

                    return [['type' => 'math', 'text' => $include]];
                } */
                elseif(preg_match('/^\*([^ ]*)([ ].+)?$/', $text, $note)) {
                    ++$this->fn_cnt;
                    $notetext = !empty($note[2])?$this->blockParser($note[2]):'';
                    if(strlen($note[1]) > 0){
                        $name = strval($note[1]);
                        if(!isset($this->fn_names[$name]))
                            $this->fn_names[$name] = [];
                        array_push($this->fn_names[$name], $this->fn_cnt);
                    }else
                        $name = $this->fn_cnt;
                    
                    if(isset($this->fn_names[$name]) && $this->fn_names[$name][0] == $this->fn_cnt || $name === $this->fn_cnt)
                        $this->fn[$this->fn_cnt] = $notetext;
                        
                    array_push($this->fn_overview, $name);

                    return [['type' => 'footnote', 'name' => htmlspecialchars($name), 'id' => $this->fn_cnt]];
                }
        }
        return [['type' => 'plaintext', 'text' => '['.htmlspecialchars($text).']']];
    }
    
    private function textProcessor($otext, $type): array
    {
        if($type !== '{{{'){
            $text = $this->formatParser($otext);
        }else{
            $text = $otext;
        }
        $tagnameset = [
            "'''" => 'strong',
            "''" => 'em',
            '--' => 'del',
            '~~' => 'del',
            '__' => 'u',
            '^^' => 'sup',
            ',,' => 'sub'
        ];
        switch ($type) {
            case "'''":
                // 볼드체
            case "''":
                // 기울임꼴
            case '--':
            case '~~':
                // 취소선
                // + 수평선 적용 안 되는 오류 수정
            case '__':
                // 목차 / 밑줄
            case '^^':
                // 위첨자
            case ',,':
                // 아래첨자
                return array_merge([['type' => 'text-start', 'effect' => $tagnameset[$type]]], $text, [['type' => 'text-end', 'effect' => $tagnameset[$type]]]);
            case '{{{':
                // 개행 없어도 적용되는 삼중괄호 문법
                if(str_starts_with($text, '#!html')) {
                    if($this->inThread)
                        return [['type' => 'void']];
                    return [['type' => 'inline-html', 'text' => substr($text, 7)]];
                } elseif(preg_match('/^\+([1-5]) (.*)/', $text, $size))
                    return [['type' => 'wiki-size', 'size' => 'up-'.$size[1], 'text' => $this->blockParser(substr($text, strlen($size[1]) + 1))]];
                elseif(preg_match('/^\-([1-5]) (.*)/', $text, $size))
                    return [['type' => 'wiki-size', 'size' => 'down-'.$size[1], 'text' => $this->blockParser(substr($text, strlen($size[1]) + 1))]];
                elseif(preg_match('/^(#[A-Fa-f0-9]{3}|#[A-Fa-f0-9]{6}|#([A-Za-z]+)) (.*)/', $text, $color) && (self::chkColor($color[1]) || self::chkColor($color[2]))) {
                    //if(empty($color[1]) && empty($color[2]))
                    //    return [['type' => 'plaintext', 'text' => $text]];
                    return [['type' => 'colortext', 'color' => (empty($color[2])?$color[1]:$color[2]), 'text' => $this->blockParser(substr($text, strlen($color[1])))]];
                }else // 문법 이스케이프
                    return [['type' => 'escape', 'text' => htmlspecialchars($text)]];
            }
            return [['type' => 'plaintext', 'text' => $type.$text.$type]];
    }

    // 목차 삽입
    private function tocInsert(&$arr, $text, $level, $path = ''): string
    {
        if(empty($arr[0])) {
            $arr[0] = array('name' => $text, 'level' => $level, 'childNodes' => array());
            return $path.'1';
        }
        $last = count($arr)-1;
        $readableId = $last+1;
        if($arr[0]['level'] >= $level) {
            $arr[] = array('name' => $text, 'level' => $level, 'childNodes' => array());
            return $path.($readableId+1);
        }

        return $this->tocInsert($arr[$last]['childNodes'], $text, $level, $path.$readableId.'.');
    }

    private static function getChar($string, int $pointer): false|string
    {
        if(!isset($string[$pointer]))
            return false;
        $char = ord($string[$pointer]);
        if($char < 128){
            return $string[$pointer];
        }else{
            if($char < 224){
                $bytes = 2;
            }elseif($char < 240){
                $bytes = 3;
            }elseif($char < 248){
                $bytes = 4;
            }elseif($char == 252){
                $bytes = 5;
            }else{
                $bytes = 6;
            }
            $str = substr($string, $pointer, $bytes);
            return $str;
        }
    }

    private static function nextChar($string, int &$pointer): false|string
    {
        if(!isset($string[$pointer]))
            return false;
        $pointer += strlen(self::getChar($string,$pointer));
        return self::getChar($string, $pointer);
    }

    private static function seekStr($text, $str, $offset=0): int|false
    {
        if($offset >= strlen($text) || $offset < 0)
            return strlen($text);
        return ($r=strpos($text, $str, $offset))===false?strlen($text):$r;
    }

    private static function chkColor(string $color, $sharp = '#'): bool
    {
        if(preg_match('/^'.$sharp.'([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $color))
            return true;
        elseif(in_array($color, self::$cssColors))
            return true;
        else
            return false;
    }
}