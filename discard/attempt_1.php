<?php
class PlainWikiPage {
	// 평문 데이터 호출
	public $title, $text, $lastchanged;
	function __construct($text) {
		$this->title = '(inline wikitext)';
		$this->text = $text;
		$this->lastchanged = time();
	}

	function getPage($name) {
		return new PlainWikiPage('');
	}
}

class NamuMark{
    public $redirectPattern = '/^#(?:redirect|넘겨주기) (.+)$/im';
    public function __construct($wpage){
		global $DO;
        $this->defaultOptions = [
			'wiki' => ['read' => $wpage->text],
			'allowedExternalImageExts' => ['PNG', 'JPG'],
			'included' => false,
			'includeParameters' => [],
			'macroNames' => ['br', 'date', '목차', 'tableofcontents', '각주', 'footnote', 'toc', 'youtube', 'nicovideo', 'kakaotv', 'include', 'age', 'dday']
		];
		$DO = $this->defaultOptions;

        $this->options = $this->defaultOptions;
        $this->wikitext = $this->options['wiki']['read'];
		$this->rendererOptions = null;
		$this->renderer = null;
    }

    private function seekEOL($text, $offset = 0){
	return (iconv_strpos($text, '\n', $offset) === false)? iconv_strlen($text) : iconv_strpos($text, '\n', $offset);
    }

    public function doParse(){
        $multiBrackets = array([
	    'open' => '{{{',
	    'close' => '}}}',
	    'multiline' => true,
	    'processor' => 'renderProcessor'
        ]);
        //$this->renderer = ($this->rendererOptions)? new HTMLRenderer($this->rendererOptions):new HTMLRenderer();
        $line = ''; $now = ''; $tokens = [];

        if($this->wikitext === null) // No Content
	    return array(['name' => 'error', 'type' => 'notfound']);

        // Redirect(#로 시작하고, 패턴이 일치하며, 시작하자마자 이 패턴이 나타난다면)
        if(str_starts_with($this->wikitext, '#') && preg_match($this->redirectPattern, $this->wikitext, $r_match) && iconv_strpos($this->wikitext, $r_match[0]) === 0)
	    return array(['name' => 'redirect', 'target' => $r_match[1]]);

        $wt_strlen = iconv_strlen($this->wikitext);
        // offset 설정하기
        for($this->i=0;$this->i < $wt_strlen; ++$this->i){
            $temp = ['pos' => $this->i];
	    $now = iconv_substr($this->wikitext,$this->i,1);

            // 라인 시작점에 공백이 있고 목록 파서가 작동하면
            if($line == '' && $now == ' ' && $temp = $this->listParser($this->wikitext, $this->i, fn($v) => $this->i = $v)){
	        $tokens += $temp;
	        $line = '';
	        $now = '';
	        continue;
	    }

            // 라인이 |로 시작하고 표 파서가 작동하면
	    if($line == '' && str_starts_with(iconv_substr($this->wikitext, $this->i), '|') && $temp = $this->tableParser($this->wikitext, $this->i, fn($v)=> $this->i = $v)){
                $tokens += $temp;
	        $line = '';
	        $now = '';
	        continue;
	    }

            // 라인이 >로 시작하고 인용문 파서가 작동하면
	    if($line == '' && str_starts_with(iconv_substr($this->wikitext, $this->i), '>') && $temp = $this->blockquoteParser($this->wikitext, $this->i, fn($v) => $this->i = $v, fn($proc, $args) => $this->$proc($args[1], $args[2]))){
	        $tokens += $temp;
	        $line = '';
	        $now = '';
	        continue;
	    }

            // multiBrackets 파싱
	    foreach($multiBrackets as $bracket){
	        // 여는 브라켓으로 시작하고 파서가 작동한다면
	        if(str_starts_with(iconv_substr($this->wikitext, $this->i), $bracket['open']) && $temp = $this->bracketParser($this->wikitext, $this->i, fn($v) => $this->i = $v)){
		    $tokens = $tokens + [['name' => 'wikitext', 'treatAsLine' => true, 'text' => $line]] + $temp;
		    $line = '';
		    $now = '';
		    break;
	        }
	    }

            // 개행 지점일때
	    if($now === '\n'){
	        $tokens += [['name' => 'wikitext', 'treatAsLine' => true, 'text' => $line]];
	        $line = '';
	    } else {
	        $line .= $now;
            }
        }
        if(iconv_strlen($line) != 0)
            $tokens += array(['name' => 'wikitext', 'treatAsLine' => true, 'text' => $line]);
			return $tokens; //return $this->renderer->getResult();
    }

    /*function processTokens($newarr){
 	    if(!is_array($newarr)) $newarr = [];
 	    foreach($newarr as $v){
 		if($v['name'] !== 'wikitext')
 		    $this->renderer->processToken($v);
 		elseif($v['parseFormat'] || $v['treatAsBlock'])
 		    $this->processTokens($this->blockParser($v['text']));
 		elseif($v['treatAsLine'])
 		    $this->processTokens($this->lineParser($v['text']));
 	    }
    }*/

