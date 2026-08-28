<?php
/**
 * scuole-cucina.php — "Le Scuole di Cucina": vetrine di partner paganti
 * (scuole e corsi di cucina), a pagamento tramite bonifico. Stesso identico
 * schema di artigiani.php ("Gli Artigiani della Pasta"), duplicato apposta
 * per due tipi di partner diversi (negozi/laboratori di pasta fresca contro
 * scuole che insegnano a cucinare) — vedi anche token.php per i costi di
 * abbonamento, tenuti distinti tra le due categorie.
 *
 * CPT gs_scuola (privato come gli altri CPT del progetto: la visibilità
 * pubblica la decide questo file, non WordPress):
 *  - post_title   = nome della scuola
 *  - post_name    = slug, usato nell'indirizzo pubblico (?gs_scu=slug)
 *  - post_author  = il partner (account WordPress creato dal titolare)
 *  - meta gs_scu_comune            = comune/città (testo)
 *  - meta gs_scu_logo              = URL del logo
 *  - meta gs_scu_testo             = racconto libero ("Chi siamo")
 *  - meta gs_scu_media             = [ {url} ] galleria foto (max 6)
 *  - meta gs_scu_youtube           = URL del video di presentazione
 *  - meta gs_scu_indirizzo         = indirizzo (facoltativo: se vuoto, niente
 *                                    riquadro "Dove trovarci" nella vetrina)
 *  - meta gs_scu_email_contatto    = dove arrivano i messaggi del modulo di contatto
 *  - meta gs_scu_stato             = 'vuota' | 'in_attesa' | 'approvata' | 'rifiutata' | 'sospesa'
 *  - meta gs_scu_scadenza          = 'AAAA-MM-GG' fino a quando l'abbonamento è pagato
 *  - meta gs_scu_pagamenti         = [ {data, importo, note} ] storico bonifici (mai sovrascritto, solo aggiunto)
 *
 * Account del partner: stesso schema già usato per i "lettori" (vedi
 * letture.php) e per gli Artigiani della Pasta — meta utente gs_status =
 * 'scuola_cucina' invece di 'in_attesa'/'approvata': gs_is_approved()
 * considera "sfoglina approvata" solo lo stato vuoto o 'approvata', quindi un
 * account 'scuola_cucina' resta escluso da solo da tutto il resto del gaming
 * (Le Sfogline, sfide, corsi…) senza dover toccare gli altri moduli.
 *
 * Visibilità pubblica di una vetrina: SOLO se approvata E con abbonamento in
 * corso (gs_scu_scadenza non passata). Alla scadenza la vetrina si nasconde
 * da sola dalla sezione pubblica, senza bisogno di un intervento manuale;
 * resta comunque modificabile dal partner e riappare non appena il titolare
 * registra un nuovo bonifico con una nuova scadenza. Avvisi automatici di
 * scadenza in avvicinamento: vedi gs_scu_avviso_scadenze() più sotto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// CPT
// -----------------------------------------------------------------------------
add_action( 'init', 'gs_scu_register_cpt' );
function gs_scu_register_cpt() {
	register_post_type( 'gs_scuola', array(
		'labels'       => array(
			'name'          => 'Scuole di Cucina',
			'singular_name' => 'Scuola di Cucina',
		),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'author' ),
	) );
}

// Indirizzo pubblico della vetrina singola: /le-scuole-di-cucina/?gs_scu=slug
add_action( 'init', function () {
	add_rewrite_tag( '%gs_scu%', '([^&]+)' );
} );
add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'gs_scu';
	return $vars;
} );

// -----------------------------------------------------------------------------
// Helper — dati
// -----------------------------------------------------------------------------

/** Dati normalizzati di una scuola, pronti per l'uso nei template. */
function gs_scu_get( $id ) {
	$id = (int) $id;
	$media = get_post_meta( $id, 'gs_scu_media', true );
	$pag   = get_post_meta( $id, 'gs_scu_pagamenti', true );
	return array(
		'id'        => $id,
		'nome'      => get_the_title( $id ),
		'slug'      => get_post_field( 'post_name', $id ),
		'autore'    => (int) get_post_field( 'post_author', $id ),
		'comune'    => (string) get_post_meta( $id, 'gs_scu_comune', true ),
		'logo'      => (string) get_post_meta( $id, 'gs_scu_logo', true ),
		'testo'     => (string) get_post_meta( $id, 'gs_scu_testo', true ),
		'media'     => is_array( $media ) ? $media : array(),
		'youtube'   => (string) get_post_meta( $id, 'gs_scu_youtube', true ),
		'indirizzo' => (string) get_post_meta( $id, 'gs_scu_indirizzo', true ),
		'email'     => (string) get_post_meta( $id, 'gs_scu_email_contatto', true ),
		'stato'     => get_post_meta( $id, 'gs_scu_stato', true ) ?: 'vuota',
		'scadenza'  => (string) get_post_meta( $id, 'gs_scu_scadenza', true ),
		'pagamenti' => is_array( $pag ) ? $pag : array(),
	);
}

/** True se l'abbonamento è in corso (scadenza impostata e non passata). */
function gs_scu_attivo( $id ) {
	$scadenza = get_post_meta( (int) $id, 'gs_scu_scadenza', true );
	if ( ! $scadenza ) {
		return false;
	}
	return $scadenza >= current_time( 'Y-m-d' );
}

/** True se la vetrina è visibile nella sezione pubblica: approvata + abbonamento attivo. */
function gs_scu_pubblicata( $id ) {
	$id = (int) $id;
	return 'approvata' === get_post_meta( $id, 'gs_scu_stato', true ) && gs_scu_attivo( $id );
}

/**
 * Il post gs_scuola posseduto da un utente (uno solo per account), o null.
 *
 * Non usa il parametro 'author' di get_posts(): il tema Newspaper altera in
 * silenzio quel tipo di query (pre_get_posts, non bloccato da
 * suppress_filters — stesso avviso già scritto in gs_solo_tipo()), rendendo
 * il post_type richiesto irrilevante e restituendo sempre zero risultati.
 * Verificato il 25/08/2026, stesso difetto del gemello artigiani.php. Si
 * filtra in PHP sull'elenco già letto da gs_scu_elenco(), poche decine di
 * righe al massimo: costa niente ed è sicuro.
 */
function gs_scu_owner_post( $uid ) {
	$uid = (int) $uid;
	if ( $uid < 1 ) {
		return null;
	}
	foreach ( gs_scu_elenco() as $p ) {
		if ( (int) $p->post_author === $uid ) {
			return $p;
		}
	}
	return null;
}

function gs_scu_is_scuola( $uid ) {
	return null !== gs_scu_owner_post( $uid );
}

/** Tutte le scuole (per il pannello di amministrazione), non cestinate. */
function gs_scu_elenco( $status = 'publish' ) {
	return get_posts( array(
		'post_type'      => 'gs_scuola',
		'post_status'    => $status,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'suppress_filters' => true,
	) );
}

