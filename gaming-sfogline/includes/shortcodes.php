<?php
/**
 * shortcodes.php — Tutti gli shortcode front-end visibili alle sfogline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper: apre un box stilizzato del plugin.
 */
function gs_box_open( $title = '', $class = '', $id = '' ) {
	$out = '<div class="gs-box ' . esc_attr( $class ) . '"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>';
	if ( $title ) {
		$out .= '<h3 class="gs-box-title">' . esc_html( $title ) . '</h3>';
	}
	return $out;
}
function gs_box_close() {
	return '</div>';
}

/**
 * Helper: messaggio "accedi prima".
 */
function gs_login_notice() {
	return '<div class="gs-box gs-notice">Per usare questa sezione devi <a href="' . esc_url( gs_login_url( get_permalink() ) ) . '">accedere</a>.</div>';
}

/**
 * Il cancello dell'area riservata: una sola porta per due modi di restarne
 * fuori. Ritorna '' se si può passare, altrimenti l'HTML da mostrare al
 * posto della sezione.
 *
 * Sostituisce, uno a uno, i controlli "if ( ! is_user_logged_in() ) return
 * gs_login_notice();" già sparsi nel plugin: quel confine — chi chiede
 * l'accesso e chi no — è già la distinzione fra le pagine "in chiaro" e il
 * gaming che Ennio ha chiesto il 26/08/2026 (documento dei trenta giorni).
 */
function gs_gate_riservato() {
	if ( ! is_user_logged_in() ) {
		return gs_login_notice();
	}
	if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( get_current_user_id() ) ) {
		return function_exists( 'gs_congelata_avviso' ) ? gs_congelata_avviso() : '';
	}
	return '';
}

/**
 * URL della pagina di accesso: quella del plugin (gs_page_login) se esiste,
 * altrimenti il login generico di WordPress. $redirect_to (facoltativo) è
 * la pagina a cui tornare dopo l'accesso riuscito.
 */
function gs_login_url( $redirect_to = '' ) {
	$pid = (int) get_option( 'gs_page_login' );
	if ( ! $pid ) {
		return wp_login_url( $redirect_to );
	}
	$url = get_permalink( $pid );
	return $redirect_to ? add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url ) : $url;
}

/**
 * Helper: riquadro collassabile "Come funziona questa sezione", stesso
 * schema del titolo cliccabile usato ovunque nel plugin (chiuso di default,
 * non ingombra la pagina). Va messo subito dopo gs_box_open() nelle
 * sezioni rivolte alle sfogline.
 */
function gs_sezione_aiuto( $testo ) {
	return '<details class="gs-sezione-aiuto"><summary>ℹ️ Come funziona questa sezione</summary><p>' . esc_html( $testo ) . '</p></details>';
}

// -----------------------------------------------------------------------------
// [gs_registrazione]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_registrazione', 'gs_sc_registrazione' );
function gs_sc_registrazione() {
	if ( is_user_logged_in() ) {
		$reg = gs_decl( get_current_user_id(), 'registrata', 'registrato' );
		return '<div class="gs-box gs-notice">Sei già ' . esc_html( $reg ) . '. Vai a <a href="' . esc_url( get_permalink( get_option( 'gs_page_dashboard' ) ) ) . '">La Mia Sfoglia</a>.</div>';
	}

	$teams = gs_get_teams();

	ob_start();
	?>
	<div class="gs-box">
		<p>Diventare membri dell'Accademia della Sfoglia non costa nulla: l'iscrizione è aperta a tutti e completamente gratuita, senza alcuna condizione nascosta. Ma non finisce qui: chi si iscrive riceve in omaggio un mese intero di accesso all'area riservata del sito, uno spazio esclusivo pensato per chi vuole andare oltre, pieno di contenuti e sorprese mai proposti prima d'ora. Un modo semplice e senza rischi per scoprire da vicino il mondo della sfoglia, prima ancora di decidere se restare.</p>
	</div>
	<?php
	echo gs_sezione_aiuto( 'Compila tutti i campi richiesti e invia: il tuo account resta in attesa finché la segreteria non lo controlla e lo approva. Riceverai accesso completo solo dopo l\'approvazione.' );
	?>
	<form class="gs-form" id="gs-form-registrazione" data-action="gs_registrati">
		<?php gs_antispam_fields(); ?>
		<p><label>Nome e cognome *<br><input type="text" name="nome" required></label></p>
		<p><label>Sei... *<br>
			<label><input type="radio" name="genere" value="f" checked> Sfoglina</label>
			<label style="margin-left:14px"><input type="radio" name="genere" value="m"> Sfoglino</label>
		</label></p>
		<p><label>Email *<br><input type="email" name="email" required></label></p>
		<p><label>Username *<br><input type="text" name="username" required></label></p>
		<p><label>Password *<br><input type="password" name="password" required minlength="6"></label></p>
		<p><label>Data di nascita<br><input type="date" name="nascita"></label></p>
		<p><label>Squadra (facoltativa)<br>
			<select name="squadra">
				<option value="">— Scegli più tardi —</option>
				<?php foreach ( $teams as $t ) { echo '<option value="' . esc_attr( $t ) . '">' . esc_html( $t ) . '</option>'; } ?>
			</select>
		</label></p>
		<p><label><input type="checkbox" name="privacy" value="1" required> Ho letto la <a href="<?php echo esc_url( gs_privacy_policy_url() ); ?>" target="_blank" rel="noopener">Privacy Policy</a> e acconsento al trattamento dei miei dati per l'iscrizione. *</label></p>
		<p><button type="submit" class="gs-btn">Invia richiesta</button></p>
		<div class="gs-form-msg"></div>
	</form>
	<?php
	return ob_get_clean();
}

