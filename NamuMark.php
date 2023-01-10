<?php
/*
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
 * :::::::::::: ORIGINAL CODE: koreapyj, 김동동(1st edited) ::::::::::::
 * :::::::::::::::::::::: 2nd Edited by PRASEOD- ::::::::::::::::::::::
 * 코드 설명 주석 추가: PRASEOD-
 * 설명 주석이 +로 시작하는 경우 PRASEOD-의 2차 수정 과정에서 추가된 코드입니다.
 *
 * ::::::::: 변경 사항 ::::::::::
 * 카카오TV 영상 문법 추가 [나무위키]
 * 문단 문법 미작동 문제 수정
 * 일부 태그 속성 수정
 * 글씨크기 관련 문법 수정
 * {{{#!wiki }}} 문법 오류 수정
 * anchor 문법 추가
 * 테이블 파서 재설계
 * 리스트 파서 재설계
 * 링크 프로세서 재설계
 * 수평선 문법 미작동 및 개행 오류 수정
 * <nowiki>, <pre> 태그 <code>로 대체
 * 취소선 태그 <s>에서 <del>로 변경
 * 본문 영역 문단별 <div> 적용
 * 접힌목차 기능 추가
 * \ 문법 지원
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
    public $prefix, $lastchange, $title, $included, $namespaces, $noredirect = 0, $inThread, $pageCount, $totalPageCount;
    private $WikiPage, $wikitextbox, $imageAsLink, $linenotend, $htr;

    private static $list_tags = [
        '*' => 'wiki-ul',
        '1.' => 'wiki-ol wiki-ol-numeric',
        'A.' => 'wiki-ol wiki-ol-capitalised',
        'a.' => 'wiki-ol wiki-ol-alphabetical',
        'I.' => 'wiki-ol wiki-ol-caproman',
        'i.' => 'wiki-ol wiki-ol-lowroman'
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

    private $macro_processors = [], $toc = [], $fn = [], $fn_overview = [], $links = [], $category = [], $fnset = [];
    private $fn_names = [], $fn_cnt = 0, $ind_level = 0, $lastfncount = 0;


    function __construct() {
        // 문법 데이터 생성
        $this->imageAsLink = false;
        $this->noredirect = 0; // redirect
        $this->prefix = '';
        $this->inThread = false; // 토론창 여부
        $this->linenotend = true; // lineParser 개행구분용
    }

    public function toHtml($wtext) {
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
        return $this->htr->render($token);
    }

    private function htmlScan($text) {
        $result = [];
        $len = strlen($text);
        $now = '';
        $line = '';

        // 리다이렉트 문법
        if(self::startsWith($text, '#') && preg_match('/^#(redirect|넘겨주기) (.+)$/im', $text, $target) && $this->inThread !== false) {
            $rd = 1;
            $html = $this->linkProcessor($text, '[[', $rd);
            array_push($this->links, ['target' => $rd['target'], 'type'=>'redirect']); // backlink
            if(!in_array('not-exist', $rd['class']) && $this->noredirect == 0)
                header('Location: /w/'.$target[1]); // redirect only valid link
            $result = array_merge($result, $html);
        }

        // 문법 처리 순서: 리스트 > 인용문 > 삼중괄호 > 표 >
        for($i=0;$i<$len && $i>=0;self::nextChar($text,$i)) {
            $now = self::getChar($text,$i);
            
            //+ 백슬래시 문법
            if($now == "\\"){
                $line .= $now;
                ++$i;
                $line .= self::getChar($text,$i);
                continue;
            }
            
            // 리스트
            if(!$this->linenotend && $line == '' && $now == ' ' && $inner = $this->listParser($text, $i)) {
                array_push($result, $inner);
                $line = '';
                $now = '';
                continue;
            }

            // 인용문
            if($line == '' && self::startsWith($text, '&gt;', $i) && $inner = $this->bqParser($text, $i)) {
                array_push($result, $inner);
                $line = '';
                $now = '';
                continue;
            }

            // 사실상 삼중괄호전용
            foreach(self::$brackets as $bracket) {
                if(self::startsWith($text, $bracket['open'], $i) && $bracket['multiline'] === true && $inner = $this->bracketParser($text, $i, $bracket)) {
                    $this->linenotend = true;
                    $result = array_merge($result, $this->lineParser($line), $inner);
                    $line = '';
                    $now = '';
                    break;
                }
            }

            // 표
            if($line == '' && self::startsWith($text, '|', $i) && $inner = $this->tableParser($text, $i)) {
                array_push($result, $inner);
                $line = '';
                $now = '';
                continue;
            }

            //+ 빈 줄 삽입 오류 수정
            if($now == "\n" && $line == ''){
                $this->linenotend = false;
                array_push($result, ['type' => 'plaintext', 'text' => '<br>']);
            }elseif($now == "\n") {
                // something in line
                $this->linenotend = false;
                $result = array_merge($result, $this->lineParser($line));
                $line = '';
            }
            else
                $line.= $now; //+ Anti-XSS
        }
        if($line != '')
            $result = array_merge($result, $this->lineParser($line));
        if($this->wikitextbox !== true){
            array_push($this->fnset, $this->fn_names);
            array_push($result, ['type' => 'footnotes', 'from' => $this->lastfncount, 'until' => $this->fn_cnt]);
        }
        // 분류 모음
        // + HTML 구조 약간 변경함
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

    private function bqParser($text, &$offset) {
        return false;
        /*$len = strlen($text);        
        $innerhtml = '';
        for($i=$offset;$i<$len;$i++) {
            if(!self::startsWith($text, '>', $i)) {
                // table end
                break;
            }
            $i+=4;
            $line = $this->formatParser(substr($text, $i, $eol-$i));
            $line = preg_replace('/^(&gt;)+/', '', $line);
            $innerhtml .= '<p>'.$line.'</p>';
        }
        if(empty($innerhtml))
            return false;

        $offset = $i-1;
        return '<blockquote class="_blockquote">'.$innerhtml.'</blockquote>';*/
    }

    //+ Rebuilt by PRASEOD-
    protected function tableParser($text, &$offset) {
        $token = ['type' => 'table', 'caption' => null, 'colstyle' => [], 'rows' => []];
        $tableinit = true;
        $tableAttr = [];
        $tdAttr = [];
        $tableattrinit = [];
        $tdInnerStr = '';
        $unloadedstr = '';
        $len = strlen($text);
        $i = $offset;
        $noheadmark = true;
        $intd = true;
        $rowIndex = 0;
        $colIndex = 0;
        $rowspan = 0;
        $colspan = 0;
        $chpos = function($now, &$i) {
            if(strlen($now) > 1){
                $i += strlen($now) - 1;
            }
        };

        // caption 파싱은 처음에만
        if(self::startsWith($text, '|', $i) && !self::startsWith($text, '||', $i) && $tableinit === true) {
            $caption = explode('|', substr($text,$i));
            if(count($caption) < 3)
                return false;
            $token['caption'] = $this->blockParser($caption[1]);
            $hasCaption = true;
            $tableinit = false;
            //   (|)   (caption content)   (|)
            $i += 1 + strlen($caption[1]) + 1;
        }elseif(self::startsWith($text, '||', $i) && $tableinit === true){
            $i += 2;
            $hasCaption = false;
            $tableinit = false;
        }elseif($tableinit === true)
            return false;

        // text 변수에는 표 앞부분의 ||가 제외된 상태의 문자열이 있음
        for($i; $i<$len; ++$i){
            $now = self::getChar($text,$i);
            
            //+ 백슬래시 문법
            if($now == "\\"){
                $unloadedstr .= $now;
                ++$i;
                $unloadedstr .= self::getChar($text,$i);
                $chpos($now, $i);
                continue;
            }elseif(self::startsWith($text, '||', $i)) {
                if($intd == true && $tdInnerStr == '' && $unloadedstr == ''){
                    if($colspan > 0){
                        ++$colspan;
                    }else{
                        $colspan = 2;
                    }
                    ++$i;
                    continue;
                }elseif($intd === true){
                    //td end and new td start
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
                    continue;
                }elseif($intd === false){
                    // new td start
                    $intd = true;
                    ++$i;
                    continue;
                }
                continue;
            }elseif($tdInnerStr == '' && $unloadedstr == '' && self::startsWith($text, '<', $i) && preg_match('/^((<(tablealign|table align|tablebordercolor|table bordercolor|tablecolor|table color|tablebgcolor|table bgcolor|tablewidth|table width|rowbgcolor|rowcolor|colbgcolor|colcolor|width|height|color|bgcolor)=[^>]+>|<(-[0-9]+|\|[0-9]+|\^[0-9]+|v[0-9]+|\:|\(|\))>)+)/', strtolower(substr($text,$i, self::seekEndOfLine($text,$i) - $i)), $match)){
                $attrs = explode('><', substr($match[1], 1, strlen($match[1])-2));
                foreach ($attrs as $attr){
                    $attr = strtolower($attr);
                    if(preg_match('/^([^=]*)=([^=]*)$/', $attr, $tbattr)){
                        // 속성은 최초 설정치가 적용됨

                        if(
                            !in_array(strtr($tbattr[1], ' ', ''), $tableattrinit) && (
                                (in_array($tbattr[1], ['tablealign', 'table align']) && in_array($tbattr[2], ['center', 'left', 'right'])) ||
                                (in_array($tbattr[1], ['tablewidth', 'table width']) && preg_match('/^-?[0-9.]*(px|%)$/', $tbattr[2])) || 
                                (in_array($tbattr[1], ['tablebgcolor', 'table bgcolor', 'tablecolor', 'table color', 'tablebordercolor', 'table bordercolor']) &&
                                self::chkColor($this, $tbattr[2]))
                            )
                        ){
                            // 표 속성
                            $i += strlen($tbattr[0]) + 2;
                            array_push($tableattrinit, strtr($tbattr[1], ' ', ''));
                            switch(strtr($tbattr[1], ' ', '')){
                                case 'tablebgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                case 'tablecolor':
                                    $tbAttrNm = 'color';
                                    break;
                                case 'tablebordercolor':
                                    $tbAttrNm = 'border-color';
                                    break;
                                case 'tablebgcolor':
                                    $tbAttrNm = 'background-color';
                                    break;
                                case 'tablewidth':
                                    $tbAttrNm = 'width';
                                    break;
                                default:
                                    $tbAttrNm = $tbattr[1];
                            }
                            if(in_array($tbattr[1], ['tablealign', 'table align']))
                                $tbClassStr = ' table-'.$tbattr[2];
                            else
                                $tableAttr[$tbAttrNm] = $tbattr[2];
                        }elseif(
                            // 개별 행 속성
                            in_array($tbattr[1], ['rowbgcolor', 'rowcolor']) && 
                            self::chkColor($this, $tbattr[2])
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
                            self::chkColor($this, $tbattr[2])
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
                            $token['colstyle'][$colIndex][$tbAttrNm] = $tbattr[2];
                        }elseif(
                            // 개별 셀 속성
                            (in_array($tbattr[1], ['width', 'height']) && preg_match('/^-?[0-9.]*(px|%)?$/', $tbattr[2])) ||
                            (in_array($tbattr[1], ['color', 'bgcolor']) && self::chkColor($this, $tbattr[2]))
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
                    }elseif(preg_match('/^(-|\|)([0-9]+)$/', $attr, $tbspan)){
                        $i += strlen($tbspan[0]) + 2;
                        // <|n>
                        if($tbspan[1] == '-')
                            $colspan = $tbspan[2];
                        elseif($tbspan[1] == '|')
                            $rowspan = $tbspan[2];
                    }elseif(preg_match('/^(\^|v|)\|([0-9]+)$/', $attr, $tbalign)){
                        $i += strlen($tbalign[0]) + 2;
                        // <^|n>
                        if($tbalign[1] == '^')
                            $tdAttr['vertical-align'] = 'top';
                        elseif($tbalign[1] == 'v')
                            $tdAttr['vertical-align'] = 'bottom';
                        
                        $colspan = $tbalign[2];
                    }else{
                        // <:>
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
                    }
                }
                --$i;

                // 정체불명의 무한루프 방지
                if(isset($last_temp) && $last_temp === $i)
                    ++$i;
                $last_temp = $i;
                continue;
            }else{
                if(self::startsWith($text, "\n||", $i) && ($unloadedstr == '' && $tdInnerStr == '')) {
                    // 행갈이
                    ++$rowIndex;
                    $colIndex = 0;
                    $noheadmark = true;
                    $intd = false;
                }elseif(self::startsWith($text, "\n", $i) && self::getChar($text,$i+1) !== '|' && ($unloadedstr == '' && $tdInnerStr == '')) {
                    // end of table
                    ++$i;
                    break;
                }elseif(self::startsWith($text, "\n", $i)) {
                    // just breaking line
                    $unloadedstr .= $now;
                    $noheadmark = false;
                }else{
                    // other string
                    $unloadedstr.=$now;
                    $noheadmark = true;
                }
            }
            $chpos($now, $i);
        }
        
        $offset = $i-1;
        unset($text);
        return $token;
    }

    //+ Rebuilt by PRASEOD-
    private function listParser($text, &$offset) {
        $len = strlen($text);
        $list = [];
        $linestart = true;
        $eol = false;
        $listdata = ['type' => 'list', 'lists' => []];
        $html = '';
        ++$offset;
        ++$this->ind_level;
        
        for($i=$offset; $i<$len; self::nextChar($text,$i)){
            $now = self::getChar($text, $i);

            if($eol && !self::startsWith($text,  str_repeat(' ', $this->ind_level), $i)){
                // 개행 + 들여쓰기 공백 적음
                $list['html'] = $this->blockParser($html);
                $html = '';
                array_push($listdata['lists'], $list);
                $list = [];
                break; // 리스트 끝
            }elseif($eol){
                // 현재 들여쓰기 칸만큼 스킵
                $html .= "\n";
                $i += $this->ind_level - 1;
                $eol = false;
                continue;
            }
            
            if($linestart && preg_match('/^((\*|1\.|a\.|A\.|I\.|i\.)(#[^ ]*)? ?)(.*)/', substr($text,$i), $match)){
                // 리스트
                if(!empty($list)){
                    $list['html'] = $this->blockParser($html);
                    $html = '';
                    array_push($listdata['lists'], $list);
                    $list = [];
                }
                $listdata['listtype'] = self::$list_tags[$match[2]];
                $listdata['start'] = substr($match[3], 1);
                
                if(!is_numeric($listdata['start']) || $listdata['start'] < 0) // #숫자 여부 + 음수 아님
                    $listdata['start'] = 1;

                $i += strlen($match[1]) - 1;
                continue;
            }elseif($i === $offset){
                // 들여쓰기
                $listdata['listtype'] = 'indent';
            }

            if($linestart){
                $linestart = false;
            }
            
            if($now == "\n"){
                // 공백 처리
                $eol = true;
                $linestart = true;
                continue;
            }

            $html .= $now;

            if($i === $len - 1){
                $list['html'] = $this->blockParser($html);
                array_push($listdata['lists'], $list);
                $list = [];
            }
        }

        $offset = $i - 1;
        --$this->ind_level;
        unset($text);
        if(empty($listdata['lists']))
            return false;

        return $listdata;
    }

    private function lineParser($line) {
        $result = [];
        $token = [];

        //+ 공백 있어서 안 되는 오류 수정
        if(self::startsWith($line, '##')) {
            // 주석
            $line = '';
        }elseif(self::startsWith($line, '=') && preg_match('/^(=+) (.*?) (=+) *$/', trim($line), $match) && $match[1]===$match[3]) {
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
            

            /*
            if(preg_match('/\[anchor\((.*)\)\]/', $innertext, $anchor)){
                $RealContent = str_replace($anchor[0], '', $innertext);
                $result .= '<a id="'.$anchor[1].'"></a><span id="'.trim($RealContent).'">'.$RealContent;
            }else{
                $result .= '<span id="'.strip_tags($innertext).'">'.$innertext;
            }*/

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

    private function blockParser($block) {
        /*if(!$this->blockInstance){
            var_dump('1');
            $this->blockInstance = new NamuMark();
            $this->blockInstance->noredirect = 1;
            $this->blockInstance->prefix = $this->prefix;
            $this->blockInstance->title = $this->title;
            $this->blockInstance->wikitextbox = true;
        }
        $this->blockInstance->fn = $this->fn;
        $this->blockInstance->fn_cnt = $this->fn_cnt;
        $this->blockInstance->fn_overview = $this->fn_overview;
        $content = $this->blockInstance->toHtml($block);
        $this->fn += $this->blockInstance->fn;
        $this->fn_cnt = $this->blockInstance->fn_cnt;
        $this->fn_overview = $this->blockInstance->fn_overview;*/

        //var_dump($block);
        $this->wikitextbox = true;
        $content = $this->toHtml($block);
        $this->wikitextbox = false;

        unset($block);
        return $content;
    }

    private function bracketParser($text, &$now, $bracket) {
        $len = strlen($text);
        $cnt = 0;
        $done = false;
        $unloadedstr = '';
        $loadedstr = [];
        $openlen = strlen($bracket['open']);
        $closelen = strlen($bracket['close']);
        $isEscape = (!$bracket['multiline'] && $bracket['open'] == '{{{');
        $isRender = ($bracket['multiline'] && $bracket['open'] == '{{{');
        $opened = [];

            
        /*
         * Logic
         * 
         * $text = 전체 텍스트
         * $now = 현재 오프셋
         * $bracket = 괄호 오브젝트
         * 
         * 오프셋에 여는괄호 길이 더하기
         * 한글자씩 스캔 {
         *     백슬래시면 스킵
         *     닫는괄호면 파서 종료
         *     여는괄호(리터럴X)면 또다시 BracketParser 
         * }
         * 
         * {{{#!wiki style="word-break:keep-all" > cnt 0
         * asfdsdfasf
         * 
         * || {{{ > cnt 1 '''asdf'''}}} > cnt 0 ||
         * || ~~ > cnt 1 asdf ~~ > cnt 0 ||
         * }}}
         * 
         * 개선 방향
         * (괄호) 안 선처리
         * 밖에서 안으로 처리해야 함
         * 
         * 괄호가 불완전한 경우 밖에서 안으로 처리
         * 
         * 1. 여는 괄호를 읽는다.
         * 2. 열고닫는 괄호를 모두 읽는다.
         * 3. 마지막 닫는 괄호를 읽는다.
         * 4. 괄호 안을 처리한다.
         */

        if(!isset($bracket['strict']))
            $bracket['strict'] = true;

        // 한글자씩 스캔
        for($i=$now+$openlen;$i<$len;self::nextChar($text,$i)) {
            $char = self::getChar($text, $i);
            //+ 백슬래시 문법 지원
            if($char == "\\" && !$isEscape) {
                // {{{ }}} 구문이 아닌 경우 \ 처리
                ++$i;
                $unloadedstr .= self::getChar($text, $i);
                $char = '';
                continue;
            }elseif(self::startsWith($text, $bracket['open'], $i) && $bracket['open'] !== $bracket['close']) {
                // 중첩구간처리
                $cnt++;
                $i += strlen($bracket['open']) - 1;
                $unloadedstr .= $bracket['open'];
                continue;
            }elseif(self::startsWith($text, $bracket['close'], $i) && $cnt > 0){
                $cnt--;
                $i += strlen($bracket['close']) - 1;
                $unloadedstr .= $bracket['close'];
                continue;
            }elseif(self::startsWith($text, $bracket['close'], $i) && $cnt === 0) {
                // 닫는괄호
                if($bracket['strict'] && $bracket['multiline'] && strpos($unloadedstr, "\n")===false)
                    return false;
                
                $loadedstr = array_merge($loadedstr, call_user_func_array([$this, $bracket['processor']],array($unloadedstr, $bracket['open'])));
                $now = $i+$closelen-1;
                return $loadedstr;
            }elseif(!$bracket['multiline'] && (self::startsWith($text, "\n", $i) || self::startsWith($text, "\r\n", $i)))
                return false; // 개행금지 문법에서 개행 발견
            
            $unloadedstr .= $char;
        }
        return false;
    }

    //+ 역슬래시 지원
    private function formatParser($line) {
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
                    if(self::startsWith($line, $bracket['open'], $j) && $bracket['multiline'] === false && $inner = $this->bracketParser($line, $nj, $bracket)) {
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
            /*if(self::startsWith($line, 'http', $j) && preg_match('/(https?:\/\/[^ ]+\.(jpg|jpeg|png|gif))(?:\?([^ ]+))?/i', $line, $match, 0, $j)) {
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
            }elseif(self::startsWith($line, 'attachment', $j) && preg_match('/attachment:([^\/]*\/)?([^ ]+\.(?:jpg|jpeg|png|gif|svg))(?:\?([^ ]+))?/i', $line, $match, 0, $j) && $this->inThread !== false) {
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

    protected function renderProcessor($text, $type) {
        //$text = '';
        /*foreach($lines as $key => $line) {
            if( (!$key && !$lines[$key]) || ($key == count($lines) - 1 && !$lines[$key]) )
                continue;
            if (preg_match('/^(:+)/', $line, $match)) {
                $line = substr($line, strlen($match[1]));
                $add = '';
                for ($i = 1; $i <= strlen($match[1]); $i++)
                    $add .= ' ';
                $line = $add . $line;
                $text .= $line . "\n";
            } else {
                $text .= $line . "\n";
            }
        }*/
        
        // + 대소문자 구분 반영
        if(self::startsWith($text, '#!html') && $this->inThread !== false) {
            // HTML
            return [['type' => 'inline-html', 'text' => preg_replace('/UNIQ--.*?--QINU/', '', substr($text, 7))]];
        } elseif(preg_match('/^#!wiki(.*)\n/', $text, $match)) {
            // + {{{#!wiki }}} 문법
            $divattr = '';
            if(preg_match('/style=\".*\"$/', $match[1], $_dattr))
                $divattr = $_dattr[0];
             
            $text = str_replace($match[0], '', $text);

            return [['type' => 'wikitext', 'attr' => $divattr, 'text' => $this->blockParser($text)]];
        } elseif(self::startsWithi($text, '#!syntax') && preg_match('/#!syntax ([^\s]*)/', $text, $match)) {
            // 구문
            return [['type' => 'syntax', 'lang' => $match[1], 'text' => preg_replace('/#!syntax ([^\s]*)/', '', $text)]];
        } elseif(preg_match('/^\+([1-5])(.*)$/sm', $text, $size)) {
            // {{{+큰글씨}}}
            return [['type' => 'wiki-size', 'size' => 'up-'.$size[1], 'text' => $this->blockParser($size[2])]];
        } elseif(preg_match('/^\-([1-5])(.*)$/sm', $text, $size)) {
            // {{{-작은글씨}}}
            return [['type' => 'wiki-size', 'size' => 'down-'.$size[1], 'text' => $this->blockParser($size[2])]];
        } elseif(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+)) (.*)$/', $text, $color) && (self::chkColor($this, $color[1], '') || self::chkColor($this, $color[2], ''))) {
            if(empty($color[1]) && empty($color[2]))
                return [['type' => 'plaintext', 'text' => $text]];
            return [['type' => 'colortext', 'color' => (empty($color[1])?$color[2]:'#'.$color[1]), 'text' => $this->formatParser($color[3])]];
        } else {
            return [['type' => 'rawtext', 'text' => $text]];
            // 문법 이스케이프
        }
    }

    //+ rebuilt by PRASEOD-
    private function linkProcessor($text, $type, &$rd=0) {
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

        // 우선처리대상
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

                    if(($opt[0] == 'height' || $opt[0] == 'width') && !preg_match('/^[0-9]/', $opt[1]) || ($opt[0] == 'align' && !in_array($opt[1], ['left', 'center', 'right','middle','bottom','top'])) || !self::chkColor($this, $opt[1])){
                        // invalid format
                        continue;
                    }elseif(($opt[0] == 'height' || $opt[0] == 'width') && self::endsWith($opt[1], '%'))
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
            // external link
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
                $display = $target;
            }
            
            if(strpos($target, '#') === 0)
                $target = $this->title;
            

            if($target == $this->title)
                array_push($classList, 'wiki-self-link');
            elseif($rd === 0){
                array_push($this->links, ['target'=>$target, 'type'=>'link']);
                array_push($classList, 'wiki-link-internal');
            }
            $href = '/w/'.$target.$exception;
        }

        //if(in_array('wiki-link-internal', $classList) && !$this->WikiPage->pageExists($target))
        //    array_push($classList, 'not-exist');
        
        if($rd !== 0) $rd = ['target' => $target.$exception, 'class' => $classList];
        
        return [['type' => 'link', 'class' => $classList, 'href' => $href.$sharp, 'text' => $display]];
    }

    // 대괄호 문법
    private function macroProcessor($text, $type) {
        $macroName = strtolower($text);
        if(!empty($this->macro_processors[$macroName]))
            return $this->macro_processors[$macroName]();
        switch($macroName) {
            case 'br':
                return [['type' => 'plaintext', 'text' => '<br>']];
            case 'date':
            case 'datetime':
                return [['type' => 'plaintext', 'text' => date('Y-m-d H:i:s')]];
            case '목차':
            case 'tableofcontents':
                // 목차
                return [['type' => 'toc']];
            case '각주':
            case 'footnote':
                // 각주모음
                $this->lastfncount = $this->fn_cnt;
                array_push($this->fnset, $this->fn_names);
                $this->fn_names = [];
                return [['type' => 'footnotes', 'from' => $this->lastfncount, 'until' => $this->fn_cnt]];
            //+ clearfix
            case 'clearfix':
                return [['type' => 'plaintext', 'text' => '<div style="clear:both;"></div>']];
            default:
                if(preg_match('/^include\((.+)\)$/i', $text, $include) && $include = $include[1] && $this->inThread !== false) {
                    // include 문법
                    if($this->included)
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
                        $child->prefix = $this->prefix;
                        $child->imageAsLink = $this->imageAsLink;
                        $child->included = true;
                        return $child->toHtml();
                    }*/
                    return [['type' => 'wikitext', 'attr' => '', 'text' => '']];
                }
                elseif(preg_match('/^(youtube|nicovideo|kakaotv|vimeo|navertv)\((.+)\)$/i', $text, $include) && $include = $include[2] && $this->inThread !== false) {
                    // 동영상
                    $include = explode(',', $include);
                    $var = array();
                    foreach($include as $v) {
                        $v = explode('=', $v);
                        if(empty($v[1]))
                            $v[1]='';
                        $var[$v[0]] = $v[1];
                    }
                    return [['type' => 'video', 'width' => (!empty($var['width'])?$var['width']:'640'), 'height' => (!empty($var['height'])?$var['height']:'360'), 'src' => $this->videoURL[$include[1]].$include[0]]];
                    
                }
                elseif(preg_match('/^age\(([0-9]{4})-(0[0-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\)$/i', $text, $include) && $this->inThread !== false) {
                    // 연령
                    $age = (date("md", date("U", mktime(0, 0, 0, $include[3], $include[2], $include[1]))) > date("md")
                        ? ((date("Y") - $include[1]) - 1)
                        : (date("Y") - $include[1]));
                    return [['type' => 'plaintext', 'text' => $age]];
                }
                elseif(preg_match('/^anchor\((.+)\)$/i', $text, $include) && $include = $include[1]) {
                    // 앵커
                    return [['type' => 'anchor', 'text' => $include]];
                }
                elseif(preg_match('/^dday\((.+)\)$/i', $text, $include) && $include = $include[1] && $this->inThread !== false) {
                    // D-DAY
                    $nDate = date("Y-m-d", time());
                    if(strtotime($nDate)==strtotime($include))
                        $return = " 0";
                    else
                        $return = (strtotime($nDate)-strtotime($include)) / 86400;
                    return [['type' => 'plaintext', 'text' => $return]];
                }
                elseif(preg_match('/^ruby\((.+)\)$/i', $text, $include) && $this->inThread !== false) {
                    $ruby = explode(',', $include[1]);
                    foreach(array_slice($ruby, 1) as $a){
                        $split = explode('=', $a);
                        if($split[0] == 'ruby'){
                            $rb = $split[1];
                        }elseif($split[0] == 'color' && self::chkColor($this,$split[1])){
                            $color = $split[1];
                        }
                    }
                    
                    if(strlen($rb) > 0 && strlen($ruby[0]) > 0)
                        return [['type' => 'ruby', 'ruby' => $rb, 'text' => $ruby[0], 'color' => $color]];
                    else
                        return [['type' => 'void']];
                }
                elseif(preg_match('/^pagecount\((.*)\)$/i', $text, $include) && $include = $include[1] && $this->inThread !== false) {
                    if(in_array($include, $this->namespaces))
                        return [['type' => 'plaintext', 'text' => $this->pageCount[$include]]];
                    else
                        return [['type' => 'plaintext', 'text' => $this->totalPageCount]];
                }
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
                    
                    if($this->lastfncount === 0)
                        $this->lastfncount = 1;

                    return [['type' => 'footnote', 'name' => htmlspecialchars($name), 'id' => $this->fn_cnt]];
                }
        }
        return [['type' => 'plaintext', 'text' => '['.htmlspecialchars($text).']']];
    }

    
    private function textProcessor($otext, $type) {
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
                // 문법 이스케이프
                return [['type' => 'escape', 'text' => htmlspecialchars($text)]];
            }
            return [['type' => 'plaintext', 'text' => $type.$text.$type]];
    }

    // 목차 삽입
    private function tocInsert(&$arr, $text, $level, $path = '') {
        if(empty($arr[0])) {
            $arr[0] = array('name' => $text, 'level' => $level, 'childNodes' => array());
            return $path.'1';
        }
        $last = self::count($arr)-1;
        $readableId = $last+1;
        if($arr[0]['level'] >= $level) {
            $arr[] = array('name' => $text, 'level' => $level, 'childNodes' => array());
            return $path.($readableId+1);
        }

        return $this->tocInsert($arr[$last]['childNodes'], $text, $level, $path.$readableId.'.');
    }

    private static function getChar($string, $pointer){
        if(!isset($string[$pointer])) return false;
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

    private static function nextChar($string, &$pointer){
        if(!isset($string[$pointer])) return false;
        $char = ord($string[$pointer]);
        if($char < 128){
            return $string[$pointer++];
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
            $pointer += $bytes;
            return $str;
        }
    }

    private static function count($var){
        if(!is_array($var) && !is_countable($var))
            return false;
        else
            return count($var);
    }

    private static function startsWith($haystack, $needle, $offset = 0) {
        $len = strlen($needle);
        if(($offset+$len)>strlen($haystack))
            return false;
        return $needle == substr($haystack, $offset, $len);
    }

    private static function startsWithi($haystack, $needle, $offset = 0) {
        $len = strlen($needle);
        if(($offset+$len)>strlen($haystack))
            return false;
        return strtolower($needle) == strtolower(substr($haystack, $offset, $len));
    }

    protected static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    private static function seekEndOfLine($text, $offset=0) {
        return self::seekStr($text, "\n", $offset);
    }

    private static function seekStr($text, $str, $offset=0) {
        if($offset >= strlen($text) || $offset < 0)
            return strlen($text);
        return ($r=strpos($text, $str, $offset))===false?strlen($text):$r;
    }

    private static function chkColor($context, string $color, $sharp = '#') {
        if(preg_match('/^'.$sharp.'([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $color))
            return true;
        elseif(in_array($color, $context::$cssColors))
            return true;
        else
            return false;
    }
}
