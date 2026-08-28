<?php
/**
 * calendario.php — Calendario appuntamenti corsi dell'Accademia della Sfoglia
 * (Rina Poletti). Date con orari e posti, prenotazioni con email di bonifico,
 * conteggio pagamenti, lista d'attesa, blocco corsi con avviso, disdetta con
 * regola dei 14 giorni, messaggi privati con i clienti.
 *
 * CPT gs_corso_cal   → una data di corso (meta: data, ore, posti, prezzo, acconto…)
 * CPT gs_prenotazione → una prenotazione di un cliente su un corso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_cal_register_cpt' );
function gs_cal_register_cpt() {
	foreach ( array( 'gs_corso_cal', 'gs_prenotazione' ) as $pt ) {
		register_post_type( $pt, array(
			'labels'       => array( 'name' => $pt ),
			'public'       => false,
			'show_ui'      => false,
			'show_in_menu' => false,
			'supports'     => array( 'title', 'author' ),
		) );
	}
}

// -----------------------------------------------------------------------------
// Impostazioni del calendario (IBAN, beneficiario, testo del corso, giorni disdetta)
// -----------------------------------------------------------------------------
function gs_cal_settings() {
	$s = gs_settings();
	$d = array(
		'iban'          => '',
		'beneficiario'  => 'Accademia della Sfoglia',
		'causale'       => "Contributo a sostegno dell'Associazione Accademia della Sfoglia",
		'istruzioni'    => "Il corso si svolge presso l'Accademia della Sfoglia con Rina Poletti. Riceverai a breve tutti i dettagli su programma, materiali e orari. Per confermare il posto è necessario versare l'acconto tramite bonifico bancario ai dati indicati qui sotto.",
		'giorni_disdetta' => 14,
	);
	$c = isset( $s['calendario'] ) && is_array( $s['calendario'] ) ? $s['calendario'] : array();
	return wp_parse_args( $c, $d );
}
function gs_cal_save_settings( $new ) {
	$s = gs_settings();
	$s['calendario'] = wp_parse_args( $new, gs_cal_settings() );
	update_option( GS_OPTION, $s );
}

// Email degli amministratori del pannello (admin sito + collaboratori).
function gs_cal_admin_emails() {
	$emails = array();
	$site = get_option( 'admin_email' );
	if ( $site ) { $emails[] = $site; }
	foreach ( get_users() as $u ) {
		if ( user_can( $u->ID, 'manage_options' ) || user_can( $u->ID, 'gs_manage_gaming' ) ) {
			if ( ! empty( $u->user_email ) ) { $emails[] = $u->user_email; }
		}
	}
	return array_values( array_unique( array_filter( $emails ) ) );
}

/**
 * Tipi di appuntamento nello stesso calendario: non solo corsi a pagamento,
 * anche esami pratici e confronti dal vero presso la scuola reale — nella
 * stessa lista che le sfogline già usano per prenotarsi, non in un canale
 * a parte.
 */
function gs_cal_tipi_appuntamento() {
	return array(
		'corso'         => 'Corso',
		'esame'         => '📋 Esame pratico',
		'confronto'     => '⚖️ Confronto dal vero',
		'base'          => '🌱 Corso Base',
		'intermedio'    => '🌿 Corso Intermedio',
		'professionale' => '🎖️ Corso Professionale',
	);
}

/**
 * I tre livelli di formazione (Base, Intermedio, Professionale — "I Corsi di
 * Formazione" della pagina Diventa Supporter) rilasciano un attestato a chi
 * lo completa, sullo stesso meccanismo (gs_cal_attestato sulla prenotazione)
 * già usato per il solo Corso Professionale fino alla 3.187.x. Estraneo al
 * gaming/sconti badge (chiarito da Ennio il 2026-08-08: "quelli vengono
 * oltre il gaming") — qui conta solo il tipo di corso prenotato, non i punti
 * o i badge della sfoglina.
 */
function gs_cal_tipo_ha_attestato( $tipo ) {
	return in_array( $tipo, array( 'base', 'intermedio', 'professionale' ), true );
}

/** Titolo dell'attestato per tipo di corso (usato nel pannello, nell'attestato stampabile e nel Registro). */
function gs_cal_attestato_titolo( $tipo ) {
	$titoli = array(
		'base'          => 'Attestato di Corso Base',
		'intermedio'    => 'Attestato di Corso Intermedio',
		'professionale' => 'Attestato di Corso Professionale',
	);
	return $titoli[ $tipo ] ?? 'Attestato';
}

// -----------------------------------------------------------------------------
// Helper corsi e prenotazioni
// -----------------------------------------------------------------------------
function gs_cal_corso_get( $id ) {
	$tipo = get_post_meta( $id, 'gs_tipo', true );
	$liv_sconto = get_post_meta( $id, 'gs_livello_sconto', true );
	return array(
		'id'        => $id,
		'titolo'    => get_the_title( $id ),
		'tipo'      => array_key_exists( $tipo, gs_cal_tipi_appuntamento() ) ? $tipo : 'corso',
		'data'      => get_post_meta( $id, 'gs_data', true ),
		'inizio'    => get_post_meta( $id, 'gs_ora_inizio', true ),
		'fine'      => get_post_meta( $id, 'gs_ora_fine', true ),
		'posti'     => (int) get_post_meta( $id, 'gs_posti', true ),
		'prezzo'    => (float) get_post_meta( $id, 'gs_prezzo', true ),
		'acconto'   => (float) get_post_meta( $id, 'gs_acconto', true ),
		'descr'     => get_post_meta( $id, 'gs_descrizione', true ),
		'stato'     => get_post_meta( $id, 'gs_stato', true ) ?: 'aperto',
		'motivo'    => get_post_meta( $id, 'gs_blocco_motivo', true ),
		// Livello corso per il sistema sconti badge (sconto-corsi.php): '' = non abilitato.
		'livello_sconto' => function_exists( 'gs_sconto_livelli' ) && array_key_exists( $liv_sconto, gs_sconto_livelli() ) ? $liv_sconto : '',
	);
}

/**
 * Colore della "Vetrina dei Corsi" per QUESTO corso — sempre lo stesso
 * ovunque compaia (la Vetrina, "Le mie prenotazioni"…), perché legato
 * all'ID del corso e non all'ordine in cui viene disegnato in pagina
 * (Ennio, 17/08/2026: "ad ogni corso dai un colore preciso… mantienilo
 * anche in altre pagine per coordinare i corsi"). Restituisce due colori
 * (per il gradiente), applicati come stile INLINE nel markup — non una
 * classe CSS: è la stessa identica tecnica della grafica di riferimento
 * approvata (creaPh() nell'anteprima), che evita ogni guerra di specificità
 * con le regole del tema/plugin sulle schede.
 */
function gs_cal_colore_corso( $corso_id ) {
	$palette = array(
		array( '#e09d1f', '#a8712a' ),
		array( '#2d8a4e', '#164d27' ),
		array( '#d4a22a', '#8f6a10' ),
		array( '#c98745', '#8a5420' ),
		array( '#bd6d5e', '#7a3d33' ),
		array( '#82a862', '#4a6636' ),
		array( '#e0b65a', '#a97a26' ),
		array( '#38a670', '#1d5c3a' ),
		array( '#dd7f3d', '#8f4b1c' ),
		array( '#a5814a', '#5f4826' ),
	);
	return $palette[ (int) $corso_id % 10 ];
}

/**
 * Le 5 schede statiche "I Nostri Percorsi" (Corso Base/Intermedio/
 * Professionale/Privato/Rina Online), con la stessa grafica a vetrina di
 * bottega del Calendario Corsi vero. Prima erano scritte a mano nel
 * contenuto della pagina "Calendario Corsi": ogni salvataggio di
 * WordPress le spezzava in pezzi (wpautop), lasciando schede vuote a
 * vista — lo stesso problema già risolto per la pagina Sostienici
 * trasformandola in shortcode. Qui si fa lo stesso: [gs_percorsi_corsi].
 */
/**
 * I 5 livelli/corsi della Vetrina — dati condivisi da TRE punti che devono
 * restare identici: [gs_percorsi_corsi] (scaffali statici, qui sotto),
 * pagina-supporter.php (stessa vetrina copiata pari pari, Ennio 17/08/2026)
 * e [gs_carosello_corsi] in caroselli.php (le stesse schede dentro il
 * carosello scorrevole, richiesto da Ennio il 19/08/2026). Cambiare un
 * corso qui aggiorna tutti e tre i punti in automatico.
 */
function gs_percorsi_corsi_dati() {
	return array(
		array( 'href' => 'https://accademiadellasfoglia.it/calendario-corsi/?livello=base', 'tag' => 'LIVELLO 1', 'titolo' => 'I Primi Passi della Sfoglia', 'testo' => 'Per chi si avvicina per la prima volta alla sfoglia fatta a mano. € 170 — min 6, max 10 partecipanti. La pasta realizzata resta a te.', 'c1' => '#e09d1f', 'c2' => '#a8712a' ),
		array( 'href' => 'https://accademiadellasfoglia.it/calendario-corsi/?livello=intermedio', 'tag' => 'LIVELLO 2', 'titolo' => 'Sfoglia Intermedia', 'testo' => 'Per amatori che vogliono affinare gesti, impasti e tecnica; dà accesso al Registro degli Amatori. € 220 — min 6, max 10 partecipanti. Insieme a «I Primi Passi della Sfoglia»: € 370.', 'c1' => '#2d8a4e', 'c2' => '#164d27' ),
		array( 'href' => 'https://accademiadellasfoglia.it/calendario-corsi/?livello=professionale', 'tag' => 'PORTA AL REGISTRO', 'titolo' => 'Corso Professionale', 'testo' => '5 giorni, 40 ore. Porta al diploma a marchio registrato e al Registro dei Professionisti.', 'c1' => '#bd6d5e', 'c2' => '#7a3d33' ),
		array( 'href' => 'https://accademiadellasfoglia.it/calendario-corsi/?livello=privato', 'tag' => 'SU MISURA', 'titolo' => 'Corso Privato', 'testo' => 'Se desideri una formazione esclusiva, a qualsiasi livello, la Maestra Rina Poletti è disponibile per te.', 'c1' => '#c98745', 'c2' => '#8a5420' ),
		array( 'href' => 'https://accademiadellasfoglia.it/calendario-corsi/?livello=online', 'tag' => 'PORTA AL REGISTRO', 'titolo' => 'Rina Online', 'testo' => 'Due incontri in videochiamata con la Maestra Rina Poletti, da ovunque tu sia.', 'c1' => '#38a670', 'c2' => '#1d5c3a' ),
	);
}

add_shortcode( 'gs_percorsi_corsi', 'gs_sc_percorsi_corsi' );
function gs_sc_percorsi_corsi() {
	// Stessa veste "gs-cs" di [gs_carosello_corsi] in "In Vetrina" (21/08/2026,
	// richiesto da Ennio: "sostituisci queste schede nella pagina CORSI con le
	// stesse schede della vetrina dei corsi") — prima qui c'era la vecchia
	// grafica "a bottega" (.gs-vc-oggetto/.gs-vc-scaffale), le due vetrine
	// mostravano gli stessi 5 corsi con due stili diversi. Dati sempre da
	// gs_percorsi_corsi_dati(), condivisi anche con pagina-supporter.php.
	if ( ! function_exists( 'gs_carosello_gs_cs_statico_html' ) ) { return ''; }
	$carte  = gs_percorsi_corsi_dati();
	$schede = '';
	foreach ( $carte as $c ) {
		$schede .= gs_carosello_gs_cs_scheda_semplice_html(
			$c['href'],
			'',
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="8" y1="2.5" x2="8" y2="6.5"></line><line x1="16" y1="2.5" x2="16" y2="6.5"></line></svg>',
			$c['c1'], $c['c2'],
			$c['tag'],
			$c['titolo'],
			$c['testo']
		);
	}

	return gs_carosello_gs_cs_statico_html( 'Calendario Corsi', '📅 La Vetrina dei Corsi', 'I 5 livelli dell\'Accademia — clicca una scheda per vedere la descrizione e le prossime date.', $schede );
}

/**
 * "I programmi" sulla pagina Corsi: 4 corsi con obiettivo, durata e il
 * programma capitolo per capitolo. Prima erano 4 <details> scritti a mano
 * nel contenuto della pagina — stesso rischio di corruzione al salvataggio
 * già visto altrove (vedi gs_sc_percorsi_corsi sopra), quindi anche questi
 * diventano uno shortcode: [gs_programmi_corsi]. Testo riassuntivo breve
 * in vista, "Vedi il programma" apre sotto tutto il testo originale.
 */