// -----------------------------------------------------------------------------
// [gs_dashboard] — La Mia Sfoglia (privata)
// -----------------------------------------------------------------------------
add_shortcode( 'gs_dashboard', 'gs_sc_dashboard' );
function gs_sc_dashboard() {
	if ( $g = gs_gate_riservato() ) { return $g; }
	$user_id = get_current_user_id();

	if ( ! gs_is_approved( $user_id ) ) {
		return '<div class="gs-box gs-notice">Il tuo account è <strong>in attesa di approvazione</strong>. Potrai partecipare non appena la segreteria avrà approvato la tua iscrizione.</div>';
	}

	// «La Mia Sfoglia» si chiude anche lei per chi è congelata (Ennio,
	// 26/08/2026: alla lettera «vedono solo le pagine in chiaro»). Le
	// istruzioni per riattivare l'accesso stanno solo nell'email di
	// scadenza, non più qui dentro.
	if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( $user_id ) ) {
		return function_exists( 'gs_congelata_avviso' ) ? gs_congelata_avviso() : '';
	}

	// Blackout: se attivo, le sfogline non vedono la dashboard.
	if ( $g = gs_blackout_gate() ) { return $g; }

	// Riprogettata il 18/08/2026 (richiesta di Ennio: "riprogettami al
	// meglio anche La Mia Sfoglia"): da una colonna di ~16 riquadri in fila
	// a un cruscotto in testa (foto, livello e tutti i numeri insieme,
	// invece che sparsi) più 4 fasce colorate — stessa idea, stessi colori
	// e stesso principio delle sotto-sezioni già usati per l'Aeroplanino e
	// per l'architettura della Plancia. Nessun testo d'aiuto o funzione è
	// stato tolto: sono stati solo raggruppati diversamente.
	$out  = '<div id="gs-mia-sfoglia">';

	// Avviso messaggi non letti: in testa alla pagina, per prima cosa
	// (richiesto da Ennio: deve essere la prima cosa che si vede).
	if ( function_exists( 'gs_messaggi_non_letti' ) ) {
		$nl = gs_messaggi_non_letti( $user_id );
		if ( $nl > 0 ) {
			$pid = (int) get_option( 'gs_page_messaggi' );
			$url = $pid ? get_permalink( $pid ) : '#';
			$out .= '<div class="gs-box gs-notice">✉️ Hai <strong>' . (int) $nl . '</strong> '
				. ( 1 === $nl ? 'messaggio non letto' : 'messaggi non letti' )
				. '. <a href="' . esc_url( $url ) . '">Vai ai messaggi</a></div>';
		}
	}

	$out .= gs_sezione_aiuto( 'Questa è la tua pagina principale. In testa trovi la tua "carta d\'identità": foto, livello e tutti i tuoi numeri insieme (punti, streak, scudi salva-streak, token, badge, sconto sui corsi), con "Prossimo passo" — un piccolo suggerimento su cosa fare, che cambia da solo in base alla tua situazione. Sotto, quattro fasce colorate: "Oggi" (missioni e ingrediente segreto — il motivo per cui apri la pagina ogni giorno), "Il tuo percorso" (livelli, badge, premi), "Le tue sfide" e "I tuoi strumenti" (promemoria, cestino, vetrina, account) — richiudibili una per una. Le pillole in alto ti portano dritta alla fascia che cerchi. Ogni 4 settimane di streak consecutive guadagni uno scudo salva-streak: se una settimana la salti, uno scudo la copre in automatico e la tua serie non si azzera.' );
	if ( function_exists( 'gs_onboarding_box' ) ) { $out .= gs_onboarding_box( $user_id ); }
	if ( function_exists( 'gs_testamento_prompt_html' ) ) { $out .= gs_testamento_prompt_html( $user_id ); }

	// ============================== HERO ==============================
	$out .= gs_mia_sfoglia_hero_html( $user_id );

	// ============================ NAV RAPIDA ===========================
	$out .= '<nav class="gs-msf-nav" aria-label="Sezioni di questa pagina">'
		. '<a href="#gs-fascia-oggi"><span class="gs-msf-nav-dot" style="background:#cd8b0c"></span>Oggi</a>'
		. '<a href="#gs-fascia-percorso"><span class="gs-msf-nav-dot" style="background:#1f6e37"></span>Il mio percorso</a>'
		. '<a href="#gs-fascia-sfide"><span class="gs-msf-nav-dot" style="background:#9c5a2e"></span>Le mie sfide</a>'
		. '<a href="#gs-fascia-strumenti"><span class="gs-msf-nav-dot" style="background:#5c4a34"></span>I miei strumenti</a>'
		. '</nav>';

	// ============================ FASCIA OGGI ==========================
	// Missioni e Ingrediente Segreto fianco a fianco: sono le due cose "di
	// oggi", il motivo per cui si apre la pagina ogni giorno.
	$out .= '<section id="gs-fascia-oggi" class="gs-fascia" style="--fc:#cd8b0c">';
	$out .= '<div class="gs-fascia-griglia">';

	$mission_page = array(
		'vota_3'    => 'gs_page_sfida',
		'diario'    => 'gs_page_diario',
		'consiglio' => 'gs_page_consigli',
		'commento'  => 'gs_page_sfida',
	);
	$out .= gs_box_open( '🎯 Missioni di oggi' );
	$out .= gs_sezione_aiuto( 'Le missioni si aggiornano da sole mentre usi il sito, non c\'è nulla da avviare a parte. Clicca su una missione per andare dritta alla pagina dove si compie davvero (votare, scrivere nel Diario, condividere un consiglio, commentare la sfida). Quando la completi, la riga si segna come fatta e i punti vengono assegnati in automatico.' );
	$out .= '<p class="gs-hint">Si aggiornano da sole mentre usi il sito: clicca su una missione per andare dritta dove si compie.</p>';
	$out .= '<div class="gs-todo-riquadro"><ul class="gs-missions">';
	foreach ( gs_missions_for_display( $user_id ) as $m ) {
		$state = $m['done'] ? 'done' : '';
		$url   = isset( $mission_page[ $m['key'] ] ) && function_exists( 'gs_pagina_url' ) ? gs_pagina_url( $mission_page[ $m['key'] ] ) : '';
		$extra = isset( $m['extra'] ) ? ' <em class="gs-mission-extra">' . esc_html( $m['extra'] ) . '</em>' : '';
		$testo = sprintf( '<span>%s</span> <em>%d/%d</em>%s <b>+%d</b>', esc_html( $m['label'] ), $m['progress'], $m['obiettivo'], $extra, $m['punti'] );
		$out  .= $url
			? sprintf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $state ), esc_url( $url ), $testo )
			: sprintf( '<li class="%s">%s</li>', esc_attr( $state ), $testo );
	}
	$out .= '</ul></div>' . gs_box_close();

	$out .= gs_render_secret_ingredient();

	$out .= '</div></section>';

	// =========================== FASCIA PERCORSO =========================
	// Livello, badge, tabellone, premi e Madrina raccolti insieme (prima
	// erano quattro/cinque riquadri separati lungo la pagina).
	$out .= '<section id="gs-fascia-percorso" class="gs-fascia" style="--fc:#1f6e37">';
	$out .= gs_mia_sfoglia_percorso_html( $user_id );
	if ( function_exists( 'gs_buono_sfoglia_box_html' ) ) { $out .= gs_buono_sfoglia_box_html( $user_id ); }
	if ( function_exists( 'gs_sconto_box_html' ) ) { $out .= gs_sconto_box_html( $user_id ); }
	if ( function_exists( 'gs_render_madrina_box' ) ) { $out .= gs_render_madrina_box( $user_id ); }
	$out .= '</section>';

	// ============================ FASCIA SFIDE ==========================
	$out .= '<section id="gs-fascia-sfide" class="gs-fascia" style="--fc:#9c5a2e">';
	$out .= gs_render_mie_sfide( $user_id );
	$out .= gs_render_cestino( $user_id );

	$streak = gs_get_streak( $user_id );
	$scudi  = function_exists( 'gs_streak_scudi' ) ? gs_streak_scudi( $user_id ) : 0;
	$out   .= gs_box_open( '🔥 Streak del Matterello' );
	$out   .= gs_sezione_aiuto( '"Streak" è un termine inglese (letteralmente "striscia", "serie") usato nei giochi e nelle app per indicare una sequenza di volte consecutive in cui fai una certa cosa, senza interruzioni — più vai avanti senza saltare un turno, più lo streak cresce; se salti una volta, di solito si azzera e riparti da capo. Esempi comuni: un\'app per imparare una lingua può contare quanti giorni di fila fai un esercizio; una squadra sportiva ha uno "streak" quando vince partite una dietro l\'altra senza interruzioni; un\'app per tenersi in contatto con qualcuno può contare i giorni di fila in cui vi scrivete almeno un messaggio. Qui conta le settimane invece dei giorni: ogni settimana (da lunedì a domenica) in cui pubblichi almeno una sfoglia, lo Streak sale di 1. Se invece salti una settimana intera senza pubblicare nulla, lo Streak torna a zero — a meno che tu non abbia uno scudo salva-streak: ne guadagni uno ogni 4 settimane di fila, e copre da solo la settimana saltata, senza far tornare lo Streak a zero.' );
	$out   .= '<p class="gs-streak">' . esc_html( $streak ) . ' settimane consecutive</p>';
	if ( $scudi > 0 ) {
		$out .= '<p class="gs-hint">🛡️ Hai ' . (int) $scudi . ( 1 === $scudi ? ' scudo salva-streak' : ' scudi salva-streak' ) . ' pronto/i a coprire una settimana saltata, senza azzerare la streak.</p>';
	} elseif ( $streak > 0 ) {
		$manca = gs_streak_scudo_ogni_settimane() - ( $streak % gs_streak_scudo_ogni_settimane() );
		$out  .= '<p class="gs-hint">🛡️ Ancora ' . $manca . ( 1 === $manca ? ' settimana' : ' settimane' ) . ' e guadagni uno scudo salva-streak.</p>';
	}
	$out   .= gs_box_close();
	$out .= '</section>';

	// ========================== FASCIA STRUMENTI ========================
	// Le sezioni "di servizio" diventano cassetti richiudibili, stesso
	// stile delle sotto-sezioni già usate per Aeroplanino e Palloncini —
	// prima allungavano la pagina restando sempre tutte aperte.
	$out .= '<section id="gs-fascia-strumenti" class="gs-fascia" style="--fc:#5c4a34">';

	$out .= '<details class="gs-sotto-sezione" open><summary>📝 Le cose da fare</summary><div class="gs-sotto-sezione-corpo">';
	$out .= gs_render_todos( $user_id );
	$out .= '</div></details>';

	// Vetrina pubblica: visibile solo se l'interruttore generale è acceso e non
	// bloccata per questa sfoglina — dentro, due stati diversi a seconda che
	// l'abbia già attivata coi token oppure no.
	if ( gs_vetrina_permessa( $user_id ) ) {
		$out .= '<details class="gs-sotto-sezione"><summary>🔗 La tua Vetrina pubblica</summary><div class="gs-sotto-sezione-corpo">';
		$out .= gs_box_open( '🔗 La tua Vetrina pubblica' );
		if ( gs_vetrina_token_attivata( $user_id ) ) {
			$out .= gs_sezione_aiuto( 'La Vetrina è il tuo profilo pubblico: foto, livello, badge, biografia (se approvata) e le tue sfoglie pubbliche. Il link qui sotto è pronto da copiare e condividere anche con chi non è iscritta all\'Accademia.' );
			$out .= '<p>Condividi il tuo profilo: <a href="' . esc_url( gs_vetrina_url( $user_id ) ) . '">' . esc_html( gs_vetrina_url( $user_id ) ) . '</a></p>';
		} else {
			$costo = function_exists( 'gs_token_costo_vetrina' ) ? gs_token_costo_vetrina() : 5;
			$saldo = function_exists( 'gs_token_saldo' ) ? gs_token_saldo( $user_id ) : 0;
			$out .= gs_sezione_aiuto( 'La Vetrina è il tuo profilo pubblico, condivisibile anche fuori dal sito: foto, livello, badge, biografia e le tue sfoglie. Si attiva una volta sola spendendo dei token dal tuo saldo — lo stesso credito che usi per le consulenze private con i maestri.' );
			$out .= '<p>Attivala spendendo <strong>' . (int) $costo . ' token</strong> (hai ' . (int) $saldo . ' token disponibili) e ottieni un link pubblico da condividere anche fuori dal sito.</p>';
			if ( $saldo >= $costo ) {
				$out .= '<p><button class="gs-btn gs-vetrina-attiva-token">Attiva la mia Vetrina (' . (int) $costo . ' token)</button> <span class="gs-vetrina-attiva-msg gs-richiesta-esito"></span></p>';
			} else {
				$out .= '<p class="gs-hint">Non hai ancora abbastanza token. Scrivi alla segreteria per un contributo associativo e ricevere altro credito (vedi "Il tuo account").</p>';
			}
		}
		$out .= gs_box_close();
		$out .= '</div></details>';
	}

	// Il tuo account: password, email, esportazione dati, richiesta cancellazione.
	if ( function_exists( 'gs_account_box_html' ) ) {
		$out .= '<details class="gs-sotto-sezione"><summary>⚙️ Il tuo account</summary><div class="gs-sotto-sezione-corpo">';
		$out .= gs_account_box_html( $user_id );
		$out .= '</div></details>';
	}

	$out .= '</section>';

	$out .= '</div>';

	return $out;
}

