<?php
/**
 * control-panel.php — Pannello di controllo sul FRONT-END per chi gestisce il
 * portale (organizzatrici / amministrazione). Permette di approvare le
 * iscrizioni, vedere le statistiche, la sfida attiva e la classifica, ed
 * esportare i PDF senza entrare in wp-admin.
 *
 * Shortcode: [gs_pannello]
 * Visibile solo a chi ha il permesso "manage_options".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'gs_pannello', 'gs_sc_pannello' );

/**
 * True se l'utente corrente può gestire il portale.
 * Vale per gli amministratori (manage_options) e per i collaboratori a cui è
 * stato dato il permesso dedicato gs_manage_gaming. Filtrabile.
 */
function gs_can_manage() {
	$can = current_user_can( 'manage_options' ) || current_user_can( 'gs_manage_gaming' );
	return apply_filters( 'gs_can_manage', $can );
}

/**
 * Shortcode [gs_pannello].
 */
function gs_sc_pannello() {
	if ( $g = gs_gate_riservato() ) { return $g; }

	// Chi non gestisce il portale viene rimandato alla propria dashboard.
	if ( ! gs_can_manage() ) {
		$dash_id  = (int) get_option( 'gs_page_dashboard' );
		$dash_url = $dash_id ? get_permalink( $dash_id ) : home_url();
		return gs_box_open( 'Area riservata', 'gs-notice' )
			. '<p>Questo è il pannello di controllo del portale, riservato a chi lo gestisce. '
			. 'La tua area personale è <a href="' . esc_url( $dash_url ) . '">La Mia Sfoglia</a>.</p>'
			. gs_box_close();
	}

	ob_start();

	$n_sfogline = gs_conta_sfogline_pubbliche();
	$pending    = gs_get_pending_users();
	$n_pending  = count( $pending );
	$n_sfide    = (int) wp_count_posts( 'gs_sfida' )->publish;
	$n_sfoglie  = (int) wp_count_posts( 'gs_sfoglia' )->publish;
	$n_aiuto    = function_exists( 'gs_aiuto_count_nuovi' ) ? gs_aiuto_count_nuovi() : 0;
	$attiva     = gs_get_active_challenge();
	?>
	<div class="gs-pannello">

		<h2 class="gs-box-title">🎛️ Pannello di Controllo</h2>
		<p class="gs-versione">Gaming Sfogline — versione <strong><?php echo esc_html( GS_VERSION ); ?></strong></p>

		<!-- Cerca sfoglina e recupero file dal cestino -->
		<div data-idx-group="sfogline">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'cerca_sfoglina' ) ) { gs_pannello_cerca_sfoglina(); } ?></div>

		<!-- Regia degli Iscritti ai Corsi (in alto, prima del Cruscotto della Verità — richiesto da Ennio il 18/08/2026) -->
		<div data-idx-group="corsi">		<?php if ( function_exists( 'gs_pannello_regia_iscritti' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'regia_iscritti' ) ) ) { gs_pannello_regia_iscritti(); } ?></div>

		<!-- Bacheca di riepilogo: i numeri del giorno -->
		<div data-idx-group="strumenti">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'riepilogo_bacheca' ) ) { gs_pannello_riepilogo(); } ?></div>

		<!-- Il Cruscotto della Verità -->
		<div data-idx-group="strumenti">		<?php if ( function_exists( 'gs_pannello_cruscotto' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'cruscotto' ) ) ) { gs_pannello_cruscotto(); } ?></div>

		<!-- Pianificazione dell'Anno (subito sotto gli avvisi in alto, prima della creazione dei corsi) -->
		<div data-idx-group="corsi">		<?php if ( function_exists( 'gs_pannello_pianificazione_anno' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'piano_anno' ) ) ) { gs_pannello_pianificazione_anno(); } ?></div>

		<!-- Calendario Corsi (segue subito la Pianificazione dell'Anno) -->
		<div data-idx-group="corsi">		<?php if ( function_exists( 'gs_pannello_calendario' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'calendario' ) ) ) { gs_pannello_calendario(); } ?></div>

		<!-- Richieste di iscrizione -->
		<div data-idx-group="sfogline"><?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'richieste_iscrizione' ) ) : ?>
		<?php echo gs_box_open( 'Richieste di Iscrizione', '', 'gs-box-iscrizioni' ); ?>
			<p class="gs-hint">Approva ogni richiesta <strong>solo dopo aver verificato il pagamento della quota</strong>. Ogni riga è rossa finché resta in attesa; quando approvi diventa verde.</p>
			<?php gs_pannello_richieste_inner( $pending ); ?>
		<?php echo gs_box_close(); ?>
		<?php if ( function_exists( 'gs_pannello_invio_mail_area_riservata' ) ) { gs_pannello_invio_mail_area_riservata(); } ?>
		<?php endif; ?></div>

		<!-- Messaggi privati alle sfogline -->
		<div data-idx-group="messaggi">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'messaggi_privati' ) ) { gs_pannello_messaggi(); } ?></div>

		<!-- In diretta: Aeroplanino + Palloncini + Palloncino Gigante riuniti a linguette -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_in_diretta' ) ) { gs_pannello_in_diretta(); } ?></div>

		<!-- Visibilità sezioni e permessi collaboratori (solo titolare) -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_sezioni' ) ) { gs_pannello_sezioni(); } ?></div>

		<!-- Pagine pubbliche nel menu -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_menu_pagine' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'menu_pagine' ) ) ) { gs_pannello_menu_pagine(); } ?></div>

		<!-- Applica la struttura del menu -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_menu_struttura' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'menu_struttura' ) ) ) { gs_pannello_menu_struttura(); } ?></div>

		<!-- Indice laterale: allineamento -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_indice' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'indice_calibrazione' ) ) ) { gs_pannello_indice(); } ?></div>

		<!-- Messaggio di benvenuto -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_percorso' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'messaggio_benvenuto' ) ) ) { gs_pannello_percorso(); } ?></div>

		<!-- Come funziona il Percorso -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_come_funziona' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'come_funziona' ) ) ) { gs_pannello_come_funziona(); } ?></div>

		<!-- Abbonamenti -->
		<div data-idx-group="pagamenti">		<?php if ( function_exists( 'gs_pannello_abbonamenti' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'abbonamenti' ) ) ) { gs_pannello_abbonamenti(); } ?></div>

		<!-- Token per le consulenze private -->
		<div data-idx-group="pagamenti">		<?php if ( function_exists( 'gs_pannello_token' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'token' ) ) ) { gs_pannello_token(); } ?></div>

		<!-- Statistiche -->
		<div class="gs-stat-grid">
			<a class="gs-stat-card" href="#gs-box-cerca-sfoglina" data-target="gs-box-cerca-sfoglina"><span class="gs-stat-num"><?php echo (int) $n_sfogline; ?></span><span class="gs-stat-lbl">Sfogline registrate</span></a>
			<a class="gs-stat-card <?php echo $n_pending ? 'gs-stat-alert' : ''; ?>" href="#gs-box-iscrizioni" data-target="gs-box-iscrizioni"><span class="gs-stat-num"><?php echo (int) $n_pending; ?></span><span class="gs-stat-lbl">Richieste in attesa</span></a>
			<div class="gs-stat-card"><span class="gs-stat-num"><?php echo (int) $n_sfide; ?></span><span class="gs-stat-lbl">Sfide pubblicate</span></div>
			<div class="gs-stat-card"><span class="gs-stat-num"><?php echo (int) $n_sfoglie; ?></span><span class="gs-stat-lbl">Sfoglie inviate</span></div>
			<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'aiuto' ) ) : ?>
			<a class="gs-stat-card <?php echo $n_aiuto ? 'gs-stat-alert' : ''; ?>" href="#gs-box-aiuto" data-target="gs-box-aiuto"><span class="gs-stat-num"><?php echo (int) $n_aiuto; ?></span><span class="gs-stat-lbl">Aiuto e suggerimenti da leggere</span></a>
			<?php endif; ?>
		</div>

		<!-- Sfida attiva -->
		<div data-idx-group="sfide"><?php echo gs_box_open( 'Sfida attiva' ); ?>
			<?php if ( $attiva ) :
				$fine = strtotime( get_post_meta( $attiva->ID, 'gs_data_fine', true ) ); ?>
				<p><strong class="gs-sfida-attiva-badge gs-lampeggia-verde"><?php echo esc_html( get_the_title( $attiva ) ); ?></strong></p>
				<p class="gs-hint">Scadenza: <?php echo esc_html( date_i18n( 'j/m/Y H:i', $fine ) ); ?></p>
				<p><a class="gs-btn gs-btn-sm" href="<?php echo esc_url( get_permalink( $attiva ) ); ?>">Vedi la sfida</a></p>
			<?php else : ?>
				<p>Nessuna sfida attiva in questo momento.</p>
			<?php endif; ?>
			<?php
			$gs_prox_ingr = function_exists( 'gs_get_next_ingredient' ) ? gs_get_next_ingredient() : null;
			$gs_riv_ingr  = function_exists( 'gs_get_revealed_ingredient' ) ? gs_get_revealed_ingredient() : null;
			?>
			<?php if ( $gs_prox_ingr ) : ?>
				<p><strong>Ingrediente Segreto in programma:</strong> <?php echo esc_html( get_the_title( $gs_prox_ingr ) ); ?><br>
				<span class="gs-hint">si rivela il <?php echo esc_html( date_i18n( 'j/m/Y H:i', get_post_time( 'U', true, $gs_prox_ingr ) ) ); ?></span></p>
			<?php elseif ( $gs_riv_ingr ) : ?>
				<p><strong>Ultimo Ingrediente Segreto rivelato:</strong> <?php echo esc_html( get_the_title( $gs_riv_ingr ) ); ?> — nessuno in programma dopo questo.</p>
			<?php else : ?>
				<p class="gs-hint">Nessun Ingrediente Segreto ancora creato.</p>
			<?php endif; ?>
			<p>
				<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gs_sfida' ) ); ?>">+ Nuova sfida</a>
				<a class="gs-btn gs-btn-sm" href="#gs-box-ingrediente-segreto" data-target="gs-box-ingrediente-segreto">+ Ingrediente segreto</a>
				<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=gs_barometro' ) ); ?>">+ Guida stagionale</a>
			</p>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gs_ingrediente' ) ); ?>">Gestisci tutti gli Ingredienti Segreti</a></p>
		<?php echo gs_box_close(); ?></div>

		<!-- Ingrediente Segreto: pannello dedicato per crearlo senza l'editor di WordPress -->
		<div data-idx-group="sfide"><?php if ( function_exists( 'gs_pannello_ingrediente_segreto' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'ingrediente_segreto' ) ) ) { gs_pannello_ingrediente_segreto(); } ?></div>

		<!-- Sfide blindate (subito sotto "Sfida attiva") -->
		<div data-idx-group="sfide">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sfide_blindate' ) ) { gs_pannello_sfide_blindate(); } ?></div>

		<!-- Classifica + export -->
		<div data-idx-group="sfide"><?php echo gs_box_open( 'Classifica generale (top 10)' ); ?>
			<table class="gs-table gs-paginate" data-per-page="10">
				<thead><tr><th>Pos.</th><th>Sfoglina</th><th>Livello</th><th>Punti</th></tr></thead>
				<tbody>
				<?php
				$pos      = 1;
				$rank_cls = array( 1 => 'gs-rank-oro', 2 => 'gs-rank-argento', 3 => 'gs-rank-bronzo' );
				$rank_ico = array( 1 => '🥇 ', 2 => '🥈 ', 3 => '🥉 ' );
				foreach ( gs_leaderboard( 10 ) as $user ) :
					$level = gs_get_level( $user->ID ); ?>
					<tr class="<?php echo esc_attr( $rank_cls[ $pos ] ?? '' ); ?>">
						<td><?php echo esc_html( $rank_ico[ $pos ] ?? '' ) . $pos++; ?></td>
						<td><?php echo esc_html( $user->display_name ); ?></td>
						<td><?php echo esc_html( $level['simbolo'] . ' ' . $level['titolo'] ); ?></td>
						<td><?php echo (int) get_user_meta( $user->ID, 'gs_points', true ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top:12px">
				<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( gs_export_url( 'generale' ) ); ?>" target="_blank">📄 Esporta classifica generale</a>
				<a class="gs-btn gs-btn-sm" href="<?php echo esc_url( gs_export_url( 'squadre' ) ); ?>" target="_blank">📄 Esporta classifica a squadre</a>
			</p>
		<?php echo gs_box_close(); ?></div>

		<!-- Sfogline in gara — controllo dei giudici -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_giudici_gara' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'giudici_gara' ) ) ) { gs_pannello_giudici_gara(); } ?></div>

		<!-- Correzione punti -->
		<div data-idx-group="sfogline">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'correzione_punti' ) ) { gs_pannello_correzione_punti(); } ?></div>

		<!-- Premio di Fine Anno -->
		<div data-idx-group="sfide">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'premio' ) ) { gs_pannello_premio(); } ?></div>

		<!-- Regia del Gaming -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_regia' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'regia' ) ) ) { gs_pannello_regia(); } ?></div>

		<!-- Vetrina on/off -->
		<div data-idx-group="partner">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'vetrina_toggle' ) ) { gs_pannello_vetrina(); } ?></div>

		<!-- L'Esperto Risponde (canali domande/risposte) -->
		<div data-idx-group="messaggi">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'esperto' ) ) { gs_pannello_esperti(); } ?></div>

		<!-- Conversazioni private (controllo) -->
		<div data-idx-group="messaggi">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'conversazioni' ) ) { gs_pannello_conversazioni(); } ?></div>

		<!-- Compleanni (controllo vetrina) -->
		<div data-idx-group="sfogline">		<?php if ( function_exists( 'gs_pannello_compleanni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'compleanni' ) ) ) { gs_pannello_compleanni(); } ?></div>

		<!-- Aiuto e suggerimenti -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_aiuto' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'aiuto' ) ) ) { gs_pannello_aiuto(); } ?></div>

		<!-- Biografie da approvare -->
		<div data-idx-group="sfogline">		<?php if ( function_exists( 'gs_pannello_biografie' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'biografie' ) ) ) { gs_pannello_biografie(); } ?></div>

		<!-- Ricettario delle Famiglie (approvazione) -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_ricettario' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'ricettario' ) ) ) { gs_pannello_ricettario(); } ?></div>

		<!-- Ricette per il Libro (riservato, non nel Ricettario pubblico) -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_ricette_libro' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'ricette_libro' ) ) ) { gs_pannello_ricette_libro(); } ?></div>

		<!-- Libreria Video delle Lezioni -->
		<div data-idx-group="corsi">		<?php if ( function_exists( 'gs_pannello_lezioni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'lezioni' ) ) ) { gs_pannello_lezioni(); } ?></div>

		<!-- Premi per Traguardo (video/messaggio automatico al livello o badge): subito
		     dopo la Libreria Video, perché i video dei premi sono spesso video di lezioni
		     già caricati qui sopra. -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_premi_traguardi' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'premi_traguardi' ) ) ) { gs_pannello_premi_traguardi(); } ?></div>

		<!-- Percorsi Guidati (gruppi ordinati di lezioni) -->
		<div data-idx-group="corsi">		<?php if ( function_exists( 'gs_pannello_percorsi_lezioni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'percorsi_lezioni' ) ) ) { gs_pannello_percorsi_lezioni(); } ?></div>

		<!-- Indovina la Sfoglia (quiz-lampo giornaliero) -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_indovina' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'indovina' ) ) ) { gs_pannello_indovina(); } ?></div>

		<!-- Il Tavolo di Lavoro (foto del giorno) -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_tavolo' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'tavolo' ) ) ) { gs_pannello_tavolo(); } ?></div>

		<!-- La Sfoglia Misurata (sfide di tecnica con dato numerico) -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_sfoglia_misurata' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'misurata' ) ) ) { gs_pannello_sfoglia_misurata(); } ?></div>

		<!-- La Giuria a Turno (voto pubblico e motivato tra sfogline) -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_giuria_turno' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'giuria' ) ) ) { gs_pannello_giuria_turno(); } ?></div>

		<!-- Sondaggi (domanda + proposte votate dalle sfogline, risultati pubblici o riservati) -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_sondaggi' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sondaggi' ) ) ) { gs_pannello_sondaggi(); } ?></div>

		<!-- FAQ - Domande -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_faq' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'faq' ) ) ) { gs_pannello_faq(); } ?></div>

		<!-- Novità: annunci pubblici -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_novita' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'novita' ) ) ) { gs_pannello_novita(); } ?></div>

		<!-- Adotta un Piatto in Via di Estinzione -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_piatti_estinzione' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'piatti_estinzione' ) ) ) { gs_pannello_piatti_estinzione(); } ?></div>

		<!-- Il Matterello Parlante (archivio vocale) -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_matterello_parlante' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'matterello' ) ) ) { gs_pannello_matterello_parlante(); } ?></div>

		<!-- La Sfida del Silenzio -->
		<div data-idx-group="sfide">		<?php if ( function_exists( 'gs_pannello_silenzio' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'silenzio' ) ) ) { gs_pannello_silenzio(); } ?></div>

		<!-- La Cassaforte del Sapere -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_cassaforte_sapere' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'cassaforte' ) ) ) { gs_pannello_cassaforte_sapere(); } ?></div>

		<!-- La Sfoglia che Insegna Se Stessa -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_sfoglia_insegna' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sfoglia_insegna' ) ) ) { gs_pannello_sfoglia_insegna(); } ?></div>

		<!-- Le Letture dei Grandi Protagonisti della Cucina -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_letture' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'letture' ) ) ) { gs_pannello_letture(); } ?></div>

		<!-- Dicono di Noi (approvazione testimonianze) -->
		<div data-idx-group="contenuti">		<?php if ( function_exists( 'gs_pannello_testimonianze' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'dicono_di_noi' ) ) ) { gs_pannello_testimonianze(); } ?></div>

		<!-- Moderazione di tutte le chat -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_moderazione' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'moderazione' ) ) ) { gs_pannello_moderazione(); } ?></div>

		<!-- Spiegazioni delle Sezioni -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_spiegazioni' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'spiegazioni' ) ) ) { gs_pannello_spiegazioni(); } ?></div>

		<!-- Il Reset del gioco — solo per il titolare, controllato dentro la funzione stessa -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_reset' ) ) { gs_pannello_reset(); } ?></div>

		<!-- Messaggi di ogni sfoglina -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_messaggi_sfogline' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'messaggi_sfogline_view' ) ) ) { gs_pannello_messaggi_sfogline(); } ?></div>

		<!-- Notifiche: quali email riceve ogni sfoglina, e su quali canali -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_notifiche_pref' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'notifiche_pref' ) ) ) { gs_pannello_notifiche_pref(); } ?></div>

		<!-- Aspetto pagina Le Sfogline -->
		<div data-idx-group="partner">		<?php if ( function_exists( 'gs_pannello_sfogline_view' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'sfogline_view' ) ) ) { gs_pannello_sfogline_view(); } ?></div>

		<!-- Caroselli per la Home Page -->
		<div data-idx-group="partner">		<?php if ( function_exists( 'gs_pannello_caroselli' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'caroselli' ) ) ) { gs_pannello_caroselli(); } ?></div>

		<!-- Posta interna -->
		<div data-idx-group="messaggi">		<?php if ( function_exists( 'gs_pannello_inbox' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'inbox' ) ) ) { gs_pannello_inbox(); } ?></div>

		<!-- Diagnostica e stato di salute -->
		<div data-idx-group="strumenti">		<?php if ( function_exists( 'gs_pannello_diagnostica' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'diagnostica' ) ) ) { gs_pannello_diagnostica(); } ?></div>

		<!-- Esporta i dati del percorso -->
		<div data-idx-group="strumenti">		<?php if ( function_exists( 'gs_pannello_export_dati' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'export_dati' ) ) ) { gs_pannello_export_dati(); } ?></div>

		<!-- Foto/video: compressione e limiti -->
		<div data-idx-group="strumenti">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'media_limiti' ) ) { gs_pannello_media(); } ?></div>

		<!-- Backup dei file -->
		<div data-idx-group="strumenti">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'backup' ) ) { gs_pannello_backup(); } ?></div>

		<!-- Blackout -->
		<div data-idx-group="impostazioni">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'blackout' ) ) { gs_pannello_blackout(); } ?></div>

		<!-- Blocco dashboard WP (solo amministratori veri) -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_blocco_wpadmin' ) ) { gs_pannello_blocco_wpadmin(); } ?></div>

		<!-- Fissa il menu in alto -->
		<div data-idx-group="impostazioni">		<?php if ( function_exists( 'gs_pannello_logo_toggle' ) ) { gs_pannello_logo_toggle(); } ?></div>

		<!-- Area Corsi Online (corsi individuali privati con un docente dell'Accademia) -->
		<div data-idx-group="corsi">		<?php if ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'area_pro' ) ) { gs_pannello_area_pro(); } ?></div>

		<!-- Diplomi e Locandine a piacimento (stampa + scarica immagine per i social) -->
		<div data-idx-group="corsi">		<?php if ( function_exists( 'gs_pannello_locandine' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'locandine' ) ) ) { gs_pannello_locandine(); } ?></div>

		<!-- Madrina & Allieva -->
		<div data-idx-group="sfogline">		<?php if ( function_exists( 'gs_pannello_madrine' ) && ( ! function_exists( 'gs_sez_zona_ok' ) || gs_sez_zona_ok( 'madrine' ) ) ) { gs_pannello_madrine(); } ?></div>

		<!-- Collaboratori (solo amministratori veri) -->
		<div data-idx-group="strumenti">		<?php gs_pannello_collaboratrici(); ?></div>

		<?php echo gs_box_open( 'Gestione avanzata', 'gs-notice' ); ?>
			<p>Tutte le impostazioni (punti, livelli, squadre, missioni, anti-spam, OneSignal) restano nel pannello completo di WordPress:
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gs-panoramica' ) ); ?>">Gaming Sfogline in wp-admin</a>.</p>
		<?php echo gs_box_close(); ?>

	</div>
	<?php
	return ob_get_clean();
}

