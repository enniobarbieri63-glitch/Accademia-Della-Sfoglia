<?php
/**
 * seo.php — SEO/GEO delle pagine pubbliche del plugin: meta description,
 * Open Graph e dati strutturati Schema.org per le pagine davvero
 * raggiungibili senza accesso: FAQ, Registro Ufficiale, Badge e Traguardi,
 * Vetrina pubblica, Galleria delle Sfogline, Calendario Corsi.
 *
 * Il Ricettario di Famiglia NON è incluso: richiede sempre il login
 * (gs_sc_ricettario() apre con gs_login_notice()), quindi nessun motore di
 * ricerca o assistente IA potrebbe comunque leggerne il contenuto — dargli
 * titolo/descrizione promettenti sarebbe fuorviante.
 *
 * "GEO" (Generative Engine Optimization): i dati strutturati — soprattutto
 * FAQPage — sono quello che gli assistenti IA (ChatGPT, Perplexity,
 * Gemini…) leggono più volentieri per rispondere citando direttamente
 * l'Accademia della Sfoglia, più della sola pagina HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True se un plugin SEO dedicato (Yoast, RankMath, All in One SEO,
 * SEOPress…) è già attivo: in quel caso non aggiungiamo un secondo
 * <meta name="description"> (i motori di ricerca si confondono con tag
 * duplicati). I dati strutturati restano comunque: sono additivi, più
 * blocchi JSON-LD sulla stessa pagina non creano conflitti.
 */
function gs_seo_altro_plugin_attivo() {
	return class_exists( 'WPSEO_Options' )
		|| defined( 'RANK_MATH_VERSION' )
		|| function_exists( 'aioseo' )
		|| function_exists( 'seopress_init' );
}

/** Le pagine pubbliche gestite qui: chiave opzione => chiave interna. */
function gs_seo_pagine() {
	return array(
		'gs_page_faq'        => 'faq',
		'gs_page_registro'   => 'registro',
		'gs_page_badge'      => 'badge',
		'gs_page_vetrina'    => 'vetrina',
		'gs_page_galleria'   => 'galleria',
		'gs_page_calendario' => 'calendario',
		'gs_page_artigiani'  => 'artigiani',
	);
}

/** Slug dell'artigiano indicato in ?gs_art=, per la sua vetrina (stessa logica di gs_sc_artigiani()). */
function gs_seo_artigiano_slug() {
	$slug = get_query_var( 'gs_art' );
	if ( ! $slug && isset( $_GET['gs_art'] ) ) {
		$slug = sanitize_title( wp_unslash( $_GET['gs_art'] ) );
	}
	return $slug;
}

/** Il post gs_artigiano pubblicato indicato in ?gs_art=, o null. */
function gs_seo_artigiano_post() {
	$slug = gs_seo_artigiano_slug();
	if ( ! $slug || ! function_exists( 'gs_art_pubblicata' ) ) {
		return null;
	}
	$posts = get_posts( array(
		'post_type'      => 'gs_artigiano',
		'name'           => $slug,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'suppress_filters' => true,
	) );
	if ( ! $posts || ! gs_art_pubblicata( $posts[0]->ID ) ) {
		return null;
	}
	return $posts[0];
}

/** Se ci si trova su una di queste pagine, la sua chiave interna; altrimenti stringa vuota. */
function gs_seo_pagina_attuale() {
	foreach ( gs_seo_pagine() as $opt => $chiave ) {
		$pid = (int) get_option( $opt );
		if ( $pid && is_page( $pid ) ) {
			return $chiave;
		}
	}
	return '';
}

/** Login della sfoglina indicata in ?sfoglina=, per la Vetrina (stessa logica di gs_sc_vetrina()). */
function gs_seo_vetrina_login() {
	$login = get_query_var( 'sfoglina' );
	if ( ! $login && isset( $_GET['sfoglina'] ) ) {
		$login = sanitize_user( wp_unslash( $_GET['sfoglina'] ) );
	}
	return $login;
}

