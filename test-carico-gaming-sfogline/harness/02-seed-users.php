<?php
global $wpdb;
$t0 = microtime(true);
$N = (int) (getenv('SEED_USERS') ?: 1000);
wp_defer_term_counting(true); wp_defer_comment_counting(true);
wp_suspend_cache_invalidation(false);
$ym = date('Y-m');
$existing = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
echo "existing users: $existing\n";
$wpdb->query('START TRANSACTION');
$ids = array();
for ($i=1; $i<=$N; $i++) {
    $login = sprintf('sfoglina%04d', $i);
    $uid = wp_insert_user(array(
        'user_login' => $login,
        'user_pass'  => 'Passw0rd!'.$i,
        'user_email' => $login.'@example.test',
        'display_name' => 'Sfoglina '.$i,
        'first_name' => 'Nome'.$i, 'last_name' => 'Cognome'.$i,
        'role' => 'subscriber',
    ));
    if (is_wp_error($uid)) { echo "ERR user $i: ".$uid->get_error_message()."\n"; continue; }
    $ids[] = $uid;
    update_user_meta($uid, 'gs_status', 'approvata');
    update_user_meta($uid, 'gs_points', rand(0, 5000));
    update_user_meta($uid, 'gs_points_mese_'.$ym, rand(0, 500));
    update_user_meta($uid, 'gs_points_anno_'.date('Y'), rand(0, 2000));
    update_user_meta($uid, 'gs_streak', rand(0, 60));
    update_user_meta($uid, 'gs_team', 'squadra'.(($i%8)+1));
    update_user_meta($uid, 'gs_birthdate', sprintf('19%02d-%02d-%02d', rand(50,99), rand(1,12), rand(1,28)));
    update_user_meta($uid, 'gs_genere', ($i%2)?'f':'m');
    update_user_meta($uid, 'gs_email_verificata', 1);
    update_user_meta($uid, 'gs_data_approvazione', date('Y-m-d'));
    if ($i % 200 === 0) { $wpdb->query('COMMIT'); $wpdb->query('START TRANSACTION'); echo "  users $i  ".round(microtime(true)-$t0,1)."s\n"; }
}
$wpdb->query('COMMIT');
echo "USERS DONE ".count($ids)." in ".round(microtime(true)-$t0,1)."s\n";
file_put_contents(GS_TEST_OUT.'/seeded-users.json', json_encode($ids));
