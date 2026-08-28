<?php
/**
 * mappa-squadre.php — Illustrazione dell'Italia con le regioni colorate,
 * dentro "Classifica a Squadre". I contorni (dati di fatto, non protetti
 * da copyright) sono stati ricavati da una mappa politica di riferimento
 * con un rilevamento automatico dei contorni, poi ridisegnati qui — non è
 * una copia dell'immagine originale.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Le regioni italiane: contorno (coordinate in un riquadro 600x708) + colore
 * + nome. I nomi (aggiunti in v3.114.0, per la Mappa dei Territori) sono
 * stati assegnati verificando visivamente ogni sagoma nel browser — non a
 * tavolino sulle sole coordinate, per lo stesso motivo per cui i contorni
 * stessi erano stati ricalcolati a mano: un errore di geografia sarebbe
 * stato peggio di un contorno imperfetto.
 */
function gs_mappa_italia_regioni() {
	return array(
		array( 'nome' => 'Sicilia', 'colore' => '#f0f000', 'punti' => array( 419, 531, 406, 535, 402, 540, 388, 538, 376, 546, 358, 549, 349, 546, 340, 552, 333, 551, 327, 544, 319, 542, 316, 537, 307, 540, 300, 548, 293, 547, 289, 542, 278, 550, 277, 562, 279, 567, 286, 572, 300, 572, 323, 588, 329, 589, 334, 595, 339, 595, 343, 599, 360, 599, 368, 606, 376, 619, 396, 620, 401, 623, 402, 611, 409, 605, 410, 600, 406, 598, 405, 591, 397, 584, 397, 580 ) ),
		// Sardegna: ricalcolata chiudendo il buco lasciato dalla scritta "SARDEGNA" stampata sull'isola nella mappa di riferimento (creava una strozzatura visibile a metà).
		array( 'nome' => 'Sardegna', 'colore' => '#0078b4', 'punti' => array( 135, 357, 116, 375, 101, 382, 89, 376, 86, 391, 94, 395, 100, 410, 100, 421, 95, 429, 102, 434, 103, 440, 96, 445, 92, 475, 98, 480, 101, 490, 114, 491, 119, 487, 119, 478, 123, 474, 134, 474, 140, 480, 145, 475, 153, 428, 148, 415, 157, 403, 158, 395, 150, 369 ) ),
		array( 'nome' => 'Piemonte', 'colore' => '#b464dc', 'punti' => array( 112, 63, 107, 67, 108, 73, 103, 75, 100, 83, 96, 85, 96, 104, 93, 108, 74, 107, 65, 112, 61, 125, 50, 130, 40, 128, 45, 133, 45, 140, 55, 144, 56, 154, 48, 161, 48, 175, 60, 184, 92, 185, 95, 174, 101, 170, 102, 162, 107, 162, 109, 167, 118, 161, 126, 161, 127, 165, 131, 154, 135, 154, 140, 161, 143, 159, 143, 153, 130, 139, 122, 140, 121, 143, 114, 142, 115, 137, 121, 137, 114, 125, 118, 114, 123, 114, 126, 119, 129, 114, 119, 96, 123, 78, 113, 68 ) ),
		// Lombardia: mancava (il verde scuro era finito escluso per un pelo dal filtro anti-bordi-neri), aggiunta a parte con un colore ben distinto dallo sfondo.
		array( 'nome' => 'Lombardia', 'colore' => '#22a352', 'punti' => array( 189, 51, 183, 55, 187, 66, 186, 75, 179, 72, 178, 64, 171, 64, 168, 70, 163, 70, 157, 58, 152, 56, 153, 65, 141, 83, 143, 91, 135, 95, 129, 82, 124, 88, 124, 96, 129, 99, 133, 118, 128, 123, 123, 123, 121, 119, 119, 124, 126, 136, 133, 135, 146, 151, 146, 156, 149, 153, 148, 141, 156, 133, 179, 133, 183, 138, 191, 139, 197, 146, 203, 142, 213, 146, 232, 145, 222, 141, 203, 119, 203, 107, 210, 96, 206, 99, 199, 97, 197, 82, 202, 75, 201, 62, 206, 60, 192, 56 ) ),
		array( 'nome' => 'Toscana', 'colore' => '#8ca0f0', 'punti' => array( 168, 176, 167, 179, 178, 189, 181, 198, 187, 205, 187, 226, 197, 239, 195, 260, 196, 258, 204, 259, 206, 268, 220, 276, 223, 282, 221, 292, 238, 290, 239, 284, 246, 278, 246, 269, 251, 266, 251, 260, 255, 258, 253, 248, 264, 242, 259, 238, 259, 232, 269, 218, 256, 217, 244, 205, 244, 196, 239, 192, 232, 191, 225, 198, 219, 196, 212, 198, 207, 193, 199, 194, 191, 186, 178, 182, 174, 174 ) ),
		array( 'nome' => 'Emilia-Romagna', 'colore' => '#c80014', 'punti' => array( 153, 142, 153, 155, 148, 160, 159, 165, 157, 173, 165, 175, 169, 168, 173, 168, 178, 171, 180, 177, 196, 184, 201, 190, 209, 189, 213, 193, 223, 193, 232, 185, 236, 185, 249, 192, 247, 202, 260, 214, 268, 202, 276, 202, 277, 206, 286, 207, 288, 205, 270, 182, 267, 170, 267, 157, 270, 154, 268, 150, 252, 147, 246, 152, 235, 149, 211, 150, 204, 147, 201, 151, 196, 151, 190, 144, 180, 142, 177, 137, 175, 139, 160, 137 ) ),
		array( 'nome' => 'Puglia', 'colore' => '#f06400', 'punti' => array( 395, 322, 394, 337, 387, 342, 394, 346, 394, 353, 402, 356, 400, 364, 431, 365, 435, 368, 435, 374, 444, 375, 450, 384, 463, 382, 471, 400, 479, 394, 488, 394, 492, 402, 503, 407, 517, 405, 526, 416, 527, 426, 540, 432, 545, 415, 543, 408, 523, 392, 519, 385, 500, 380, 483, 366, 446, 354, 425, 341, 426, 334, 438, 324, 435, 318 ) ),
		array( 'nome' => 'Veneto', 'colore' => '#508cb4', 'punti' => array( 265, 50, 264, 56, 257, 58, 254, 67, 259, 70, 261, 77, 253, 81, 249, 89, 240, 87, 232, 92, 227, 104, 216, 104, 214, 96, 212, 105, 208, 107, 208, 119, 222, 135, 231, 138, 236, 145, 243, 148, 247, 144, 260, 143, 271, 147, 274, 151, 279, 147, 265, 129, 267, 118, 275, 111, 280, 111, 282, 114, 295, 106, 301, 106, 302, 102, 297, 97, 291, 100, 283, 99, 275, 89, 279, 77, 274, 73, 274, 68, 279, 65, 279, 60, 290, 51, 278, 50, 277, 53, 269, 53 ) ),
		array( 'nome' => 'Lazio', 'colore' => '#f00064', 'punti' => array( 252, 272, 250, 280, 243, 286, 244, 291, 237, 294, 243, 297, 249, 313, 258, 314, 266, 322, 267, 328, 273, 331, 276, 336, 279, 336, 285, 346, 298, 351, 304, 356, 304, 359, 309, 356, 319, 356, 323, 361, 333, 359, 337, 355, 337, 347, 342, 344, 340, 337, 328, 331, 317, 331, 313, 328, 311, 322, 307, 322, 306, 326, 301, 325, 299, 310, 303, 305, 309, 305, 311, 308, 311, 304, 305, 298, 305, 283, 298, 284, 291, 293, 278, 297, 274, 292, 267, 290, 266, 281, 256, 281, 254, 279, 255, 274 ) ),
		array( 'nome' => 'Calabria', 'colore' => '#f0f08c', 'punti' => array( 456, 422, 453, 423, 451, 435, 436, 436, 431, 430, 426, 435, 432, 455, 439, 462, 441, 468, 449, 467, 449, 472, 442, 474, 445, 487, 451, 491, 450, 499, 445, 505, 433, 508, 437, 511, 436, 521, 425, 537, 425, 544, 429, 548, 441, 547, 445, 533, 455, 523, 463, 519, 461, 498, 477, 485, 487, 486, 489, 484, 485, 473, 485, 462, 487, 461, 476, 456, 471, 449, 461, 450, 458, 447, 457, 437, 462, 427 ) ),
		array( 'nome' => 'Trentino-Alto Adige', 'colore' => '#f0f000', 'punti' => array( 269, 25, 253, 31, 242, 30, 239, 33, 230, 31, 227, 34, 226, 39, 221, 43, 213, 43, 206, 37, 203, 37, 198, 41, 197, 44, 202, 50, 202, 54, 210, 58, 210, 64, 205, 65, 206, 77, 201, 84, 203, 95, 208, 92, 215, 92, 218, 94, 219, 100, 224, 101, 230, 88, 237, 83, 246, 84, 248, 78, 257, 75, 251, 70, 253, 54, 262, 51, 263, 47, 269, 47, 270, 49, 275, 48, 271, 39, 265, 35, 265, 31 ) ),
		array( 'nome' => 'Campania', 'colore' => '#8cf0f0', 'punti' => array( 341, 352, 341, 357, 336, 363, 342, 367, 347, 376, 348, 383, 364, 383, 367, 386, 367, 391, 362, 395, 375, 391, 376, 389, 382, 389, 391, 405, 390, 412, 388, 413, 389, 416, 395, 418, 407, 428, 417, 422, 421, 415, 419, 412, 411, 411, 411, 399, 407, 397, 407, 392, 402, 389, 402, 379, 410, 376, 411, 371, 408, 370, 407, 376, 401, 375, 402, 370, 395, 365, 397, 359, 389, 356, 388, 347, 374, 352, 372, 355, 365, 354, 355, 348, 350, 349, 348, 353 ) ),
		array( 'nome' => 'Abruzzo', 'colore' => '#14c828', 'punti' => array( 311, 285, 310, 296, 316, 301, 317, 308, 314, 312, 309, 312, 306, 309, 304, 311, 304, 315, 314, 317, 318, 320, 318, 324, 323, 327, 332, 326, 335, 329, 343, 332, 344, 330, 348, 330, 348, 324, 350, 321, 354, 319, 359, 319, 365, 325, 365, 327, 365, 324, 372, 316, 372, 313, 375, 309, 370, 307, 366, 303, 363, 303, 361, 301, 361, 298, 356, 297, 355, 292, 348, 288, 341, 279, 338, 271, 332, 270, 330, 274, 324, 273, 323, 276, 320, 277, 318, 284 ) ),
		array( 'nome' => 'Basilicata', 'colore' => '#8cf000', 'punti' => array( 416, 370, 415, 377, 409, 382, 407, 382, 406, 386, 410, 389, 411, 394, 415, 396, 415, 402, 426, 405, 426, 410, 423, 411, 427, 413, 427, 417, 421, 423, 424, 429, 429, 426, 434, 426, 439, 432, 449, 431, 450, 419, 463, 419, 465, 411, 461, 411, 460, 406, 465, 405, 466, 408, 468, 407, 468, 403, 463, 398, 463, 390, 460, 386, 454, 389, 449, 389, 445, 386, 440, 378, 433, 378, 427, 368 ) ),
		array( 'nome' => 'Marche', 'colore' => '#648c8c', 'punti' => array( 271, 205, 269, 213, 267, 214, 272, 215, 272, 221, 274, 222, 275, 227, 277, 223, 285, 223, 286, 229, 282, 230, 284, 230, 285, 233, 291, 232, 294, 236, 296, 249, 299, 250, 300, 260, 297, 261, 307, 263, 312, 267, 313, 271, 317, 273, 321, 268, 328, 269, 329, 264, 334, 265, 335, 254, 333, 253, 330, 238, 327, 234, 327, 231, 325, 230, 325, 227, 324, 231, 319, 231, 318, 226, 315, 226, 312, 221, 305, 218, 303, 212, 298, 212, 297, 208, 294, 207, 294, 204, 291, 206, 290, 211, 283, 211, 282, 208, 275, 209, 274, 206 ) ),
		array( 'nome' => 'Umbria', 'colore' => '#f0c8b4', 'punti' => array( 271, 227, 268, 228, 268, 231, 265, 232, 264, 238, 270, 240, 270, 244, 266, 247, 262, 247, 259, 251, 259, 256, 262, 257, 264, 260, 268, 260, 269, 265, 260, 266, 259, 264, 258, 276, 259, 277, 267, 276, 270, 279, 270, 284, 272, 287, 276, 288, 279, 291, 283, 291, 284, 289, 289, 288, 292, 283, 298, 280, 299, 277, 305, 277, 306, 272, 308, 271, 302, 269, 301, 267, 295, 267, 291, 265, 291, 258, 293, 257, 289, 236, 283, 236, 279, 232, 273, 231 ) ),
		array( 'nome' => 'Friuli-Venezia Giulia', 'colore' => '#f08cb4', 'punti' => array( 293, 55, 286, 62, 283, 63, 283, 68, 278, 71, 284, 77, 284, 82, 280, 84, 281, 89, 284, 91, 284, 93, 287, 94, 297, 93, 302, 94, 304, 97, 316, 98, 318, 102, 319, 97, 324, 94, 321, 92, 318, 92, 317, 87, 319, 83, 319, 79, 324, 78, 324, 77, 316, 77, 314, 73, 314, 67, 317, 61, 320, 61, 321, 59, 326, 58, 324, 55, 320, 55, 319, 57, 309, 58, 306, 57, 305, 55, 299, 55, 298, 54 ) ),
		array( 'nome' => 'Molise', 'colore' => '#f0f000', 'punti' => array( 391, 320, 388, 318, 382, 317, 378, 314, 367, 331, 362, 331, 360, 329, 359, 326, 355, 324, 353, 326, 353, 335, 352, 336, 347, 336, 347, 339, 345, 340, 346, 341, 346, 346, 345, 348, 346, 348, 346, 346, 349, 344, 357, 344, 358, 345, 361, 345, 369, 350, 371, 348, 375, 348, 378, 346, 382, 345, 383, 344, 382, 337, 383, 336, 387, 336, 389, 335, 391, 332, 390, 331, 390, 322 ) ),
		array( 'nome' => "Valle d'Aosta", 'colore' => '#c8c88c', 'punti' => array( 90, 86, 85, 86, 84, 84, 82, 83, 82, 82, 79, 81, 76, 87, 66, 87, 65, 88, 60, 88, 59, 87, 57, 87, 56, 88, 52, 88, 50, 90, 51, 90, 54, 93, 56, 97, 58, 98, 58, 105, 59, 107, 65, 107, 69, 105, 69, 104, 66, 104, 65, 103, 65, 99, 66, 98, 70, 98, 71, 99, 71, 102, 72, 101, 75, 101, 75, 99, 76, 98, 84, 97, 85, 98, 85, 101, 88, 101, 89, 103, 90, 103, 91, 97, 93, 96, 90, 93 ) ),
		// Liguria: prima erano due schegge sottili separate dal bordo nero della mappa di riferimento; ricalcolata come un'unica fascia costiera piena, con un colore leggermente più scuro dell'arancio della Puglia per distinguerle bene.
		// Tentativo di ridisegno il 16/08/2026 scartato da Ennio ("assolutamente non va bene", forma ancora sbagliata): tenuta questa versione precedente.
		array( 'nome' => 'Liguria', 'colore' => '#e07800', 'punti' => array( 177, 195, 160, 177, 152, 174, 153, 166, 142, 167, 133, 161, 130, 167, 120, 165, 115, 170, 106, 170, 100, 176, 98, 185, 94, 189, 81, 191, 70, 203, 79, 205, 91, 201, 110, 178, 121, 170, 132, 170, 149, 177, 167, 194 ) ),
	);
}

