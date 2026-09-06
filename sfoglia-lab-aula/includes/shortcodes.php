<?php
/**
 * shortcodes.php — le due pagine pubbliche del plugin:
 *   [sla_pannello_docente]  pannello del docente
 *   [sla_ingresso]          ingresso e svolgimento per lo studente
 *
 * Entrambe caricano gli stessi assets/js/sla.js e assets/css/sla.css, solo
 * quando lo shortcode è davvero presente nella pagina (niente script
 * caricati a vuoto sul resto del sito).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'sla_forse_carica_assets' );
function sla_forse_carica_assets() {
	global $post;
	if ( ! $post || ! has_shortcode( $post->post_content, 'sla_pannello_docente' )
		&& ! has_shortcode( $post->post_content, 'sla_ingresso' ) ) {
		return;
	}

	wp_enqueue_style( 'sla-stile', SLA_URL . 'assets/css/sla.css', array(), SLA_VERSION );
	wp_enqueue_script( 'sla-script', SLA_URL . 'assets/js/sla.js', array(), SLA_VERSION, true );
	wp_localize_script( 'sla-script', 'slaDati', array(
		'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
		'nonceDocente'    => wp_create_nonce( 'sla_ajax' ),
		'noncePubblico'   => wp_create_nonce( 'sla_ajax_pubblico' ),
	) );
}

// -----------------------------------------------------------------------------
// [sla_pannello_docente]
// -----------------------------------------------------------------------------

add_shortcode( 'sla_pannello_docente', 'sla_shortcode_pannello_docente' );
function sla_shortcode_pannello_docente() {
	if ( ! is_user_logged_in() ) {
		return '<p class="sla-avviso">Accedi con il tuo account per gestire le classi.</p>';
	}
	if ( ! sla_can_manage() ) {
		return '<p class="sla-avviso">Il tuo account non è abilitato a gestire le classi.</p>';
	}

	ob_start();
	$gruppi       = sla_classi_per_anno( get_current_user_id() );
	$anno_corrente = sla_anno_corrente();
	?>
	<div class="sla-pannello" id="sla-pannello-docente">

		<section class="sla-riquadro sla-crea-classe">
			<h3>Crea una nuova classe</h3>
			<form class="sla-form" data-sla-azione="crea-classe">
				<label>Nome della classe
					<input type="text" name="nome" placeholder="es. 3A Cucina" required>
				</label>
				<label>Materia
					<select name="materia">
						<option value="cucina">Cucina</option>
						<option value="pasticceria">Pasticceria</option>
						<option value="sala">Sala</option>
					</select>
				</label>
				<label>Anno scolastico
					<input type="text" name="anno" placeholder="es. <?php echo esc_attr( $anno_corrente ); ?>" value="<?php echo esc_attr( $anno_corrente ); ?>">
				</label>
				<button type="submit">Crea classe</button>
				<span class="sla-esito"></span>
			</form>
		</section>

		<section class="sla-riquadro">
			<h3>Le tue classi</h3>
			<?php if ( empty( $gruppi ) ) : ?>
				<p class="sla-vuoto">Non hai ancora nessuna classe. Creane una qui sopra.</p>
			<?php else : ?>
				<?php foreach ( $gruppi as $anno => $classi_anno ) : ?>
					<?php $e_corrente = ( $anno === $anno_corrente ); ?>
					<details class="sla-gruppo-anno" <?php echo $e_corrente ? 'open' : ''; ?>>
						<summary>
							<?php echo esc_html( $anno ); ?>
							<?php echo $e_corrente ? ' (anno corrente)' : ''; ?>
							· <?php echo count( $classi_anno ); ?> <?php echo 1 === count( $classi_anno ) ? 'classe' : 'classi'; ?>
						</summary>
						<div class="sla-elenco-classi">
							<?php foreach ( $classi_anno as $classe ) : ?>
								<?php sla_render_riquadro_classe( $classe, $anno_corrente ); ?>
							<?php endforeach; ?>
						</div>
					</details>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>

	</div>
	<?php
	return ob_get_clean();
}

function sla_render_riquadro_classe( $classe, $anno_corrente ) {
	$classe_id     = $classe->ID;
	$codice        = get_post_meta( $classe_id, 'sla_codice', true );
	$materia       = get_post_meta( $classe_id, 'sla_materia', true );
	$anno          = get_post_meta( $classe_id, 'sla_anno', true );
	$studenti      = sla_studenti_della_classe( $classe_id );
	$assegnazioni  = sla_assegnazioni_della_classe( $classe_id );
	$assegnazione  = $assegnazioni ? $assegnazioni[0] : null;
	$catalogo      = sla_catalogo_esercizi();
	$e_anno_passato = ( '' !== $anno && $anno !== $anno_corrente );
	?>
	<div class="sla-classe" data-classe-id="<?php echo esc_attr( $classe_id ); ?>">
		<header class="sla-classe-testa">
			<h4><?php echo esc_html( $classe->post_title ); ?></h4>
			<p class="sla-classe-meta">
				<?php echo esc_html( ucfirst( $materia ) ); ?>
				<?php if ( $anno ) : ?> · <?php echo esc_html( $anno ); ?><?php endif; ?>
				· <?php echo count( $studenti ); ?> posti
			</p>
			<p class="sla-codice">
				Codice: <code class="sla-codice-valore"><?php echo esc_html( $codice ); ?></code>
				<button type="button" class="sla-link" data-sla-azione="rigenera-codice">Rigenera</button>
				<?php if ( $e_anno_passato ) : ?>
					<button type="button" class="sla-link" data-sla-azione="duplica-classe">Duplica per <?php echo esc_html( $anno_corrente ); ?></button>
				<?php endif; ?>
			</p>
		</header>

		<details class="sla-blocco">
			<summary>Elenco studenti (<?php echo count( $studenti ); ?>)</summary>
			<form class="sla-form" data-sla-azione="aggiungi-studenti">
				<label>Incolla un nickname per riga
					<textarea name="elenco" rows="4" placeholder="Alunno 01&#10;Alunno 02&#10;Alunno 03"></textarea>
				</label>
				<button type="submit">Aggiungi</button>
				<span class="sla-esito"></span>
			</form>
			<table class="sla-tabella">
				<thead><tr><th>Nickname</th><th>Stato</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $studenti as $studente ) :
						$occupato = '1' === get_post_meta( $studente->ID, 'sla_occupato', true );
						?>
						<tr>
							<td><?php echo esc_html( get_post_meta( $studente->ID, 'sla_nickname', true ) ); ?></td>
							<td><?php echo $occupato ? 'collegato' : 'libero'; ?></td>
							<td>
								<?php if ( $occupato ) : ?>
									<button type="button" class="sla-link" data-sla-azione="libera-posto"
										data-studente-id="<?php echo esc_attr( $studente->ID ); ?>">Libera posto</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>

		<details class="sla-blocco" open>
			<summary>Assegna un esercizio o un quiz</summary>
			<form class="sla-form" data-sla-azione="assegna-esercizio">
				<label>Contenuto
					<select name="contenuto">
						<optgroup label="Esercizi">
							<?php foreach ( $catalogo as $slug => $es ) : ?>
								<option value="esercizio|<?php echo esc_attr( $slug ); ?>">
									<?php echo esc_html( $es['titolo'] ); ?>
									<?php echo ! empty( $es['esempio'] ) ? ' (dati di esempio)' : ''; ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
						<optgroup label="Quiz">
							<?php foreach ( sla_catalogo_quiz() as $slug => $qz ) : ?>
								<option value="quiz|<?php echo esc_attr( $slug ); ?>">
									<?php echo esc_html( $qz['titolo'] ); ?>
									<?php echo ! empty( $qz['esempio'] ) ? ' (dati di esempio)' : ''; ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					</select>
				</label>
				<label>Scadenza (facoltativa)
					<input type="datetime-local" name="scadenza">
				</label>
				<label>Tentativi consentiti (0 = illimitati)
					<input type="number" name="tentativi" value="3" min="0">
				</label>
				<label><input type="checkbox" name="vale_voto" value="1"> Vale come voto</label>
				<button type="submit">Assegna</button>
				<span class="sla-esito"></span>
			</form>
		</details>

		<?php if ( $assegnazione ) : ?>
			<details class="sla-blocco" open>
				<summary>Risultati — <?php echo esc_html( get_the_title( $assegnazione ) ); ?></summary>
				<table class="sla-tabella">
					<thead><tr><th>Nickname</th><th>Consegnato</th><th>Punteggio</th></tr></thead>
					<tbody>
						<?php foreach ( sla_dati_cruscotto( $classe_id, $assegnazione->ID ) as $riga ) : ?>
							<tr>
								<td><?php echo esc_html( $riga['nickname'] ); ?></td>
								<td><?php echo $riga['consegnato'] ? 'sì' : 'no'; ?></td>
								<td><?php echo null === $riga['punteggio'] ? '—' : esc_html( $riga['punteggio'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<a class="sla-link"
					href="<?php echo esc_url( wp_nonce_url(
						admin_url( 'admin-post.php?action=sla_esporta_csv&classe_id=' . $classe_id . '&assegnazione_id=' . $assegnazione->ID ),
						'sla_esporta_csv_' . $classe_id
					) ); ?>">Scarica CSV</a>
			</details>
		<?php endif; ?>

		<?php sla_render_errori_frequenti( $classe_id ); ?>
	</div>
	<?php
}

/**
 * La Vista 2: dove sbaglia la classe, su tutte le assegnazioni insieme
 * (non solo l'ultima). Non mostra nulla finché non c'è almeno un errore
 * registrato — un elenco vuoto qui è una buona notizia, non va forzato a
 * comparire comunque.
 */
