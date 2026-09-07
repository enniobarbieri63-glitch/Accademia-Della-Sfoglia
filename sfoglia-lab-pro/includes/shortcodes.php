<?php
/**
 * shortcodes.php — le pagine pubbliche del plugin:
 *   [slp_pannello_gestore]   pannello del gestore corsi
 *   [slp_catalogo_corsi]     catalogo pubblico + iscrizione
 *   [slp_elenco_certificati] elenco pubblico dei certificati
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'slp_forse_carica_assets' );
function slp_forse_carica_assets() {
	global $post;
	if ( ! $post
		|| ! has_shortcode( $post->post_content, 'slp_pannello_gestore' )
		&& ! has_shortcode( $post->post_content, 'slp_catalogo_corsi' )
		&& ! has_shortcode( $post->post_content, 'slp_elenco_certificati' ) ) {
		return;
	}

	wp_enqueue_style( 'slp-stile', SLP_URL . 'assets/css/slp.css', array(), SLP_VERSION );
	wp_enqueue_script( 'slp-script', SLP_URL . 'assets/js/slp.js', array(), SLP_VERSION, true );
	wp_localize_script( 'slp-script', 'slpDati', array(
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'nonceGestore'  => wp_create_nonce( 'slp_ajax' ),
		'noncePubblico' => wp_create_nonce( 'slp_ajax_pubblico' ),
	) );
}

add_shortcode( 'slp_pannello_gestore', 'slp_render_pannello' );

add_shortcode( 'slp_catalogo_corsi', 'slp_shortcode_catalogo_corsi' );
function slp_shortcode_catalogo_corsi() {
	ob_start();
	$sessioni = slp_sessioni_aperte();
	?>
	<div class="slp-catalogo">
		<?php if ( empty( $sessioni ) ) : ?>
			<p class="slp-vuoto">Nessuna data disponibile al momento. Scrivici per essere avvisato delle prossime.</p>
		<?php endif; ?>
		<?php foreach ( $sessioni as $sessione ) :
			$codice = get_post_meta( $sessione->ID, 'slp_corso_codice', true );
			$corso  = slp_get_corso( $codice );
			$data   = get_post_meta( $sessione->ID, 'slp_data', true );
			$liberi = slp_posti_liberi( $sessione->ID );
			if ( ! $corso ) {
				continue;
			}
			$acconto = slp_calcola_acconto( $corso['quota'] );
			?>
			<div class="slp-corso-scheda">
				<h3><?php echo esc_html( $corso['titolo'] ); ?></h3>
				<p class="slp-corso-meta">
					<?php echo esc_html( $corso['destinatario'] ); ?> ·
					<?php echo esc_html( $corso['durata'] ); ?> ·
					<?php echo esc_html( $data ); ?>
				</p>
				<p class="slp-corso-prezzo">
					<?php echo esc_html( slp_euro( $corso['quota'] ) ); ?>
					<span class="slp-corso-acconto">— acconto <?php echo esc_html( slp_euro( $acconto ) ); ?>, non rimborsabile</span>
				</p>

				<?php if ( null !== $liberi && $liberi <= 0 ) : ?>
					<p class="slp-avviso">Posti esauriti per questa data.</p>
				<?php else : ?>
					<form class="slp-form" data-sla-azione="iscrivi" data-sessione-id="<?php echo esc_attr( $sessione->ID ); ?>">
						<label>Nome e cognome
							<input type="text" name="nome" required>
						</label>
						<label>Email
							<input type="email" name="email" required>
						</label>
						<label>Telefono
							<input type="tel" name="telefono">
						</label>

						<?php // Campo esca per i programmi che compilano tutto: nascosto alla vista e alla lettura assistita, mai da compilare. ?>
						<div class="slp-esca" aria-hidden="true">
							<label>Sito web (non compilare)
								<input type="text" name="sito_web" tabindex="-1" autocomplete="off">
							</label>
						</div>

						<label class="slp-consenso">
							<input type="checkbox" name="consenso" value="1" required>
							<span>
								Acconsento al trattamento di nome, email e telefono per gestire
								questa iscrizione e comunicarmi le informazioni sul corso.
								<?php $slp_informativa = get_privacy_policy_url(); ?>
								<?php if ( $slp_informativa ) : ?>
									<a href="<?php echo esc_url( $slp_informativa ); ?>" target="_blank" rel="noopener">Informativa privacy</a>.
								<?php endif; ?>
							</span>
						</label>

						<button type="submit">Iscriviti</button>
						<span class="slp-esito" role="status" aria-live="polite"></span>
					</form>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'slp_elenco_certificati', 'slp_shortcode_elenco_certificati' );
function slp_shortcode_elenco_certificati() {
	ob_start();
	$certificati = slp_elenco_pubblico_certificati();
	$livelli     = slp_livelli();
	?>
	<div class="slp-elenco-certificati">
		<?php if ( empty( $certificati ) ) : ?>
			<p class="slp-vuoto">Ancora nessun certificato pubblicato.</p>
		<?php else : ?>
			<table class="slp-tabella">
				<thead><tr><th scope="col">Nome</th><th scope="col">Livello</th><th scope="col">Numero</th></tr></thead>
				<tbody>
					<?php foreach ( $certificati as $certificato ) : ?>
						<tr>
							<td><?php echo esc_html( get_post_meta( $certificato->ID, 'slp_persona_nome', true ) ); ?></td>
							<td><?php echo esc_html( $livelli[ (int) get_post_meta( $certificato->ID, 'slp_livello', true ) ] ?? '' ); ?></td>
							<td><?php echo esc_html( get_post_meta( $certificato->ID, 'slp_numero', true ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
