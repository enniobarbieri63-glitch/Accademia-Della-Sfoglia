# Verifica di gaming-sfogline 3.296.0 contro ISTRUZIONE-IL-RESET.md

**27/08/2026 — verifica riga per riga del pacchetto `gamingsfogline3.296.0.zip`.**

Il documento chiede una cosa sola, ed è quella su cui ho passato la maggior parte del tempo:
**l'elenco delle chiavi da tenere deve essere completo.** Una dimenticata lì dentro non dà
nessun errore, non compare in nessuna prova, e si vede il giorno dopo — quando si apre
l'account di una sfoglina e l'abbonamento non ha più la scadenza.

Per questo non ho letto l'elenco: ho **censito il plugin** e confrontato il censimento con
l'elenco. Sono due cose diverse. Rileggere una lista conferma quello che c'è; il censimento
trova quello che manca.

---

## Come ho verificato

1. **Chiavi meta utente.** Estratte tutte le chiavi `gs_` passate a
   `get_/update_/add_/delete_user_meta()` in tutti i 109 file del pacchetto, comprese quelle
   costruite a runtime per concatenazione e quelle definite come costanti PHP.
2. **Tipi di contenuto.** Estratte tutte le chiamate `register_post_type()` del plugin — non
   solo le sei di `cpt.php`, ma anche le altre trenta sparse negli altri file.
3. **Confronto** dei due censimenti con `gs_reset_meta_da_tenere()` e
   `gs_reset_tipi_da_tenere()`, e classificazione di **ogni singola voce** che finirebbe
   cancellata: dato di gioco, oppure no.
4. **Il resto del documento** (i tre casi particolari, i marcatori, la cache, il log, i due
   pulsanti, la parola digitata, la Parte 2 sullo username) letto punto per punto contro il
   codice.

---

## Quello che è a posto

- **L'elenco delle 34 chiavi da tenere è identico al documento**, carattere per carattere:
  nessuna riga saltata nel copiarlo, nessun gruppo perso. La verifica del documento contro il
  codice, in senso stretto, passa.
- **I conti del documento tornano.** Il plugin usa esattamente **63 chiavi meta fisse**
  scritte come stringhe letterali, più **10 prefissi costruiti a runtime**
  (`gs_points_mese_…`, `gs_points_anno_…`, `gs_points_…`, `gs_badge_dato_…`,
  `gs_badge_label_…`, `gs_buono_mese_…`, `gs_lezione_vista_…`, `gs_piatto_pagato_…`,
  `gs_sondaggio_proposta_pagata_…`, `gs_mail_benv_…`). Ho controllato i prefissi uno per uno:
  **sono tutti dati di gioco, nessuno va tenuto.** La scelta di fondo del documento — elenco
  di cose da tenere, mai di cose da cancellare — è quella giusta e regge.
- **I tre casi particolari** ci sono tutti: voti dei sondaggi svuotati senza cancellare il
  sondaggio; sfide aperte **segnalate** in anteprima e non decise dal codice; le tre opzioni
  segnaposto azzerate.
- **Le due correzioni al documento fatte dal codice sono giuste**, e le confermo:
  - `gs_sfida` aggiunto ai tipi da tenere. Il documento lo aveva dimenticato pur dicendo nel
    testo che le sfide vecchie non si toccano. Senza, si cancellava tutta la storia delle sfide.
  - `gs_mappa_territori_vincitrice` **non è un'opzione a sé**: vive dentro `GS_OPTION`
    (`mappa-squadre.php:161`). `delete_option()` non avrebbe trovato niente da cancellare.
- **Marcatore prima e dopo, `wp_cache_flush()`, `gs_reset_log`**: presenti e nell'ordine giusto.
- **Il cestino incluso nella cancellazione** (`'post_status' => array( 'any', 'trash' )`): la
  correzione trovata su guru2 è reale e importante, `'any'` in `WP_Query` esclude davvero il
  cestino.
- **Parte 2 (username fuori dalla rete): completa.** `helpers.php:857` usa `user_nicename`;
  i **tre** punti che leggono `?sfoglina=` (`shortcodes.php:1119`, `seo.php:111`,
  `seo.php:250`) cercano tutti per `slug`; nessun punto è rimasto a cercare per `login`.
  I `get_user_by('login', …)` rimasti sono quelli del login e della ricerca in amministrazione,
  e lì è corretto così. Lo slug alla registrazione c'è, con il ciclo delle omonime.
- **Versione** 3.296.0 nei tre punti (intestazione, `GS_VERSION`, `Stable tag`) più la voce di
  changelog. `gs_box_open( $title, $class, $id )`: la chiamata del pannello ora passa davvero
  la classe come secondo argomento.