// -----------------------------------------------------------------------------
// Sezione: Correzione punti
// -----------------------------------------------------------------------------
function gs_pannello_correzione_punti() {
	echo gs_box_open( 'Correggi punti di una sfoglina' );
	$sfogline_cp = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	?>
	<p class="gs-hint">Scegli la sfoglina dall'elenco, i punti da aggiungere (o togliere, con il segno meno) e un motivo. Serve solo per errori o casi particolari: i punti restano automatici per ogni azione.</p>
	<form class="gs-form gs-form-correzione">
		<p><label>Sfoglina<br>
			<select name="utente" required style="min-width:260px">
				<option value="">— scegli —</option>
				<?php foreach ( $sfogline_cp as $u ) : ?>
					<option value="<?php echo esc_attr( $u->user_login ); ?>"><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label></p>
		<p><label>Punti (es. 50 oppure -20)<br><input type="number" name="punti" required style="max-width:160px"></label></p>
		<p><label>Motivo<br><input type="text" name="motivo" required></label></p>
		<p><button type="submit" class="gs-btn gs-btn-sm">Applica correzione</button></p>
		<p class="gs-form-msg"></p>
	</form>
	<?php
	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// Sezione: Premio di Fine Anno
// -----------------------------------------------------------------------------
function gs_pannello_premio() {
	$anno_corrente = date( 'Y', current_time( 'timestamp' ) );
	$anno = isset( $_GET['gs_anno'] ) ? preg_replace( '/[^0-9]/', '', $_GET['gs_anno'] ) : $anno_corrente;

	$settings   = gs_settings()['year_prize'];
	$n          = (int) $settings['numero_vincitrici'];
	$classifica = gs_year_leaderboard( $anno, $n );
	$assegnato  = gs_year_prize_assigned( $anno );

	echo gs_box_open( 'Premio di Fine Anno — Corso con Rina Poletti' );
	?>
	<p><em><?php echo esc_html( $settings['testo'] ); ?></em></p>

	<form method="get" style="margin:8px 0">
		<?php gs_pannello_preserve_page_field(); ?>
		<label>Anno: <input type="number" name="gs_anno" value="<?php echo esc_attr( $anno ); ?>" style="width:100px"></label>
		<button class="gs-btn gs-btn-sm gs-btn-verde">Mostra</button>
	</form>

	<h4>Classifica <?php echo esc_html( $anno ); ?> — prime <?php echo (int) $n; ?></h4>
	<table class="gs-table gs-paginate" data-per-page="10">
		<thead><tr><th>Pos.</th><th>Sfoglina</th><th>Punti <?php echo esc_html( $anno ); ?></th></tr></thead>
		<tbody>
		<?php $pos = 1; foreach ( $classifica as $riga ) : ?>
			<tr><td><?php echo $pos++; ?></td><td><?php echo esc_html( $riga['user']->display_name ); ?></td><td><?php echo (int) $riga['punti']; ?></td></tr>
		<?php endforeach; ?>
		<?php if ( empty( $classifica ) ) : ?><tr><td colspan="3">Nessun dato per quest'anno.</td></tr><?php endif; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $classifica ) ) : ?>
		<div class="gs-premio-azione" data-anno="<?php echo esc_attr( $anno ); ?>" style="margin-top:12px">
			<?php if ( $assegnato ) : ?>
				<p class="gs-hint"><strong>Premio già assegnato</strong> il <?php echo esc_html( $assegnato ); ?>. Riassegnando invierai di nuovo le email.</p>
			<?php endif; ?>
			<button class="gs-btn gs-btn-sm gs-assegna-premio">🎓 Assegna il premio alle prime <?php echo (int) $n; ?></button>
			<a class="gs-btn gs-btn-sm gs-btn-ghost" href="<?php echo esc_url( gs_export_url( 'generale' ) ); ?>" target="_blank">📄 Esporta PDF</a>
			<p class="gs-premio-msg gs-richiesta-esito"></p>
		</div>
	<?php endif; ?>
	<?php
	echo gs_box_close();
}

// -----------------------------------------------------------------------------
// Sezione: Sfide blindate — ammissione concorrenti a discrezione
// -----------------------------------------------------------------------------
function gs_pannello_sfide_blindate() {
	$tutte = get_posts( array(
		'post_type'      => 'gs_sfida',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	echo gs_box_open( 'Sfide — regole di partecipazione' );
	echo gs_sezione_aiuto( 'Due strumenti diversi per limitare chi partecipa a una sfida, a tua scelta: le "sfide blindate" (livello minimo + ammissioni/esclusioni una per una) e, per QUALSIASI sfida, l\'esclusione automatica delle prime posizioni della classifica generale — utile per dare spazio a chi è più indietro, senza dover escludere una per una le sfogline in testa.' );

	if ( empty( $tutte ) ) {
		echo '<p class="gs-hint">Non hai ancora nessuna sfida. Creane una da <a href="' . esc_url( admin_url( 'post-new.php?post_type=gs_sfida' ) ) . '">Nuova sfida</a>.</p>';
		echo gs_box_close();
		return;
	}

	$sel = isset( $_GET['gs_sfida_sel'] ) ? (int) $_GET['gs_sfida_sel'] : (int) $tutte[0]->ID;
	?>
	<form method="get" style="margin-bottom:12px">
		<?php gs_pannello_preserve_page_field(); ?>
		<label>Sfida:
			<select name="gs_sfida_sel" onchange="this.form.submit()">
				<?php foreach ( $tutte as $b ) :
					$lm = (int) get_post_meta( $b->ID, 'gs_livello_min', true );
					$tn = (int) get_post_meta( $b->ID, 'gs_escludi_top_n', true ); ?>
					<option value="<?php echo (int) $b->ID; ?>" <?php selected( $sel, $b->ID ); ?>>
						<?php echo esc_html( get_the_title( $b ) ); ?><?php echo $lm ? ' — livello min ' . $lm : ''; ?><?php echo $tn ? ' — prime ' . $tn . ' escluse' : ''; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
	</form>

	<?php
	$top_n = (int) get_post_meta( $sel, 'gs_escludi_top_n', true );
	?>
	<div class="gs-todo-riquadro" style="margin-bottom:14px">
		<p class="gs-hint">Escludi automaticamente da questa sfida le sfogline nelle prime posizioni della classifica generale (0 = nessuna esclusione, tutte possono partecipare). Chi è ammessa a tua scelta (whitelist qui sotto) partecipa comunque, anche se è tra le prime.</p>
		<p>
			<label>Escludi le prime <input type="number" min="0" max="500" class="gs-top-n-input" value="<?php echo $top_n; ?>" style="width:70px"> posizioni della classifica generale</label>
			<button class="gs-btn gs-btn-sm gs-top-n-salva" data-sfida="<?php echo (int) $sel; ?>">Salva</button>
			<span class="gs-top-n-msg gs-richiesta-esito"></span>
		</p>
	</div>

	<?php if ( gs_sfida_blindata( $sel ) ) :
		$livello_min = (int) get_post_meta( $sel, 'gs_livello_min', true );
		$white = gs_sfida_whitelist( $sel );
		$black = gs_sfida_blacklist( $sel );

		// Elenco sfogline approvate.
		$sfogline = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		?>
		<p class="gs-hint">
			Sfida blindata — livello minimo: <strong><?php echo $livello_min ? $livello_min : 'nessuno'; ?></strong>.
			Chi ha il livello richiesto è ammessa in automatico; puoi comunque <strong>ammettere</strong> chi è sotto livello o <strong>escludere</strong> chi non vuoi, a tua discrezione.
		</p>
		<input type="text" class="gs-cerca-input" data-target=".gs-tabella-ammissioni" placeholder="🔍 Cerca sfoglina…" style="width:100%;max-width:320px;margin-bottom:10px">
		<table class="gs-table gs-paginate gs-tabella-ammissioni" data-per-page="10" data-sfida="<?php echo (int) $sel; ?>">
			<thead><tr><th>Sfoglina</th><th>Livello</th><th>Stato</th><th>Azioni</th></tr></thead>
			<tbody>
			<?php foreach ( $sfogline as $u ) :
				if ( ! gs_e_sfoglina_vera( $u ) ) { continue; }
				$lv = gs_get_level( $u->ID );
				echo gs_ammissione_riga( $sel, $u, $lv, $livello_min, $white, $black );
			endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="gs-hint">Questa sfida non è "blindata" (nessun livello minimo, nessuna lista di ammissione/esclusione per singola sfoglina). Puoi renderla blindata modificando la sfida, oppure usare solo l'esclusione per classifica qui sopra.</p>
	<?php endif; ?>
	<?php
	echo gs_box_close();
}

/**
 * Restituisce l'HTML di una riga della tabella ammissioni.
 */
function gs_ammissione_riga( $sfida_id, $u, $lv, $livello_min, $white, $black ) {
	$uid = (int) $u->ID;
	$in_white = in_array( $uid, $white, true );
	$in_black = in_array( $uid, $black, true );

	if ( $in_black ) {
		$stato = '<span class="gs-stato gs-stato-no">Esclusa</span>';
	} elseif ( $in_white ) {
		$stato = '<span class="gs-stato gs-stato-si">Ammessa (a tua scelta)</span>';
	} elseif ( $livello_min > 0 && (int) $lv['numero'] >= $livello_min ) {
		$stato = '<span class="gs-stato gs-stato-si">Ammessa (per livello)</span>';
	} else {
		$stato = '<span class="gs-stato gs-stato-off">Non ammessa</span>';
	}

	ob_start(); ?>
	<tr data-user="<?php echo $uid; ?>">
		<td><?php echo esc_html( $u->display_name ); ?></td>
		<td><?php echo esc_html( $lv['simbolo'] . ' ' . $lv['numero'] ); ?></td>
		<td class="gs-stato-cell"><?php echo $stato; ?></td>
		<td class="gs-ammissione-azioni">
			<button class="gs-btn gs-btn-sm gs-ammetti" data-stato="ammetti">Ammetti</button>
			<button class="gs-btn gs-btn-sm gs-btn-ghost gs-escludi" data-stato="escludi">Escludi</button>
			<button class="gs-btn gs-btn-sm gs-btn-ghost gs-reset-amm" data-stato="reset">Reset</button>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}

// -----------------------------------------------------------------------------
// Bacheca di riepilogo: i numeri del giorno a colpo d'occhio
// -----------------------------------------------------------------------------

/**
 * Dati grezzi della bacheca di riepilogo, con una chiave stabile per voce
 * (usata anche dal "cartellino" della Plancia Generale in admin.php, per
 * agganciarsi al conteggio giusto senza dipendere dal testo dell'etichetta,
 * che può cambiare). Ogni voce: array( conteggio, etichetta, colore,
 * allerta, id della sezione a cui saltare sul sito pubblico ).
 */
function gs_riepilogo_dati() {
	// Iscrizioni da approvare.
	$iscr = function_exists( 'gs_get_pending_users' ) ? count( gs_get_pending_users() ) : 0;

	// Domande senza risposta (tutti i canali).
	$senza = 0;
	if ( function_exists( 'gs_esperti_canali' ) && function_exists( 'gs_esperto_senza_risposta' ) ) {
		foreach ( gs_esperti_canali() as $slug => $ch ) { $senza += gs_esperto_senza_risposta( $slug ); }
	}

	// Richieste di conversazione in attesa.
	$attesa = function_exists( 'gs_conv_in_attesa' ) ? count( gs_conv_in_attesa() ) : 0;

	// Ricette in attesa di approvazione.
	$ricette = function_exists( 'gs_ricette_in_attesa' ) ? count( gs_ricette_in_attesa() ) : 0;

	// Testimonianze in attesa di approvazione.
	$testim = function_exists( 'gs_testim_in_attesa' ) ? count( gs_testim_in_attesa() ) : 0;

	// Biografie della Vetrina in attesa di approvazione.
	$bio = function_exists( 'gs_bio_in_attesa' ) ? count( gs_bio_in_attesa() ) : 0;

	// Risposte alle lezioni video ancora senza un riscontro.
	$lezioni_risp = function_exists( 'gs_lezioni_totale_senza_riscontro' ) ? gs_lezioni_totale_senza_riscontro() : 0;

	// Foto del Tavolo di Lavoro ancora senza un commento.
	$tavolo_senza = function_exists( 'gs_tavolo_totale_senza_commento' ) ? gs_tavolo_totale_senza_commento() : 0;

	// Messaggi privati che attendono ancora una risposta della segreteria.
	$msg_attesa = function_exists( 'gs_msg_totale_in_attesa' ) ? gs_msg_totale_in_attesa() : 0;

	// Abbonamenti scaduti.
	$abb_scaduti = function_exists( 'gs_abbonamento_totale_scaduti' ) ? gs_abbonamento_totale_scaduti() : 0;

	// Vetrine "Gli Artigiani della Pasta" in attesa di approvazione.
	$artigiani = 0;
	if ( function_exists( 'gs_art_elenco' ) ) {
		foreach ( gs_art_elenco() as $art_p ) {
			if ( 'in_attesa' === get_post_meta( $art_p->ID, 'gs_art_stato', true ) ) { $artigiani++; }
		}
	}

	// Vetrine "Le Scuole di Cucina" in attesa di approvazione.
	$scuole = 0;
	if ( function_exists( 'gs_scu_elenco' ) ) {
		foreach ( gs_scu_elenco() as $scu_p ) {
			if ( 'in_attesa' === get_post_meta( $scu_p->ID, 'gs_scu_stato', true ) ) { $scuole++; }
		}
	}

	// Corsi professionali attivi.
	$corsi = 0;
	foreach ( get_posts( array( 'post_type' => 'gs_corso', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $cid ) {
		if ( 'sospeso' !== get_post_meta( $cid, 'gs_stato', true ) ) { $corsi++; }
	}

	// Sfoglie pubblicate oggi.
	$oggi = count( get_posts( array(
		'post_type'      => 'gs_sfoglia',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => array( array( 'after' => 'today midnight' ) ),
	) ) );

	// Il 5° valore, quando presente, è l'id della sezione a cui saltare al clic
	// (solo le schede che possono lampeggiare hanno una sezione unica di destinazione).
	return array(
		'iscrizioni'    => array( $iscr,     'Iscrizioni da approvare', '#2f8f5b', $iscr > 0,   'gs-box-iscrizioni' ),
		'esperti'       => array( $senza,    'Domande senza risposta',  '#9c5b3b', $senza > 0,  'gs-box-esperti' ),
		'conversazioni' => array( $attesa,   'Richieste in attesa',     '#c08a2c', $attesa > 0, 'gs-box-conversazioni' ),
		'ricettario'    => array( $ricette,  'Ricette in attesa',       '#8a5a2f', $ricette > 0, 'gs-box-ricettario' ),
		'testimonianze' => array( $testim,   'Testimonianze in attesa', '#4a7a8c', $testim > 0, 'gs-box-testimonianze' ),
		'biografie'     => array( $bio,      'Biografie in attesa',     '#8c4a7a', $bio > 0,    'gs-box-biografie' ),
		// Nessun target (5° valore): "Artigiani della Pasta" si gestisce solo
		// dalla Plancia Generale in wp-admin, non ha un riquadro nel pannello
		// del sito — qui la scheda informa, ma non c'è dove saltare al clic.
		'artigiani'     => array( $artigiani, 'Vetrine Artigiani da approvare', '#a6790c', $artigiani > 0, '' ),
		'scuole'        => array( $scuole,   'Vetrine Scuole di Cucina da approvare', '#6a8caf', $scuole > 0, '' ),
		'lezioni'       => array( $lezioni_risp, 'Risposte lezioni da leggere', '#3b7a9c', $lezioni_risp > 0, 'gs-box-lezioni' ),
		'tavolo'        => array( $tavolo_senza, 'Foto del Tavolo da commentare', '#a35b2f', $tavolo_senza > 0, 'gs-box-tavolo' ),
		'messaggi_attesa' => array( $msg_attesa, 'Messaggi in attesa di risposta', '#b23223', $msg_attesa > 0, 'gs-box-msg' ),
		'abbonamenti_scaduti' => array( $abb_scaduti, 'Abbonamenti scaduti', '#b23223', $abb_scaduti > 0, 'gs-box-abbonamenti' ),
		'sfoglie_oggi'  => array( $oggi,     'Sfoglie di oggi',         '#a8541f', false, '' ),
		'corsi_attivi'  => array( $corsi,    'Corsi attivi',            '#7a4a86', false, '' ),
	);
}

function gs_pannello_riepilogo() {
	if ( ! gs_can_manage() ) { return; }

	$cards = array_values( gs_riepilogo_dati() );

	echo '<div class="gs-riepilogo">';
	foreach ( $cards as $c ) {
		$alert  = $c[3] ? ' gs-riep-alert' : '';
		$target = isset( $c[4] ) ? $c[4] : '';
		$tag    = $target ? 'a' : 'div';
		printf(
			'<%1$s class="gs-riep-card%2$s"%3$s style="--rc:%4$s"><span class="gs-riep-num">%5$d</span><span class="gs-riep-lbl">%6$s</span></%1$s>',
			$tag,
			esc_attr( $alert ),
			$target ? ' href="#' . esc_attr( $target ) . '" data-target="' . esc_attr( $target ) . '"' : '',
			esc_attr( $c[2] ),
			(int) $c[0],
			esc_html( $c[1] )
		);
	}
	echo '</div>';
}

// -----------------------------------------------------------------------------
// Sezione: Blackout (oscura tutto il Gaming)
// -----------------------------------------------------------------------------
function gs_pannello_blackout() {
	$attivo = gs_blackout_attivo();
	echo gs_box_open( 'Oscura il Gaming (blackout)' );
	?>
	<p class="gs-hint">Con un clic sospendi tutto il Gaming per le sfogline: pagine, gallerie, classifiche e linguette mostrano un avviso di sospensione. Tu e i collaboratori continuate a vedere tutto.</p>
	<p>Stato attuale:
		<strong class="gs-blackout-stato <?php echo $attivo ? 'off' : 'on'; ?>">
			<?php echo $attivo ? 'GAMING OSCURATO' : 'GAMING ATTIVO'; ?>
		</strong>
	</p>
	<p>
		<button class="gs-btn gs-btn-sm gs-toggle-blackout<?php echo $attivo ? ' gs-btn-verde' : ''; ?>">
			<?php echo $attivo ? 'Riattiva il Gaming' : 'Oscura tutto il Gaming'; ?>
		</button>
		<span class="gs-blackout-msg gs-richiesta-esito"></span>
	</p>

	<?php
	$sfogline = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	$esenti   = gs_blackout_esenti_lista();
	?>
	<p class="gs-hint" style="margin-top:16px">Vuoi far provare il Gaming a qualcuna <strong>anche mentre è oscurato</strong> (per farle vedere come funziona, senza aprirlo a tutte)? Scegli qui le sue sfogline: solo loro, oltre a te e ai collaboratori, continueranno a vedere tutto.</p>
	<form class="gs-form gs-form-blackout-esenti" onsubmit="return false">
		<?php if ( $sfogline ) : ?>
			<select multiple class="gs-blackout-esenti-select" size="6" style="min-width:220px;max-width:100%">
				<?php foreach ( $sfogline as $u ) : ?>
					<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $esenti, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="gs-hint">Tieni premuto Ctrl/Cmd per sceglierne più di una.</p>
		<?php else : ?>
			<p class="gs-hint">Non ci sono ancora sfogline approvate tra cui scegliere.</p>
		<?php endif; ?>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-blackout-esenti-salva">Salva eccezioni</button> <span class="gs-blackout-esenti-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_fe_toggle_blackout', 'gs_ajax_fe_toggle_blackout' );
function gs_ajax_fe_toggle_blackout() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$s = gs_settings();
	$nuovo = empty( $s['features']['blackout'] );
	$s['features']['blackout'] = $nuovo;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array(
		'attivo'  => $nuovo,
		'message' => $nuovo ? 'Gaming oscurato.' : 'Gaming riattivato.',
	) );
}

add_action( 'wp_ajax_gs_blackout_esenti_salva', 'gs_ajax_blackout_esenti_salva' );
function gs_ajax_blackout_esenti_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}
	$ids = isset( $_POST['esenti'] ) && is_array( $_POST['esenti'] ) ? array_map( 'intval', $_POST['esenti'] ) : array();
	$s = gs_settings();
	$s['blackout_esenti'] = array_values( array_unique( array_filter( $ids ) ) );
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Eccezioni salvate.' ) );
}

// -----------------------------------------------------------------------------
// Sezione: Blocco dashboard WP (solo amministratori veri)
// -----------------------------------------------------------------------------
function gs_pannello_blocco_wpadmin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return; // solo un amministratore vero può togliere l'accesso agli altri
	}
	$attivo = gs_blocco_wpadmin_attivo();
	echo gs_box_open( 'Blocco dashboard WordPress' );
	?>
	<p class="gs-hint">Con l'interruttore acceso, chiunque non sia amministratore vero (compresi i collaboratori) viene rimandato al Pannello di Controllo del sito appena prova ad aprire la dashboard di WordPress: può continuare a navigare e lavorare nel progetto, ma non vede il resto di WordPress. Utile per condividere il sito durante una manutenzione. Tu, come amministratore vero, non sei mai bloccato.</p>
	<p>Stato attuale:
		<strong class="gs-blackout-stato <?php echo $attivo ? 'off' : 'on'; ?>">
			<?php echo $attivo ? 'DASHBOARD BLOCCATA' : 'DASHBOARD LIBERA'; ?>
		</strong>
	</p>
	<p>
		<button class="gs-btn gs-btn-sm gs-toggle-blocco-wpadmin<?php echo $attivo ? ' gs-btn-verde' : ''; ?>">
			<?php echo $attivo ? 'Riapri la dashboard a tutti' : 'Blocca la dashboard agli altri'; ?>
		</button>
		<span class="gs-blocco-wpadmin-msg gs-richiesta-esito"></span>
	</p>
	<?php
	echo gs_box_close();
}