/** URL della vetrina pubblica di una scuola. */
function gs_scu_url( $id ) {
	$slug    = get_post_field( 'post_name', (int) $id );
	$page_id = (int) get_option( 'gs_page_scuole' );
	if ( ! $slug || ! $page_id ) {
		return '';
	}
	return add_query_arg( 'gs_scu', $slug, get_permalink( $page_id ) );
}

/** URL del pannello di autogestione del partner. */
function gs_scu_pannello_url() {
	return function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_scuola_pannello' ) : home_url( '/' );
}

/** Estrae l'ID video da un URL YouTube (watch, youtu.be, shorts, embed). */
function gs_scu_youtube_id( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

function gs_scu_stato_label( $stato ) {
	$labels = array(
		'vuota'     => 'Non ancora compilata',
		'in_attesa' => 'In attesa di approvazione',
		'approvata' => 'Approvata',
		'rifiutata' => 'Rifiutata',
		'sospesa'   => 'Sospesa',
	);
	return $labels[ $stato ] ?? $stato;
}

// -----------------------------------------------------------------------------
// Shortcode pubblico [gs_scuole_cucina] — direttorio + vetrina singola
// -----------------------------------------------------------------------------
add_shortcode( 'gs_scuole_cucina', 'gs_sc_scuole' );
function gs_sc_scuole() {
	$slug = get_query_var( 'gs_scu' );
	if ( ! $slug && isset( $_GET['gs_scu'] ) ) {
		$slug = sanitize_title( wp_unslash( $_GET['gs_scu'] ) );
	}
	if ( $slug ) {
		return gs_scu_render_vetrina_da_slug( $slug );
	}
	return gs_scu_render_direttorio();
}

function gs_scu_render_vetrina_da_slug( $slug ) {
	$posts = get_posts( array(
		'post_type'      => 'gs_scuola',
		'name'           => $slug,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'suppress_filters' => true,
	) );
	if ( ! $posts || ! gs_scu_pubblicata( $posts[0]->ID ) ) {
		return gs_scu_render_non_disponibile();
	}
	return gs_scu_render_vetrina( gs_scu_get( $posts[0]->ID ) );
}

function gs_scu_render_non_disponibile() {
	$dir = function_exists( 'gs_pagina_url' ) ? gs_pagina_url( 'gs_page_scuole' ) : home_url( '/' );
	return '<div class="gs-art"><div class="gs-art-notice">'
		. '<p>Questa vetrina non è al momento disponibile.</p>'
		. '<p><a class="gs-art-btn" href="' . esc_url( $dir ) . '">← Torna a Le Scuole di Cucina</a></p>'
		. '</div></div>';
}

/** Direttorio pubblico: tutte le scuole con vetrina attiva. */
function gs_scu_render_direttorio() {
	$tutti = gs_scu_elenco();
	$attivi = array_filter( $tutti, function ( $p ) { return gs_scu_pubblicata( $p->ID ); } );

	ob_start();
	?>
	<div class="gs-art">
		<div class="gs-art-hero">
			<div class="gs-art-hero-testo">
				<div class="gs-art-eyebrow">Accademia della Sfoglia — Communitas</div>
				<h1>Le Scuole di Cucina</h1>
				<p>Scuole e corsi che insegnano l'arte della cucina, dalla pasta fresca ai grandi classici della tradizione. Ogni vetrina è raccontata e curata direttamente da chi insegna.</p>
			</div>
			<?php echo gs_scu_onde_html(); ?>
		</div>

		<div class="gs-art-wrap gs-art-direttorio">
			<?php if ( ! $attivi ) : ?>
				<p class="gs-art-intro">Non ci sono ancora vetrine pubblicate in questa sezione.</p>
			<?php else : ?>
				<div class="gs-art-griglia">
					<?php foreach ( $attivi as $p ) :
						$a = gs_scu_get( $p->ID );
						$foto = $a['logo'] ? $a['logo'] : ( ! empty( $a['media'][0]['url'] ) ? $a['media'][0]['url'] : '' );
						?>
						<a class="gs-art-card" href="<?php echo esc_url( gs_scu_url( $a['id'] ) ); ?>">
							<div class="gs-art-card-foto"<?php echo $foto ? ' style="background-image:url(\'' . esc_url( $foto ) . '\')"' : ''; ?>>
								<?php if ( ! $foto ) { echo '🎓'; } ?>
							</div>
							<div class="gs-art-card-corpo">
								<?php if ( $a['comune'] ) : ?><div class="gs-art-comune"><?php echo esc_html( $a['comune'] ); ?></div><?php endif; ?>
								<h3><?php echo esc_html( $a['nome'] ); ?></h3>
								<?php if ( $a['testo'] ) : ?><p><?php echo esc_html( wp_trim_words( $a['testo'], 20 ) ); ?></p><?php endif; ?>
								<span class="gs-art-link">Scopri la vetrina →</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/** Vetrina pubblica di una singola scuola. */
function gs_scu_render_vetrina( $a ) {
	$url = gs_scu_url( $a['id'] );
	ob_start();
	?>
	<div class="gs-art">
		<div class="gs-art-hero">
			<div class="gs-art-hero-testo">
				<div class="gs-art-eyebrow">Le Scuole di Cucina<?php echo $a['comune'] ? ' — ' . esc_html( $a['comune'] ) : ''; ?></div>
				<h1><?php echo esc_html( $a['nome'] ); ?></h1>
			</div>
			<?php echo gs_scu_onde_html(); ?>
		</div>

		<?php if ( $url ) : ?>
		<div class="gs-art-wrap gs-art-condividi">
			<?php echo gs_scu_condividi_html( $url, $a['nome'] ); ?>
		</div>
		<?php endif; ?>

		<?php if ( $a['testo'] || $a['logo'] ) : ?>
		<div class="gs-art-wrap gs-art-editoriale">
			<div class="gs-art-grid2">
				<div>
					<div class="gs-art-eyebrow">Chi siamo</div>
					<?php if ( $a['logo'] ) : ?><img class="gs-art-logo" src="<?php echo esc_url( $a['logo'] ); ?>" alt="<?php echo esc_attr( $a['nome'] ); ?>"><?php endif; ?>
				</div>
				<div class="gs-art-testo"><?php echo wp_kses_post( wpautop( esc_html( $a['testo'] ) ) ); ?></div>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $a['media'] ) : ?>
		<div class="gs-art-wrap gs-art-galleria">
			<div class="gs-art-eyebrow gs-art-centro">La scuola in foto</div>
			<div class="gs-art-galleria-griglia">
				<?php foreach ( $a['media'] as $m ) : ?>
					<div class="gs-art-foto" style="background-image:url('<?php echo esc_url( $m['url'] ); ?>')"></div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php $yt = gs_scu_youtube_id( $a['youtube'] ); if ( $yt ) : ?>
		<div class="gs-art-wrap gs-art-video">
			<div class="gs-art-eyebrow gs-art-centro">Il video di presentazione</div>
			<div class="gs-art-video-box">
				<iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $yt ); ?>" title="Video di presentazione" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $a['indirizzo'] || $a['email'] ) : ?>
		<div class="gs-art-wrap gs-art-doppie">
			<div class="gs-art-doppie-grid">
				<?php if ( $a['indirizzo'] ) : ?>
				<div class="gs-art-cardgrande">
					<span class="gs-art-emoji">📍</span>
					<h3>Dove trovarci</h3>
					<p><?php echo esc_html( $a['indirizzo'] ); ?></p>
					<a class="gs-art-btn" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?php echo rawurlencode( $a['indirizzo'] ); ?>">Apri in Google Maps</a>
					<div class="gs-art-mappa">
						<iframe src="https://maps.google.com/maps?q=<?php echo rawurlencode( $a['indirizzo'] ); ?>&output=embed" loading="lazy" title="Mappa"></iframe>
					</div>
				</div>
				<?php endif; ?>
				<?php if ( $a['email'] ) : ?>
				<div class="gs-art-cardgrande">
					<span class="gs-art-emoji">✉️</span>
					<h3>Scrivi a <?php echo esc_html( $a['nome'] ); ?></h3>
					<p>Il messaggio arriva direttamente a loro.</p>
					<form class="gs-scu-form-contatto" data-scuola="<?php echo (int) $a['id']; ?>" onsubmit="return false">
						<?php gs_antispam_fields(); ?>
						<label>Il tuo nome<br><input type="text" name="nome" required></label>
						<label>La tua email<br><input type="email" name="email" required></label>
						<label>Messaggio<br><textarea name="messaggio" rows="4" required></textarea></label>
						<button type="submit" class="gs-art-btn gs-scu-contatta-invia">Invia messaggio</button>
						<span class="gs-scu-form-msg"></span>
					</form>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** Le 5 fasce ondulate del banner (usate sia nel direttorio sia nella vetrina). */
function gs_scu_onde_html() {
	return '<div class="gs-art-onda gs-art-o1"></div><div class="gs-art-onda gs-art-o2"></div>'
		. '<div class="gs-art-onda gs-art-o3"></div><div class="gs-art-onda gs-art-o4"></div><div class="gs-art-onda gs-art-o5"></div>';
}

/**
 * Riga "Condividi": WhatsApp, Facebook e "Copia link", pronta da incollare
 * sia nella vetrina pubblica sia nel pannello del partner (stesso indirizzo
 * in entrambi i casi, così quello che condivide è sempre il link vero).
 */
function gs_scu_condividi_html( $url, $nome ) {
	$testo_wa = $nome . ' — ' . $url;
	$out  = '<div class="gs-art-condividi-riga">';
	$out .= '<span class="gs-art-condividi-etichetta">Condividi:</span>';
	$out .= '<a class="gs-art-condividi-link gs-art-condividi-wa" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text=' . rawurlencode( $testo_wa ) . '">WhatsApp</a>';
	$out .= '<a class="gs-art-condividi-link gs-art-condividi-fb" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) . '">Facebook</a>';
	$out .= '<button type="button" class="gs-art-condividi-link gs-scu-copia-link" data-link="' . esc_attr( $url ) . '">🔗 Copia link</button>';
	$out .= '</div>';
	return $out;
}

// -----------------------------------------------------------------------------
// AJAX pubblico — modulo di contatto
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_nopriv_gs_scu_contatta', 'gs_ajax_scu_contatta' );
add_action( 'wp_ajax_gs_scu_contatta', 'gs_ajax_scu_contatta' );
function gs_ajax_scu_contatta() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	$check = gs_antispam_check( $_POST, 'scu_contatta' );
	if ( is_wp_error( $check ) ) {
		wp_send_json_error( array( 'message' => $check->get_error_message() ) );
	}

	$id  = isset( $_POST['scuola'] ) ? (int) $_POST['scuola'] : 0;
	if ( 'gs_scuola' !== get_post_type( $id ) || ! gs_scu_pubblicata( $id ) ) {
		wp_send_json_error( array( 'message' => 'Vetrina non valida.' ) );
	}
	$dest = get_post_meta( $id, 'gs_scu_email_contatto', true );
	if ( ! $dest || ! is_email( $dest ) ) {
		wp_send_json_error( array( 'message' => 'Questa vetrina non ha ancora un\'email di contatto attiva.' ) );
	}

	$nome     = gs_clean( $_POST['nome'] ?? '' );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$messaggio = isset( $_POST['messaggio'] ) ? sanitize_textarea_field( wp_unslash( $_POST['messaggio'] ) ) : '';
	if ( ! $nome || ! is_email( $email ) || ! $messaggio ) {
		wp_send_json_error( array( 'message' => 'Compila nome, email e messaggio.' ) );
	}

	$nome_attivita = get_the_title( $id );
	$corpo = "Nuovo messaggio dalla vetrina \"$nome_attivita\" (Le Scuole di Cucina):\n\n"
		. "Da: $nome <$email>\n\n$messaggio";

	$ok = wp_mail(
		$dest,
		'Nuovo messaggio dalla tua vetrina — ' . $nome_attivita,
		$corpo,
		array( 'Reply-To: ' . $nome . ' <' . $email . '>' )
	);

	if ( ! $ok ) {
		wp_send_json_error( array( 'message' => 'Invio non riuscito, riprova tra poco.' ) );
	}
	wp_send_json_success( array( 'message' => 'Messaggio inviato! Riceverai risposta direttamente dalla scuola.' ) );
}