    function blockParser($line){
		$result = array();
		$s_formats = ["'''", "''", '~~', '--', '__', '^^',',,'];
		$s_result = [];
		foreach($s_formats as $s_e) {
			array_push($s_result, [
				'open' => $s_e,
				'close' => $s_e,
				'multiline' => false,
				'processor' => 'textProcessor'
			]);
		}
		$singleBrackets = array_merge([[
			'open' => '{{{',
			'close' => '}}}',
			'multiline' => false,
			'processor' => 'textProcessor'
		],
			[
			'open' => '{{|',
			'close' => '|}}',
			'multiline' => false,
			'processor' => 'closureProcessor'
		],
		[
			'open' => '[[',
			'close' => ']]',
			'multiline' => false,
			'processor' => 'linkProcessor'
			],
		[
			'open' => '[',
			'close' => ']',
			'multiline' => false,
			'processor' => 'macroProcessor'
		],
		[
			'open' => '@',
			'close' => '@',
			'multiline' => false,
			'processor' => 'textProcessor'
		]], $s_result);
		$plainTemp = '';
		$linecount = iconv_strlen($line);
		for($j=0;$j<$linecount;$j++){
			$extImgPattern = '/(https?:\\/\\/[^ \\n]+(?:\\??.)(?:'.implode('|',$this->options['allowedExternalImageExts']).'))(\\?[^ \n]+|)/i';
				$extImgOptionPattern = '/[&?]((width|height|align)=(left|center|right|[0-9]+(?:%|px|)))/';

				// offset 지점이 http로 시작하고, 패턴이 일치하며, 해당 패턴이 오프셋지점에 있을 때
			if(str_starts_with(iconv_substr($line, $j),'http') && preg_match($extImgPattern, $line, $matches) && iconv_strpos($line,$matches[0]) === 0){
			$imgUrl = $matches[1];
			$optionsString = $matches[2];
			$optionMatches = preg_match_all($extImgOptionPattern, $optionsString);

					// 외부이미지 불러오기 옵션
			if (!is_array($optionMatches[1])) $optionMatches = array(null, []);
			$styleOptions = [];
			$_omcount = count($optionMatches[1]);
			for($k=1;$k<$_omcount;$k++){
						$styleOptions[$optionMatches[1][$k]] = $optionMatches[2][$k];
			}
					// 평문 처리
			if(strlen($plainTemp) !== 0){
				array_push($result, ['name' => 'plain', 'text' => $plainTemp]);
				$plainTemp = '';
			}
			array_push($result, ['name' => 'external-image', 'style'=> $styleOptions, 'target' => $imgUrl]);
			$j += iconv_strlen($matches[0]) -1;
			continue;
			}else{
				$this->nj = $j;
				$matched = false;

				// 싱글브라켓 처리
				foreach ($singleBrackets as $bracket){
					$temp = null;
					$innerStrLen = null;
					// 여는 브라켓으로 시작하고 파서가 작동한다면
					if(str_starts_with(iconv_substr($line,$j),$bracket['open']) && $temp = $this->bracketParser($line, $this->nj, $bracket, fn($v) => $this->nj = $v, fn($proc, $args) => call_user_func(['NamuMark', $proc], $args[0], $args[1]), fn($v) => $this->innerStrLen = $v)){
						if(strlen($plainTemp) !== 0){
								array_push($result, ['name' => 'plain', 'text' => $plainTemp]);
							$plainTemp = '';
						}
						$result += $temp;
						$j += $this->innerStrLen - 1;
						$matched = true;
						break;
					}
				}

				if(!$matched){
							// 한 줄의 끝
					if(substr($line,$j,1) == '\n'){
					array_push($result, ['name' => 'plain', 'text' => $plainTemp]);
					$plainTemp = '';
					}else{
					$plainTemp .= substr($line,$j,1);
					}
				}
			}
		}
		if(strlen($plainTemp) !== 0) {
			array_push($result, ['name' => 'plain', 'text' => $plainTemp]);
			$plainTemp = '';
		}
		return $result;
    }

    function lineParser($line){
		$result = [];
		$headings = [
			'/^= (.+) =$/' => 1,
			'/^== (.+) ==$/' => 2,
			'/^=== (.+) ===$/' => 3,
			'/^==== (.+) ====$/' => 4,
			'/^===== (.+) =====$/' => 5,
			'/^====== (.+) ======$/' => 6
		];

			// ##으로 시작하는 행: 주석
		if(str_starts_with($line, '##'))
				return array(array('name' => 'comment', 'text' => iconv_substr($line,2)));

			// =로 시작하는 행: 목차
		if(str_starts_with($line, '=')){
			foreach($headings as $patternString){
				if(preg_match($patternString, $line, $hd_m)){
				$level = $headings[$patternString];
				return [['name' => 'heading-start', 'level' => $level], ['name' => 'wikitext', 'treatAsBlock' => true, 'text' => $hd_m[1]], ['name' => 'heading-end']];
			}
			}
		}

			// 행이 -로만 되어 있고, 그 길이가 4자이상 10자이하일때: 수평선
		$l_strlen = strlen($line);
		if(!preg_match('/[^-]/', $line) && $l_strlen >= 4 && $l_strlen <= 10){
			return [['name' => 'horizontal-line']];
		}

			// 행 길이가 0이 아닐 때
		if(strlen($line) !== 0)
			return [['name' => 'paragraph-start'], blockParser($line), ['name' => 'paragraph-end']];
		else
			return array();
    }
	