/**
 * Pulsante "Nascondi logo" dentro il Pannello di Controllo (richiesto da
 * Ennio, 17/08/2026): stesso interruttore già usato altrove sul sito (classe
 * body "gs-logo-hidden", vedi gsHeaderToggleInit() in gaming.js), ma
 * richiamabile anche da qui invece che solo dal pulsante fluttuante presente
 * su alcune pagine. Puramente lato client (nessun salvataggio sul server):
 * uno stato voluto per la sessione di navigazione, non un'impostazione del
 * sito da ricordare per tutti.
 */
function gs_pannello_logo_toggle() {
	if ( ! gs_can_manage() ) { return; }
	echo gs_box_open( 'Fissa il menu in alto' );
	?>
	<p class="gs-hint">Compatta il menu in cima al sito per lasciare più spazio al contenuto — stessa funzione del pulsante che compare da solo sulle pagine dove serve (quelle con un sito esterno incorporato, es. "Rina Poletti" o "Lentium Notizie"). Qui la trovi sempre, a portata di mano.</p>
	<p>
		<button type="button" class="gs-btn gs-btn-sm gs-toggle-logo-pannello">Fissa il menu in alto</button>
		<span class="gs-logo-pannello-msg gs-richiesta-esito"></span>
	</p>
	<?php
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_fe_toggle_blocco_wpadmin', 'gs_ajax_fe_toggle_blocco_wpadmin' );
function gs_ajax_fe_toggle_blocco_wpadmin() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Solo un amministratore può cambiare questa impostazione.' ) );
	}
	$s = gs_settings();
	$nuovo = empty( $s['features']['blocca_wp_admin'] );
	$s['features']['blocca_wp_admin'] = $nuovo;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array(
		'attivo'  => $nuovo,
		'message' => $nuovo ? 'Dashboard bloccata per chi non è amministratore.' : 'Dashboard riaperta a tutti.',
	) );
}

