<?php
protected function tableParser($text, &$offset) {
        $token = ['caption' => null, 'colstyle' => [], 'rows' => []];
        $tableinit = true;
        $tableAttr = [];
        $tableattrinit = [];
        $trAttrStr = '';
        $tableInnerStr = '';
        $trInnerStr = '';
        $tdInnerStr = '';
        $tdAttrStr = '';
        $text = substr($text, $offset);
        $i = $offset;
        $len = strlen($text);
        $noheadmark = true; // 리스트랑 인용문은 행의 처음에서만 적용되어야 함.
        $intd = true;
        $rowIndex = 0;
        $colIndex = 0;

        // caption 파싱은 처음에만
        if(self::startsWith($text, '|') && !self::startsWith($text, '||') && $tableinit === true) {
            $caption = explode('|', $text);
            if(count($caption) < 3)
                return false;
            $text = implode('|', array_slice($caption, 2));
            $token['caption'] = $this->blockParser($caption[1]);
            $hasCaption = true;
            $tableinit = false;
            //   (|)   (caption content)   (|)
            $i += 1 + strlen($caption[1]) + 1;
        }elseif(self::startsWith($text, '||') && $tableinit === true){
            $text = substr($text, 2);
            $hasCaption = false;
            $tableinit = false;
        }elseif($tableinit === true)
            return false;
        
        $text = htmlspecialchars_decode($text);
        for($i; $i<$len; ++$i){
            $now = self::getChar($text,$i);
            
            //+ 백슬래시 문법
            if($now == "\\"){
                ++$i;
                $tdInnerStr .= self::getChar($text,$i);
                continue;
            }
            
            // 리스트
            if($noheadmark === false && $tdInnerStr == '' && $now == ' ' && $list = $this->listParser($text, $i)) {
                $tdInnerStr .= $list;
                $now = '';
                continue;
            }

            // td 구분
            if($now.self::getChar($text,$i+1) == '||') {
                //var_dump($tdInnerStr);
                if($intd === true){
                    //td end and new td start
                    $token['rows'][$rowIndex]['cols'][$colIndex] = ['text' => $this->blockParser($tdInnerStr), 'style' => $tdAttr];
                    $tdInnerStr = '';
                    ++$colIndex;
                    ++$i;
                    continue;
                }elseif($intd === false){
                    // new td start
                    //$token['rows'][$rowIndex]['cols'][$colIndex] = [];
                    $intd = true;
                    ++$i;
                    continue;
                }
                $now = '';
                continue;
            }

            // 인용문
            if($noheadmark === false && $tdInnerStr == '' && self::startsWith($text, '&gt;', $i) && $blockquote = $this->bqParser($text, $i)) {
                $tdInnerStr .= $blockquote;
                $now = '';
                continue;
            }
            
            // bracket
            foreach($this->multi_bracket as $bracket) {
                if(self::startsWith($text, $bracket['open'], $i) && $innerstr = $this->bracketParser($text, $i, $bracket)) {
                    $tdInnerStr .= $this->lineParser($tdInnerStr).$innerstr;
                    $now = '';
                    break;
                }
            }

            // new row
            if($now.self::getChar($text,$i+1).self::getChar($text,$i+2) == "\n||" && $tdInnerStr == '') {
                ++$rowIndex;
                $colIndex = 0;
                $noheadmark = true;
                $intd = false;
            }elseif($now == "\n" && self::getChar($text,$i+1) !== '|' && $tdInnerStr == ''){
                ++$i;
                break;
            }elseif($now == "\n"){
                // just breaking line
                $tdInnerStr .= $now;
                $noheadmark = false;
            }else{
                // other string
                $tdInnerStr.=$now;
                $noheadmark = true;
            }
            if(strlen($now) > 1){
                $i += strlen($now) - 1;
            }
        }

        foreach ($token['rows'] as $r){
            if(!is_array($r))
                var_dump($r);
            foreach ($r['cols'] as $rc){
                if($rc == 'span')
                    continue;
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
        $offset = $i;
        return '<div class="wiki-table-wrap'.$tbClassStr.'"><table'.$tableAttrStr.'>'.$tableInnerStr.'</table></div>';
    }