	function bracketParser($wikitext, $pos, $bracket, $setpos, $callProc, $matchLenCallback=null){
		$cnt = 0;
		$done = false;
		$txtstrlen = iconv_strlen($wikitext);
		for($i=$pos;$i<$txtstrlen;++$i){
			// 여는괄호로 시작하고, 여는괄호가 닫는괄호랑 모양이 다르고, 열린 괄호가 없을 때
			if(str_starts_with(iconv_substr($wikitext, $i), $bracket['open']) && !($bracket['open'] === $bracket['close'] && $cnt > 0)){
				// 열린 괄호 수 +1
				++$cnt;
				$done = true;
				$i += strlen($bracket['open']) - 1;
			} elseif(str_starts_with(iconv_substr($wikitext, $i), $bracket['close'])){
				// 열린 괄호 수 -1
				--$cnt;
				$i += strlen($bracket['close']) - 1;
			} elseif(!$bracket['multiline'] && iconv_substr($wikitext,$i,1) === '\n'){
				return null;
			}

			if($cnt === 0 && $done){
				$innerString = iconv_substr($wikitext, $pos + strlen($bracket['open']), $i - strlen($bracket['close']) + 1);
				if($matchLenCallback)
					$matchLenCallback(iconv_strlen($innerString) + strlen($bracket['open']) + strlen($bracket['close']));
				$setpos($i);
				return $callProc($bracket['processor'], [$innerString, $bracket['open']]);
			}
		}
		return null;
	}
	function blockquoteParser($wikitext, $pos, $setpos){
		$temp = array();
		$result = array();
		$_wlen = iconv_strlen($wikitext);
		for($i=$pos;$i<$_wlen;$i=$this->seekEOL($wikitext, $i)+1){
			$eol = $this->seekEOL($wikitext, $i);
			if(!str_starts_with(iconv_substr($wikitext, $i), '>'))
				break;
			preg_match('/^>+/', iconv_substr($wikitext,$i), $bq_match);
			$level = iconv_strlen($bq_match[0]);
			$line = iconv_substr($wikitext, $i+$level, $eol);
			array_push($temp, array('level' => $level, 'line' => $line));
		}
		if(count($temp) == 0)
			return null;
		$curLevel = 1;
		array_push($result, array('name' => 'blockquote-start'));
		foreach($temp as $curTemp){
			if($curTemp['level'] > $curLevel){
				$_clvcalc = $curTemp['level'] - $curLevel;
				for($i=0; $i<$_clvcalc; $i++)
					array_push($result, array('name' => 'blockquote-start'));
			} elseif($curTemp['level'] < $curLevel){
				$_clvcalc = $curLevel - $curTemp['level'];
				for($i=0; $i<$_clvcalc; $i++)
					array_push($result, array('name' => 'blockquote-end'));
			} else{
				array_push($result, array('name' => 'new-line'));
			}
			array_push($result, array('name' => 'wikitext', 'parseFormat' => true, 'text' => $curTemp['line']));
		}
		array_push($result, array('name' => 'blockquote-end'));
		$setpos($i-1);
                return $result;
	}
	function finishTokens($tokens){
		$result = array();
		$prevListLevel = 0;
		$prevIndentLevel = 0;
		$prevWasList = false;
		foreach($tokens as $curToken){
			$curWasList = $curToken['name'] === 'list-item-temp';
			if($curWasList !== $prevWasList) {
				$_lv = ($prevWasList)?$prevListLevel:$prevIndentLevel;
				for($j=0; $j<$_lv; $j++)
					($prevWasList)? $_leie = 'list-end':$_leie = 'indent-end';
					array_push($result, array('name' => $_leie));
				if($prevWasList) $prevListLevel = 0;
				else $prevIndentLevel = 0;
			}
			switch($curToken['name']){
				case 'list-item-temp':
					if($prevListLevel < $curToken['level']) {
						$_clpl = $curToken['level'] - $prevListLevel;
						for($j=0; $j < $_clpl; $j++)
							array_push($result, array('name' => 'list-start', 'listType' => $curToken['listType']));
					} elseif ($prevListLevel > $curToken['level']){
						$_clpl = $prevListLevel - $curToken['level'];
						for($j=0; $j < $_clpl; $j++)
							array_push($result, array('name' => 'list-end'));
					} elseif($prevListType['ordered'] !== $curToken['listType']['ordered'] || $prevListType['type'] !== $curToken['listType']['type']){
						array_push($result, array('name' => 'list-end'));
						array_push($result, array('name' => 'list-start', 'listType' => $curToken['listType']));
					}
					$prevListLevel = $curToken['level'];
					$prevListType = $curToken['listtype'];
					($curToken['startNo'])? $_sN = $curToken['startNo']:$_sN = null;
					array_push($result, array('name' => 'list-item-start', 'startNo' => $_sN));
					array_push($result, array('name' => 'wikitext', 'treatAsBlock' => true, 'text' => $curToken['wikitext']));
					array_push($result, array('name' => 'list-item-end'));
					break;
				case 'indent-temp':
					if($prevIndentLevel < $curToken['level']) {
						$_clpl = $curToken['level'] - $prevIndentLevel;
						for($j=0; $j<$_clpl; $j++)
							array_push($result, array('name' => 'indent-start'));
					} elseif($prevIndentLevel > $curToken['level']) {
						$_clpl = $prevIndentLevel - $curToken['level'];
						for($j=0; $j<$_clpl; $j++)
							array_push($result, array('name' => 'indent-end'));
					}
					$prevIndentLevel = $curToken['level'];
					array_push($result, array('name' => 'wikitext', 'treatAsBlock' => true, 'text' => $curToken['wikitext']));
					### listParser.js 50행
			}
			if($i === count($tokens) - 1){
				if($curWasList) {
					for($j=0; $j < $prevListLevel; $j++)
						array_push($result, array('name' => 'list-end'));
				} else{
					for($j=0; $j < $prevIndentLevel; $j++)
						array_push($result, array('name' => 'indent-end'));
				}
			}
			$prevWasList = $curWasList;
		}
		return $result;
	}
	function listParser($wikitext, $pos, $setpos){
		$listTags = array(
			'*' => array('ordered' => false),
			'1.' => array('ordered' => true, 'type' => 'decimal'),
			'A.' => array('ordered' => true, 'type' => 'upper-alpha'),
			'a.' => array('ordered' => true, 'type' => 'lower-alpha'),
			'I.' => array('ordered' => true, 'type' => 'upper-roman'),
			'i.' => array('ordered' => true, 'type' => 'lower-roman')
		);
		$lineStart = $pos;
		$result = array();
		$isList = null;
		$_wlen = iconv_strlen($wikitext);
		for($i=$pos; $i<$_wlen; $i++){
			$char = iconv_substr($wikitext, $i, 1);
			if($char !== ' ') {
				if($lineStart === $i)
					break;
				$level = $i - $lineStart;
				$matched = false;
				$quit = false;
				$eol = $this->seekEOL($wikitext, $i);
				$innerString = iconv_substr($wikitext, $i, $eol);
				$_ltcount = count($listTags);
				for($k=0; $k<$_ltcount; $k++){
                                        $j = array_keys($listTags)[$k];
					$listTagInfo = $listTags[$j];
					$innerString = iconv_substr($wikitext, $i + strlen($j), $eol);
					preg_match('/'.preg_replace('/\*/', '\\*', preg_replace('/\./', '\\.', $j)).'#([0-9]+)/', iconv_substr($wikitext, $i), $startNoSpecifiedPattern);
					if(str_starts_with(iconv_substr($wikitext, $i), $j)){
						if($isList === null)
							$isList = true;
						elseif(!$isList) {
							$quit = true;
							break;
						}
						$matched = true;
						if($startNoSpecifiedPattern){
							$startNo = intval($startNoSpecifiedPattern[1]);
							$innerString = preg_replace('/^#[0-9]+/', '', $innerString);
							array_push($result, array('name' => 'list-item-temp', 'listType' => $listTagInfo, 'level' => $level, 'startNo' => $startNo, 'wikitext' => $innerString));
						} else {
							array_push($result, array('name' => 'list-item-temp', 'listType' => $listTagInfo, 'level' => $level, 'wikitext' => $innerString));
						}
						$i = $eol;
						$lineStart = $eol + 1;
						break;
					}
				}
				if($quit){
					$i = $lineStart;
					break;
				}
				if(!$matched){
					if($isList === null){
						$isList = false;
					} elseif($isList) {
						$i = $lineStart;
						break;
					}
					array_push($result, array('name' => 'indent-temp', 'level' => $level, 'wikitext' => $innerString));
					$i = $eol;
					$char = "\n";
				}
			}
			if($char === '\n')
				$lineStart = $i + 1;
		}
		if(count($result) === 0){
			$result = null;
			$setpos = null;
		}else{
			$result = $this->finishTokens($result);
			$setpos($i - 1);
		}
		return $result;
	}
	function parseOptionBracket($optionContent){
		$colspan = 0;
		$rowspan = 0;
		$colOption = array();
		$tableOptions = array();
		$rowOptions = array();
		$matched = false;
		if(preg_match('/^-[0-9]+$/', $optionContent, $colspan_str)) {
			$colspan += intval($colspan_str);
			$matched = true;
		} elseif(preg_match('/^\|([0-9]+)$/', $optionContent, $rowspan_mid) || preg_match('/^\^\|([0-9]+)$/', $optionContent, $rowspan_top) || preg_match('/^v\|([0-9]+)$/', $optionContent, $rowspan_bot)){
			$matched = true;
			if($rowspan_mid){
				$rowspan += intval($rowspan_mid[1]);
				$colOptions['vertical-align'] = 'middle';
			} elseif($rowspan_top){
				$rowspan += intval($rowspan_top[1]);
				$colOptions['vertical-align'] = 'top';
			} elseif($rowspan_bot){
				$rowspan += intval($rowspan_top[1]);
				$colOptions['vertical-align'] = 'top';
			}
		} elseif(str_starts_with($optionContent, 'table ')) {
			$tableOptionContent = substr($optionContent, 6);
			$tableOptionPatterns = array(
				'align' => '/^align=(left|center|right)$/',
				'background-color' => '/^bgcolor=(#[a-zA-Z0-9]{3,6}|[a-zA-Z]+)$/',
				'border-color' => '/^bordercolor=(#[a-zA-Z0-9]{3,6}|[a-zA-Z]+)$/',
				'width' => '/^width=([0-9]+(?:in|pt|pc|mm|cm|px))$/'
			);
			foreach($tableOptionPatterns as $optionName){
				if(preg_match($tableOptionPatterns[$optionName], $tableOptionContent, $t_top)){
					$tableOptions[$optionName] = $t_top[1];
					$matched = true;
				}
			}
		} else {
			$textAlignCellOptions = array(
				'left' => '/^\($/',
				'middle' => '/^:$/',
				'right' => '/^\)$/'
			);
			$paramlessCellOptions = array(
				'background-color' => '/^bgcolor=(#[0-9a-zA-Z]{3,6}|[a-zA-Z0-9]+)$/',
				'row-background-color' => '/^rowbgcolor=(#[0-9a-zA-Z]{3,6}|[a-zA-Z0-9]+)$/',
				'width' => '/^width=([0-9]+(?:in|pt|pc|mm|cm|px|%))$/',
				'height' => '/^height=([0-9]+(?:in|pt|pc|mm|cm|px|%))$/'
			);
			foreach ($textAlignCellOptions as $i) {
				if(preg_match($textAlignCellOptions[$i], $optionContent)){
					$colOptions['text-align'] = $optionContent;
					$matched = true;
				}
				else
					foreach($paramlessCellOptions as $optionName){
						if(!preg_match($paramlessCellOptions[$optionName], $optionContent, $_prg_rop))
							continue;
						if(str_starts_with($optionName, 'row-'))
							$rowOptions[substr($optionName, 4)] = $_prg_rop[1];
						else
							$colOptions[$optionName] = $_prg_rop[1];
						$matched = true;
					}
			}
		}
		return array('colspan_add' => $colspan, 'rowspan_add' => $rowspan, 'colOptions_set' => $colOptions, 'rowOptions_set' => $rowOptions, 'tableOptions_set' => $tableOptions, 'matched' => $matched);
	}
	function tableParser($wikitext, $pos, $setpos) {
		$caption = null;
		if(!str_starts_with(substr($wikitext, $pos), '||')){
			$caption = iconv_substr($wikitext, $pos + 1, iconv_strpos($wikitext, '|', $pos + 2));
			$pos = iconv_strpos($wikitext, '|', $pos + 1) + 1;
			// echo $caption;
		} else {
			$pos += 2;
		}
		$cols = explode('||', iconv_substr($wikitext, $pos));
		$rowno = 0;
		$hasTableContent = false;
		$colspan = 0;
		$rowspan = 0;
		$optionPattern = '/<(.+?)>/';
		// echo $cols;
		$table = array(array());
		$tableOptions = array();
		if(count($cols) < 2)
			return null;
		foreach ($cols as $col) {
			$curColOptions = array();
			$rowOption = array();
			if(str_starts_with($col, '\n') && iconv_strlen($col) > 1){
				break;
			}
			if($col == '\n'){
				$table[++$rowno] = array();
				continue;
			}
			if(iconv_strlen($col) == 0){
				$colspan++;
				continue;
			}
			
			if(str_starts_with($col, ' ') && !str_ends_with($col, ' '))
				$curColOptions['text-align'] = 'left';
			elseif(!str_starts_with($col, ' ') && str_ends_with($col, ' '))
				$curColOptions['text-align'] = 'right';
			elseif(str_starts_with($col, ' ') && str_ends_with($col, ' '))
				$curColOptions['text-align'] = 'middle';
			
			while (preg_match($optionPattern, $col, $match)){
				if(iconv_strpos($col, $match) != 0)
					break;
				$optionContent = $match[1];
				$pO = $this->parseOptionBracket($optionContent);
				$colOptions_set = $pO['colOptions_set'];
				$tableOptions_set = $pO['tableOptions_set'];
				$colspan_add = $pO['colspan_add'];
				$rowspan_add = $pO['rowspan_add'];
				$rowOptions_set = $pO['rowOptions_set'];
				$matched = $pO['matched'];
				$curColOptions = array_merge($curColOptions, $colOptions_set);
				$tableOptions = array_merge($tableOptions, $colOptions_set);
				$rowOptions_set = array_merge($rowOption, $rowOptions_set);
				
				$colspan += $colspan_add;
				$rowspan += $rowspan_add;
				
				if($tableOptions['border-color']){
					$tableOptions['border'] = '2px solid '.$tableOptions['border-color'];
					unset($tableOptions['border-color']);
				}
				
				$col = iconv_substr($col, iconv_strlen($match[0]));
			}
			$colObj = array(
				'options' => $curColOptions,
				'colspan' => $colspan,
				'rowspan' => $rowspan,
				'rowOption' => $rowOption,
				'wikitext'=> $col
			);
			$colspan = 0;
			$rowspan = 0;
			array_push($table[$rowno], $colObj);
			$hasTableContent = true;
		}
		$rowOptions = array();
		$_tbcount = count($table);
		foreach($table as $_te){
			$rowOption = array();
			foreach($_te as $_tee){
				$rowOption = array_merge($rowOption, $_te['rowOption']);
			}
			array_push($rowOption, $rowOption);
		}
			       
		$result = array(['name' => 'table-start', 'options' => $tableOptions]);
		$rowCount = count($table);
		for($j=0; $j<$rowCount; $j++){
			array_push($result, array('name' => 'table-row-start', 'options' => $rowOptions[$j]));
			$_tbkcount = count($table[$j]);
			for($k=0; $k<$_tbkcount; $k++){
				array_push($result, ['name' => 'table-col-start', 'options' => $table[$j][$k]['colspan'], 'rowspan' =>  $table[$j][$k]['rowspan']]);
				array_push($result, ['name' => 'wikitext', 'text' => $table[$j][$k]['wikitext'], 'treatAsLine' => true]);
				array_push($result, ['name' => 'table-col-end']);
			}
			array_push($result, array('name' => 'table-row-end'));
		}
		array_push($result, array('name' => 'table-end'));
		if($hasTableContent){
			$setpos(iconv_strlen($pos + implode('||', array_slice($cols, 0, $i))) + 1);
			return $result;
		}else{
			return null;
		}
	}
	function closureProcessor($text, $type){
		return array(array('name' => 'closure-start'), array('name' => 'wikitext', 'parseFormat' => true, 'text' => $text), array('name' => 'closure-end'));
	}
	function linkProcessor($text, $type){
		$href = explode('|', $text);
		if(preg_match('/^https?:\/\//', $text)){
			if(count($href) > 1){
				return array(array(
					'name' => 'link-start',
					'external' => true,
					'target' => $href[0]
				), array(
					'name' => 'wikitext',
					'parseFormat' => true,
					'text' => $href[1]
				));
			}else{
				return array(array(
					'name' => 'link-start',
					'external' => true,
					'target' => $href[0]
				), array(
					'name' => 'plain',
					'parseFormat' => true,
					'text' => $href[0]
				));
			}
		} elseif(preg_match('/^분류:(.+)$/', $href[0], $category)){
			if(!$this->defaultOptions['included'])
				return array(array(
					'name' => 'add-category',
					'blur' => str_ends_with('#blur'),
					'categoryName' => $category
				));
		} elseif (preg_match('/^파일:(.+)$/', $href[0], $h_file)){
			$fileOpts = array();
			$haveOpts = false;
			if(count($href) > 1){
				$pattern = '/[&?]?(^[=]+)=([^\&]+)/g';
				$match = null;
				while(preg_match($pattern, $href[1], $match)){
					if(($match[1] === 'width' || $match[1] === 'height') && preg_match('/^[0-9]$/', $match[2])) {
						$match[2] = $match[2].'px';
					}
					$fileOpts[$match[1]] = $match[2];
					$haveOpts = true;
				}
			}
			if($haveOpts) {
				return array(array(
					'name' => 'image',
					'taget' => $h_file[1]
				));
			} else {
				return array(array(
					'name' => 'image',
					'taget' => $h_file[1],
					'options' => $fileOpts
				));
			}
		} else {
			if(str_starts_with($href[0], ' ') || str_starts_with($href[0], ':')){
				$href[0] = iconv_substr($href[0], 1);
			}
			if(count($href) > 1){
				return array(array(
					'name' => 'link-start',
					'internal' => true,
					'target' => $href[0]
				), array(
					'name' => 'wikitext',
					'parseFormat' => true,
					'text' => $href[1]
				), array(
					'name' => 'link-end'
				));
			} else {
				return array(array(
					'name' => 'link-start',
					'internal' => true,
					'target' => $href[0]
				), array(
					'name' => 'plain',
					'text' => $href[0]
				), array(
					'name' => 'link-end'
				));
			}
		}
	}
	function macroProcessor($text, $type) {
		$defaultResult = array(['name' => 'plain', 'text' => '['.$text.']']);
		if(str_starts_with($text, '*') && preg_match('/^\*([^ ]*) (.+)$/', $text, $matches)) {
			(iconv_strlen($matches[1]) === 0)? $supText = null: $supText = $matches[1];
			return array([
				'name' => 'footnote-start',
				'supText' => $supText
			], [
				'name' => 'wikitext',
				'treatAsBlock' => true,
				'text' => $matches[2]
			], [
				'name' => 'footnote-end'
			]);
		} else {
			if(preg_match('/^[^\(]+$/', $text)){
				if(!array_search($text, $this->defaultOptions['macroNames']))
					return $defaultResult;
				else
					return array(['name' => 'macro', 'macroName' => $text]);
			} elseif(preg_match('/^([^\(]+)\((.*)\)/', $text, $matches)){
				if(!array_search($matches[1], $this->defaultOptions['macroNames']))
					return $defaultResult;
				$macroName = $matches[1];
				$optionSplitted = explode(',', $matches[2]);
				$options = [];
				if(iconv_strlen($matches[2]) != 0) {
					foreach($optionSplitted as $i){
						if(!array_search('=', $i)){
							array_push($options, $i);
						} else {
							$_m_i_v = explode('=', $i);
							array_push($options, ['name' => $_m_i_v[0], 'value' => $_m_i_v[1]]);
						}
					}
				}
				return array(['name' => 'macro', 'macroName' => $macroName, 'options' => $options]);
			}
			return $defaultResult;
		}
	}
	function renderProcessor($text, $type){
		if(preg_match('/^#!html/i', $text)){
			return array([
				'name' => 'unsafe-plain',
				'text' => iconv_substr($text, 6)
			]);
		} elseif (preg_match('/^#!folding/i', $text) && iconv_strpos($text, '\n') >= 10) {
			return array([
				'name' => 'folding-start',
				'summary' => iconv_substr($text, 10, iconv_strpos($text, '\n'))
			], [
				'name' => 'wikitext',
				'treatAsBlock' => true,
				'text' => iconv_substr($text, iconv_strpos($text, '\n') + 1)
			], [
				'name' => 'folding-end'
			]);
		} elseif (preg_match('/^#!syntax/i', $text) && iconv_strpos($text, '\n') >= 9) {
			return array([
				'name' => 'syntax-highlighting',
				'header' => iconv_substr($text, 9, iconv_strpos($text, '\n')),
				'body' => iconv_substr($text, iconv_strpos($text, '\n') + 1)
			]);
		} elseif (preg_match('/^#!wiki/i', $text)) {
			if(iconv_strpos($text, '\n') >= 7) {
				$params = iconv_substr($text, 7, iconv_strpos($text, '\n'));
				if(str_starts_with($params, "style=\"") && preg_match('/" +$/', $params, $match)){
					return array([
						'name' => 'wiki-box-start',
						'style' => substr($params, 7, strlen($params) - strlen($match[0]))
					], [
						'name' => 'wikitext',
						'treatAsBlock' => true,
						'text' => iconv_substr($text, iconv_strpos($text, '\n') + 1)
					], [
						'name' => 'wiki-box-end'
					]);
				} else {
					return array([
						'name' => 'wiki-box-start',
					], [
						'name' => 'wikitext',
						'treatAsBlock' => true,
						'text' => iconv_substr($text, iconv_strpos($text, '\n') + 1)
					], [
						'name' => 'wiki-box-end'
					]);
				}
			}
		} elseif (preg_match('/^#([A-Fa-f0-9]{3,6}) (.*)$/', $text, $matches)){
			if(iconv_strlen($matches[1]) === 0 && iconv_strlen($matches[2]) === 0)
				return array([
					'name' => 'plain',
					'text' => $text
				]);
			return array([
				'name' => 'font-color-start',
				'color' => $matches[1]
			], [
				'name' => 'wikitext',
				'parseFormat' => true,
				'text' => $matches[2]
			], [
				'name' => 'font-color-end'
			]);
		} elseif (preg_match('/^\+([1-5]) (.*)$/', $text, $matches)){
			return array([
				'name' => 'font-size-start',
				'color' => $matches[1]
			], [
				'name' => 'wikitext',
				'parseFormat' => true,
				'text' => $matches[2]
			], [
				'name' => 'font-size-end'
			]);
		}
		return array([
			'name' => 'monoscape-font-start',
			'pre' => true
		], [
			'name' => 'plain',
			'text' => iconv_substr($text, 1)
		], [
			'name' => 'monoscape-font-end'
		]);
	}
	function textProcessor($text, $type){
		$styles = ["'''" => 'strong',"''" => 'italic','--' => 'strike','~~' => 'strike','__' => 'underline','^^' => 'superscript',',,' => 'subscript'];
		switch($type){
			case "'''":
			case "''":
			case '--':
			case '~~':
			case '__':
			case '^^':
			case ',,':
				return array(['name' => $styles[$type].'-start'],['name' => 'wikitext', 'parseFormat' => true, 'text' => $text], ['name' => $styles[$type].'-end']);
				break;
			case '{{{':
				if(str_starts_with($text, '#!html')){
					return array(['name' => 'unsafe-plain', 'text' => iconv_substr($text, 6)]);
				} elseif (preg_match('/^#([A-Fa-f0-9]{3,6}) (.*)$/', $text, $matches)){
					if(iconv_strlen($matches[1]) === 0 && iconv_iconv_strlen($matches[2]) === 0)
						return array(['name' => 'plain','text' => $text]);
					return array(['name' => 'font-color-start','color' => $matches[1]], ['name' => 'wikitext','parseFormat' => true,'text' => $matches[2]], ['name' => 'font-color-end']);
				} elseif (preg_match('/^\+([1-5]) (.*)$/', $text, $matches)) {
					return array(['name' => 'font-size-start','color' => $matches[1]], ['name' => 'wikitext','parseFormat' => true,'text' => $matches[2]], ['name' => 'font-size-end']); 
				}
				return array(['name' => 'monoscape-font-start','pre' => true], ['name' => 'plain','text' => iconv_substr($text, 1)], ['name' => 'monoscape-font-end']);
			case '@':
				if(!$this->defaultOptions['included'])
					break;
				if(array_search($text, array_keys($this->defaultOptions['includeParameters'])))
					return array(['name' => 'wikitext', 'parseFormat' => true, 'text' => $this->defaultOptions['includeParameters'][$text]]);
				else
					return null;
		}
		return array(['name' => 'plain', 'text' => $type.$text.$type]);
	}
    function parse() { return $this->doParse(); }
    function setIncluded() { $this->defaultOptions['included'] = true;}
	function setIncludeParameters($paramsObj) { $this->defaultOptions['includeParameters'] = $paramsObj; }
	//function setRenderer($r = null, $o = null){
	//	if($r !==null) $this->rendererClass = $r;
	//	if($o !==null) $this->rendererOptions = $o;
	//	return;
	//}
    public function toHTML($tokens, $_options=[]){
	// Token to HTML
		global $DO;
		$this->_options = $_options;
        $this->HTMLOutPut = [];
        $this->options = array_merge($DO, $_options);
        $this->headings = [];
        $this->footnotes = [];
        $this->categories = [];
        $this->links = [];
        $this->isHeadingNow = false;
        $this->isFootnoteNow = false;
        $this->lastHeadingLevel = 0;
        $this->hLevels = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
        $this->footnoteCount = 0;
        $this->headingCount = 0;
        $this->lastListOrdered = [];
        $this->wasPreMono = false;
	$this->processTokens($tokens);
	return $this->HTMLOutPut;
    }
    public function processTokens($tset){
	    foreach($tset as $v) {
                if($v['name'] !== "wikitext")
                    $this->processToken($v);
                elseif($v['parseFormat'] || $v['treatAsBlock'])
                    $this->processTokens($this->blockParser($v['text']));
                elseif($v['treatAsLine'])
                    $this->processTokens($this->lineParser($v['text']));
        }
    }
	