/** Meta description per una chiave di pagina (statica, o dinamica per la Vetrina di una sfoglina specifica). */
function gs_seo_descrizione( $chiave ) {
	switch ( $chiave ) {
		case 'faq':
			return "Le domande più frequenti sull'Accademia della Sfoglia: corsi, iscrizione, sfide e community, con le risposte della segreteria.";
		case 'registro':
			return "Il Registro Ufficiale degli Allievi dell'Accademia della Sfoglia di Rina Poletti: chi ha completato l'intero percorso di formazione, fino alla Laurea in Sfoglia.";
		case 'badge':
			return "I badge e i traguardi dell'Accademia della Sfoglia: gli obiettivi da sbloccare partecipando alle sfide, condividendo ricette di famiglia e imparando la sfoglia a mano.";
		case 'vetrina':
			$login = gs_seo_vetrina_login();
			if ( $login ) {
				// 'slug', non 'login': vedi la nota su gs_seo_schema_vetrina() più sotto.
				$user = get_user_by( 'slug', $login );
				if ( $user && function_exists( 'gs_get_level' ) ) {
					$level = gs_get_level( $user->ID );
					return 'Vetrina di ' . $user->display_name . " sull'Accademia della Sfoglia — livello " . $level['titolo'] . '.';
				}
			}
			return "Le vetrine pubbliche delle sfogline dell'Accademia della Sfoglia: livello, punti e sfoglie condivise da ognuna.";
		case 'galleria':
			return 'La Galleria delle Sfogline: le foto delle sfoglie realizzate nelle sfide della Accademia della Sfoglia, filtrabili per stagione, ingrediente e squadra.';
		case 'calendario':
			return "Il Calendario dei Corsi dell'Accademia della Sfoglia di Rina Poletti: date, orari e prenotazioni per imparare la sfoglia a mano.";
		case 'artigiani':
			$post = gs_seo_artigiano_post();
			if ( $post ) {
				$a = function_exists( 'gs_art_get' ) ? gs_art_get( $post->ID ) : array( 'testo' => '', 'comune' => '' );
				if ( ! empty( $a['testo'] ) ) {
					return wp_strip_all_tags( wp_trim_words( $a['testo'], 30 ) );
				}
				return get_the_title( $post ) . ( $a['comune'] ? ' — ' . $a['comune'] : '' ) . ": una vetrina di Gli Artigiani della Pasta, Accademia della Sfoglia.";
			}
			return "Gli Artigiani della Pasta: pastifici, botteghe e piccoli produttori che portano avanti la tradizione della sfoglia fatta a mano, in collaborazione con l'Accademia della Sfoglia.";
	}
	return '';
}

/** Titolo social (og:title) per una chiave di pagina: solo dove serve un titolo diverso da quello della pagina. */
function gs_seo_og_titolo( $chiave ) {
	if ( 'artigiani' === $chiave ) {
		$post = gs_seo_artigiano_post();
		if ( $post ) {
			return get_the_title( $post );
		}
	}
	return '';
}

/** Immagine social (og:image) per una chiave di pagina: solo dove ne esiste una sensata da mostrare. */
function gs_seo_og_immagine( $chiave ) {
	if ( 'artigiani' === $chiave ) {
		$post = gs_seo_artigiano_post();
		if ( $post && function_exists( 'gs_art_get' ) ) {
			$a = gs_art_get( $post->ID );
			if ( ! empty( $a['logo'] ) ) {
				return $a['logo'];
			}
			if ( ! empty( $a['media'][0]['url'] ) ) {
				return $a['media'][0]['url'];
			}
		}
	}
	return '';
}

// -----------------------------------------------------------------------------
// Dati strutturati (Schema.org / JSON-LD), una funzione per pagina.
// -----------------------------------------------------------------------------

/** FAQPage: la struttura che gli assistenti IA e Google leggono meglio per rispondere citando le nostre risposte. */
function gs_seo_schema_faq() {
	if ( ! function_exists( 'gs_faq_per_categoria' ) ) {
		return null;
	}
	$domande = array();
	foreach ( gs_faq_per_categoria() as $voci ) {
		foreach ( $voci as $v ) {
			$risposta = wp_strip_all_tags( (string) $v['risposta'] );
			if ( '' === trim( (string) $v['domanda'] ) || '' === trim( $risposta ) ) {
				continue;
			}
			$domande[] = array(
				'@type'          => 'Question',
				'name'           => $v['domanda'],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $risposta ),
			);
		}
	}
	if ( ! $domande ) {
		return null;
	}
	return array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $domande );
}

