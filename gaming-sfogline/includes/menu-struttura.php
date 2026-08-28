<?php
/**
 * menu-struttura.php — "Applica la struttura del menu": un solo pulsante
 * che riorganizza il menu principale del sito secondo la proposta
 * approvata da Ennio (bacheca del 01/08/2026), creando le voci-contenitore
 * (L'Accademia, Corsi, Community, Contenuti) e spostando sotto di loro le
 * voci già esistenti nel menu.
 *
 * Regola di sicurezza: ogni voce che non è una pagina del plugin viene
 * trovata cercando il suo TITOLO tra quelle già presenti in un menu
 * qualsiasi del sito — mai creando un collegamento verso un indirizzo
 * indovinato da qui. Se un titolo non si trova, viene segnalato e
 * saltato: nessun link nuovo verso una pagina sbagliata.
 *
 * Non tocca mai il menu in alto (Home / Appello per il Governo /
 * Disciplinare / Scrivici): se una voce cercata vive lì, non viene
 * spostata via — si crea invece una nuova voce nel menu di destinazione
 * che punta alla stessa pagina, lasciando l'originale dov'era.
 *
 * "Home" e "Sostieni l'Accademia" restano fuori da questo strumento:
 * nella proposta rimangono voci di primo livello, senza bisogno di
 * essere toccate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * La struttura di destinazione: gruppo => elenco di voci, nell'ordine
 * desiderato. Ogni voce è:
 *  - una stringa: il titolo con cui cercare una voce già esistente in un
 *    menu del sito (qualunque pagina sia, non gestita da qui);
 *  - un array ['pagina' => opzione gs_page_*, 'titolo' => ..., 'shortcode' => ...]
 *    per le pagine del plugin, create al volo se mancano ancora (stessa
 *    logica di gs_menu_pagine_elenco()).
 */
function gs_menu_struttura_definizione() {
	return array(
		"L'Accademia" => array(
			'Rina Poletti',
			'Storie Cultura e Pasta',
			'Dicono di Noi',
			array( 'pagina' => 'gs_page_registro', 'titolo' => 'Registro Ufficiale degli Allievi', 'shortcode' => '[gs_registro_ufficiale]' ),
			array( 'pagina' => 'gs_page_traguardi', 'titolo' => 'Ultimi Traguardi', 'shortcode' => '[gs_ultimi_traguardi]' ),
		),
		'Corsi' => array(
			'Calendario Corsi',
			'FAQ',
			'Iscrizione',
		),
		'Community' => array(
			'Iscrizione',
			array( 'pagina' => 'gs_page_dashboard', 'titolo' => 'La Mia Sfoglia', 'shortcode' => '[gs_dashboard]' ),
			'Le Sfogline',
			'Le Letture dei Grandi Protagonisti della Cucina',
			'I Compleanni di Oggi',
			"L'Esperto Risponde",
			'Eventi',
		),
		'Contenuti' => array(
			'Eventi',
			'Ricette',
			'Lentium Notizie',
			'Libri',
			array( 'pagina' => 'gs_page_novita', 'titolo' => 'Novità', 'shortcode' => '[gs_novita]' ),
		),
	);
}

/** Cerca una voce per titolo in QUALSIASI menu del sito. Ritorna ['item'=>oggetto, 'menu_id'=>int] o null. */
function gs_menu_struttura_trova_voce_per_titolo( $titolo ) {
	$titolo = trim( wp_strip_all_tags( (string) $titolo ) );
	if ( '' === $titolo ) {
		return null;
	}
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			if ( 0 === strcasecmp( trim( wp_strip_all_tags( (string) $item->title ) ), $titolo ) ) {
				return array( 'item' => $item, 'menu_id' => (int) $menu->term_id );
			}
		}
	}
	return null;
}

/** Trova (o crea, come voce "custom" senza pagina) il gruppo-contenitore col titolo dato, di primo livello nel menu indicato. */
function gs_menu_struttura_trova_o_crea_gruppo( $menu_id, $titolo ) {
	foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
		$primo_livello = empty( $item->menu_item_parent );
		if ( $primo_livello && 0 === strcasecmp( trim( (string) $item->title ), $titolo ) ) {
			return (int) $item->ID;
		}
	}
	return wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'  => $titolo,
		'menu-item-url'    => '#',
		'menu-item-type'   => 'custom',
		'menu-item-status' => 'publish',
	) );
}

