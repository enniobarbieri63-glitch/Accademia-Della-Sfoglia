<?php
$dir=$argv[1];
$files=[]; foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $f) if($f->isFile()&&$f->getExtension()==='php') $files[]=$f->getPathname();
$funcs=[];
foreach($files as $file){
    $src=file_get_contents($file); $toks=token_get_all($src); $n=count($toks);
    for($i=0;$i<$n;$i++){ if(is_array($toks[$i])&&$toks[$i][0]===T_FUNCTION){
        $j=$i+1; while($j<$n&&is_array($toks[$j])&&$toks[$j][0]===T_WHITESPACE)$j++;
        if(!is_array($toks[$j])||$toks[$j][0]!==T_STRING)continue;
        $name=$toks[$j][1];$line=$toks[$j][2];$k=$j;$d=0;$s=false;$b='';
        for(;$k<$n;$k++){$t=$toks[$k];$x=is_array($t)?$t[1]:$t; if($x==='{'){$d++;$s=true;}elseif($x==='}'){$d--;if($s&&$d===0)break;} if($s)$b.=$x;}
        $funcs[strtolower($name)]=['file'=>$file,'line'=>$line,'body'=>$b];
    }}
}
$reg=[];
foreach($files as $file){$src=file_get_contents($file);
    if(preg_match_all("/add_action\(\s*'(wp_ajax(_nopriv)?_[a-zA-Z0-9_]+)'\s*,\s*'([a-zA-Z0-9_]+)'/",$src,$m,PREG_SET_ORDER))
        foreach($m as $mm) $reg[$mm[3]][]=$mm[1];
}
$hits=[];
foreach($reg as $fn=>$hooks){
    $k=strtolower($fn); if(!isset($funcs[$k]))continue; $b=$funcs[$k]['body'];
    // takes an id from request
    if(!preg_match('/\$_(POST|REQUEST|GET)\s*\[\s*[\'"](id|pid|post|post_id|pren|uid|user|user_id|msg|conv|c|m)[\'"]/',$b)) continue;
    // performs a mutation
    if(!preg_match('/wp_trash_post|wp_delete_post|wp_update_post|update_post_meta|delete_post_meta|wp_untrash_post|update_user_meta|delete_user_meta|wp_delete_user|wp_insert_comment|wp_update_user/',$b)) continue;
    // has admin gate?
    if(preg_match('/gs_can_manage\s*\(|current_user_can\s*\(/',$b)) continue;
    // has ownership check?
    $own = preg_match('/post_author|get_current_user_id\s*\(\s*\)\s*!==|!==\s*\(?\s*int\s*\)?\s*\$?\w*author|gs_\w*owner|gs_\w*proprietari|->post_author/',$b);
    $hits[]=[implode(',',$hooks),str_replace($dir.'/','',$funcs[$k]['file']).':'.$funcs[$k]['line'],$own?'has-ownership-check':'NO-OWNERSHIP-CHECK'];
}
echo "candidates: ".count($hits)."\n";
foreach($hits as $h) printf("  %-45s %-40s %s\n",$h[0],$h[1],$h[2]);
