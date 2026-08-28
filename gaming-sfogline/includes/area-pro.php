<?php
/**
 * area-pro.php — Lotto 3: AREA PROFESSIONALE
 * Corsi individuali e privati con un docente dell'Accademia (Rina Poletti,
 * Bruno Cingolani, o chiunque altro il titolare indichi nel campo "Docente"
 * di ogni corso), uno per sfoglina, con compiti giornalieri. Il gestore
 * controlla tutti i parametri in forma privata dal pannello; i corsi non
 * sono visibili alle altre sfogline; il gestore può "oscurare" in qualsiasi
 * momento i dati scambiati con una singola sfoglina. Il Diploma di fine
 * percorso (vedi più sotto) riporta sempre il nome del docente vero di
 * quel corso, non un nome fisso: stesso trattamento per ogni docente.
 *
 * Struttura:
 * - CPT gs_corso (privato): un corso per sfoglina (meta gs_corso_utente).
 * - Compiti giornalieri salvati come meta array gs_compiti del corso:
 *     { id, data, testo, fatto, data_fatto, nota (sfoglina), feedback (docente) }
 * - Parametri corso: gs_stato (attivo|sospeso), gs_oscurato (0|1), gs_docente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// CPT privato del corso
// -----------------------------------------------------------------------------
add_action( 'init', 'gs_register_corso_cpt' );
function gs_register_corso_cpt() {
	register_post_type( 'gs_corso', array(
		'labels'          => array( 'name' => 'Corsi Online', 'singular_name' => 'Corso' ),
		'public'          => false,
		'show_ui'         => false,
		'show_in_menu'    => false,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------

/** Il corso (post) assegnato a una sfoglina, o null. */
function gs_get_corso_utente( $user_id ) {
	$q = get_posts( array(
		'post_type'      => 'gs_corso',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => 'gs_corso_utente',
		'meta_value'     => (int) $user_id,
	) );
	return $q ? $q[0] : null;
}

function gs_corso_oscurato( $corso_id ) {
	return '1' === get_post_meta( $corso_id, 'gs_oscurato', true );
}
function gs_corso_stato( $corso_id ) {
	$s = get_post_meta( $corso_id, 'gs_stato', true );
	return $s ? $s : 'attivo';
}
function gs_get_compiti( $corso_id ) {
	$c = get_post_meta( $corso_id, 'gs_compiti', true );
	return is_array( $c ) ? $c : array();
}
function gs_save_compiti( $corso_id, $compiti ) {
	update_post_meta( $corso_id, 'gs_compiti', array_values( $compiti ) );
}

/** Cestino dei compiti eliminati: non sono un CPT, niente wp_trash_post(). */
function gs_get_compiti_cestino( $corso_id ) {
	$c = get_post_meta( $corso_id, 'gs_compiti_cestino', true );
	return is_array( $c ) ? $c : array();
}
function gs_save_compiti_cestino( $corso_id, $cestino ) {
	if ( count( $cestino ) > 30 ) { $cestino = array_slice( $cestino, -30 ); }
	update_post_meta( $corso_id, 'gs_compiti_cestino', array_values( $cestino ) );
}
/** Sposta un compito dagli attivi al cestino. */
function gs_compito_sposta_nel_cestino( $corso_id, $id ) {
	$compiti = gs_get_compiti( $corso_id );
	$cestino = gs_get_compiti_cestino( $corso_id );
	foreach ( $compiti as $c ) {
		if ( $c['id'] === $id ) { $c['ts'] = time(); $cestino[] = $c; }
	}
	$compiti = array_filter( $compiti, function ( $c ) use ( $id ) { return $c['id'] !== $id; } );
	gs_save_compiti( $corso_id, $compiti );
	gs_save_compiti_cestino( $corso_id, $cestino );
}
/** Riporta un compito dal cestino agli attivi. */
function gs_compito_ripristina_dal_cestino( $corso_id, $id ) {
	$cestino = gs_get_compiti_cestino( $corso_id );
	$compiti = gs_get_compiti( $corso_id );
	foreach ( $cestino as $c ) {
		if ( $c['id'] === $id ) { unset( $c['ts'] ); $compiti[] = $c; }
	}
	$cestino = array_filter( $cestino, function ( $c ) use ( $id ) { return $c['id'] !== $id; } );
	gs_save_compiti( $corso_id, $compiti );
	gs_save_compiti_cestino( $corso_id, $cestino );
}

/** Vero se l'utente corrente può gestire l'Area Professionale (come il pannello). */
function gs_pro_can_manage() {
	return function_exists( 'gs_can_manage' ) && gs_can_manage();
}

/** Modifica giorno/titolo/testo di un compito esistente. */
function gs_compito_modifica( $corso_id, $id, $data, $titolo, $testo ) {
	$compiti = gs_get_compiti( $corso_id );
	$trovato = false;
	foreach ( $compiti as &$c ) {
		if ( $c['id'] === $id ) {
			$c['data']   = $data;
			$c['titolo'] = $titolo;
			$c['testo']  = $testo;
			$trovato     = true;
		}
	}
	unset( $c );
	if ( $trovato ) { gs_save_compiti( $corso_id, $compiti ); }
	return $trovato;
}

// =============================================================================
// PIANO DI STUDIO UFFICIALE — dal principiante alla Laurea in Sfoglia
// =============================================================================

/**
 * Curriculum a gradi. Ogni voce è un compito con:
 *  - grado   : numero del grado (1..6, 7 = Laurea)
 *  - grado_nome
 *  - giorno  : offset in giorni dalla data di inizio
 *  - titolo  : titolo breve dell'azione
 *  - testo   : compito preciso
 */