// -----------------------------------------------------------------------------
// Pannello di autogestione del partner — shortcode [gs_vetrina_scuola_pannello]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_vetrina_scuola_pannello', 'gs_sc_scu_pannello' );
function gs_sc_scu_pannello() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	$post = gs_scu_owner_post( get_current_user_id() );
	if ( ! $post ) {
		return gs_box_open( 'Area riservata', 'gs-notice' ) . '<p>Questa pagina è riservata alle scuole partner de Le Scuole di Cucina. Se pensi si tratti di un errore, scrivi all\'Accademia.</p>' . gs_box_close();
	}
	$a = gs_scu_get( $post->ID );

	$out  = gs_box_open( '🎓 La Mia Vetrina — ' . $a['nome'], '', 'gs-box-scu-pannello' );
	$out .= '<p><span class="gs-pill">' . esc_html( gs_scu_stato_label( $a['stato'] ) ) . '</span>'
		. ( gs_scu_attivo( $a['id'] ) ? ' <span class="gs-pill gs-pill-verde">Abbonamento attivo fino al ' . esc_html( gs_data_it( $a['scadenza'] ) ) . '</span>' : ' <span class="gs-pill gs-pill-rosso">Abbonamento non attivo</span>' ) . '</p>';

	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<p><strong>Come funziona:</strong> compila i campi qui sotto — logo, testo, foto, video e indirizzo — e salva. Ogni modifica torna "in attesa di approvazione": l\'Accademia la controlla e la pubblica. La vetrina resta online finché l\'abbonamento è attivo; se scade si nasconde da sola dalla sezione pubblica finché l\'Accademia non registra un nuovo pagamento.</p>';
	$out .= '</div>';

	if ( gs_scu_pubblicata( $a['id'] ) ) {
		$url = gs_scu_url( $a['id'] );
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<p><strong>La tua vetrina è online:</strong> puoi condividerla con i tuoi allievi sui social, o incollare il link dove preferisci.</p>';
		$out .= '<p><input type="text" class="gs-scu-link-input" value="' . esc_attr( $url ) . '" readonly onclick="this.select()" style="width:100%;margin-bottom:8px"></p>';
		$out .= gs_scu_condividi_html( $url, $a['nome'] );
		$out .= '</div>';
	} else {
		$out .= '<p class="gs-hint">Il link da condividere sarà disponibile qui non appena la vetrina sarà approvata e l\'abbonamento attivo.</p>';
	}

	// Logo attuale.
	$out .= '<div class="gs-bio-foto-riga">';
	if ( $a['logo'] ) {
		$out .= '<img class="gs-profile-foto" src="' . esc_url( $a['logo'] ) . '" alt="">';
		$out .= '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-scu-logo-rimuovi">Rimuovi logo</button>';
	} else {
		$out .= '<span class="gs-hint">Nessun logo caricato.</span>';
	}
	$out .= '</div>';

	// Galleria attuale.
	if ( $a['media'] ) {
		$out .= '<div class="gs-bio-media-wrap">';
		foreach ( $a['media'] as $i => $m ) {
			$out .= '<div class="gs-bio-media-item">' . gs_msg_media_html( $m['url'], 'image' )
				. '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-scu-media-rimuovi" data-i="' . (int) $i . '">Rimuovi</button></div>';
		}
		$out .= '</div>';
	}

	$out .= '<form class="gs-form gs-form-scu" onsubmit="return false">';
	$out .= '<p><label>Logo dell\'attività (facoltativo)<br><input type="file" name="logo" class="gs-scu-logo-file" accept="image/*"></label></p>';
	$out .= '<p><label>Comune / città<br><input type="text" name="comune" value="' . esc_attr( $a['comune'] ) . '" style="width:100%"></label></p>';
	$out .= '<p><label>Racconto — "Chi siamo" (testo libero)<br><textarea name="testo" rows="6" style="width:100%">' . esc_textarea( $a['testo'] ) . '</textarea></label></p>';
	$out .= '<p><label>Aggiungi una foto alla galleria (fino a 6 in tutto, ridotte automaticamente)<br><input type="file" name="foto" class="gs-scu-foto-file" accept="image/*"></label></p>';
	$out .= '<p><label>Link del video di presentazione (YouTube)<br><input type="url" name="youtube" value="' . esc_attr( $a['youtube'] ) . '" placeholder="https://www.youtube.com/watch?v=…" style="width:100%"></label></p>';
	$out .= '<p><label>Indirizzo (per la mappa e "Apri in Google Maps" — lascia vuoto per non mostrarlo)<br><input type="text" name="indirizzo" value="' . esc_attr( $a['indirizzo'] ) . '" style="width:100%"></label></p>';
	$out .= '<p><label>Email a cui arrivano i messaggi dei visitatori<br><input type="email" name="email" value="' . esc_attr( $a['email'] ) . '" style="width:100%"></label></p>';
	$out .= '<p><button class="gs-btn gs-scu-salva">Salva e invia per approvazione</button> <span class="gs-scu-msg gs-richiesta-esito"></span></p>';
	$out .= '</form>';

	$out .= gs_box_close();
	return $out;
}