/**
 * Testata-cruscotto di "La Mia Sfoglia": foto, nome, livello, squadra e —
 * tutti insieme, per la prima volta — punti, streak, scudi, token, badge e
 * sconto sui corsi. Prima questi sei numeri vivevano in sei riquadri
 * diversi lungo la pagina. Sotto, la barra di avanzamento e "Prossimo
 * passo". Solo per "La Mia Sfoglia": la Vetrina pubblica continua a usare
 * gs_render_profile(), invariata.
 */
function gs_mia_sfoglia_hero_html( $user_id ) {
	$user  = get_userdata( $user_id );
	$level = gs_get_level( $user_id );
	$team  = gs_get_user_team( $user_id );
	$foto  = function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $user_id ) : '';

	$streak = gs_get_streak( $user_id );
	$scudi  = function_exists( 'gs_streak_scudi' ) ? gs_streak_scudi( $user_id ) : 0;
	$token  = function_exists( 'gs_token_saldo' ) ? gs_token_saldo( $user_id ) : 0;
	$badge_n = count( gs_get_user_badges( $user_id ) );
	$sconto = function_exists( 'gs_sconto_percentuale' ) ? gs_sconto_percentuale( $user_id ) : 0;

	$out = '<div class="gs-msf-hero">';
	$out .= '<div class="gs-msf-hero-testa">';
	if ( $foto ) {
		$out .= '<img class="gs-msf-foto" src="' . esc_url( $foto ) . '" alt="' . esc_attr( $user->display_name ) . '">';
	} else {
		$out .= '<span class="gs-msf-foto-vuota">' . get_avatar( $user_id, 74 ) . '</span>';
	}
	$out .= '<div class="gs-msf-nome"><h2>' . esc_html( $user->display_name ) . '</h2>';
	$out .= '<p class="gs-level">' . esc_html( $level['simbolo'] . ' ' . $level['titolo'] ) . '</p>';
	if ( $team ) { $out .= '<p class="gs-team">🎽 ' . esc_html( $team ) . '</p>'; }
	$out .= '</div></div>';

	$out .= '<div class="gs-msf-numeri">';
	$out .= '<div class="gs-msf-numero"><b>' . (int) $level['punti'] . '</b><span>Punti</span></div>';
	$out .= '<div class="gs-msf-numero"><b>🔥 ' . (int) $streak . '</b><span>Streak settimane</span></div>';
	$out .= '<div class="gs-msf-numero"><b>🛡️ ' . (int) $scudi . '</b><span>Scudi salva-streak</span></div>';
	$out .= '<div class="gs-msf-numero"><b>🎫 ' . (int) $token . '</b><span>Token</span></div>';
	$out .= '<div class="gs-msf-numero"><b>🎖️ ' . (int) $badge_n . '</b><span>Badge</span></div>';
	if ( $sconto > 0 ) {
		$out .= '<div class="gs-msf-numero"><b>💶 ' . esc_html( rtrim( rtrim( number_format( $sconto, 1, ',', '' ), '0' ), ',' ) ) . '%</b><span>Sconto corsi</span></div>';
	}
	$out .= '</div>';

	if ( function_exists( 'gs_tabellone_percorso_html' ) ) {
		$out .= '<div class="gs-msf-tabellone">' . gs_tabellone_percorso_html( $level ) . '</div>';
	}
	$out .= '<p class="gs-progress-label">' . (int) $level['punti'] . ' punti';
	if ( $level['next'] ) {
		$out .= ' — ' . (int) $level['to_next'] . ' al livello ' . esc_html( $level['next']['titolo'] );
	} else {
		$out .= ' — livello massimo raggiunto! 👑';
	}
	$out .= '</p>';

	if ( function_exists( 'gs_prossimo_passo_html' ) ) {
		$out .= gs_prossimo_passo_html( $user_id );
	}

	$out .= '</div>';
	return $out;
}

/**
 * Contenuto della fascia "Il tuo percorso": presentazione, guida "Come
 * funziona", e i badge — stessi pezzi che prima stavano dentro
 * gs_render_profile(), qui riscritti perché quella funzione resta
 * invariata (la usa anche la Vetrina pubblica, che non cambia).
 */
function gs_mia_sfoglia_percorso_html( $user_id ) {
	$out = gs_box_open( '🗺️ Il mio percorso' );

	if ( function_exists( 'gs_percorso_html' ) ) { $out .= gs_percorso_html(); }
	if ( function_exists( 'gs_come_funziona_html' ) ) { $out .= gs_come_funziona_html(); }

	$badges = gs_get_user_badges( $user_id );
	$defs   = gs_get_badges_definitions();
	if ( $badges ) {
		$out .= '<p style="font-weight:700;margin:14px 0 4px">🎖️ I tuoi badge</p>';
		$out .= '<div class="gs-badges">';
		foreach ( $badges as $key ) {
			if ( isset( $defs[ $key ] ) ) {
				$out .= '<details class="gs-badge"><summary>' . $defs[ $key ]['icon'] . ' ' . esc_html( $defs[ $key ]['label'] ) . '</summary>'
					. '<p class="gs-badge-desc">' . esc_html( $defs[ $key ]['desc'] ) . '</p></details>';
			} else {
				$label = get_user_meta( $user_id, 'gs_badge_label_' . $key, true );
				if ( $label ) {
					$out .= '<span class="gs-badge">' . esc_html( $label ) . '</span>';
				}
			}
		}
		$out .= '</div>';
	}

	$out .= gs_box_close();
	return $out;
}

/**
 * Blocco profilo riutilizzabile (usato in dashboard e vetrina).
 */