// -----------------------------------------------------------------------------
// Sezione: Collaboratori (permesso dedicato, solo per veri amministratori)
// -----------------------------------------------------------------------------
function gs_pannello_collaboratrici() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return; // solo gli amministratori veri possono nominare collaboratori
	}

	echo gs_box_open( 'Collaboratori del pannello' );
	?>
	<p class="gs-hint">Dai il permesso di usare il Pannello di Controllo a una o due persone di fiducia <strong>senza renderle amministratori del sito</strong>. I collaboratori vedranno il pannello e potranno gestire iscrizioni, punti, sfide, premio e vetrina, ma non avranno accesso alle altre parti di WordPress.</p>

	<form class="gs-form gs-form-collab-nuovo" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">
		<strong>Aggiungi un nuovo collaboratore</strong>
		<p class="gs-hint">Se la persona non ha ancora un account sul sito, crealo direttamente qui: riceverà una email per impostare la propria password. Se ha già un account, cercala invece nell'elenco qui sotto.</p>
		<p><label>Nome<br><input type="text" name="nome" autocomplete="off" style="width:100%" required></label></p>
		<p><label>Email<br><input type="email" name="email" autocomplete="off" style="width:100%" required></label></p>
		<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-collab-crea">Crea e rendi collaboratore</button> <span class="gs-collab-crea-msg gs-richiesta-esito"></span></p>
	</form>
	<?php
	$utenti = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
	?>
	<input type="text" class="gs-cerca-input" data-target=".gs-tabella-collab" placeholder="🔍 Cerca persona…" style="width:100%;max-width:320px;margin-bottom:10px">
	<table class="gs-table gs-paginate gs-tabella-collab" data-per-page="10">
		<thead><tr><th>Persona</th><th>Ruolo</th><th>Stato</th><th>Azione</th></tr></thead>
		<tbody>
		<?php foreach ( $utenti as $u ) :
			// Gli amministratori gestiscono già tutto: non serve elencarli.
			if ( user_can( $u->ID, 'manage_options' ) ) { continue; }
			echo gs_collab_riga( $u );
		endforeach; ?>
		</tbody>
	</table>

	<form class="gs-form gs-form-collab-modifica" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-top:14px;display:none">
		<strong>Modifica dati collaboratore</strong>
		<input type="hidden" name="user">
		<p><label>Nome<br><input type="text" name="nome" style="width:100%" required></label></p>
		<p><label>Email<br><input type="email" name="email" style="width:100%" required></label></p>
		<p>
			<button class="gs-btn gs-btn-sm gs-btn-verde gs-collab-modifica-salva">Salva modifiche</button>
			<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-collab-modifica-annulla">Annulla</button>
			<span class="gs-collab-modifica-msg gs-richiesta-esito"></span>
		</p>
	</form>
	<?php
	echo gs_box_close();
}