/** True se sotto $parent_id, in $menu_id, esiste già una voce che punta esattamente a quella destinazione (evita doppioni se lo strumento viene rilanciato). */
function gs_menu_struttura_figlio_esiste( $menu_id, $parent_id, $sorgente ) {
	foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( (int) $item->menu_item_parent !== (int) $parent_id ) {
			continue;
		}
		if ( 'custom' === $sorgente->type ) {
			if ( 'custom' === $item->type && trim( (string) $item->url ) === trim( (string) $sorgente->url ) ) {
				return true;
			}
		} elseif ( $item->type === $sorgente->type && (int) $item->object_id === (int) $sorgente->object_id && (int) $sorgente->object_id > 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * Applica la struttura al menu $menu_id. Ritorna un array riassuntivo
 * (spostate/create/non_trovate) oppure un WP_Error se il menu non esiste.
 */
function gs_menu_struttura_applica( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( ! wp_get_nav_menu_object( $menu_id ) ) {
		return new WP_Error( 'gs_menu', 'Menu non valido.' );
	}

	$spostate    = array();
	$create      = array();
	$non_trovate = array();
	$gia_posta   = array(); // titolo => true, dopo la prima collocazione: le ripetizioni dello stesso titolo diventano copie, non spostano di nuovo l'originale

	foreach ( gs_menu_struttura_definizione() as $gruppo => $voci ) {
		$parent_id = gs_menu_struttura_trova_o_crea_gruppo( $menu_id, $gruppo );
		if ( is_wp_error( $parent_id ) || ! $parent_id ) {
			continue;
		}
		$pos = 1;
		foreach ( $voci as $voce ) {

			// --- Pagina gestita dal plugin: crea se manca, come le altre pagine di questo pannello. ---
			if ( is_array( $voce ) ) {
				$pid = (int) get_option( $voce['pagina'] );
				if ( ! $pid || ! get_post( $pid ) || 'trash' === get_post_status( $pid ) ) {
					$pid = wp_insert_post( array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => $voce['titolo'],
						'post_content' => $voce['shortcode'],
					) );
					if ( is_wp_error( $pid ) || ! $pid ) {
						$non_trovate[] = $voce['titolo'] . ' (errore nel crearla)';
						continue;
					}
					update_option( $voce['pagina'], $pid );
				}
				$sorgente_finto = (object) array( 'type' => 'post_type', 'object_id' => $pid, 'url' => '' );
				if ( gs_menu_struttura_figlio_esiste( $menu_id, $parent_id, $sorgente_finto ) ) {
					$pos++;
					continue; // già al posto giusto, richiamare lo strumento non duplica nulla
				}
				$item_id = gs_menu_pagina_item_id( $menu_id, $pid );
				wp_update_nav_menu_item( $menu_id, $item_id, array(
					'menu-item-title'     => $voce['titolo'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pid,
					'menu-item-type'      => 'post_type',
					'menu-item-parent-id' => $parent_id,
					'menu-item-position'  => $pos,
					'menu-item-status'    => 'publish',
				) );
				$spostate[] = $voce['titolo'] . ' → ' . $gruppo;
				$pos++;
				continue;
			}

			// --- Voce già esistente da qualche parte sul sito: mai indovinata, sempre cercata per titolo. ---
			$trovato = gs_menu_struttura_trova_voce_per_titolo( $voce );
			if ( ! $trovato ) {
				$non_trovate[] = $voce . ' (per "' . $gruppo . '")';
				continue;
			}
			$sorgente      = $trovato['item'];
			$menu_sorgente = $trovato['menu_id'];

			if ( gs_menu_struttura_figlio_esiste( $menu_id, $parent_id, $sorgente ) ) {
				$pos++;
				continue; // già al posto giusto
			}

			if ( ! isset( $gia_posta[ $voce ] ) && $menu_sorgente === $menu_id ) {
				// Prima volta, stesso menu: sposta la voce che già c'è (aggiorna genitore e posizione, non la duplica).
				wp_update_nav_menu_item( $menu_id, $sorgente->ID, array(
					'menu-item-title'     => $sorgente->title,
					'menu-item-object'    => $sorgente->object,
					'menu-item-object-id' => $sorgente->object_id,
					'menu-item-type'      => $sorgente->type,
					'menu-item-url'       => $sorgente->url,
					'menu-item-parent-id' => $parent_id,
					'menu-item-position'  => $pos,
					'menu-item-status'    => 'publish',
				) );
				$spostate[] = $voce . ' → ' . $gruppo;
			} else {
				// La voce vive in un altro menu (es. quello in alto) oppure è una ripetizione
				// dello stesso titolo già collocato altrove: si crea una nuova voce che punta
				// alla stessa pagina, senza mai toccare l'originale.
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $sorgente->title,
					'menu-item-object'    => $sorgente->object,
					'menu-item-object-id' => $sorgente->object_id,
					'menu-item-type'      => $sorgente->type,
					'menu-item-url'       => $sorgente->url,
					'menu-item-parent-id' => $parent_id,
					'menu-item-position'  => $pos,
					'menu-item-status'    => 'publish',
				) );
				$create[] = $voce . ' → ' . $gruppo . ( $menu_sorgente !== $menu_id ? ' (copiata da un altro menu, l\'originale resta dov\'era)' : ' (duplicata)' );
			}
			$gia_posta[ $voce ] = true;
			$pos++;
		}
	}

	return array( 'spostate' => $spostate, 'create' => $create, 'non_trovate' => $non_trovate );
}