function gs_render_profile( $user_id, $public = false ) {
	$user  = get_userdata( $user_id );
	$level = gs_get_level( $user_id );
	$team  = gs_get_user_team( $user_id );

	$out  = gs_box_open( '', 'gs-profile' );
	if ( ! $public ) {
		$out .= gs_sezione_aiuto( 'Il tuo profilo: foto, livello raggiunto, squadra, barra di avanzamento verso il livello successivo e i tuoi badge. Sale da solo appena raggiungi la soglia di punti del livello successivo.' );
	}
	$out .= '<div class="gs-todo-riquadro gs-profile-riquadro">';
	$out .= '<div class="gs-profile-head' . ( $public ? ' gs-profile-head-pubblico' : '' ) . '">';
	$foto = function_exists( 'gs_bio_foto_url' ) ? gs_bio_foto_url( $user_id ) : '';
	if ( $foto ) {
		$out .= '<img class="gs-profile-foto" src="' . esc_url( $foto ) . '" alt="' . esc_attr( $user->display_name ) . '">';
	} else {
		$out .= get_avatar( $user_id, $public ? 160 : 64 );
	}
	$out .= '<div><h3>' . esc_html( $user->display_name ) . '</h3>';
	$out .= '<p class="gs-level">' . esc_html( $level['simbolo'] . ' ' . $level['titolo'] ) . '</p>';
	if ( $team ) {
		$out .= '<p class="gs-team">🎽 ' . esc_html( $team ) . '</p>';
	}
	$out .= '</div></div>';
	$out .= '</div>';

	// Prossimo passo suggerito: solo sulla propria pagina, non sulla vetrina pubblica.
	if ( ! $public && function_exists( 'gs_prossimo_passo_html' ) ) {
		$out .= gs_prossimo_passo_html( $user_id );
	}

	// Presentazione del percorso (titolo cliccabile che apre il testo).
	if ( function_exists( 'gs_percorso_html' ) ) {
		$out .= gs_percorso_html();
	}

	// Guida "Come funziona il Percorso": solo in "La Mia Sfoglia", non sulla Vetrina pubblica.
	if ( ! $public && function_exists( 'gs_come_funziona_html' ) ) {
		$out .= gs_come_funziona_html();
	}

	$out .= '<div class="gs-todo-riquadro">';
	if ( function_exists( 'gs_tabellone_percorso_html' ) ) {
		$out .= gs_tabellone_percorso_html( $level );
	}
	$out .= '<p class="gs-progress-label">' . (int) $level['punti'] . ' punti';
	if ( $level['next'] ) {
		$out .= ' — ' . (int) $level['to_next'] . ' al livello ' . esc_html( $level['next']['titolo'] );
	} else {
		$out .= ' — livello massimo raggiunto! 👑';
	}
	$out .= '</p>';

	// Badge: titolo cliccabile che apre la spiegazione, non un tooltip (invisibile al tocco).
	$badges = gs_get_user_badges( $user_id );
	$defs   = gs_get_badges_definitions();
	if ( $badges ) {
		$out .= '<div class="gs-badges">';
		foreach ( $badges as $key ) {
			if ( isset( $defs[ $key ] ) ) {
				$out .= '<details class="gs-badge"><summary>' . $defs[ $key ]['icon'] . ' ' . esc_html( $defs[ $key ]['label'] ) . '</summary>'
					. '<p class="gs-badge-desc">' . esc_html( $defs[ $key ]['desc'] ) . '</p></details>';
			} else {
				$label = get_user_meta( $user_id, 'gs_badge_label_' . $key, true );
				if ( $label ) {
					$out .= '<span class="gs-badge">' . esc_html( $label ) . '</span>';
				}
			}
		}
		$out .= '</div>';
	}
	$out .= '</div>';

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_sfida_corrente]
// -----------------------------------------------------------------------------
/** Attiva la Vetrina pubblica di una sfoglina spendendo i suoi token. */
add_action( 'wp_ajax_gs_vetrina_attiva_token', 'gs_ajax_vetrina_attiva_token' );
function gs_ajax_vetrina_attiva_token() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	$uid = get_current_user_id();
	if ( ! $uid || ! gs_puo_partecipare( $uid ) ) { wp_send_json_error( array( 'message' => 'Il tuo account non può fare questa cosa adesso.' ) ); }
	if ( ! gs_vetrina_permessa( $uid ) ) { wp_send_json_error( array( 'message' => 'La Vetrina non è disponibile al momento.' ) ); }
	if ( gs_vetrina_token_attivata( $uid ) ) { wp_send_json_error( array( 'message' => 'La tua Vetrina è già attiva.' ) ); }

	$costo = function_exists( 'gs_token_costo_vetrina' ) ? gs_token_costo_vetrina() : 5;
	$saldo = function_exists( 'gs_token_saldo' ) ? gs_token_saldo( $uid ) : 0;
	if ( $saldo < $costo ) {
		wp_send_json_error( array( 'message' => 'Non hai abbastanza token: te ne servono ' . (int) $costo . ', ne hai ' . (int) $saldo . '.' ) );
	}

	gs_token_movimento( $uid, -$costo, 'consumo', 'Attivazione Vetrina pubblica' );
	update_user_meta( $uid, 'gs_vetrina_token_attiva', '1' );

	wp_send_json_success( array(
		'message' => 'Vetrina attivata! Il tuo link pubblico è pronto qui sotto.',
		'url'     => gs_vetrina_url( $uid ),
	) );
}