// gs_data_it() è già definita in artigiani.php (caricato prima di questo
// file): riusata qui, non ridichiarata, per evitare l'errore fatale "Cannot
// redeclare gs_data_it()" che ha bloccato l'attivazione del plugin.

add_action( 'wp_ajax_gs_scu_salva', 'gs_ajax_scu_salva' );
function gs_ajax_scu_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	$post = gs_scu_owner_post( $uid );
	if ( ! $post ) { wp_send_json_error( array( 'message' => 'Nessuna vetrina collegata a questo account.' ) ); }
	$id = $post->ID;

	$comune    = gs_clean( $_POST['comune'] ?? '' );
	$testo     = isset( $_POST['testo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testo'] ) ) : '';
	$youtube   = isset( $_POST['youtube'] ) ? esc_url_raw( wp_unslash( $_POST['youtube'] ) ) : '';
	$indirizzo = gs_clean( $_POST['indirizzo'] ?? '' );
	$email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

	if ( $email && ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'L\'email di contatto non è valida.' ) );
	}

	update_post_meta( $id, 'gs_scu_comune', $comune );
	update_post_meta( $id, 'gs_scu_testo', $testo );
	update_post_meta( $id, 'gs_scu_youtube', $youtube );
	update_post_meta( $id, 'gs_scu_indirizzo', $indirizzo );
	update_post_meta( $id, 'gs_scu_email_contatto', $email );

	if ( function_exists( 'gs_msg_upload' ) ) {
		$logo = gs_msg_upload( 'logo' );
		if ( is_wp_error( $logo ) ) { wp_send_json_error( array( 'message' => $logo->get_error_message() ) ); }
		if ( is_array( $logo ) ) {
			if ( 'image' !== $logo['type'] ) { wp_send_json_error( array( 'message' => 'Il logo deve essere un\'immagine.' ) ); }
			update_post_meta( $id, 'gs_scu_logo', $logo['url'] );
		}

		$foto = gs_msg_upload( 'foto' );
		if ( is_wp_error( $foto ) ) { wp_send_json_error( array( 'message' => $foto->get_error_message() ) ); }
		if ( is_array( $foto ) ) {
			if ( 'image' !== $foto['type'] ) { wp_send_json_error( array( 'message' => 'Nella galleria puoi aggiungere solo immagini.' ) ); }
			$media = get_post_meta( $id, 'gs_scu_media', true );
			if ( ! is_array( $media ) ) { $media = array(); }
			if ( count( $media ) >= 6 ) {
				wp_send_json_error( array( 'message' => 'Hai già raggiunto il massimo di 6 foto: rimuovine una per aggiungerne un\'altra.' ) );
			}
			$media[] = array( 'url' => $foto['url'] );
			update_post_meta( $id, 'gs_scu_media', $media );
		}
	}

	// Ogni modifica torna in attesa di approvazione.
	update_post_meta( $id, 'gs_scu_stato', 'in_attesa' );

	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea(
			'Vetrina Scuola di Cucina da approvare: ' . get_the_title( $id ),
			'Una scuola di cucina ha inviato o modificato la propria vetrina. Va approvata dalla Plancia Generale per tornare visibile nella sezione pubblica.',
			array( 'author' => $uid, 'link_ancora' => 'admin.php?page=gs-generale#gs-zona-scuole' )
		);
	}
	wp_send_json_success( array( 'message' => 'Vetrina salvata e inviata per approvazione.' ) );
}

