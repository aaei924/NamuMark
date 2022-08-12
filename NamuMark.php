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
 * 링크 프로세서 재설계
 * 수평선 문법 미작동 및 개행 오류 수정
 * <nowiki>, <pre> 태그 <code>로 대체
 * 취소선 태그 <s>에서 <del>로 변경
 * 본문 영역 문단별 <div> 적용
 * 접힌목차 기능 추가
 * \ 문법 지원
 */
if(!class_exists('WikiPage')){
class WikiPage {
    // 평문 데이터 호출
    public $title, $text, $lastchanged;
    function __construct($text) {
        $this->title = '(inline wikitext)';
        $this->text = $text;
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
    public $prefix, $lastchange;

    function __construct($wtext) {
        // 문법 데이터 생성
        $this->list_tag = [
            ['*', 'ul data-pressdo-ul'],
            ['1.', 'ol data-pressdo-ol data-pressdo-ol-numeric'],
            ['A.', 'ol data-pressdo-ol data-pressdo-ol-capitalised'],
            ['a.', 'ol data-pressdo-ol data-pressdo-ol-alphabetical'],
            ['I.', 'ol data-pressdo-ol data-pressdo-ol-caproman'],
            ['i.', 'ol data-pressdo-ol data-pressdo-ol-lowroman']
        ];

        $this->h_tag = [
            ['/^======#? (.*) #?======/', 6],
            ['/^=====#? (.*) #?=====/', 5],
            ['/^====#? (.*) #?====/', 4],
            ['/^===#? (.*) #?===/', 3],
            ['/^==#? (.*) #?==/', 2],
            ['/^=#? (.*) #?=/', 1]
        ];

        $this->brackets = [
            [
                'open'    => '{{{',
                'close' => '}}}',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '{{{',
                'close' => '}}}',
                'multiline' => true,
                'processor' => [$this,'renderProcessor']
            ],
            [
                'open'    => '[[',
                'close' => ']]',
                'multiline' => false,
                'processor' => [$this,'linkProcessor']
            ],
            [
                'open'    => '[',
                'close' => ']',
                'multiline' => false,
                'processor' => [$this,'macroProcessor']
            ],

            [
                'open'    => '\'\'\'',
                'close' => '\'\'\'',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '\'\'',
                'close' => '\'\'',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '**',
                'close' => '**',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '~~',
                'close' => '~~',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '--',
                'close' => '--',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '__',
                'close' => '__',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => '^^',
                'close' => '^^',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ],
            [
                'open'    => ',,',
                'close' => ',,',
                'multiline' => false,
                'processor' => [$this,'textProcessor']
            ]
        ];
        
        

        $this->cssColors = [
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
        
        $this->videoURL = [
            'youtube' => '//www.youtube.com/embed/',
            'kakaotv' => '//tv.kakao.com/embed/player/cliplink/',
            'nicovideo' => '//embed.nicovideo.jp/watch/',
            'navertv' => '//tv.naver.com/embed/',
            'vimeo' => '//player.vimeo.com/video/'
        ];

        $this->macro_processors = [];

        $this->WikiPage = $wtext;
        $this->imageAsLink = false;
        $this->noredirect = 0; // redirect
        $this->toc = array(); //목차
        $this->fn = []; //각주
        $this->fn_overview = [];
        $this->category = array(); //분류
        $this->links = []; //링크
        $this->lastfn = 0;
        $this->fn_cnt = 0;
        $this->prefix = '';
        $this->inThread = false; // 토론창 여부
    }

    public function toHtml() {
        // 문법을 HTML로 변환하는 함수
        if(empty($this->WikiPage->title))
            return '';
        $this->whtml = $this->WikiPage->text;
        $this->whtml = $this->htmlScan($this->whtml);
        return '<div id="w">'.$this->whtml.'</div>';
    }

    private function htmlScan($text) {
        $result = '';
        $len = strlen($text);
        $now = '';
        $line = '';

        // 리다이렉트 문법
        if(self::startsWith($text, '#') && preg_match('/^#(redirect|넘겨주기) (.+)$/im', $text, $target) && $this->inThread !== false) {
            $rd = 1;
            $html = $this->linkProcessor($text, '[[', $rd);
            array_push($this->links, ['target' => $rd['target'], 'type'=>'redirect']);
            if(!in_array('not-exist', $rd['class']) && $this->noredirect == 0)
                header('Location: '.$this->uriset['wiki'].'/'.self::encodeURI($target[1]));
            $result .= $html;
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
            
            if($line == '' && $now == ' ' && $list = $this->listParser($text, $i)) {
                $result .= $list;
                $line = '';
                $now = '';
                continue;
            }

            // 인용문
            if($line == '' && self::startsWith($text, '&gt;', $i) && $blockquote = $this->bqParser($text, $i)) {
                $result .= $blockquote;
                $line = '';
                $now = '';
                continue;
            }

            foreach($this->brackets as $bracket) {
                if(self::startsWith($text, $bracket['open'], $i) && $bracket['multiline'] === true && $innerstr = $this->bracketParser($text, $i, $bracket)) {
                    $result .= $this->lineParser($line).$innerstr;
                    $line = '';
                    $now = '';
                    break;
                }
            }

            // 표
            if($line == '' && self::startsWith($text, '|', $i) && $table = $this->tableParser($text, $i)) {
                $result .= $table;
                $line = '';
                $now = '';
                continue;
            }

            //+ 빈 줄 삽입 오류 수정
            if($now == "\n" && $line == ''){
                $result .= '<br>';
            }elseif($now == "\n") { // line parse
                $result .= $this->lineParser($line);
                $line = '';
            }
            else
                $line.= $now; //+ Anti-XSS
        }
        if($line != '')
            $result .= $this->lineParser($line);
        if($this->wikitextbox !== true)
            $result .= $this->printFootnote();

        // 분류 모음
        // + HTML 구조 약간 변경함
        if(!empty($this->category) && $this->inThread !== false) {
            $result .= '<div id="categories" class="wiki-categories">
                            <h2>분류</h2>
                            <ul>';
            foreach($this->category as $category) {
                $result .= '<li class="wiki-categories">'.$this->linkProcessor(':분류:'.$category.'|'.$category, '[[').'</li>';
            }
            $result .= '</ul></div>';
        }
        return $result;
    }

    private function bqParser($text, &$offset) {
        /*$len = strlen($text);        
        $innerhtml = '';
        for($i=$offset;$i<$len;$i=self::seekEndOfLine($text, $i)+1) {
            $eol = self::seekEndOfLine($text, $i);
            if(!self::startsWith($text, '&gt;', $i)) {
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
        /*$temp = [];
        $_wlen = iconv_strlen($text);
        $init = true;
        for($i=$offset;$i<$_wlen;$i=self::seekEndOfLine($text, $i)+1){
            // 매 루프마다 i값을 다음 줄의 첫 글자로 넘김
            $eol = self::seekEndOfLine($text, $i);
            
            // 첫 글자가 >가 아닐 경우 (인용문 끝)
            if(!str_starts_with(iconv_substr($wikitext, $i), '>'))
                break;
            preg_match('/^>+/', iconv_substr($wikitext,$i), $bq_match);
            $level = iconv_strlen($bq_match[0]); // >의개수
            
            // i + level: >다음의 첫글자
            $line = iconv_substr($wikitext, $i+$level, $eol - $level - $i + 1);
            array_push($temp, array('level' => $level, 'line' => $line));
        }
        if(count($temp) == 0)
            return null;
        $curLevel = 1;
        $result = '<blockquote class="wiki-quote">';
        foreach($temp as $curTemp){
            // 다중 인용문
            if($curTemp['level'] > $curLevel){
                $_clvcalc = $curTemp['level'] - $curLevel;
                for($i=0; $i<$_clvcalc; $i++)
                    $result .= '<blockquote class="wiki-quote">';
            } elseif($curTemp['level'] < $curLevel){
                $_clvcalc = $curLevel - $curTemp['level'];
                for($i=0; $i<$_clvcalc; $i++)
                    $result .= '</blockquote>';
            } else
                $result .= '<br>';
            
            array_push($result, array('name' => 'wikitext', 'parseFormat' => true, 'text' => $curTemp['line']));
        }
        array_push($result, array('name' => 'blockquote-end'));
        $setpos($i-1);*/
        return $result;
    }

    protected static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    protected function tableParser($text, &$offset) {
        $token = ['caption' => null, 'colstyle' => [], 'rows' => []];
        $tableinit = true;
        $tableAttr = [];
        $tdAttr = [];
        $tableattrinit = [];
        $tableAttrStr = '';
        $trAttrStr = '';
        $tableInnerStr = '';
        $trInnerStr = '';
        $tdInnerStr = '';
        $tdAttrStr = '';
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
        /*
        * DOM 구조
        {
            table:[
                'caption': 'caption',
                'colstyles' => []
                'rows' => [
                    [
                        'style' => ['style' => 'style'],
                        'cols' => [
                            ['text' => blockParser, 'style' => ['style' => 'style'], 'rowspan' => 1],
                            'span',
                            [...]
                        ]
                    ],
        */
        for($i; $i<$len; ++$i){
            $now = self::getChar($text,$i);
            
            //+ 백슬래시 문법
            if($now == "\\"){
                $unloadedstr .= $now;
                ++$i;
                $unloadedstr .= self::getChar($text,$i);
                $chpos($now, $i);
                continue;
            }/*elseif($noheadmark === false && $tdInnerStr == '' && $now == ' ' && $list = $this->listParser($text, $i)) {
                $tdInnerStr .= $list;
                continue;
            }*/elseif(self::startsWith($text, '||', $i)) {
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
            }elseif($noheadmark === false && $tdInnerStr == '' && $unloadedstr == '' && self::startsWith($text, '>', $i) && $blockquote = $this->bqParser($text, $i)) {
                $tdInnerStr .= $this->blockParser($unloadedstr).$blockquote;
                continue;
            }elseif($tdInnerStr == '' && $unloadedstr == '' && self::startsWith($text, '<', $i) && preg_match('/^((<(tablealign|table align|tablebordercolor|table bordercolor|tablecolor|table color|tablebgcolor|table bgcolor|tablewidth|table width|rowbgcolor|rowcolor|colbgcolor|colcolor|width|height|color|bgcolor)=[^>]+>|<(-[0-9]+|\|[0-9]+|\^[0-9]+|v[0-9]+|:|\(|\))>)+)/', strtolower(substr($text,$i, self::seekEndOfLine($text,$i) - $i)), $match)){
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
                if($last_temp === $i)
                    ++$i;
                $last_temp = $i;
                continue;
            }else{
                // bracket
                foreach($this->brackets as $bracket) {
                    if(self::startsWith($text, $bracket['open'], $i) && $innerstr = $this->bracketParser($text, $i, $bracket)) {
                        $tdInnerStr .= $this->blockParser($unloadedstr).$innerstr;
                        $unloadedstr = '';
                        $now = '';
                        continue;
                    }
                }

                //+ \r과 \r\n 모두에서 작동할 수 있도록 함.
                if((self::startsWith($text, "\r\n||", $i) || self::startsWith($text, "\n||", $i)) && $tdInnerStr == '') {
                    ++$rowIndex;
                    $colIndex = 0;
                    $noheadmark = true;
                    $intd = false;
                }elseif((self::startsWith($text, "\r\n", $i) || self::startsWith($text, "\n", $i)) && self::getChar($text,$i+1) !== '|' && $tdInnerStr == '') {
                    ++$i;
                    break;
                }elseif(self::startsWith($text, "\r\n", $i) || self::startsWith($text, "\n", $i)) {
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

        // token to HTML
        foreach ($token['rows'] as $r){
            if(!is_array($r))
                return false;
            foreach ($r['cols'] as $rc){
                if($rc == 'span')
                continue;
                if(!isset($rc['style']['text-align'])){
                    $start = (substr($rc['text'], 0, 1) === ' ');
                    $end = (substr($rc['text'], -1, 1) === ' ');
                    if($start && $end)
                        $rc['style']['text-align'] = 'center';
                    elseif($start && !$end)
                        $rc['style']['text-align'] = 'right';
                    elseif(!$start && $end)
                        $rc['style']['text-align'] = 'left';
                }
                if(!empty($rc['style'])){
                    $rcCount = count($rc['style']);
                    $rcKeys = array_keys($rc['style']);
                    for($k=0; $k<$rcCount; ++$k){
                        if($k !== 0)
                        $tdAttrStr .= ' ';
                        $tdAttrStr .= $rcKeys[$k].':'.$rc['style'][$rcKeys[$k]].';';
                    }
                }
                    if(strlen($tdAttrStr) > 0)
                        $tdAttrStr = ' style="'.$tdAttrStr.'"';
                    if(isset($rc['rowspan']))
                        $tdAttrStr .= ' rowspan="'.$rc['rowspan'].'"';
                    if(isset($rc['colspan']))
                        $tdAttrStr .= ' colspan="'.$rc['colspan'].'"';
                    $trInnerStr .= '<td'.$tdAttrStr.'>'.$rc['text'].'</td>';
                    $tdAttrStr = '';
            }
            if(!empty($r['style'])){
                $attrlen = count($r['style']);
                $attkeys = array_keys($r['style']);
                for($k=0; $k<$attrlen; ++$k){
                    $trAttrStr .= $attkeys[$k].':'.$r['style'][$attkeys[$k]].'; ';
                }
            }
            if(strlen($trAttrStr) > 0)
                $trAttrStr = ' style="'.$trAttrStr.'"';
            
            $tableInnerStr .= '<tr'.$trAttrStr.'>'.$trInnerStr.'</tr>';
            $trInnerStr = $trAttrStr = '';
        }
        
        $attrlen = count($tableAttr);
        $attkeys = array_keys($tableAttr);
        for($k=0; $k<$attrlen; ++$k){
            $tableAttrStr .= $attkeys[$k].':'.$tableAttr[$attkeys[$k]].'; ';
        }
        if(strlen($tableAttrStr) > 0)
            $tableAttrStr = ' style="'.$tableAttrStr.'"';
        if(!isset($tbClassStr))
            $tbClassStr = '';
        
        $offset = $i-1;
        return '<div class="wiki-table-wrap'.$tbClassStr.'"><table class="wiki-table" '.$tableAttrStr.'>'.$tableInnerStr.'</table></div>';
    }

    // 리스트 생성
    private function listParser($text, &$offset) {
        $listTable = array();
        $len = strlen($text);
        $lineStart = $offset;

        $quit = false;
        for($i=$offset;$i<$len;$before=self::nextChar($text,$i)) {
            $now = self::getChar($text,$i);
            if($now == "\n" && empty($listTable[0])) {
                    return false;
            }
            if($now != ' ') {
                if($lineStart == $i) {
                    // list end
                    break;
                }

                $match = false;

                foreach($this->list_tag as $list_tag) {
                    if(self::startsWith($text, $list_tag[0], $i)) {

                        if(!empty($listTable[0]) && $listTable[0]['tag']=='indent') {
                            $i = $lineStart;
                            $quit = true;
                            break;
                        }

                        $eol = self::seekEndOfLine($text, $lineStart);
                        $tlen = strlen($list_tag[0]);
                        $innerstr = substr($text, $i+$tlen, $eol-($i+$tlen));
                        $this->listInsert($listTable, $innerstr, ($i-$lineStart), $list_tag[1]);
                        $i = $eol;
                        $now = "\n";
                        $match = true;
                        break;
                    }
                }
                if($quit)
                    break;

                if(!$match) {
                    // indent
                    if(!empty($listTable[0]) && $listTable[0]['tag']!='indent') {
                        $i = $lineStart;
                        break;
                    }

                    $eol = self::seekEndOfLine($text, $lineStart);
                    $innerstr = substr($text, $i, $eol-$i);
                    $this->listInsert($listTable, $innerstr, ($i-$lineStart), 'indent');
                    $i = $eol;
                    $now = "\n";
                }
            }
            if($now == "\n") {
                $lineStart = $i+1;
            }
        }
        if(!empty($listTable[0])) {
            $offset = $i-1;
            return $this->listDraw($listTable);
        }
        return false;
    }

    // 리스트에 추가
    private function listInsert(&$arr, $text, $level, $tag) {
        if(preg_match('/^#([1-9][0-9]*) /', $text, $start))
            $start = $start[1];
        else
            $start = 1;
        if(empty($arr[0])) {
            $arr[0] = array('text' => $text, 'start' => $start, 'level' => $level, 'tag' => $tag, 'childNodes' => array());
            return true;
        }

        $last = self::count($arr)-1;
        $readableId = $last+1;
        if($arr[0]['level'] >= $level) {
            $arr[] = array('text' => $text, 'start' => $start, 'level' => $level, 'tag' => $tag, 'childNodes' => array());
            return true;
        }

        return $this->listInsert($arr[$last]['childNodes'], $text, $level, $tag);
    }

    // 리스트 생성
    private function listDraw($arr) {
        if(empty($arr[0]))
            return '';

        $tag = $arr[0]['tag'];
        $start = $arr[0]['start'];
        $result = '<'.($tag=='indent'?'div class="indent"':$tag.($start!=1?' start="'.$start.'"':'')).'>';
        foreach($arr as $li) {
            $text = $this->blockParser($li['text']).$this->listDraw($li['childNodes']);
            $result .= $tag=='indent'?$text:'<li>'.$text.'</li>';
        }
        $result .= '</'.($tag=='indent'?'div':$tag).'>';
        return $result;
    }

    private function lineParser($line) {
        $result = '';
        $line_len = strlen($line);

        // 주석
        // == Title == (문단)
        //+ 공백 있어서 안 되는 오류 수정
        if(self::startsWith($line, '##')) {
            $line = '';
        }elseif(self::startsWith($line, '=') && preg_match('/^(=+) (.*?) (=+) *$/', trim($line), $match) && $match[1]===$match[3]) {
            $level = strlen($match[1]);
            $innertext = $this->blockParser($match[2]);

            //+ 접힌문단 기능 추가
                if (preg_match('/^# (.*) #$/', $innertext, $ftoc)) {
                        $folded = 'fold="true"';
                        $innertext = $ftoc[1];
                }else{
                    $folded = 'fold="false"';
                }
            $id = $this->tocInsert($this->toc, $innertext, $level);
            $result .= '</div><h'.$level.' class="wiki-heading" '.$folded.' id="s-'.$id.'">
                        <a name="s-'.$id.'" href="#_toc">'.$id.'.</a>';

            //+ 문단에서 앵커가 태그속에 들어가는 부분 수정
            if(preg_match('/\[anchor\((.*)\)\]/', $innertext, $anchor)){
                $RealContent = str_replace($anchor[0], '', $innertext);
                $result .= '<a id="'.$anchor[1].'"></a><span id="'.trim($RealContent).'">'.$RealContent;
            }else{
                $result .= '<span id="'.strip_tags($innertext).'">'.$innertext;
            }

            //+ 부분 편집 기능 작업
            //+ content-s- 속성 추가 (문단 숨기기용)
            $result .= '<span class="wiki-edit-section"><a href="'.$this->uriset['edit'].$this->title.$this->uriprefix.'section='.$id.'" rel="nofollow">[편집]</a></span>
                            </span>
                        </h'.$level.'><div id="content-s-'.$id.'" class="wiki-heading-content" '.$folded.'>';
            $line = '';

        }

        //+ 수평줄 문제 개선
        if(preg_match('/^[^-]*(-{4,9})[^-]*$/', $line))
            $line = '<hr class="wiki-hr">';
        else
            $line = $this->blockParser($line);

        // 행에 뭐가 있을때
        if($line != '') {
            if(strpos($line, '<hr class="wiki-hr">') !== false)
                $result .= $line;
            else
                $result .= $line.'<br>';
        }

        return $result;
    }

    private function blockParser($block) {
        return $this->formatParser($block);
    }

    private function bracketParser($text, &$now, $bracket) {
        $len = strlen($text);
        $cnt = 0;
        $done = false;
        $unloadedstr = '';
        $loadedstr = '';
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
            }elseif(self::startsWith($text, $bracket['close'], $i)) {
                // 닫는괄호에서 (}}} 중첩 시 문제 발생)
                if($bracket['strict'] && $bracket['multiline'] && strpos($unloadedstr, "\n")===false)
                    return false;
                
                $loadedstr .= call_user_func_array($bracket['processor'],array($unloadedstr, $bracket['open']));
                $now = $i+$closelen-1;
                return $loadedstr;
            }elseif(!$bracket['multiline'] && (self::startsWith($text, "\n", $i) || self::startsWith($text, "\r\n", $i)))
                return false; // 개행금지 문법에서 개행 발견
            else{
                foreach($this->brackets as $brac){
                    if(self::startsWith($text, $brac['open'], $i) && !$isEscape){
                        ++$cnt;
                        $char = $brac['open'];
                        $i += strlen($brac['open']) - 1;
                        array_push($opened, $brac['open']);
                        continue;
                    }elseif(self::startsWith($text, $brac['close'], $i) && !$isEscape){
                        --$cnt;
                        $char = $brac['close'];
                        $i += strlen($brac['close']) - 1;
                        unset($opened[array_search($brac['open'], $opened)]);
                        continue;
                    }
                }
            }
            $unloadedstr .= $char;
        }
        return false;
    }

    //+ 역슬래시 지원
    private function formatParser($line) {
        $line_len = strlen($line);
        $inline = '';
        for($j=0;$j<$line_len;self::nextChar($line,$j)) {
            $now = self::getChar($line,$j);
            if($now == "\\"){
                ++$j;
                $inline .= htmlspecialchars(self::getChar($line,$j));
                continue;
            }else {
                foreach($this->brackets as $bracket) {
                    $nj=$j;
                    if(self::startsWith($line, $bracket['open'], $j) && $bracket['multiline'] === false && $innerstr = $this->bracketParser($line, $nj, $bracket)) {
                        $inline .= $innerstr;
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
        return $inline;
    }

    protected function renderProcessor($text, $type) {
        $lines = explode("\n", $text);
        // 라인 단위로 끊어 읽기
        $text = '';
        foreach($lines as $key => $line) {
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
        }
        
        // {{{ }}} 처리
        // 코드블럭
        if(self::startsWithi($text, '#!html') && $this->inThread !== false) {
            // HTML
            return '<html>' . preg_replace('/UNIQ--.*?--QINU/', '', substr($text, 7)) . '</html>';
        } elseif(self::startsWithi($text, '#!wiki') && preg_match('/^([^=]+)=(?|"(.*?)"|\'(.*)\'|(.*))\n/', substr($text, 7), $match)) {
            // + {{{#!wiki }}} 문법
            $text = str_replace($match[0], '', substr($text,7));
            var_dump($text);
            $wPage = new WikiPage($text);
            $wEngine = new NamuMark($wPage);
            $wEngine->noredirect = $this->noredirect;
            $wEngine->prefix = $this->prefix;
            $wEngine->title = $this->title;
            $wEngine->uriset = $this->uriset;
            $wEngine->uriprefix = $this->uriprefix;
            $wEngine->wikitextbox = true;
            $wEngine->fn = $this->fn;
            $wEngine->fn_cnt = $this->fn_cnt;
            $wEngine->fn_overview = $this->fn_overview;
            $output = '<div '.html_entity_decode($match[0]).'>'.$wEngine->toHtml().'</div>';
            $this->fn += $wEngine->fn;
            $this->fn_cnt = $wEngine->fn_cnt;
            $this->fn_overview = $wEngine->fn_overview;

            return $output;
        } elseif(self::startsWithi($text, '#!syntax') && preg_match('/#!syntax ([^\s]*)/', $text, $match)) {
            // 구문
            return '<syntaxhighlight lang="' . $match[1] . '" line="1">' . preg_replace('/#!syntax ([^\s]*)/', '', $text) . '</syntaxhighlight>';
        } elseif(preg_match('/^\+([1-5])(.*)$/sm', $text, $size)) {
            // {{{+큰글씨}}}

            $lines = explode("\n", $size[2]);
            $size[2] = '';
            foreach($lines as $line) {
                if($line !== '')
                    $size[2] .= $line . "\n";
            }

            if(self::startsWith($size[2], '||')) {
                $offset = 0;
                $size[2] = $this->tableParser($size[2], $offset);
            }

            return '<span class="wiki-size size-up-'.$size[1].'">'.$this->formatParser($size[2]).'</span>';
        } elseif(preg_match('/^\-([1-5])(.*)$/sm', $text, $size)) {
            // {{{-작은글씨}}}

            $lines = explode("\n", $size[2]);
            $size[2] = '';
            foreach($lines as $line) {
                if($line !== '')
                    $size[2] .= $line . "\n";
            }

            if(self::startsWith($size[2], '||')) {
                $offset = 0;
                $size[2] = $this->tableParser($size[2], $offset);
            }

            return '<span class="wiki-size size-down-'.$size[1].'">' . $this->formatParser($size[2]) . '</span>';
        } else {
            return '<pre>' . $text . '</pre>';
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
        if(substr($text, -1, 1) == "\\"){
            return '[[]]';
        }

        // 우선처리대상
        if(strpos($text, '파일:') === 0){
            $linkpart = explode('|', $text);
            if(!$this->WikiPage->pageExists($linkpart[0])){
                return '<a class="wiki-link-internal not-exist" href="'.$this->uriset['wiki'].$linkpart[0].'">'.$linkpart[0].'</a>';
            }
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
                        // invalid format: 유효성을 switch문 이전에 확인하여 처리 속도를 높임.
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
            
            $href = $this->uriset['file'].hash('sha256', $fileName);
            if($rd === 0) array_push($this->links, ['target'=>$linkpart[0], 'type'=>'file']);
            $result = '<a class="wiki-link-auto" title="'.'" href="'.'" rel="nofollow">'
                    .'<span class="wiki-image-align-'.$imgAlign.'"'.$attr_align.'>'
                    .'<span class="wiki-image-wrapper"'.$attr_wrapper.'>'
                    .'<img class="wiki-image-space"'.$wrapTag
                    .' src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTE3OCIgaGVpZ2h0PSIxMTc4IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjwvc3ZnPg==">'
                    .'<img class="wiki-image" '.$wrapTag.'src="'.$href.'" alt="'.$fnWithoutExt.'" loading="lazy"></span></span></a>';
            if($rd !== 0) $rd = ['target' => $linkpart[0], 'class' => $classList];
            return $result;
        }elseif(strpos($text, '분류:') === 0){
            array_push($this->category, substr($text, strlen('분류:')));
            if($rd === 0) array_push($this->links, ['target'=>$target, 'type'=>'category']);
            if($rd !== 0) $rd = ['target' => $text, 'class' => $classList];
            return '';
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
                foreach($this->brackets as $bracket) {
                    if(self::startsWith($text, $bracket['open'], $i) && $bracket['multiline'] === true && $innerstr = $this->bracketParser($text, $i, $bracket)) {
                        $display .= $this->blockParser($unloadedstr).$innerstr;
                        $unloadedstr = '';
                        $now = '';
                        continue;
                    }
                }
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
            $href = $this->uriset['wiki'].self::encodeURIComponents($target).$exception;
        }

        if(in_array('wiki-link-internal', $classList) && !$this->WikiPage->pageExists($target))
            array_push($classList, 'not-exist');
        

        if(count($classList) > 0)
            $classStr = implode(' ', $classList);
        else
            $classStr = '';
            if($rd !== 0) $rd = ['target' => self::encodeURIComponents($target).$exception, 'class' => $classList];
        return '<a class="'.$classStr.'" href="'.$href.$sharp.'">'.$display.'</a>';
    }

    // 대괄호 문법
    private function macroProcessor($text, $type) {
        $macroName = strtolower($text);
        if(!empty($this->macro_processors[$macroName]))
            return $this->macro_processors[$macroName]();
        switch($macroName) {
            case 'br':
                return '<br>';
            case 'date':
            case 'datetime':
                return date('Y-m-d H:i:s');
            case '목차':
            case 'tableofcontents':
                // 목차
                return $this->printToc();
            case '각주':
            case 'footnote':
                // 각주모음
                return $this->printFootnote();
            //+ clearfix
            case 'clearfix':
                return '<div style="clear:both;"></div>';
            default:
                if(self::startsWithi($text, 'include') && preg_match('/^include\((.+)\)$/i', $text, $include) && $include = $include[1] && $this->inThread !== false) {
                    // include 문법
                    if($this->included)
                        return '';

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
                    return ' ';
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
                    return '<iframe width="'.(!empty($var['width'])?$var['width']:'640').'" height="'.(!empty($var['height'])?$var['height']:'360').'" src="//'.$this->videoURL[$include[1]].$include[0].'" frameborder="0" allowfullscreen></iframe>';
                }
                elseif(preg_match('/^age\(([0-9]{4))-(0[0-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\)$/i', $text, $include) && $this->inThread !== false) {
                    // 연령
                    $age = (date("md", date("U", mktime(0, 0, 0, $include[3], $include[2], $include[1]))) > date("md")
                        ? ((date("Y") - $include[1]) - 1)
                        : (date("Y") - $include[1]));
                    return $age;
                }
                elseif(preg_match('/^anchor\((.+)\)$/i', $text, $include) && $include = $include[1]) {
                    // 앵커
                    return '<a name="'.$include.'"></a>';
                }
                elseif(preg_match('/^dday\((.+)\)$/i', $text, $include) && $include = $include[1] && $this->inThread !== false) {
                    // D-DAY
                    $nDate = date("Y-m-d", time());
                    if(strtotime($nDate)==strtotime($include)){
                        return " 0";
                    }
                    return intval((strtotime($nDate)-strtotime($include)) / 86400);
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
                    if(isset($color)){
                        $rb = '<span style="color:'.$color.'">'.$rb.'</span>';
                    }
                    if(strlen($rb) > 0){
                        return '<ruby>'.$ruby[0].'<rp>(</rp><rt>'.$rb.'</rt><rp>)</rp></ruby>';
                    }else{
                        return ' ';
                    }
                    
                }
                elseif(preg_match('/^pagecount\((.*)\)$/i', $text, $include) && $include = $include[1] && $this->inThread !== false) {
                    if(in_array($include, $this->namespaces))
                        return $this->pageCount[$include];
                }
                elseif(preg_match('/^\*([^ ]*)([ ].+)?$/', $text, $note)) {
                    $notetext = !empty($note[2])?$this->blockParser($note[2]):'';
                    if(strlen($note[1]) > 0)
                        $name = strval($note[1]);
                    else
                        $name =  intval($this->fn_cnt + $this->lastfn) + 1;

                    if(!isset($this->fn[$name]))
                        $this->fn[$name] = $notetext;
                        
                    array_push($this->fn_overview, $name);
                    ++$this->fn_cnt;
                    $hrefid = ($note[1]=='')?$name:htmlspecialchars($note[1]);
                    return '<a class="wiki-fn-content" href="#fn-'.$hrefid.'"><span class="target" id="rfn-'.htmlspecialchars($name).'"></span>['.($note[1]?$note[1]:$name).']</a>';
                }
        }
        return '['.htmlspecialchars($text).']';
    }

    
    private function textProcessor($otext, $type) {
        if($type !== '{{{'){
            $text = $this->formatParser($otext);
        }else{
            $text = $otext;
        }
        switch ($type) {
            case "'''":
                // 볼드체
                return '<strong>'.$text.'</strong>';
            case "''":
                // 기울임꼴
                return '<em>'.$text.'</em>';
            case '--':
            case '~~':
                // 취소선
                // + 수평선 적용 안 되는 오류 수정
                if(@$this->strikeLine){
                    $text = '';
                }
                return '<del>'.$text.'</del>';
            case '__':
                // 목차 / 밑줄
                return '<u>'.$text.'</u>';
            case '^^':
                // 위첨자
                return '<sup>'.$text.'</sup>';
            case ',,':
                // 아래첨자
                return '<sub>'.$text.'</sub>';
            case '{{{':
                // HTML
                if(self::startsWith($text, '#!html') && $this->inThread !== false) {
                    $html = substr($text, 6);
                    $html = ltrim($html);
                    $html = self::inlineHtml($html);
                    return $html;
                }
                elseif(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+)) (.*)$/', $text, $color) && (self::chkColor($this, $color[1], '') || self::chkColor($this, $color[2], ''))) {
                    if(empty($color[1]) && empty($color[2]))
                        return $text;
                    return '<span style="color: '.(empty($color[1])?$color[2]:'#'.$color[1]).'">'.$this->formatParser($color[3]).'</span>';
                }
                elseif(preg_match('/^\+([1-5]) (.*)$/', $text, $size)) {
                    return '<span class="wiki-size size-up-'.$size[1].'">'.$this->formatParser($size[2]).'</span>';
                }
                elseif(preg_match('/^\-([1-5]) (.*)$/', $text, $size)) {
                    return '<span class="wiki-size size-down-'.$size[1].'">'.$this->formatParser($size[2]).'</span>';
                }
                // 문법 이스케이프
                return '<code class="wiki-escape">' . htmlspecialchars($text) . '</code>';
            }
            return $type.$text.$type;
    }

    // 페이지 하단 각주 목록
    private function printFootnote() {
        $declared = [];
        $result = '<div class="wiki-macro-footnote">';
        
        for($i=0; $i<$this->fn_cnt; ++$i) {
            $fn_name = $this->fn_overview[$i];
            $footnote = $this->fn[$fn_name];
            if(!array_search($fn_name, $declared)){
                $result .= '<span class="footnote-list">';
                $result .= '<span class="target" id="fn-'.htmlspecialchars($fn_name).'"></span>';
                
                $ak = array_keys($this->fn_overview, $fn_name);
                $jc = count($ak);
                if(is_string($fn_name) && $jc > 1){
                    $result .= '['.$fn_name.'] ';
                    for($j=0; $j<$jc; ++$j){
                        $result .= '<a href="#rfn-'.$ak[$j]+$this->lastfn+1 .'"><sup>'.min($ak)+$this->lastfn+1 .'.'.$j+1 .'</sup></a> ';
                    }
                }else{
                    $result .= '<a href="#rfn-'.$i+$this->lastfn+1 .'">['.$fn_name.']</a> ';
                }
            
                $result .= $footnote.'</span>';
                array_push($declared, $fn_name);
            }
        }
        $result .= '</div>';
        $this->fn_overview = [];
        $this->fn = [];
        $this->fn_cnt = 0;
        $this->lastfn += $i;
        return $result;
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

    private function hParse(&$text) {
        // 행 분리
        $lines = explode("\n", $text);
        $result = '';
        foreach($lines as $line) {
            $matched = false;
            foreach($this->h_tag as $tag_ar) {
                $tag = $tag_ar[0];
                $level = $tag_ar[1];
                if(!empty($tag) && preg_match($tag, $line, $match)) {
                    $this->tocInsert($this->toc, $this->blockParser($match[1]), $level);
                    $matched = true;
                    break;
                }
            }
        }

        return $result;
    }

    // HTML 목차 출력
    private function printToc(&$arr = null, $level = -1, $path = '') {
        if($level == -1) {
            $bak = $this->toc;
            $this->toc = array();
            $this->hParse($this->WikiPage->text);
            $result = ''
                .'<div class="wiki-macro-toc" id="toc">'
                    .$this->printToc($this->toc, 0)
                .'</div>'
                .'';
            $this->toc = $bak;
            return $result;
        }

        if(empty($arr[0]))
            return '';

        // + 목차에 앵커 들어가는거 수정
        $result  = '<div class="toc-indent">';
        foreach($arr as $i => $item) {
            $readableId = $i+1;
            $result .= '<span class="toc-item"><a href="#s-'.$path.$readableId.'">'.$path.$readableId.'</a>. '
                            .preg_replace('/\[anchor\((.*)\)\]/', '', $item['name']).'</span>'
                            .$this->printToc($item['childNodes'], $level+1, $path.$readableId.'.')
                            .'';
        }
        $result .= '</div>';
        return $result;
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

    private static function seekEndOfLine($text, $offset=0) {
        return self::seekStr($text, "\n", $offset);
    }

    private static function seekStr($text, $str, $offset=0) {
        if($offset >= strlen($text) || $offset < 0)
            return strlen($text);
        return ($r=strpos($text, $str, $offset))===false?strlen($text):$r;
    }

    // HTML 문법
    private static function inlineHtml($html) {
        $html = str_replace("\n", '', $html);
        $html = preg_replace('/<\/?(?:object|param)[^>]*>/', '', $html);
        $html = preg_replace('/<embed([^>]+)>/', '<iframe$1 frameborder="0"></iframe>', $html);
        $html = preg_replace('/(<img[^>]*[ ]+src=[\'\"]?)(https?\:[^\'\"\s]+)([\'\"]?)/', '$1$2$3', $html);
        return $html;
    }

    private static function encodeURIComponents($str) {
        return str_replace(['%', ':', '/', '#', '(', ')', '|'], ['%25', '%3A', '%2F', '%23', '%28', '%29', '%7C'], $str);
    }

    private static function chkColor($context, string $color, $sharp = '#') {
        if(preg_match('/^'.$sharp.'([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $color))
            return true;
        elseif(in_array($color, $context->cssColors))
            return true;
        else
            return false;
    }
}

class HTMLElement {
    public $tagName, $innerHTML, $attributes;
    function __construct($tagname) {
        $this->tagName = $tagname;
        $this->innerHTML = null;
        $this->attributes = array();
        $this->style = array();
    }

    public function toString() {
        $style = $attr = '';
        if(!empty($this->style)) {
            foreach($this->style as $key => $value) {
                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('"', '\\"', $value);
                $style.=$key.':'.$value.';';
            }
            $this->attributes['style'] = substr($style, 0, -1);
        }
        if(!empty($this->attributes)) {
            foreach($this->attributes as $key => $value) {
                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('"', '\\"', $value);
                $attr.=' '.$key.'="'.$value.'"';
            }
        }
        return '<'.$this->tagName.$attr.'>'.$this->innerHTML.'</'.$this->tagName.'>';
    }
}
}