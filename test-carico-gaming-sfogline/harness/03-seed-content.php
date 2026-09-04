<?php
global $wpdb;
$t0=microtime(true);
$uids = $wpdb->get_col("SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'sfoglina%'");
echo "users: ".count($uids)."\n";
$wpdb->query('START TRANSACTION');
// 1) sfida corrente + passate
$sfide=array();
for($k=0;$k<6;$k++){
  $id = wp_insert_post(array('post_type'=>'gs_sfida','post_status'=>'publish','post_title'=>'Sfida '.($k+1),'post_content'=>'Tema della sfida '.($k+1)));
  update_post_meta($id,'gs_data_inizio', date('Y-m-d', strtotime("-".(30*(6-$k))." days")));
  update_post_meta($id,'gs_data_fine', date('Y-m-d', strtotime($k==5 ? "+10 days" : "-".(30*(5-$k))." days")));
  $sfide[]=$id;
}
$cur = end($sfide);
// 2) sfoglie (elaborati) 2 per utente sulle ultime sfide
$n=0;
foreach($uids as $i=>$u){
  foreach(array_slice($sfide,-2) as $sf){
    $pid = wp_insert_post(array('post_type'=>'gs_sfoglia','post_status'=>'publish','post_author'=>$u,'post_title'=>'Sfoglia di '.$u.' #'.$sf,'post_content'=>'Descrizione'));
    update_post_meta($pid,'gs_sfida_id',$sf);
    update_post_meta($pid,'gs_voti', rand(0,50));
    $n++;
  }
  if($i%100===0){$wpdb->query('COMMIT');$wpdb->query('START TRANSACTION');}
}
echo "sfoglie: $n  ".round(microtime(true)-$t0,1)."s\n";
// 3) messaggi broadcast (gs_dest=0) e personali
for($k=0;$k<20;$k++){
  $mid=wp_insert_post(array('post_type'=>'gs_messaggio','post_status'=>'publish','post_title'=>'Comunicazione '.$k,'post_content'=>'Testo comunicazione '.$k));
  update_post_meta($mid,'gs_dest',0);
  // simula 500 lettori che l'hanno letto -> array serializzato grande
  update_post_meta($mid,'gs_letto_da', array_slice($uids,0,500));
}
foreach(array_slice($uids,0,200) as $u){
  $mid=wp_insert_post(array('post_type'=>'gs_messaggio','post_status'=>'publish','post_title'=>'Messaggio per '.$u,'post_content'=>'Testo'));
  update_post_meta($mid,'gs_dest',$u);
}
// 4) conversazioni
foreach(array_slice($uids,0,200) as $u){
  $cid=wp_insert_post(array('post_type'=>'gs_conversazione','post_status'=>'publish','post_author'=>$u,'post_title'=>'Conversazione di '.$u));
  update_post_meta($cid,'gs_conv_sfoglina',$u);
  update_post_meta($cid,'gs_conv_esperto',$uids[0]);
  update_post_meta($cid,'gs_conv_stato','attiva');
  $msgs=array(); for($j=0;$j<10;$j++){ $msgs[]=array('uid'=>$u,'testo'=>'Messaggio '.$j,'ts'=>time()-$j*3600,'letti'=>array($u)); }
  update_post_meta($cid,'gs_conv_msgs',$msgs);
}
$wpdb->query('COMMIT');
// 5) altri contenuti di catalogo
$wpdb->query('START TRANSACTION');
$catalogo = array('gs_ricetta'=>200,'gs_lezione'=>80,'gs_faq'=>60,'gs_novita'=>50,'gs_piatto'=>40,'gs_voce'=>150,'gs_diario'=>300,'gs_consiglio'=>120,'gs_testimonianza'=>60,'gs_lettura'=>40,'gs_corso_cal'=>30,'gs_premio'=>25,'gs_misura'=>80,'gs_giuria'=>20,'gs_sondaggio'=>15,'gs_barometro'=>12,'gs_ingrediente'=>12,'gs_aiuto'=>30,'gs_artigiano'=>25,'gs_scuola'=>25,'gs_percorso_lezioni'=>10,'gs_cassaforte'=>30,'gs_errore_didattico'=>40,'gs_locandina'=>15,'gs_domanda'=>50,'gs_augurio'=>60,'gs_msg_interno'=>80,'gs_conversazione'=>0);
foreach($catalogo as $cpt=>$cnt){
  for($i=0;$i<$cnt;$i++){
    $pid=wp_insert_post(array('post_type'=>$cpt,'post_status'=>'publish','post_author'=>$uids[array_rand($uids)],'post_title'=>ucfirst(str_replace('gs_','',$cpt)).' '.$i,'post_content'=>str_repeat('Contenuto di prova. ',20)));
    update_post_meta($pid,'gs_stato','pubblicato');
    if($cpt==='gs_corso_cal'){ update_post_meta($pid,'gs_data', date('Y-m-d', strtotime('+'.$i.' days'))); update_post_meta($pid,'gs_posti',12); update_post_meta($pid,'gs_prezzo',80); }
  }
}
$wpdb->query('COMMIT');
echo "catalogo fatto ".round(microtime(true)-$t0,1)."s\n";
$tot = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
$tm  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta}");
$um  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta}");
echo "posts=$tot postmeta=$tm usermeta=$um\n";