// -----------------------------------------------------------------------------
// Correzioni puntuali trovate con un controllo del menu (2026-08-02):
//  1) una voce etichettata per errore "miao", che punta davvero alla pagina
//     di Rina Poletti — resta lì, si corregge solo l'etichetta;
//  2) una voce "FAQ" (dentro "Contenuti") che punta a una pagina vuota e
//     abbandonata (/informazioni-faq/) invece delle vere FAQ del plugin;
//  3) la voce "Dicono di noi" (minuscolo) che punta alla vecchia pagina
//     sede/B&B, ormai superata da "La Nostra Sede" e da cestinare: la voce
//     di menu si trasforma per puntare lì, invece di sparire.
// Stessa regola di sicurezza dello strumento sopra: agisce solo su una
// corrispondenza precisa, altrimenti segnala e non tocca nulla.
// -----------------------------------------------------------------------------
define( 'GS_MENU_ID_VECCHIA_PAGINA_SEDE', 64191 ); // "Dicono di noi", cestinata: contenuto sede/B&B superato da "La Nostra Sede"

function gs_menu_correzioni_applica( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( ! wp_get_nav_menu_object( $menu_id ) ) {
		return new WP_Error( 'gs_menu', 'Menu non valido.' );
	}

	$fatte     = array();
	$non_fatte = array();

	foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
		$titolo = trim( wp_strip_all_tags( (string) $item->title ) );

		// 1) "miao" → "Rina Poletti"
		if ( 0 === strcasecmp( $titolo, 'miao' ) && false !== strpos( (string) $item->url, '/rina-poletti/' ) ) {
			wp_update_nav_menu_item( $menu_id, $item->ID, array(
				'menu-item-title'     => 'Rina Poletti',
				'menu-item-url'       => $item->url,
				'menu-item-type'      => $item->type,
				'menu-item-object'    => $item->object,
				'menu-item-object-id' => $item->object_id,
				'menu-item-parent-id' => $item->menu_item_parent,
				'menu-item-position'  => $item->menu_order,
				'menu-item-status'    => 'publish',
			) );
			$fatte[] = '"miao" rinominata in "Rina Poletti"';
		}

		// 2) "FAQ" verso la pagina vuota /informazioni-faq/ → ripuntata alle vere FAQ
		if ( 0 === strcasecmp( $titolo, 'FAQ' ) && false !== strpos( (string) $item->url, '/informazioni-faq/' ) ) {
			$vera     = gs_menu_struttura_trova_voce_per_titolo( 'FAQ - Domande' );
			$url_vero = '';
			if ( $vera ) {
				$url_vero = $vera['item']->url;
				// Le voci che puntano a una Pagina spesso non hanno un URL salvato
				// a parte (WordPress lo calcola al volo dal permalink): lo calcolo qui.
				if ( ! $url_vero && 'post_type' === $vera['item']->type && $vera['item']->object_id ) {
					$url_vero = get_permalink( $vera['item']->object_id );
				}
			}
			if ( ! $url_vero && function_exists( 'gs_pagina_url' ) && get_option( 'gs_page_faq' ) ) {
				$url_vero = gs_pagina_url( 'gs_page_faq' );
			}
			if ( $url_vero ) {
				wp_update_nav_menu_item( $menu_id, $item->ID, array(
					'menu-item-title'     => $item->title,
					'menu-item-url'       => $url_vero,
					'menu-item-type'      => 'custom',
					'menu-item-parent-id' => $item->menu_item_parent,
					'menu-item-position'  => $item->menu_order,
					'menu-item-status'    => 'publish',
				) );
				$fatte[] = '"FAQ" ripuntata alle vere FAQ (' . $url_vero . ')';
			} else {
				$non_fatte[] = '"FAQ": non ho trovato la pagina vera delle FAQ, non tocco nulla.';
			}
		}

		// 3) "Dicono di noi" (minuscolo) verso la vecchia pagina sede/B&B → diventa "La Nostra Sede"
		if ( 0 === strcasecmp( $titolo, 'Dicono di noi' ) && 'post_type' === $item->type && (int) $item->object_id === GS_MENU_ID_VECCHIA_PAGINA_SEDE ) {
			$sede = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'la-nostra-sede' ) : null;
			if ( $sede ) {
				wp_update_nav_menu_item( $menu_id, $item->ID, array(
					'menu-item-title'     => 'La Nostra Sede',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $sede->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-parent-id' => $item->menu_item_parent,
					'menu-item-position'  => $item->menu_order,
					'menu-item-status'    => 'publish',
				) );
				$fatte[] = '"Dicono di noi" (verso la vecchia pagina sede/B&B) trasformata in "La Nostra Sede"';
			} else {
				$non_fatte[] = '"Dicono di noi": non ho trovato la pagina "La Nostra Sede" (indirizzo /la-nostra-sede/) per ripuntarci, non tocco nulla.';
			}
		}
	}

	if ( ! $fatte && ! $non_fatte ) {
		$non_fatte[] = 'Nessuna delle voci da correggere ("miao", "FAQ" verso /informazioni-faq/, "Dicono di noi" verso la vecchia pagina sede) è stata trovata in questo menu: forse sono già state sistemate, o vivono in un altro menu.';
	}

	return array( 'fatte' => $fatte, 'non_fatte' => $non_fatte );
}

