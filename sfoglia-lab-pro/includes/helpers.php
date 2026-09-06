<?php
/**
 * helpers.php — utilità condivise da tutto il plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function slp_clean( $valore ) {
	return sanitize_text_field( wp_unslash( (string) $valore ) );
}

function slp_can_manage() {
	return current_user_can( 'slp_gestisci_corsi' ) || current_user_can( 'manage_options' );
}

function slp_euro( $centesimi ) {
	return number_format( $centesimi / 100, 2, ',', '.' ) . ' €';
}
