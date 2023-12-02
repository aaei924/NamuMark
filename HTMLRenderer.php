<?php
/**
 * HTML Render for Tokenized Namumark
 * @author PRASEOD-
 */
class HTMLRenderer
{
    public $title, $toc = [], $fn_overview = [], $fn = [], $fnset = [];

    public function __construct()
    {}

    public function render($token)
    {
        $result = '';
        foreach($token as $t){
            switch($t['type']){
                
                case 'void':
                    break;
                case 'math':
                    $result .= '<span class="katex">'.$t['text'].'</span>';
                    break;
                case 'syntax':
                    $result .= '<pre><code class="syntax" data-language="'.$t['language'].'">'.$t['text'].'</code></pre>';
                    break;
                case 'wikitext':
                    $result .= '<div '.$t['attr'].'>'.$t['text'].'</div>';
                    break;
                case 'inline-html':
                    $result .= '<html>'.$t['text'].'</html>';
                    break;
                case 'wiki-size':
                    $result .= '<span class="wiki-size size-'.$t['size'].'">'.$t['text'].'</span>';
                    break;
                case 'rawtext':
                    $result .= '<pre>'.$t['text'].'</pre>';
                    break;
                case 'escape':
                    $result .= '<code class="wiki-escape">'.$t['text'].'</code>';
                    break;
                case 'plaintext':
                    $result .= $t['text'];
                    break;
                case 'text-start':
                    $result .= '<'.$t['effect'].'>';
                    break;
                case 'text-end':
                    $result .= '</'.$t['effect'].'>';
                    break;
                case 'colortext':
                    $result .= '<span style="color: '.$t['color'].'">'.$t['text'].'</span>';
                    break;
                case 'anchor':
                    $result .= '<a id="'.$t['text'].'"></a>';
                    break;
                case 'video':
                    $result .= '<iframe width="'.$t['width'].'" height="'.$t['height'].'" src="'.$t['src'].'" frameborder="0" allowfullscreen loading="lazy"></iframe>';
                    break;
                case 'blockquote':
                    $result .= '<blockquote class="wiki-quote">'.$t['html'].'</blockquote>';
                    break;
                case 'footnote':
                    $result .= '<a class="wiki-fn-content" href="#fn-'.$t['name'].'"><span class="target" id="rfn-'.$t['id'].'"></span>['.$t['name'].']</a>';
                    break;
                case 'heading':
                    $result .= '</div>
                            <h'.$t['level'].' class="wiki-heading" '.$t['folded'].'>'
                            .  '<a id="s-'.$t['section'].'" href="#toc">'.$t['section'].'.</a><span id="'.strip_tags($t['id']).'">'.$t['text'].'</span>'
                            .  '<span class="wiki-edit-section"><a href="/edit/'.$this->title.'?section='.$t['section'].'" rel="nofollow">[편집]</a></span>
                            </span>
                        </h'.$t['level'].'><div id="content-s-'.$t['section'].'" class="wiki-heading-content" fold="'.$t['folded'].'">';
                    break;
                case 'folding':
                    $result .= '<dl class="wiki-folding"><dt>'.$t['text']
                            .'</dt><dd>'.$t['html'].'</dd></dl>';
                    break;
                case 'footnotes':
                    $result .= $this->printFootnote($t['from'], $t['until']);
                    break;
                case 'categories':
                    $result .= $this->printCategories();
                    break;
                case 'list':
                    $result .= $this->printList($t);
                    break;
                case 'table':
                    $result .= $this->printTable($t);
                    break;
                case 'toc':
                    $result .= $this->printToc();
                    break;
                case 'ruby':
                    if($t['color'])
                        $rb = '<span style="color:'.$t['color'].'">'.$t['ruby'].'</span>';
                    else
                        $rb = $t['ruby'];
                    
                    $result .= '<ruby>'.$t['text'].'<rp>(</rp><rt>'.$rb.'</rt><rp>)</rp></ruby>';
                    break;
                case 'link':
                    if($t['linktype'] == 'file'){
                        /*if(in_array('not-exist', $t['class']))
                            $result .= '<a class="wiki-link-internal not-exist" href="'.$t['href'].'">'.$t['href'].'</a>';
                        else{
                            $result = '<a class="wiki-link-auto" title="'.'" href="'.'" rel="nofollow">'
                            .'<span class="wiki-image-align-'.$t['imgalign'].'"'.$t['attralign'].'>'
                            .'<span class="wiki-image-wrapper"'.$t['attrwrapper'].'>'
                            .'<img class="wiki-image-space"'.$t['wraptag']
                            .' src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTE3OCIgaGVpZ2h0PSIxMTc4IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjwvc3ZnPg==">'
                            .'<img class="wiki-image" '.$t['wraptag'].'src="'.$t['href'].'" alt="'.$t['fnwithouttext'].'" loading="lazy"></span></span></a>';
                        }*/
                    }else{
                        if(count($t['class']) > 0)
                            $classStr = implode(' ', $t['class']);
                        else
                            $classStr = '';
    
                        $result .= '<a class="'.$classStr.'" href="'.$t['href'].'" title="'.$t['target'].'">'.$t['text'].'</a>';
                    }
                    break;
            }
        }

        return $result;
    }