add_shortcode( 'gs_sfida_corrente', 'gs_sc_sfida_corrente' );
function gs_sc_sfida_corrente() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'sfida' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }
	$sfida = gs_get_active_challenge();
	if ( ! $sfida ) {
		return gs_box_open( 'Sfida della Settimana' ) . '<p>Nessuna sfida attiva al momento. Torna presto! 🌾</p>' . gs_box_close();
	}

	$fine = strtotime( get_post_meta( $sfida->ID, 'gs_data_fine', true ) );

	$out  = gs_box_open( '🏆 ' . get_the_title( $sfida ), 'gs-sfida' );
	$out .= gs_sezione_aiuto( 'Qui trovi la sfida della settimana attiva, con il tempo rimanente. Se puoi partecipare, in fondo trovi il modulo per inviare la tua sfoglia (titolo, descrizione, foto). Nella galleria sotto puoi votare le sfoglie delle altre, non la tua, una sola volta per sfoglia.' );
	if ( has_post_thumbnail( $sfida->ID ) ) {
		$out .= get_the_post_thumbnail( $sfida->ID, 'medium' );
	}
	$out .= '<div class="gs-sfida-desc">' . wp_kses_post( apply_filters( 'the_content', $sfida->post_content ) ) . '</div>';
	$out .= '<p class="gs-countdown" data-deadline="' . esc_attr( $fine ) . '">⏳ Calcolo tempo rimanente…</p>';

	// Avviso sfida blindata.
	$is_blindata = function_exists( 'gs_sfida_blindata' ) && gs_sfida_blindata( $sfida->ID );
	$puo_partecipare = is_user_logged_in() && gs_can_participate( get_current_user_id(), $sfida->ID );

	if ( $is_blindata ) {
		$lm = (int) get_post_meta( $sfida->ID, 'gs_livello_min', true );
		$out .= '<p class="gs-notice" style="padding:8px 12px;border-radius:6px;background:var(--gs-uovo)">🔒 Sfida blindata'
			. ( $lm ? ' — riservata dal livello ' . $lm . ' in su' : ' — su ammissione' )
			. '.</p>';
	}

	// Modulo invio (solo per approvate e, se blindata, ammesse).
	if ( is_user_logged_in() && gs_is_approved( get_current_user_id() ) && $puo_partecipare ) {
		$out .= '<form class="gs-form" id="gs-form-sfoglia" data-action="gs_invia_sfoglia" enctype="multipart/form-data">';
		$out .= wp_nonce_field( 'gs_ajax', 'nonce', false, false );
		ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
		$out .= '<input type="hidden" name="sfida_id" value="' . (int) $sfida->ID . '">';
		$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off"></label></p>';
		$out .= '<p><label>Descrizione<br><textarea name="descrizione" rows="3"></textarea></label></p>';
		$out .= '<p><label>Foto della tua sfoglia<br><input type="file" name="foto" accept="image/*"></label></p>';
		$out .= '<p><button type="submit" class="gs-btn">Invia la mia sfoglia</button></p>';
		$out .= '<div class="gs-form-msg"></div>';
		$out .= '</form>';
	} elseif ( is_user_logged_in() && gs_is_approved( get_current_user_id() ) && ! $puo_partecipare ) {
		$out .= '<p>Questa sfida è blindata e al momento non sei tra le persone ammesse a partecipare.</p>';
	} elseif ( ! is_user_logged_in() ) {
		$out .= '<p><a href="' . esc_url( gs_login_url( get_permalink() ) ) . '">Accedi</a> per partecipare.</p>';
	}

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_galleria_sfida] — galleria + voto della sfida attiva
// -----------------------------------------------------------------------------
add_shortcode( 'gs_galleria_sfida', 'gs_sc_galleria_sfida' );
function gs_sc_galleria_sfida() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'sfida' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }
	$sfida = gs_get_active_challenge();
	if ( ! $sfida ) {
		return '';
	}

	$visibilita = get_post_meta( $sfida->ID, 'gs_visibilita', true );
	if ( 'riservata' === $visibilita && ( ! is_user_logged_in() || ! gs_is_approved( get_current_user_id() ) ) ) {
		return gs_box_open( 'Galleria della sfida' ) . '<p>🔒 Galleria riservata alle iscritte approvate.</p>' . gs_box_close();
	}

	$sfoglie = gs_get_sfoglie( $sfida->ID );
	$out = gs_box_open( '🖼️ Sfoglie in gara' );
	$out .= gs_sezione_aiuto( 'Qui trovi tutte le sfoglie inviate per la sfida attiva. Di solito, se sei loggata e la richiesta è già stata approvata, puoi votare quelle delle altre (non la tua) assegnando un punteggio per ogni criterio: la media dei voti ricevuti aggiorna la posizione in classifica. Per alcune sfide il titolare può però scegliere di far giudicare solo i maestri (titolare e collaboratori): in quel caso non trovi il modulo di voto, solo la galleria.' );
	if ( ! $sfoglie ) {
		$out .= '<p>Ancora nessuna sfoglia. Sii la prima!</p>' . gs_box_close();
		return $out;
	}

	$user_id   = get_current_user_id();
	$criteria  = gs_vote_criteria();
	$tipo_voto = function_exists( 'gs_sfida_tipo_voto' ) ? gs_sfida_tipo_voto( $sfida->ID ) : 'sfogline';
	$sono_maestro = function_exists( 'gs_can_manage' ) && gs_can_manage();
	if ( 'maestri' === $tipo_voto ) {
		$out .= '<p class="gs-hint">⚖️ Questa sfida è giudicata dai maestri, non a voto tra sfogline: qui sotto vedi le sfoglie in gara, il punteggio arriva da chi gestisce il portale.</p>';
	}

	$out .= '<div class="gs-gallery">';
	foreach ( $sfoglie as $s ) {
		$gia_votata = $user_id && function_exists( 'gs_utente_ha_votato' ) && gs_utente_ha_votato( $s->ID, $user_id );
		$propria    = $user_id && (int) $s->post_author === $user_id;

		$out .= '<div class="gs-card">';
		if ( has_post_thumbnail( $s->ID ) ) {
			$out .= '<div class="gs-card-head" style="background-image:url(' . esc_url( get_the_post_thumbnail_url( $s->ID, 'medium' ) ) . ')"></div>';
		} else {
			$out .= '<div class="gs-card-head gs-card-head-ph">🍝</div>';
		}
		$out .= '<div class="gs-card-body">';
		$autrice = get_userdata( $s->post_author );
		$out .= '<p class="gs-card-author">di ' . esc_html( $autrice ? $autrice->display_name : '—' ) . '</p>';
		$out .= '<h4>' . esc_html( get_the_title( $s ) ) . '</h4>';
		$out .= '<p class="gs-card-media">⭐ Media: ' . esc_html( gs_calc_media_voti( $s->ID ) ) . '/20</p>';
		$out .= '</div>';

		$puo_votare_tipo = 'sfogline' === $tipo_voto || $sono_maestro;

		$out .= '<div class="gs-card-foot">';
		if ( ! $puo_votare_tipo ) {
			$out .= '<p class="gs-voted" style="color:#8b7d5f">⚖️ Giudicano i maestri</p>';
		} elseif ( is_user_logged_in() && gs_is_approved( $user_id ) && ! $propria && ! $gia_votata ) {
			$out .= '<form class="gs-form gs-vote-form" data-action="gs_vota">';
			$out .= wp_nonce_field( 'gs_ajax', 'nonce', false, false );
			ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
			$out .= '<input type="hidden" name="sfoglia_id" value="' . (int) $s->ID . '">';
			foreach ( $criteria as $key => $label ) {
				$out .= '<label class="gs-vote-row">' . esc_html( $label ) . '<br>';
				$out .= '<select name="voto_' . esc_attr( $key ) . '">';
				for ( $i = 1; $i <= 5; $i++ ) {
					$out .= '<option value="' . $i . '">' . str_repeat( '⭐', $i ) . '</option>';
				}
				$out .= '</select></label>';
			}
			// Commento obbligatorio sul perché di quel voto (Ennio, 13/08/2026):
			// senza un motivo scritto il voto non si può inviare.
			$out .= '<label class="gs-vote-row">Perché hai dato questo voto? (obbligatorio)<br>';
			$out .= '<textarea name="commento_voto" rows="2" maxlength="500" style="width:100%" placeholder="Scrivi il motivo del tuo voto…" required></textarea></label>';
			$out .= '<button type="submit" class="gs-btn gs-btn-sm">Vota</button>';
			$out .= '<div class="gs-form-msg"></div>';
			$out .= '</form>';
		} elseif ( $gia_votata ) {
			$out .= '<p class="gs-voted">✔ Hai già votato</p>';
		} elseif ( $propria ) {
			$out .= '<p class="gs-voted">La tua sfoglia</p>';
		} else {
			$out .= '<p class="gs-voted" style="color:#8b7d5f">Accedi per votare</p>';
		}
		$out .= '</div>';

		// Motivi dei voti ricevuti (un commento per ogni voto, obbligatorio dal
		// 13/08/2026): separati dai commenti liberi qui sotto.
		$voti_commenti = function_exists( 'gs_sfoglia_voti_commenti' ) ? gs_sfoglia_voti_commenti( $s->ID ) : array();
		if ( $voti_commenti ) {
			$out .= '<div class="gs-card-commenti">';
			$out .= '<details><summary>⭐ ' . count( $voti_commenti ) . ( 1 === count( $voti_commenti ) ? ' motivo di voto' : ' motivi di voto' ) . '</summary>';
			$out .= '<ul class="gs-todo-list">';
			foreach ( $voti_commenti as $c ) {
				$out .= '<li class="gs-todo-item"><strong>' . esc_html( $c['nome'] ) . ':</strong> ' . esc_html( $c['testo'] ) . '</li>';
			}
			$out .= '</ul></details></div>';
		}

		// Commenti sulla sfoglia (non il voto): missione "vota_3" li conta come
		// seconda condizione, e sbloccano il badge "Maestra Generosa" a quota 50.
		$commenti = function_exists( 'gs_sfoglia_commenti' ) ? gs_sfoglia_commenti( $s->ID ) : array();
		$out .= '<div class="gs-card-commenti">';
		$out .= '<details><summary>💬 ' . count( $commenti ) . ( 1 === count( $commenti ) ? ' commento' : ' commenti' ) . '</summary>';
		if ( $commenti ) {
			$out .= '<ul class="gs-todo-list">';
			foreach ( $commenti as $c ) {
				$out .= '<li class="gs-todo-item"><strong>' . esc_html( $c['nome'] ) . ':</strong> ' . esc_html( $c['testo'] ) . '</li>';
			}
			$out .= '</ul>';
		} else {
			$out .= '<p class="gs-hint">Nessun commento ancora.</p>';
		}
		if ( is_user_logged_in() && gs_is_approved( $user_id ) ) {
			$out .= '<form class="gs-form" data-action="gs_sfoglia_commento">';
			ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
			$out .= '<input type="hidden" name="sfoglia_id" value="' . (int) $s->ID . '">';
			$out .= '<textarea name="testo" rows="2" maxlength="500" style="width:100%" placeholder="Scrivi un commento…"></textarea>';
			$out .= '<button type="submit" class="gs-btn gs-btn-sm">Commenta</button>';
			$out .= '<div class="gs-form-msg"></div>';
			$out .= '</form>';
		}
		$out .= '</details>';
		$out .= '</div>';

		$out .= '</div>';
	}
	$out .= '</div>' . gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_classifica]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_classifica', 'gs_sc_classifica' );