function gs_piano_studio() {
	$g = array(
		1 => 'Grado 1 — Le Basi della Sfoglia',
		2 => 'Grado 2 — La Tiratura al Matterello',
		3 => 'Grado 3 — Le Paste Ripiene',
		4 => 'Grado 4 — Le Forme della Tradizione',
		5 => 'Grado 5 — Tecnica Avanzata e Stagionalità',
		6 => 'Grado 6 — Maestria e Trasmissione',
		7 => 'Esame di Laurea',
	);

	$p = array();
	$add = function ( $grado ) use ( &$p, $g ) {
		$args = func_get_args();
		$p[] = array( 'grado' => $grado, 'grado_nome' => $g[ $grado ], 'giorno' => $args[1], 'titolo' => $args[2], 'testo' => $args[3] );
	};

	// --- Grado 1 ---
	$add( 1, 0,  'Conoscere gli ingredienti', 'Studia farina (00 e semola), uova e proporzioni classiche (1 uovo ogni 100 g di farina). Scrivi nel Diario cosa hai capito su qualità e freschezza.' );
	$add( 1, 2,  'La fontana e l\'impasto', 'Prepara la fontana su spianatoia, incorpora le uova e impasta 10 minuti fino a impasto liscio ed elastico. Fotografa il risultato.' );
	$add( 1, 4,  'Il riposo dell\'impasto', 'Fai riposare l\'impasto coperto 30 minuti. Annota la differenza di elasticità prima e dopo il riposo.' );
	$add( 1, 6,  'La prima sfoglia', 'Stendi la tua prima sfoglia col matterello a spessore uniforme di circa 2 mm. Invia foto in controluce.' );
	$add( 1, 9,  'Verifica del Grado 1', 'Ripeti l\'impasto e una sfoglia da 2 mm senza aiuti. Autovaluta colore, elasticità e uniformità.' );

	// --- Grado 2 ---
	$add( 2, 12, 'La tecnica del matterello', 'Impara a "tirare" allargando la sfoglia con movimenti dal centro ai bordi. Esercizio: 20 minuti di sola tiratura.' );
	$add( 2, 15, 'Spessore uniforme', 'Tira una sfoglia a 1,5 mm cercando spessore omogeneo su tutta la superficie. Misura in 5 punti diversi.' );
	$add( 2, 18, 'Umidità e temperatura', 'Osserva come cambia la sfoglia con ambiente più caldo/umido; regola l\'infarinatura. Scrivi le tue osservazioni nel Diario.' );
	$add( 2, 21, 'Il taglio delle tagliatelle', 'Arrotola la sfoglia e taglia tagliatelle da 6-7 mm. Invia foto del nido e valuta la regolarità.' );
	$add( 2, 24, 'Verifica del Grado 2', 'Prepara 250 g di tagliatelle a regola d\'arte partendo da zero, entro un tempo definito con chi ti segue.' );

	// --- Grado 3 ---
	$add( 3, 28, 'La sfoglia per il ripieno', 'Tira una sfoglia sottile (1 mm) adatta alle paste ripiene, mantenendola morbida per la chiusura.' );
	$add( 3, 31, 'I tortellini', 'Prepara i tortellini: dado di ripieno, chiusura attorno al dito, sigillatura. Invia foto di 6 pezzi.' );
	$add( 3, 34, 'Tortelloni e cappelletti', 'Realizza tortelloni e cappelletti curando dimensione costante e sigillatura senza aria.' );
	$add( 3, 37, 'Il ripieno di famiglia', 'Componi un ripieno seguendo una ricetta di famiglia o tradizionale; annota dosi e bilanciamento dei sapori.' );
	$add( 3, 40, 'Verifica del Grado 3', 'Produci 30 paste ripiene uniformi e ben sigillate nel tempo concordato.' );

	// --- Grado 4 ---
	$add( 4, 44, 'Garganelli e maltagliati', 'Realizza garganelli col pettine e maltagliati; cura la texture rigata dei garganelli.' );
	$add( 4, 47, 'Lasagne e sfoglia verde', 'Prepara la sfoglia verde agli spinaci e i fogli per lasagne, curando lo spessore per la cottura al forno.' );
	$add( 4, 50, 'Strichetti e forme regionali', 'Studia e realizza strichetti e almeno una forma regionale a scelta; documenta l\'origine nel Diario.' );
	$add( 4, 53, 'Verifica del Grado 4', 'Presenta un vassoio con tre formati diversi eseguiti correttamente.' );

	// --- Grado 5 ---
	$add( 5, 58, 'La sfoglia "a velo"', 'Tira una sfoglia sottilissima (velo) mantenendo tenuta e uniformità. Prova del controluce.' );
	$add( 5, 62, 'Impasti colorati e aromatizzati', 'Prepara due impasti alternativi (es. barbabietola, nero di seppia, zafferano) mantenendo la corretta idratazione.' );
	$add( 5, 66, 'Adattarsi alla stagione', 'Esegui la stessa sfoglia in due condizioni ambientali diverse, regolando idratazione e infarinatura.' );
	$add( 5, 70, 'Velocità e costanza', 'Cronometra la preparazione di 250 g di tagliatelle mantenendo qualità costante. Migliora il tempo del Grado 2.' );
	$add( 5, 74, 'Verifica del Grado 5', 'Sfoglia a velo + un formato ripieno, valutati su colore, uniformità, forma e presentazione.' );

	// --- Grado 6 ---
	$add( 6, 80, 'Produzione in quantità', 'Pianifica e prepara paste fresche per 10 coperti, gestendo tempi e conservazione.' );
	$add( 6, 85, 'Impiattamento e presentazione', 'Cura la presentazione finale di un piatto di pasta fresca; fotografa per la Vetrina.' );
	$add( 6, 90, 'Trasmettere la tecnica', 'Prepara una breve spiegazione (scritta o a voce) per insegnare un passaggio a una principiante.' );
	$add( 6, 95, 'Il piatto d\'autore', 'Progetta un piatto personale che rappresenti il tuo stile, dall\'impasto alla presentazione.' );

	// --- Laurea ---
	$add( 7, 100, 'Esame di Laurea — Menu degustazione', 'Prepara un menu di tre paste fresche diverse (una ripiena, una tagliata, una speciale), valutato sui quattro criteri.' );
	$add( 7, 102, 'Esame di Laurea — Ricetta di famiglia', 'Presenta la tua ricetta di famiglia originale, con racconto e dimostrazione della tecnica.' );
	$add( 7, 104, 'Laurea in Sfoglia', 'Discussione finale con chi ti segue e proclamazione. Consegna del titolo di Maestra Sfoglina dell\'Accademia.' );

	return $p;
}