function gs_pannello_menu_struttura() {
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	echo gs_box_open( '🧭 Applica la struttura del menu proposta' );
	echo gs_sezione_aiuto( 'Riorganizza in un clic il menu scelto secondo la struttura concordata: crea le voci-contenitore L\'Accademia, Corsi, Community e Contenuti, e sposta sotto di loro le voci già esistenti nel menu (cercandole per titolo, mai indovinando un indirizzo). "Home" e "Sostieni l\'Accademia" restano dove sono, come voci singole. Non tocca mai il menu in alto (Home / Appello per il Governo / Disciplinare / Scrivici): se una voce che serve — come "L\'Esperto Risponde" — vive lì, ne crea una copia nel menu scelto, lasciando l\'originale al suo posto. Si può rilanciare più volte senza creare doppioni: quello che è già a posto viene lasciato com\'è. <strong>Va scelto un menu solo per volta</strong>: se il sito mostra più di un menu contemporaneamente (es. una barra sottile in alto e il menu principale sotto il logo, come nel tema Newspaper), applicarla al menu sbagliato crea gli stessi gruppi anche lì — è già successo il 03/08/2026, corretto con lo strumento di pulizia qui sotto. "Correggi voci trovate" sistema invece tre errori individuati con un controllo del 2026-08-02: una voce etichettata per errore "miao" (punta davvero alla pagina di Rina Poletti); una voce "FAQ" che punta a una pagina vuota e abbandonata invece delle vere FAQ; e la voce "Dicono di noi" (minuscolo) che punta alla vecchia pagina su sede/B&B — da cestinare a parte in Pagine — trasformata qui in "La Nostra Sede", la pagina nuova sullo stesso argomento. Solo questa seconda operazione (le tre correzioni puntuali, mai la struttura completa) parte anche da sola, una volta sola, su tutti i menu del sito.' );

	$auto = get_option( 'gs_menu_auto_report' );
	if ( is_array( $auto ) && ! empty( $auto['quando'] ) ) {
		echo '<div style="background:var(--gs-uovo);padding:10px 12px;border-radius:6px;margin-bottom:14px;font-size:13px">';
		echo '<strong>Ultima esecuzione automatica:</strong> ' . esc_html( $auto['quando'] ) . '<br>';
		if ( ! empty( $auto['righe'] ) ) {
			echo '<ul style="margin:6px 0 0;padding-left:18px">';
			foreach ( $auto['righe'] as $riga ) { echo '<li>' . esc_html( $riga ) . '</li>'; }
			echo '</ul>';
		} else {
			echo 'Nessuna modifica necessaria: era già tutto a posto.';
		}
		echo '</div>';
	}

	$menus = wp_get_nav_menus();
	if ( ! $menus ) {
		echo '<p class="gs-hint">Nessun menu trovato: crealo prima in Aspetto → Menu.</p>' . gs_box_close();
		return;
	}

	echo '<form class="gs-form gs-form-menu-struttura" onsubmit="return false">';
	echo '<p><label>Menu su cui applicare la struttura<br><select name="menu_id" style="min-width:220px">';
	foreach ( $menus as $m ) {
		echo '<option value="' . (int) $m->term_id . '">' . esc_html( $m->name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-menu-struttura-applica">Applica questa struttura</button> <span class="gs-menu-struttura-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	echo '<form class="gs-form gs-form-menu-correzioni" onsubmit="return false" style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--gs-bordo,#d9cba6)">';
	echo '<p><label>Menu su cui cercare le voci da correggere<br><select name="menu_id" style="min-width:220px">';
	foreach ( $menus as $m ) {
		echo '<option value="' . (int) $m->term_id . '">' . esc_html( $m->name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-menu-correzioni-applica">🔧 Correggi voci trovate ("miao", FAQ morta, Dicono di noi)</button> <span class="gs-menu-correzioni-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	echo '<form class="gs-form gs-form-menu-rimuovi-gruppi" onsubmit="return false" style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--gs-bordo,#d9cba6)">';
	echo '<p><strong>Riparazione:</strong> togli L\'Accademia/Corsi/Community/Contenuti da un menu su cui sono finiti per sbaglio (mai le pagine a cui puntano, solo le voci di menu).</p>';
	echo '<p><label>Menu da ripulire<br><select name="menu_id" style="min-width:220px">';
	foreach ( $menus as $m ) {
		echo '<option value="' . (int) $m->term_id . '">' . esc_html( $m->name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-menu-rimuovi-gruppi">🧹 Rimuovi i gruppi da questo menu</button> <span class="gs-menu-rimuovi-gruppi-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	echo '<form class="gs-form gs-form-menu-diagnostica" onsubmit="return false" style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--gs-bordo,#d9cba6)">';
	echo '<p><strong>Diagnostica:</strong> se "Rimuovi i gruppi" dice che non c\'è nulla da togliere ma tu vedi i gruppi sul sito, guarda qui esattamente cosa vede il codice in quel menu.</p>';
	echo '<p><label>Menu da esaminare<br><select name="menu_id" style="min-width:220px">';
	foreach ( $menus as $m ) {
		echo '<option value="' . (int) $m->term_id . '">' . esc_html( $m->name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-ghost gs-menu-diagnostica">🔍 Mostra le voci di primo livello</button></p>';
	echo '<p class="gs-menu-diagnostica-msg gs-richiesta-esito" style="word-break:break-word"></p>';
	echo '</form>';

	echo '<form class="gs-form gs-form-menu-ripara-esperto" onsubmit="return false" style="margin-top:14px;padding-top:14px;border-top:1px dashed var(--gs-bordo,#d9cba6)">';
	echo '<p><strong>Riparazione mirata:</strong> "L\'Esperto Risponde" nel menu in alto aveva il titolo scritto in modo anomalo (apostrofo salvato come codice) e un tentativo di correggerlo a mano l\'ha spostata per sbaglio sotto un\'altra voce. Un clic la riporta di primo livello e corregge il titolo, qualunque delle due cose sia ancora sbagliata.</p>';
	echo '<p><label>Menu da riparare<br><select name="menu_id" style="min-width:220px">';
	foreach ( $menus as $m ) {
		echo '<option value="' . (int) $m->term_id . '">' . esc_html( $m->name ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-menu-ripara-esperto">🔧 Ripara "L\'Esperto Risponde"</button> <span class="gs-menu-ripara-esperto-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_menu_struttura_applica', 'gs_ajax_menu_struttura_applica' );
function gs_ajax_menu_struttura_applica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato: serve il permesso di modificare i menu.' ) );
	}
	$menu_id = isset( $_POST['menu_id'] ) ? (int) $_POST['menu_id'] : 0;
	$esito   = gs_menu_struttura_applica( $menu_id );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}
	$parti = array();
	if ( $esito['spostate'] ) { $parti[] = 'Spostate: ' . implode( ', ', $esito['spostate'] ) . '.'; }
	if ( $esito['create'] ) { $parti[] = 'Aggiunte: ' . implode( ', ', $esito['create'] ) . '.'; }
	if ( $esito['non_trovate'] ) { $parti[] = 'Non trovate (controlla il titolo esatto nel menu): ' . implode( ', ', $esito['non_trovate'] ) . '.'; }
	if ( ! $parti ) { $parti[] = 'Struttura già applicata, nessuna modifica necessaria.'; }
	wp_send_json_success( array( 'message' => implode( ' ', $parti ) ) );
}

add_action( 'wp_ajax_gs_menu_correzioni_applica', 'gs_ajax_menu_correzioni_applica' );
function gs_ajax_menu_correzioni_applica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato: serve il permesso di modificare i menu.' ) );
	}
	$menu_id = isset( $_POST['menu_id'] ) ? (int) $_POST['menu_id'] : 0;
	$esito   = gs_menu_correzioni_applica( $menu_id );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}
	$parti = array();
	if ( $esito['fatte'] ) { $parti[] = implode( ' ', $esito['fatte'] ) . '.'; }
	if ( $esito['non_fatte'] ) { $parti[] = implode( ' ', $esito['non_fatte'] ); }
	wp_send_json_success( array( 'message' => implode( ' ', $parti ) ) );
}

// -----------------------------------------------------------------------------
// Esecuzione automatica, una sola volta: applica SOLO le correzioni puntuali
// (miao, FAQ morta, Dicono di noi — voci singole cercate per corrispondenza
// esatta) a tutti i menu del sito, senza bisogno che qualcuno prema il
// pulsante sopra.
//
// La struttura completa (creare L'Accademia/Corsi/Community/Contenuti e
// spostarci sotto le voci esistenti) NON parte più da sola: un sito può
// avere più di un menu mostrato contemporaneamente (es. il tema "Newspaper"
// mostra una barra sottile in alto E il menu principale sotto il logo, due
// menu WordPress distinti) — applicarla in automatico "a tutti i menu" ha
// creato la struttura anche nella barra in alto, che doveva restare con le
// sue poche voci originali (Home / Appello per il Governo / Disciplinare /
// Scrivici), duplicando i gruppi su entrambe le barre (bug del 03/08/2026,
// versione 3.165.0). Resta disponibile solo a mano, un menu alla volta,
// dal modulo qui sotto — così chi gestisce il sito sceglie consapevolmente
// su quale menu applicarla.
//
// Aumentare GS_MENU_AUTO_VERSIONE quando le correzioni puntuali cambiano
// ancora, per far ripartire l'automatismo una volta di più.
// -----------------------------------------------------------------------------
define( 'GS_MENU_AUTO_VERSIONE', '3165-solo-correzioni' );

add_action( 'init', 'gs_menu_correzioni_auto', 20 );
function gs_menu_correzioni_auto() {
	if ( get_option( 'gs_menu_auto_fatta' ) === GS_MENU_AUTO_VERSIONE ) {
		return;
	}
	if ( ! function_exists( 'wp_get_nav_menus' ) ) {
		return;
	}

	$report = array();
	foreach ( wp_get_nav_menus() as $menu ) {
		$r2 = gs_menu_correzioni_applica( $menu->term_id );
		if ( ! is_wp_error( $r2 ) && $r2['fatte'] ) {
			$report[] = $menu->name . ': ' . implode( '; ', $r2['fatte'] ) . '.';
		}
	}

	update_option( 'gs_menu_auto_fatta', GS_MENU_AUTO_VERSIONE );
	update_option( 'gs_menu_auto_report', array( 'quando' => current_time( 'mysql' ), 'righe' => $report ) );
}

// -----------------------------------------------------------------------------
// Riparazione: rimuove da UN menu le voci-contenitore L'Accademia / Corsi /
// Community / Contenuti (e tutti i loro figli, a qualunque livello) create
// per errore da "Applica questa struttura" — mai le pagine a cui puntavano,
// solo le voci di menu. Serve a pulire un menu su cui la struttura è finita
// per sbaglio (es. la barra in alto del tema Newspaper).
// -----------------------------------------------------------------------------

/** Elimina ricorsivamente tutti i discendenti di $parent_id dentro $items (voci di menu, non le pagine). */
function gs_menu_struttura_elimina_discendenti( $items, $parent_id ) {
	foreach ( $items as $it ) {
		if ( (int) $it->menu_item_parent === (int) $parent_id ) {
			gs_menu_struttura_elimina_discendenti( $items, $it->ID );
			wp_delete_post( $it->ID, true );
		}
	}
}

/** Rimuove i gruppi L'Accademia/Corsi/Community/Contenuti (e figli) da $menu_id. Ritorna i titoli rimossi, o un WP_Error. */
function gs_menu_struttura_rimuovi_gruppi( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( ! wp_get_nav_menu_object( $menu_id ) ) {
		return new WP_Error( 'gs_menu', 'Menu non valido.' );
	}

	$gruppi  = array_keys( gs_menu_struttura_definizione() );
	$items   = wp_get_nav_menu_items( $menu_id );
	$rimossi = array();

	foreach ( $items as $item ) {
		if ( ! empty( $item->menu_item_parent ) ) {
			continue; // solo voci di primo livello: un gruppo non può essere figlio di un altro
		}
		$titolo = trim( (string) $item->title );
		$match  = false;
		foreach ( $gruppi as $g ) {
			if ( 0 === strcasecmp( $titolo, $g ) ) { $match = true; break; }
		}
		if ( ! $match ) {
			continue;
		}
		gs_menu_struttura_elimina_discendenti( $items, $item->ID );
		wp_delete_post( $item->ID, true );
		$rimossi[] = $titolo;
	}

	return array( 'rimossi' => $rimossi );
}

add_action( 'wp_ajax_gs_menu_struttura_rimuovi_gruppi', 'gs_ajax_menu_struttura_rimuovi_gruppi' );
function gs_ajax_menu_struttura_rimuovi_gruppi() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato: serve il permesso di modificare i menu.' ) );
	}
	$menu_id = isset( $_POST['menu_id'] ) ? (int) $_POST['menu_id'] : 0;
	$esito   = gs_menu_struttura_rimuovi_gruppi( $menu_id );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}
	if ( $esito['rimossi'] ) {
		$msg = 'Rimossi da questo menu: ' . implode( ', ', $esito['rimossi'] ) . '.';
	} else {
		// Nessuna corrispondenza: mostra subito, nella STESSA richiesta, cosa
		// vede davvero il codice in questo menu — niente più rischio di
		// controllare un menu con la Diagnostica e ripulirne un altro per
		// sbaglio (due click separati, due tendine separate).
		$menu_oggetto = wp_get_nav_menu_object( $menu_id );
		$diag         = gs_menu_struttura_diagnostica( $menu_id );
		$parti_diag   = array();
		if ( ! is_wp_error( $diag ) ) {
			foreach ( $diag['righe'] as $r ) { $parti_diag[] = '«' . $r['titolo'] . '»'; }
		}
		$msg = 'Nessuno dei gruppi L\'Accademia/Corsi/Community/Contenuti trovato nel menu "'
			. ( $menu_oggetto ? $menu_oggetto->name : '?' ) . '" (ID ' . $menu_id . '). '
			. 'Voci di primo livello viste in questo menu: ' . ( $parti_diag ? implode( ' — ', $parti_diag ) : 'nessuna' ) . '.';
	}
	wp_send_json_success( array( 'message' => $msg ) );
}

// -----------------------------------------------------------------------------
// Diagnostica: mostra esattamente cosa vede il codice nelle voci di primo
// livello di un menu (titolo esatto, tipo, lunghezza in byte) — serve a
// scoprire perché una corrispondenza per titolo non scatta (es. un
// apostrofo "intelligente" al posto di quello semplice, o spazi nascosti),
// senza dover indovinare da qui.
// -----------------------------------------------------------------------------
function gs_menu_struttura_diagnostica( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( ! wp_get_nav_menu_object( $menu_id ) ) {
		return new WP_Error( 'gs_menu', 'Menu non valido.' );
	}
	$items = wp_get_nav_menu_items( $menu_id );
	$righe = array();
	foreach ( $items as $item ) {
		if ( ! empty( $item->menu_item_parent ) ) {
			continue; // solo voci di primo livello: sono quelle su cui "Rimuovi i gruppi" cerca corrispondenza
		}
		$righe[] = array(
			'id'     => (int) $item->ID,
			'titolo' => (string) $item->title,
			'tipo'   => (string) $item->type,
			'byte'   => strlen( (string) $item->title ),
		);
	}
	return array( 'righe' => $righe, 'totale_voci' => count( $items ) );
}

add_action( 'wp_ajax_gs_menu_struttura_diagnostica', 'gs_ajax_menu_struttura_diagnostica' );
function gs_ajax_menu_struttura_diagnostica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato: serve il permesso di modificare i menu.' ) );
	}
	$menu_id = isset( $_POST['menu_id'] ) ? (int) $_POST['menu_id'] : 0;
	$esito   = gs_menu_struttura_diagnostica( $menu_id );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}
	if ( ! $esito['righe'] ) {
		wp_send_json_success( array( 'message' => 'Questo menu non ha nessuna voce di primo livello (' . (int) $esito['totale_voci'] . ' voci totali, tutte figlie di qualcos\'altro).' ) );
	}
	$parti = array();
	foreach ( $esito['righe'] as $r ) {
		$parti[] = '«' . $r['titolo'] . '» (tipo: ' . $r['tipo'] . ', ' . $r['byte'] . ' byte)';
	}
	wp_send_json_success( array( 'message' => 'Voci di primo livello (' . count( $esito['righe'] ) . ' su ' . (int) $esito['totale_voci'] . ' totali): ' . implode( ' — ', $parti ) ) );
}