function sla_render_errori_frequenti( $classe_id ) {
	$righe = sla_errori_frequenti( $classe_id );
	if ( empty( $righe ) ) {
		return;
	}
	?>
	<details class="sla-blocco" open>
		<summary>Dove sbaglia la classe</summary>
		<div class="sla-errori">
			<?php foreach ( $righe as $riga ) : ?>
				<div class="sla-errore-riga">
					<div class="sla-errore-testa">
						<span class="sla-errore-etichetta"><?php echo esc_html( $riga['etichetta'] ); ?></span>
						<span class="sla-errore-percentuale"><?php echo esc_html( $riga['percentuale'] ); ?>%</span>
					</div>
					<div class="sla-errore-barra">
						<div class="sla-errore-riempimento" style="width: <?php echo esc_attr( $riga['percentuale'] ); ?>%;"></div>
					</div>
					<div class="sla-errore-origine">
						<?php echo esc_html( $riga['origine'] ); ?>
						<?php if ( ! empty( $riga['suggerimento'] ) ) : ?>
							— <?php echo esc_html( $riga['suggerimento'] ); ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</details>
	<?php
}

// -----------------------------------------------------------------------------
// [sla_ingresso]
// -----------------------------------------------------------------------------

add_shortcode( 'sla_ingresso', 'sla_shortcode_ingresso' );
function sla_shortcode_ingresso() {
	$sessione = sla_studente_da_sessione();

	ob_start();
	if ( $sessione ) {
		sla_render_svolgimento( $sessione );
	} else {
		sla_render_form_ingresso();
	}
	return ob_get_clean();
}

