<?php
/**
 * pannello.php — la vista di chi gestisce i corsi: le sessioni aperte, chi
 * si è iscritto a ciascuna, lo stato del pagamento, e la possibilità di
 * registrare un bonifico ricevuto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True se la sessione è di una persona che possiede/gestisce il corso
 * (in questa prima versione, chiunque abbia la capacità slp_gestisci_corsi
 * vede tutte le sessioni: non c'è, come per le classi di Sfoglia Lab —
 * Aula, un concetto di "sessione di un gestore" distinto da un altro,
 * perché qui il gestore è tipicamente uno solo, il titolare).
 */
function slp_render_pannello() {
	if ( ! is_user_logged_in() || ! slp_can_manage() ) {
		return '<p class="slp-avviso">Il tuo account non è abilitato a gestire i corsi.</p>';
	}

	ob_start();
	$sessioni = slp_sessioni_del_gestore();
	?>
	<div class="slp-pannello">

		<?php if ( ! slp_email_attive() ) : ?>
			<p class="slp-avviso slp-nota-email">
				Le email automatiche sono spente: chi si iscrive non riceve le coordinate
				per il bonifico, devi scrivergli tu. Si accendono qui sotto, in
				<strong>Impostazioni</strong>, dopo aver messo l'IBAN.
			</p>
		<?php endif; ?>

		<section class="slp-sessione slp-crea-sessione">
			<h3>Programma una nuova sessione</h3>
			<form class="slp-form" data-sla-azione="crea-sessione">
				<label>Corso
					<select name="corso_codice">
						<?php foreach ( slp_catalogo_corsi() as $codice => $corso ) : ?>
							<option value="<?php echo esc_attr( $codice ); ?>">
								<?php echo esc_html( $codice . ' — ' . $corso['titolo'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Data
					<input type="date" name="data" required>
				</label>
				<label>Posti (vuoto = usa il default del catalogo)
					<input type="number" name="posti_max" min="0">
				</label>
				<button type="submit">Crea sessione</button>
				<span class="slp-esito" role="status" aria-live="polite"></span>
			</form>
		</section>

		<?php if ( empty( $sessioni ) ) : ?>
			<p class="slp-vuoto">Nessuna sessione ancora programmata.</p>
		<?php else : ?>
			<?php foreach ( $sessioni as $sessione ) : ?>
				<?php slp_render_riquadro_sessione( $sessione ); ?>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php slp_render_certificati(); ?>
		<?php slp_render_impostazioni(); ?>
		<?php slp_render_diagnostica(); ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Il rilascio degli attestati. Fino a ieri la funzione che li crea esisteva
 * nel codice e l'elenco pubblico pure, ma non c'era nessun punto da cui
 * rilasciarne uno: il percorso di certificazione era costruito a metà.
 */
function slp_render_certificati() {
	$livelli     = slp_livelli();
	$certificati = slp_tutti_i_certificati();
	?>
	<section class="slp-sezione">
		<h3>Attestati</h3>

		<form class="slp-form" data-sla-azione="rilascia-certificato">
			<label>Nome e cognome
				<input type="text" name="persona_nome" required>
			</label>
			<label>Livello raggiunto
				<select name="livello">
					<?php foreach ( $livelli as $numero => $nome ) : ?>
						<option value="<?php echo esc_attr( $numero ); ?>"><?php echo esc_html( $nome ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="slp-consenso">
				<input type="checkbox" name="pubblico" value="1">
				<span>
					La persona autorizza a pubblicare il suo nome nell'elenco pubblico
					dei certificati. Senza questa spunta l'attestato è valido lo stesso,
					ma resta fuori dall'elenco (il livello Praticante non vi compare
					comunque).
				</span>
			</label>
			<button type="submit">Rilascia attestato</button>
			<span class="slp-esito" role="status" aria-live="polite"></span>
		</form>

		<?php if ( empty( $certificati ) ) : ?>
			<p class="slp-vuoto">Nessun attestato ancora rilasciato.</p>
		<?php else : ?>
			<table class="slp-tabella">
				<thead>
					<tr>
						<th scope="col">Numero</th><th scope="col">Nome</th>
						<th scope="col">Livello</th><th scope="col">Rilasciato</th><th scope="col">Elenco pubblico</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $certificati as $certificato ) :
						$rilascio    = (string) get_post_meta( $certificato->ID, 'slp_data_rilascio', true );
						$rilascio_ts = $rilascio ? strtotime( $rilascio ) : 0;
						// Senza data non si può dire che sia scaduto: strtotime('')
						// ritorna false, che come timestamp vale il 1970 e farebbe
						// apparire "scaduto" ogni attestato con la data mancante.
						$scaduto = $rilascio_ts && slp_certificato_scaduto( $rilascio_ts, time() );
						?>
						<tr>
							<td><?php echo esc_html( get_post_meta( $certificato->ID, 'slp_numero', true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $certificato->ID, 'slp_persona_nome', true ) ); ?></td>
							<td><?php echo esc_html( $livelli[ (int) get_post_meta( $certificato->ID, 'slp_livello', true ) ] ?? '' ); ?></td>
							<td>
								<?php echo esc_html( substr( $rilascio, 0, 10 ) ); ?>
								<?php echo $scaduto ? ' — scaduto' : ''; ?>
							</td>
							<td><?php echo '1' === get_post_meta( $certificato->ID, 'slp_pubblico', true ) ? 'sì' : 'no'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Le coordinate del bonifico e l'interruttore delle email. Stanno qui, nel
 * pannello, e non in una pagina delle opzioni di WordPress, perché chi
 * gestisce i corsi lavora da questa pagina e non deve andare a cercarle
 * altrove.
 */
function slp_render_impostazioni() {
	$impostazioni = slp_impostazioni();
	?>
	<section class="slp-sezione">
		<h3>Impostazioni</h3>
		<form class="slp-form" data-sla-azione="salva-impostazioni">
			<label>Intestatario del conto
				<input type="text" name="intestatario" value="<?php echo esc_attr( $impostazioni['intestatario'] ); ?>">
			</label>
			<label>IBAN
				<input type="text" name="iban" value="<?php echo esc_attr( $impostazioni['iban'] ); ?>">
			</label>
			<label>Banca
				<input type="text" name="banca" value="<?php echo esc_attr( $impostazioni['banca'] ); ?>">
			</label>
			<label>Email per gli avvisi di nuova iscrizione
				<input type="email" name="email_gestore" value="<?php echo esc_attr( $impostazioni['email_gestore'] ); ?>">
			</label>
			<label class="slp-consenso">
				<input type="checkbox" name="invia_email" value="1" <?php checked( '1', $impostazioni['invia_email'] ); ?>>
				<span>
					Manda le email automatiche (conferma con le coordinate a chi si iscrive,
					avviso a te, conferma quando registri il bonifico). Finché è spenta non
					parte nessun messaggio: tienila spenta sul sito di prova.
				</span>
			</label>
			<button type="submit">Salva impostazioni</button>
			<span class="slp-esito" role="status" aria-live="polite"></span>
		</form>
	</section>
	<?php
}

/**
 * Un riquadro diagnostico, ripiegato di default: mostra TUTTE le sessioni
 * presenti nel database, qualunque sia il loro stato, per distinguere due
 * problemi molto diversi — "il modulo non sta salvando nulla" da "sta
 * salvando, ma qualcosa nasconde il risultato dall'elenco normale qui
 * sopra". Se il modulo "Programma una nuova sessione" dovesse mai smettere
 * di funzionare, guarda qui prima di tutto: se dopo un clic su "Crea
 * sessione" questa tabella cresce di una riga, il salvataggio funziona e il
 * problema è nel filtro sopra; se resta invariata, il problema è nel
 * salvataggio stesso o nella chiamata che non arriva.
 *
 * Il pulsante Elimina serve solo a ripulire righe di prova (per esempio le
 * sessioni salvate col vecchio codice minuscolo, mai raggiungibili
 * dall'elenco normale): rifiuta di agire se la sessione ha già un
 * iscritto, per non perdere dati veri per errore (vedi
 * slp_elimina_sessione_diagnostica() in sessioni.php).
 */
function slp_render_diagnostica() {
	$sessioni = slp_sessioni_diagnostica();
	?>
	<details class="slp-diagnostica">
		<summary>Diagnostica — tutte le sessioni nel database (<?php echo count( $sessioni ); ?>)</summary>
		<?php if ( empty( $sessioni ) ) : ?>
			<p class="slp-vuoto">Nessuna riga trovata: nessuna sessione è mai stata salvata, per nessun corso, con nessuno stato.</p>
		<?php else : ?>
			<table class="slp-tabella">
				<thead>
					<tr>
						<th scope="col">ID</th><th scope="col">Titolo</th><th scope="col">Stato del post</th>
						<th scope="col">Corso</th><th scope="col">Data</th><th scope="col">Stato sessione</th>
						<th scope="col">Elimina</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sessioni as $riga ) : ?>
						<tr>
							<td><?php echo esc_html( $riga['id'] ); ?></td>
							<td><?php echo esc_html( $riga['titolo'] ); ?></td>
							<td><?php echo esc_html( $riga['stato_post'] ); ?></td>
							<td><?php echo esc_html( $riga['corso_codice'] ); ?></td>
							<td><?php echo esc_html( $riga['data'] ); ?></td>
							<td><?php echo esc_html( $riga['stato_sessione'] ); ?></td>
							<td>
								<?php if ( 'trash' !== $riga['stato_post'] ) : ?>
									<button type="button" class="slp-elimina-diagnostica" data-sla-azione="elimina-sessione-diagnostica" data-sessione-id="<?php echo esc_attr( $riga['id'] ); ?>">Elimina</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</details>
	<?php
}

function slp_render_riquadro_sessione( $sessione ) {
	$codice   = get_post_meta( $sessione->ID, 'slp_corso_codice', true );
	$corso    = slp_get_corso( $codice );
	$data     = get_post_meta( $sessione->ID, 'slp_data', true );
	$liberi   = slp_posti_liberi( $sessione->ID );
	$iscritti = slp_iscrizioni_della_sessione( $sessione->ID );
	if ( ! $corso ) {
		return;
	}
	?>
	<?php
	$stato_sessione = (string) get_post_meta( $sessione->ID, 'slp_stato', true );
	$stati          = slp_stati_sessione();
	$url_csv        = wp_nonce_url(
		admin_url( 'admin-post.php?action=slp_esporta_iscritti&sessione_id=' . $sessione->ID ),
		'slp_esporta_iscritti_' . $sessione->ID
	);
	?>
	<div class="slp-sessione slp-stato-<?php echo esc_attr( $stato_sessione ); ?>" data-sessione-id="<?php echo esc_attr( $sessione->ID ); ?>">
		<header class="slp-sessione-testa">
			<h4><?php echo esc_html( $corso['titolo'] ); ?> <span class="slp-codice-corso"><?php echo esc_html( $codice ); ?></span></h4>
			<p class="slp-sessione-meta">
				<?php echo esc_html( $data ); ?> ·
				<?php echo esc_html( slp_euro( $corso['quota'] ) ); ?> ·
				<?php echo null === $liberi ? 'nessun limite di posti' : esc_html( $liberi ) . ' posti liberi'; ?> ·
				<strong><?php echo esc_html( $stati[ $stato_sessione ] ?? $stato_sessione ); ?></strong>
			</p>

			<p class="slp-azioni-sessione">
				<?php foreach ( $stati as $chiave => $etichetta ) : ?>
					<?php if ( $chiave !== $stato_sessione ) : ?>
						<button type="button" class="slp-secondario" data-sla-azione="cambia-stato-sessione"
							data-sessione-id="<?php echo esc_attr( $sessione->ID ); ?>"
							data-stato="<?php echo esc_attr( $chiave ); ?>">
							<?php echo esc_html( $etichetta ); ?>
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
				<a class="slp-secondario" href="<?php echo esc_url( $url_csv ); ?>">Scarica elenco partecipanti (CSV)</a>
			</p>
		</header>

		<table class="slp-tabella">
			<thead>
				<tr>
					<th scope="col">Nome</th><th scope="col">Contatto</th>
					<th scope="col">Stato</th><th scope="col">Versato</th><th scope="col">Registra bonifico</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $iscritti as $iscrizione ) :
					$pagamenti = get_post_meta( $iscrizione->ID, 'slp_pagamenti', true );
					$totale    = slp_totale_pagato( is_array( $pagamenti ) ? $pagamenti : array() );
					$stato     = get_post_meta( $iscrizione->ID, 'slp_stato', true );
					?>
					<tr>
						<td><?php echo esc_html( get_post_meta( $iscrizione->ID, 'slp_nome', true ) ); ?></td>
						<td>
							<?php echo esc_html( get_post_meta( $iscrizione->ID, 'slp_email', true ) ); ?><br>
							<?php echo esc_html( get_post_meta( $iscrizione->ID, 'slp_telefono', true ) ); ?>
						</td>
						<td><?php echo esc_html( str_replace( '_', ' ', $stato ) ); ?></td>
						<td><?php echo esc_html( slp_euro( $totale ) ); ?> / <?php echo esc_html( slp_euro( $corso['quota'] ) ); ?></td>
						<td>
							<?php if ( 'annullata' !== $stato ) : ?>
								<form class="slp-form-pagamento" data-sla-azione="registra-pagamento" data-iscrizione-id="<?php echo esc_attr( $iscrizione->ID ); ?>">
									<input type="number" step="0.01" name="importo" placeholder="€" style="width: 80px;" required>
									<button type="submit">Registra</button>
								</form>
								<button type="button" class="slp-secondario slp-annulla-iscrizione" data-sla-azione="annulla-iscrizione"
									data-iscrizione-id="<?php echo esc_attr( $iscrizione->ID ); ?>">Annulla iscrizione</button>
							<?php else : ?>
								<span class="slp-vuoto">annullata</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $iscritti ) ) : ?>
					<tr><td colspan="5" class="slp-vuoto">Ancora nessun iscritto.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