/** Carica il piano di studio in un corso, con date a partire da $inizio (Y-m-d). */
function gs_pro_applica_piano( $corso_id, $inizio ) {
	$ts = strtotime( $inizio );
	if ( ! $ts ) { $ts = time(); }
	$compiti = gs_get_compiti( $corso_id );
	foreach ( gs_piano_studio() as $voce ) {
		$compiti[] = array(
			'id'       => uniqid( 'c' ),
			'data'     => date( 'Y-m-d', $ts + $voce['giorno'] * DAY_IN_SECONDS ),
			'grado'    => $voce['grado'],
			'titolo'   => $voce['grado_nome'] . ' · ' . $voce['titolo'],
			'testo'    => $voce['testo'],
			'fatto'    => false,
			'nota'     => '',
			'feedback' => '',
		);
	}
	gs_save_compiti( $corso_id, $compiti );
	return count( gs_piano_studio() );
}

add_action( 'wp_ajax_gs_pro_carica_piano', 'gs_ajax_pro_carica_piano' );
function gs_ajax_pro_carica_piano() {
	gs_pro_guard();
	$cid    = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	$inizio = isset( $_POST['inizio'] ) ? sanitize_text_field( wp_unslash( $_POST['inizio'] ) ) : date( 'Y-m-d' );
	if ( 'gs_corso' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	$n = gs_pro_applica_piano( $cid, $inizio );
	wp_send_json_success( array( 'message' => 'Piano di studio caricato: ' . $n . ' azioni assegnate.' ) );
}

// =============================================================================
// LATO SFOGLINA — [gs_area_pro]
// =============================================================================
add_shortcode( 'gs_area_pro', 'gs_sc_area_pro' );
function gs_sc_area_pro() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	if ( $g = gs_blackout_gate() ) { return $g; }

	$uid   = get_current_user_id();
	$corso = gs_get_corso_utente( $uid );

	if ( ! $corso ) {
		return gs_box_open( '🎓 Corsi Online' )
			. '<p>Non puoi frequentare l\'aula in presenza? Possiamo offrirti un corso online individuale, con un docente reale dell\'Accademia e compiti assegnati giorno per giorno. Se ti interessa, parlane con la segreteria.</p>'
			. gs_box_close();
	}
	if ( 'sospeso' === gs_corso_stato( $corso->ID ) ) {
		return gs_box_open( '🎓 Corsi Online' )
			. '<p>Il tuo corso è momentaneamente sospeso.</p>' . gs_box_close();
	}
	if ( gs_corso_oscurato( $corso->ID ) ) {
		return gs_box_open( '🎓 Corsi Online' )
			. '<p>I contenuti del tuo corso sono momentaneamente non disponibili.</p>' . gs_box_close();
	}

	$docente = get_post_meta( $corso->ID, 'gs_docente', true );
	$docente = $docente ? $docente : 'Rina Poletti';
	$compiti = gs_get_compiti( $corso->ID );
	$oggi    = date_i18n( 'Y-m-d' );

	// Ordina per data.
	usort( $compiti, function ( $a, $b ) { return strcmp( $a['data'], $b['data'] ); } );

	$out  = gs_box_open( '🎓 ' . esc_html( get_the_title( $corso ) ) );
	$out .= gs_sezione_aiuto( 'Segui i compiti nell\'ordine proposto: segna quelli completati e lascia una nota, chi ti segue ti risponde con il suo riscontro. I parametri del corso li imposta il titolare in forma privata, tu vedi solo il tuo percorso personale.' );
	$out .= '<p class="gs-hint">Corso individuale con <strong>' . esc_html( $docente ) . '</strong>. Qui trovi i tuoi compiti giornalieri: segna quelli completati e lascia una nota; ti risponde con il suo riscontro.</p>';

	if ( '1' === get_post_meta( $corso->ID, 'gs_diploma_rina', true ) ) {
		$sigillo_url = gs_certificato_logo_url( 'assets/img/diploma-rina.png' );
		$out .= '<div style="background:#fdf6e3;border:1px solid #e0c98a;border-radius:8px;padding:16px;margin:14px 0;text-align:center">';
		if ( gs_certificato_logo_esiste( 'assets/img/diploma-rina.png' ) ) {
			$out .= '<img src="' . esc_url( $sigillo_url ) . '" alt="Marchio Accademia della Sfoglia" style="width:90px;height:90px;margin-bottom:8px">';
		}
		$out .= '<p style="font-weight:700;color:#7a4a1a;margin:4px 0">🏅 Hai conquistato il Diploma con ' . esc_html( $docente ) . '!</p>';
		$out .= '<p class="gs-hint">Hai completato tutto il percorso con ' . esc_html( $docente ) . ', dall\'inizio alla fine.</p>';
		$out .= '<p><a class="gs-btn gs-btn-sm" href="' . esc_url( gs_diploma_url( $corso->ID ) ) . '" target="_blank">🖨️ Vedi / stampa il tuo diploma</a></p>';
		$out .= '</div>';
	}

	if ( empty( $compiti ) ) {
		$out .= '<p>Nessun compito assegnato per ora. Torna a controllare!</p>';
	} else {
		$out .= '<div class="gs-todo-riquadro"><div class="gs-compiti">';
		foreach ( $compiti as $c ) {
			$data_lbl = date_i18n( 'j/m/Y', strtotime( $c['data'] ) );
			$oggi_cls = ( $c['data'] === $oggi ) ? ' gs-compito-oggi' : '';
			$fatto    = ! empty( $c['fatto'] );
			$out .= '<div class="gs-compito' . $oggi_cls . '" data-id="' . esc_attr( $c['id'] ) . '">';
			$out .= '<div class="gs-compito-head"><span class="gs-compito-data">' . esc_html( $data_lbl ) . ( $c['data'] === $oggi ? ' · oggi' : '' ) . '</span>'
				. '<label class="gs-compito-check"><input type="checkbox" class="gs-compito-fatto" ' . checked( $fatto, true, false ) . '> completato</label></div>';
			$out .= '<p class="gs-compito-testo">';
			if ( ! empty( $c['titolo'] ) ) { $out .= '<strong>' . esc_html( $c['titolo'] ) . '</strong><br>'; }
			$out .= nl2br( esc_html( $c['testo'] ) ) . '</p>';
			$out .= '<label class="gs-hint">La tua nota</label>';
			$out .= '<textarea class="gs-compito-nota" rows="2" style="width:100%">' . esc_textarea( isset( $c['nota'] ) ? $c['nota'] : '' ) . '</textarea>';
			if ( ! empty( $c['feedback'] ) ) {
				$out .= '<div class="gs-compito-feedback"><strong>' . esc_html( $docente ) . ':</strong> ' . nl2br( esc_html( $c['feedback'] ) ) . '</div>';
			}
			$out .= '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-compito-salva">Salva</button> <span class="gs-compito-msg gs-richiesta-esito"></span></p>';
			$out .= '</div>';
		}
		$out .= '</div></div>';
	}
	$out .= gs_box_close();
	return $out;
}