/**
 * Ricava uno username WordPress libero a partire dalla parte prima della @
 * dell'email, aggiungendo un numero in fondo se è già in uso.
 */
function gs_username_da_email( $email ) {
	$base = sanitize_user( strtolower( substr( $email, 0, strpos( $email, '@' ) ) ), true );
	if ( '' === $base ) {
		$base = 'collaboratore';
	}
	$username = $base;
	$n = 1;
	while ( username_exists( $username ) ) {
		$n++;
		$username = $base . $n;
	}
	return $username;
}

/**
 * Crea un nuovo account WordPress e lo rende subito collaboratore — senza
 * passare dalla coda di iscrizione pubblica: il titolare lo sta già
 * approvando nell'atto stesso di crearlo. La password non è mai visibile
 * qui: WordPress manda alla persona una email con il link per impostarla.
 */
add_action( 'wp_ajax_gs_collab_crea', 'gs_ajax_collab_crea' );
function gs_ajax_collab_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Solo un amministratore può aggiungere collaboratori.' ) );
	}

	$nome  = gs_clean( $_POST['nome'] ?? '' );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! $nome || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Scrivi un nome e un\'email valida.' ) );
	}
	if ( email_exists( $email ) ) {
		wp_send_json_error( array( 'message' => 'Esiste già un account con questa email — cercala nell\'elenco qui sotto invece di crearne uno nuovo.' ) );
	}

	$user_id = wp_insert_user( array(
		'user_login'   => gs_username_da_email( $email ),
		'user_email'   => $email,
		'user_pass'    => wp_generate_password( 20 ),
		'display_name' => $nome,
		'first_name'   => $nome,
		'role'         => 'subscriber',
	) );
	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
	}

	update_user_meta( $user_id, 'gs_status', 'approvata' );
	$user = get_user_by( 'id', $user_id );
	if ( $user ) {
		$user->add_cap( 'gs_manage_gaming' );
	}
	gs_cache_bump();

	// Email di benvenuto standard di WordPress, con il link per impostare la password.
	wp_new_user_notification( $user_id, null, 'user' );

	wp_send_json_success( array(
		'message' => $nome . ' è stato aggiunto come collaboratore. Riceverà una email per impostare la password.',
	) );
}