add_shortcode( 'gs_programmi_corsi', 'gs_sc_programmi_corsi' );
function gs_sc_programmi_corsi() {
	$ico_cal = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="8" y1="2.5" x2="8" y2="6.5"></line><line x1="16" y1="2.5" x2="16" y2="6.5"></line></svg>';
	$ico_online = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>';
	$ico_stella = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.5l2.6 5.8 6.2.6-4.7 4.2 1.4 6.2L12 16.6l-5.5 2.7 1.4-6.2-4.7-4.2 6.2-.6z"></path></svg>';

	$corsi = array(
		array(
			'id' => 'corso-1', 'c1' => '#e09d1f', 'c2' => '#a8712a', 'ico' => $ico_cal,
			'titolo' => 'I Primi Passi nella Sfoglia', 'sub' => 'Corso Base · 6–8 ore',
			'riassunto' => 'Le basi per la sfoglia perfetta: impasto, stesura al matterello e i primi formati tagliati.',
			'meta' => '6–8 ORE · NESSUN PREREQUISITO', 'ribbon' => '',
			'righe' => array( array( 'Obiettivo', "Fornire le basi per la realizzazione della sfoglia perfetta, imparando le tecniche fondamentali e i trucchi del mestiere." ) ),
			'capitoli' => array(
				array( 'Introduzione al mondo della sfoglia', array( "Storia e tradizione della sfoglia emiliana.", "L'importanza degli ingredienti: farine, uova.", "Gli strumenti del mestiere: matterello, tagliere, tarocco." ) ),
				array( "La preparazione dell'impasto", array( 'Tecniche di impasto a mano: la "fontana" e l\'incorporazione degli ingredienti.', "Consistenza e riposo dell'impasto: l'importanza del glutine." ) ),
				array( 'La stesura della sfoglia', array( 'Movimenti base del matterello: avanti e indietro, rotazione.', 'Come ottenere una sfoglia sottile e omogenea.', 'Esercitazioni pratiche individuali.' ) ),
				array( 'Realizzazione di formati base', array( 'Tagliatelle: la larghezza perfetta.', 'Quadrifiore (quadrucci): un formato semplice e versatile.', 'Garganelli: la rigatura con il pettine.', 'Spaghetto alla chitarra.', 'Consigli per la cottura e il condimento.' ) ),
			),
			'firma' => "Il corso è a cura dell'Accademia della Sfoglia", 'link' => '',
		),
		array(
			'id' => 'corso-2', 'c1' => '#2d8a4e', 'c2' => '#164d27', 'ico' => $ico_cal,
			'titolo' => 'Sfoglia Intermedia: Formati e Ripieni', 'sub' => 'Corso Intermedio · 6–8 ore',
			'riassunto' => 'Tortellini, cappelletti e tortelloni: chiusure tradizionali, ripieni classici e abbinamenti.',
			'meta' => '6–8 ORE · DOPO IL CORSO BASE', 'ribbon' => '',
			'righe' => array(
				array( 'Obiettivo', 'Approfondire le tecniche di stesura e imparare a realizzare formati di pasta più complessi, introducendo anche la preparazione di ripieni.' ),
				array( 'Prerequisito', 'Aver frequentato il corso "I Primi Passi nella Sfoglia" o possedere una buona base di stesura.' ),
			),
			'capitoli' => array(
				array( 'Revisione delle tecniche base', array( 'Rafforzamento delle competenze sulla stesura.', 'Trucchi per una sfoglia ancora più sottile.' ) ),
				array( 'Formati di pasta fresca', array( 'Tortellini: la chiusura tradizionale.', 'Cappelletti: la chiusura tradizionale.', 'Tortelloni: la forma e il ripieno classico.' ) ),
				array( 'Preparazione dei ripieni', array( 'Ricette classiche: ricotta e spinaci, zucca, carne.', 'Consigli per la scelta degli ingredienti e la loro preparazione.' ) ),
				array( 'Abbinamenti e condimenti', array( 'Salse e sughi adatti ai diversi formati di pasta ripiena.', 'Consigli per la presentazione del piatto.' ) ),
			),
			'firma' => "Il corso è a cura dell'Accademia della Sfoglia", 'link' => '',
		),
		array(
			'id' => 'corso-3', 'c1' => '#bd6d5e', 'c2' => '#7a3d33', 'ico' => $ico_cal,
			'titolo' => 'Corso Professionale', 'sub' => '5 giorni, dal lunedì al venerdì · 40 ore',
			'riassunto' => "Il percorso che porta al diploma con marchio registrato dell'Accademia della Sfoglia e al Registro dei Professionisti.",
			'meta' => '5 GIORNI (LUN–VEN) · 40 ORE', 'ribbon' => 'Porta al Registro',
			'righe' => array(
				array( 'Obiettivo', 'Sviluppare creatività e precisione nella stesura, imparando tecniche avanzate e realizzando formati di pasta colorati e decorati con ripieni gourmet.' ),
				array( 'Chi può accedere', 'Tutti, anche chi parte da zero: le prime due giornate ripercorrono il Corso Base e il Corso Intermedio. Chi li ha già frequentati può entrare direttamente dalla terza giornata.' ),
			),
			'capitoli' => array(
				array( 'Tecniche avanzate di stesura', array( 'La sfoglia "al velo": massima sottigliezza e trasparenza.', "L'uso di stampi speciali: rigati e decorati." ) ),
				array( 'Formati di pasta colorati e decorati', array( 'Balanzoni: la forma e il ripieno ricco.', 'Caramelle: con ripieni vari.', 'Fiocchi di neve: una decorazione elegante e scenografica.', 'Altre decorazioni e forme creative.' ) ),
				array( 'Ripieni gourmet e abbinamenti innovativi', array( 'Ingredienti di alta qualità e tecniche di cottura raffinate.', 'Creazione di abbinamenti originali e sorprendenti.' ) ),
				array( "L'arte dell'impiattamento", array( 'Come presentare i piatti a livello professionale.' ) ),
			),
			'firma' => "Il corso è a cura dell'Accademia della Sfoglia",
			'link' => '<a href="#perche-prezzo">Perché scegliere il Corso Professionale</a>, spiegato dalla Maestra.',
		),
		array(
			'id' => 'corso-4', 'c1' => '#38a670', 'c2' => '#1d5c3a', 'ico' => $ico_online,
			'titolo' => 'Rina Online', 'sub' => 'Corso a distanza · 2 incontri da 2 ore',
			'riassunto' => 'Due incontri in videochiamata con la Maestra Rina Poletti, da ovunque tu sia.',
			'meta' => '2 INCONTRI DA 2 ORE', 'ribbon' => 'Porta al Registro',
			'righe' => array(
				array( 'Come si svolge', "In videochiamata (WhatsApp o un'altra piattaforma indicata dalla Maestra). Le date si programmano in base alla sua agenda." ),
				array( 'Cosa ottieni', "Dopo la prova pratica del secondo incontro e la valutazione della Maestra, ottieni l'Attestato di Frequenza Intermedia e l'iscrizione al Registro Pubblico come Sfoglina o Sfoglino Amatoriale." ),
			),
			'capitoli' => array(
				array( 'Primo incontro — La lezione', array( "L'impasto: proporzioni, tecnica e consistenza giusta.", 'La stesura al matterello: postura, movimento, spessore uniforme.' ) ),
				array( 'Secondo incontro — La prova pratica', array( 'Dimostri quanto hai appreso, seguita dal vivo dalla Maestra.', "Valutazione finale e assegnazione dell'attestato." ) ),
			),
			'firma' => 'Il corso è a cura della Maestra Rina Poletti',
			'link' => '→ Per i dettagli e per fissare la data, <a href="#iscrizione">contatta la segreteria</a>.',
		),
		array(
			'id' => 'corso-5', 'c1' => '#c98745', 'c2' => '#8a5420', 'ico' => $ico_stella,
			'titolo' => 'Corso Privato', 'sub' => 'Formazione esclusiva · livello a tua scelta',
			'riassunto' => 'Se desideri una formazione esclusiva, a qualsiasi livello, la Maestra Rina Poletti è disponibile per te.',
			'meta' => 'PERCORSO INDIVIDUALE', 'ribbon' => '',
			'righe' => array(
				array( 'Obiettivo', "Se desideri una formazione esclusiva, a qualsiasi livello, la Maestra Rina Poletti è disponibile per te: un percorso costruito da zero sulle tue esigenze, dal primo impasto alle tecniche più avanzate." ),
				array( 'Come funziona', "Contenuti, ritmo e durata si decidono insieme alla Maestra, in base al punto da cui parti e all'obiettivo che vuoi raggiungere — che tu stia muovendo i primi passi o voglia perfezionare una tecnica specifica." ),
				array( 'Per chi è', "Chi vuole un'attenzione completamente dedicata, orari su misura, o semplicemente preferisce imparare da sola o da solo con la Maestra invece che in un gruppo." ),
				array( 'Cosa ottieni', 'A seconda del livello raggiunto e verificato dalla Maestra, puoi accedere agli stessi attestati e agli stessi Registri degli altri percorsi.' ),
			),
			'capitoli' => array(),
			'firma' => 'Il corso è a cura della Maestra Rina Poletti',
			'link' => '→ Per costruire insieme il tuo percorso, <a href="#iscrizione">contatta la segreteria</a>.',
		),
	);

	$disegna_corso = function ( $c ) {
		?>
		<div class="gs-pc-corso" id="<?php echo esc_attr( $c['id'] ); ?>">
			<?php if ( $c['ribbon'] ) : ?>
				<span class="gs-pc-ribbon" style="background:<?php echo esc_attr( $c['c2'] ); ?>"><?php echo esc_html( $c['ribbon'] ); ?></span>
			<?php endif; ?>
			<div class="gs-pc-ph" style="background:linear-gradient(150deg,<?php echo esc_attr( $c['c1'] ); ?>,<?php echo esc_attr( $c['c2'] ); ?>)"><?php echo $c['ico']; ?></div>
			<h3 class="gs-pc-titolo"><?php echo esc_html( $c['titolo'] ); ?></h3>
			<p class="gs-pc-riassunto"><?php echo esc_html( $c['riassunto'] ); ?></p>
			<p class="gs-pc-meta"><?php echo esc_html( $c['meta'] ); ?></p>
			<details class="gs-pc-dettaglio">
				<summary class="gs-pc-vedi">Vedi il programma →</summary>
				<div class="gs-pc-corpo">
					<?php foreach ( $c['righe'] as $r ) : ?>
						<div class="gs-pc-r"><b><?php echo esc_html( $r[0] ); ?></b><span><?php echo esc_html( $r[1] ); ?></span></div>
					<?php endforeach; ?>
					<?php if ( $c['capitoli'] ) : ?>
					<ol class="gs-pc-capitoli">
						<?php foreach ( $c['capitoli'] as $cap ) : ?>
							<li><h4><?php echo esc_html( $cap[0] ); ?></h4><ul>
								<?php foreach ( $cap[1] as $voce ) : ?><li><?php echo esc_html( $voce ); ?></li><?php endforeach; ?>
							</ul></li>
						<?php endforeach; ?>
					</ol>
					<?php endif; ?>
					<?php if ( $c['link'] ) : ?><p class="gs-pc-link"><?php echo wp_kses_post( $c['link'] ); ?></p><?php endif; ?>
					<p class="gs-pc-firma"><?php echo esc_html( $c['firma'] ); ?></p>
				</div>
			</details>
		</div>
		<?php
	};

	ob_start();
	?>
	<div class="gs-pc-cornice">
	<div class="gs-pc-riga">
	<?php foreach ( array_slice( $corsi, 0, 3 ) as $c ) : $disegna_corso( $c ); endforeach; ?>
	</div>
	<div class="gs-pc-riga gs-pc-riga-logo">
	<?php $disegna_corso( $corsi[3] ); ?>
	<img class="gs-pc-logo" src="<?php echo esc_url( GS_URL . 'assets/img/logo-accademia-sigillo.png' ); ?>" alt="Accademia della Sfoglia" />
	<?php $disegna_corso( $corsi[4] ); ?>
	</div>
	</div>
	<?php
	return ob_get_clean();
}

function gs_cal_corsi( $solo_futuri = false ) {
	$q = get_posts( array(
		'post_type'      => 'gs_corso_cal',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_data',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	) );
	if ( ! $solo_futuri ) { return $q; }
	$oggi = date( 'Y-m-d', current_time( 'timestamp' ) );
	return array_values( array_filter( $q, function ( $p ) use ( $oggi ) {
		return get_post_meta( $p->ID, 'gs_data', true ) >= $oggi;
	} ) );
}
/** Prenotazioni di un corso (o tutte), eventualmente filtrate per stato. */
function gs_cal_prenotazioni( $corso_id = 0, $stati = null ) {
	$args = array(
		'post_type'      => 'gs_prenotazione',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
	);
	if ( $corso_id ) {
		$args['meta_query'] = array( array( 'key' => 'gs_corso', 'value' => (int) $corso_id ) );
	}
	$list = get_posts( $args );
	if ( $stati ) {
		$stati = (array) $stati;
		$list = array_values( array_filter( $list, function ( $p ) use ( $stati ) {
			return in_array( get_post_meta( $p->ID, 'gs_stato', true ), $stati, true );
		} ) );
	}
	return $list;
}
/**
 * La sfoglina può togliere questa prenotazione dalla propria lista (cestino
 * recuperabile) solo se è "chiusa": corso effettuato (confermata e la data
 * del corso è passata), oppure annullata/assente/rimborsata. Mai una
 * prenotazione ancora attiva (prenotato/confermato con corso futuro,
 * lista d'attesa) — richiesto da Ennio il 2026-07-30.
 */
function gs_cal_pren_puo_eliminare( $pid ) {
	$stato = get_post_meta( $pid, 'gs_stato', true ) ?: 'prenotato';
	if ( in_array( $stato, array( 'annullato', 'annullato_tardi', 'no_show', 'rimborsato' ), true ) ) {
		return true;
	}
	if ( 'confermato' === $stato ) {
		$corso = gs_cal_corso_get( (int) get_post_meta( $pid, 'gs_corso', true ) );
		return ! empty( $corso['data'] ) && strtotime( $corso['data'] ) < current_time( 'timestamp' );
	}
	return false;
}

/** Posti che occupano davvero un posto: prenotato + confermato. */
function gs_cal_posti_occupati( $corso_id ) {
	return count( gs_cal_prenotazioni( $corso_id, array( 'prenotato', 'confermato' ) ) );
}
function gs_cal_posti_rimasti( $corso ) {
	$id = is_array( $corso ) ? $corso['id'] : $corso;
	$tot = is_array( $corso ) ? $corso['posti'] : (int) get_post_meta( $id, 'gs_posti', true );
	return max( 0, $tot - gs_cal_posti_occupati( $id ) );
}
function gs_cal_pren_get( $id ) {
	return array(
		'id'       => $id,
		'corso'    => (int) get_post_meta( $id, 'gs_corso', true ),
		'cliente'  => (int) get_post_meta( $id, 'gs_cliente', true ),
		'stato'    => get_post_meta( $id, 'gs_stato', true ) ?: 'prenotato',
		'acconto'  => (float) get_post_meta( $id, 'gs_acconto_versato', true ),
		'saldo'    => (float) get_post_meta( $id, 'gs_saldo_versato', true ),
	);
}
function gs_cal_pren_pagato( $id ) {
	return (float) get_post_meta( $id, 'gs_acconto_versato', true ) + (float) get_post_meta( $id, 'gs_saldo_versato', true );
}
/**
 * True se il cliente ha già versato un acconto su una qualsiasi
 * prenotazione. Interroga il database con i filtri giusti invece di
 * caricare TUTTE le prenotazioni mai create e filtrarle in PHP — chiamata
 * da gs_cal_mail_prenotazione(), cioè sul percorso che la sfoglina sta
 * aspettando (trovato il 23/08/2026).
 */
function gs_cal_ha_acconto( $uid ) {
	$q = get_posts( array(
		'post_type'      => 'gs_prenotazione',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'gs_cliente', 'value' => (int) $uid ),
			array( 'key' => 'gs_acconto_versato', 'value' => 0, 'compare' => '>', 'type' => 'DECIMAL(10,2)' ),
		),
	) );
	return ! empty( $q );
}

// -----------------------------------------------------------------------------
// Email
// -----------------------------------------------------------------------------
function gs_cal_mail_prenotazione( $pren_id ) {
	$pren = gs_cal_pren_get( $pren_id );
	$u    = get_userdata( $pren['cliente'] );
	if ( ! $u ) { return; }
	$corso = gs_cal_corso_get( $pren['corso'] );
	$cfg   = gs_cal_settings();
	$gia   = ( gs_cal_ha_acconto( $pren['cliente'] ) && (float) get_post_meta( $pren_id, 'gs_acconto_versato', true ) <= 0 );

	$data = $corso['data'] ? date_i18n( 'l j F Y', strtotime( $corso['data'] ) ) : '';
	$corpo  = "Ciao " . $u->display_name . ",\n\n";
	$corpo .= "grazie per la tua prenotazione al corso dell'Accademia della Sfoglia.\n\n";
	$corpo .= "DETTAGLI DEL CORSO\n";
	$corpo .= "• Data: " . $data . "\n";
	$corpo .= "• Orario: " . $corso['inizio'] . ( $corso['fine'] ? ' – ' . $corso['fine'] : '' ) . "\n";
	if ( $corso['titolo'] ) { $corpo .= "• Corso: " . $corso['titolo'] . "\n"; }
	$corpo .= "\n" . $cfg['istruzioni'] . "\n\n";

	if ( $gia ) {
		$corpo .= "Risulti già in regola con l'acconto versato in precedenza: non devi effettuare un nuovo versamento.\n\n";
	} else {
		$corpo .= "COME CONFERMARE IL POSTO — BONIFICO\n";
		$corpo .= "• Importo acconto: € " . number_format( $corso['acconto'], 2, ',', '.' ) . "\n";
		if ( $corso['prezzo'] ) { $corpo .= "• Quota totale del corso: € " . number_format( $corso['prezzo'], 2, ',', '.' ) . "\n"; }
		$corpo .= "• Beneficiario: " . $cfg['beneficiario'] . "\n";
		if ( $cfg['iban'] ) { $corpo .= "• IBAN: " . $cfg['iban'] . "\n"; }
		$corpo .= "• Causale: " . $cfg['causale'] . "\n\n";
	}
	$corpo .= "CONDIZIONI\n";
	$corpo .= "• In caso di mancata partecipazione senza preavviso, l'acconto è trattenuto a copertura spese.\n";
	$corpo .= "• L'acconto è restituito solo se la disdetta viene inviata almeno " . (int) $cfg['giorni_disdetta'] . " giorni prima del corso.\n";
	$corpo .= "• Per impossibilità giustificate potremo, a nostra discrezione, inserirti in lista d'attesa per date future.\n\n";
	$corpo .= "A presto,\nAccademia della Sfoglia";

	if ( function_exists( 'gs_mail_progetto' ) ) {
		gs_mail_progetto( $u->ID, 'calendario', 'Prenotazione corso — Accademia della Sfoglia', $corpo );
	} elseif ( $u->user_email ) {
		wp_mail( $u->user_email, 'Prenotazione corso — Accademia della Sfoglia', $corpo );
	}
}