/** La sfoglina salva stato e nota di un proprio compito (se non oscurato). */
add_action( 'wp_ajax_gs_compito_salva', 'gs_ajax_compito_salva' );
function gs_ajax_compito_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid   = get_current_user_id();
	$corso = $uid ? gs_get_corso_utente( $uid ) : null;
	if ( ! $corso || gs_corso_oscurato( $corso->ID ) || 'sospeso' === gs_corso_stato( $corso->ID ) ) {
		wp_send_json_error( array( 'message' => 'Non disponibile.' ) );
	}
	$cid   = isset( $_POST['compito'] ) ? sanitize_text_field( wp_unslash( $_POST['compito'] ) ) : '';
	$fatto = ! empty( $_POST['fatto'] );
	$nota  = isset( $_POST['nota'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nota'] ) ) : '';

	$compiti = gs_get_compiti( $corso->ID );
	$trovato = false;
	foreach ( $compiti as &$c ) {
		if ( $c['id'] === $cid ) {
			$c['fatto'] = $fatto;
			$c['nota']  = $nota;
			if ( $fatto && empty( $c['data_fatto'] ) ) { $c['data_fatto'] = date_i18n( 'Y-m-d' ); }
			$trovato = true;
		}
	}
	unset( $c );
	if ( ! $trovato ) { wp_send_json_error( array( 'message' => 'Compito non trovato.' ) ); }
	gs_save_compiti( $corso->ID, $compiti );
	wp_send_json_success( array( 'message' => 'Salvato.' ) );
}

// =============================================================================
// LATO GESTORE — pannello Area Professionale
// =============================================================================
function gs_pannello_area_pro() {
	if ( ! gs_pro_can_manage() ) { return; }

	$corsi = get_posts( array( 'post_type' => 'gs_corso', 'post_status' => 'publish', 'posts_per_page' => -1 ) );

	echo gs_box_open( 'Area Online — Corsi Individuali' );
	?>
	<p class="gs-hint">Corsi <strong>individuali e privati</strong>: uno per sfoglina, non visibili alle altre. Assegni compiti giornalieri, dai riscontri, e puoi <strong>oscurare</strong> i dati scambiati con una sfoglina in qualsiasi momento.</p>

	<!-- Crea nuovo corso -->
	<form class="gs-form gs-form-nuovo-corso" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">
		<strong>Nuovo corso</strong>
		<p><label>Sfoglina<br>
			<select name="utente" style="min-width:240px">
				<option value="">— scegli —</option>
				<?php
				$assegnati = array();
				foreach ( $corsi as $co ) { $assegnati[] = (int) get_post_meta( $co->ID, 'gs_corso_utente', true ); }
				foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $u ) {
					if ( ! gs_e_sfoglina_vera( $u ) ) { continue; }
					if ( in_array( $u->ID, $assegnati, true ) ) { continue; }
					echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</option>';
				}
				?>
			</select></label></p>
		<p><label>Titolo del corso<br><input type="text" name="titolo" autocomplete="off" value="Corso Online di Sfoglia" style="width:320px"></label></p>
		<p><label>Docente<br><input type="text" name="docente" value="Rina Poletti" style="width:240px"></label></p>
		<p><button class="gs-btn gs-btn-sm gs-crea-corso">Crea corso</button> <span class="gs-corso-msg gs-richiesta-esito"></span></p>
	</form>

	<?php if ( empty( $corsi ) ) : ?>
		<p>Non ci sono ancora corsi. Creane uno qui sopra.</p>
	<?php else :
		$sel = isset( $_GET['gs_corso_sel'] ) ? (int) $_GET['gs_corso_sel'] : (int) $corsi[0]->ID;
		?>
		<form method="get" style="margin:6px 0 12px">
			<?php gs_pannello_preserve_page_field(); ?>
			<label>Corso:
				<select name="gs_corso_sel" onchange="this.form.submit()">
					<?php foreach ( $corsi as $co ) :
						$u = get_user_by( 'id', (int) get_post_meta( $co->ID, 'gs_corso_utente', true ) );
						$nome = $u ? $u->display_name : 'sfoglina';
						$flag = gs_corso_oscurato( $co->ID ) ? ' [oscurato]' : ( 'sospeso' === gs_corso_stato( $co->ID ) ? ' [sospeso]' : '' ); ?>
						<option value="<?php echo (int) $co->ID; ?>" <?php selected( $sel, $co->ID ); ?>>
							<?php echo esc_html( get_the_title( $co ) . ' — ' . $nome . $flag ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		</form>
		<?php gs_pro_render_corso( $sel ); ?>
	<?php endif;
	echo gs_box_close();
}

/** Dettaglio di un corso per il gestore (compiti, feedback, parametri). */
function gs_pro_render_corso( $corso_id ) {
	$corso = get_post( $corso_id );
	if ( ! $corso || 'gs_corso' !== $corso->post_type ) { echo '<p>Corso non valido.</p>'; return; }

	$u        = get_user_by( 'id', (int) get_post_meta( $corso_id, 'gs_corso_utente', true ) );
	$oscurato = gs_corso_oscurato( $corso_id );
	$stato    = gs_corso_stato( $corso_id );
	$compiti  = gs_get_compiti( $corso_id );
	usort( $compiti, function ( $a, $b ) { return strcmp( $a['data'], $b['data'] ); } );
	?>
	<div class="gs-pro-corso" data-corso="<?php echo (int) $corso_id; ?>">
		<p><strong>Sfoglina:</strong> <?php echo esc_html( $u ? $u->display_name : '—' ); ?>
		&nbsp;·&nbsp; <strong>Stato:</strong>
		<span class="gs-pro-stato"><?php echo 'sospeso' === $stato ? 'Sospeso' : 'Attivo'; ?></span></p>

		<p>
			<button class="gs-btn gs-btn-sm gs-pro-stato-toggle<?php echo 'sospeso' === $stato ? ' gs-btn-verde' : ''; ?>" data-stato="<?php echo esc_attr( $stato ); ?>">
				<?php echo 'sospeso' === $stato ? 'Riattiva corso' : 'Sospendi corso'; ?>
			</button>
			<button class="gs-btn gs-btn-sm gs-pro-oscura" data-osc="<?php echo $oscurato ? '1' : '0'; ?>">
				<?php echo $oscurato ? '👁️ Mostra i dati alla sfoglina' : '🙈 Oscura i dati alla sfoglina'; ?>
			</button>
			<button class="gs-btn gs-btn-sm gs-btn-ghost gs-pro-del-corso">Elimina corso</button>
			<span class="gs-pro-msg gs-richiesta-esito"></span>
		</p>
		<?php if ( $oscurato ) : ?>
			<p class="gs-hint" style="color:#b3261e">🙈 I dati di questo corso sono attualmente <strong>oscurati</strong> alla sfoglina: lei non vede compiti né riscontri finché non li mostri di nuovo.</p>
		<?php endif; ?>

		<?php $docente_corso = get_post_meta( $corso_id, 'gs_docente', true ); $docente_corso = $docente_corso ? $docente_corso : 'Rina Poletti'; ?>
		<hr>
		<!-- Diploma di fine percorso: assegnazione manuale, decide il titolare. Il nome del docente è quello vero di questo corso, non fisso. -->
		<?php $diploma = '1' === get_post_meta( $corso_id, 'gs_diploma_rina', true ); ?>
		<div style="background:#fdf6e3;border:1px solid #e0c98a;border-radius:6px;padding:10px 12px;margin-bottom:12px">
			<strong>🏅 Diploma con <?php echo esc_html( $docente_corso ); ?></strong>
			<p class="gs-hint">Il riconoscimento — marchio registrato dell'Accademia — di avere completato tutto il percorso con <?php echo esc_html( $docente_corso ); ?>, dall'inizio alla fine, per diventare sfoglina professionista. Decidi tu quando assegnarlo a questa sfoglina.</p>
			<p>
				<span class="gs-pro-diploma-stato"><?php echo $diploma ? '<span class="gs-stato gs-stato-si">Assegnato</span>' : '<span class="gs-stato gs-stato-off">Non ancora assegnato</span>'; ?></span>
				&nbsp;
				<button class="gs-btn gs-btn-sm gs-pro-diploma-toggle" data-assegnato="<?php echo $diploma ? '1' : '0'; ?>">
					<?php echo $diploma ? 'Revoca il diploma' : 'Assegna il diploma'; ?>
				</button>
				<?php if ( $diploma ) : ?>
					<a class="gs-btn gs-btn-sm gs-btn-ghost" href="<?php echo esc_url( gs_diploma_url( $corso_id ) ); ?>" target="_blank">🖨️ Vedi/stampa il diploma</a>
				<?php endif; ?>
				<span class="gs-pro-diploma-msg gs-richiesta-esito"></span>
			</p>
		</div>
		<!-- Piano di studio ufficiale -->
		<form class="gs-form gs-form-piano" onsubmit="return false" style="background:var(--gs-uovo);padding:10px 12px;border-radius:6px;margin-bottom:12px">
			<details class="gs-corso-descr">
				<summary><strong>Piano di studio ufficiale (fino alla Laurea in Sfoglia)</strong></summary>
				<?php
				$grado_corrente = null;
				foreach ( gs_piano_studio() as $voce ) :
					if ( $voce['grado_nome'] !== $grado_corrente ) :
						if ( null !== $grado_corrente ) { echo '</ul>'; }
						$grado_corrente = $voce['grado_nome'];
						echo '<p style="margin:10px 0 4px"><strong>' . esc_html( $grado_corrente ) . '</strong></p><ul class="gs-missions">';
					endif;
					$e_esame = ( false !== stripos( $voce['titolo'], 'verifica' ) || false !== stripos( $voce['titolo'], 'esame' ) || false !== stripos( $voce['titolo'], 'laurea' ) );
					echo '<li>' . ( $e_esame ? '🎓 ' : '📌 ' ) . esc_html( $voce['titolo'] ) . '</li>';
				endforeach;
				if ( null !== $grado_corrente ) { echo '</ul>'; }
				?>
			</details>
			<p class="gs-hint">Carica in un colpo solo tutte le azioni del percorso a gradi, con le date calcolate dalla data di inizio. Potrai poi modificarle o aggiungerne altre.</p>
			<p><label>Data di inizio<br><input type="date" class="gs-piano-inizio" value="<?php echo esc_attr( date_i18n( 'Y-m-d' ) ); ?>"></label>
			&nbsp; <button class="gs-btn gs-btn-sm gs-carica-piano">Carica il piano di studio</button>
			<span class="gs-piano-msg gs-richiesta-esito"></span></p>
		</form>

		<!-- Nuovo compito giornaliero -->
		<form class="gs-form gs-form-compito" onsubmit="return false">
			<strong>Assegna un compito</strong>
			<p><label>Giorno<br><input type="date" class="gs-compito-data-input" value="<?php echo esc_attr( date_i18n( 'Y-m-d' ) ); ?>"></label></p>
			<p><label>Compito preciso<br><textarea class="gs-compito-testo-input" rows="2" style="width:100%" placeholder="Es. Impastare 300g di farina 00 con 3 uova, tirare la sfoglia a 1,2 mm e inviare foto."></textarea></label></p>
			<p><button class="gs-btn gs-btn-sm gs-add-compito">Aggiungi compito</button></p>
		</form>

		<?php $gs_pro_colore = 'gs-pro-colore-' . ( $corso_id % 8 ); // colore diverso per ogni corso, stabile (calcolato dall'ID) ?>
		<div class="gs-pro-compiti">
			<?php if ( empty( $compiti ) ) : ?>
				<p class="gs-hint">Nessun compito assegnato.</p>
			<?php else : ?>
				<?php foreach ( $compiti as $c ) : ?>
					<div class="gs-pro-compito <?php echo esc_attr( $gs_pro_colore ); ?>" data-id="<?php echo esc_attr( $c['id'] ); ?>" style="border-radius:6px;padding:10px;margin-bottom:10px">
						<p style="margin:0 0 4px">
							<?php echo ! empty( $c['fatto'] ) ? '<span class="gs-stato gs-stato-si">completato</span>' : '<span class="gs-stato gs-stato-off">da fare</span>'; ?>
						</p>
						<p><label><span class="gs-pro-label">Giorno</span><br><input type="date" class="gs-pro-compito-data" value="<?php echo esc_attr( $c['data'] ); ?>"></label></p>
						<p><label><span class="gs-pro-label">Titolo (facoltativo)</span><br><input type="text" class="gs-pro-compito-titolo" value="<?php echo esc_attr( isset( $c['titolo'] ) ? $c['titolo'] : '' ); ?>" style="width:100%"></label></p>
						<p><label><span class="gs-pro-label">Compito</span><br><textarea class="gs-pro-compito-testo" rows="2" style="width:100%"><?php echo esc_textarea( $c['testo'] ); ?></textarea></label></p>
						<p><button class="gs-btn gs-btn-sm gs-pro-compito-modifica" title="Salva modifiche">✎ Salva modifiche</button></p>
						<?php if ( ! empty( $c['nota'] ) ) : ?>
							<p class="gs-hint"><strong>Nota della sfoglina:</strong> <?php echo nl2br( esc_html( $c['nota'] ) ); ?></p>
						<?php endif; ?>
						<span class="gs-pro-label">Il tuo riscontro</span>
						<textarea class="gs-pro-feedback" rows="2" style="width:100%"><?php echo esc_textarea( isset( $c['feedback'] ) ? $c['feedback'] : '' ); ?></textarea>
						<p><button class="gs-btn gs-btn-sm gs-pro-feedback-salva">Salva riscontro</button>
						<button class="gs-btn gs-btn-sm gs-btn-ghost gs-pro-del-compito">Elimina</button>
						<span class="gs-pro-c-msg gs-richiesta-esito"></span></p>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<?php $compiti_cestino = gs_get_compiti_cestino( $corso_id ); ?>
		<details class="gs-todo-cestino"><summary>🗑️ Compiti eliminati (<?php echo count( $compiti_cestino ); ?>)</summary>
		<?php if ( ! $compiti_cestino ) : ?>
			<p class="gs-hint">Il cestino è vuoto.</p>
		<?php else : ?>
			<ul class="gs-todo-list gs-todo-list-cestino">
			<?php foreach ( array_reverse( $compiti_cestino ) as $c ) : ?>
				<li class="gs-todo-item" data-id="<?php echo esc_attr( $c['id'] ); ?>">
					<span><strong><?php echo esc_html( date_i18n( 'j/m/Y', strtotime( $c['data'] ) ) ); ?>:</strong> <?php echo esc_html( wp_trim_words( $c['testo'], 12 ) ); ?></span>
					<button class="gs-todo-ripristina gs-pro-ripristina-compito" title="Ripristina">↺ Ripristina</button>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		</details>
	</div>
	<?php
}

// -----------------------------------------------------------------------------
// AJAX gestore
// -----------------------------------------------------------------------------
function gs_pro_guard() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_pro_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
}