/**
 * Modifica nome ed email di un utente esistente (collaboratore o no) —
 * riservato al titolare, come le altre azioni su questa sezione.
 */
add_action( 'wp_ajax_gs_collab_modifica', 'gs_ajax_collab_modifica' );
function gs_ajax_collab_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Solo un amministratore può modificare questi dati.' ) );
	}

	$uid  = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
	$user = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $user ) {
		wp_send_json_error( array( 'message' => 'Persona non valida.' ) );
	}

	$nome  = gs_clean( $_POST['nome'] ?? '' );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! $nome || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Scrivi un nome e un\'email valida.' ) );
	}
	// L'email va verificata solo se è cambiata: altrimenti risulterebbe
	// "già in uso" dalla stessa persona che la sta modificando.
	if ( strtolower( $email ) !== strtolower( $user->user_email ) && email_exists( $email ) ) {
		wp_send_json_error( array( 'message' => 'Esiste già un altro account con questa email.' ) );
	}

	$result = wp_update_user( array(
		'ID'           => $uid,
		'display_name' => $nome,
		'first_name'   => $nome,
		'user_email'   => $email,
	) );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}
	gs_cache_bump();

	wp_send_json_success( array( 'message' => 'Dati aggiornati.' ) );
}

/**
 * Riga della tabella collaboratori.
 */
function gs_collab_riga( $u ) {
	$is_collab = user_can( $u->ID, 'gs_manage_gaming' );
	$ruolo     = ! empty( $u->roles ) ? $u->roles[0] : '—';
	ob_start(); ?>
	<tr data-user="<?php echo (int) $u->ID; ?>" data-nome="<?php echo esc_attr( $u->display_name ); ?>" data-email="<?php echo esc_attr( $u->user_email ); ?>">
		<td><?php echo esc_html( $u->display_name ); ?></td>
		<td><?php echo esc_html( $ruolo ); ?></td>
		<td class="gs-collab-stato-cell">
			<?php if ( $is_collab ) : ?>
				<span class="gs-stato gs-stato-si">Collaboratore</span>
			<?php else : ?>
				<span class="gs-stato gs-stato-off">Normale</span>
			<?php endif; ?>
		</td>
		<td class="gs-collab-azione">
			<button class="gs-btn gs-btn-sm gs-toggle-collab <?php echo $is_collab ? 'gs-btn-ghost' : ''; ?>">
				<?php echo $is_collab ? 'Revoca' : 'Collaboratore'; ?>
			</button>
			<button type="button" class="gs-btn gs-btn-sm gs-btn-ghost gs-collab-modifica-apri">✏️ Modifica</button>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}

// -----------------------------------------------------------------------------
// Sezione: Vetrina on/off
// -----------------------------------------------------------------------------
function gs_pannello_vetrina() {
	$attiva = gs_vetrina_attiva();
	echo gs_box_open( 'Vetrina pubblica del profilo' );
	?>
	<p class="gs-hint">La Vetrina è la pagina pubblica "Vedi/Condividi la tua vetrina" delle sfogline. Puoi attivarla o disattivarla per tutto il portale.</p>
	<p>
		Stato attuale:
		<strong class="gs-vetrina-stato <?php echo $attiva ? 'on' : 'off'; ?>">
			<?php echo $attiva ? 'ATTIVA' : 'DISATTIVATA'; ?>
		</strong>
	</p>
	<p>
		<button class="gs-btn gs-btn-sm gs-toggle-vetrina" data-attiva="<?php echo $attiva ? '1' : '0'; ?>">
			<?php echo $attiva ? 'Disattiva la Vetrina' : 'Attiva la Vetrina'; ?>
		</button>
		<span class="gs-vetrina-msg gs-richiesta-esito"></span>
	</p>

	<hr>
	<p class="gs-hint"><strong>Controllo per singola sfoglina.</strong> Anche con l'interruttore generale acceso, qui puoi bloccare la vetrina di una singola sfoglina: la sua pagina pubblica, il link e la linguetta spariranno solo per lei.</p>
	<?php
	$sfogline = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
	?>
	<input type="text" class="gs-cerca-input" data-target=".gs-tabella-vetrine" placeholder="🔍 Cerca sfoglina…" style="width:100%;max-width:320px;margin-bottom:10px">
	<table class="gs-table gs-paginate gs-tabella-vetrine" data-per-page="10">
		<thead><tr><th>Sfoglina</th><th>Stato vetrina</th><th>Azione</th></tr></thead>
		<tbody>
		<?php foreach ( $sfogline as $u ) :
			if ( ! gs_e_sfoglina_vera( $u ) ) { continue; }
			echo gs_vetrina_riga( $u );
		endforeach; ?>
		</tbody>
	</table>
	<?php
	echo gs_box_close();
}

/**
 * Riga della tabella "vetrine per singola sfoglina".
 */
function gs_vetrina_riga( $u ) {
	$bloccata = gs_vetrina_bloccata( $u->ID );
	ob_start(); ?>
	<tr class="<?php echo $bloccata ? 'gs-vetrina-rossa' : 'gs-vetrina-verde'; ?>" data-user="<?php echo (int) $u->ID; ?>">
		<td><?php echo esc_html( $u->display_name ); ?></td>
		<td class="gs-vetrina-stato-cell">
			<?php if ( $bloccata ) : ?>
				<span class="gs-stato gs-stato-no">Bloccata</span>
			<?php else : ?>
				<span class="gs-stato gs-stato-si">Attiva</span>
			<?php endif; ?>
		</td>
		<td class="gs-vetrina-azione">
			<button class="gs-btn gs-btn-sm gs-toggle-vetrina-utente<?php echo $bloccata ? ' gs-btn-verde' : ''; ?>" data-stato="<?php echo $bloccata ? 'blocca' : 'attiva'; ?>">
				<?php echo $bloccata ? 'Riattiva' : 'Blocca'; ?>
			</button>
		</td>
	</tr>
	<?php
	return ob_get_clean();
}

/**
 * Elenco richieste di iscrizione in attesa (tabella con azioni AJAX).
 * Riutilizzabile sia nel pannello front-end sia nella plancia wp-admin.
 */
function gs_pannello_richieste_inner( $pending = null ) {
	if ( null === $pending ) {
		$pending = gs_get_pending_users();
	}
	if ( ! empty( $pending ) ) {
		echo '<input type="text" class="gs-cerca-input" data-target="#gs-pannello-richieste" placeholder="🔍 Cerca richiesta…" style="width:100%;max-width:320px;margin-bottom:10px">';
	}
	echo '<div id="gs-pannello-richieste">';
	if ( empty( $pending ) ) {
		echo '<p>Nessuna richiesta in attesa. 🎉</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="10"><thead><tr><th>Nome</th><th>Username</th><th>Email</th><th>Squadra</th><th>Data</th><th>Email verificata</th><th>Azioni</th></tr></thead><tbody>';
		foreach ( $pending as $u ) {
			$team    = get_user_meta( $u->ID, 'gs_team', true );
			$verificata = function_exists( 'gs_email_verificata' ) && gs_email_verificata( $u->ID );
			printf(
				'<tr class="gs-richiesta-rossa" data-user="%1$d"><td>%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td><td>%7$s</td>'
				. '<td class="gs-richiesta-azioni">'
				. '<button class="gs-btn gs-btn-sm gs-approva" data-user="%1$d">Approva</button> '
				. '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-rifiuta" data-user="%1$d">Rifiuta</button>'
				. '</td></tr>',
				(int) $u->ID,
				esc_html( $u->display_name ),
				esc_html( $u->user_login ),
				esc_html( $u->user_email ),
				esc_html( $team ? $team : '—' ),
				esc_html( mysql2date( 'j/m/Y', $u->user_registered ) ),
				$verificata ? '<span class="gs-badge-ok">✅ Sì</span>' : '<span class="gs-hint">— No</span>'
			);
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

/**
 * Campo nascosto per conservare la pagina corrente nei form GET del pannello,
 * sia sul front-end (page_id) sia in wp-admin (page).
 */
function gs_pannello_preserve_page_field() {
	if ( is_admin() && isset( $_GET['page'] ) ) {
		echo '<input type="hidden" name="page" value="' . esc_attr( sanitize_key( $_GET['page'] ) ) . '">';
		return;
	}
	$page_id = get_queried_object_id();
	if ( $page_id ) {
		echo '<input type="hidden" name="page_id" value="' . (int) $page_id . '">';
	}
}

// -----------------------------------------------------------------------------
// AJAX: approva / rifiuta un'iscrizione dal front-end
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_gestisci_iscrizione', 'gs_ajax_fe_gestisci_iscrizione' );

function gs_ajax_fe_gestisci_iscrizione() {
	check_ajax_referer( 'gs_ajax', 'nonce' );

	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Non hai i permessi per gestire le iscrizioni.' ) );
	}

	$uid       = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
	$decisione = isset( $_POST['decisione'] ) ? sanitize_key( $_POST['decisione'] ) : '';

	if ( ! $uid || ! get_userdata( $uid ) ) {
		wp_send_json_error( array( 'message' => 'Utente non valido.' ) );
	}

	if ( 'approva' === $decisione ) {
		gs_approve_user( $uid );
		wp_send_json_success( array( 'message' => 'Sfoglina approvata e avvisata via email.' ) );
	} elseif ( 'rifiuta' === $decisione ) {
		gs_reject_user( $uid );
		wp_send_json_success( array( 'message' => 'Richiesta rifiutata.' ) );
	}

	wp_send_json_error( array( 'message' => 'Azione non riconosciuta.' ) );
}

// -----------------------------------------------------------------------------
// AJAX: correzione punti dal front-end
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_correggi_punti', 'gs_ajax_fe_correggi_punti' );

function gs_ajax_fe_correggi_punti() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}

	$ident  = isset( $_POST['utente'] ) ? sanitize_text_field( wp_unslash( $_POST['utente'] ) ) : '';
	$punti  = isset( $_POST['punti'] ) ? (int) $_POST['punti'] : 0;
	$motivo = isset( $_POST['motivo'] ) ? sanitize_text_field( wp_unslash( $_POST['motivo'] ) ) : '';

	$user = is_email( $ident ) ? get_user_by( 'email', $ident ) : get_user_by( 'login', $ident );
	if ( ! $user ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non trovata.' ) );
	}
	if ( 0 === $punti ) {
		wp_send_json_error( array( 'message' => 'Indica un numero di punti diverso da zero.' ) );
	}
	if ( ! $motivo ) {
		wp_send_json_error( array( 'message' => 'Indica un motivo.' ) );
	}

	gs_add_points( $user->ID, $punti, 'Correzione manuale: ' . $motivo );
	$totale = (int) get_user_meta( $user->ID, 'gs_points', true );

	wp_send_json_success( array(
		'message' => sprintf( 'Fatto: %s%d punti a %s. Nuovo totale: %d.', $punti > 0 ? '+' : '', $punti, $user->display_name, $totale ),
	) );
}

