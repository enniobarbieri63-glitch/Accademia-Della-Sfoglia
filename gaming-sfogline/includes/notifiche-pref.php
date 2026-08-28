<?php
/**
 * notifiche-pref.php — Preferenze di notifica per sfoglina.
 *
 * Dal pannello di controllo, il titolare decide per ogni sfoglina e per ogni
 * categoria di email del progetto se mandarla come EMAIL vera, come
 * MESSAGGIO INTERNO (compare nella sua sezione "Messaggi dalla segreteria",
 * senza toccare la sua posta ufficiale), entrambe le cose, o nessuna delle
 * due. Serve per non "stressare" la posta ufficiale di chi non la vuole
 * piena di notifiche automatiche, senza perdere l'avviso: il contenuto
 * arriva comunque dentro al sito.
 *
 * Le preferenze valgono anche per chi gestisce il portale (titolare e
 * collaboratori): hanno una loro tabella separata nello stesso pannello,
 * con lo stesso comportamento delle sfogline. Fino al 2026-07-29 erano
 * bloccati sulla sola email senza possibilità di scelta — il titolare ha
 * chiesto di poter controllare anche le proprie notifiche, sia via email
 * sia come messaggio interno del progetto.
 *
 * Ogni categoria ha due canali indipendenti, non un blocco unico:
 *  - 'email'   => manda la vera email (default: sì, per non cambiare nulla
 *                 a chi non ha mai toccato le preferenze)
 *  - 'interno' => manda anche/solo come messaggio interno (default: no)
 * Le due caselle sono indipendenti: si può avere solo email (comportamento
 * di sempre), solo interno (mail bloccata, redirect automatico), entrambe
 * (es. scadenza abbonamento: la manda comunque via mail E la lascia anche
 * come messaggio interno), o nessuna delle due (notifica soppressa del
 * tutto per quella categoria).
 *
 * gs_mail_progetto() è il punto unico da cui devono passare le email
 * indirizzate a un utente preciso (non le liste per indirizzi fissi come
 * "avvisa l'amministrazione"): sostituisce le vecchie chiamate dirette a
 * wp_mail() sparse nei vari moduli.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Le categorie di email del progetto, con l'etichetta per il pannello. */
function gs_notifiche_categorie() {
	return array(
		'livelli'    => 'Livelli, Badge e Punti',
		'calendario' => 'Calendario Corsi e Lezioni Video',
		'digest'     => 'Digest settimanale',
		'messaggi'   => 'Messaggi e Conversazioni',
		'iscrizione' => 'Iscrizione e Account',
		'promemoria' => 'Promemoria giornaliero',
	);
}

/**
 * Preferenze di una sfoglina, con i default per le categorie mai impostate:
 * solo email, come è sempre stato prima di questa funzione.
 */
function gs_notifiche_pref_utente( $uid ) {
	$salvate = get_user_meta( $uid, 'gs_notifiche_pref', true );
	$salvate = is_array( $salvate ) ? $salvate : array();
	$out = array();
	foreach ( gs_notifiche_categorie() as $cat => $label ) {
		$out[ $cat ] = array(
			'email'   => isset( $salvate[ $cat ]['email'] ) ? (bool) $salvate[ $cat ]['email'] : true,
			'interno' => isset( $salvate[ $cat ]['interno'] ) ? (bool) $salvate[ $cat ]['interno'] : false,
		);
	}
	return $out;
}

/** Salva le preferenze di una sfoglina. $prefs = array( categoria => array('email'=>bool,'interno'=>bool) ). */
function gs_notifiche_pref_salva( $uid, $prefs ) {
	$pulite = array();
	foreach ( gs_notifiche_categorie() as $cat => $label ) {
		$pulite[ $cat ] = array(
			'email'   => ! empty( $prefs[ $cat ]['email'] ),
			'interno' => ! empty( $prefs[ $cat ]['interno'] ),
		);
	}
	update_user_meta( $uid, 'gs_notifiche_pref', $pulite );
}

/**
 * Punto unico di invio delle email indirizzate a un utente preciso.
 * Rispetta le preferenze salvate per QUALSIASI destinatario, sfoglina o no —
 * anche titolare, collaboratori ed esperti passano da qui e vengono spenti
 * se hanno tolto la spunta (verificato il 23/08/2026 col caso concreto di un
 * collaboratore con tutte le caselle spente: nessuna email è partita).
 *
 * @param int    $uid       destinataria/o.
 * @param string $categoria una delle chiavi di gs_notifiche_categorie().
 * @param string $oggetto   oggetto dell'email.
 * @param string $corpo     testo dell'email (e, se previsto, del messaggio interno).
 * @return bool true se è stato mandato almeno un canale (email e/o interno).
 */
function gs_mail_progetto( $uid, $categoria, $oggetto, $corpo ) {
	$uid = (int) $uid;
	$u   = $uid ? get_userdata( $uid ) : null;
	if ( ! $u ) {
		return false;
	}

	$pref   = gs_notifiche_pref_utente( $uid );
	$canali = isset( $pref[ $categoria ] ) ? $pref[ $categoria ] : array( 'email' => true, 'interno' => false );
	$fatto  = false;

	if ( ! empty( $canali['email'] ) && $u->user_email ) {
		wp_mail( $u->user_email, $oggetto, $corpo );
		$fatto = true;
	}
	if ( ! empty( $canali['interno'] ) && function_exists( 'gs_invia_messaggio' ) ) {
		gs_invia_messaggio( $uid, $oggetto, $corpo );
		$fatto = true;
	}
	return $fatto;
}