add_action( 'wp_ajax_gs_scu_logo_rimuovi', 'gs_ajax_scu_logo_rimuovi' );
function gs_ajax_scu_logo_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$post = gs_scu_owner_post( get_current_user_id() );
	if ( ! $post ) { wp_send_json_error( array( 'message' => 'Nessuna vetrina collegata a questo account.' ) ); }
	delete_post_meta( $post->ID, 'gs_scu_logo' );
	update_post_meta( $post->ID, 'gs_scu_stato', 'in_attesa' );
	wp_send_json_success( array( 'message' => 'Logo rimosso.' ) );
}

add_action( 'wp_ajax_gs_scu_media_rimuovi', 'gs_ajax_scu_media_rimuovi' );
function gs_ajax_scu_media_rimuovi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$post = gs_scu_owner_post( get_current_user_id() );
	if ( ! $post ) { wp_send_json_error( array( 'message' => 'Nessuna vetrina collegata a questo account.' ) ); }
	$i = isset( $_POST['i'] ) ? (int) $_POST['i'] : -1;
	$media = get_post_meta( $post->ID, 'gs_scu_media', true );
	if ( ! is_array( $media ) || ! isset( $media[ $i ] ) ) {
		wp_send_json_error( array( 'message' => 'Foto non trovata.' ) );
	}
	unset( $media[ $i ] );
	update_post_meta( $post->ID, 'gs_scu_media', array_values( $media ) );
	update_post_meta( $post->ID, 'gs_scu_stato', 'in_attesa' );
	wp_send_json_success( array( 'message' => 'Foto rimossa.' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO DI AMMINISTRAZIONE (Plancia Generale)
// -----------------------------------------------------------------------------
function gs_pannello_scuole() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_sezione_aiuto( 'Qui crei e controlli i partner de "Le Scuole di Cucina": aggiungi una scuola (viene creato subito l\'account con cui accederà al proprio pannello), controlli/approvi le modifiche che invia, registri i bonifici ricevuti e la nuova scadenza. Una vetrina compare nella sezione pubblica SOLO se è approvata E ha l\'abbonamento attivo: se l\'abbonamento scade, si nasconde da sola finché non registri un nuovo pagamento. Il costo dell\'abbonamento è indipendente da quello degli Artigiani della Pasta: si imposta a parte nel pannello Token.' );

	echo '<h4>Aggiungi una nuova Scuola</h4>';
	echo '<form class="gs-form gs-form-scu-crea" onsubmit="return false">';
	echo '<p><label>Nome della scuola<br><input type="text" name="nome" style="width:100%" required></label></p>';
	echo '<p><label>Comune / città<br><input type="text" name="comune" style="width:100%"></label></p>';
	echo '<p><label>Email della persona di riferimento (riceverà il link per impostare la password)<br><input type="email" name="email" style="width:100%" required></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-scu-crea-invia">Crea account e vetrina</button> <span class="gs-scu-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$tutti = gs_scu_elenco();
	echo '<h4 style="margin-top:18px">Scuole di Cucina (' . count( $tutti ) . ')</h4>';
	if ( ! $tutti ) {
		echo '<p class="gs-hint">Nessuna scuola ancora creata.</p>';
	} else {
		echo '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $tutti as $p ) {
			$a = gs_scu_get( $p->ID );
			$u = get_userdata( $a['autore'] );
			$attivo = gs_scu_attivo( $a['id'] );
			echo '<details class="gs-inbox-item' . ( 'in_attesa' === $a['stato'] ? ' gs-non-letto gs-attesa-forte' : '' ) . '" data-scu="' . (int) $a['id'] . '">';
			echo '<summary class="gs-inbox-oggetto">' . ( 'in_attesa' === $a['stato'] ? '<span class="gs-dot"></span> ' : '' ) . esc_html( $a['nome'] )
				. ( $a['comune'] ? ' — ' . esc_html( $a['comune'] ) : '' )
				. ' <span class="gs-msg-data">' . esc_html( gs_scu_stato_label( $a['stato'] ) ) . ( $attivo ? ' · attivo fino al ' . esc_html( gs_data_it( $a['scadenza'] ) ) : ' · abbonamento non attivo' ) . '</span></summary>';
			echo '<div class="gs-inbox-corpo">';
			echo '<p>Account: <strong>' . esc_html( $u ? $u->display_name . ' <' . $u->user_email . '>' : '—' ) . '</strong></p>';
			if ( $a['logo'] ) { echo '<img class="gs-profile-foto" src="' . esc_url( $a['logo'] ) . '" alt="">'; }
			if ( $a['testo'] ) { echo '<div class="gs-bio-testo">' . nl2br( esc_html( $a['testo'] ) ) . '</div>'; }
			if ( $a['media'] ) {
				echo '<div class="gs-bio-media-wrap">';
				foreach ( $a['media'] as $m ) { echo gs_msg_media_html( $m['url'], 'image' ); }
				echo '</div>';
			}
			if ( $a['youtube'] ) { echo '<p>🎬 Video: <a href="' . esc_url( $a['youtube'] ) . '" target="_blank" rel="noopener">' . esc_html( $a['youtube'] ) . '</a></p>'; }
			if ( $a['indirizzo'] ) { echo '<p>📍 ' . esc_html( $a['indirizzo'] ) . '</p>'; }
			if ( $a['email'] ) { echo '<p>✉️ ' . esc_html( $a['email'] ) . '</p>'; }
			if ( gs_scu_pubblicata( $a['id'] ) ) {
				echo '<p><a class="gs-btn gs-btn-sm gs-btn-ghost" href="' . esc_url( gs_scu_url( $a['id'] ) ) . '" target="_blank" rel="noopener">Vedi la vetrina pubblica</a></p>';
			}

			echo '<p>';
			if ( 'approvata' !== $a['stato'] ) {
				echo '<button class="gs-btn gs-btn-sm gs-scu-modera" data-scu="' . (int) $a['id'] . '" data-esito="approvata">Approva</button> ';
			}
			if ( 'sospesa' !== $a['stato'] && 'vuota' !== $a['stato'] ) {
				echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-scu-modera" data-scu="' . (int) $a['id'] . '" data-esito="sospesa">Sospendi</button> ';
			}
			echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-scu-modera" data-scu="' . (int) $a['id'] . '" data-esito="rifiutata">Rifiuta</button> ';
			echo '<span class="gs-scu-mod-msg gs-richiesta-esito"></span></p>';

			echo '<details class="gs-sezione-aiuto"><summary>💶 Registra un bonifico</summary>';
			echo '<form class="gs-form gs-form-scu-pagamento" data-scu="' . (int) $a['id'] . '" onsubmit="return false">';
			echo '<p><label>Importo ricevuto (€)<br><input type="text" name="importo" placeholder="es. 120,00" style="width:140px"></label></p>';
			echo '<p><label>Abbonamento attivo fino al<br><input type="date" name="scadenza" value="' . esc_attr( $a['scadenza'] ) . '"></label></p>';
			echo '<p><label>Note (facoltativo)<br><input type="text" name="note" style="width:100%"></label></p>';
			echo '<p><button class="gs-btn gs-btn-sm gs-scu-pagamento-invia">Registra pagamento</button> <span class="gs-scu-pag-msg gs-richiesta-esito"></span></p>';
			echo '</form>';
			if ( $a['pagamenti'] ) {
				echo '<table class="gs-tabella-semplice"><tr><th>Data registrazione</th><th>Importo</th><th>Scadenza impostata</th><th>Note</th></tr>';
				foreach ( array_reverse( $a['pagamenti'] ) as $pag ) {
					echo '<tr><td>' . esc_html( gs_data_it( $pag['data'] ?? '' ) ) . '</td><td>€ ' . number_format( (float) ( $pag['importo'] ?? 0 ), 2, ',', '.' ) . '</td><td>' . esc_html( gs_data_it( $pag['scadenza'] ?? '' ) ) . '</td><td>' . esc_html( $pag['note'] ?? '' ) . '</td></tr>';
				}
				echo '</table>';
			}
			echo '</details>';

			echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-scu-cestina" data-scu="' . (int) $a['id'] . '">🗑️ Sposta nel cestino</button></p>';
			echo '</div></details>';
		}
		echo '</div>';
	}

	$cestino = gs_scu_elenco( 'trash' );
	echo '<details class="gs-todo-cestino"><summary>🗑️ Cestino Scuole di Cucina (' . count( $cestino ) . ')</summary>';
	if ( ! $cestino ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		foreach ( $cestino as $p ) {
			echo '<p>' . esc_html( get_the_title( $p ) ) . ' <button class="gs-btn gs-btn-sm gs-scu-ripristina" data-scu="' . (int) $p->ID . '">↺ Ripristina</button></p>';
		}
	}
	echo '</details>';
}

