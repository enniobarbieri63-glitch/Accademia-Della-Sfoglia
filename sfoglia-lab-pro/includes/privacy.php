<?php
/**
 * privacy.php — quello che serve quando una persona iscritta chiede "che
 * dati avete su di me" oppure "cancellatemi".
 *
 * WordPress ha già gli strumenti pronti in Bacheca → Strumenti →
 * Esporta/Cancella dati personali: chiedono l'email, mandano una richiesta
 * di conferma e poi girano il lavoro ai plugin installati. Qui il plugin
 * si presenta a quel meccanismo, così le iscrizioni ai corsi non restano
 * fuori dal conto.
 *
 * Una scelta da sapere: la cancellazione NON butta via le iscrizioni che
 * hanno un pagamento registrato. Cancella nome, email e telefono (e
 * l'iscrizione resta come riga contabile anonima), perché una ricevuta di
 * un incasso va conservata per gli obblighi fiscali, che sono una base
 * giuridica autonoma rispetto al consenso. Le iscrizioni senza nemmeno un
 * euro versato vengono invece eliminate del tutto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Le iscrizioni collegate a un indirizzo email.
 */
function slp_iscrizioni_per_email( $email ) {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return array();
	}

	return get_posts( array(
		'post_type'      => 'slp_iscrizione',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array( 'key' => 'slp_email', 'value' => $email ),
		),
	) );
}

// -----------------------------------------------------------------------------
// Esportazione
// -----------------------------------------------------------------------------

add_filter( 'wp_privacy_personal_data_exporters', 'slp_registra_esportatore' );
function slp_registra_esportatore( $esportatori ) {
	$esportatori['sfoglia-lab-pro'] = array(
		'exporter_friendly_name' => 'Iscrizioni ai corsi (Sfoglia Lab — Pro)',
		'callback'               => 'slp_esporta_dati_personali',
	);
	return $esportatori;
}

function slp_esporta_dati_personali( $email, $pagina = 1 ) {
	$esportati = array();

	foreach ( slp_iscrizioni_per_email( $email ) as $iscrizione ) {
		$sessione  = get_post( $iscrizione->post_parent );
		$corso     = $sessione ? slp_get_corso( get_post_meta( $sessione->ID, 'slp_corso_codice', true ) ) : null;
		$pagamenti = get_post_meta( $iscrizione->ID, 'slp_pagamenti', true );
		$versato   = slp_totale_pagato( is_array( $pagamenti ) ? $pagamenti : array() );

		$esportati[] = array(
			'group_id'    => 'slp_iscrizioni',
			'group_label' => 'Iscrizioni ai corsi',
			'item_id'     => 'slp-iscrizione-' . $iscrizione->ID,
			'data'        => array(
				array( 'name' => 'Corso', 'value' => $corso ? $corso['codice'] . ' — ' . $corso['titolo'] : '(corso non più a catalogo)' ),
				array( 'name' => 'Data del corso', 'value' => $sessione ? get_post_meta( $sessione->ID, 'slp_data', true ) : '' ),
				array( 'name' => 'Nome', 'value' => get_post_meta( $iscrizione->ID, 'slp_nome', true ) ),
				array( 'name' => 'Email', 'value' => get_post_meta( $iscrizione->ID, 'slp_email', true ) ),
				array( 'name' => 'Telefono', 'value' => get_post_meta( $iscrizione->ID, 'slp_telefono', true ) ),
				array( 'name' => 'Stato', 'value' => str_replace( '_', ' ', (string) get_post_meta( $iscrizione->ID, 'slp_stato', true ) ) ),
				array( 'name' => 'Totale versato', 'value' => slp_euro( $versato ) ),
				array( 'name' => 'Data dell\'iscrizione', 'value' => $iscrizione->post_date ),
				array( 'name' => 'Consenso raccolto il', 'value' => get_post_meta( $iscrizione->ID, 'slp_consenso_data', true ) ),
			),
		);
	}

	return array( 'data' => $esportati, 'done' => true );
}

// -----------------------------------------------------------------------------
// Cancellazione
// -----------------------------------------------------------------------------

add_filter( 'wp_privacy_personal_data_erasers', 'slp_registra_cancellatore' );
function slp_registra_cancellatore( $cancellatori ) {
	$cancellatori['sfoglia-lab-pro'] = array(
		'eraser_friendly_name' => 'Iscrizioni ai corsi (Sfoglia Lab — Pro)',
		'callback'             => 'slp_cancella_dati_personali',
	);
	return $cancellatori;
}

function slp_cancella_dati_personali( $email, $pagina = 1 ) {
	$rimossi    = false;
	$conservati = false;
	$messaggi   = array();

	foreach ( slp_iscrizioni_per_email( $email ) as $iscrizione ) {
		$pagamenti = get_post_meta( $iscrizione->ID, 'slp_pagamenti', true );
		$versato   = slp_totale_pagato( is_array( $pagamenti ) ? $pagamenti : array() );

		if ( $versato > 0 ) {
			update_post_meta( $iscrizione->ID, 'slp_nome', 'Dati cancellati su richiesta' );
			delete_post_meta( $iscrizione->ID, 'slp_email' );
			delete_post_meta( $iscrizione->ID, 'slp_telefono' );
			wp_update_post( array(
				'ID'         => $iscrizione->ID,
				'post_title' => 'Iscrizione anonimizzata #' . $iscrizione->ID,
			) );

			$rimossi    = true;
			$conservati = true;
			$messaggi[] = sprintf(
				'Iscrizione #%d: nome, email e telefono sono stati cancellati. La riga dell\'incasso resta registrata, senza dati personali, per gli obblighi di conservazione contabile.',
				$iscrizione->ID
			);
			continue;
		}

		wp_delete_post( $iscrizione->ID, true );
		$rimossi = true;
	}

	return array(
		'items_removed'  => $rimossi,
		'items_retained' => $conservati,
		'messages'       => $messaggi,
		'done'           => true,
	);
}

// -----------------------------------------------------------------------------
// Testo suggerito per l'informativa privacy del sito
// -----------------------------------------------------------------------------

add_action( 'admin_init', 'slp_suggerisci_testo_privacy' );
function slp_suggerisci_testo_privacy() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$testo = '<h3>Iscrizioni ai corsi professionali</h3>'
		. '<p>Quando ti iscrivi a un corso raccogliamo nome e cognome, email e, se lo lasci, il telefono. '
		. 'Servono per gestire l\'iscrizione, comunicarti le informazioni pratiche sulla data scelta e tenere '
		. 'il conto dei pagamenti ricevuti.</p>'
		. '<p>Conserviamo questi dati per il tempo necessario a gestire il corso e, quando c\'è stato un pagamento, '
		. 'per il periodo previsto dagli obblighi contabili e fiscali. Puoi chiedere in qualsiasi momento di vedere '
		. 'i tuoi dati o di cancellarli: dei dati collegati a un incasso restano solo le informazioni contabili, senza nome né contatti.</p>'
		. '<p>Se ricevi un attestato, il tuo nome e il livello raggiunto compaiono nell\'elenco pubblico dei certificati '
		. 'soltanto se lo hai autorizzato: senza quell\'autorizzazione l\'attestato resta valido ma non viene pubblicato.</p>';

	wp_add_privacy_policy_content( 'Sfoglia Lab — Pro', wp_kses_post( $testo ) );
}
