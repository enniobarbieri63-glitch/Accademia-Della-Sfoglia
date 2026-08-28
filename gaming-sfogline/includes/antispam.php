<?php
/**
 * antispam.php — Protezioni silenziose per tutti i moduli pubblici:
 * honeypot, trappola del tempo, limite invii per IP. Nessun servizio esterno.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stampa i campi nascosti anti-spam da includere in ogni modulo pubblico.
 */
function gs_antispam_fields() {
	// Honeypot: campo invisibile che un bot tende a compilare.
	echo '<div style="position:absolute;left:-9999px;" aria-hidden="true">';
	echo '<label>Lascia vuoto questo campo</label>';
	echo '<input type="text" name="gs_hp" value="" tabindex="-1" autocomplete="off">';
	echo '</div>';

	// Trappola del tempo: timestamp di caricamento della pagina.
	echo '<input type="hidden" name="gs_ts" value="' . esc_attr( time() ) . '">';
}

/**
 * Verifica un invio. Restituisce true se legittimo, WP_Error se sospetto.
 *
 * @param array $data Dati grezzi dell'invio (tipicamente $_POST).
 * @param string $context Etichetta del modulo (per il rate-limit per contesto).
 * @return true|WP_Error
 */
function gs_antispam_check( $data, $context = 'generic' ) {
	$settings = gs_settings()['antispam'];

	// 1) Honeypot compilato → bot.
	if ( ! empty( $data['gs_hp'] ) ) {
		return new WP_Error( 'gs_spam', 'Invio bloccato (honeypot).' );
	}

	// 2) Trappola del tempo → invio troppo rapido.
	$ts = isset( $data['gs_ts'] ) ? (int) $data['gs_ts'] : 0;
	$min = (int) $settings['min_seconds'];
	if ( $ts <= 0 || ( time() - $ts ) < $min ) {
		return new WP_Error( 'gs_spam', 'Invio troppo rapido, riprova.' );
	}

	// 3) Limite per IP nell'ultima ora.
	if ( ! gs_antispam_rate_ok( $context, (int) $settings['max_per_hour'] ) ) {
		return new WP_Error( 'gs_spam', 'Troppi invii dallo stesso indirizzo. Riprova tra un\'ora.' );
	}

	return true;
}

/**
 * Rate-limit per IP+contesto usando i transient (finestra oraria).
 */
function gs_antispam_rate_ok( $context, $max_per_hour ) {
	$ip  = gs_get_ip();
	$key = 'gs_rl_' . md5( $context . '|' . $ip );
	$count = (int) get_transient( $key );

	if ( $count >= $max_per_hour ) {
		return false;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return true;
}