add_action( 'wp_ajax_gs_pro_crea_corso', 'gs_ajax_pro_crea_corso' );
function gs_ajax_pro_crea_corso() {
	gs_pro_guard();
	$uid     = isset( $_POST['utente'] ) ? (int) $_POST['utente'] : 0;
	$titolo  = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : 'Corso Online di Sfoglia';
	$docente = isset( $_POST['docente'] ) ? sanitize_text_field( wp_unslash( $_POST['docente'] ) ) : 'Rina Poletti';
	if ( ! $uid || ! get_userdata( $uid ) ) { wp_send_json_error( array( 'message' => 'Scegli una sfoglina.' ) ); }
	if ( gs_get_corso_utente( $uid ) ) { wp_send_json_error( array( 'message' => 'Questa sfoglina ha già un corso.' ) ); }

	$cid = wp_insert_post( array(
		'post_type'   => 'gs_corso',
		'post_status' => 'publish',
		'post_title'  => $titolo ? $titolo : 'Corso Online di Sfoglia',
	) );
	if ( is_wp_error( $cid ) || ! $cid ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }
	update_post_meta( $cid, 'gs_corso_utente', $uid );
	update_post_meta( $cid, 'gs_docente', $docente );
	update_post_meta( $cid, 'gs_stato', 'attivo' );
	update_post_meta( $cid, 'gs_oscurato', '' );
	wp_send_json_success( array( 'message' => 'Corso creato.' ) );
}

