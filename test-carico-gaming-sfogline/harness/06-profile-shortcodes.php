<?php
/**
 * Profila ogni shortcode del plugin: query, tempo, memoria, warning PHP.
 * Uso: php wpcli.php profile.php
 */
global $wpdb, $shortcode_tags;
if (!defined('SAVEQUERIES')) define('SAVEQUERIES', true);
$wpdb->queries = array();

$errors = array();
set_error_handler(function($no,$str,$file,$line) use (&$errors){
    $errors[] = array('type'=>$no,'msg'=>$str,'file'=>basename($file),'line'=>$line);
    return true;
});

$uids = $wpdb->get_col("SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'sfoglina%' LIMIT 5");
$uid = $uids[0];
wp_set_current_user($uid);

$tags = array_values(array_filter(array_keys($shortcode_tags), function($t){ return strpos($t,'gs_')===0; }));
sort($tags);
$rows = array();
foreach ($tags as $tag) {
    // reset caches to simulate a cold request
    wp_cache_flush();
    $wpdb->queries = array();
    $errors = array();
    $t = microtime(true); $m0 = memory_get_usage();
    $out = '';
    $fatal = '';
    try { $out = do_shortcode('['.$tag.']'); }
    catch (\Throwable $e) { $fatal = get_class($e).': '.$e->getMessage().' @'.basename($e->getFile()).':'.$e->getLine(); }
    $dt = (microtime(true)-$t)*1000;
    $q  = count($wpdb->queries);
    $rows[] = array('tag'=>$tag,'ms'=>round($dt,1),'q'=>$q,'bytes'=>strlen($out),'mem'=>round((memory_get_usage()-$m0)/1024),'err'=>count($errors),'fatal'=>$fatal,'errs'=>array_slice($errors,0,4));
}
restore_error_handler();
usort($rows, function($a,$b){ return $b['q'] <=> $a['q']; });
printf("%-34s %8s %7s %9s %6s %s\n",'SHORTCODE','QUERIES','ms','out bytes','warn','note');
foreach($rows as $r){
    printf("%-34s %8d %7.1f %9d %6d %s\n",$r['tag'],$r['q'],$r['ms'],$r['bytes'],$r['err'],$r['fatal']);
}
echo "\n=== WARNING/NOTICE DETTAGLIO ===\n";
foreach($rows as $r){ foreach($r['errs'] as $e){ printf("  [%s] %s (%s:%d)\n", $r['tag'], $e['msg'], $e['file'], $e['line']); } }
$tq = array_sum(array_column($rows,'q')); $tt = array_sum(array_column($rows,'ms'));
echo "\nTOTALE: ".count($rows)." shortcode, $tq query, ".round($tt)." ms\n";