function gs_sc_classifica() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'classifica' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }

	$silenzio = function_exists( 'gs_silenzio_attivo' ) && gs_silenzio_attivo();
	$righe    = array();
	if ( $silenzio ) {
		gs_silenzio_snapshot_assicura();
		foreach ( gs_silenzio_classifica_snapshot() as $r ) {
			$righe[] = array( 'id' => (int) $r['id'], 'nome' => $r['nome'], 'punti' => (int) $r['punti'] );
		}
	} else {
		foreach ( gs_leaderboard( 50 ) as $user ) {
			$righe[] = array( 'id' => (int) $user->ID, 'nome' => $user->display_name, 'punti' => (int) get_user_meta( $user->ID, 'gs_points', true ) );
		}
	}

	$out = gs_box_open( '🏅 Classifica Generale' );
	// Classifica animata del mese ("Matterello che stende", sistema mensile
	// di gioco — Ennio 19-20/08/2026), sopra la tabella generale che segue
	// invariata: due classifiche diverse, mensile qui sopra e vita natural
	// durante sotto, non vanno confuse tra loro.
	if ( function_exists( 'gs_classifica_mensile_html' ) ) {
		$blocco_mensile = gs_classifica_mensile_html();
		if ( $blocco_mensile ) {
			$out .= $blocco_mensile . '<hr style="margin:26px 0">';
		}
	}
	$out .= gs_sezione_aiuto( 'La tabella è paginata: usa "Vedi tutti" per l\'elenco completo. Clicca sul nome di una sfoglina per aprire la sua Vetrina pubblica. Più sotto trovi la classifica a squadre e, se non ne hai ancora una, il menu per sceglierla. Durante la Sfida del Silenzio questa classifica resta ferma a com\'era all\'inizio del periodo: i punti continuano ad arrivare normalmente, semplicemente non li vedi muoversi qui finché il periodo non finisce.' );
	if ( $silenzio ) {
		$out .= '<p class="gs-hint">🤫 <strong>Sfida del Silenzio in corso:</strong> la classifica è congelata a com\'era all\'inizio del periodo.</p>';
	}
	$out .= '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Pos.</th><th>Sfoglina</th><th>Livello</th><th>Punti</th></tr></thead><tbody>';
	$pos = 1;
	foreach ( $righe as $r ) {
		$level = gs_get_level( $r['id'] );
		if ( $silenzio ) {
			$idx   = gs_level_index( $r['punti'] );
			$liv   = gs_settings()['levels'][ $idx ] ?? null;
			$level = $liv ? array( 'simbolo' => $liv['simbolo'], 'titolo' => $liv['titolo'] ) : $level;
		}
		$link  = esc_url( gs_vetrina_url( $r['id'] ) );
		$out  .= sprintf(
			'<tr><td>%d</td><td><a href="%s">%s</a></td><td>%s</td><td>%d</td></tr>',
			$pos++, $link, esc_html( $r['nome'] ),
			esc_html( $level['simbolo'] . ' ' . $level['titolo'] ),
			$r['punti']
		);
	}
	$out .= '</tbody></table>' . gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_squadre_classifica]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_squadre_classifica', 'gs_sc_squadre_classifica' );
function gs_sc_squadre_classifica() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'classifica' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }
	$teams = gs_team_leaderboard();
	$out = gs_box_open( '🎽 Classifica a Squadre' );
	$out .= gs_sezione_aiuto( 'I punti di ogni squadra sono la somma dei punti di tutte le sue sfogline. La mappa dell\'Italia parte bianca: si colorano solo le regioni della squadra in testa alla classifica, nel suo colore — finché nessuna squadra ha punti resta tutta bianca. Se non fai ancora parte di una squadra, più sotto trovi il modulo per scegliere la tua.' );
	if ( function_exists( 'gs_mappa_squadre_html' ) ) {
		$squadra_vincente = ( $teams && $teams[0]['punti'] > 0 ) ? $teams[0]['squadra'] : '';
		$out .= gs_mappa_squadre_html( $squadra_vincente );
	}
	$out .= '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Pos.</th><th>Squadra</th><th>Membri</th><th>Punti</th></tr></thead><tbody>';
	$pos = 1;
	foreach ( $teams as $t ) {
		$out .= sprintf( '<tr><td>%d</td><td>%s</td><td>%d</td><td>%d</td></tr>', $pos++, esc_html( $t['squadra'] ), $t['membri'], $t['punti'] );
	}
	$out .= '</tbody></table>' . gs_box_close();

	// Scelta squadra per chi non ne ha una.
	if ( is_user_logged_in() && ! gs_get_user_team( get_current_user_id() ) ) {
		$out .= gs_box_open( 'Scegli la tua squadra' );
		$out .= '<form class="gs-form" data-action="gs_scegli_squadra">';
		$out .= wp_nonce_field( 'gs_ajax', 'nonce', false, false );
		ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
		$out .= '<select name="squadra">';
		foreach ( gs_get_teams() as $team ) {
			$out .= '<option value="' . esc_attr( $team ) . '">' . esc_html( $team ) . '</option>';
		}
		$out .= '</select> <button type="submit" class="gs-btn gs-btn-sm">Entra</button>';
		$out .= '<div class="gs-form-msg"></div></form>' . gs_box_close();
	}

	return $out;
}