function gs_cal_mail_blocco( $corso_id, $motivo ) {
	$corso = gs_cal_corso_get( $corso_id );
	$data  = $corso['data'] ? date_i18n( 'j F Y', strtotime( $corso['data'] ) ) : '';
	foreach ( gs_cal_prenotazioni( $corso_id, array( 'prenotato', 'confermato' ) ) as $p ) {
		$u = get_userdata( (int) get_post_meta( $p->ID, 'gs_cliente', true ) );
		if ( ! $u ) { continue; }
		$corpo  = "Ciao " . $u->display_name . ",\n\n";
		$corpo .= "il corso del " . $data . " è stato sospeso dall'Accademia della Sfoglia.\n\n";
		$corpo .= "MOTIVAZIONE\n" . $motivo . "\n\n";
		$corpo .= "Ti invitiamo a scegliere una nuova data disponibile dal calendario dei corsi sul nostro sito. ";
		$corpo .= "Se avevi già versato l'acconto, resta valido per la nuova prenotazione.\n\n";
		$corpo .= "Ci scusiamo per il disagio.\nAccademia della Sfoglia";
		if ( function_exists( 'gs_mail_progetto' ) ) {
			gs_mail_progetto( $u->ID, 'calendario', 'Corso sospeso — nuove date disponibili', $corpo );
		} elseif ( $u->user_email ) {
			wp_mail( $u->user_email, 'Corso sospeso — nuove date disponibili', $corpo );
		}
	}
}

function gs_cal_mail_lista_attesa( $pren_id, $corso_id ) {
	$pren = gs_cal_pren_get( $pren_id );
	$u    = get_userdata( $pren['cliente'] );
	if ( ! $u ) { return; }
	$corso = gs_cal_corso_get( $corso_id );
	$data  = $corso['data'] ? date_i18n( 'l j F Y', strtotime( $corso['data'] ) ) : '';
	$gia   = ( $pren['acconto'] > 0 );
	$corpo  = "Ciao " . $u->display_name . ",\n\n";
	$corpo .= "si è liberata una data per il corso dell'Accademia della Sfoglia ed eri in lista d'attesa.\n\n";
	$corpo .= "DATA PROPOSTA\n• " . $data . " — orario " . $corso['inizio'] . ( $corso['fine'] ? ' – ' . $corso['fine'] : '' ) . "\n\n";
	if ( $gia ) {
		$corpo .= "Avendo già versato l'acconto, NON devi effettuare un nuovo versamento: ti basta confermare la partecipazione.\n\n";
	} else {
		$corpo .= "Per confermare sarà necessario versare l'acconto secondo le istruzioni che ti invieremo.\n\n";
	}
	$corpo .= "Rispondi a questa mail o scrivici dall'area riservata per accettare la data.\n\nAccademia della Sfoglia";
	if ( function_exists( 'gs_mail_progetto' ) ) {
		gs_mail_progetto( $u->ID, 'calendario', 'Data disponibile — Accademia della Sfoglia', $corpo );
	} elseif ( $u->user_email ) {
		wp_mail( $u->user_email, 'Data disponibile — Accademia della Sfoglia', $corpo );
	}
}

function gs_cal_mail_admin_nuovo_cliente( $user_id ) {
	$u = get_userdata( $user_id );
	if ( ! $u ) { return; }
	$corpo  = "Nuova registrazione sulla piattaforma dell'Accademia della Sfoglia.\n\n";
	$corpo .= "• Nome: " . $u->display_name . "\n• Email: " . $u->user_email . "\n• Username: " . $u->user_login . "\n\n";
	$corpo .= "Puoi gestirla dal pannello di controllo.";
	foreach ( gs_cal_admin_emails() as $to ) {
		wp_mail( $to, 'Nuova registrazione — Accademia della Sfoglia', $corpo );
	}
}
// Avvisa gli amministratori quando un cliente si registra.
add_action( 'gs_after_registration', 'gs_cal_mail_admin_nuovo_cliente' );

// -----------------------------------------------------------------------------
// AJAX lato CLIENTE — prenota, disdici, messaggi
// -----------------------------------------------------------------------------
// gs_is_approved() da solo, SENZA gs_puo_partecipare(): chi ha pagato un
// corso deve poterlo gestire anche a gaming chiuso (documento dei trenta
// giorni, 26/08/2026). L'accesso alla pagina del Calendario Corsi resta
// governato dal suo controllo suo proprio (sezioni.php, livello
// 'superiore'), non toccato da questo lavoro.
add_action( 'wp_ajax_gs_cal_prenota', 'gs_ajax_cal_prenota' );
function gs_ajax_cal_prenota() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_is_approved( $uid ) ) { wp_send_json_error( array( 'message' => 'Devi accedere per prenotare.' ) ); }
	$corso_id = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	if ( 'gs_corso_cal' !== get_post_type( $corso_id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	$corso = gs_cal_corso_get( $corso_id );
	if ( 'aperto' !== $corso['stato'] ) { wp_send_json_error( array( 'message' => 'Le prenotazioni per questo corso non sono aperte.' ) ); }

	// Da qui in poi si tocca il numero di posti disponibili: una sezione
	// critica con un lock nominato di MySQL, uno per corso. Senza questo,
	// due prenotazioni arrivate quasi insieme possono passare ENTRAMBE il
	// controllo "posti rimasti" prima che una delle due sia stata
	// registrata davvero — dimostrato con un test di carico il 14/08/2026:
	// un corso con 3 posti ne ha accettate 4. Il lock riguarda solo questo
	// specifico corso: le prenotazioni su corsi diversi non si aspettano
	// a vicenda.
	global $wpdb;
	$lock_prenota = 'gs_prenota_' . $corso_id;
	if ( '1' !== $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_prenota ) ) ) {
		wp_send_json_error( array( 'message' => 'Troppe richieste nello stesso momento su questo corso, riprova tra un attimo.' ) );
	}

	if ( gs_cal_posti_rimasti( $corso ) < 1 ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_prenota ) );
		wp_send_json_error( array( 'message' => 'Posti esauriti per questa data.' ) );
	}

	// Già prenotata su questo corso?
	foreach ( gs_cal_prenotazioni( $corso_id ) as $p ) {
		if ( (int) get_post_meta( $p->ID, 'gs_cliente', true ) === $uid
			&& in_array( get_post_meta( $p->ID, 'gs_stato', true ), array( 'prenotato', 'confermato' ), true ) ) {
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_prenota ) );
			wp_send_json_error( array( 'message' => 'Hai già una prenotazione per questa data.' ) );
		}
	}

	$pid = wp_insert_post( array(
		'post_type'   => 'gs_prenotazione',
		'post_status' => 'publish',
		'post_author' => $uid,
		'post_title'  => 'Prenotazione',
	) );
	if ( is_wp_error( $pid ) || ! $pid ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_prenota ) );
		wp_send_json_error( array( 'message' => 'Errore, riprova.' ) );
	}
	update_post_meta( $pid, 'gs_corso', $corso_id );
	update_post_meta( $pid, 'gs_cliente', $uid );
	update_post_meta( $pid, 'gs_stato', 'prenotato' );
	update_post_meta( $pid, 'gs_acconto_versato', 0 );
	update_post_meta( $pid, 'gs_saldo_versato', 0 );

	// Fine della sezione critica: il lock si rilascia qui, non serve
	// tenerlo durante l'invio delle email qui sotto.
	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_prenota ) );

	gs_cal_mail_prenotazione( $pid );
	if ( function_exists( 'gs_inbox_crea' ) ) {
		$cu = get_userdata( $uid );
		gs_inbox_crea( 'Nuova prenotazione: ' . $corso['titolo'], ( $cu ? $cu->display_name : 'Una sfoglina' ) . ' ha prenotato il corso del ' . ( $corso['data'] ? date_i18n( 'j F Y', strtotime( $corso['data'] ) ) : '' ) . '.', array( 'from' => $cu ? $cu->display_name : 'Cliente', 'link_pren' => $pid ) );
	}
	wp_send_json_success( array( 'message' => 'Prenotazione registrata! Ti abbiamo inviato una email con le istruzioni per il bonifico.' ) );
}

add_action( 'wp_ajax_gs_cal_disdici', 'gs_ajax_cal_disdici' );
function gs_ajax_cal_disdici() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$pid = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) ); }
	if ( (int) get_post_meta( $pid, 'gs_cliente', true ) !== $uid ) { wp_send_json_error( array( 'message' => 'Non è la tua prenotazione.' ) ); }

	$corso = gs_cal_corso_get( (int) get_post_meta( $pid, 'gs_corso', true ) );

	// Disdicibile solo se ancora attiva: non una prenotazione già chiusa
	// (rimborsato/no_show/annullata) né un corso già svolto — altrimenti si
	// riscrive lo stato di una prenotazione chiusa, cancellando per esempio
	// un "rimborsato" già registrato dal gestore (trovato il 23/08/2026).
	$stato_ora = get_post_meta( $pid, 'gs_stato', true ) ?: 'prenotato';
	if ( ! in_array( $stato_ora, array( 'prenotato', 'confermato', 'lista_attesa' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Questa prenotazione è già chiusa: per modificarla scrivi all\'Accademia.' ) );
	}
	if ( $corso['data'] && strtotime( $corso['data'] ) < current_time( 'timestamp' ) ) {
		wp_send_json_error( array( 'message' => 'Il corso si è già svolto: non è più disdicibile.' ) );
	}

	$cfg   = gs_cal_settings();
	$giorni = (int) $cfg['giorni_disdetta'];
	$in_tempo = $corso['data'] && ( strtotime( $corso['data'] ) - current_time( 'timestamp' ) ) >= $giorni * DAY_IN_SECONDS;

	update_post_meta( $pid, 'gs_stato', $in_tempo ? 'annullato' : 'annullato_tardi' );

	// Avviso a chi gestisce, con l'informazione che serve per agire: prima
	// una disdetta non avvisava nessuno, e se c'era un acconto versato il
	// messaggio alla sfoglina prometteva un rimborso che non veniva
	// registrato da nessuna parte (trovato il 23/08/2026).
	if ( function_exists( 'gs_inbox_crea' ) ) {
		$cu  = get_userdata( $uid );
		$acc = (float) get_post_meta( $pid, 'gs_acconto_versato', true );
		gs_inbox_crea(
			'Disdetta: ' . $corso['titolo'],
			( $cu ? $cu->display_name : 'Una sfoglina' ) . ' ha disdetto il corso del '
				. ( $corso['data'] ? date_i18n( 'j F Y', strtotime( $corso['data'] ) ) : '' ) . ".\n\n"
				. ( $in_tempo
					? ( $acc > 0
						? '⚠️ Disdetta NEI TERMINI con acconto di € ' . number_format( $acc, 2, ',', '.' ) . ' già versato: va restituito.'
						: 'Disdetta nei termini, nessun acconto versato.' )
					: 'Disdetta fuori termine: l\'acconto resta trattenuto a copertura spese.' ),
			array( 'from' => $cu ? $cu->display_name : 'Cliente', 'link_pren' => $pid )
		);
	}

	$msg = $in_tempo
		? 'Disdetta registrata entro i termini: l\'eventuale acconto ti sarà restituito.'
		: 'Disdetta registrata. Essendo a meno di ' . $giorni . ' giorni dal corso, l\'acconto è trattenuto a copertura spese.';
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_cal_pren_elimina', 'gs_ajax_cal_pren_elimina' );
function gs_ajax_cal_pren_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$pid = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) ); }
	$cliente = (int) get_post_meta( $pid, 'gs_cliente', true );
	if ( $uid !== $cliente && ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	if ( ! gs_cal_pren_puo_eliminare( $pid ) ) {
		wp_send_json_error( array( 'message' => 'Puoi togliere dalla lista solo le prenotazioni concluse (corso effettuato, annullate, assente o rimborsate).' ) );
	}
	wp_trash_post( $pid );
	wp_send_json_success( array( 'message' => 'Prenotazione tolta dalla tua lista. Resta recuperabile nel cestino.' ) );
}

add_action( 'wp_ajax_gs_cal_pren_ripristina', 'gs_ajax_cal_pren_ripristina' );
function gs_ajax_cal_pren_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$pid = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) ); }
	wp_untrash_post( $pid );
	wp_send_json_success( array( 'message' => 'Prenotazione ripristinata.' ) );
}