// -----------------------------------------------------------------------------
// AJAX: assegnazione Premio di Fine Anno dal front-end
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_assegna_premio', 'gs_ajax_fe_assegna_premio' );

function gs_ajax_fe_assegna_premio() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}

	$anno = isset( $_POST['anno'] ) ? preg_replace( '/[^0-9]/', '', $_POST['anno'] ) : '';
	if ( ! $anno ) {
		wp_send_json_error( array( 'message' => 'Anno non valido.' ) );
	}

	$vincitrici = gs_assign_year_prize( $anno );
	wp_send_json_success( array(
		'message' => sprintf( 'Premio assegnato a %d sfogline: %s', count( $vincitrici ), implode( ', ', $vincitrici ) ),
	) );
}

// -----------------------------------------------------------------------------
// AJAX: ammissione/esclusione concorrenti in una sfida blindata
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_ammissione', 'gs_ajax_fe_ammissione' );

function gs_ajax_fe_ammissione() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}

	$sfida_id = isset( $_POST['sfida'] ) ? (int) $_POST['sfida'] : 0;
	$user_id  = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
	$stato    = isset( $_POST['stato'] ) ? sanitize_key( $_POST['stato'] ) : '';

	if ( ! $sfida_id || 'gs_sfida' !== get_post_type( $sfida_id ) ) {
		wp_send_json_error( array( 'message' => 'Sfida non valida.' ) );
	}
	if ( ! $user_id || ! get_userdata( $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non valida.' ) );
	}
	if ( ! in_array( $stato, array( 'ammetti', 'escludi', 'reset' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Stato non valido.' ) );
	}

	gs_set_ammissione( $sfida_id, $user_id, $stato );

	// Ricostruisce l'etichetta di stato aggiornata.
	$lv          = gs_get_level( $user_id );
	$livello_min = (int) get_post_meta( $sfida_id, 'gs_livello_min', true );
	$white       = gs_sfida_whitelist( $sfida_id );
	$black       = gs_sfida_blacklist( $sfida_id );

	if ( in_array( $user_id, $black, true ) ) {
		$label = '<span class="gs-stato gs-stato-no">Esclusa</span>';
	} elseif ( in_array( $user_id, $white, true ) ) {
		$label = '<span class="gs-stato gs-stato-si">Ammessa (a tua scelta)</span>';
	} elseif ( $livello_min > 0 && (int) $lv['numero'] >= $livello_min ) {
		$label = '<span class="gs-stato gs-stato-si">Ammessa (per livello)</span>';
	} else {
		$label = '<span class="gs-stato gs-stato-off">Non ammessa</span>';
	}

	wp_send_json_success( array( 'stato_html' => $label ) );
}

// -----------------------------------------------------------------------------
// AJAX: esclusione automatica delle prime N posizioni della classifica da una sfida
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_top_n_salva', 'gs_ajax_fe_top_n_salva' );
function gs_ajax_fe_top_n_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }

	$sfida_id = isset( $_POST['sfida'] ) ? (int) $_POST['sfida'] : 0;
	if ( 'gs_sfida' !== get_post_type( $sfida_id ) ) { wp_send_json_error( array( 'message' => 'Sfida non valida.' ) ); }

	$top_n = isset( $_POST['top_n'] ) ? (int) $_POST['top_n'] : 0;
	if ( $top_n < 0 ) { $top_n = 0; }
	update_post_meta( $sfida_id, 'gs_escludi_top_n', $top_n );

	$msg = $top_n > 0
		? 'Salvato: le prime ' . $top_n . ' posizioni della classifica generale non partecipano a questa sfida.'
		: 'Salvato: nessuna esclusione per classifica su questa sfida.';
	wp_send_json_success( array( 'message' => $msg ) );
}

// -----------------------------------------------------------------------------
// AJAX: attiva/disattiva la Vetrina
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_toggle_vetrina', 'gs_ajax_fe_toggle_vetrina' );

function gs_ajax_fe_toggle_vetrina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}

	$s = gs_settings();
	$nuovo = empty( $s['features']['vetrina_attiva'] );
	$s['features']['vetrina_attiva'] = $nuovo;
	update_option( GS_OPTION, $s );

	wp_send_json_success( array(
		'attiva'  => $nuovo,
		'message' => $nuovo ? 'Vetrina attivata.' : 'Vetrina disattivata.',
	) );
}

// -----------------------------------------------------------------------------
// AJAX: blocca/riattiva la vetrina di UNA singola sfoglina
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_toggle_vetrina_utente', 'gs_ajax_fe_toggle_vetrina_utente' );

function gs_ajax_fe_toggle_vetrina_utente() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) {
		wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
	}

	$uid = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
	if ( ! $uid || ! get_userdata( $uid ) ) {
		wp_send_json_error( array( 'message' => 'Sfoglina non valida.' ) );
	}

	$bloccata_ora = gs_vetrina_bloccata( $uid );
	if ( $bloccata_ora ) {
		delete_user_meta( $uid, 'gs_vetrina_bloccata' );
		$bloccata = false;
	} else {
		update_user_meta( $uid, 'gs_vetrina_bloccata', '1' );
		$bloccata = true;
	}

	wp_send_json_success( array(
		'bloccata'    => $bloccata,
		'stato_html'  => $bloccata
			? '<span class="gs-stato gs-stato-no">Bloccata</span>'
			: '<span class="gs-stato gs-stato-si">Attiva</span>',
		'btn_label'   => $bloccata ? 'Riattiva' : 'Blocca',
		'message'     => $bloccata ? 'Vetrina bloccata per questa sfoglina.' : 'Vetrina riattivata.',
	) );
}

// -----------------------------------------------------------------------------
// AJAX: nomina/revoca un collaboratore (solo veri amministratori)
// -----------------------------------------------------------------------------
add_action( 'wp_ajax_gs_fe_toggle_collab', 'gs_ajax_fe_toggle_collab' );

function gs_ajax_fe_toggle_collab() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	// Solo chi è realmente amministratore può assegnare permessi.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Solo un amministratore può nominare i collaboratori.' ) );
	}

	$uid  = isset( $_POST['user'] ) ? (int) $_POST['user'] : 0;
	$user = $uid ? get_user_by( 'id', $uid ) : null;
	if ( ! $user ) {
		wp_send_json_error( array( 'message' => 'Persona non valida.' ) );
	}
	if ( user_can( $uid, 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Questa persona ha già i permessi di amministratore del sito.' ) );
	}

	$era_collab = user_can( $uid, 'gs_manage_gaming' );
	if ( $era_collab ) {
		$user->remove_cap( 'gs_manage_gaming' );
		$collab = false;
	} else {
		$user->add_cap( 'gs_manage_gaming' );
		$collab = true;
	}
	gs_cache_bump(); // il permesso cambia l'elenco sfogline (esclude i gestori)

	wp_send_json_success( array(
		'collab'     => $collab,
		'stato_html' => $collab
			? '<span class="gs-stato gs-stato-si">Collaboratore</span>'
			: '<span class="gs-stato gs-stato-off">Normale</span>',
		'btn_label'  => $collab ? 'Revoca' : 'Collaboratore',
		'message'    => $collab ? 'Permesso assegnato.' : 'Permesso revocato.',
	) );
}

