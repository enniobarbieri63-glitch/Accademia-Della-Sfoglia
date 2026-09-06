<?php
/**
 * vista-errori.php — Vista 2 del cruscotto: "dove sbaglia la classe"
 * (docs/02-specifica-classi-virtuali.md § 7). A differenza della Vista 1
 * (chi ha consegnato, con che punteggio — vive in cruscotto.php), questa
 * vista guarda TUTTE le assegnazioni della classe insieme, non solo
 * l'ultima, e cerca il problema che si ripete più spesso.
 *
 * Due fonti di errore, aggregate nello stesso elenco:
 *  - un esercizio numerico: la percentuale di tentativi finiti in fascia
 *    rossa, con l'etichetta presa dal messaggio del maestro per quella
 *    fascia (non "punteggio basso", ma la descrizione del difetto vero)
 *  - un quiz: la percentuale di risposte sbagliate DOMANDA PER DOMANDA,
 *    non solo il punteggio complessivo del quiz
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'SLA_TEST' ) ) {
	exit;
}

/**
 * La percentuale di casi "in errore" su un totale, arrotondata a intero.
 * Isolata da WordPress apposta per essere provata da sola: con un totale
 * a zero (nessun tentativo ancora) ritorna 0, non un errore di divisione.
 */
function sla_percentuale_errore( $in_errore, $totale ) {
	$in_errore = max( 0, (int) $in_errore );
	$totale    = max( 0, (int) $totale );
	if ( 0 === $totale ) {
		return 0;
	}
	return (int) round( 100 * min( $in_errore, $totale ) / $totale );
}

if ( defined( 'SLA_TEST' ) ) {
	return; // il resto del file usa funzioni di WordPress: si ferma qui nei test standalone.
}

/**
 * Le righe della Vista 2 per una classe: un array ordinato dalla
 * percentuale di errore più alta alla più bassa, ciascuna con l'etichetta
 * da mostrare, la percentuale, e da quale contenuto viene ("origine").
 * Le righe a 0% non compaiono: non sono un errore, sono un successo.
 */
function sla_errori_frequenti( $classe_id ) {
	$righe = array();

	foreach ( sla_assegnazioni_della_classe( $classe_id ) as $assegnazione ) {
		$tentativi = get_posts( array(
			'post_type'      => 'sla_tentativo',
			'post_status'    => 'publish',
			'post_parent'    => $assegnazione->ID,
			'posts_per_page' => -1,
		) );
		if ( empty( $tentativi ) ) {
			continue;
		}

		$slug = get_post_meta( $assegnazione->ID, 'sla_esercizio', true );

		if ( 'quiz' === sla_tipo_assegnazione( $assegnazione->ID ) ) {
			$quiz = sla_get_quiz( $slug );
			if ( ! $quiz ) {
				continue;
			}

			$conteggio = array();
			foreach ( $tentativi as $tentativo ) {
				$dettaglio = json_decode( (string) get_post_meta( $tentativo->ID, 'sla_dettaglio', true ), true );
				if ( ! is_array( $dettaglio ) ) {
					continue;
				}
				foreach ( $dettaglio as $id_domanda => $d ) {
					if ( ! isset( $conteggio[ $id_domanda ] ) ) {
						$conteggio[ $id_domanda ] = array( 'sbagliate' => 0, 'totale' => 0 );
					}
					$conteggio[ $id_domanda ]['totale']++;
					if ( empty( $d['corretta'] ) ) {
						$conteggio[ $id_domanda ]['sbagliate']++;
					}
				}
			}

			foreach ( $quiz['domande'] as $domanda ) {
				if ( ! isset( $conteggio[ $domanda['id'] ] ) ) {
					continue;
				}
				$c           = $conteggio[ $domanda['id'] ];
				$percentuale = sla_percentuale_errore( $c['sbagliate'], $c['totale'] );
				if ( $percentuale > 0 ) {
					$righe[] = array(
						'etichetta'   => $domanda['testo'],
						'percentuale' => $percentuale,
						'origine'     => $quiz['titolo'],
						'suggerimento'=> $domanda['spiegazione'],
					);
				}
			}
		} else {
			$esercizio = sla_get_esercizio( $slug );
			if ( ! $esercizio ) {
				continue;
			}

			$in_rosso = 0;
			foreach ( $tentativi as $tentativo ) {
				$punteggio = (int) get_post_meta( $tentativo->ID, 'sla_punteggio', true );
				if ( 'rosso' === sla_fascia_punteggio( $punteggio ) ) {
					$in_rosso++;
				}
			}
			$percentuale = sla_percentuale_errore( $in_rosso, count( $tentativi ) );
			if ( $percentuale > 0 ) {
				$righe[] = array(
					'etichetta'    => $esercizio['messaggi']['rosso'],
					'percentuale'  => $percentuale,
					'origine'      => $esercizio['titolo'],
					'suggerimento' => '',
				);
			}
		}
	}

	usort( $righe, function ( $a, $b ) {
		return $b['percentuale'] <=> $a['percentuale'];
	} );

	return $righe;
}