// -----------------------------------------------------------------------------
// AJAX — salvataggio in blocco dal pannello (una sola richiesta per tutte
// le sfogline della pagina, come per la visibilità delle sezioni).
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_notifiche_pref_salva', 'gs_ajax_notifiche_pref_salva' );
function gs_ajax_notifiche_pref_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$dati = isset( $_POST['pref'] ) ? wp_unslash( $_POST['pref'] ) : array();
	if ( ! is_array( $dati ) ) {
		wp_send_json_error( array( 'message' => 'Dati non validi.' ) );
	}
	$categorie = gs_notifiche_categorie();
	foreach ( $dati as $uid => $per_categoria ) {
		$uid = (int) $uid;
		if ( ! $uid || ! is_array( $per_categoria ) ) {
			continue;
		}
		$prefs = array();
		foreach ( $categorie as $cat => $label ) {
			$prefs[ $cat ] = array(
				'email'   => ! empty( $per_categoria[ $cat ]['email'] ),
				'interno' => ! empty( $per_categoria[ $cat ]['interno'] ),
			);
		}
		gs_notifiche_pref_salva( $uid, $prefs );
	}
	wp_send_json_success( array( 'message' => 'Preferenze salvate.' ) );
}

// -----------------------------------------------------------------------------
// Pannello — una riga per sfoglina, due caselle (Email / Interno) per ognuna
// delle categorie.
// -----------------------------------------------------------------------------
/** Tabella (righe utente × categorie) riusata sia per le sfogline sia per chi gestisce il portale. */
function gs_notifiche_tabella_html( $utenti, $table_id ) {
	$categorie = gs_notifiche_categorie();
	$out  = '<div style="overflow-x:auto">';
	$out .= '<table class="gs-table gs-paginate" data-per-page="15" id="' . esc_attr( $table_id ) . '">';
	$out .= '<thead><tr><th>Nome</th>';
	foreach ( $categorie as $cat => $label ) { $out .= '<th>' . esc_html( $label ) . '</th>'; }
	$out .= '</tr></thead><tbody>';
	foreach ( $utenti as $u ) {
		$pref = gs_notifiche_pref_utente( $u->ID );
		$out .= '<tr data-uid="' . (int) $u->ID . '" data-nome="' . esc_attr( strtolower( $u->display_name ) ) . '">';
		$out .= '<td><strong>' . esc_html( $u->display_name ) . '</strong></td>';
		foreach ( $categorie as $cat => $label ) {
			$c = $pref[ $cat ];
			$out .= '<td style="white-space:nowrap">';
			$out .= '<label style="display:block;font-size:12px"><input type="checkbox" class="gs-notif-email" data-cat="' . esc_attr( $cat ) . '" ' . checked( $c['email'], true, false ) . '> Email</label>';
			$out .= '<label style="display:block;font-size:12px"><input type="checkbox" class="gs-notif-interno" data-cat="' . esc_attr( $cat ) . '" ' . checked( $c['interno'], true, false ) . '> Interno</label>';
			$out .= '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</tbody></table></div>';
	return $out;
}

function gs_pannello_notifiche_pref() {
	if ( ! gs_can_manage() ) {
		return;
	}
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	$gestori  = array();
	foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
		if ( user_can( $u->ID, 'manage_options' ) || user_can( $u->ID, 'gs_manage_gaming' ) ) { $gestori[] = $u; }
	}

	echo gs_box_open( '📧 Notifiche per sfoglina', '', 'gs-box-notifiche' );
	echo gs_sezione_aiuto( 'Per ogni persona e per ogni tipo di email, scegli i canali: <strong>Email</strong> manda la vera email; <strong>Interno</strong> la fa comparire come messaggio dalla segreteria nella sua area, senza toccare la sua posta. Puoi tenerle entrambe attive (es. per le scadenze dell\'iscrizione, che restano anche via mail), solo una, o nessuna. Chi non ha mai avuto preferenze impostate riceve tutto via email, come sempre. Vale anche per te e per i collaboratori: trovi la vostra tabella più sotto.' );

	if ( ! $sfogline && ! $gestori ) {
		echo '<p class="gs-hint">Nessuna sfoglina registrata.</p>';
		echo gs_box_close();
		return;
	}

	echo '<form class="gs-form gs-form-notifiche" onsubmit="return false">';

	if ( $sfogline ) {
		echo '<input type="text" class="gs-cerca-input" data-target="#gs-notifiche-tabella" placeholder="🔍 Cerca una sfoglina…" style="width:100%;max-width:360px;margin-bottom:12px">';
		echo gs_notifiche_tabella_html( $sfogline, 'gs-notifiche-tabella' );
	} else {
		echo '<p class="gs-hint">Nessuna sfoglina registrata.</p>';
	}

	if ( $gestori ) {
		echo '<h4 style="margin-top:18px">Le tue notifiche (titolare e collaboratori)</h4>';
		echo gs_notifiche_tabella_html( $gestori, 'gs-notifiche-tabella-gestori' );
	}

	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-notif-salva">Salva preferenze</button> <span class="gs-notif-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo gs_box_close();
}