	public function processToken($i) {
        switch($i['name']){
            case 'blockquote-start':
                $this->appendResult('<blockquote>');
                break;
            case 'blockquote-end':
                $this->appendResult('</blockquote>');
                break;
            case 'list-start':
                array_push($this->lastListOrdered, $i['listType']['ordered']);
                ($i['listType']['ordered'])? $li_t = 'ol':$li_t = 'ul';
                ($i['listType']['type'])? $li_c = ' class="'.$i['listType']['type'].'"':$li_c = '';
                $this->appendResult('<'.$li_t.$li_c.'>');
                break;
            case 'list-end':
                (array_pop($this->lastListOrdered))? $li_t = 'ol':$li_t = 'ul';
                $this->appendResult("</$li_t>");
                break;
            case 'indent-start':
                $this->appendResult('<div class="wiki-indent">');
                break;
            case 'indent-end':
                $this->appendResult('</div>');
                break;
            case 'list-item-start':
                ($i['startNo'])? $li_t = '<li value='.htmlspecialchars($i['startNo']).'>':$li_t = '<li>';
                $this->appendResult("<$li_t>");
                break;
            case 'list-item-end':
                $this->appendResult('</li>');
                break;
            case 'table-start':
                ($i['options'])? $t_st = ' style="'.$this->ObjToCssString($i['options']).'"':$t_st = '';
                $this->appendResult("<table$t_st>");
                break;
            case 'table-col-start':
                ($i['options'])? $t_st = ' style="'.$this->ObjToCssString($i['options']).'"':$t_st = '';
                ($i['colspan'] > 0)? $t_cs = ' colspan='.$i['colspan']: $t_cs='';
                ($i['rowspan'] > 0)? $t_rs = ' rowspan='.$i['rowspan']: $t_rs='';
                $this->appendResult("<td$t_st$t_cs$t_rs>");
                break;
            case 'table-col-end':
                $this->appendResult('</td>');
                break;
            case 'table-row-end':
                $this->appendResult('</tr>');
                break;
            case 'table-row-start':
                ($i['options'])? $t_st = ' style=\"'.$this->ObjToCssString($i['options']).'\"':$t_st = '';
                $this->appendResult("<tr$t_st>");
                break;
            case 'table-end':
                $this->appendResult('</table>');
                break;
            case 'closure-start':
                $this->appendResult('</div class="wiki-closure">');
                break;
            case 'closure-end':
                $this->appendResult('</div>');
                break;
            case 'link-start':
                ($i['internal'])? $l_h = $this->resolveurl($i['target'], 'wiki'):$l_h = $i['target'];
                ($i['internal'])? $l_c = 'wiki-internal-link':$l_c = '';
                ($i['external'])? $l_c = 'wiki-external-link':$l_c = '';
                $this->appendResult('<a href="'.$l_h.'" class="'.$l_c.'">');
                break;
            case 'link-end':
                $this->appendResult('</a>');
                break;
            case 'plain':
                $this->appendResult(htmlspecialchars($i['text']));
                break;
            case 'new-line':
                $this->appendResult('<br>');
                break;
            case 'add-category':
                array_push($this->categories, $i['categoryName']);
                break;
            case 'image':
                ($i['fileOpts'])? $i_st=' style='.$this->ObjToCssString($i['fileOpts']):$i_st='';
                $this->appendResult('<img src="'.$this->resolveUrl($i['target'], 'internal-image').'"'.$i_st.'>');
                break;
            case 'footnote-start':
                $fnNo = ++$this->footnoteCount;
                ($i['supText'])? $f_t = $i['supText']:$f_t = $fnNo;
                $this->appendResult('<a href="#fn-'.$fnNo.'" id="rfn-'.$fnNo.'" class="footnote"><sup class="footnote-sup">['.$f_t.'] ');
                array_push($this->footnotes, array('sup' => $i['supText'], 'value' => ''));
                $this->isFootnoteNow = true;
                break;
            case 'macro':
                switch($i['macroName']){
                    case 'clearfix':
                        $this->appendResult('<div style="clear:both">');
                        break;
                    case 'br':
                        $this->appendResult('<br>');
                        break;
                    case 'dday':
                        if(count($i['options']) === 0 || is_string($i['options'][0]))
                            $this->appendResult('<span class="wikitext-syntax-error">dday 매크로: 매개변수가 없거나 익명 매개변수가 아닙니다.</span>');
                        else {
                            $mo = date('Y-m-d', strtotime($i['options'][0]));
                            if(!checkdate($mo)){
                                $this->appendResult('<span class="wikitext-syntax-error">dday 매크로: 날짜 형식이 잘못되었습니다..</span>');
                            } else {
                                $days = -date_diff(date('Y-m-d'), $mo)->days;
                                $this->appendResult(strval($days));
                            }
                        }
                        break;
                    case 'age':
                        if(count($i['options']) === 0 || is_string($i['options'][0]))
                            $this->appendResult('<span class="wikitext-syntax-error">age 매크로: 매개변수가 없거나 익명 매개변수가 아닙니다.</span>');
                        else {
                            $mo = date('Y-m-d', strtotime($i['options'][0]));
                            $koreanWay = (strlen($i['options']) > 1 && !array_search('korean', array_slice($i['options'], 1)));
                            if(!checkdate($mo)){
                                $this->appendResult('<span class="wikitext-syntax-error">age 매크로: 날짜 형식이 잘못되었습니다.</span>');
                            } else {
                                $years = ($koreanWay)? date('Y') - explode('-',$mo)[0] + 1:date('Y') - explode('-',$mo)[0];
                                $this->appendResult(strval($years));
                            }
                        }
                        break;
                    case 'date':
                        $this->appendResult(strval(date('Y-m-d')));
                        break;
                    case 'youtube':
                        if(count($i['options']) === 0){
                            $this->appendResult()('<span class="wikitext-syntax-error">오류 : youtube 동영상 ID가 제공되지 않았습니다!</span>');
                        } elseif (count($i['options']) >= 1) {
                            if(is_string($i['options'][0]))
                                if(count($i['options']) == 1)
                                    $this->appendResult('<iframe src="//www.youtube.com/embed/'.$i['options'][0].'"></iframe>');
                                else
                                    $this->appendResult('<iframe src="//www.youtube.com/embed/'.$i['options'][0].'" style="'.$this->ObjToCssString(array_slice($i['options'], 1)).'"></iframe>');
                            else
                                $this->appendResult('<span class="wikitext-syntax-error">오류 : youtube 동영상 ID는 첫번째 인자로 제공되어야 합니다!</span>');
                        }
                        break;
                    case '각주':
                    case 'footnote':
                        $footnoteContent = '';
                        foreach($this->footnotes as $footnote){
                            ($footnote['sup'])? $fn_s = $footnote['sup']: $fn_s = $j+1;
                            $footnoteContent .= '<a href="#rfn-'.$j+1 .'" id="fn-'. $j+1 .'" class="footnote"><sup class="footnote-sup">['.$fn_s.']</sup></a> '.$footnote['value'].'<br>';
                        }
                        $this->footnotes = array();
                        $this->appendResult($footnoteContent);
                        break;
                    case '목차':
                    case 'tableofcontents':
                    case 'include':
                        ($i['options'])? $arc = ['name' => 'macro', 'macroName' => $i['macroName'], 'options' => $i['options']]:$arc = ['name' => 'macro', 'macroName' => $i['macroName']];
                        $this->appendResult($arc);
                        break;
                    default:
                        $this->appendResult('[Unsupported Macro]');
                        break;
                }
                break;
            case 'monoscape-font-start':
                $this->wasPreMono = $i['pre'];
                $p_re_ = ($wasPreMono)? '<pre>':'';
                $this->appendResult($p_re_.'<code>');
                break;
            case 'monoscape-font-end':
                $p_re_nd = ($this->wasPreMono)? '</pre>':'';
                $this->appendResult('</code>'.$p_re_nd);
                break;
            case 'strong-start':
                $this->appendResult('<strong>');
                break;
            case 'italic-start':
                $this->appendResult('<em>');
                break;
            case 'strike-start':
                $this->appendResult('<del>');
                break;
            case 'underline-start':
                $this->appendResult('<u>');
                break;
            case 'superscript-start':
                $this->appendResult('<sup>');
                break;
            case 'subscript-start':
                $this->appendResult('<sub>');
                break;
            case 'strong-end':
                $this->appendResult('</strong>');
                break;
            case 'italic-end':
                $this->appendResult('</em>');
                break;
            case 'strike-end':
                $this->appendResult('</del>');
                break;
            case 'underline-end':
                $this->appendResult('</u>');
                break;
            case 'superscript-end':
                $this->appendResult('</sup>');
                break;
            case 'subscript-end':
                $this->appendResult('</sub>');
                break;
            case 'unsafe-plain':
                $this->appendResult($i['text']);
                break;
            case 'font-color-start':
                $this->appendResult('</span style="color: '.$i['color'].'">');
                break;
            case 'font-size-start':
                $this->appendResult('</span class="wiki-size-'.$i['level'].'-level">');
                break;
            case 'font-color-end':
            case 'font-size-end':
                $this->appendResult('</span>');
                break;
            case 'external-image':
                $ei_s = ($i['styleOptions'])? 'style="'.$this->ObjToCssString($i['styleOptions']).'"':'';
                $this->appendResult('<img src="'.$i['target'].'" '.$ei_s.'/>');
                break;
            case 'comment':
                break;
            case 'heading-start':
                if($this->lastHeadingLevel > $i['level'])
                    $this->hLevels[$i['level']] = 0;
                $this->lastHeadingLevel = $i['level'];
                $this->hLevels[$i['level']]++;
                $this->appendResult('<h'.$i['level'].' id="heading-'.++$this->headingCount.'"><a href="#wiki-toc">'.$this->hLevels[$i['level']].'. </a>');
                $this->isHeadingNow = true;
                array_push($this->headings, array('level' => $i['level'], 'value' => ''));
                break;
            case 'heading-end':
                $this->isHeadingNow = false;
                $this->appendResult('<h'.$this->lastHeadingLevel.'>');
                break;
            case 'horizonal-line':
                $this->appendResult('<hr>');
                break;
            case 'paragraph-start':
                $this->appendResult('<p>');
                break;
            case 'paragraph-end':
                $this->appendResult('</p>');
                break;
            case 'wiki-box-start':
                $w_s = ($i['style'])? $i['style'] : '';
                $this->appendResult('<div '.$w_s.'>');
                break;
            case 'wiki-box-end':
                $this->appendResult('</div>');
                break;
            case 'folding-start':
                $this->appendResult('<details><summary>'.htmlspecialchars($i['summary']).'</summary>');
                break;
            case 'folding-end':
                $this->appendResult('</details>');
                break;
        }
    }
	private function resolvUrl($target, $type){
        switch($type){
            case 'wiki':
                return "/wiki/$target";
                break;
            case 'internal-image':
                return "/file/$target";
                break;
        }
    }
    private function appendResult($value) {
        if($this->isFootnoteNow) {
            $this->footnotes[count($this->footnotes) - 1] .= (is_string($value))? $value: strval($value);
            return;
        } elseif($this->isHeadingNow) {
            $this->headings[count($this->headings) - 1] .= (is_string($value))? $value: strval($value);
        }
        if(count($this->HTMLOutPut) === 0)
            array_push($this->HTMLOutPut, $value);
        else {
            if(is_string($value) && is_string($this->HTMLOutPut[count($this->HTMLOutPut)-1])){
                $this->HTMLOutPut[count($this->HTMLOutPut)-1] .= $value;
            } else {
                array_push($this->HTMLOutPut, $value);
            }
        }
    }
    private function ObjToCssString($obj) {
        $styleString = '';
        foreach($obj as $name){
            $styleString .= $name.':'.$obj[$name].'; ';
        }
        return iconv_substr($styleString, 0, iconv_strlen($styleString) - 1);
    }
}