---

## Da correggere prima di premere il pulsante

Tre cose. Tutte e tre della stessa famiglia: nessuna dà errore, nessuna si vede nell'anteprima.

### 1. `gs_archivio_gaming` — l'abbonamento e i token di chi è nel Cestino

**È esattamente lo scenario descritto nel documento, ed è l'unico punto in cui succede davvero.**

Quando una sfoglina finisce nel Cestino (stato `rifiutata`, `sospesa` o `eliminata`),
`gs_archivia_dati_gaming_utente()` (`sfogline-extra.php:40`) prende **tutti** i suoi meta `gs_`
tranne sette e li chiude dentro un unico meta, `gs_archivio_gaming`. I sette esclusi sono
`gs_status`, `gs_email_verificata`, `gs_email_verify_token`, `gs_genere`, `gs_birthdate`,
`gs_richiesta_cancellazione`. **Non ci sono l'abbonamento, la scadenza, i token, la Vetrina,
lo sconto e il testamento**: quelli finiscono dentro la scatola.

`gs_archivio_gaming` non è nell'elenco del Reset. Quindi il Reset **cancella la scatola intera**:

- abbonamento, scadenza e data di approvazione di quella persona: spariti;
- token comprati con bonifico: spariti;
- Vetrina pagata 49 euro (`gs_bio_*`): sparita;
- sconto guadagnato sui corsi, testamento: spariti;
- e il ripristino dal Cestino, dal giorno dopo, non riporta più niente perché non c'è più niente da riportare.

Peggio: quelle persone **non compaiono nella tabella dell'anteprima**. Il riepilogo per
sfoglina scorre `gs_sez_sfogline()`, che le sfogline nel Cestino non le contiene. La tabella
che il documento chiama giustamente «la parte importante di tutto il pannello» direbbe che è
tutto a posto — e sarebbe vero, per le sfogline che mostra.

Nota di contorno: l'archiviazione parte da sola, alla prima apertura dell'elenco del Cestino
(`sfogline-extra.php:573`). Non serve che qualcuno l'abbia chiesta.

> Una `sospesa` è una persona che ha pagato ed è sospesa per un motivo temporaneo. È la prima
> a cui si guarderebbe l'abbonamento, il giorno dopo.

### 2. `gs_corso_cal` e `gs_prenotazione` — il calendario dei corsi e gli acconti versati

Il documento elenca `gs_corso` fra i tipi da tenere, ma i corsi del calendario sono un altro
tipo. `calendario.php:18` registra **due** tipi che non sono nell'elenco:

- **`gs_corso_cal`** — una data di corso: `gs_data`, `gs_ora_inizio`, `gs_posti`, `gs_prezzo`,
  `gs_acconto`, `gs_descrizione`. La scrive il titolare dal pannello (`calendario.php:773`):
  è catalogo quanto una lezione.
- **`gs_prenotazione`** — la prenotazione di un cliente, con dentro
  **`gs_acconto_versato`, `gs_saldo_versato`, `gs_pagamenti_log`, `gs_pagamenti_rif`**
  (i riferimenti dei bonifici) e `gs_cal_attestato`.

Il Reset li cancella tutti e due, in via definitiva (`wp_delete_post( $id, true )`). È la stessa
cosa che il documento vieta per i token — «cancellare denaro versato» — solo che qui è denaro
versato per un corso vero con Rina, e insieme sparisce anche il calendario che lo vendeva.

### 3. Il pulsante è riservato al titolare nella pagina, ma non nella richiesta

`gs_pannello_reset()` controlla `current_user_can( 'manage_options' )`, e il commento sopra dice
la cosa giusta: *«titolare soltanto, non i collaboratori: è l'unica operazione del plugin che
non si annulla»*.

Ma i due AJAX controllano `gs_can_manage()`, che è
`manage_options` **oppure** `gs_manage_gaming` (`control-panel.php:24`) — cioè anche i
collaboratori. Il nonce `gs_ajax` ce l'hanno su ogni pagina del pannello. Una sola richiesta
POST, e il reset parte: il pulsante nascosto non è una protezione.

Vale per `gs_reset_esegui` e per `gs_nicename_applica` (che riscrive gli indirizzi pubblici di
tutti).

### Le correzioni, da incollare

