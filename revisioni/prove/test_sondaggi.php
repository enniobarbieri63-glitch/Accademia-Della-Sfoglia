<?php
require 'stub-wp.php';
// Le righe vere di gs_ajax_sondaggio_vota in 3.286.0 (invariate dal 3.284.4).
// Qui sono eseguite intrecciate, come capita quando due richieste arrivano
// insieme: e' esattamente cio' che il lucchetto impedirebbe.
echo "  === SONDAGGI 3.286.0 — due sfogline votano nello stesso istante ===\n\n";
azzera();
$id = 12;
update_post_meta( $id, 'gs_sond_voti', [] );

// -- Anna entra --                          -- Bruna entra --
$voti_anna  = get_post_meta( $id, 'gs_sond_voti', true );   // legge []
$voti_bruna = get_post_meta( $id, 'gs_sond_voti', true );   // legge [] anche lei

echo "  Anna legge i voti:  " . json_encode( $voti_anna ) . "\n";
echo "  Bruna legge i voti: " . json_encode( $voti_bruna ) . "\n";

if ( isset( $voti_anna[ 101 ] ) )  { echo "  Anna respinta\n"; }  else { $voti_anna[ 101 ] = 1; }
if ( isset( $voti_bruna[ 202 ] ) ) { echo "  Bruna respinta\n"; } else { $voti_bruna[ 202 ] = 2; }

update_post_meta( $id, 'gs_sond_voti', $voti_anna );   // Anna scrive
gs_add_points( 101, 5, 'Voto dato in un sondaggio' );
echo "  → Anna: «Voto registrato, grazie!» +5 punti\n";

update_post_meta( $id, 'gs_sond_voti', $voti_bruna );  // Bruna scrive un attimo dopo
gs_add_points( 202, 5, 'Voto dato in un sondaggio' );
echo "  → Bruna: «Voto registrato, grazie!» +5 punti\n\n";

$finali = get_post_meta( $id, 'gs_sond_voti', true );
echo "  VOTI REGISTRATI DAVVERO NEL SONDAGGIO: " . json_encode( $finali ) . "\n";
echo "  punti dati: Anna " . ( $GLOBALS['PUNTI'][101] ?? 0 ) . ", Bruna " . ( $GLOBALS['PUNTI'][202] ?? 0 ) . "\n\n";

$persi = 2 - count( $finali );
echo $persi
  ? "  ✗✗ $persi voto su 2 e' SPARITO. Ad Anna e' stato detto che era registrato,\n     ha preso i 5 punti, e il suo voto non c'e'. Il sondaggio dara' un\n     risultato sbagliato che sembra giusto.\n"
  : "  ✓ nessun voto perso\n";

echo "\n  Con GET_LOCK su 'gs_sond_12' Bruna avrebbe aspettato Anna, riletto\n  l'elenco aggiornato e scritto sopra il voto di Anna invece che al suo posto.\n";