/** Elenco canonico delle 20 regioni italiane, nello stesso ordine di gs_mappa_italia_regioni() — usato per il modulo "provenienza" delle ricette e per la Mappa dei Territori. */
function gs_regioni_italiane() {
	return array_map( function ( $r ) { return $r['nome']; }, gs_mappa_italia_regioni() );
}

/**
 * A quale squadra "appartengono" geograficamente le 20 regioni, per colorare
 * la mappa di "Classifica a Squadre" — le 4 squadre fisse di gs_get_teams()
 * prendono il nome proprio da queste macro-aree.
 */
function gs_regioni_per_squadra() {
	return array(
		'Team Nord'            => array( "Valle d'Aosta", 'Piemonte', 'Liguria', 'Lombardia', 'Trentino-Alto Adige', 'Veneto', 'Friuli-Venezia Giulia', 'Emilia-Romagna' ),
		'Team Centro'          => array( 'Toscana', 'Umbria', 'Marche', 'Lazio' ),
		'Team Sud e Isole'     => array( 'Abruzzo', 'Molise', 'Campania', 'Puglia', 'Basilicata', 'Calabria', 'Sicilia', 'Sardegna' ),
	);
}

/** Colore di ogni squadra sulla mappa. */
function gs_squadra_colore( $squadra ) {
	$colori = array(
		'Team Nord'           => '#3a6ea5',
		'Team Centro'         => '#bd8a13',
		'Team Sud e Isole'    => '#1f6e37',
	);
	return $colori[ $squadra ] ?? '#8a7a5c';
}

