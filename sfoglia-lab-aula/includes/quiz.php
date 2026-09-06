<?php
/**
 * quiz.php — motore dei quiz a scelta singola/multipla (cantiere 6 della
 * specifica). Stesso principio di esercizi.php e punteggio.php: il
 * catalogo è nel codice, non in una tabella gestibile dal pannello, ed è
 * marcato come esempio finché non arriva la banca di domande vera.
 *
 * A differenza dell'esercizio numerico, qui non c'è uno "scarto da un
 * valore corretto": ogni domanda vale allo stesso modo, il punteggio è la
 * percentuale di domande risposte esattamente bene, arrotondata a intero.
 *
 * La funzione di valutazione, sla_valuta_quiz(), non chiama nessuna
 * funzione di WordPress, per lo stesso motivo di sla_calcola_punteggio():
 * poterla provare da sola (vedi tests/test-quiz.php).
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLA_TEST' ) ) {
	exit;
}

/**
 * Il catalogo dei quiz. Ogni domanda ha: testo, tipo (singola o multipla),
 * opzioni (lettera => testo), le lettere corrette, una spiegazione che si
 * mostra SEMPRE dopo la risposta (anche quando è giusta — è la parte
 * didattica, non solo la correzione) e un'area per raggruppare le domande
 * per argomento.
 */
function sla_catalogo_quiz() {
	return array(
		'quiz-formati-base' => array(
			'slug'    => 'quiz-formati-base',
			'titolo'  => 'Quiz — Formati e farine (esempio)',
			'esempio' => true, // ATTENZIONE: domande dimostrative, non la banca reale di 400
			'domande' => array(
				array(
					'id'          => 'd1',
					'testo'       => 'Qual è, in generale, il rapporto tradizionale tra uova e farina per la sfoglia all\'uovo?',
					'tipo'        => 'singola',
					'opzioni'     => array(
						'a' => '1 uovo ogni 100 g di farina',
						'b' => '2 uova ogni 100 g di farina',
						'c' => '1 uovo ogni kg di farina',
					),
					'corrette'    => array( 'a' ),
					'spiegazione' => 'La proporzione classica è un uovo ogni 100 grammi di farina: è un punto di partenza, non un valore fisso — dipende dalla dimensione delle uova e dalla forza della farina.',
					'area'        => 'formati',
				),
				array(
					'id'          => 'd2',
					'testo'       => 'La tagliatella è generalmente più stretta o più larga della pappardella?',
					'tipo'        => 'singola',
					'opzioni'     => array(
						'a' => 'Più stretta',
						'b' => 'Più larga',
						'c' => 'Hanno la stessa larghezza',
					),
					'corrette'    => array( 'a' ),
					'spiegazione' => 'La tagliatella è più stretta: la pappardella è il formato più largo tra le paste lunghe all\'uovo.',
					'area'        => 'formati',
				),
				array(
					'id'          => 'd3',
					'testo'       => 'Quali tra questi sono formati di pasta ripiena? (puoi scegliere più di una risposta)',
					'tipo'        => 'multipla',
					'opzioni'     => array(
						'a' => 'Tortellino',
						'b' => 'Tagliolino',
						'c' => 'Cappellaccio',
						'd' => 'Gramigna',
					),
					'corrette'    => array( 'a', 'c' ),
					'spiegazione' => 'Tortellino e cappellaccio sono paste ripiene. Tagliolino e gramigna sono paste lisce, senza ripieno.',
					'area'        => 'formati',
				),
				array(
					'id'          => 'd4',
					'testo'       => 'Nella laminazione della pasta sfoglia, il burro va lavorato:',
					'tipo'        => 'singola',
					'opzioni'     => array(
						'a' => 'Il più caldo possibile, per stenderlo meglio',
						'b' => 'Freddo, altrimenti esce dalle pieghe',
						'c' => 'A temperatura ambiente, non ha importanza',
					),
					'corrette'    => array( 'b' ),
					'spiegazione' => 'Il burro va tenuto freddo: se scalda troppo esce dalle pieghe durante la laminazione e la sfoglia non si alza in cottura.',
					'area'        => 'temperature',
				),
				array(
					'id'          => 'd5',
					'testo'       => 'Perché l\'impasto della sfoglia ha bisogno di un periodo di riposo prima di essere tirato?',
					'tipo'        => 'singola',
					'opzioni'     => array(
						'a' => 'Solo per comodità del cuoco, non cambia il risultato',
						'b' => 'Per far rilassare il glutine, così la sfoglia si tira senza ritirarsi',
						'c' => 'Per far asciugare la farina',
					),
					'corrette'    => array( 'b' ),
					'spiegazione' => 'Il riposo rilassa la maglia glutinica formata durante l\'impasto: senza, la sfoglia tende a ritirarsi appena la si tira sottile.',
					'area'        => 'temperature',
				),
			),
		),
	);
}

function sla_get_quiz( $slug ) {
	$catalogo = sla_catalogo_quiz();
	return isset( $catalogo[ $slug ] ) ? $catalogo[ $slug ] : null;
}

/**
 * Valuta le risposte di uno studente contro le domande di un quiz.
 *
 * @param array $risposte  Associativo id_domanda => lettera o array di lettere scelte.
 * @param array $domande   L'elenco delle domande del quiz (da sla_get_quiz()).
 * @return array{
 *   punteggio: int,
 *   dettaglio: array<string, array{corretta: bool, corrette: array, scelte: array}>
 * }
 */
function sla_valuta_quiz( $risposte, $domande ) {
	$dettaglio  = array();
	$corrette_n = 0;

	foreach ( $domande as $domanda ) {
		$id = $domanda['id'];

		$scelte = $risposte[ $id ] ?? array();
		if ( ! is_array( $scelte ) ) {
			$scelte = array( $scelte );
		}
		$scelte = array_values( array_unique( array_filter( array_map( 'strval', $scelte ), 'strlen' ) ) );
		sort( $scelte );

		$attese = $domanda['corrette'];
		$attese_ordinate = $attese;
		sort( $attese_ordinate );

		$ok = ( $scelte === $attese_ordinate );
		if ( $ok ) {
			$corrette_n++;
		}

		$dettaglio[ $id ] = array(
			'corretta' => $ok,
			'corrette' => $attese,
			'scelte'   => $scelte,
		);
	}

	$totale    = count( $domande );
	$punteggio = $totale > 0 ? (int) round( 100 * $corrette_n / $totale ) : 0;

	return array(
		'punteggio' => $punteggio,
		'dettaglio' => $dettaglio,
	);
}
