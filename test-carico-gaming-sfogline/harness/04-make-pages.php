<?php
global $shortcode_tags;
$tags = array_values(array_filter(array_keys($shortcode_tags), function($t){ return strpos($t,'gs_')===0; }));
sort($tags);
$map = array();
foreach ($tags as $t) {
    $slug = 'test-'.str_replace('_','-',$t);
    $ex = get_page_by_path($slug);
    $pid = $ex ? $ex->ID : wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Test '.$t,'post_name'=>$slug,'post_content'=>'['.$t.']'));
    $map[$t] = array('id'=>$pid,'url'=>get_permalink($pid));
}
// collega le opzioni gs_page_* alle pagine più ovvie
$link = array('gs_page_dashboard'=>'gs_dashboard','gs_page_login'=>'gs_login','gs_page_messaggi'=>'gs_messaggi','gs_page_calendario'=>'gs_calendario','gs_page_sfogline'=>'gs_sfogline','gs_page_classifica'=>'gs_classifica','gs_page_faq'=>'gs_faq','gs_page_novita'=>'gs_novita','gs_page_iscrizione'=>'gs_registrazione','gs_page_lezioni'=>'gs_lezioni','gs_page_ricettario'=>'gs_ricettario','gs_page_pannello'=>'gs_pannello');
foreach($link as $opt=>$tag){ if(isset($map[$tag])) update_option($opt, $map[$tag]['id']); }
file_put_contents(GS_TEST_OUT.'/pages.json', json_encode($map, JSON_PRETTY_PRINT));
echo "pagine create: ".count($map)."\n";
echo "home: ".home_url('/')."\n";
