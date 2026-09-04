<?php
$dir=$argv[1];
$files=[]; foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $f) if($f->isFile()&&$f->getExtension()==='php') $files[]=$f->getPathname();
function grab($src,$fnName){
    $out=[]; $off=0;
    while(($p=strpos($src,$fnName.'(',$off))!==false){
        // ensure it's a call not a definition
        $pre = substr($src, max(0,$p-12), 12);
        if (preg_match('/function\s+$/',$pre)) { $off=$p+1; continue; }
        $i=$p+strlen($fnName); $depth=0; $j=$i;
        for(;$j<strlen($src);$j++){ if($src[$j]==='(')$depth++; elseif($src[$j]===')'){$depth--; if($depth===0){break;} } }
        $args=substr($src,$i,$j-$i+1);
        $line=substr_count(substr($src,0,$p),"\n")+1;
        $out[]=[$line,$args]; $off=$j;
    }
    return $out;
}
$report=[];
foreach($files as $file){
    $src=file_get_contents($file); $rel=str_replace($dir.'/','',$file);
    foreach(['get_users','get_posts','new WP_Query','new WP_User_Query'] as $fn){
        foreach(grab($src,$fn) as [$line,$args]){
            $a=preg_replace('/\s+/',' ',$args);
            $unbounded=false; $why='';
            if(preg_match("/'(number|posts_per_page|numberposts)'\s*=>\s*-1/",$a)){$unbounded=true;$why='explicit -1';}
            elseif(in_array($fn,['get_users','new WP_User_Query']) && !preg_match("/'number'\s*=>/",$a)){$unbounded=true;$why='no number => all users';}
            elseif($fn==='new WP_Query' && !preg_match("/'posts_per_page'\s*=>|'nopaging'/",$a)){$unbounded=false;}
            if($unbounded) $report[]=[$rel,$line,$fn,$why,substr($a,0,150)];
        }
    }
}
echo "UNBOUNDED QUERIES: ".count($report)."\n";
foreach($report as $r) printf("%-34s:%-5d %-16s %-22s %s\n", $r[0],$r[1],$r[2],$r[3],$r[4]);