// Messaggio privato cliente ↔ accademia (per prenotazione).
add_action( 'wp_ajax_gs_cal_msg', 'gs_ajax_cal_msg' );
function gs_ajax_cal_msg() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	$pid = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) ); }
	$cliente = (int) get_post_meta( $pid, 'gs_cliente', true );
	$is_mgr  = gs_can_manage();
	if ( $uid !== $cliente && ! $is_mgr ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	if ( function_exists( 'gs_antispam_check' ) ) {
		$check = gs_antispam_check( $_POST, 'cal_msg' );
		if ( is_wp_error( $check ) ) { wp_send_json_error( array( 'message' => $check->get_error_message() ) ); }
	}
	$testo = isset( $_POST['testo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) ) : '';
	$media = function_exists( 'gs_msg_upload' ) ? gs_msg_upload( 'media' ) : null;
	if ( is_wp_error( $media ) ) { wp_send_json_error( array( 'message' => $media->get_error_message() ) ); }
	if ( '' === $testo && ! $media ) { wp_send_json_error( array( 'message' => 'Scrivi un messaggio o allega un file.' ) ); }

	$msgs = get_post_meta( $pid, 'gs_msgs', true );
	if ( ! is_array( $msgs ) ) { $msgs = array(); }
	$msgs[] = array(
		'da_accademia' => $is_mgr && $uid !== $cliente,
		'nome'         => $is_mgr && $uid !== $cliente ? 'Accademia' : ( get_userdata( $uid ) ? get_userdata( $uid )->display_name : 'Cliente' ),
		'testo'        => $testo,
		'media'        => is_array( $media ) ? $media['url'] : '',
		'media_type'   => is_array( $media ) ? $media['type'] : '',
		'time'         => current_time( 'timestamp' ),
	);
	update_post_meta( $pid, 'gs_msgs', $msgs );

	// Notifica email all'altra parte.
	if ( $is_mgr && $uid !== $cliente ) {
		$u = get_userdata( $cliente );
		if ( $u ) {
			$corpo_notifica = "Hai un nuovo messaggio riguardo la tua prenotazione:\n\n\"" . $testo . "\"\n\nAccedi all'area riservata per rispondere.";
			if ( function_exists( 'gs_mail_progetto' ) ) {
				gs_mail_progetto( $u->ID, 'messaggi', 'Messaggio dall\'Accademia della Sfoglia', $corpo_notifica );
			} elseif ( $u->user_email ) {
				wp_mail( $u->user_email, 'Messaggio dall\'Accademia della Sfoglia', $corpo_notifica );
			}
		}
	} else {
		$cn = get_userdata( $uid ) ? get_userdata( $uid )->display_name : 'cliente';
		foreach ( gs_cal_admin_emails() as $to ) {
			wp_mail( $to, 'Messaggio da un cliente (prenotazione)', "Messaggio da " . $cn . ":\n\n\"" . $testo . "\"" );
		}
		if ( function_exists( 'gs_inbox_crea' ) ) {
			gs_inbox_crea( 'Messaggio prenotazione da ' . $cn, $testo, array( 'from' => $cn, 'link_pren' => $pid ) );
		}
	}
	wp_send_json_success( array( 'message' => 'Messaggio inviato.' ) );
}

// -----------------------------------------------------------------------------
// AJAX lato GESTORE
// -----------------------------------------------------------------------------
function gs_cal_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
}

add_action( 'wp_ajax_gs_cal_salva_corso', 'gs_ajax_cal_salva_corso' );
function gs_ajax_cal_salva_corso() {
	gs_cal_guard();
	$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$data   = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data ) ) { wp_send_json_error( array( 'message' => 'Data non valida.' ) ); }
	if ( ! $id ) {
		$id = wp_insert_post( array( 'post_type' => 'gs_corso_cal', 'post_status' => 'publish', 'post_title' => $titolo ?: ( 'Corso del ' . $data ) ) );
		if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore.' ) ); }
		update_post_meta( $id, 'gs_stato', 'aperto' );
	} else {
		wp_update_post( array( 'ID' => $id, 'post_title' => $titolo ?: ( 'Corso del ' . $data ) ) );
		// Ripara l'autore se questo corso, creato prima che esistesse la foto sul
		// quadratino della Pianificazione dell'Anno, non ne aveva uno.
		if ( function_exists( 'gs_piano_ripara_autore' ) ) { gs_piano_ripara_autore( $id ); }
	}
	$tipo = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'corso';
	if ( ! array_key_exists( $tipo, gs_cal_tipi_appuntamento() ) ) { $tipo = 'corso'; }
	update_post_meta( $id, 'gs_tipo', $tipo );
	update_post_meta( $id, 'gs_data', $data );
	update_post_meta( $id, 'gs_ora_inizio', sanitize_text_field( wp_unslash( $_POST['inizio'] ?? '' ) ) );
	update_post_meta( $id, 'gs_ora_fine', sanitize_text_field( wp_unslash( $_POST['fine'] ?? '' ) ) );
	update_post_meta( $id, 'gs_posti', max( 0, (int) ( $_POST['posti'] ?? 0 ) ) );
	update_post_meta( $id, 'gs_prezzo', (float) str_replace( ',', '.', $_POST['prezzo'] ?? 0 ) );
	update_post_meta( $id, 'gs_acconto', (float) str_replace( ',', '.', $_POST['acconto'] ?? 0 ) );
	update_post_meta( $id, 'gs_descrizione', sanitize_textarea_field( wp_unslash( $_POST['descrizione'] ?? '' ) ) );
	$liv_sconto = isset( $_POST['livello_sconto'] ) ? sanitize_key( wp_unslash( $_POST['livello_sconto'] ) ) : '';
	if ( ! function_exists( 'gs_sconto_livelli' ) || ! array_key_exists( $liv_sconto, gs_sconto_livelli() ) ) { $liv_sconto = ''; }
	update_post_meta( $id, 'gs_livello_sconto', $liv_sconto );
	wp_send_json_success( array( 'message' => 'Corso salvato.' ) );
}

add_action( 'wp_ajax_gs_cal_elimina_corso', 'gs_ajax_cal_elimina_corso' );
function gs_ajax_cal_elimina_corso() {
	gs_cal_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_corso_cal' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Corso spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_cal_blocca_corso', 'gs_ajax_cal_blocca_corso' );
function gs_ajax_cal_blocca_corso() {
	gs_cal_guard();
	$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$motivo = isset( $_POST['motivo'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['motivo'] ) ) ) : '';
	if ( 'gs_corso_cal' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	if ( '' === $motivo ) { wp_send_json_error( array( 'message' => 'Scrivi la motivazione da inviare agli iscritti.' ) ); }
	update_post_meta( $id, 'gs_stato', 'bloccato' );
	update_post_meta( $id, 'gs_blocco_motivo', $motivo );
	gs_cal_mail_blocco( $id, $motivo );
	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea( 'Corso bloccato: ' . $corso['titolo'], "Il corso è stato bloccato. Motivazione inviata agli iscritti:\n\n" . $motivo, array( 'from' => 'Sistema', 'link_pren' => 0 ) );
	}
	wp_send_json_success( array( 'message' => 'Corso bloccato e avviso inviato agli iscritti.' ) );
}

add_action( 'wp_ajax_gs_cal_riapri_corso', 'gs_ajax_cal_riapri_corso' );
function gs_ajax_cal_riapri_corso() {
	gs_cal_guard();
	$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( 'gs_corso_cal' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	update_post_meta( $id, 'gs_stato', 'aperto' );
	delete_post_meta( $id, 'gs_blocco_motivo' );
	wp_send_json_success( array( 'message' => 'Corso riaperto.' ) );
}

// Stato prenotazione: conferma / no_show / annulla / lista_attesa / rimborsato
add_action( 'wp_ajax_gs_cal_pren_stato', 'gs_ajax_cal_pren_stato' );
function gs_ajax_cal_pren_stato() {
	gs_cal_guard();
	$pid   = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	$stato = isset( $_POST['stato'] ) ? sanitize_key( wp_unslash( $_POST['stato'] ) ) : '';
	$validi = array( 'prenotato', 'confermato', 'no_show', 'annullato', 'lista_attesa', 'rimborsato' );
	if ( 'gs_prenotazione' !== get_post_type( $pid ) || ! in_array( $stato, $validi, true ) ) { wp_send_json_error( array( 'message' => 'Dati non validi.' ) ); }
	$stato_precedente = get_post_meta( $pid, 'gs_stato', true );
	update_post_meta( $pid, 'gs_stato', $stato );
	if ( 'confermato' === $stato && 'confermato' !== $stato_precedente && function_exists( 'gs_accoda_volo' ) ) {
		$pren  = get_post( $pid );
		$corso = gs_cal_corso_get( (int) get_post_meta( $pid, 'gs_corso', true ) );
		gs_accoda_volo( $pren->post_author, 'PRENOTAZIONE CONFERMATA: ' . mb_strtoupper( $corso['titolo'] ), gs_pagina_url( 'gs_page_calendario' ) );
	}
	wp_send_json_success( array( 'message' => 'Stato aggiornato: ' . $stato . '.' ) );
}

/**
 * Registro di ogni pagamento e correzione su una prenotazione — mai
 * riscritto, solo accodato, stesso schema già usato per gli sconti
 * (gs_sconto_log_aggiungi) e il Buono Sfoglia (gs_buono_sfoglia_log_aggiungi).
 * Serve perché il totale versato (gs_acconto_versato/gs_saldo_versato) è
 * solo il numero finale: senza un registro non c'è modo di capire come ci
 * si è arrivati se qualcosa non torna (trovato il 23/08/2026).
 *
 * ATTENZIONE: è un leggi-modifica-scrivi su un solo meta, quindi due
 * scritture simultanee sulla stessa prenotazione possono far perdere una
 * delle due righe — il totale versato non si perde (le somme di
 * gs_acconto_versato/gs_saldo_versato sono scritte a parte), il registro sì.
 * La rete di sicurezza di gs_ajax_cal_pagamento() legge l'ultima voce di
 * questo registro per riconoscere un doppio invio senza identificativo: se
 * proprio quella voce va persa, la rete non vede il doppio invio (trovato
 * il 25/08/2026 — stesso limite in gs_log_points() e gs_token_movimento()).
 */
function gs_cal_pagamento_log_aggiungi( $pid, $voce ) {
	$log = get_post_meta( $pid, 'gs_pagamenti_log', true );
	$log = is_array( $log ) ? $log : array();
	$voce['data'] = current_time( 'mysql' );
	$voce['da']   = get_current_user_id();
	$log[]        = $voce;
	update_post_meta( $pid, 'gs_pagamenti_log', $log );
}

// Registra un pagamento (acconto o saldo) per una prenotazione.
add_action( 'wp_ajax_gs_cal_pagamento', 'gs_ajax_cal_pagamento' );
function gs_ajax_cal_pagamento() {
	gs_cal_guard();
	$pid    = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	$tipo   = isset( $_POST['tipo'] ) ? sanitize_key( wp_unslash( $_POST['tipo'] ) ) : 'acconto';
	$importo = (float) str_replace( ',', '.', $_POST['importo'] ?? 0 );
	$rif    = isset( $_POST['rif'] ) ? sanitize_key( wp_unslash( $_POST['rif'] ) ) : '';
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) ); }
	if ( 0.0 === $importo ) { wp_send_json_error( array( 'message' => 'Importo non valido.' ) ); }
	// Un importo negativo serve a correggere un errore (es. un doppio clic
	// registrato prima di questa correzione, o un importo sbagliato): solo
	// il titolare può farlo, e resta nel registro come correzione — non si
	// usa max(0, ...) per non nascondere l'errore, deve restare visibile
	// insieme al registro che spiega come ci si è arrivati.
	if ( $importo < 0 && ! gs_is_titolare() ) {
		wp_send_json_error( array( 'message' => 'Solo il titolare può registrare una correzione (importo negativo).' ) );
	}

	// Blocca il doppio invio: il client manda un identificativo generato
	// una volta sola all'apertura della scheda (non a ogni clic, vedi
	// gaming.js), il server rifiuta un secondo invio con lo stesso
	// identificativo PRIMA di sommare. Senza questo, un leggi-somma-riscrivi
	// su denaro come quello qui sotto registra due volte lo stesso pagamento
	// se il pulsante riceve un doppio clic (trovato il 23/08/2026).
	$visti = get_post_meta( $pid, 'gs_pagamenti_rif', true );
	$visti = is_array( $visti ) ? $visti : array();
	if ( $rif && in_array( $rif, $visti, true ) ) {
		wp_send_json_error( array( 'message' => 'Questo pagamento risulta già registrato.' ) );
	}
	if ( $rif ) {
		$visti[] = $rif;
		update_post_meta( $pid, 'gs_pagamenti_rif', $visti );
	} else {
		// Rete di sicurezza per quando l'identificativo non arriva (browser con
		// il JavaScript vecchio in cache: SiteGround Optimizer combina e tiene
		// in cache i file, quindi capita davvero nei giorni dopo un
		// aggiornamento). Due pagamenti identici a pochi secondi l'uno
		// dall'altro sono un doppio clic, non due versamenti veri — usa il
		// registro appena creato invece di rifiutare i pagamenti senza
		// identificativo, perché la cosa peggiore che può fare un pannello di
		// pagamenti è rifiutare un incasso vero (trovato il 25/08/2026).
		$log_ora = get_post_meta( $pid, 'gs_pagamenti_log', true );
		$ultima  = is_array( $log_ora ) && $log_ora ? end( $log_ora ) : null;
		if ( $ultima
			&& (string) $ultima['tipo'] === (string) $tipo
			&& abs( (float) $ultima['importo'] - $importo ) < 0.005
			&& ( current_time( 'timestamp' ) - strtotime( $ultima['data'] ) ) < 15 ) {
			wp_send_json_error( array( 'message' => 'Un pagamento identico è stato registrato pochi secondi fa: se è davvero un secondo versamento, riprova fra un minuto.' ) );
		}
	}

	$key = ( 'saldo' === $tipo ) ? 'gs_saldo_versato' : 'gs_acconto_versato';
	$corrente = (float) get_post_meta( $pid, $key, true );
	update_post_meta( $pid, $key, $corrente + $importo );
	gs_cal_pagamento_log_aggiungi( $pid, array(
		'tipo'    => $tipo,
		'importo' => $importo,
		'nota'    => $importo < 0 ? 'correzione' : 'pagamento',
	) );
	// Solo un versamento vero (non una correzione negativa) conferma la
	// prenotazione: una correzione non deve far scattare una conferma.
	if ( 'acconto' === $tipo && $importo > 0 && 'prenotato' === get_post_meta( $pid, 'gs_stato', true ) ) {
		update_post_meta( $pid, 'gs_stato', 'confermato' );
		if ( function_exists( 'gs_accoda_volo' ) ) {
			$pren  = get_post( $pid );
			$corso = gs_cal_corso_get( (int) get_post_meta( $pid, 'gs_corso', true ) );
			gs_accoda_volo( $pren->post_author, 'PRENOTAZIONE CONFERMATA: ' . mb_strtoupper( $corso['titolo'] ), gs_pagina_url( 'gs_page_calendario' ) );
		}
	}
	wp_send_json_success( array( 'message' => $importo < 0 ? 'Correzione registrata.' : 'Pagamento registrato.' ) );
}

// Offri una data a chi è in lista d'attesa (email automatica).
add_action( 'wp_ajax_gs_cal_offri_data', 'gs_ajax_cal_offri_data' );
function gs_ajax_cal_offri_data() {
	gs_cal_guard();
	$pid   = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	$corso = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) || 'gs_corso_cal' !== get_post_type( $corso ) ) { wp_send_json_error( array( 'message' => 'Dati non validi.' ) ); }
	gs_cal_mail_lista_attesa( $pid, $corso );
	update_post_meta( $pid, 'gs_data_offerta', $corso );
	wp_send_json_success( array( 'message' => 'Email di proposta data inviata.' ) );
}

/**
 * Chi era in lista d'attesa ha accettato la data proposta: la prenotazione
 * passa su quel corso e diventa confermata (l'eventuale acconto resta
 * valido). Ritorna un array 'ok' => bool, 'message' => string.
 */
function gs_cal_accetta_offerta( $pid ) {
	$pid = (int) $pid;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) {
		return array( 'ok' => false, 'message' => 'Prenotazione non valida.' );
	}
	$corso_id = (int) get_post_meta( $pid, 'gs_data_offerta', true );
	if ( ! $corso_id || 'gs_corso_cal' !== get_post_type( $corso_id ) ) {
		return array( 'ok' => false, 'message' => 'Prima proponi una data, poi segna l\'accettazione.' );
	}
	$corso = gs_cal_corso_get( $corso_id );
	if ( gs_cal_posti_rimasti( $corso ) < 1 ) {
		return array( 'ok' => false, 'message' => 'Quella data si è nel frattempo riempita: proponine un\'altra.' );
	}
	update_post_meta( $pid, 'gs_corso', $corso_id );
	update_post_meta( $pid, 'gs_stato', 'confermato' );
	update_post_meta( $pid, 'gs_offerta_accettata', 1 );
	delete_post_meta( $pid, 'gs_data_offerta' );

	if ( function_exists( 'gs_accoda_volo' ) ) {
		$pren = get_post( $pid );
		gs_accoda_volo( $pren->post_author, 'PRENOTAZIONE CONFERMATA: ' . mb_strtoupper( $corso['titolo'] ), gs_pagina_url( 'gs_page_calendario' ) );
	}
	return array( 'ok' => true, 'message' => 'Prenotazione confermata sulla data proposta.' );
}

add_action( 'wp_ajax_gs_cal_offerta_accettata', 'gs_ajax_cal_offerta_accettata' );
function gs_ajax_cal_offerta_accettata() {
	gs_cal_guard();
	$pid = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	$r   = gs_cal_accetta_offerta( $pid );
	if ( ! $r['ok'] ) { wp_send_json_error( array( 'message' => $r['message'] ) ); }
	wp_send_json_success( array( 'message' => $r['message'] ) );
}

// -----------------------------------------------------------------------------
// SHORTCODE [gs_calendario] — calendario e prenotazioni lato cliente
// -----------------------------------------------------------------------------
/**
 * [gs_corsi_pulsanti corso="Nome del corso" prezzo="150"] — due pulsanti da
 * incollare a mano in fondo a ogni corso nella pagina "Corsi" (una pagina
 * scritta a mano, non gestita dal plugin, diversa da "Calendario Corsi"):
 * "Vai al Calendario e Prenotazioni" e "Scrivici", con l'oggetto della email
 * già scritto con il nome del corso (e la quota, se indicata). Gli attributi
 * sono facoltativi: senza, l'oggetto resta generico.
 */
