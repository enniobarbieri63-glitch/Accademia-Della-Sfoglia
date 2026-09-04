<?php
global $wpdb;
if(!defined('SAVEQUERIES')) define('SAVEQUERIES',true);
wp_set_current_user(1);
$fns = array_filter(get_defined_functions()['user'], function($f){ return strpos($f,'gs_pannello_')===0; });
sort($fns);
$rows=array();
foreach($fns as $f){
  $r = new ReflectionFunction($f);
  if ($r->getNumberOfRequiredParameters() > 0) continue;
  wp_cache_flush(); $wpdb->queries=array(); $t=microtime(true);
  ob_start(); try { $out = $f(); } catch(\Throwable $e){ $out=''; } $buf=ob_get_clean();
  $html = strlen((string)$buf) + strlen(is_string($out)?$out:'');
  $rows[]=array($f, (microtime(true)-$t)*1000, count($wpdb->queries), $html);
}
usort($rows,function($a,$b){return $b[2]<=>$a[2];});
printf("%-42s %8s %8s %10s\n",'SEZIONE PANNELLO','ms','query','KB html');
foreach(array_slice($rows,0,20) as $r) printf("%-42s %8.0f %8d %10.1f\n",$r[0],$r[1],$r[2],$r[3]/1024);
printf("\nTOTALE %d sezioni: %.0f ms, %d query, %.1f MB html\n", count($rows), array_sum(array_column($rows,1)), array_sum(array_column($rows,2)), array_sum(array_column($rows,3))/1048576);