/** ItemList degli Allievi del Registro Ufficiale. */
function gs_seo_schema_registro() {
	if ( ! function_exists( 'gs_registro_allievi' ) ) {
		return null;
	}
	$righe = gs_registro_allievi();
	if ( ! $righe ) {
		return null;
	}
	$items = array();
	$i     = 1;
	foreach ( $righe as $r ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i++, 'name' => $r['nome'] );
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => "Registro Ufficiale dell'Accademia della Sfoglia",
		'itemListElement' => $items,
	);
}

/** ItemList dei badge/traguardi ottenibili. */
function gs_seo_schema_badge() {
	if ( ! function_exists( 'gs_get_badges_definitions' ) ) {
		return null;
	}
	$defs  = gs_get_badges_definitions();
	$items = array();
	$i     = 1;
	foreach ( $defs as $b ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i++,
			'item'     => array( '@type' => 'Thing', 'name' => $b['label'], 'description' => $b['desc'] ),
		);
	}
	if ( ! $items ) {
		return null;
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => 'Badge e Traguardi — Accademia della Sfoglia',
		'itemListElement' => $items,
	);
}

/** ProfilePage/Person: solo quando la Vetrina mostra una sfoglina specifica (?sfoglina=login), mai per la pagina generica. */
function gs_seo_schema_vetrina( $login ) {
	if ( ! $login ) {
		return null;
	}
	// 'slug' (user_nicename): l'indirizzo pubblico della Vetrina non porta
	// più lo username (ISTRUZIONE-IL-RESET.md, 26/08/2026). Il parametro si
	// chiama ancora $login per non toccare la firma della funzione, ma dal
	// 26/08/2026 il valore che arriva da ?sfoglina= è un nicename.
	$user = get_user_by( 'slug', $login );
	if ( ! $user ) {
		return null;
	}
	if ( function_exists( 'gs_vetrina_bloccata' ) && gs_vetrina_bloccata( $user->ID ) ) {
		return null;
	}
	$level = function_exists( 'gs_get_level' ) ? gs_get_level( $user->ID ) : array( 'titolo' => '' );
	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'ProfilePage',
		'mainEntity' => array(
			'@type'       => 'Person',
			'name'        => $user->display_name,
			'memberOf'    => array( '@type' => 'Organization', 'name' => 'Accademia della Sfoglia' ),
			'description' => 'Livello: ' . $level['titolo'],
		),
	);
}

/** ImageGallery: un campione delle foto pubbliche più recenti (sempre non filtrato: la pagina cambia coi filtri dell'utente, i dati strutturati restano una rappresentazione generale). */
function gs_seo_schema_galleria() {
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
	if ( ! $sfide_ids ) {
		return null;
	}
	$sfoglie = get_posts( array(
		'post_type'      => 'gs_sfoglia',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'meta_query'     => array( array( 'key' => 'gs_sfida_id', 'value' => $sfide_ids, 'compare' => 'IN' ) ),
	) );
	$immagini = array();
	foreach ( $sfoglie as $s ) {
		if ( ! has_post_thumbnail( $s->ID ) ) {
			continue;
		}
		$immagini[] = array(
			'@type'      => 'ImageObject',
			'contentUrl' => get_the_post_thumbnail_url( $s->ID, 'medium' ),
			'name'       => get_the_title( $s ),
		);
	}
	if ( ! $immagini ) {
		return null;
	}
	return array(
		'@context' => 'https://schema.org',
		'@type'    => 'ImageGallery',
		'name'     => 'Galleria delle Sfogline — Accademia della Sfoglia',
		'image'    => $immagini,
	);
}