add_action( 'wp_ajax_gs_pro_add_compito', 'gs_ajax_pro_add_compito' );
function gs_ajax_pro_add_compito() {
	gs_pro_guard();
	$cid   = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	$data  = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';
	$testo = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( 'gs_corso' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	if ( ! $data || ! $testo ) { wp_send_json_error( array( 'message' => 'Indica giorno e compito.' ) ); }

	$compiti   = gs_get_compiti( $cid );
	$compiti[] = array( 'id' => uniqid( 'c' ), 'data' => $data, 'testo' => $testo, 'fatto' => false, 'nota' => '', 'feedback' => '' );
	gs_save_compiti( $cid, $compiti );
	wp_send_json_success( array( 'message' => 'Compito aggiunto.' ) );
}

add_action( 'wp_ajax_gs_pro_modifica_compito', 'gs_ajax_pro_modifica_compito' );
function gs_ajax_pro_modifica_compito() {
	gs_pro_guard();
	$cid    = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	$id     = isset( $_POST['compito'] ) ? sanitize_text_field( wp_unslash( $_POST['compito'] ) ) : '';
	$data   = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';
	$titolo = isset( $_POST['titolo'] ) ? sanitize_text_field( wp_unslash( $_POST['titolo'] ) ) : '';
	$testo  = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	if ( '' === trim( $testo ) ) { wp_send_json_error( array( 'message' => 'Il compito non può essere vuoto.' ) ); }
	if ( ! gs_compito_modifica( $cid, $id, $data, $titolo, $testo ) ) { wp_send_json_error( array( 'message' => 'Compito non trovato.' ) ); }
	wp_send_json_success( array( 'message' => 'Modifiche salvate.' ) );
}

add_action( 'wp_ajax_gs_pro_feedback', 'gs_ajax_pro_feedback' );
function gs_ajax_pro_feedback() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	$id  = isset( $_POST['compito'] ) ? sanitize_text_field( wp_unslash( $_POST['compito'] ) ) : '';
	$fb  = isset( $_POST['feedback'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feedback'] ) ) : '';
	$compiti = gs_get_compiti( $cid );
	foreach ( $compiti as &$c ) { if ( $c['id'] === $id ) { $c['feedback'] = $fb; } }
	unset( $c );
	gs_save_compiti( $cid, $compiti );
	wp_send_json_success( array( 'message' => 'Riscontro salvato.' ) );
}

add_action( 'wp_ajax_gs_pro_del_compito', 'gs_ajax_pro_del_compito' );
function gs_ajax_pro_del_compito() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	$id  = isset( $_POST['compito'] ) ? sanitize_text_field( wp_unslash( $_POST['compito'] ) ) : '';
	gs_compito_sposta_nel_cestino( $cid, $id );
	wp_send_json_success( array( 'message' => 'Compito spostato nel cestino.' ) );
}

add_action( 'wp_ajax_gs_pro_ripristina_compito', 'gs_ajax_pro_ripristina_compito' );
function gs_ajax_pro_ripristina_compito() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	$id  = isset( $_POST['compito'] ) ? sanitize_text_field( wp_unslash( $_POST['compito'] ) ) : '';
	gs_compito_ripristina_dal_cestino( $cid, $id );
	wp_send_json_success( array( 'message' => 'Compito ripristinato.' ) );
}