// -----------------------------------------------------------------------------
// [gs_diario]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_diario', 'gs_sc_diario' );
function gs_sc_diario() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( $g = gs_gate_riservato() ) { return $g; }
	$user_id = get_current_user_id();
	if ( ! gs_is_approved( $user_id ) ) {
		return '<div class="gs-box gs-notice">Account in attesa di approvazione.</div>';
	}

	$out  = '';
	if ( function_exists( 'gs_diario_doppio_senso_html' ) ) { $out .= gs_diario_doppio_senso_html( $user_id ); }

	$out .= gs_box_open( '📖 Nuova voce di diario' );
	$out .= gs_sezione_aiuto( 'Scrivi un titolo (facoltativo) e il testo, poi "Salva nel diario". Le voci che hai già scritto sono più sotto, con titolo cliccabile: aprine una per modificarla o eliminarla. Puoi segnare una voce come "ricetta di famiglia originale" per farla notare ai maestri. Se un anno fa, in questo stesso giorno, avevi già scritto qualcosa, lo trovi in un riquadro qui sopra: è privato, solo tu lo vedi.' );
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-form" data-action="gs_aggiungi_diario">';
	$out .= wp_nonce_field( 'gs_ajax', 'nonce', false, false );
	ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
	$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off"></label></p>';
	$out .= '<p><label>Testo *<br><textarea name="testo" rows="4" required></textarea></label></p>';
	$out .= '<p><label><input type="checkbox" name="ricetta_famiglia" value="1"> Ricetta di famiglia originale 👑</label></p>';
	$out .= '<p><button type="submit" class="gs-btn">Salva nel diario</button></p>';
	$out .= '<div class="gs-form-msg"></div></form>';
	$out .= '</div>';
	$out .= gs_box_close();

	// Voci esistenti: titolo cliccabile, testo modificabile.
	$voci = gs_get_diario( $user_id );
	$out .= gs_box_open( 'Le tue voci' );
	if ( $voci ) {
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="5">';
		foreach ( $voci as $v ) {
			$out .= '<details class="gs-inbox-item" data-id="' . (int) $v->ID . '">';
			$out .= '<summary class="gs-inbox-oggetto">' . esc_html( get_the_title( $v ) )
				. ' <span class="gs-msg-data">' . esc_html( get_the_date( '', $v ) ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo">';
			$out .= '<form class="gs-form gs-form-voce" data-id="' . (int) $v->ID . '" data-tipo="diario" onsubmit="return false">';
			$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( get_the_title( $v ) ) . '" style="width:100%"></label></p>';
			$out .= '<p><label>Testo<br><textarea name="testo" rows="5" style="width:100%">' . esc_textarea( $v->post_content ) . '</textarea></label></p>';
			$out .= '<p><button class="gs-btn gs-btn-sm gs-voce-salva">Salva modifiche</button> ';
			$out .= '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-voce-elimina">Elimina</button> ';
			$out .= '<span class="gs-voce-msg gs-richiesta-esito"></span></p></form>';
			$out .= '</div></details>';
		}
		$out .= '</div>';
		$out .= '</div>';
	} else {
		$out .= '<p>Il tuo diario è ancora vuoto.</p>';
	}
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_consigli]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_consigli', 'gs_sc_consigli' );
function gs_sc_consigli() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	$out = '';

	if ( is_user_logged_in() && gs_is_approved( get_current_user_id() ) ) {
		$out .= gs_box_open( '💡 Condividi un consiglio' );
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<form class="gs-form" data-action="gs_aggiungi_consiglio">';
		$out .= wp_nonce_field( 'gs_ajax', 'nonce', false, false );
		ob_start(); gs_antispam_fields(); $out .= ob_get_clean();
		$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off"></label></p>';
		$out .= '<p><label>Il tuo consiglio *<br><textarea name="testo" rows="4" required></textarea></label></p>';
		$out .= '<p><label><input type="checkbox" name="ricetta_famiglia" value="1"> Ricetta di famiglia originale 👑</label></p>';
		$out .= '<p><button type="submit" class="gs-btn">Pubblica</button></p>';
		$out .= '<div class="gs-form-msg"></div></form>';
		$out .= '</div>';
		$out .= gs_box_close();
	}

	$consigli = gs_get_consigli( 30 );
	$out .= gs_box_open( 'Consigli della Community' );
	$out .= gs_sezione_aiuto( 'Se il tuo account è approvato puoi condividere un consiglio dal modulo qui sopra. I consigli di tutta la community sono qui sotto: titolo cliccabile per aprirli, e sono ricercabili.' );
	if ( $consigli ) {
		$me = get_current_user_id();
		$out .= '<div class="gs-todo-riquadro">';
		$out .= '<div class="gs-inbox-lista gs-paginate" data-per-page="8">';
		foreach ( $consigli as $c ) {
			$autrice = get_userdata( $c->post_author );
			$mio     = ( $me && (int) $c->post_author === (int) $me );
			$out .= '<details class="gs-inbox-item" data-id="' . (int) $c->ID . '">';
			$out .= '<summary class="gs-inbox-oggetto">' . esc_html( get_the_title( $c ) )
				. ' <span class="gs-msg-data">di ' . esc_html( $autrice ? $autrice->display_name : '—' ) . '</span></summary>';
			$out .= '<div class="gs-inbox-corpo">';
			if ( $mio || gs_can_manage() ) {
				// L'autrice (o il gestore) può modificare il proprio consiglio.
				$out .= '<form class="gs-form gs-form-voce" data-id="' . (int) $c->ID . '" data-tipo="consiglio" onsubmit="return false">';
				$out .= '<p><label>Titolo<br><input type="text" name="titolo" autocomplete="off" value="' . esc_attr( get_the_title( $c ) ) . '" style="width:100%"></label></p>';
				$out .= '<p><label>Testo<br><textarea name="testo" rows="4" style="width:100%">' . esc_textarea( $c->post_content ) . '</textarea></label></p>';
				$out .= '<p><button class="gs-btn gs-btn-sm gs-voce-salva">Salva modifiche</button> ';
				$out .= '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-voce-elimina">Elimina</button> ';
				$out .= '<span class="gs-voce-msg gs-richiesta-esito"></span></p></form>';
			} else {
				$out .= '<div class="gs-inbox-testo">' . nl2br( esc_html( $c->post_content ) ) . '</div>';
			}
			$out .= '</div></details>';
		}
		$out .= '</div>';
		$out .= '</div>';
	} else {
		$out .= '<p>Ancora nessun consiglio. Condividi il primo!</p>';
	}
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_badge_lista]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_badge_lista', 'gs_sc_badge_lista' );
function gs_sc_badge_lista() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'badge' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }
	$defs  = gs_get_badges_definitions();
	$uid   = is_user_logged_in() ? get_current_user_id() : 0;
	$owned = $uid ? gs_get_user_badges( $uid ) : array();
	$log   = $uid ? get_user_meta( $uid, 'gs_badges_log', true ) : array();
	$log   = is_array( $log ) ? $log : array();

	// Restyling "gioiello" (Ennio, 21-22/08/2026): stessi 9 badge, stesse
	// chiavi, stesso ordine — solo l'aspetto cambia, da icona piatta a
	// medaglione con corona dorata e pietra colorata, sulla falsariga del
	// file di riferimento distintivi-9-nuovi-corona.html (colori/gradienti
	// riportati identici in gs_get_badges_definitions()).
	$out = gs_box_open( '🎖️ Badge e Traguardi' );
	$out .= gs_sezione_aiuto( 'Pagina di sola consultazione: i medaglioni già sbloccati sono a colori pieni, quelli ancora da sbloccare sono in grigio. Ogni scheda spiega cosa serve fare per sbloccarlo.' );
	$out .= '<div class="gs-medaglioni-griglia">';
	$i = 0;
	foreach ( $defs as $key => $b ) {
		$got      = in_array( $key, $owned, true );
		$gradiente = ! empty( $b['gradiente'] ) ? $b['gradiente'] : 'radial-gradient(circle at 34% 24%,#8A6A1E 0%,#5c4a10 46%,#3a2f0a 100%)';
		$pietra    = ! empty( $b['pietra'] ) ? $b['pietra'] : 'oro';
		$anno      = isset( $log[ $key ] ) ? date_i18n( 'Y', $log[ $key ] ) : date_i18n( 'Y' );

		$out .= '<div class="gs-medaglione-blocco ' . ( $got ? 'unlocked' : 'locked' ) . '">';
		$out .= '<div class="gs-medaglione" title="' . esc_attr( $b['desc'] ) . '">';
		$out .= '<div class="gs-med-scanalatura"></div>';
		$out .= '<div class="gs-med-anello-ext"></div>';
		$out .= '<div class="gs-med-anello-int"></div>';
		$out .= '<div class="gs-med-pietra" style="background:' . esc_attr( $gradiente ) . '">';
		$out .= '<div class="gs-med-riflesso"></div><div class="gs-med-bordo-int"></div>';
		$out .= '<div class="gs-med-testo">';
		$out .= '<div class="gs-med-rombo"></div>';
		$out .= '<div class="gs-med-nome">' . esc_html( mb_strtoupper( $b['label'] ) ) . '</div>';
		$out .= '<div class="gs-med-filo"></div>';
		$out .= '<div class="gs-med-romano">' . esc_html( gs_badge_romano( $i ) ) . '</div>';
		$out .= '<div class="gs-med-anno">' . esc_html( $anno ) . '</div>';
		$out .= '</div></div></div>';
		$out .= '<div class="gs-med-didascalia">';
		$out .= '<div class="gs-med-traguardo">TRAGUARDO ' . ( $i + 1 ) . '</div>';
		$out .= '<div class="gs-med-nomepietra">' . esc_html( $pietra ) . '</div>';
		$out .= '<p>' . esc_html( $b['desc'] ) . '</p>';
		$out .= '<span class="gs-badge-status">' . ( $got ? '✔ Sbloccato' : '🔒 Da sbloccare' ) . '</span>';
		$out .= '</div>';
		$out .= '</div>';
		$i++;
	}
	$out .= '</div>' . gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_barometro]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_barometro', 'gs_sc_barometro' );