add_action( 'wp_ajax_gs_scu_crea', 'gs_ajax_scu_crea' );
function gs_ajax_scu_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$nome   = gs_clean( $_POST['nome'] ?? '' );
	$comune = gs_clean( $_POST['comune'] ?? '' );
	$email  = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! $nome || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Scrivi il nome dell\'attività e un\'email valida.' ) );
	}

	$user_id     = null;
	$email_inviata = false;
	if ( email_exists( $email ) ) {
		$existing = get_user_by( 'email', $email );
		$user_id  = $existing ? $existing->ID : null;
	}

	// Un account che esiste già può essere una sfoglina, o Ennio stesso.
	// Renderlo "scuola_cucina" la toglie da Le Sfogline, dal nastro, dal
	// contatore e dalle classifiche, in silenzio: stesso incidente reale
	// dell'8 agosto 2026 già capitato con il gemello artigiani.php. Da qui
	// in avanti si può fare, ma solo dicendo di chi si tratta e facendolo
	// confermare (trovato il 25/08/2026).
	if ( $user_id && empty( $_POST['conferma'] ) ) {
		$stato_ora = get_user_meta( $user_id, 'gs_status', true );
		$e_admin   = user_can( $user_id, 'manage_options' );
		if ( $e_admin || ! in_array( $stato_ora, array( 'artigiano', 'scuola_cucina', 'lettore' ), true ) ) {
			$chi = get_userdata( $user_id );
			wp_send_json_error( array(
				'message'  => 'L\'email ' . $email . ' è già di ' . $chi->display_name
					. ( $e_admin ? ' (un amministratore del sito)' : ' (una sfoglina del gaming)' )
					. '. Collegandogli questa vetrina, quell\'account diventa un partner ed esce da Le Sfogline, dal nastro e dalle classifiche. Se è voluto, conferma; altrimenti usa un\'altra email.',
				'conferma' => true,
			) );
		}
	}

	// Una vetrina per account: gs_scu_owner_post() ne restituisce una sola,
	// quindi una seconda resterebbe invisibile al partner (trovato il
	// 25/08/2026).
	if ( $user_id && gs_scu_owner_post( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Questo account ha già una vetrina: modificala invece di crearne una seconda (la seconda resterebbe invisibile al partner).' ) );
	}

	if ( ! $user_id ) {
		$user_id = wp_insert_user( array(
			'user_login'   => gs_username_da_email( $email ),
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 20 ),
			'display_name' => $nome,
			'role'         => 'subscriber',
		) );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}
		// wp_new_user_notification() non segnala se l'invio è davvero riuscito
		// (dipende da wp_mail/SMTP, silenzioso in caso di fallimento): il
		// messaggio qui sotto lo dice sempre come "tentata", non "garantita".
		wp_new_user_notification( $user_id, null, 'user' );
		$email_inviata = true;
	}
	update_user_meta( $user_id, 'gs_status', 'scuola_cucina' );

	$post_id = wp_insert_post( array(
		'post_type'   => 'gs_scuola',
		'post_status' => 'publish',
		'post_title'  => $nome,
		'post_author' => $user_id,
	) );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
	}
	if ( $comune ) {
		update_post_meta( $post_id, 'gs_scu_comune', $comune );
	}
	update_post_meta( $post_id, 'gs_scu_stato', 'vuota' );

	if ( $email_inviata ) {
		$msg = $nome . ' è stato creato. Se il sito riesce a spedire email (controllalo in Diagnostica → "Invia email di prova"), riceverà a breve il link per impostare la password.';
	} else {
		// Dire di chi si tratta anche quando è voluto — prima si sapeva solo
		// "collegato a quell'account esistente", senza il nome (25/08/2026).
		$chi_finale = get_userdata( $user_id );
		$msg = $nome . ' è stato creato, ma con l\'email ' . $email . ' già di ' . ( $chi_finale ? $chi_finale->display_name : 'un account esistente' )
			. ': collegato a quell\'account esistente, quindi NON gli è stata mandata nessuna nuova email — deve accedere con le credenziali che ha già, oppure usa "Password dimenticata" nella pagina di accesso. Da adesso è un partner e non compare più fra le sfogline; per annullare, sposta la vetrina nel cestino.';
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

add_action( 'wp_ajax_gs_scu_modera', 'gs_ajax_scu_modera' );
function gs_ajax_scu_modera() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id    = isset( $_POST['scu'] ) ? (int) $_POST['scu'] : 0;
	$esito = isset( $_POST['esito'] ) ? sanitize_key( wp_unslash( $_POST['esito'] ) ) : '';
	if ( 'gs_scuola' !== get_post_type( $id ) || ! in_array( $esito, array( 'approvata', 'rifiutata', 'sospesa' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Dati non validi.' ) );
	}
	update_post_meta( $id, 'gs_scu_stato', $esito );

	$autore = (int) get_post_field( 'post_author', $id );
	$u      = $autore ? get_userdata( $autore ) : null;
	if ( $u && $u->user_email ) {
		$msg = array(
			'approvata' => "Ciao " . $u->display_name . ",\n\nla tua vetrina \"" . get_the_title( $id ) . "\" è stata approvata. Se anche l'abbonamento è attivo, è già visibile in \"Le Scuole di Cucina\".",
			'rifiutata' => "Ciao " . $u->display_name . ",\n\nla tua vetrina \"" . get_the_title( $id ) . "\" non è stata approvata così com'è. Puoi modificarla dal tuo pannello e inviarla di nuovo.",
			'sospesa'   => "Ciao " . $u->display_name . ",\n\nla tua vetrina \"" . get_the_title( $id ) . "\" è stata sospesa e non è più visibile pubblicamente.",
		);
		wp_mail( $u->user_email, 'La tua vetrina — Accademia della Sfoglia', $msg[ $esito ] );
	}
	wp_send_json_success( array( 'message' => 'Stato aggiornato: ' . gs_scu_stato_label( $esito ) . '.' ) );
}

add_action( 'wp_ajax_gs_scu_pagamento', 'gs_ajax_scu_pagamento' );
function gs_ajax_scu_pagamento() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id       = isset( $_POST['scu'] ) ? (int) $_POST['scu'] : 0;
	$importo  = (float) str_replace( ',', '.', $_POST['importo'] ?? 0 );
	$scadenza = isset( $_POST['scadenza'] ) ? sanitize_text_field( wp_unslash( $_POST['scadenza'] ) ) : '';
	$note     = gs_clean( $_POST['note'] ?? '' );
	$rif      = isset( $_POST['rif'] ) ? sanitize_text_field( wp_unslash( $_POST['rif'] ) ) : '';
	$conferma = ! empty( $_POST['conferma'] );
	if ( 'gs_scuola' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Scuola non valida.' ) ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) { wp_send_json_error( array( 'message' => 'Indica una data di scadenza valida.' ) ); }
	if ( $importo <= 0 ) {
		wp_send_json_error( array( 'message' => 'Indica l\'importo ricevuto (maggiore di zero).' ) );
	}

	// La data arriva già compilata con la scadenza in corso (vedi il modulo):
	// registrare un bonifico senza toccarla è il modo più facile di
	// incassare un rinnovo e non rinnovare niente. Una scadenza che non
	// allunga si può fare (una rettifica può volerla accorciare davvero),
	// ma va confermata apposta — non lasciata passare in silenzio
	// (trovato il 25/08/2026).
	$scadenza_ora = (string) get_post_meta( $id, 'gs_scu_scadenza', true );
	if ( $scadenza_ora && $scadenza <= $scadenza_ora && ! $conferma ) {
		wp_send_json_error( array(
			'message'  => 'La scadenza indicata (' . gs_data_it( $scadenza ) . ') non è successiva a quella attuale (' . gs_data_it( $scadenza_ora ) . '): la vetrina non resterà online più a lungo. Se è voluto, conferma.',
			'conferma' => true,
		) );
	}

	$log = get_post_meta( $id, 'gs_scu_pagamenti', true );
	if ( ! is_array( $log ) ) { $log = array(); }

	// Blocca il doppio invio, stesso schema già usato per i pagamenti del
	// calendario (C1), l'accredito dei token (T1) e il gemello in
	// artigiani.php: l'identificativo arriva dalla scheda, si controlla e si
	// scrive PRIMA di toccare il registro — ma DOPO i controlli sopra,
	// altrimenti una richiesta respinta per la scadenza segnerebbe il rif
	// come "già visto" e il reinvio confermato dallo stesso clic verrebbe
	// rifiutato per errore. Il registro è per vetrina, non per persona: qui
	// non serve il tetto di 50 messo sui token, un partner registra uno o
	// due bonifici l'anno.
	$visti = get_post_meta( $id, 'gs_scu_pag_rif', true );
	if ( ! is_array( $visti ) ) { $visti = array(); }
	if ( $rif && in_array( $rif, $visti, true ) ) {
		wp_send_json_error( array( 'message' => 'Questo pagamento risulta già registrato.' ) );
	}
	if ( $rif ) {
		$visti[] = $rif;
		update_post_meta( $id, 'gs_scu_pag_rif', $visti );
	} elseif ( $log ) {
		// Rete di sicurezza per quando l'identificativo non arriva (browser
		// con JavaScript vecchio in cache). La finestra è la giornata, non i
		// 15 secondi del calendario: una scuola che versa due volte lo
		// stesso importo con la stessa scadenza nello stesso giorno non
		// esiste davvero.
		$ultima = end( $log );
		if ( abs( (float) ( $ultima['importo'] ?? 0 ) - $importo ) < 0.005
			&& (string) ( $ultima['scadenza'] ?? '' ) === (string) $scadenza
			&& ( $ultima['data'] ?? '' ) === current_time( 'Y-m-d' ) ) {
			wp_send_json_error( array( 'message' => 'Un bonifico identico è già stato registrato oggi per questa scuola: se è davvero un secondo versamento, aggiungi una nota che lo distingua.' ) );
		}
	}

	$log[] = array( 'data' => current_time( 'Y-m-d' ), 'importo' => $importo, 'scadenza' => $scadenza, 'note' => $note );
	update_post_meta( $id, 'gs_scu_pagamenti', $log );
	update_post_meta( $id, 'gs_scu_scadenza', $scadenza );

	wp_send_json_success( array( 'message' => 'Pagamento registrato. Abbonamento attivo fino al ' . gs_data_it( $scadenza ) . '.' ) );
}

add_action( 'wp_ajax_gs_scu_cestina', 'gs_ajax_scu_cestina' );
function gs_ajax_scu_cestina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['scu'] ) ? (int) $_POST['scu'] : 0;
	if ( 'gs_scuola' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Scuola non valida.' ) ); }

	// Se l'account collegato era stato reso "scuola_cucina" da questa vetrina (es.
	// per errore, agganciando l'email di una sfoglina già esistente — caso
	// reale del 2026-08-08), cestinare la vetrina la fa tornare una sfoglina
	// normale: stato utente vuoto = "approvata" per gs_is_approved(), stessa
	// regola già usata per gli account preesistenti.
	$autore   = (int) get_post_field( 'post_author', $id );
	$ripristinato_sfoglina = false;
	if ( $autore && 'scuola_cucina' === get_user_meta( $autore, 'gs_status', true ) ) {
		delete_user_meta( $autore, 'gs_status' );
		$ripristinato_sfoglina = true;
	}

	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostato nel cestino.' . ( $ripristinato_sfoglina ? ' L\'account collegato è tornato una sfoglina normale.' : '' ) ) );
}