add_shortcode( 'gs_corsi_pulsanti', 'gs_sc_corsi_pulsanti' );
function gs_sc_corsi_pulsanti( $atts ) {
	$atts = shortcode_atts( array( 'corso' => '', 'prezzo' => '' ), $atts, 'gs_corsi_pulsanti' );
	$corso  = sanitize_text_field( $atts['corso'] );
	$prezzo = sanitize_text_field( $atts['prezzo'] );

	$oggetto = $corso ? 'Richiesta informazioni: ' . $corso . ( $prezzo ? ' (quota € ' . $prezzo . ')' : '' ) : 'Richiesta informazioni sui corsi';

	$url_calendario = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_calendario' ) : '';

	$out = '<p class="gs-corsi-pulsanti" style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0">';
	if ( $url_calendario ) {
		$out .= '<a class="gs-btn gs-btn-sm" href="' . esc_url( $url_calendario ) . '">📅 Vai al Calendario e Prenotazioni</a>';
	}
	$out .= '<a class="gs-btn gs-btn-sm gs-btn-ghost" href="mailto:' . esc_attr( get_option( 'admin_email' ) ) . '?subject=' . rawurlencode( $oggetto ) . '">✉️ Scrivici per informazioni</a>';
	$out .= '</p>';
	return $out;
}

/**
 * Scheda completa di una data del calendario (icona, titolo, data, etichetta,
 * pulsante "vedi dettagli", e i dettagli veri nascosti dentro — orario,
 * posti, prezzo, presentazione, prenota, scrivici). Estratta come funzione
 * a sé (19/08/2026) per essere riusata sia nell'elenco "Le prossime date"
 * sia nei blocchi della griglia mensile (gs_cal_griglia_html): stesso corso,
 * stesso popup di dettagli, un solo posto dove tenere aggiornata la logica.
 */
function gs_cal_scheda_card_html( $p, $me, $nascosta = false ) {
	$c = gs_cal_corso_get( $p->ID );
	$rim = gs_cal_posti_rimasti( $c );
	$data_lunga = $c['data'] ? date_i18n( 'l j F Y', strtotime( $c['data'] ) ) : '';
	$cal_svg = '<svg class="gs-cal-ico" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="8" y1="2.5" x2="8" y2="6.5"/><line x1="16" y1="2.5" x2="16" y2="6.5"/></svg>';
	list( $col1, $col2 ) = gs_cal_colore_corso( $p->ID );

	$out = '<div class="gs-vc-oggetto" data-corso="' . (int) $p->ID . '"' . ( $nascosta ? ' style="display:none"' : '' ) . '>';
	$out .= '<div class="gs-vc-ph" style="background:linear-gradient(150deg,' . esc_attr( $col1 ) . ',' . esc_attr( $col2 ) . ')">' . $cal_svg . '</div>';
	$out .= '<p class="gs-vc-titolo" style="color:#4a3a28 !important">' . esc_html( $c['titolo'] ) . '</p>';
	$out .= '<p class="gs-vc-luogo" style="color:#8a7a5c !important">' . esc_html( $data_lunga ) . '</p>';
	$out .= '<span class="gs-vc-cartellino">' . esc_html( mb_strtoupper( gs_cal_tipi_appuntamento()[ $c['tipo'] ] ) ) . '</span>';

	// Pulsante che apre un popup dedicato con tutti i dettagli e la
	// prenotazione (Ennio, 19/08/2026: "gli si deve aprire una schermata con
	// tutte le informazioni e indicazioni per iscriversi") — il blocco vero
	// e proprio (gs-vc-dettagli) resta nel markup ma nascosto: il clic in
	// gaming.js lo copia dentro il popup, così i pulsanti "Prenota"/
	// "Scrivici" restano gli stessi elementi (funzionano identici, niente
	// duplicazione della logica AJAX).
	if ( ! $nascosta ) {
		$out .= '<button type="button" class="gs-btn gs-btn-sm gs-cal-apri-dettagli" data-corso="' . (int) $p->ID . '">🔍 Vedi dettagli e prenota</button>';
	}

	$out .= '<div class="gs-vc-dettagli" style="display:none">';
	$out .= '🕒 ' . esc_html( $c['inizio'] . ( $c['fine'] ? ' – ' . $c['fine'] : '' ) ) . '<br>';
	$out .= '🎟️ Posti rimasti: <strong>' . (int) $rim . '</strong> / ' . (int) $c['posti'] . '<br>';
	$out .= '💶 Acconto: € ' . number_format( $c['acconto'], 2, ',', '.' );
	if ( $c['prezzo'] ) { $out .= ' · Quota: € ' . number_format( $c['prezzo'], 2, ',', '.' ); }
	if ( $c['descr'] ) {
		$out .= '<div class="gs-corso-descr" style="margin-top:6px"><p style="font-weight:700;margin:0 0 4px">📖 Presentazione</p>';
		$out .= '<p class="gs-hint">' . nl2br( esc_html( $c['descr'] ) ) . '</p>';
		$out .= '</div>';
	}
	if ( ! $me ) {
		$out .= '<p class="gs-hint" style="margin-top:6px">Accedi per prenotare.</p>';
	} elseif ( $rim < 1 ) {
		$out .= '<p style="margin-top:6px"><span class="gs-voted">Posti esauriti</span></p>';
	} else {
		$etichetta_pulsante = 'esame' === $c['tipo'] ? 'Iscriviti all\'esame' : ( 'confronto' === $c['tipo'] ? 'Iscriviti al confronto' : 'Prenota il posto' );
		$out .= '<button class="gs-btn gs-btn-sm gs-cal-prenota" data-corso="' . (int) $p->ID . '">' . esc_html( $etichetta_pulsante ) . '</button> <span class="gs-cal-msg gs-richiesta-esito"></span>';
	}
	// Scrivici, con oggetto già compilato con il corso e la quota (richiesto
	// da Ennio, 11/08/2026): visibile a chiunque legga la pagina, anche
	// senza accesso — un mailto non dipende da nessun modulo di contatto
	// esterno, funziona sempre.
	$oggetto_mail = 'Richiesta informazioni: ' . $c['titolo'] . ( $c['prezzo'] ? ' (quota € ' . number_format( $c['prezzo'], 2, ',', '.' ) . ')' : '' );
	$out .= '<p style="margin-top:6px"><a class="gs-btn gs-btn-sm gs-btn-ghost" href="mailto:' . esc_attr( get_option( 'admin_email' ) ) . '?subject=' . rawurlencode( $oggetto_mail ) . '">✉️ Scrivici</a></p>';
	$out .= '</div></div>';
	return $out;
}

/**
 * Griglia mensile (Gen–Dic), una riga per livello, blocchi colorati nella
 * colonna del mese giusto — stesso linguaggio grafico di "Pianificazione
 * dell'Anno" (l'attrezzo di regia degli amministratori), ma pubblica, di
 * sola lettura, e limitata al solo Calendario Corsi: gli amministratori
 * vedono anche gare/percorsi/corsi online e possono trascinare per spostare
 * le date, qui invece si può solo guardare e cliccare per aprire il popup
 * di dettagli/prenotazione — richiesto da Ennio il 19/08/2026 ("voglio che
 * appaia un calendario del tipo Pianificazione dell'Anno").
 */
/**
 * Raggruppa i corsi aperti per livello (stesse 5 parole-chiave di
 * $livelli_filtro, più "altro" per esami/confronti/titoli che non
 * corrispondono a nessun livello) e per anno — usato sia dalla griglia
 * mensile sia dalla ruota dell'anno, così la classificazione resta identica
 * nei due punti di vista (19/08/2026).
 */
function gs_cal_raggruppa_per_livello( $aperti_tutti ) {
	$parole = array(
		'base'          => 'Primi Passi',
		'intermedio'    => 'Sfoglia Intermedia',
		'professionale' => 'Corso Professionale',
		'privato'       => 'Corso Privato',
		'online'        => 'Online',
	);
	$colori_livello = array();
	if ( function_exists( 'gs_percorsi_corsi_dati' ) ) {
		foreach ( gs_percorsi_corsi_dati() as $cd ) {
			foreach ( $parole as $slug => $parola ) {
				if ( false !== strpos( $cd['href'], 'livello=' . $slug ) ) {
					$colori_livello[ $slug ] = array( $cd['titolo'], $cd['c1'], $cd['c2'] );
				}
			}
		}
	}

	$righe = array();
	foreach ( $parole as $slug => $parola ) {
		$meta = isset( $colori_livello[ $slug ] ) ? $colori_livello[ $slug ] : array( $parola, '#a8862e', '#6b4f14' );
		$righe[ $slug ] = array( 'titolo' => $meta[0], 'c1' => $meta[1], 'c2' => $meta[2], 'eventi' => array(), 'schede' => array() );
	}
	$righe['altro'] = array( 'titolo' => 'Altro (esami, confronti…)', 'c1' => '#8a7a5c', 'c2' => '#5c5140', 'eventi' => array(), 'schede' => array() );

	$anni = array();
	foreach ( $aperti_tutti as $p ) {
		$data = get_post_meta( $p->ID, 'gs_data', true );
		if ( ! $data ) { continue; }
		$anno   = (int) substr( $data, 0, 4 );
		$mese   = (int) substr( $data, 5, 2 );
		$giorno = (int) substr( $data, 8, 2 );

		$slug_trovato = 'altro';
		foreach ( $parole as $slug => $parola ) {
			if ( false !== stripos( get_the_title( $p ), $parola ) ) { $slug_trovato = $slug; break; }
		}
		$righe[ $slug_trovato ]['eventi'][ $anno ][ $mese ][] = array( 'id' => $p->ID, 'giorno' => $giorno, 'titolo' => get_the_title( $p ) );
		$righe[ $slug_trovato ]['schede'][ $p->ID ] = $p;
		$anni[ $anno ] = true;
	}
	if ( ! $anni ) { return null; }
	ksort( $anni );
	$anno_corrente = (int) date_i18n( 'Y', current_time( 'timestamp' ) );
	$anno_default  = isset( $anni[ $anno_corrente ] ) ? $anno_corrente : array_key_first( $anni );

	return array( 'righe' => $righe, 'anni' => array_keys( $anni ), 'anno_default' => $anno_default );
}