/**
 * L'illustrazione, pronta per essere inserita in "Classifica a Squadre".
 * L'Italia parte bianca; solo le regioni della squadra in testa alla
 * classifica (se ha già dei punti) si colorano nel colore di quella
 * squadra — non è più una mappa decorativa a colori fissi.
 */
function gs_mappa_squadre_html( $squadra_vincente = '' ) {
	$regioni_vincenti = array();
	if ( $squadra_vincente ) {
		$mappa = gs_regioni_per_squadra();
		if ( isset( $mappa[ $squadra_vincente ] ) ) {
			$regioni_vincenti = $mappa[ $squadra_vincente ];
		}
	}
	$colore_vincente = $squadra_vincente ? gs_squadra_colore( $squadra_vincente ) : '';

	$out = '<div class="gs-todo-riquadro"><div class="gs-mappa-squadre"><svg viewBox="0 0 600 708" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">';
	foreach ( gs_mappa_italia_regioni() as $regione ) {
		$punti = $regione['punti'];
		$d = 'M ' . implode( ' L ', array_map( function ( $i ) use ( $punti ) {
			return $punti[ $i ] . ',' . $punti[ $i + 1 ];
		}, range( 0, count( $punti ) - 2, 2 ) ) ) . ' Z';
		$vinta = in_array( $regione['nome'], $regioni_vincenti, true );
		$fill  = $vinta ? $colore_vincente : '#f5f1e6';
		$out  .= '<path d="' . esc_attr( $d ) . '" fill="' . esc_attr( $fill ) . '" stroke="#4a3a28" stroke-width="1.3" stroke-linejoin="round"><title>' . esc_html( $regione['nome'] ) . '</title></path>';
	}
	$out .= '</svg>';
	if ( $squadra_vincente ) {
		$out .= '<p class="gs-mappa-squadre-legenda"><span class="gs-mappa-squadre-pallino" style="background:' . esc_attr( $colore_vincente ) . '"></span> In testa: <strong>' . esc_html( $squadra_vincente ) . '</strong></p>';
	}
	$out .= '</div></div>';
	return $out;
}