add_action( 'wp_ajax_gs_pro_oscura', 'gs_ajax_pro_oscura' );
function gs_ajax_pro_oscura() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	if ( 'gs_corso' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	$nuovo = ! gs_corso_oscurato( $cid );
	update_post_meta( $cid, 'gs_oscurato', $nuovo ? '1' : '' );
	wp_send_json_success( array(
		'oscurato' => $nuovo,
		'label'    => $nuovo ? '👁️ Mostra i dati alla sfoglina' : '🙈 Oscura i dati alla sfoglina',
		'message'  => $nuovo ? 'Dati oscurati alla sfoglina.' : 'Dati di nuovo visibili alla sfoglina.',
	) );
}

add_action( 'wp_ajax_gs_pro_stato', 'gs_ajax_pro_stato' );
function gs_ajax_pro_stato() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	if ( 'gs_corso' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	$nuovo = ( 'sospeso' === gs_corso_stato( $cid ) ) ? 'attivo' : 'sospeso';
	update_post_meta( $cid, 'gs_stato', $nuovo );
	wp_send_json_success( array(
		'stato'   => $nuovo,
		'label'   => 'sospeso' === $nuovo ? 'Riattiva corso' : 'Sospendi corso',
		'testo'   => 'sospeso' === $nuovo ? 'Sospeso' : 'Attivo',
		'message' => 'sospeso' === $nuovo ? 'Corso sospeso.' : 'Corso riattivato.',
	) );
}