/** ItemList di Course, uno per corso in programma (con data e prezzo se impostati). */
function gs_seo_schema_calendario() {
	if ( ! function_exists( 'gs_cal_corsi' ) || ! function_exists( 'gs_cal_corso_get' ) ) {
		return null;
	}
	$items = array();
	$i     = 1;
	foreach ( gs_cal_corsi( true ) as $p ) {
		$c = gs_cal_corso_get( $p->ID );
		if ( 'bloccato' === $c['stato'] ) {
			continue;
		}
		$corso = array(
			'@type'       => 'Course',
			'name'        => $c['titolo'],
			'description' => $c['descr'] ? wp_strip_all_tags( $c['descr'] ) : $c['titolo'],
			'provider'    => array( '@type' => 'Organization', 'name' => 'Accademia della Sfoglia' ),
		);
		if ( $c['data'] ) {
			$istanza = array(
				'@type'      => 'CourseInstance',
				'courseMode' => 'onsite',
				'startDate'  => $c['data'] . ( $c['inizio'] ? 'T' . $c['inizio'] : '' ),
			);
			if ( $c['prezzo'] > 0 ) {
				$istanza['offers'] = array( '@type' => 'Offer', 'price' => (string) $c['prezzo'], 'priceCurrency' => 'EUR' );
			}
			$corso['hasCourseInstance'] = array( $istanza );
		}
		$items[] = array( '@type' => 'ListItem', 'position' => $i++, 'item' => $corso );
	}
	if ( ! $items ) {
		return null;
	}
	return array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'name'            => 'Calendario dei Corsi — Accademia della Sfoglia',
		'itemListElement' => $items,
	);
}

/** LocalBusiness: solo quando la pagina mostra la vetrina di un artigiano specifico (?gs_art=slug). */
function gs_seo_schema_artigiano() {
	$post = gs_seo_artigiano_post();
	if ( ! $post || ! function_exists( 'gs_art_get' ) ) {
		return null;
	}
	$a = gs_art_get( $post->ID );
	$out = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'LocalBusiness',
		'name'        => $a['nome'],
		'description' => $a['testo'] ? wp_strip_all_tags( wp_trim_words( $a['testo'], 40 ) ) : '',
	);
	if ( $a['comune'] ) { $out['address'] = array( '@type' => 'PostalAddress', 'addressLocality' => $a['comune'] ); }
	if ( $a['indirizzo'] ) { $out['address'] = array( '@type' => 'PostalAddress', 'streetAddress' => $a['indirizzo'] ); }
	if ( $a['logo'] ) { $out['image'] = $a['logo']; }
	if ( function_exists( 'gs_art_url' ) ) { $out['url'] = gs_art_url( $a['id'] ); }
	return $out;
}

/** Dati strutturati per una chiave di pagina, o null se non applicabile / nulla da mostrare. */
function gs_seo_schema( $chiave ) {
	switch ( $chiave ) {
		case 'faq':        return gs_seo_schema_faq();
		case 'registro':   return gs_seo_schema_registro();
		case 'badge':      return gs_seo_schema_badge();
		case 'galleria':   return gs_seo_schema_galleria();
		case 'calendario': return gs_seo_schema_calendario();
		case 'vetrina':    return gs_seo_schema_vetrina( gs_seo_vetrina_login() );
		case 'artigiani':  return gs_seo_schema_artigiano();
	}
	return null;
}

/** Aggancia meta description/OG e dati strutturati nell'intestazione delle pagine pubbliche gestite qui. */
function gs_seo_head() {
	$chiave = gs_seo_pagina_attuale();
	if ( ! $chiave ) {
		return;
	}

	$descrizione = gs_seo_descrizione( $chiave );
	if ( $descrizione && ! gs_seo_altro_plugin_attivo() ) {
		echo '<meta name="description" content="' . esc_attr( $descrizione ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $descrizione ) . '">' . "\n";

		// Titolo e immagine social: solo dove una funzione ne restituisce uno
		// sensato (oggi solo la vetrina di un singolo Artigiano della Pasta),
		// così le anteprime su WhatsApp/Facebook mostrano nome e logo/foto
		// invece della sola scheda generica del sito.
		$og_titolo = gs_seo_og_titolo( $chiave );
		if ( $og_titolo ) {
			echo '<meta property="og:title" content="' . esc_attr( $og_titolo ) . '">' . "\n";
		}
		$og_immagine = gs_seo_og_immagine( $chiave );
		if ( $og_immagine ) {
			echo '<meta property="og:image" content="' . esc_url( $og_immagine ) . '">' . "\n";
		}
	}

	$schema = gs_seo_schema( $chiave );
	if ( $schema ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'gs_seo_head' );