function sla_render_form_ingresso() {
	?>
	<div class="sla-ingresso" id="sla-ingresso">
		<form class="sla-form" data-sla-azione="cerca-classe">
			<label>Codice della classe
				<input type="text" name="codice" maxlength="6" autocomplete="off"
					style="text-transform:uppercase" required>
			</label>
			<button type="submit">Continua</button>
			<span class="sla-esito"></span>
		</form>
		<form class="sla-form" data-sla-azione="entra" hidden>
			<input type="hidden" name="codice">
			<label>Il tuo nickname
				<select name="studente_id" required></select>
			</label>
			<button type="submit">Entra</button>
			<span class="sla-esito"></span>
		</form>
	</div>
	<?php
}

function sla_render_svolgimento( $sessione ) {
	$assegnazione = sla_assegnazione_corrente( $sessione['classe_id'] );
	?>
	<div class="sla-svolgimento">
		<p class="sla-benvenuto">Ciao, <strong><?php echo esc_html( $sessione['nickname'] ); ?></strong>.</p>

		<?php
		if ( ! $assegnazione ) :
			?>
			<p class="sla-vuoto">Non c'è ancora nessun esercizio assegnato. Torna più tardi.</p>
			<?php
		elseif ( 'quiz' === sla_tipo_assegnazione( $assegnazione->ID ) ) :
			sla_render_svolgimento_quiz( $assegnazione, $sessione );
		else :
			sla_render_svolgimento_esercizio( $assegnazione, $sessione );
		endif;
		?>
	</div>
	<?php
}