function gs_sc_barometro() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( $g = gs_gate_riservato() ) { return $g; }
	$data = gs_get_accessible_guides( get_current_user_id() );

	$out = gs_box_open( '🌡️ Guida Stagionale' );
	$out .= gs_sezione_aiuto( 'Pagina di sola consultazione: le guide a cui hai già accesso sono aperte, quelle bloccate mostrano un lucchetto con la condizione per sbloccarle. Si sblocca partecipando a una sfida nel trimestre giusto, non c\'è nulla da cliccare.' );
	if ( $data['accessible'] || $data['locked'] ) {
		$out .= '<div class="gs-todo-riquadro">';
		foreach ( $data['accessible'] as $g ) {
			$out .= '<div class="gs-guide"><h4>' . esc_html( get_the_title( $g ) ) . '</h4>';
			$out .= '<div>' . wp_kses_post( apply_filters( 'the_content', $g->post_content ) ) . '</div></div>';
		}
		foreach ( $data['locked'] as $l ) {
			$out .= '<div class="gs-guide locked"><h4>🔒 ' . esc_html( get_the_title( $l['post'] ) ) . '</h4>';
			$out .= '<p>Partecipa a una sfida nel trimestre ' . esc_html( $l['trimestre'] ) . ' per sbloccare questa guida.</p></div>';
		}
		$out .= '</div>';
	} else {
		$out .= '<p>Nessuna guida disponibile al momento.</p>';
	}
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_galleria_pubblica] — filtrabile per stagione/ingrediente/regione
// -----------------------------------------------------------------------------
add_shortcode( 'gs_galleria_pubblica', 'gs_sc_galleria_pubblica' );
function gs_sc_galleria_pubblica() {
	if ( $g = gs_blackout_gate() ) { return $g; }
	if ( function_exists( 'gs_sez_collab_pagina_ok' ) && ! gs_sez_collab_pagina_ok( 'galleria' ) ) { return '<div class="gs-box"><p>Questa sezione non è disponibile al momento.</p></div>'; }
	$f_stagione   = isset( $_GET['gs_stagione'] ) ? sanitize_text_field( wp_unslash( $_GET['gs_stagione'] ) ) : '';
	$f_ingrediente= isset( $_GET['gs_ingrediente'] ) ? (int) $_GET['gs_ingrediente'] : 0;
	$f_regione    = isset( $_GET['gs_regione'] ) ? sanitize_text_field( wp_unslash( $_GET['gs_regione'] ) ) : '';

	// Raccoglie tutte le sfoglie di sfide con visibilità pubblica.
	$sfide_pubbliche = get_posts( array(
		'post_type'      => 'gs_sfida',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	$sfide_ids = array();
	foreach ( $sfide_pubbliche as $sid ) {
		if ( 'riservata' !== get_post_meta( $sid, 'gs_visibilita', true ) ) {
			$sfide_ids[] = $sid;
		}
	}

	$out = gs_box_open( '🖼️ Galleria delle Sfogline' );
	$out .= gs_sezione_aiuto( 'Usa i tre menu a tendina (stagione, ingrediente, squadra) e premi "Filtra" per restringere i risultati. Clicca sul nome dell\'autrice sotto ogni foto per aprire la sua Vetrina pubblica.' );

	// Filtri.
	$out .= '<div class="gs-todo-riquadro">';
	$out .= '<form class="gs-filters" method="get">';
	$out .= '<select name="gs_stagione"><option value="">Tutte le stagioni</option>';
	foreach ( array( 'primavera', 'estate', 'autunno', 'inverno' ) as $st ) {
		$out .= '<option value="' . $st . '" ' . selected( $f_stagione, $st, false ) . '>' . ucfirst( $st ) . '</option>';
	}
	$out .= '</select> ';

	$out .= '<select name="gs_ingrediente"><option value="0">Tutti gli ingredienti</option>';
	foreach ( get_posts( array( 'post_type' => 'gs_ingrediente', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $ing ) {
		$out .= '<option value="' . (int) $ing->ID . '" ' . selected( $f_ingrediente, $ing->ID, false ) . '>' . esc_html( get_the_title( $ing ) ) . '</option>';
	}
	$out .= '</select> ';

	$out .= '<select name="gs_regione"><option value="">Tutte le squadre</option>';
	foreach ( gs_get_teams() as $team ) {
		$out .= '<option value="' . esc_attr( $team ) . '" ' . selected( $f_regione, $team, false ) . '>' . esc_html( $team ) . '</option>';
	}
	$out .= '</select> <button type="submit" class="gs-btn gs-btn-sm">Filtra</button></form>';
	$out .= '</div>';

	if ( empty( $sfide_ids ) ) {
		$out .= '<p>Nessuna galleria pubblica disponibile.</p>' . gs_box_close();
		return $out;
	}

	$args = array(
		'post_type'      => 'gs_sfoglia',
		'post_status'    => 'publish',
		'posts_per_page' => 60,
		'meta_query'     => array(
			array( 'key' => 'gs_sfida_id', 'value' => $sfide_ids, 'compare' => 'IN' ),
		),
	);
	$sfoglie = get_posts( $args );

	// Filtri applicati lato PHP (stagione via tipo sfida, ingrediente via sfida, regione via autrice).
	$out .= '<div class="gs-gallery">';
	$count = 0;
	foreach ( $sfoglie as $s ) {
		$sfida_id = (int) get_post_meta( $s->ID, 'gs_sfida_id', true );

		if ( $f_regione ) {
			$team = gs_get_user_team( $s->post_author );
			if ( $team !== $f_regione ) {
				continue;
			}
		}
		if ( $f_stagione ) {
			$tipo = get_post_meta( $sfida_id, 'gs_tipo', true );
			$stag = get_post_meta( $sfida_id, 'gs_stagione', true );
			if ( $stag && $stag !== $f_stagione ) {
				continue;
			}
		}
		if ( $f_ingrediente ) {
			$ing_id = (int) get_post_meta( $sfida_id, 'gs_ingrediente_id', true );
			if ( $ing_id !== $f_ingrediente ) {
				continue;
			}
		}

		$autrice = get_userdata( $s->post_author );
		$out .= '<div class="gs-card">';
		if ( has_post_thumbnail( $s->ID ) ) {
			$out .= '<div class="gs-card-head" style="background-image:url(' . esc_url( get_the_post_thumbnail_url( $s->ID, 'medium' ) ) . ')"></div>';
		} else {
			$out .= '<div class="gs-card-head gs-card-head-ph">🍝</div>';
		}
		$out .= '<div class="gs-card-body">';
		$out .= '<p class="gs-card-author"><a href="' . esc_url( gs_vetrina_url( $s->post_author ) ) . '">' . esc_html( $autrice ? $autrice->display_name : '—' ) . '</a></p>';
		$out .= '<h4>' . esc_html( get_the_title( $s ) ) . '</h4>';
		$out .= '</div>';
		$out .= '</div>';
		$count++;
	}
	$out .= '</div>';
	if ( ! $count ) {
		$out .= '<p>Nessuna sfoglia corrisponde ai filtri.</p>';
	}
	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// [gs_vetrina] — profilo pubblico di sola lettura
// -----------------------------------------------------------------------------
add_shortcode( 'gs_vetrina', 'gs_sc_vetrina' );
function gs_sc_vetrina() {
	if ( ! gs_vetrina_attiva() ) {
		return '<div class="gs-box gs-notice">La Vetrina pubblica è momentaneamente disattivata.</div>';
	}

	$login = get_query_var( 'sfoglina' );
	if ( ! $login && isset( $_GET['sfoglina'] ) ) {
		$login = sanitize_user( wp_unslash( $_GET['sfoglina'] ) );
	}
	if ( ! $login ) {
		return '<div class="gs-box gs-notice">Nessuna sfoglina indicata. Aggiungi ?sfoglina=nomeutente all\'indirizzo.</div>';
	}

	// 'slug' (user_nicename), non 'login': l'indirizzo pubblico della Vetrina
	// non porta più lo username (ISTRUZIONE-IL-RESET.md, 26/08/2026).
	$user = get_user_by( 'slug', $login );
	if ( ! $user ) {
		return '<div class="gs-box gs-notice">Sfoglina non trovata.</div>';
	}

	if ( gs_vetrina_bloccata( $user->ID ) ) {
		return '<div class="gs-box gs-notice">La vetrina di questa sfoglina non è al momento disponibile.</div>';
	}
	if ( ! gs_vetrina_token_attivata( $user->ID ) ) {
		return '<div class="gs-box gs-notice">Questa sfoglina non ha ancora attivato la propria Vetrina pubblica.</div>';
	}

	$level  = gs_get_level( $user->ID );
	$streak = gs_get_streak( $user->ID );
	$team   = gs_get_user_team( $user->ID );
	$num    = (int) get_user_meta( $user->ID, 'gs_num_sfoglie', true );

	$out  = gs_sezione_aiuto( 'Questa è la pagina pubblica che puoi condividere con chi non è iscritto all\'Accademia: il link è pronto per essere copiato dalla tua pagina "La Mia Sfoglia". È di sola consultazione.' );
	$out .= gs_render_profile( $user->ID, true );
	if ( function_exists( 'gs_bio_pubblica_html' ) ) { $out .= gs_bio_pubblica_html( $user->ID ); }
	$out .= gs_box_open( 'In sintesi' );
	$out .= '<div class="gs-todo-riquadro"><ul class="gs-vetrina-stats">';
	$out .= '<li>' . $level['simbolo'] . ' Livello: <strong>' . esc_html( $level['titolo'] ) . '</strong></li>';
	$out .= '<li>💯 Punti: <strong>' . (int) $level['punti'] . '</strong></li>';
	$out .= '<li>🔥 Streak: <strong>' . $streak . ' settimane</strong></li>';
	if ( $team ) {
		$out .= '<li>🎽 Squadra: <strong>' . esc_html( $team ) . '</strong></li>';
	}
	$out .= '<li>🌾 Contenuti condivisi: <strong>' . $num . '</strong></li>';
	$out .= '</ul></div>' . gs_box_close();
	if ( function_exists( 'gs_testamento_vetrina_html' ) ) { $out .= gs_testamento_vetrina_html( $user->ID ); }
	return $out;
}

// -----------------------------------------------------------------------------
// Shortcode secondari: [gs_profilo] e [gs_streak]
// -----------------------------------------------------------------------------
add_shortcode( 'gs_profilo', function () {
	if ( $g = gs_gate_riservato() ) { return $g; }
	return gs_render_profile( get_current_user_id(), false );
} );

add_shortcode( 'gs_streak', function () {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$streak = gs_get_streak( get_current_user_id() );
	return '<span class="gs-streak-inline">🔥 ' . (int) $streak . ' settimane</span>';
} );

/**
 * Box dell'Ingrediente Segreto (usato nella dashboard).
 */
function gs_render_secret_ingredient() {
	$revealed = gs_get_revealed_ingredient();
	$next_ts  = gs_next_reveal_timestamp();

	$out = gs_box_open( '🥄 Ingrediente Segreto' );
	$out .= gs_sezione_aiuto( 'Ogni venerdì un ingrediente a sorpresa da usare in una ricetta. Finché non è svelato vedi solo il conto alla rovescia; puoi anche attivare "Avvisami quando si svela" per ricevere una notifica appena viene pubblicato.' );
	if ( $revealed ) {
		$out .= '<p>Ingrediente della settimana: <strong>' . esc_html( get_the_title( $revealed ) ) . '</strong></p>';
		$out .= '<div>' . wp_kses_post( wpautop( $revealed->post_content ) ) . '</div>';
	}
	if ( $next_ts ) {
		$out .= '<p class="gs-countdown" data-deadline="' . esc_attr( $next_ts ) . '">⏳ Prossimo svelamento…</p>';
		$out .= '<button class="gs-btn gs-btn-sm gs-push-optin">🔔 Avvisami quando si svela</button>';
	} elseif ( ! $revealed ) {
		$out .= '<p>Il prossimo ingrediente sarà svelato venerdì alle 18:00.</p>';
	}
	$out .= gs_box_close();
	return $out;
}
