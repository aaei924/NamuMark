<?php
/*
 * NamuMark Renderer in PHP
 * by PRASEOD-(aaei924)
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
 */
//$t = file_get_contents('../raw.txt');
$t = 
" * list
 ㅁㄴㅇㄹ
  ㅁㄴㅇㄹ
ㅁㄴㅇㄹ";
$NM = new NamuMark($t);
echo json_encode($NM->parse(), JSON_UNESCAPED_UNICODE);
class NamuMark{
    public function __construct($txt){
        $this->wikitext = $txt;
        $this->lines = explode("\n", $this->wikitext);
        $this->lineCount = count($this->lines);
        $this->heading_tags = [
            '/^====== (.*) ======$/' => ['level' => 6, 'folding' => false],
            '/^===== (.*) =====$/' => ['level' => 5, 'folding' => false],
            '/^==== (.*) ====$/' => ['level' => 4, 'folding' => false],
            '/^=== (.*) ===$/' => ['level' => 3, 'folding' => false],
            '/^== (.*) ==$/' => ['level' => 2, 'folding' => false],
            '/^= (.*) =$/' => ['level' => 1, 'folding' => false],
            '/^======# (.*) #======$/' => ['level' => 6, 'folding' => true],
            '/^=====# (.*) #=====$/' => ['level' => 5, 'folding' => true],
            '/^====# (.*) #====$/' => ['level' => 4, 'folding' => true],
            '/^===# (.*) #===$/' => ['level' => 3, 'folding' => true],
            '/^==# (.*) #==$/' => ['level' => 2, 'folding' => true],
            '/^=# (.*) #=$/' => ['level' => 1, 'folding' => true]
        ];
        $this->list_tags = [
            '*' => array('ordered' => false),
            '1.' => array('ordered' => true, 'type' => 'decimal'),
            'A.' => array('ordered' => true, 'type' => 'upper-alpha'),
            'a.' => array('ordered' => true, 'type' => 'lower-alpha'),
            'I.' => array('ordered' => true, 'type' => 'upper-roman'),
            'i.' => array('ordered' => true, 'type' => 'lower-roman')
        ];
        $this->inline_tags = [
            '--','~~',"'''","''",'__',',,','^^','{{{'
        ];
    }
    