function sla_render_svolgimento_esercizio( $assegnazione, $sessione ) {
	$slug      = get_post_meta( $assegnazione->ID, 'sla_esercizio', true );
	$esercizio = sla_get_esercizio( $slug );
	$puo       = sla_puo_tentare( $assegnazione->ID, $sessione['studente_id'] );
	?>
	<div class="sla-esercizio" data-assegnazione-id="<?php echo esc_attr( $assegnazione->ID ); ?>">
		<h3><?php echo esc_html( $esercizio['titolo'] ); ?></h3>
		<?php if ( ! empty( $esercizio['esempio'] ) ) : ?>
			<p class="sla-avviso">Valori di esempio, non ancora validati dai maestri.</p>
		<?php endif; ?>
		<p><?php echo esc_html( $esercizio['enunciato'] ); ?></p>

		<?php if ( $puo ) : ?>
			<form class="sla-form" data-sla-azione="consegna">
				<label>Il tuo valore (<?php echo esc_html( $esercizio['unita'] ); ?>)
					<input type="number" step="0.01" name="valore" required>
				</label>
				<button type="submit">Consegna</button>
			</form>
		<?php else : ?>
			<p class="sla-avviso">Hai già usato tutti i tentativi disponibili.</p>
		<?php endif; ?>

		<div class="sla-esito-esercizio" hidden></div>
	</div>
	<?php
}

function sla_render_svolgimento_quiz( $assegnazione, $sessione ) {
	$slug = get_post_meta( $assegnazione->ID, 'sla_esercizio', true );
	$quiz = sla_get_quiz( $slug );
	$puo  = sla_puo_tentare( $assegnazione->ID, $sessione['studente_id'] );
	?>
	<div class="sla-quiz" data-assegnazione-id="<?php echo esc_attr( $assegnazione->ID ); ?>">
		<h3><?php echo esc_html( $quiz['titolo'] ); ?></h3>
		<?php if ( ! empty( $quiz['esempio'] ) ) : ?>
			<p class="sla-avviso">Domande di esempio, non ancora la banca completa.</p>
		<?php endif; ?>

		<?php if ( $puo ) : ?>
			<form class="sla-form sla-form-quiz" data-sla-azione="consegna-quiz">
				<?php foreach ( $quiz['domande'] as $indice => $d ) : ?>
					<fieldset class="sla-domanda">
						<legend><?php echo ( $indice + 1 ) . '. ' . esc_html( $d['testo'] ); ?></legend>
						<?php foreach ( $d['opzioni'] as $lettera => $testo ) : ?>
							<label class="sla-opzione">
								<input type="<?php echo 'multipla' === $d['tipo'] ? 'checkbox' : 'radio'; ?>"
									name="risposte[<?php echo esc_attr( $d['id'] ); ?>]<?php echo 'multipla' === $d['tipo'] ? '[]' : ''; ?>"
									value="<?php echo esc_attr( $lettera ); ?>">
								<?php echo esc_html( $testo ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endforeach; ?>
				<button type="submit">Consegna il quiz</button>
			</form>
		<?php else : ?>
			<p class="sla-avviso">Hai già usato tutti i tentativi disponibili.</p>
		<?php endif; ?>

		<div class="sla-esito-quiz" hidden></div>
	</div>
	<?php
}