// -----------------------------------------------------------------------------
// Riparazione mirata: "L'Esperto Risponde" nel menu in alto aveva il titolo
// salvato con l'entità HTML letterale "&#8217;" invece dell'apostrofo vero
// (probabile incidente di una modifica passata), e un tentativo di
// correggerlo a mano nell'editor dei menu l'ha invece spostata per sbaglio
// come sotto-voce di un'altra — errore facile da rifare trascinando a mano.
// Un solo pulsante: la riporta di primo livello E corregge il titolo,
// qualunque delle due cose sia ancora sbagliata (richiamabile più volte
// senza rischi, se è già a posto la risalva così com'è).
// -----------------------------------------------------------------------------
function gs_menu_struttura_ripara_esperto( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( ! wp_get_nav_menu_object( $menu_id ) ) {
		return new WP_Error( 'gs_menu', 'Menu non valido.' );
	}
	$items   = wp_get_nav_menu_items( $menu_id );
	$trovato = null;
	foreach ( $items as $item ) {
		$titolo_pulito = trim( str_replace( '&#8217;', "'", (string) $item->title ) );
		if ( 0 === strcasecmp( $titolo_pulito, "L'Esperto Risponde" ) ) {
			$trovato = $item;
			break;
		}
	}
	if ( ! $trovato ) {
		return new WP_Error( 'gs_menu', 'Voce "L\'Esperto Risponde" non trovata in questo menu.' );
	}

	$era_annidata = ! empty( $trovato->menu_item_parent );
	$titolo_rotto = false !== strpos( (string) $trovato->title, '&#8217;' );

	wp_update_nav_menu_item( $menu_id, $trovato->ID, array(
		'menu-item-title'     => "L'Esperto Risponde",
		'menu-item-object'    => $trovato->object,
		'menu-item-object-id' => $trovato->object_id,
		'menu-item-type'      => $trovato->type,
		'menu-item-url'       => $trovato->url,
		'menu-item-parent-id' => 0,
		'menu-item-status'    => 'publish',
	) );

	$fatto = array();
	if ( $era_annidata ) { $fatto[] = 'riportata di primo livello'; }
	if ( $titolo_rotto ) { $fatto[] = 'titolo corretto'; }
	if ( ! $fatto ) { $fatto[] = 'era già a posto, risalvata comunque'; }

	return array( 'fatto' => $fatto );
}

add_action( 'wp_ajax_gs_menu_struttura_ripara_esperto', 'gs_ajax_menu_struttura_ripara_esperto' );
function gs_ajax_menu_struttura_ripara_esperto() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() || ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permesso negato: serve il permesso di modificare i menu.' ) );
	}
	$menu_id = isset( $_POST['menu_id'] ) ? (int) $_POST['menu_id'] : 0;
	$esito   = gs_menu_struttura_ripara_esperto( $menu_id );
	if ( is_wp_error( $esito ) ) {
		wp_send_json_error( array( 'message' => $esito->get_error_message() ) );
	}
	wp_send_json_success( array( 'message' => '"L\'Esperto Risponde": ' . implode( ', ', $esito['fatto'] ) . '.' ) );
}