// -----------------------------------------------------------------------------
// Mappa dei Territori (v3.114.0): la sfoglina "conquista" una regione quando
// ha almeno una ricetta APPROVATA con quella provenienza — usa il campo
// dedicato gs_regione_mappa (elenco chiuso delle 20 regioni), non il campo
// libero "Provenienza" del Ricettario, che può contenere qualunque testo
// (anche l'estero) e non si può far corrispondere in modo affidabile ai
// contorni della mappa.
// -----------------------------------------------------------------------------

/** Le regioni italiane per cui la sfoglina ha almeno una ricetta approvata. */
function gs_regioni_conquistate_da( $uid ) {
	$uid = (int) $uid;
	if ( ! $uid ) { return array(); }
	$conquistate = array();
	foreach ( gs_ricette_approvate() as $r ) {
		if ( (int) $r->post_author !== $uid ) { continue; }
		$regione = (string) get_post_meta( $r->ID, 'gs_regione_mappa', true );
		if ( $regione && ! in_array( $regione, $conquistate, true ) ) {
			$conquistate[] = $regione;
		}
	}
	return $conquistate;
}

/**
 * Chi ha vinto la gara della Mappa dei Territori (prima a conquistare tutte
 * le 20 regioni): user ID, o 0 se nessuna ha ancora vinto. Una gara sola,
 * un solo premio: dato in GS_OPTION (impostazioni), non per-utente.
 */