add_action( 'wp_ajax_gs_scu_ripristina', 'gs_ajax_scu_ripristina' );
function gs_ajax_scu_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['scu'] ) ? (int) $_POST['scu'] : 0;
	if ( 'gs_scuola' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Scuola non valida.' ) ); }
	wp_untrash_post( $id );

	// Simmetrico a gs_ajax_scu_cestina(): se al momento del cestino l'account
	// era tornato sfoglina normale, ripristinando la vetrina torna "scuola_cucina".
	$autore = (int) get_post_field( 'post_author', $id );
	if ( $autore && 'scuola_cucina' !== get_user_meta( $autore, 'gs_status', true ) ) {
		update_user_meta( $autore, 'gs_status', 'scuola_cucina' );
	}

	wp_send_json_success( array( 'message' => 'Ripristinato.' ) );
}

// -----------------------------------------------------------------------------
// Avviso automatico di scadenza in arrivo (email al partner + avviso nei
// pannelli generali) — stesso schema di gs_art_controlla_scadenze() in
// artigiani.php, gira sul cron giornaliero già esistente del plugin.
// -----------------------------------------------------------------------------
add_action( 'gs_daily_cron', 'gs_scu_controlla_scadenze' );
function gs_scu_controlla_scadenze() {
	$giorni_preavviso = 7;
	$oggi = current_time( 'timestamp' );
	foreach ( gs_scu_elenco() as $p ) {
		$scadenza = get_post_meta( $p->ID, 'gs_scu_scadenza', true );
		if ( ! $scadenza || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $scadenza ) ) { continue; }
		$ts = strtotime( $scadenza . ' 00:00:00' );
		if ( false === $ts ) { continue; }
		$giorni_mancanti = (int) floor( ( $ts - $oggi ) / DAY_IN_SECONDS );

		// Tre momenti invece di uno: sette giorni prima, l'ultimo giorno, e
		// il giorno in cui la vetrina si è nascosta davvero. Prima c'era un
		// solo avviso a sette giorni e poi silenzio, proprio nei giorni che
		// contano (trovato il 25/08/2026). Oltre 3 giorni dopo la scadenza,
		// niente: la vetrina è già nascosta da un pezzo, il partner è già
		// stato avvisato tre volte.
		if ( $giorni_mancanti > $giorni_preavviso || $giorni_mancanti < -3 ) { continue; }
		if ( $giorni_mancanti >= 0 && $giorni_mancanti <= 1 ) { $fase = 'ultimo'; }
		elseif ( $giorni_mancanti < 0 ) { $fase = 'scaduto'; }
		else { $fase = 'preavviso'; }

		// Il marcatore tiene scadenza + fase, così ogni fase parte una volta
		// sola anche se il cron gira più volte o salta dei giorni. Sulle
		// vetrine già avvisate col codice precedente, gs_scu_avviso_per vale
		// solo la data (es. "2026-11-01"): non corrisponde più a nessun
		// marcatore nuovo, quindi il preavviso riparte una volta — un avviso
		// in più, non uno in meno, accettabile e da sapere prima di
		// installare.
		$marcatore = $scadenza . '|' . $fase;
		if ( get_post_meta( $p->ID, 'gs_scu_avviso_per', true ) === $marcatore ) { continue; }
		// Scritto PRIMA di mandare, non dopo: la regola della chiusura del mese.
		update_post_meta( $p->ID, 'gs_scu_avviso_per', $marcatore );

		$autore = (int) get_post_field( 'post_author', $p->ID );
		$u = $autore ? get_userdata( $autore ) : null;
		$testi = array(
			'preavviso' => array(
				'oggetto_mail' => "L'abbonamento della tua vetrina sta per scadere — Accademia della Sfoglia",
				'corpo_mail'   => "l'abbonamento della tua vetrina \"" . get_the_title( $p->ID ) . "\" su \"Le Scuole di Cucina\" scade il " . date_i18n( 'j F Y', $ts ) . ".\n\nPer restare visibile nella sezione pubblica, rinnova con un bonifico prima di questa data: scrivi all'Accademia per i dati e la causale.",
				'inbox'        => false,
			),
			'ultimo' => array(
				'oggetto_mail' => "La tua vetrina scade domani — Accademia della Sfoglia",
				'corpo_mail'   => "l'abbonamento della tua vetrina \"" . get_the_title( $p->ID ) . "\" su \"Le Scuole di Cucina\" scade il " . date_i18n( 'j F Y', $ts ) . ": da domani, se non rinnovi, non sarà più visibile nella sezione pubblica.\n\nRinnova con un bonifico: scrivi all'Accademia per i dati e la causale.",
				'inbox'        => true,
			),
			'scaduto' => array(
				'oggetto_mail' => "La tua vetrina non è più visibile — Accademia della Sfoglia",
				'corpo_mail'   => "l'abbonamento della tua vetrina \"" . get_the_title( $p->ID ) . "\" su \"Le Scuole di Cucina\" è scaduto: la vetrina non è più visibile nella sezione pubblica.\n\nSi riaccende non appena registriamo il rinnovo: scrivi all'Accademia per i dati del bonifico.",
				'inbox'        => true,
			),
		);
		$t = $testi[ $fase ];

		if ( $u && $u->user_email ) {
			$corpo = "Ciao " . $u->display_name . ",\n\n" . $t['corpo_mail'] . "\n\nA presto,\nAccademia della Sfoglia";
			wp_mail( $u->user_email, $t['oggetto_mail'], $corpo );
		}
		if ( $t['inbox'] && function_exists( 'gs_inbox_crea' ) ) {
			gs_inbox_crea(
				( 'scaduto' === $fase ? 'Vetrina scaduta' : 'Abbonamento in scadenza domani' ) . ' — Scuole di Cucina: ' . get_the_title( $p->ID ),
				'scaduto' === $fase
					? 'Scaduto il ' . date_i18n( 'j/m/Y', $ts ) . ', vetrina non più visibile. Registra un nuovo bonifico dalla zona "Scuole di Cucina" per riaccenderla.'
					: 'Scade domani, ' . date_i18n( 'j/m/Y', $ts ) . '. Registra un nuovo bonifico dalla zona "Scuole di Cucina" per rinnovarlo, altrimenti la vetrina si nasconderà da sola dalla sezione pubblica.',
				array( 'author' => $autore )
			);
		}
	}
}