function gs_cal_griglia_html( $aperti_tutti, $me ) {
	if ( ! $aperti_tutti ) { return ''; }
	$dati = gs_cal_raggruppa_per_livello( $aperti_tutti );
	if ( ! $dati ) { return ''; }
	$righe        = $dati['righe'];
	$anni         = $dati['anni'];
	$anno_default = $dati['anno_default'];
	$mesi_label   = array( 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' );

	$out = '<div class="gs-cal-piano">';
	if ( count( $anni ) > 1 ) {
		$out .= '<p><label>Anno: <select class="gs-cal-piano-anno-select">';
		foreach ( $anni as $a ) {
			$out .= '<option value="' . (int) $a . '"' . selected( $a, $anno_default, false ) . '>' . (int) $a . '</option>';
		}
		$out .= '</select></label></p>';
	}
	foreach ( $anni as $a ) {
		$out .= '<div class="gs-cal-piano-anno-blocco" data-anno="' . (int) $a . '"' . ( $a !== $anno_default ? ' style="display:none"' : '' ) . '>';
		$out .= '<div class="gs-cal-piano-riga gs-cal-piano-intestazione"><div class="gs-cal-piano-etichetta"></div>';
		foreach ( $mesi_label as $ml ) { $out .= '<div class="gs-cal-piano-cella-label">' . $ml . '</div>'; }
		$out .= '</div>';
		foreach ( $righe as $riga ) {
			if ( empty( $riga['eventi'][ $a ] ) ) { continue; }
			$out .= '<div class="gs-cal-piano-riga">';
			$out .= '<div class="gs-cal-piano-etichetta" style="background:' . esc_attr( $riga['c1'] ) . '">' . esc_html( $riga['titolo'] ) . '</div>';
			for ( $m = 1; $m <= 12; $m++ ) {
				$out .= '<div class="gs-cal-piano-cella">';
				if ( ! empty( $riga['eventi'][ $a ][ $m ] ) ) {
					foreach ( $riga['eventi'][ $a ][ $m ] as $ev ) {
						$out .= '<button type="button" class="gs-cal-piano-blocco" data-corso="' . (int) $ev['id'] . '" title="' . esc_attr( $ev['titolo'] ) . '" style="background:linear-gradient(150deg,' . esc_attr( $riga['c1'] ) . ',' . esc_attr( $riga['c2'] ) . ')">' . (int) $ev['giorno'] . '</button>';
					}
				}
				$out .= '</div>';
			}
			$out .= '</div>';
		}
		// Schede nascoste (icona/titolo/data/dettagli) per ogni corso di
		// questa griglia: il popup le legge da qui via data-corso, non serve
		// che siano anche nell'elenco "Le prossime date" sotto (che può
		// essere filtrato per livello mentre la griglia mostra sempre tutto).
		foreach ( $righe as $riga ) {
			foreach ( $riga['schede'] as $p ) {
				$out .= gs_cal_scheda_card_html( $p, $me, true );
			}
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * Ruota dell'anno: un cerchio con un anello per livello (dal centro verso
 * fuori) e un pallino per ogni data reale, posizionato lungo l'anello in
 * base al mese/giorno — seconda vista dello stesso piano annuale, più
 * scenografica della griglia, richiesta da Ennio il 19/08/2026 accanto alla
 * griglia stessa ("realizzale tutte e due nella pagina").
 */
function gs_cal_ruota_html( $aperti_tutti, $me ) {
	if ( ! $aperti_tutti ) { return ''; }
	$dati = gs_cal_raggruppa_per_livello( $aperti_tutti );
	if ( ! $dati ) { return ''; }
	$righe        = $dati['righe'];
	$anni         = $dati['anni'];
	$anno_default = $dati['anno_default'];
	$mesi_label   = array( 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic' );
	$giorni_mese  = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
	$cx = 250; $cy = 250;

	// Un raggio per livello (anelli concentrici, dal centro verso fuori) —
	// stesso ordine dei 5 livelli, "altro" come anello più esterno.
	$raggi = array( 'base' => 108, 'intermedio' => 132, 'professionale' => 156, 'privato' => 178, 'online' => 198, 'altro' => 206 );

	$punto = function ( $raggio, $gradi ) use ( $cx, $cy ) {
		$rad = $gradi * M_PI / 180;
		return array( 'x' => round( $cx + $raggio * cos( $rad ), 1 ), 'y' => round( $cy + $raggio * sin( $rad ), 1 ) );
	};

	$out  = '<div class="gs-cal-ruota">';
	if ( count( $anni ) > 1 ) {
		$out .= '<p><label>Anno: <select class="gs-cal-ruota-anno-select">';
		foreach ( $anni as $a ) {
			$out .= '<option value="' . (int) $a . '"' . selected( $a, $anno_default, false ) . '>' . (int) $a . '</option>';
		}
		$out .= '</select></label></p>';
	}
	foreach ( $anni as $a ) {
		$out .= '<div class="gs-cal-ruota-anno-blocco" data-anno="' . (int) $a . '"' . ( $a !== $anno_default ? ' style="display:none"' : '' ) . '>';
		$out .= '<svg class="gs-cal-ruota-svg" viewBox="0 0 500 500" role="img" aria-label="Ruota dell\'anno con le date dei corsi">';

		// Anelli guida, uno per livello.
		foreach ( $raggi as $r ) {
			$out .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . (int) $r . '" fill="none" stroke="#e8d9b5" stroke-width="1" />';
		}
		// Cornice esterna dorata + raggi/etichette dei mesi.
		$out .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="222" fill="none" stroke="#cd8b0c" stroke-width="1.4" opacity=".55" />';
		for ( $m = 0; $m < 12; $m++ ) {
			$gradi = $m * 30 - 90;
			$p1 = $punto( 96, $gradi );
			$p2 = $punto( 210, $gradi );
			$out .= '<line x1="' . $p1['x'] . '" y1="' . $p1['y'] . '" x2="' . $p2['x'] . '" y2="' . $p2['y'] . '" stroke="#ecdfc0" stroke-width="1" />';
			$pl = $punto( 236, $gradi + 15 );
			$out .= '<text x="' . $pl['x'] . '" y="' . $pl['y'] . '" class="gs-cal-ruota-mese" text-anchor="middle" dominant-baseline="middle">' . esc_html( $mesi_label[ $m ] ) . '</text>';
		}
		// Medaglione centrale.
		$out .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="78" fill="#fffdf7" stroke="#cd8b0c" stroke-width="1.4" />';
		$out .= '<text x="' . $cx . '" y="' . ( $cy - 4 ) . '" class="gs-cal-ruota-centro-anno" text-anchor="middle">' . (int) $a . '</text>';
		$out .= '<text x="' . $cx . '" y="' . ( $cy + 18 ) . '" class="gs-cal-ruota-centro-nome" text-anchor="middle">Accademia della Sfoglia</text>';

		// Punti evento.
		foreach ( $righe as $slug => $riga ) {
			if ( empty( $riga['eventi'][ $a ] ) ) { continue; }
			$raggio = isset( $raggi[ $slug ] ) ? $raggi[ $slug ] : 206;
			for ( $m = 1; $m <= 12; $m++ ) {
				if ( empty( $riga['eventi'][ $a ][ $m ] ) ) { continue; }
				foreach ( $riga['eventi'][ $a ][ $m ] as $ev ) {
					$frazione = ( $m - 1 ) + ( max( 1, $ev['giorno'] ) - 1 ) / $giorni_mese[ $m - 1 ];
					$p = $punto( $raggio, $frazione * 30 - 90 );
					$out .= '<circle cx="' . $p['x'] . '" cy="' . $p['y'] . '" r="6.5" fill="' . esc_attr( $riga['c1'] ) . '" class="gs-cal-ruota-punto gs-cal-piano-blocco" data-corso="' . (int) $ev['id'] . '"><title>' . esc_html( $ev['titolo'] ) . '</title></circle>';
				}
			}
		}
		$out .= '</svg>';

		// Legenda.
		$out .= '<div class="gs-cal-ruota-legenda">';
		foreach ( $righe as $riga ) {
			$out .= '<span><i style="background:' . esc_attr( $riga['c1'] ) . '"></i>' . esc_html( $riga['titolo'] ) . '</span>';
		}
		$out .= '</div>';

		// Schede nascoste per il popup (stesso principio della griglia).
		foreach ( $righe as $riga ) {
			foreach ( $riga['schede'] as $p ) {
				$out .= gs_cal_scheda_card_html( $p, $me, true );
			}
		}
		$out .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

add_shortcode( 'gs_calendario', 'gs_sc_calendario' );
function gs_sc_calendario() {
	$me  = get_current_user_id();
	// La grafica "Vetrina di bottega" (Ennio, 17/08/2026) resta per le date
	// vere di prenotazione, ma il nome "La Vetrina dei Corsi" ora è del
	// catalogo dei livelli sopra ([gs_percorsi_corsi]): titoli diversi per
	// non avere due "Vetrina dei Corsi" sulla stessa pagina.
	$out = gs_box_open( '📅 Calendario e Prenotazioni', 'gs-box-calendario' );
	$out .= gs_sezione_aiuto( 'Scegli una data e prenota: riceverai un\'email con le istruzioni per il bonifico dell\'acconto e, il giorno prima del corso, un promemoria automatico. Se un corso è al completo puoi metterti in lista d\'attesa. Oltre ai corsi trovi qui anche gli esami pratici e i confronti dal vero presso la scuola reale, segnalati con un\'etichetta dedicata. Il "🎖️ Corso Professionale (una settimana)" rilascia, a chi lo frequenta, l\'Attestato di Corso Professionale: lo trovi pronto da aprire e stampare in "Le mie prenotazioni" non appena l\'Accademia te lo assegna. Le condizioni di disdetta sono scritte in fondo alla pagina.' );
	$out .= '<p class="gs-hint">Scegli una data e prenota il tuo posto. Dopo la prenotazione riceverai una email con il programma e le istruzioni per il bonifico dell\'acconto.</p>';

	$aperti = gs_cal_aperti_tutti();
	// Copia non filtrata per livello, prima del filtro qui sotto: la griglia
	// mensile deve sempre mostrare il quadro completo, anche quando si arriva
	// da un link ?livello=X che restringe solo l'elenco "Le prossime date".
	$aperti_tutti = $aperti;

	$out .= gs_cal_griglia_html( $aperti_tutti, $me );
	$out .= gs_cal_ruota_html( $aperti_tutti, $me );

	// Filtro per livello via link esterni (es. dalla pagina "Diventa Supporter"):
	// ?livello=base|intermedio|professionale|privato|online mostra solo i corsi
	// il cui titolo contiene quella parola, per collegare ogni scheda del
	// percorso direttamente alle sue date in calendario (Ennio, 13/08/2026).
	$livelli_filtro = array(
		'base'          => 'Primi Passi',
		'intermedio'    => 'Sfoglia Intermedia',
		'professionale' => 'Corso Professionale',
		'privato'       => 'Corso Privato',
		'online'        => 'Online',
	);
	$livello_attivo = isset( $_GET['livello'] ) ? sanitize_key( wp_unslash( $_GET['livello'] ) ) : '';
	if ( $livello_attivo && isset( $livelli_filtro[ $livello_attivo ] ) ) {
		$parola = $livelli_filtro[ $livello_attivo ];
		$aperti = array_filter( $aperti, function ( $p ) use ( $parola ) {
			return false !== stripos( get_the_title( $p ), $parola );
		} );
		$out .= '<p class="gs-hint">Stai vedendo solo: <strong>' . esc_html( $parola ) . '</strong> — '
			. '<a href="' . esc_url( remove_query_arg( 'livello' ) ) . '">vedi tutti i corsi</a></p>';

		// Descrizione del livello (titolo, testo, prezzo), sempre visibile
		// arrivando da una scheda del percorso — anche quando non ci sono
		// ancora date pubblicate, chi clicca deve vedere di cosa si tratta,
		// non solo "nessuna data" (Ennio, 19/08/2026: "mi deve portare alla
		// sua descrizione e prenotazione").
		if ( function_exists( 'gs_percorsi_corsi_dati' ) ) {
			foreach ( gs_percorsi_corsi_dati() as $cd ) {
				if ( false !== strpos( $cd['href'], 'livello=' . $livello_attivo ) ) {
					$out .= '<div class="gs-cdc" style="background:linear-gradient(150deg,' . esc_attr( $cd['c1'] ) . ',' . esc_attr( $cd['c2'] ) . ');border-radius:12px;padding:16px 20px;margin:10px 0;color:#fff">'
						. '<p style="font-weight:800;font-size:15px;margin:0 0 4px">' . esc_html( $cd['titolo'] ) . '</p>'
						. '<p style="margin:0;font-size:13px;opacity:.95">' . esc_html( $cd['testo'] ) . '</p>'
						. '</div>';
					break;
				}
			}
		}
	}

	if ( empty( $aperti ) ) {
		if ( $livello_attivo && isset( $livelli_filtro[ $livello_attivo ] ) ) {
			$out .= '<p>Al momento non ci sono date in programma per "' . esc_html( $livelli_filtro[ $livello_attivo ] ) . '". '
				. '<a href="' . esc_url( remove_query_arg( 'livello' ) ) . '">Vedi tutti i corsi</a> o <a href="https://accademiadellasfoglia.it/scrivici-laccademia-della-sfoglia-risponde/">scrivici</a> per essere avvisata/o appena ci sono nuove date.</p>';
		} else {
			$out .= '<p>Al momento non ci sono date in programma. Torna presto!</p>';
		}
	} else {
		// "Vetrina di bottega": le classi (gs-vc-bottega/oggetto/ph/titolo/
		// luogo/cartellino) sono copiate pari pari dall'anteprima già
		// approvata — vedi il blocco di commento sopra le regole in
		// gaming.css. Sotto la parte copiata, i dati veri della
		// prenotazione (posti/acconto/pulsante), che la demo non aveva.
		$out .= '<div class="gs-vc-bottega"><p class="gs-vc-insegna">· Le prossime date ·</p>';
		// Un ripiano ogni 4 schede, ognuno con la sua barra di legno sotto —
		// stessa struttura a "scaffali" dell'anteprima originale, non un
		// unico ripiano lunghissimo (Ennio, 17/08/2026).
		$per_ripiano = 3;
		$gruppi      = array_chunk( $aperti, $per_ripiano );
		foreach ( $gruppi as $gruppo ) {
			$out .= '<div class="gs-vc-scaffale"><div class="gs-vc-ripiano-oggetti">';
			foreach ( $gruppo as $p ) {
				$out .= gs_cal_scheda_card_html( $p, $me );
			}
			$out .= '</div><div class="gs-vc-ripiano-asse"></div></div>';
		}
		$out .= '</div>';
	}
	$out .= gs_box_close();

	// Le mie prenotazioni
	if ( $me ) {
		$mie = array_filter( gs_cal_prenotazioni(), function ( $p ) use ( $me ) {
			return (int) get_post_meta( $p->ID, 'gs_cliente', true ) === $me;
		} );
		if ( $mie ) {
			$out .= gs_box_open( '🎫 Le mie prenotazioni' );
			$out .= '<p class="gs-hint">Qui trovi le tue prenotazioni, i pagamenti registrati e lo spazio per scrivere in privato all\'Accademia.</p>';
			$out .= '<div class="gs-todo-riquadro">';
			$stati_it = array( 'prenotato' => 'In attesa di acconto', 'confermato' => 'Confermata', 'no_show' => 'Assente', 'annullato' => 'Annullata', 'annullato_tardi' => 'Annullata (fuori termine)', 'lista_attesa' => 'In lista d\'attesa', 'rimborsato' => 'Rimborsata' );
			foreach ( $mie as $p ) {
				$corso_id = (int) get_post_meta( $p->ID, 'gs_corso', true );
				$c = gs_cal_corso_get( $corso_id );
				$st = get_post_meta( $p->ID, 'gs_stato', true ) ?: 'prenotato';
				// Stesso colore della Vetrina per questo stesso corso, per
				// riconoscerlo a colpo d'occhio anche qui (Ennio, 17/08/2026).
				list( $pcol1, ) = gs_cal_colore_corso( $corso_id );
				$out .= '<div class="gs-prenotazione-riga gs-prenotazione-colore" style="border-left-color:' . esc_attr( $pcol1 ) . '">';
				$out .= '<strong>' . esc_html( $c['titolo'] ) . '</strong> — ' . esc_html( $c['data'] ? date_i18n( 'j F Y', strtotime( $c['data'] ) ) : '' );
				$out .= '<br>Stato: <strong>' . esc_html( $stati_it[ $st ] ?? $st ) . '</strong>';
				$out .= '<br>Pagato: € ' . number_format( gs_cal_pren_pagato( $p->ID ), 2, ',', '.' );
				if ( in_array( $st, array( 'prenotato', 'confermato' ), true ) ) {
					$out .= '<br><button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-disdici" data-pren="' . (int) $p->ID . '">Disdici</button> <span class="gs-cal-dmsg gs-richiesta-esito"></span>';
				}
				if ( gs_cal_pren_puo_eliminare( $p->ID ) ) {
					$out .= '<br><button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-pren-elimina" data-pren="' . (int) $p->ID . '" title="Puoi sempre recuperarla, chiedendo alla segreteria">✕ Togli dalla mia lista</button> <span class="gs-cal-pren-elimina-msg gs-richiesta-esito"></span>';
				}
				if ( gs_cal_tipo_ha_attestato( $c['tipo'] ) && '1' === get_post_meta( $p->ID, 'gs_cal_attestato', true ) ) {
					$out .= '<br>🎖️ <a href="' . esc_url( gs_cal_attestato_url( $p->ID ) ) . '" target="_blank" rel="noopener">Apri/Stampa il tuo ' . esc_html( gs_cal_attestato_titolo( $c['tipo'] ) ) . '</a>';
				}
				// Messaggi privati con l'Accademia (titolo cliccabile che apre lo scambio)
				$msgs = get_post_meta( $p->ID, 'gs_msgs', true );
				$nmsg = is_array( $msgs ) ? count( $msgs ) : 0;
				$out .= '<details class="gs-inbox-item" style="margin-top:8px"><summary class="gs-inbox-oggetto">✉️ Messaggi con l\'Accademia <span class="gs-msg-data">' . (int) $nmsg . '</span></summary>';
				$out .= '<div class="gs-inbox-corpo"><div class="gs-conv-thread">';
				if ( is_array( $msgs ) ) {
					// Stessa bolla whatsapp delle Conversazioni: qui il
					// destinatario è sempre la sfoglina, quindi "mine" sono i
					// suoi messaggi (non quelli firmati "Accademia") —
					// richiesto da Ennio il 2026-07-30.
					foreach ( $msgs as $m ) {
						$mine = empty( $m['da_accademia'] );
						$out .= '<div class="gs-conv-msg' . ( $mine ? ' mine' : '' ) . '"><span class="gs-conv-from">' . esc_html( $m['nome'] ) . '</span> '
							. '<span class="gs-msg-data">' . esc_html( date_i18n( 'j/m H:i', (int) $m['time'] ) ) . '</span>'
							. '<div>' . nl2br( esc_html( gs_msg_clean( $m['testo'] ) ) ) . '</div>'
							. ( ! empty( $m['media'] ) && function_exists( 'gs_msg_media_html' ) ? gs_msg_media_html( $m['media'], isset( $m['media_type'] ) ? $m['media_type'] : 'image' ) : '' )
							. '</div>';
					}
				}
				$out .= '<form class="gs-form gs-form-cal-msg" data-pren="' . (int) $p->ID . '" onsubmit="return false">';
				if ( function_exists( 'gs_antispam_fields' ) ) { ob_start(); gs_antispam_fields(); $out .= ob_get_clean(); }
				$out .= '<textarea name="testo" rows="2" style="width:100%" placeholder="Scrivi all\'Accademia…"></textarea><p>' . gs_msg_file_field() . '</p><p><button class="gs-btn gs-btn-sm gs-cal-msg-invia">Invia</button> <span class="gs-cal-mmsg gs-richiesta-esito"></span></p></form>';
				$out .= '</div></details>';
				$out .= '</div>';
			}
			$out .= '</div>';
			$out .= gs_box_close();
		}
	}
	return $out;
}

/** Corsi aperti (non bloccati) su cui si basano sia [gs_calendario] sia le
 * due viste separate qui sotto — stesso identico elenco, un solo punto dove
 * calcolarlo. */
function gs_cal_aperti_tutti() {
	$corsi = gs_cal_corsi( true );
	return array_filter( $corsi, function ( $p ) { return 'bloccato' !== get_post_meta( $p->ID, 'gs_stato', true ); } );
}

/**
 * Le due viste del piano annuale come shortcode a sé, per poterle mettere
 * su pagine diverse da quella del Calendario (Ennio, 19/08/2026: "mi
 * sdoppi il codice dei due calendari"). Richiamano esattamente le stesse
 * funzioni già usate dentro [gs_calendario] — stesso output, identico
 * anche nell'aspetto e nel comportamento, solo staccato dal resto.
 */
add_shortcode( 'gs_calendario_griglia', 'gs_sc_calendario_griglia' );
function gs_sc_calendario_griglia() {
	return gs_cal_griglia_html( gs_cal_aperti_tutti(), get_current_user_id() );
}

add_shortcode( 'gs_calendario_ruota', 'gs_sc_calendario_ruota' );
function gs_sc_calendario_ruota() {
	return gs_cal_ruota_html( gs_cal_aperti_tutti(), get_current_user_id() );
}

// -----------------------------------------------------------------------------
// PANNELLO GESTORE — Calendario Corsi (ogni scheda ha la spiegazione in testa)
// -----------------------------------------------------------------------------
function gs_pannello_calendario() {
	if ( ! gs_can_manage() ) { return; }
	$cfg = gs_cal_settings();

	// --- Impostazioni bonifico ---
	echo gs_box_open( 'Calendario Corsi — Dati per il bonifico' );
	echo '<p class="gs-hint">Qui imposti i dati che finiscono nell\'email di prenotazione: beneficiario, IBAN, causale, il testo che spiega come si svolge il corso e i giorni di preavviso per la disdetta con rimborso.</p>';
	?>
	<form class="gs-form gs-form-cal-cfg" onsubmit="return false">
		<p><label>Beneficiario<br><input type="text" name="beneficiario" value="<?php echo esc_attr( $cfg['beneficiario'] ); ?>" style="width:320px"></label></p>
		<p><label>IBAN<br><input type="text" name="iban" value="<?php echo esc_attr( $cfg['iban'] ); ?>" style="width:320px"></label></p>
		<p><label>Causale<br><input type="text" name="causale" value="<?php echo esc_attr( $cfg['causale'] ); ?>" style="width:320px"></label></p>
		<p><label>Come si svolge il corso (testo dell'email)<br><textarea name="istruzioni" rows="3" style="width:100%"><?php echo esc_textarea( $cfg['istruzioni'] ); ?></textarea></label></p>
		<p><label>Giorni di preavviso per disdetta con rimborso<br><input type="number" name="giorni_disdetta" value="<?php echo (int) $cfg['giorni_disdetta']; ?>" min="0" style="width:90px"></label></p>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-cal-cfg-salva">Salva impostazioni</button> <span class="gs-cal-cfg-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();

	// --- Nuovo corso ---
	echo gs_box_open( 'Aggiungi una data di corso' );
	echo '<p class="gs-hint">Inserisci una nuova data: giorno, orario di inizio e fine, numero di posti, quota totale e acconto. Il numero di posti rimasti si aggiorna da solo con le prenotazioni. Oltre ai corsi normali, con "Tipo di appuntamento" puoi mettere in calendario anche un esame pratico o un confronto dal vero presso la scuola reale: compaiono nella stessa lista che le sfogline già usano per prenotarsi, non serve un canale a parte (per un esame/confronto puoi lasciare quota e acconto a 0 se non si paga). Con "Livello per lo sconto badge" segnali a quale livello (Base/Avanzato/Professionale) del sistema sconti appartiene questo corso: se lo lasci su "Nessuno" il corso non entra nel sistema sconti. Con "🖼️ Crea locandina per questo corso" vai dritto al modulo "Nuovo documento" in Diplomi e Locandine, già precompilato con titolo, data e quota.</p>';
	$tipi = gs_cal_tipi_appuntamento();
	$livelli_sconto = function_exists( 'gs_sconto_livelli' ) ? gs_sconto_livelli() : array();
	?>
	<form class="gs-form gs-form-cal-corso" data-id="0" onsubmit="return false">
		<p><label>Titolo del corso<br><input type="text" name="titolo" autocomplete="off" placeholder="Es. Corso base di sfoglia" style="width:320px"></label></p>
		<p><label>Tipo di appuntamento<br><select name="tipo"><?php foreach ( $tipi as $val => $lbl ) : ?><option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?></select></label></p>
		<p style="display:flex;gap:12px;flex-wrap:wrap">
			<label>Data<br><input type="date" name="data"></label>
			<label>Inizio<br><input type="time" name="inizio"></label>
			<label>Fine<br><input type="time" name="fine"></label>
			<label>Posti<br><input type="number" name="posti" min="1" value="8" style="width:80px"></label>
		</p>
		<p style="display:flex;gap:12px;flex-wrap:wrap">
			<label>Quota totale €<br><input type="text" name="prezzo" placeholder="120,00" style="width:110px"></label>
			<label>Acconto €<br><input type="text" name="acconto" placeholder="40,00" style="width:110px"></label>
			<label>Livello per lo sconto badge<br><select name="livello_sconto"><option value="">Nessuno</option><?php foreach ( $livelli_sconto as $val => $lbl ) : ?><option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?></select></label>
		</p>
		<p><label>Come si svolge (facoltativo, override del testo generale)<br><textarea name="descrizione" rows="2" style="width:100%"></textarea></label></p>
		<p><button class="gs-btn gs-btn-sm gs-cal-corso-salva">Salva corso</button> <button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-vai-locandina">🖼️ Crea locandina per questo corso</button> <span class="gs-cal-corso-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();

	// --- Elenco corsi + partecipanti ---
	echo gs_box_open( 'Corsi in calendario e partecipanti' );
	echo '<p class="gs-hint">Per ogni corso vedi posti totali e rimasti, e i partecipanti. Puoi confermare chi ha pagato, segnare gli assenti (perdono l\'acconto), registrare acconti/saldi, mettere in lista d\'attesa, scrivere al cliente e, se serve, bloccare il corso inviando a tutti la motivazione. Sui corsi di tipo "🎖️ Corso Professionale (una settimana)" ogni cliente iscritto ha anche il pulsante "Assegna attestato": rilascia l\'Attestato di Corso Professionale, stampabile subito e sempre riapribile dalla propria pagina "Le mie prenotazioni". Se il corso ha un "Livello per lo sconto badge" impostato e il cliente ha uno sconto accumulato di quel livello (vinto vincendo badge, vedi Premi per Traguardo), lo vedi evidenziato nella sua scheda con la quota già calcolata: clicca "🎓 Sconto applicato / corso fatto" solo dopo aver confermato che ha davvero partecipato, per azzerarlo e farla passare al livello successivo. Ogni riga è colorata secondo il tipo — il corso normale con lo stesso arancio del Calendario Corsi nella Pianificazione dell\'Anno, poi esame, confronto e bloccato con i loro colori dedicati.</p>';
	$corsi = gs_cal_corsi();
	if ( ! $corsi ) {
		echo '<p class="gs-hint">Nessun corso inserito.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="5">';
		foreach ( $corsi as $p ) {
			$c   = gs_cal_corso_get( $p->ID );
			$rim = gs_cal_posti_rimasti( $c );
			$tipi_lbl = gs_cal_tipi_appuntamento();
			$cls_riga = 'bloccato' === $c['stato'] ? ' gs-cal-bloccato' : ' gs-cal-tipo-' . $c['tipo'];
			echo '<details class="gs-inbox-item' . esc_attr( $cls_riga ) . '" data-corso="' . (int) $p->ID . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( 'corso' !== $c['tipo'] ? esc_html( $tipi_lbl[ $c['tipo'] ] ) . ' — ' : '' ) . esc_html( $c['titolo'] )
				. ' <span class="gs-msg-data">' . esc_html( $c['data'] ? date_i18n( 'j/m/Y', strtotime( $c['data'] ) ) : 'senza data' )
				. ' · ' . esc_html( $c['inizio'] . ( $c['fine'] ? '–' . $c['fine'] : '' ) )
				. ' · posti ' . (int) $rim . '/' . (int) $c['posti']
				. ' · ' . esc_html( $c['stato'] ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';

			// Modifica del corso (form precompilato).
			echo '<form class="gs-form gs-form-cal-corso" data-id="' . (int) $p->ID . '" onsubmit="return false">';
			echo '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( $c['titolo'] ) . '" style="width:320px"></label></p>';
			echo '<p><label>Tipo di appuntamento<br><select name="tipo">';
			foreach ( $tipi_lbl as $val => $lbl ) { echo '<option value="' . esc_attr( $val ) . '" ' . selected( $c['tipo'], $val, false ) . '>' . esc_html( $lbl ) . '</option>'; }
			echo '</select></label></p>';
			echo '<p style="display:flex;gap:12px;flex-wrap:wrap">';
			echo '<label>Data<br><input type="date" name="data" value="' . esc_attr( $c['data'] ) . '"></label>';
			echo '<label>Inizio<br><input type="time" name="inizio" value="' . esc_attr( $c['inizio'] ) . '"></label>';
			echo '<label>Fine<br><input type="time" name="fine" value="' . esc_attr( $c['fine'] ) . '"></label>';
			echo '<label>Posti<br><input type="number" name="posti" min="1" value="' . (int) $c['posti'] . '" style="width:80px"></label>';
			echo '</p><p style="display:flex;gap:12px;flex-wrap:wrap">';
			echo '<label>Quota totale €<br><input type="text" name="prezzo" value="' . esc_attr( number_format( $c['prezzo'], 2, ',', '' ) ) . '" style="width:110px"></label>';
			echo '<label>Acconto €<br><input type="text" name="acconto" value="' . esc_attr( number_format( $c['acconto'], 2, ',', '' ) ) . '" style="width:110px"></label>';
			echo '<label>Livello per lo sconto badge<br><select name="livello_sconto"><option value="">Nessuno</option>';
			foreach ( $livelli_sconto as $val => $lbl ) { echo '<option value="' . esc_attr( $val ) . '" ' . selected( $c['livello_sconto'], $val, false ) . '>' . esc_html( $lbl ) . '</option>'; }
			echo '</select></label>';
			echo '</p>';
			echo '<p><label>Come si svolge<br><textarea name="descrizione" rows="2" style="width:100%">' . esc_textarea( $c['descr'] ) . '</textarea></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-cal-corso-salva">Salva modifiche</button> ';
			echo '<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-vai-locandina">🖼️ Crea locandina per questo corso</button> ';
			if ( 'bloccato' === $c['stato'] ) {
				echo '<button class="gs-btn gs-btn-sm gs-cal-riapri" data-id="' . (int) $p->ID . '">Riapri</button> ';
			} else {
				echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-blocca" data-id="' . (int) $p->ID . '">Blocca e avvisa</button> ';
			}
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-elimina" data-id="' . (int) $p->ID . '">Elimina corso</button> ';
			echo '<span class="gs-cal-corso-msg gs-cal-row-msg gs-richiesta-esito"></span></p></form>';

			// Partecipanti
			$prens = gs_cal_prenotazioni( $p->ID );
			if ( $prens ) {
				echo '<h4>Clienti iscritti al corso (' . count( $prens ) . ')</h4>';
				echo '<p class="gs-hint">Clicca sul nome per aprire la scheda del cliente e gestire stato e pagamenti.</p>';
				echo '<div class="gs-inbox-lista gs-lista-risultati gs-paginate" data-per-page="8">';
				foreach ( $prens as $pr ) {
					$cli = get_userdata( (int) get_post_meta( $pr->ID, 'gs_cliente', true ) );
					$st  = get_post_meta( $pr->ID, 'gs_stato', true ) ?: 'prenotato';
					$pag = gs_cal_pren_pagato( $pr->ID );
					echo '<details class="gs-inbox-item" data-pren="' . (int) $pr->ID . '">';
					echo '<summary class="gs-inbox-oggetto">' . esc_html( $cli ? $cli->display_name : '—' )
						. ' <span class="gs-msg-data">' . esc_html( $st ) . ' · € ' . number_format( $pag, 2, ',', '.' ) . '</span></summary>';
					echo '<div class="gs-inbox-corpo">';
					$a_cls = function ( $s ) use ( $st ) { return $st === $s ? ' gs-cal-active' : ''; };
					echo '<p>';
					echo '<button class="gs-btn gs-btn-sm gs-cal-stato' . $a_cls( 'confermato' ) . '" data-pren="' . (int) $pr->ID . '" data-stato="confermato">Conferma</button> ';
					echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-stato' . $a_cls( 'no_show' ) . '" data-pren="' . (int) $pr->ID . '" data-stato="no_show">Assente</button> ';
					echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-stato' . $a_cls( 'lista_attesa' ) . '" data-pren="' . (int) $pr->ID . '" data-stato="lista_attesa">Lista attesa</button> ';
					echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-cal-stato' . $a_cls( 'rimborsato' ) . '" data-pren="' . (int) $pr->ID . '" data-stato="rimborsato">Rimborsato</button> ';
					echo '<span class="gs-cal-row-msg gs-richiesta-esito"></span></p>';
					echo '<p>Registra pagamento: '
						. '<select class="gs-cal-pay-tipo"><option value="acconto">Acconto</option><option value="saldo">Saldo</option></select> '
						. '<input type="text" class="gs-cal-pay-imp" placeholder="€" style="width:70px"> '
						. '<button class="gs-btn gs-btn-sm gs-cal-pay">Registra</button></p>';

					// Sconto badge accumulato: solo se questo corso è del livello giusto
					// per questo cliente e ha davvero uno sconto da spendere (sconto-corsi.php).
					if ( $cli && $c['livello_sconto'] && function_exists( 'gs_sconto_livello_corrente' )
						&& gs_sconto_livello_corrente( $cli->ID ) === $c['livello_sconto'] ) {
						$pct = gs_sconto_percentuale( $cli->ID );
						if ( $pct > 0 ) {
							$scontato = $c['prezzo'] * ( 1 - $pct / 100 );
							echo '<p class="gs-sconto-evidenza">🎓 Ha ' . number_format( $pct, 0 ) . '% di sconto accumulato su questo livello'
								. ( $c['prezzo'] > 0 ? ' — quota scontata: € ' . number_format( $scontato, 2, ',', '.' ) : '' ) . '</p>';
							echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-sconto-applica" data-pren="' . (int) $pr->ID . '">🎓 Sconto applicato / corso fatto</button> ';
							echo '<span class="gs-sconto-applica-msg gs-richiesta-esito"></span></p>';
							echo '<p class="gs-hint">Clicca solo dopo aver confermato che ha davvero partecipato: azzera lo sconto e la fa passare al livello successivo.</p>';
						}
					}

					// Attestato: sui corsi di tipo Base, Intermedio o Professionale (gs_cal_tipo_ha_attestato).
					if ( gs_cal_tipo_ha_attestato( $c['tipo'] ) ) {
						$assegnato = '1' === get_post_meta( $pr->ID, 'gs_cal_attestato', true );
						$titolo_att = gs_cal_attestato_titolo( $c['tipo'] );
						echo '<p>';
						echo '<button class="gs-btn gs-btn-sm' . ( $assegnato ? '' : ' gs-btn-verde' ) . ' gs-cal-attestato-toggle" data-pren="' . (int) $pr->ID . '">' . ( $assegnato ? 'Revoca attestato' : '🎖️ Assegna ' . esc_html( $titolo_att ) ) . '</button> ';
						if ( $assegnato ) {
							echo '<a class="gs-btn gs-btn-sm gs-btn-ghost" href="' . esc_url( gs_cal_attestato_url( $pr->ID ) ) . '" target="_blank" rel="noopener">Apri/Stampa attestato</a> ';
						}
						echo '<span class="gs-cal-attestato-msg gs-richiesta-esito"></span></p>';
					}
					echo '</div></details>';
				}
				echo '</div>';
			} else {
				echo '<p class="gs-hint">Nessuna prenotazione.</p>';
			}
			echo '</div></details>';
		}
		echo '</div>';
	}
	echo gs_box_close();

	// --- Lista d'attesa ---
	echo gs_box_open( 'Lista d\'attesa' );
	echo '<p class="gs-hint">Chi non può partecipare ma è giustificato può essere messo in lista d\'attesa. Quando si libera una data, proponigliela: parte una email automatica. Se ha già versato l\'acconto, non dovrà rifarlo. Ogni riga è rossa finché resta in attesa; quando risponde di sì clicca "✅ Ha accettato" e la vedi diventare verde — la prenotazione passa subito su quella data.</p>';
	$attesa = gs_cal_prenotazioni( 0, array( 'lista_attesa' ) );
	if ( ! $attesa ) {
		echo '<p class="gs-hint">Nessuno in lista d\'attesa.</p>';
	} else {
		$futuri = gs_cal_corsi( true );
		echo '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Cliente</th><th>Acconto già versato</th><th>Proponi data</th></tr></thead><tbody>';
		foreach ( $attesa as $pr ) {
			$cli     = get_userdata( (int) get_post_meta( $pr->ID, 'gs_cliente', true ) );
			$acc     = (float) get_post_meta( $pr->ID, 'gs_acconto_versato', true );
			$offerta = (int) get_post_meta( $pr->ID, 'gs_data_offerta', true );
			echo '<tr class="gs-cal-attesa-rossa" data-pren="' . (int) $pr->ID . '">';
			echo '<td>' . esc_html( $cli ? $cli->display_name : '—' ) . '</td>';
			echo '<td>' . ( $acc > 0 ? 'Sì (€ ' . number_format( $acc, 2, ',', '.' ) . ')' : 'No' ) . '</td>';
			echo '<td><select class="gs-cal-offri-corso"><option value="">— scegli data —</option>';
			foreach ( $futuri as $fp ) {
				$fc = gs_cal_corso_get( $fp->ID );
				echo '<option value="' . (int) $fp->ID . '" ' . selected( $offerta, $fp->ID, false ) . '>' . esc_html( date_i18n( 'j/m/Y', strtotime( $fc['data'] ) ) ) . '</option>';
			}
			echo '</select> <button class="gs-btn gs-btn-sm gs-cal-offri">Invia proposta</button>';
			if ( $offerta ) {
				$fc = gs_cal_corso_get( $offerta );
				echo ' <span class="gs-hint">Proposta: ' . esc_html( date_i18n( 'j/m/Y', strtotime( $fc['data'] ) ) ) . '</span>';
				echo ' <button class="gs-btn gs-btn-sm gs-btn-verde gs-cal-offerta-accettata">✅ Ha accettato</button>';
			}
			echo ' <span class="gs-cal-offri-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
	}
	echo gs_box_close();

	// --- Riepilogo pagamenti per cliente ---
	echo gs_box_open( 'Riepilogo pagamenti per cliente' );
	echo '<p class="gs-hint">Totale versato da ogni cliente (acconti + saldi) su tutte le sue prenotazioni. Rosso = non ha ancora saldato quanto dovuto sulle prenotazioni attive; verde = ha saldato per intero.</p>';
	$per_cliente = array();
	foreach ( gs_cal_prenotazioni() as $pr ) {
		$cid   = (int) get_post_meta( $pr->ID, 'gs_cliente', true );
		$stato = get_post_meta( $pr->ID, 'gs_stato', true ) ?: 'prenotato';
		if ( ! isset( $per_cliente[ $cid ] ) ) { $per_cliente[ $cid ] = array( 'pagato' => 0, 'dovuto' => 0 ); }
		$per_cliente[ $cid ]['pagato'] += gs_cal_pren_pagato( $pr->ID );
		if ( in_array( $stato, array( 'prenotato', 'confermato' ), true ) ) {
			$corso = gs_cal_corso_get( (int) get_post_meta( $pr->ID, 'gs_corso', true ) );
			$per_cliente[ $cid ]['dovuto'] += (float) $corso['prezzo'];
		}
	}
	if ( ! $per_cliente ) {
		echo '<p class="gs-hint">Ancora nessun pagamento registrato.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="15"><thead><tr><th>Cliente</th><th>Totale versato</th></tr></thead><tbody>';
		foreach ( $per_cliente as $cid => $d ) {
			$cli = get_userdata( $cid );
			$cls = '';
			if ( $d['dovuto'] > 0 ) {
				$cls = $d['pagato'] >= $d['dovuto'] ? ' gs-cal-saldo-verde' : ' gs-cal-saldo-rosso';
			}
			echo '<tr class="' . esc_attr( trim( $cls ) ) . '"><td>' . esc_html( $cli ? $cli->display_name : '—' ) . '</td><td>€ ' . number_format( $d['pagato'], 2, ',', '.' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo gs_box_close();

	// --- Cestino prenotazioni: quelle che una sfoglina ha tolto dalla sua lista ---
	$cestino_pren = get_posts( array(
		'post_type'      => 'gs_prenotazione',
		'post_status'    => 'trash',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	echo '<details class="gs-todo-cestino"><summary>🗑️ Prenotazioni nel cestino (' . count( $cestino_pren ) . ')</summary>';
	if ( ! $cestino_pren ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<p class="gs-hint">Prenotazioni concluse che la sfoglina ha tolto dalla sua lista personale — recuperabili.</p>';
		echo '<ul class="gs-todo-list">';
		foreach ( $cestino_pren as $pr ) {
			$cli = get_userdata( (int) get_post_meta( $pr->ID, 'gs_cliente', true ) );
			$c   = gs_cal_corso_get( (int) get_post_meta( $pr->ID, 'gs_corso', true ) );
			echo '<li class="gs-todo-item" data-pren="' . (int) $pr->ID . '"><span><strong>' . esc_html( $cli ? $cli->display_name : '—' ) . '</strong> — ' . esc_html( $c['titolo'] ) . '</span>'
				. '<button class="gs-todo-ripristina gs-cal-pren-ripristina" title="Ripristina">↺ Ripristina</button></li>';
		}
		echo '</ul>';
	}
	echo '</details>';
}

add_action( 'wp_ajax_gs_cal_salva_cfg', 'gs_ajax_cal_salva_cfg' );
function gs_ajax_cal_salva_cfg() {
	gs_cal_guard();
	gs_cal_save_settings( array(
		'beneficiario'    => sanitize_text_field( wp_unslash( $_POST['beneficiario'] ?? '' ) ),
		'iban'            => sanitize_text_field( wp_unslash( $_POST['iban'] ?? '' ) ),
		'causale'         => sanitize_text_field( wp_unslash( $_POST['causale'] ?? '' ) ),
		'istruzioni'      => sanitize_textarea_field( wp_unslash( $_POST['istruzioni'] ?? '' ) ),
		'giorni_disdetta' => max( 0, (int) ( $_POST['giorni_disdetta'] ?? 14 ) ),
	) );
	wp_send_json_success( array( 'message' => 'Impostazioni salvate.' ) );
}

// -----------------------------------------------------------------------------
// Promemoria automatico: email il giorno prima del corso a chi ha prenotato
// (stato 'prenotato' o 'confermato'). Gira sul cron giornaliero già esistente
// del plugin (gs_daily_cron). Un flag sul corso evita invii doppi se il cron
// gira più di una volta nello stesso giorno.
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_cal_promemoria_domani' );
function gs_cal_promemoria_domani() {
	$domani = date( 'Y-m-d', current_time( 'timestamp' ) + DAY_IN_SECONDS );
	$corsi  = get_posts( array(
		'post_type'      => 'gs_corso_cal',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'gs_data',
		'meta_value'     => $domani,
	) );
	foreach ( $corsi as $corso_post ) {
		if ( get_post_meta( $corso_post->ID, 'gs_promemoria_inviato', true ) ) {
			continue; // già inviato per questa data.
		}
		$corso = gs_cal_corso_get( $corso_post->ID );
		foreach ( gs_cal_prenotazioni( $corso_post->ID, array( 'prenotato', 'confermato' ) ) as $p ) {
			$pren = gs_cal_pren_get( $p->ID );
			$u    = get_userdata( $pren['cliente'] );
			if ( ! $u ) { continue; }
			$corpo  = "Ciao " . $u->display_name . ",\n\n";
			$corpo .= "un promemoria: domani hai il corso all'Accademia della Sfoglia";
			if ( $corso['titolo'] ) { $corpo .= " — " . $corso['titolo']; }
			$corpo .= ".\n\n";
			$corpo .= "• Data: " . date_i18n( 'l j F Y', strtotime( $corso['data'] ) ) . "\n";
			$corpo .= "• Orario: " . $corso['inizio'] . ( $corso['fine'] ? ' – ' . $corso['fine'] : '' ) . "\n\n";
			$corpo .= "A domani,\nAccademia della Sfoglia";
			if ( function_exists( 'gs_mail_progetto' ) ) {
				gs_mail_progetto( $u->ID, 'calendario', 'Promemoria: il tuo corso è domani — Accademia della Sfoglia', $corpo );
			} elseif ( $u->user_email ) {
				wp_mail( $u->user_email, 'Promemoria: il tuo corso è domani — Accademia della Sfoglia', $corpo );
			}
		}
		update_post_meta( $corso_post->ID, 'gs_promemoria_inviato', 1 );
	}
}

// =============================================================================
// ATTESTATI — assegnazione manuale, sui corsi in presenza di tipo Base,
// Intermedio o Professionale (gs_cal_tipo_ha_attestato), un attestato per
// ogni prenotazione/cliente. Stesso schema del diploma di Area Professionale
// (gs_pro_diploma_toggle in area-pro.php), qui applicato alle prenotazioni
// del Calendario invece che ai corsi individuali. Chi riceve l'attestato di
// Base o Intermedio entra nel Registro degli Amatori; chi riceve quello di
// Professionale entra nel Registro dei Professionisti (vedi registro.php) —
// esattamente come descritto ne "I Corsi di Formazione" della pagina
// pubblica Diventa Supporter.
// =============================================================================

/**
 * Assegna/revoca l'attestato per una prenotazione e restituisce il nuovo
 * stato. Logica pura, separata dall'AJAX per poterla testare.
 */
function gs_cal_attestato_toggle( $pren_id ) {
	$nuovo = '1' !== get_post_meta( $pren_id, 'gs_cal_attestato', true );
	update_post_meta( $pren_id, 'gs_cal_attestato', $nuovo ? '1' : '' );
	if ( $nuovo ) {
		update_post_meta( $pren_id, 'gs_cal_attestato_data', date_i18n( 'Y-m-d' ) );
	}
	return $nuovo;
}

add_action( 'wp_ajax_gs_cal_attestato', 'gs_ajax_cal_attestato' );
function gs_ajax_cal_attestato() {
	gs_cal_guard();
	$pid = isset( $_POST['pren'] ) ? (int) $_POST['pren'] : 0;
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) { wp_send_json_error( array( 'message' => 'Prenotazione non valida.' ) ); }
	$nuovo = gs_cal_attestato_toggle( $pid );
	wp_send_json_success( array(
		'assegnato' => $nuovo,
		'message'   => $nuovo ? 'Attestato assegnato.' : 'Attestato revocato.',
	) );
}

/** URL firmato per aprire/stampare l'attestato di una prenotazione. */
function gs_cal_attestato_url( $pren_id ) {
	return wp_nonce_url( add_query_arg( array( 'gs_attestato_cal' => (int) $pren_id ), admin_url( 'admin.php' ) ), 'gs_attestato_cal_' . $pren_id );
}

/** Intercetta la richiesta di stampa dell'attestato (?gs_attestato_cal=ID). */
add_action( 'admin_init', 'gs_handle_attestato_cal_stampa' );
function gs_handle_attestato_cal_stampa() {
	if ( empty( $_GET['gs_attestato_cal'] ) ) {
		return;
	}
	$pid = (int) $_GET['gs_attestato_cal'];
	if ( 'gs_prenotazione' !== get_post_type( $pid ) ) {
		wp_die( 'Attestato non trovato.' );
	}
	check_admin_referer( 'gs_attestato_cal_' . $pid );

	$pren       = gs_cal_pren_get( $pid );
	$puo_vedere = ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) || ( get_current_user_id() === $pren['cliente'] );
	if ( ! $puo_vedere ) {
		wp_die( 'Non hai il permesso di vedere questo attestato.' );
	}
	if ( '1' !== get_post_meta( $pid, 'gs_cal_attestato', true ) ) {
		wp_die( 'L\'attestato non è ancora stato assegnato.' );
	}

	$corso      = gs_cal_corso_get( $pren['corso'] );
	$u          = get_user_by( 'id', $pren['cliente'] );
	$nome       = $u ? $u->display_name : '';
	$data       = get_post_meta( $pid, 'gs_cal_attestato_data', true );
	$data_lbl   = $data ? date_i18n( 'j F Y', strtotime( $data ) ) : date_i18n( 'j F Y' );
	$sigillo    = gs_certificato_logo_url( 'assets/img/sigillo-alta-qualita.png' );
	$ha_sigillo = gs_certificato_logo_esiste( 'assets/img/sigillo-alta-qualita.png' );
	$titolo_att = gs_cal_attestato_titolo( $corso['tipo'] );
	$testi_corpo = array(
		'base'          => "ha frequentato il Corso Base presso l'Accademia della Sfoglia, muovendo i primi passi nella sfoglia fatta a mano.",
		'intermedio'    => "ha frequentato il Corso Intermedio presso l'Accademia della Sfoglia, affinando gesti, impasti e tecnica.",
		'professionale' => "ha frequentato l'intero Corso Professionale in presenza presso l'Accademia della Sfoglia, superando l'esame di valutazione della Maestra Rina Poletti.",
	);
	$testo_corpo = $testi_corpo[ $corso['tipo'] ] ?? "ha frequentato il corso presso l'Accademia della Sfoglia.";

	header( 'Content-Type: text/html; charset=utf-8' );
	?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title><?php echo esc_html( $titolo_att ); ?> — <?php echo esc_html( $nome ); ?></title>
	<?php echo gs_certificato_css(); ?>
</head>
<body onload="window.print()">
	<div class="page">
		<div class="diploma-wrap">
			<div class="diploma">
				<span class="corner tl"></span><span class="corner tr"></span>
				<span class="corner bl"></span><span class="corner br"></span>

				<p class="eyebrow-top">Accademia della Sfoglia</p>

				<?php if ( $ha_sigillo ) : ?>
					<div class="sigillo-wrap">
						<img class="sigillo" src="<?php echo esc_url( $sigillo ); ?>" alt="Marchio Accademia della Sfoglia">
					</div>
				<?php endif; ?>

				<div class="chip"><?php echo esc_html( $titolo_att ); ?></div>
				<p class="sottotitolo"><?php echo esc_html( $corso['titolo'] ? $corso['titolo'] : $titolo_att ); ?></p>

				<div class="divider">
					<span class="line"></span>
					<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
						<rect x="8" y="21" width="32" height="6" rx="3" fill="currentColor"/>
						<circle cx="6" cy="24" r="5" fill="currentColor"/>
						<circle cx="42" cy="24" r="5" fill="currentColor"/>
					</svg>
					<span class="line"></span>
				</div>

				<div class="nome"><?php echo esc_html( $nome ); ?></div>

				<p class="testo"><?php echo esc_html( $testo_corpo ); ?></p>

				<div class="firma-riga">
					<div class="firma">
						<div class="nome-firma">Rina Poletti</div>
						<div class="etichetta">Maestra sfoglina</div>
					</div>
					<div class="data-riga">
						<div class="valore"><?php echo esc_html( $data_lbl ); ?></div>
						<div class="etichetta">Data</div>
					</div>
				</div>
			</div>

			<p class="noprint"><button onclick="window.print()">🖨️ Stampa / Salva come PDF</button></p>
		</div>
	</div>
</body>
</html><?php
	exit;
}
