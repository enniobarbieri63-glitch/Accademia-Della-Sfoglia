# Verifica di gaming-sfogline 3.297.0

**27/08/2026 — controllo del pacchetto consegnato dopo `ISTRUZIONE-LE-CORREZIONI-AL-RESET.md`.**

Confronto fatto sul pacchetto intero, non sulle righe dichiarate: `3.296.0` contro `3.297.0`.

## Cosa è cambiato davvero

Quattro file, nessun altro: `includes/reset.php`, `assets/js/gaming.js`,
`gaming-sfogline.php`, `readme.txt`. Nessun file aggiunto, nessuno tolto, nessuna modifica
di contorno arrivata per sbaglio.

## Le tre bloccanti

| | verificato |
|---|---|
| **La scatola del Cestino** | Tutte e tre le parti. `gs_archivio_gaming` è nell'elenco da tenere; il passo `1b` ripulisce il contenuto con `array_intersect_key()` sulla stessa lista, e conta le scatole; l'anteprima ha `gs_reset_meta_o_archivio()`, `gs_reset_riepilogo_cestino()`, la chiave `cestino` e la tabella nel JS. |
| **Calendario e prenotazioni** | `gs_corso_cal` e `gs_prenotazione` fra i tipi da tenere, con il commento sul denaro versato. |
| **Il permesso** | `current_user_can( 'manage_options' )` su **tutti e quattro** gli handler AJAX, non solo sui due distruttivi. |

## Le quattro minori

`gs_telefono` nel gruppo «Chi è», `gs_vetrina_bloccata` nel gruppo della Vetrina,
`gs_barometro` e `gs_ingrediente` fra i tipi da tenere. Tutte e quattro con il commento che
dice il perché.

## Il controllo di classificazione — verificato in modo indipendente

Ho rifatto il censimento sul pacchetto nuovo, senza fidarmi del conteggio dichiarato:

- **36 tipi `gs_` registrati** (6 in `cpt.php`, 2 in `calendario.php`, 28 sparsi negli altri file);
- **23 da tenere + 13 da cancellare-voluti = 36**;
- **nessun tipo non classificato**, nessuno elencato ma non registrato, nessuno in tutti e due gli elenchi.

La riga rossa dell'anteprima esiste, ed è in cima al risultato, dove serve.

Censimento delle chiavi meta rifatto allo stesso modo: l'elenco da tenere è passato da 34 a 37
voci, e quello che resta cancellabile sono tutti dati di gioco — più `gs_todos` e
`gs_todos_cestino`, che sono la **decisione A**, giustamente lasciata a Ennio.

## Le decisioni sono rimaste sospese

Nessuna traccia del codice dei piatti, nessuna aggiunta di `gs_todos`, `gs_diario`,
`gs_consiglio`, `gs_errore_didattico`. Le quattro decisioni sono citate nel changelog e
lasciate aperte, come chiedeva il documento.

## Controlli formali

`php -l` pulito su `reset.php` e `gaming-sfogline.php`; `node --check` pulito su `gaming.js`.
Versione 3.297.0 nei tre punti, changelog scritto con dentro **cosa sarebbe successo**, non
«corretti alcuni bug».

---

## Una cosa da correggere, e viene dal mio snippet

Nel passo `1b`, quando la scatola ripulita resta **vuota** — una sfoglina nel Cestino che non
aveva nessuna delle chiavi da tenere, per esempio una registrazione rifiutata subito — il
codice scrive comunque `update_user_meta( …, array() )` e lascia in giro una scatola vuota.

Due conseguenze, piccole ma reali:

1. `gs_ripristina_dati_gaming_utente()` esce subito su un archivio vuoto **senza cancellare la
   chiave**: quella persona continuerebbe a comparire per sempre nella tabella nuova
   dell'anteprima, anche dopo essere stata ripristinata.
2. `gs_utenti_sfogline_cestino()` decide se archiviare con `'' === get_user_meta( … )`, e un
   array vuoto non è `''`: se quella persona tornasse nel Cestino, **non verrebbe più
   archiviata**.

Una riga:

```php
		if ( $ripulita ) {
			update_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META, $ripulita );
		} else {
			delete_user_meta( $uid_arch, GS_ARCHIVIO_GAMING_META );   // scatola vuota: si toglie, non si lascia in giro
		}
		$scatole_ripulite++;
```

## Due cose da sapere

- Il changelog cita `tests/test-reset.php`, ma **nel pacchetto non c'è nessuna cartella
  `tests/`** — né in 3.296.0 né in 3.297.0. Se il test vive fuori dalla cartella del plugin
  (accanto a `prova.sh`) va bene così; è da controllare nel repository, non si vede da qui.
- `scatole_ripulite` viene scritto nel log ma non compare da nessuna parte: né nel messaggio
  di fine reset, né nella «Cronologia dei reset già fatti» del pannello, che stampa solo righe
  di dati e contenuti. Una parola in più su quella riga e Ennio vede anche quel numero.

## Resta come prima

La verifica dal vivo — l'anteprima aperta su guru2 con una sfoglina davvero sospesa dentro il
Cestino — non si vede dal pacchetto. È la prova 1 delle cinque, ed è quella che conta: è
l'unico modo per guardare da fuori la cosa che il Reset non perdona.
