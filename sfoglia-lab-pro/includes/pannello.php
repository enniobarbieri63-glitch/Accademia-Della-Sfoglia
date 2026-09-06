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
	$sessioni = slp_sessioni_aperte();
	?>
	<div class="slp-pannello">

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
	</div>
	<?php
	return ob_get_clean();
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
	<div class="slp-sessione" data-sessione-id="<?php echo esc_attr( $sessione->ID ); ?>">
		<header class="slp-sessione-testa">
			<h4><?php echo esc_html( $corso['titolo'] ); ?> <span class="slp-codice-corso"><?php echo esc_html( $codice ); ?></span></h4>
			<p class="slp-sessione-meta">
				<?php echo esc_html( $data ); ?> ·
				<?php echo esc_html( slp_euro( $corso['quota'] ) ); ?> ·
				<?php echo null === $liberi ? 'nessun limite di posti' : esc_html( $liberi ) . ' posti liberi'; ?>
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
							<form class="slp-form-pagamento" data-sla-azione="registra-pagamento" data-iscrizione-id="<?php echo esc_attr( $iscrizione->ID ); ?>">
								<input type="number" step="0.01" name="importo" placeholder="€" style="width: 80px;" required>
								<button type="submit">Registra</button>
							</form>
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