add_action( 'wp_ajax_gs_pro_del_corso', 'gs_ajax_pro_del_corso' );
function gs_ajax_pro_del_corso() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	if ( 'gs_corso' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	wp_trash_post( $cid );
	wp_send_json_success( array( 'message' => 'Corso spostato nel cestino.' ) );
}

// =============================================================================
// DIPLOMA DI RINA POLETTI — assegnazione manuale (marchio registrato)
// =============================================================================

/**
 * Inverte lo stato del diploma per un corso (assegna/revoca) e restituisce il
 * nuovo stato. Logica pura, separata dall'AJAX per poterla testare.
 */
function gs_pro_diploma_toggle( $corso_id ) {
	$nuovo = '1' !== get_post_meta( $corso_id, 'gs_diploma_rina', true );
	update_post_meta( $corso_id, 'gs_diploma_rina', $nuovo ? '1' : '' );
	if ( $nuovo ) {
		update_post_meta( $corso_id, 'gs_diploma_data', date_i18n( 'Y-m-d' ) );
		do_action( 'gs_diploma_assegnato', $corso_id );
	}
	return $nuovo;
}

add_action( 'wp_ajax_gs_pro_diploma', 'gs_ajax_pro_diploma' );
function gs_ajax_pro_diploma() {
	gs_pro_guard();
	$cid = isset( $_POST['corso'] ) ? (int) $_POST['corso'] : 0;
	if ( 'gs_corso' !== get_post_type( $cid ) ) { wp_send_json_error( array( 'message' => 'Corso non valido.' ) ); }
	$nuovo = gs_pro_diploma_toggle( $cid );
	wp_send_json_success( array(
		'assegnato'  => $nuovo,
		'label'      => $nuovo ? 'Revoca il diploma' : 'Assegna il diploma',
		'stato_html' => $nuovo ? '<span class="gs-stato gs-stato-si">Assegnato</span>' : '<span class="gs-stato gs-stato-off">Non ancora assegnato</span>',
		'url'        => $nuovo ? gs_diploma_url( $cid ) : '',
		'message'    => $nuovo ? 'Diploma assegnato.' : 'Diploma revocato.',
	) );
}

/** URL firmato per aprire/stampare il diploma di un corso. */
function gs_diploma_url( $corso_id ) {
	return wp_nonce_url( add_query_arg( array( 'gs_diploma' => (int) $corso_id ), admin_url( 'admin.php' ) ), 'gs_diploma_' . $corso_id );
}

/** Intercetta la richiesta di stampa del diploma (?gs_diploma=ID). */
add_action( 'admin_init', 'gs_handle_diploma_stampa' );
function gs_handle_diploma_stampa() {
	if ( empty( $_GET['gs_diploma'] ) ) {
		return;
	}
	$cid = (int) $_GET['gs_diploma'];
	if ( 'gs_corso' !== get_post_type( $cid ) ) {
		wp_die( 'Diploma non trovato.' );
	}
	check_admin_referer( 'gs_diploma_' . $cid );

	$uid_corso = (int) get_post_meta( $cid, 'gs_corso_utente', true );
	$puo_vedere = ( function_exists( 'gs_can_manage' ) && gs_can_manage() ) || ( get_current_user_id() === $uid_corso );
	if ( ! $puo_vedere ) {
		wp_die( 'Non hai il permesso di vedere questo diploma.' );
	}
	if ( '1' !== get_post_meta( $cid, 'gs_diploma_rina', true ) ) {
		wp_die( 'Il diploma non è ancora stato assegnato.' );
	}

	$u    = get_user_by( 'id', $uid_corso );
	$nome = $u ? $u->display_name : '';
	$data = get_post_meta( $cid, 'gs_diploma_data', true );
	$data_lbl = $data ? date_i18n( 'j F Y', strtotime( $data ) ) : date_i18n( 'j F Y' );
	$sigillo = gs_certificato_logo_url( 'assets/img/diploma-rina.png' );

	$ha_sigillo = gs_certificato_logo_esiste( 'assets/img/diploma-rina.png' );

	$docente = get_post_meta( $cid, 'gs_docente', true );
	$docente = $docente ? $docente : 'Rina Poletti';

	header( 'Content-Type: text/html; charset=utf-8' );
	?><!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title>Diploma di <?php echo esc_html( $docente ); ?> — <?php echo esc_html( $nome ); ?></title>
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

				<div class="chip">Diploma di Sfoglina Professionista</div>
				<p class="sottotitolo">Laurea in Sfoglia — Accademia della Sfoglia</p>

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

				<p class="testo">ha completato l'intero percorso di formazione con <?php echo esc_html( $docente ); ?>, dall'inizio alla fine, secondo il Piano di Studio Ufficiale dell'Accademia della Sfoglia.</p>

				<div class="firma-riga">
					<div class="firma">
						<div class="nome-firma"><?php echo esc_html( $docente ); ?></div>
						<div class="etichetta">Docente</div>
					</div>
					<div class="data-riga">
						<div class="valore"><?php echo esc_html( $data_lbl ); ?></div>
						<div class="etichetta">Data</div>
					</div>
				</div>
			</div>
		</div>

		<p class="noprint"><button onclick="window.print()">🖨️ Stampa / Salva come PDF</button></p>
	</div>
</body>
</html><?php
	exit;
}
