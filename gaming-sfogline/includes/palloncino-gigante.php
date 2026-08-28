<?php
/**
 * palloncino-gigante.php — Palloncino Gigante: si gonfia fino a coprire
 * tutto lo schermo ed esplode, sullo schermo di ogni utente collegato in
 * quel momento (sfogline e gestori) — stesso principio "ultimo lancio +
 * polling ogni 15 secondi" già usato per Aeroplanino e Palloncini (vedi
 * volo-notifiche.php), qui in un file a parte perché ha molti più controlli
 * (velocità, dimensione, colore, suono, tre versioni di scoppio, foto delle
 * sfogline).
 *
 * Nato come anteprima standalone (2026-08-16_palloncino-gigante-anteprima.html),
 * rifinita passo per passo, integrata qui nel pannello vero.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gs_pannello_palloncino_gigante() {
	if ( ! gs_can_manage() ) { return; }
	echo gs_box_open( '🎈 Palloncino Gigante — festeggiamenti in grande', '', 'gs-box-palloncino-gigante' );
	echo '<p class="gs-hint">Un palloncino che si gonfia fino a coprire tutto lo schermo, poi scoppia — sullo schermo di <strong>ogni utente collegato</strong> in quel momento, come Palloncini e Aeroplanino qui sopra. Allo scoppio succede una di tre cose, a scelta: parte la pioggia dei palloncini piccoli, appare un messaggio, oppure attraversa lo schermo l\'aeroplanino. Le stelline e i cuoricini dorati che scintillano allo scoppio possono mostrare le foto vere delle sfogline al posto del colore pieno.</p>';

	// Nome → ID delle sfogline vere: usato lato JS per risolvere il nome
	// scritto nel campo "Palloncino gigante per" (autocompletamento nativo
	// del browser, nessuna ricerca AJAX da scrivere apposta).
	$mappa_persone = array();
	foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
		if ( gs_e_sfoglina_vera( $u ) ) {
			$mappa_persone[ $u->display_name ] = $u->ID;
		}
	}
	?>
	<form class="gs-form gs-form-palloncino-gigante" onsubmit="return false">
		<div class="gs-todo-riquadro">
			<p><label>Velocità di gonfiaggio<br>
				<input type="range" name="velocita" min="2" max="14" step="0.5" value="6" class="gs-pg-range" data-out="gs-pg-out-velocita">
				<span class="gs-pg-out" id="gs-pg-out-velocita">6.0 s</span>
			</label></p>
			<p><label>Dimensione di partenza<br>
				<input type="range" name="dimensione" min="1" max="10" step="1" value="3" class="gs-pg-range" data-out="gs-pg-out-dimensione">
				<span class="gs-pg-out" id="gs-pg-out-dimensione">3</span>
			</label></p>
		</div>
		<div class="gs-todo-riquadro">
			<p>
				<label>Colore <input type="color" name="colore" value="#e74c3c"></label>
				<label style="margin-left:16px"><input type="checkbox" name="colore_casuale" checked> Colore casuale</label>
			</p>
		</div>
		<div class="gs-todo-riquadro">
			<p><label><input type="checkbox" name="suono" checked> Suoni attivi (gonfiaggio + scoppio)</label></p>
		</div>
		<div class="gs-todo-riquadro">
			<p style="font-weight:700;margin:0 0 8px">Foto delle sfogline — palloncini piccoli della pioggia (versione 1)</p>
			<p>
				<label><input type="radio" name="modo_foto" value="colore" checked> Colore pieno</label>
				<label style="margin-left:14px"><input type="radio" name="modo_foto" value="foto"> Sempre foto</label>
				<label style="margin-left:14px"><input type="radio" name="modo_foto" value="mix"> Mix — alcuni con foto, alcuni a colore</label>
			</p>
			<p style="margin-top:10px"><label>Palloncino gigante per <span class="gs-hint" style="font-weight:400">(facoltativo — per festeggiare una persona in particolare con la sua foto, es. la festeggiata di un compleanno)</span><br>
				<input type="text" name="persona_gigante" list="gs-pg-persone" placeholder="Nome della sfoglina, lascia vuoto per nessuno" style="width:100%;max-width:420px">
			</label></p>
			<datalist id="gs-pg-persone">
				<?php foreach ( array_keys( $mappa_persone ) as $nome ) : ?>
					<option value="<?php echo esc_attr( $nome ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
		</div>
		<div class="gs-todo-riquadro">
			<p style="font-weight:700;margin:0 0 8px">Allo scoppio</p>
			<p><label><input type="radio" name="versione" value="1" checked> 🎈 Pioggia di palloncini</label></p>
			<p><label><input type="radio" name="versione" value="2"> ✉️ Messaggio dell'amministrazione</label></p>
			<p class="gs-pg-campo-testo" data-per="2" style="display:none;margin-left:26px">
				<input type="text" name="testo_messaggio" maxlength="200" placeholder="Testo del messaggio…" style="width:100%;max-width:420px">
			</p>
			<p><label><input type="radio" name="versione" value="3"> 🛩️ Aeroplanino con messaggio</label></p>
			<p class="gs-pg-campo-testo" data-per="3" style="display:none;margin-left:26px">
				<input type="text" name="testo_aereo" maxlength="200" placeholder="Testo dell'aeroplanino…" style="width:100%;max-width:420px">
				<label style="display:block;margin-top:8px">Velocità aeroplanino<br>
					<input type="range" name="velocita_aereo" min="2" max="12" step="0.5" value="5" class="gs-pg-range" data-out="gs-pg-out-velaereo">
					<span class="gs-pg-out" id="gs-pg-out-velaereo">5.0 s</span>
				</label>
			</p>
		</div>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-palloncino-gigante-lancia">🎈 Gonfia il palloncino!</button> <span class="gs-pg-msg gs-richiesta-esito"></span></p>
	</form>
	<script>window.GS_PG_PERSONE = <?php echo wp_json_encode( $mappa_persone ); ?>;</script>
	<?php
	echo gs_box_close();
}

/** Manda il palloncino gigante a tutti gli utenti collegati in quel momento. */
add_action( 'wp_ajax_gs_palloncino_gigante_invia', 'gs_ajax_palloncino_gigante_invia' );
function gs_ajax_palloncino_gigante_invia() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$versione = isset( $_POST['versione'] ) ? sanitize_key( wp_unslash( $_POST['versione'] ) ) : '1';
	if ( ! in_array( $versione, array( '1', '2', '3' ), true ) ) { $versione = '1'; }
	$modo_foto = isset( $_POST['modo_foto'] ) ? sanitize_key( wp_unslash( $_POST['modo_foto'] ) ) : 'colore';
	if ( ! in_array( $modo_foto, array( 'colore', 'foto', 'mix' ), true ) ) { $modo_foto = 'colore'; }
	$colore = isset( $_POST['colore'] ) ? wp_unslash( $_POST['colore'] ) : '#e74c3c';
	if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $colore ) ) { $colore = '#e74c3c'; }
	// Il colore casuale viene deciso UNA VOLTA sola qui, non da chi riceve:
	// altrimenti ogni schermo collegato vedrebbe un colore diverso invece
	// dello stesso palloncino per tutti.
	if ( ! empty( $_POST['colore_casuale'] ) ) {
		$palette = array( '#e74c3c', '#3498db', '#f1c40f', '#9b59b6', '#1abc9c', '#e67e22', '#ec407a', '#2ecc71' );
		$colore  = $palette[ array_rand( $palette ) ];
	}

	$dati = array(
		'ts'              => time(),
		'velocita'        => max( 2, min( 14, (float) ( $_POST['velocita'] ?? 6 ) ) ),
		'dimensione'      => max( 1, min( 10, (int) ( $_POST['dimensione'] ?? 3 ) ) ),
		'colore'          => $colore,
		'colore_casuale'  => ! empty( $_POST['colore_casuale'] ) ? 1 : 0,
		'suono'           => ! empty( $_POST['suono'] ) ? 1 : 0,
		'modo_foto'       => $modo_foto,
		'versione'        => $versione,
		'testo_messaggio' => isset( $_POST['testo_messaggio'] ) ? sanitize_text_field( wp_unslash( $_POST['testo_messaggio'] ) ) : '',
		'testo_aereo'     => isset( $_POST['testo_aereo'] ) ? sanitize_text_field( wp_unslash( $_POST['testo_aereo'] ) ) : '',
		'velocita_aereo'  => max( 2, min( 12, (float) ( $_POST['velocita_aereo'] ?? 5 ) ) ),
	);

	// Persona per il palloncino gigante (facoltativa): risolta qui una volta
	// sola, così chi riceve il broadcast non deve rifare la ricerca.
	$persona_id      = isset( $_POST['persona_gigante_id'] ) ? (int) $_POST['persona_gigante_id'] : 0;
	$dati['persona']  = null;
	if ( $persona_id && gs_e_sfoglina_vera( $persona_id ) ) {
		$u = get_user_by( 'id', $persona_id );
		$dati['persona'] = array(
			'nome' => $u->display_name,
			'foto' => function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $persona_id ) : '',
		);
	}

	// Campione di sfogline per la pioggia con le foto (modo "foto"/"mix"):
	// fino a 10 sfogline vere scelte a caso, con la loro foto se ce l'hanno.
	$dati['campione'] = array();
	if ( in_array( $modo_foto, array( 'foto', 'mix' ), true ) ) {
		$tutte = array();
		foreach ( get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) ) as $u ) {
			if ( gs_e_sfoglina_vera( $u ) ) { $tutte[] = $u; }
		}
		shuffle( $tutte );
		foreach ( array_slice( $tutte, 0, 10 ) as $u ) {
			$dati['campione'][] = array(
				'nome' => $u->display_name,
				'foto' => function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $u->ID ) : '',
			);
		}
	}

	$s = gs_settings();
	$s['palloncino_gigante_ultimo'] = $dati;
	update_option( GS_OPTION, $s );

	// Rimando indietro anche i dati appena risolti (colore, persona, campione)
	// così chi ha appena cliccato "Lancia" vede subito lo stesso identico
	// palloncino che vedranno gli altri al giro di interrogazione, invece di
	// uno slegato costruito lato suo con dati incompleti.
	wp_send_json_success( array_merge( array( 'message' => 'Palloncino gigante in arrivo su tutti gli schermi collegati.' ), $dati ) );
}

/**
 * Interrogato ogni 15 secondi da OGNI utente collegato (sfogline comprese),
 * stesso schema di gs_ajax_palloncini_ultimo(): non gated da gs_can_manage(),
 * pensato per essere visto da tutti.
 */
add_action( 'wp_ajax_gs_palloncino_gigante_ultimo', 'gs_ajax_palloncino_gigante_ultimo' );
function gs_ajax_palloncino_gigante_ultimo() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! is_user_logged_in() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$s      = gs_settings();
	$ultimo = isset( $s['palloncino_gigante_ultimo'] ) && is_array( $s['palloncino_gigante_ultimo'] ) ? $s['palloncino_gigante_ultimo'] : array();
	wp_send_json_success( $ultimo );
}