// -----------------------------------------------------------------------------
// PANNELLO — calibrazione dell'indice laterale (allineamento delle sezioni)
// -----------------------------------------------------------------------------
function gs_pannello_indice() {
	if ( ! gs_can_manage() ) { return; }
	$s = gs_settings();
	$extra = isset( $s['idx_extra'] ) ? (int) $s['idx_extra'] : 0;
	echo gs_box_open( 'Indice laterale — allineamento delle sezioni' );
	echo '<p class="gs-hint">Il pulsante verde "☰ Sezioni" (bordo sinistro dello schermo) apre l\'indice: i pannelli sono raggruppati in 7 macro-aree colorate (Messaggistica, Corsi, Sfide, Contenuti, Iscrizioni/sfogline, Impostazioni, Strumenti), clicca il nome del gruppo per aprirlo e vedere le sue voci. Quando salti a una sezione, il titolo deve restare ben visibile sotto le barre in alto. Se sul tuo schermo la pagina si ferma troppo in alto o troppo in basso, regola qui: <strong>valore negativo = la pagina scende di piu</strong> (il titolo sale), positivo = scende di meno. Grazie a un limite di sicurezza, il titolo non finira mai nascosto sotto le barre. Sposta il cursore, premi Prova per vedere subito il risultato e poi salva.</p>';
	?>
	<form class="gs-form gs-form-idx" onsubmit="return false">
		<p>
			<input type="range" class="gs-idx-extra" min="-200" max="200" step="5" value="<?php echo (int) $extra; ?>" style="width:260px;vertical-align:middle">
			<strong class="gs-idx-val"><?php echo (int) $extra; ?> px</strong>
		</p>
		<p>
			<button class="gs-btn gs-btn-sm gs-idx-prova">Prova</button>
			<button class="gs-btn gs-btn-sm gs-idx-salva">Salva allineamento</button>
			<button class="gs-btn gs-btn-sm gs-btn-ghost gs-idx-diag">Diagnostica</button>
			<span class="gs-idx-msg gs-richiesta-esito"></span>
		</p>
	</form>
	<?php
	echo '<pre class="gs-idx-diag-out" style="display:none;background:#fff;border:1px solid #ddd;padding:10px;white-space:pre-wrap;font-size:12px"></pre>';
	echo '<p class="gs-hint">Versione del codice attualmente caricata dal browser: <strong>' . esc_html( GS_VERSION ) . '</strong>. Se qui non vedi la versione appena installata, il browser o la cache del sito stanno servendo i file vecchi.</p>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_idx_extra_salva', 'gs_ajax_idx_extra_salva' );
function gs_ajax_idx_extra_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$extra = isset( $_POST['extra'] ) ? (int) $_POST['extra'] : 0;
	if ( $extra < -300 ) { $extra = -300; }
	if ( $extra > 300 ) { $extra = 300; }
	$s = gs_settings();
	$s['idx_extra'] = $extra;
	update_option( GS_OPTION, $s );
	wp_send_json_success( array( 'message' => 'Allineamento salvato (' . $extra . ' px).' ) );
}

// -----------------------------------------------------------------------------
// PANNELLO — pagine pubbliche nei menu di navigazione del sito
// -----------------------------------------------------------------------------

/** Le pagine pubbliche gestite qui: chiave opzione => [titolo, shortcode]. */
function gs_menu_pagine_elenco() {
	return array(
		'gs_page_iscrizione'    => array( 'Iscrizione', '[gs_registrazione]' ),
		'gs_page_dicono_di_noi' => array( 'Dicono di Noi', '[gs_dicono_di_noi]' ),
		'gs_page_faq'           => array( 'FAQ - Domande', '[gs_faq]' ),
		'gs_page_novita'        => array( 'Novità', '[gs_novita]' ),
		'gs_page_letture'       => array( 'Le Letture dei Grandi Protagonisti della Cucina', '[gs_letture]' ),
		'gs_page_dashboard'     => array( 'La Mia Sfoglia (accesso/area personale)', '[gs_dashboard]' ),
		'gs_page_registro'      => array( 'Registro Ufficiale degli Allievi', '[gs_registro_ufficiale]' ),
		'gs_page_traguardi'     => array( 'Ultimi Traguardi', '[gs_ultimi_traguardi]' ),
	);
}

/** True se la pagina $pid ha già una voce in quel menu. */
function gs_menu_pagina_presente( $menu_id, $pid ) {
	if ( ! $pid ) { return false; }
	foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( 'post_type' === $item->type && (int) $item->object_id === (int) $pid ) { return true; }
	}
	return false;
}

/** ID della voce di menu che punta a $pid dentro $menu_id, o 0 se non c'è. */
function gs_menu_pagina_item_id( $menu_id, $pid ) {
	foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( 'post_type' === $item->type && (int) $item->object_id === (int) $pid ) { return (int) $item->ID; }
	}
	return 0;
}

function gs_pannello_menu_pagine() {
	if ( ! gs_can_manage() ) { return; }

	$pagine = gs_menu_pagine_elenco();

	echo gs_box_open( 'Pagine pubbliche nel menu del sito', '', 'gs-box-menu-pagine' );
	echo '<p class="gs-hint">Le pagine <strong>Iscrizione</strong> (dove ci si registra come sfoglina), <strong>Dicono di Noi</strong> (le testimonianze di chi ha fatto un percorso all\'Accademia), <strong>FAQ - Domande</strong> (le domande frequenti), <strong>Novità</strong> (gli ultimi annunci di chi gestisce il portale), <strong>Le Letture dei Grandi Protagonisti della Cucina</strong> e <strong>La Mia Sfoglia</strong> (mostra l\'invito ad accedere a chi non è collegata, e la propria pagina a chi lo è già — utile come punto di accesso nel menu, visto che la barra di WordPress in alto a destra la vede solo chi gestisce il sito) esistono già. Spunta in quali menu del sito deve comparire il link di ciascuna: puoi spuntarne più di uno alla volta, oppure togliere una spunta per far sparire il link solo da quel menu (la pagina resta online, cambia solo dove compare il collegamento). Se manca ancora, la pagina viene creata al primo salvataggio.</p>';

	// Stato attuale delle pagine.
	echo '<ul style="margin:6px 0 10px 18px">';
	foreach ( $pagine as $opt => $dati ) {
		$pid = (int) get_option( $opt );
		$ok  = $pid && get_post( $pid ) && 'trash' !== get_post_status( $pid );
		echo '<li>' . esc_html( $dati[0] ) . ': ' . ( $ok
			? '<a href="' . esc_url( get_permalink( $pid ) ) . '" target="_blank">pagina presente</a>'
			: '<em>pagina mancante (verrà creata al primo salvataggio)</em>' ) . '</li>';
	}
	echo '</ul>';

	// Menu disponibili.
	$menus = wp_get_nav_menus();
	if ( ! $menus ) {
		echo '<p class="gs-hint">Non ho trovato nessun menu di navigazione: crealo prima in Aspetto &rarr; Menu.</p>' . gs_box_close();
		return;
	}

	echo '<form class="gs-form gs-form-menu-pagine-matrice" onsubmit="return false">';
	echo '<div style="overflow-x:auto"><table class="gs-table"><thead><tr><th>Pagina</th>';
	foreach ( $menus as $m ) { echo '<th>' . esc_html( $m->name ) . '</th>'; }
	echo '</tr></thead><tbody>';
	foreach ( $pagine as $opt => $dati ) {
		$pid = (int) get_option( $opt );
		echo '<tr><td>' . esc_html( $dati[0] ) . '</td>';
		foreach ( $menus as $m ) {
			$presente = gs_menu_pagina_presente( $m->term_id, $pid );
			echo '<td style="text-align:center"><input type="checkbox" class="gs-menu-check" data-pagina="' . esc_attr( $opt ) . '" data-menu="' . (int) $m->term_id . '" ' . ( $presente ? 'checked' : '' ) . '></td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table></div>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-menu-matrice-salva">Salva</button> <span class="gs-menu-matrice-msg gs-richiesta-esito"></span></p>';
	echo '</form>';
	echo gs_box_close();
}

add_action( 'wp_ajax_gs_menu_matrice_salva', 'gs_ajax_menu_matrice_salva' );
function gs_ajax_menu_matrice_salva() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato: serve il permesso di modificare i menu.' ) );
	}
	$pagine = gs_menu_pagine_elenco();
	$stati  = isset( $_POST['stati'] ) && is_array( $_POST['stati'] ) ? $_POST['stati'] : array();

	$aggiunte = array();
	$rimosse  = array();
	foreach ( $pagine as $opt => $dati ) {
		if ( ! isset( $stati[ $opt ] ) || ! is_array( $stati[ $opt ] ) ) { continue; }
		list( $titolo, $shortcode ) = $dati;
		$pid = (int) get_option( $opt );

		foreach ( $stati[ $opt ] as $menu_id => $voluto ) {
			$menu_id = (int) $menu_id;
			$voluto  = ! empty( $voluto ) && '0' !== $voluto;
			$menu    = $menu_id ? wp_get_nav_menu_object( $menu_id ) : false;
			if ( ! $menu ) { continue; }

			if ( $voluto ) {
				// Crea la pagina se manca ancora, solo quando serve davvero.
				if ( ! $pid || ! get_post( $pid ) || 'trash' === get_post_status( $pid ) ) {
					$pid = wp_insert_post( array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => $titolo,
						'post_content' => $shortcode,
					) );
					if ( is_wp_error( $pid ) || ! $pid ) { continue; }
					update_option( $opt, $pid );
				}
				if ( gs_menu_pagina_presente( $menu_id, $pid ) ) { continue; }
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $titolo,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => (int) $pid,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				) );
				$aggiunte[] = $titolo . ' → ' . $menu->name;
			} else {
				$item_id = $pid ? gs_menu_pagina_item_id( $menu_id, $pid ) : 0;
				if ( ! $item_id ) { continue; }
				wp_delete_post( $item_id, true );
				$rimosse[] = $titolo . ' → ' . $menu->name;
			}
		}
	}

	$msg = array();
	if ( $aggiunte ) { $msg[] = 'Aggiunte: ' . implode( ', ', $aggiunte ) . '.'; }
	if ( $rimosse ) { $msg[] = 'Rimosse: ' . implode( ', ', $rimosse ) . '.'; }
	if ( ! $msg ) { $msg[] = 'Nessuna modifica.'; }
	wp_send_json_success( array( 'message' => implode( ' ', $msg ) ) );
}