    private function printList($listdata)
    {
        if(strpos($listdata['listtype'], 'ul') !== false)
            $tag = 'ul';
        elseif(strpos($listdata['listtype'], 'list') !== false)
            $tag = 'ol';
        elseif($listdata['listtype'] == 'wiki-indent')
            $tag = 'div';
        
        $html = '<'.$tag.' '.($tag == 'ol' ? 'start="'.$listdata['start'].'" ': ' ').'class="'.$listdata['listtype'].'">';
        
        foreach($listdata['lists'] as $li){
            
            if($tag == 'div'){
                $html .= $li['html'];
            }else{
                $html .= '<li><div>'.$li['html'].'</div></li>';
            }
        }

        $html .= '</'.$tag.'>';
        return $html;
    }

    private function printTable($token)
    {
        $tdAttrStr = $trInnerStr = $tdAttrStr = $trAttrStr = $tableInnerStr = $tableAttrStr = '';
        $tableAttr = $token['style'];

        if(!empty($token['colstyle'])){
            foreach($token['colstyle'] as $cci => $cs){
                foreach($cs as $ccr => $ccs){
                    $rcnt = count($token['rows']);
                    for($j=$ccr; $j<$rcnt; $j++){
                        if(isset($token['rows'][$j]['cols'][$cci])){
                            if(!isset($token['rows'][$j]['cols'][$cci]['style']))
                                $token['rows'][$j]['cols'][$cci]['style'] = $ccs;
                            else
                                $token['rows'][$j]['cols'][$cci]['style'] = array_merge($ccs, $token['rows'][$j]['cols'][$cci]['style']);
                        }
                    }
                }
            }
        }

        foreach ($token['rows'] as $r){
            if(!is_array($r))
                return false;

            if(empty($r)){
                $tableInnerStr .= '<tr></tr>';
                continue;
            }else{
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
            if($attkeys[$k] == 'tablebordercolor')
                $tableAttrStr .= 'border: 2px solid '.$tableAttr[$attkeys[$k]].';';
            else
                $tableAttrStr .= $attkeys[$k].':'.$tableAttr[$attkeys[$k]].'; ';
        }
        if(strlen($tableAttrStr) > 0)
            $tableAttrStr = ' style="'.$tableAttrStr.'"';
        if(empty($token['class']))
            $tbClassStr = '';
        else
            $tbClassStr = implode(' ', $token['class']);
        
        return '<div class="wiki-table-wrap '.$tbClassStr.'"><table class="wiki-table" '.$tableAttrStr.'>'.$tableInnerStr.'</table></div>';
    }

    private function printCategories()
    {
    }

    private function printFootnote(int $from, int $until)
    {
        $result = '<div>';
        $fns = $this->fnset[0];
        array_shift($this->fnset);

        if($from == $until)
            return '<div class="wiki-macro-footnote"></div>';
        
        for ($i=$from+1; $i<=$until; ++$i) {
            $fn_name = $this->fn_overview[$i - 1];

            if(isset($fns[$fn_name]) && $fn_name !== $i && $i === $fns[$fn_name][0]){
                // 이름이 지정된 각주
                $footnote = $this->fn[$i];
                $min = min($fns[$fn_name]);
                $result .= '<span class="footnote-list">'
                        .  '<span id="fn-'.$fn_name.'"></span>';
                
                if(count($fns[$fn_name]) > 1){
                    $result .= '['.$fn_name.']';
                    foreach($fns[$fn_name] as $k => $f){
                        $result .= '<a href="#rfn-'.$f .'">';
                        $result .= '<sup>'.$min.'.'.$k+1 .'</sup></a> ';
                    }
                }else{
                    $result .= '<a href="#rfn-'.$i .'">['.$fn_name.']</a>';
                }
            }elseif($fn_name !== $i){
                continue;
            }else{
                //이름이 지정되지 않은 각주
                $footnote = $this->fn[$i];
                $result .= '<span class="footnote-list">'
                        .  '<span id="fn-'.$fn_name.'"></span>'
                        .  '<a href="#rfn-'.$i .'">['.$fn_name.']</a>';
            }
            
            $result .= $footnote.'</span>';
        }
        $result .= '</div>';
        return '<div class="wiki-macro-footnote">'.$result.'</div>';
    }

    // HTML 목차 출력
    private function printToc(&$arr = null, $level = -1, $path = '') {
        if($level == -1) {
            $bak = $this->toc;
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
}