```php
// gs_reset_meta_da_tenere(), dentro i gruppi esistenti:

		// --- Chi è. Non si tocca mai, per nessun motivo. ---
		… 'gs_telefono',            // vedi punto 4

		// --- La Vetrina pubblica: si paga a parte, 49 euro.
		… 'gs_vetrina_bloccata',    // vedi punto 5

		// --- La scatola di chi è nel Cestino. gs_archivia_dati_gaming_utente()
		// (sfogline-extra.php) ci chiude dentro TUTTI i meta gs_ di una sfoglina
		// sospesa o rifiutata, abbonamento, scadenza, token, Vetrina e sconto
		// compresi: cancellare questa chiave sola vuol dire cancellare tutti
		// quelli insieme, e il ripristino dal Cestino non riporta più niente.
		// Chi tocca uno dei due elenchi guardi anche l'altro:
		// gs_archivio_gaming_meta_esclusi() decide cosa finisce qui dentro.
		'gs_archivio_gaming',       // = GS_ARCHIVIO_GAMING_META
```

```php
// gs_reset_tipi_da_tenere():

		// Il calendario dei corsi e le prenotazioni: dentro ci sono acconti e
		// saldi già versati con bonifico (gs_acconto_versato, gs_saldo_versato,
		// gs_pagamenti_rif). Stessa ragione dei token: è denaro.
		'gs_corso_cal', 'gs_prenotazione',

		// Scritti dal titolare in wp-admin, non dalle sfogline: catalogo.
		'gs_barometro',        // le Guide Stagionali
		'gs_ingrediente',      // gli Ingredienti Segreti, anche quelli programmati
```

```php
// reset.php — nei due AJAX che cambiano qualcosa:
if ( ! current_user_can( 'manage_options' ) ) {
	// Non gs_can_manage(): quello comprende i collaboratori (gs_manage_gaming),
	// e questa è l'unica operazione del plugin che non si annulla.
	wp_send_json_error( array( 'message' => 'Permesso negato.' ) );
}
```

---

## Da correggere, meno urgente

**4. `gs_telefono`.** È il numero di telefono della persona, letto in `regia-iscritti.php:225`
e `:342` per i link WhatsApp. Non lo scrive il plugin: è inserito a mano, una volta. Cancellarlo
non rompe niente — il link semplicemente smette di comparire, in silenzio. È dato di contatto,
gruppo «Chi è».

