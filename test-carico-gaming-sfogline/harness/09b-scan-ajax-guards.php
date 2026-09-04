<?php
$dir=$argv[1];
$files=[]; foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $f) if($f->isFile()&&$f->getExtension()==='php') $files[]=$f->getPathname();
$funcs=[];
foreach($files as $file){
    $src=file_get_contents($file); $toks=token_get_all($src); $n=count($toks);
    for($i=0;$i<$n;$i++){
        if(is_array($toks[$i])&&$toks[$i][0]===T_FUNCTION){
            $j=$i+1; while($j<$n&&is_array($toks[$j])&&$toks[$j][0]===T_WHITESPACE)$j++;
            if(!is_array($toks[$j])||$toks[$j][0]!==T_STRING)continue;
            $name=$toks[$j][1]; $line=$toks[$j][2]; $k=$j; $depth=0;$started=false;$body='';
            for(;$k<$n;$k++){ $t=$toks[$k]; $txt=is_array($t)?$t[1]:$t;
                if($txt==='{'){$depth++;$started=true;} elseif($txt==='}'){$depth--; if($started&&$depth===0)break;}
                if($started)$body.=$txt; }
            $funcs[strtolower($name)]=['file'=>$file,'line'=>$line,'body'=>$body];
        }
    }
}
function analyze($fn,$funcs,$depth=0,&$seen=[]){
    $fn=strtolower($fn);
    if(isset($seen[$fn])||$depth>3||!isset($funcs[$fn])) return ['nonce'=>false,'cap'=>false];
    $seen[$fn]=1; $b=$funcs[$fn]['body'];
    $nonce=(bool)preg_match('/check_ajax_referer|wp_verify_nonce|check_admin_referer/',$b);
    $cap=(bool)preg_match('/current_user_can\s*\(|gs_can_manage\s*\(|is_super_admin|gs_is_admin_vero|gs_solo_admin/',$b);
    // recurse into called gs_ functions
    if(preg_match_all('/\b(gs_[a-z0-9_]+)\s*\(/i',$b,$m)){
        foreach(array_unique($m[1]) as $callee){
            if(strtolower($callee)===$fn) continue;
            $r=analyze($callee,$funcs,$depth+1,$seen);
            $nonce=$nonce||$r['nonce']; $cap=$cap||$r['cap'];
        }
    }
    return ['nonce'=>$nonce,'cap'=>$cap];
}
$reg=[];
foreach($files as $file){ $src=file_get_contents($file);
    if(preg_match_all("/add_action\(\s*'(wp_ajax(_nopriv)?_[a-zA-Z0-9_]+)'\s*,\s*'([a-zA-Z0-9_]+)'/",$src,$m,PREG_SET_ORDER))
        foreach($m as $mm) $reg[]=['hook'=>$mm[1],'nopriv'=>(bool)$mm[2],'fn'=>$mm[3]];
}
$bad=[];
foreach($reg as $r){ $seen=[]; $a=analyze($r['fn'],$funcs,0,$seen);
    if(!$a['nonce']||!$a['cap']) $bad[]=$r+$a+['loc'=>isset($funcs[strtolower($r['fn'])])?str_replace($dir.'/','',$funcs[strtolower($r['fn'])]['file']).':'.$funcs[strtolower($r['fn'])]['line']:'?'];
}
echo "registrations: ".count($reg)."  suspicious: ".count($bad)."\n\n";
echo "--- NO NONCE (anywhere in call chain) ---\n";
foreach($bad as $x) if(!$x['nonce']) printf("  %-42s cap=%d  %s\n",$x['hook'],$x['cap'],$x['loc']);
echo "\n--- NONCE ok but NO capability check (any logged-in user can call) ---\n";
foreach($bad as $x) if($x['nonce']&&!$x['cap']) printf("  %-42s %s\n",$x['hook'],$x['loc']);