function gs_mappa_territori_vincitrice() {
	$s = gs_settings();
	return isset( $s['mappa_territori_vincitrice'] ) ? (int) $s['mappa_territori_vincitrice'] : 0;
}

/**
 * Da richiamare ogni volta che una ricetta viene approvata (aggancio
 * all'azione 'gs_ricetta_approvata', già usata da badges.php): se questa
 * sfoglina ha appena conquistato l'ultima regione che le mancava ed è la
 * prima a riuscirci, vince 50 punti. Una gara sola: chi arriva dopo trova
 * tutte le regioni comunque colorabili, ma il premio è già stato assegnato.
 */
add_action( 'gs_ricetta_approvata', 'gs_mappa_territori_verifica_vincitrice', 20, 1 );
function gs_mappa_territori_verifica_vincitrice( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || gs_mappa_territori_vincitrice() ) { return; }
	if ( count( gs_regioni_conquistate_da( $user_id ) ) < count( gs_regioni_italiane() ) ) { return; }

	$s = gs_settings();
	$s['mappa_territori_vincitrice']      = $user_id;
	$s['mappa_territori_vincitrice_data'] = current_time( 'mysql' );
	update_option( GS_OPTION, $s );

	if ( function_exists( 'gs_add_points' ) ) {
		gs_add_points( $user_id, 50, 'Prima ad accendere tutte le regioni della Mappa dei Territori' );
	}
	if ( function_exists( 'gs_accoda_volo' ) ) {
		gs_accoda_volo( $user_id, '🗺️ HAI ACCESO TUTTE LE REGIONI! +50 PUNTI', gs_pagina_url( 'gs_page_classifica' ) );
	}
}