    public function parse(){
        $this->pos = 0;
        $this->paragraphLevel = 0;
        $this->indentLevel = 0;
        $this->listLevel = 0;
        $this->tokens = [];
        // redirect
        if(preg_match('/^#(redirect|넘겨주기) (.*)$/', $this->lines[0], $match)){
            return [['name' => 'redirect', 'target' => $match[2]]];
        }
        
        for($i=0; $i<$this->lineCount; ++$i){   // read per line
            $line = $this->lines[$i];           // each line
            $len = iconv_strlen($line);         // line length
            if($len === 0){
                array_push($this->tokens, ['name' => 'paragraph-start'], ['name' => 'paragraph-end']);
                continue;                       // Nothing::skip line
            }
            
            for($j=0; $j<$len; ++$j){           // read per char
                var_dump($j);
                $now = iconv_substr($line,$j,1);
                // valid in first char only
                if($j === 0){
                    if(preg_match('/^([ ]{1,})(.*)$/', $line, $match)){
                        // 이 줄의 \n이 나올 때까지 텍스트가 없을 때
                        $inlineSet = [];
                        if(strlen($match[2]) < 1) array_push($this->token, ['name' => 'paragraph-start'], ['name' => 'paragraph-end']);
                        $startCount = strlen($match[1]) - $this->indentLevel;
                        $endCount = $this->indentLevel - strlen($match[1]);

                        // 인덴트 레벨 지정 / 리스트는 앞에 공백 하나가 더 있어서 따로 처리
                        if(preg_match('/^(\*|1.|a.|A.|I.|i.) .*/', $match[2])){
                            $this->indentLevel = (strlen($match[1]) - 1);
                            --$startCount;
                            ++$endCount;
                        }else
                            $this->indentLevel = strlen($match[1]);

                        // 들여쓰기 수에 맞춰서 indent 시작과 끝 넣기(들여쓰기 수 변화 있어야 작동)
                        for($k=0; $k<$startCount; ++$k)
                            array_push($this->tokens, ['name' => 'indent-start']);
                        if($endCount > 0){
                            // Indent가 줄어들 때 리스트를 먼저 닫아야 하므로
                            if($this->listLevel > 0){
                                --$this->listLevel;
                                array_push($this->tokens, ['name' => 'list-end']);
                            }
                        }
                        for($k=0; $k<$endCount; ++$k)
                            array_push($this->tokens, ['name' => 'indent-end']);

                        // 진짜 리스트 처리
                        if(preg_match('/^(\*|1.|a.|A.|I.|i.) (.*)/', $match[2], $matches)){
                            // 들여쓰기 변화 없을 때
                            if($startCount === 0 && $endCount === 0){
                                array_push($this->tokens, ['name' => 'list-contents', 'type' => 'block', 'content' => $matches[2]]);
                            }elseif($startCount > 0){
                                ++$this->listLevel;
                                array_push($this->tokens, ['name' => 'list-start', 'type' => $this->list_tags[$matches[1]]], ['name' => 'list-contents', 'type' => 'block', 'content' => $matches[2]]);
                            }
                            $inlineText = '';
                        }else{
                            // 
                            if($this->paragraphLevel) array_push($this->tokens, ['name' => 'line-break']);
                            $this->paragraphLevel = $this->indentLevel + 1;
                            array_push($this->tokens, ['name' => 'paragraph-start'], ['name' => 'list-contents', 'type' => 'block', 'content' => $matches[2]]);
                        }
                        // 다음줄은 리스트가 아닐 때
                        if(!preg_match('/^([ ]{1,})(\*|1.|a.|A.|I.|i.) (.*)/', $this->lines[$i+1]) && $this->listLevel > 0){
                            --$this->listLevel;
                            array_push($this->tokens, ['name' => 'list-end']);
                        }
                        $now = '';
                        break;
                    }

                    if(preg_match('/^||(.*)||$/', $line, $match)){

                    }
                }

                // last char in line
                if($j === $len - 1 && strlen($inlineText) > 0){
                    array_push($this->tokens, ['name' => 'paragraph-start'],['name' => 'text-plain', 'content' => $inlineText.$now],['name' => 'paragraph-end']);
                    $now= '';
                }else
                    $inlineText .= $now;
                ++$this->pos;
                /* NAMUMARK
                
                    * li
                    * li
                    * li

                    */
                    /* DOM TREE
                        ul/ol
                        - li
                            - div(paragraph) /
                            - div(indent)
                                - ul
                                    - li
                                        - div(paragraph) /
                                    - /li
                                - /ul
                            - /div
                        - /li
                        - li /
                    */
                    // (.+)는 1개 이상의 문자, (.*)는 0개 이상의 문자

                    

                    /*/ 삼중괄호
                    if(preg_match('/{{{}}}/', $l, $match)){
                        $done = false;
                        if($this->listLevel == 0 && !$done){
                            ++$this->listLevel;
                            array_push($this->tokens, ['name' => 'list-start'] + $this->list_tags[$match[2]]);
                        }elseif($this->listLevel > 0){

                        }elseif($this->listLevel == 0 && $done){
                            array_push($this->tokens, ['name' => 'list-end']);
                        }
                        array_push($this->tokens, ['name' => 'list-contents', 'level' => $this->listLevel, 'content' => $this->BlockParser($match[3])]);
                }*/
            }
            // 마지막 줄인데 인덴트가 걸려있으면 다 풀어버리기
            if($i === $this->lineCount - 1){
                if($this->indentLevel > 0){
                    for($j=0; $j<$this->indentLevel; ++$j):
                        array_push($this->tokens, ['name' => 'indent-end']);
                    endfor;
                }
            }

            ++$this->pos;
        }
        //preg_match('/\[\[((.*)[^\\])\]\]/', $temp, $match);
        return $this->tokens;
    }

    protected function BlockParser($block){
        $result = '';
        if(preg_match('/^(\*|1.|a.|A.|I.|i.) (.*)$/', $match[2], $match)){
            $this->ListParser($match[0]);
        }
        $this->pos = $this->eol($black);
    }

    protected function ListParser($text){
        $strlen = iconv_strlen($text);
        for($i=0; $i<$strlen; ++$i){
            if(preg_match('/^(\*|1.|a.|A.|I.|i.) (.*)$/', iconv_substr($text, $i), $match)){
                $done = false;
                // 리스트 시작
                if($this->listLevel == 0 && !$done){
                    ++$this->listLevel;
                    array_push($this->tokens, ['name' => 'list-start'] + $this->list_tags[$match[1]]);
                    array_push($this->tokens, ['name' => 'list-contents', 'content' => $this->BlockParser($match[2])]);
                    $i = iconv_strpos($text, $match[2], 1);
                // 리스트 중간
                }elseif($this->listLevel > 0){
                    if($indent_size < strlen($match[1])){
                        ++$this->listLevel;
                    }elseif($indent_size > strlen($match[1])){
                        --$this->listLevel;
                    }
                // 리스트 끝
                }elseif($this->listLevel == 0 && $done){
                    array_push($this->tokens, ['name' => 'list-end']);
                    
                }
            }
        }
    }
    
}