**5. `gs_vetrina_bloccata`.** È il blocco amministrativo di una Vetrina pubblica
(`control-panel.php:1289`). Il Reset lo cancella mentre **tiene** il contenuto della Vetrina
(`gs_bio_*`, che sono nell'elenco): il giorno del reset, una Vetrina bloccata dal titolare
torna pubblica da sola. Decisione di moderazione, non punteggio.

**6. `gs_barometro` e `gs_ingrediente`.** Le Guide Stagionali si scrivono solo in wp-admin,
hanno una voce di menu propria («Guide Stagionali», `admin.php:45`) e un metabox: catalogo puro.
Gli Ingredienti Segreti si creano solo dal pannello, dietro `gs_can_manage()`, e possono essere
**programmati nel futuro** (`post_status => 'future'`): il Reset cancellerebbe anche quelli non
ancora usciti. Rientrano entrambi nella riga del documento «il catalogo che avete scritto voi».

**7. `gs_errore_didattico` promossi.** Li scrivono le sfogline, ma quelli con
`gs_stato = 'promosso'` sono materiale didattico ufficiale scelto dal titolare, pubblicato
nell'archivio pubblico («La Sfoglia che Insegna Se Stessa»). Oggi vengono cancellati insieme a
quelli in attesa. Se si vogliono tenere solo i promossi serve un caso particolare, come per i
sondaggi; se si tengono tutti, basta aggiungere il tipo all'elenco.

---

## Da decidere — e le decisioni sono di Ennio, non mie

**8. Le «Cose da Fare» (`gs_todos`, `gs_todos_cestino`).** Sono i promemoria personali che la
sfoglina si scrive da sola. Non sono punteggio. È lo stesso argomento della decisione 1 sul
Testamento — *«l'ha scritto lei»* — e il commento nel codice dice pure *«la regola resta la
stessa, niente cancellazione definitiva»*. Il Reset invece li cancella, insieme al loro cestino.

**9. Il Diario dell'Impasto (`gs_diario`) e i Consigli (`gs_consiglio`).** Scritti dalle
sfogline. Il documento tiene testamenti, ricette e letture con l'argomento «l'ha scritto lei,
non è un punteggio», ma non nomina questi due, che oggi vengono cancellati. Con contenuti
dimostrativi non cambia niente; se il reset venisse rifatto fra un anno, cambierebbe molto.

**10. Lo stato di gioco che resta attaccato ai contenuti tenuti.** Il documento ha visto il
problema per i sondaggi («il sondaggio riparte con i voti di prima e nessuno può più votare»),
ma non è l'unico caso:

| contenuto tenuto | cosa resta attaccato | cosa succede il giorno dopo |
|---|---|---|
| `gs_piatto` | `gs_custode_tipo`, `gs_custode_id`, `gs_custode_team` | **i piatti restano adottati** da sfogline che non hanno più niente, e `gs_piatto_adotta_uid()` risponde «Questo piatto ha già una custode» a chiunque provi ad adottarli — identico al caso dei sondaggi |
| `gs_lezione` | `gs_risposte` (una voce per sfoglina che ha risposto, con i punti assegnati), `gs_assegnazioni` | le risposte al quiz del gioco vecchio restano sotto le lezioni, con i punti che le sfogline non hanno più. *(`gs_domande` invece è il quiz scritto dai docenti: giusto tenerlo)* |
| `gs_corso` (Area Pro) | `gs_corso_utente`, `gs_compiti`, `gs_compiti_cestino`, `gs_diploma_rina`, `gs_diploma_data` | non è catalogo: è **un corso per sfoglina**, con i suoi compiti, le sue note e il feedback del docente. Resta tutto, diploma compreso — probabilmente è giusto (è un corso pagato, come lo sconto della decisione 2), ma va detto e non scoperto |
| `gs_lettura` | `gs_commenti`, `gs_commenti_cestino` | i commenti restano, mentre `gs_commenti_count` di chi li ha scritti torna a zero |

Il piatto è l'unico dei quattro che **blocca il gioco nuovo**: gli altri tre sono roba vecchia
che resta in vista. Il primo lo tratterei come i sondaggi (svuotare i tre meta del custode nel
Reset); sugli altri tre decide Ennio.

---

## Minori, da sapere e basta

- `gs_year_prize_assigned_*` viene cancellato solo per l'anno scorso, questo e il prossimo:
  le annate più vecchie restano. Innocuo.
- `gs_ultimo_accesso` e `gs_ultimo_accesso_timestamp` vengono cancellati: subito dopo il reset
  tutte le sfogline risultano «mai entrate» finché non rientrano.
- Le opzioni `gs_aereo_click_*` (una per notifica inviata) non le tocca nessuno: restano
  orfane nel database. Innocuo, ma crescono.
- `gs_reset_tipi_da_cancellare()` usa `get_post_types()`, quindi vede solo i tipi **registrati
  in quel momento**. È la scelta giusta — un tipo nuovo entra da solo — ma un tipo *tolto* dal
  plugin lascia i suoi contenuti nel database senza che nessuno li veda più.
- Parte 2: il parametro `?sfoglina=` viene ripulito con `sanitize_user()`, che toglie le
  sequenze percent-encoded. Con nomi italiani non succede mai (`sanitize_title()` toglie già gli
  accenti); con un nome in alfabeto non latino il `user_nicename` viene salvato percent-encoded
  e quella Vetrina non si troverebbe più. Da tenere a mente solo se un giorno si iscrive
  qualcuno con un nome così.

---

## Una prova che il prossimo mese non si dimentica

Le tre cose gravi sono tutte dello stesso tipo: **una cosa nuova aggiunta al plugin che nessuno
ha ricollegato all'elenco del Reset.** Il calendario dei corsi è arrivato dopo; l'archivio delle
sfogline nel Cestino è di dieci giorni fa. Nessuna prova le prende, perché non c'è niente che
non funzioni.

La regola del documento — elenco di cose da tenere, mai di cose da cancellare — è giusta per
*decidere*. Per *accorgersene* serve l'opposto: un controllo in `prova.sh` che elenchi **tutti**
i tipi `gs_` registrati e **tutte** le chiavi meta `gs_` presenti nel database, e fallisca se
ne trova uno che non è né nell'elenco da tenere né in un elenco esplicito di «questo si
cancella, ed è voluto». Chi aggiunge un tipo nuovo il mese prossimo non deve ricordarsi del
Reset: deve trovarsi una prova rossa che glielo ricorda.

---

## Ordine consigliato

1. Correggere i tre punti bloccanti (elenco meta, elenco tipi, permesso degli AJAX).
2. Decidere i punti 4–7 e i punti 8–10 con Ennio, sul documento, non a voce.
3. Rifare l'anteprima e **rileggerla su una sfoglina nel Cestino**, non solo sulle sette
   dell'elenco: è l'unico modo per vedere il punto 1 dall'esterno.
4. Backup, poi Parte 2, poi anteprima letta da Ennio, poi Reset, poi i sette account aperti
   a mano — come dice il documento.