/** La Mappa dei Territori della sfoglina: regioni conquistate a colori, le altre in grigio. */
function gs_mappa_territori_html( $uid ) {
	$conquistate = $uid ? gs_regioni_conquistate_da( $uid ) : array();
	$tutte       = gs_mappa_italia_regioni();
	$vincitrice_id = gs_mappa_territori_vincitrice();

	$out  = '<div class="gs-todo-riquadro">';
	$out .= '<p class="gs-hint" style="font-size:16px"><strong>🗺️ La Mappa dei Territori — la gara</strong><br>'
		. 'Pubblica una ricetta di famiglia approvata per ognuna delle 20 regioni italiane, indicando la regione nel modulo: ogni ricetta accende quel territorio sulla tua mappa. '
		. '<strong>Chi accende per prima TUTTE le 20 regioni vince 50 punti.</strong> Dopo la prima vincitrice la gara è chiusa, ma puoi continuare a completare la tua mappa per il gusto di farlo.</p>';
	if ( $vincitrice_id ) {
		$vincitrice = get_userdata( $vincitrice_id );
		$out .= '<p class="gs-hint">🏆 Gara conclusa: <strong>' . esc_html( $vincitrice ? $vincitrice->display_name : 'una sfoglina' ) . '</strong> ha acceso per prima tutte le regioni e ha vinto i 50 punti.</p>';
	}
	$out .= '<p class="gs-hint">' . count( $conquistate ) . ' di ' . count( $tutte ) . ' regioni conquistate.</p>';
	$out .= '<div class="gs-mappa-squadre"><svg viewBox="0 0 600 708" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">';
	foreach ( $tutte as $regione ) {
		$punti     = $regione['punti'];
		$d = 'M ' . implode( ' L ', array_map( function ( $i ) use ( $punti ) {
			return $punti[ $i ] . ',' . $punti[ $i + 1 ];
		}, range( 0, count( $punti ) - 2, 2 ) ) ) . ' Z';
		$conquistata = in_array( $regione['nome'], $conquistate, true );
		$fill        = $conquistata ? $regione['colore'] : '#e7e0d2';
		$out .= '<path d="' . esc_attr( $d ) . '" fill="' . esc_attr( $fill ) . '" stroke="#4a3a28" stroke-width="1.3" stroke-linejoin="round"><title>' . esc_attr( $regione['nome'] ) . ( $conquistata ? ' — conquistata' : ' — non ancora conquistata' ) . '</title></path>';
	}
	$out .= '</svg></div>';
	if ( $conquistate ) {
		sort( $conquistate );
		$out .= '<p class="gs-hint">Conquistate: ' . esc_html( implode( ', ', $conquistate ) ) . '</p>';
	} else {
		$out .= '<p class="gs-hint">Nessuna regione ancora conquistata: invia una ricetta di famiglia indicando la regione per iniziare a colorare la tua mappa.</p>';
	}
	$out .= '</div>';
	return $out;
}
