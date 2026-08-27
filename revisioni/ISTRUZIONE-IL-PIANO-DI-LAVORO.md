# Istruzione: il Pannello «Piano di Lavoro»

**Per Claude Code Ennio 2 — 27/08/2026, scritta su 3.292.0**

Ennio ha approvato la proposta grafica. Disegno e anteprime:
**https://claude.ai/code/artifact/7bd9b363-938e-463e-8c9e-c778c13fa088**
(pagina «La proposta», tavole chiara e scura). Nel repository: `design/pannello/`.

**Questo non è un lavoro con una scadenza.** Viene dopo il reset, lo username fuori dalla rete, e le quattro voci dei trenta giorni. È scritto adesso perché la decisione è fresca, non perché vada fatto adesso.

---

## Le eccezioni, dette prima

Una parte di questo lavoro tocca **sei punti uguali in un file solo**, ed è la forma che ci ha morso tre volte. Quindi, prima delle istruzioni:

- **Non si tocca nessun pannello esistente.** Le 73 zone restano dove sono, con le loro funzioni: il Piano di Lavoro è **una schermata nuova che sta davanti**, non una riscrittura.
- **Non si tocca `gs_riepilogo_dati()`.** Serve ancora al cartellino della Plancia in `admin.php`, che si aggancia alle sue chiavi. Si aggiunge accanto, non al posto.
- **Non si toccano i permessi**: chi vede cosa continua a passare da `gs_sez_zona_ok()`. Una coda che il collaboratore non può gestire non deve comparirgli.
- **Se trovate un punto che vi sembra un'eccezione e non è qui, fermatevi e chiedete.** Probabilmente ho dimenticato qualcosa: è successo tre volte in due giorni.

---

## La cosa da capire prima di tutto

`gs_riepilogo_dati()` (`control-panel.php:539`) fa questo, quattordici volte:

```php
$ricette = count( gs_ricette_in_attesa() );
$testim  = count( gs_testim_in_attesa() );
$bio     = count( gs_bio_in_attesa() );
```

**Il pannello ha già le cose in mano, le conta, e le butta via.** Tiene il numero.

Tutto quello che serve al Piano di Lavoro — l'ordine per attesa, il pulsante acceso sul più vecchio, la pastiglia «e altre 2 cose sue» — **esce da quelle stesse funzioni**, se invece di contarle si guarda cosa contengono.

Questo è il lavoro. Il resto è disegno.

---

## 1. Una forma sola per tutte le code

```php
/**
 * Le voci di una coda, tutte con la stessa forma. È il pezzo su cui sta in
 * piedi tutto il Piano di Lavoro: l'ordine per attesa, il pulsante acceso
 * sul più vecchio e la pastiglia "e altre cose sue" non sono tre funzioni
 * diverse — sono tre modi di leggere QUESTA.
 *
 * Le funzioni che producono le voci esistono già tutte
 * (gs_get_pending_users, gs_ricette_in_attesa, gs_bio_in_attesa…):
 * gs_riepilogo_dati() le chiama, le conta e getta via il contenuto.
 * Qui il contenuto si tiene.
 */
function gs_coda_voci( $coda ) {
    // ritorna un array di:
    // array(
    //   'chi'    => (int) uid della sfoglina, o 0 se la voce non è di nessuno
    //   'titolo' => 'Rosa Camilli'            // il nome, o la cosa
    //   'sotto'  => 'Iscritta ma non ha mai caricato niente'
    //   'da'     => (int) timestamp di quando ha cominciato ad aspettare
    //   'verbo'  => 'Scrivi'                  // cosa fa il pulsante
    //   'tipo'   => 'avanti' | 'normale'      // 'avanti' = verde (Approva, Conferma)
    //   'vai'    => '#gs-box-iscrizioni'      // dove si va al clic
    // )
}
```

**`chi` e `da` sono i due campi che oggi non esistono da nessuna parte**, e sono quelli che fanno la differenza fra questo pannello e quello di adesso.

## 2. Da dove viene «da quanto aspetta» — e le tre code dove non c'è

Questa è la parte da guardare **prima di cominciare**, o se ne accorgerete a metà.

| coda | da dove | |
|---|---|---|
| Richieste di iscrizione | `$user->user_registered` | ✅ c'è |
| Ricette | `post_modified` del `gs_ricetta` | ✅ c'è — **`post_modified`, non `post_date`**: ogni modifica rimette in attesa, quindi conta l'ultima |
| Testimonianze | `post_modified` | ✅ c'è |
| Conversazioni in attesa | `post_date` del `gs_conversazione` | ✅ c'è |
| Messaggi senza risposta | `post_date` | ✅ c'è |
| Abbonamenti scaduti | `gs_abbonamento_scadenza` | ✅ c'è |
| **Biografie della Vetrina** | `gs_bio_stato = 'in_attesa'` | ❌ **nessuna data** |
| **Vetrine Artigiani** | `gs_art_stato = 'in_attesa'` | ❌ **nessuna data** |
| **Vetrine Scuole** | `gs_scu_stato = 'in_attesa'` | ❌ **nessuna data** |

Le ultime tre sono uno stato senza un quando: si sa **che** aspettano, non **da quando**.

### Come si aggiunge, e perché è la parte delicata

Ogni volta che lo stato diventa `'in_attesa'`, va scritta anche la data. **In `biografia.php` questo succede in sei punti** (righe 56, 78, 105, 284, 398 e una nel salvataggio dal pannello), in `artigiani.php` in due, in `scuole-cucina.php` in due.

```php
// Non basta lo stato: serve sapere DA QUANDO aspetta, o il Piano di Lavoro
// non può metterla in ordine né dire "da 3 giorni". Si scrive qui, insieme
// allo stato, e mai altrove — se le due scritture si separano, prima o poi
// una voce resta senza data e sparisce dall'ordinamento in silenzio.
update_user_meta( $uid, 'gs_bio_stato', 'in_attesa' );
update_user_meta( $uid, 'gs_bio_stato_da', current_time( 'timestamp' ) );
```

**Dieci punti in tre file, tutti uguali.** È esattamente la forma che ha prodotto la ricorsione: **non fatelo con una sostituzione in blocco.** Uno alla volta, e alla fine `prova.sh`.

**Per le voci già in attesa oggi** che non hanno la data: mostrate «in attesa» senza numero di giorni e mettetele **in fondo**, non in cima. Una voce senza data non deve fingere di essere nuova né di essere vecchia.

## 3. Le tre regole che escono gratis

Con `gs_coda_voci()` in mano, le tre cose nuove del disegno sono tre righe:

**L'ordine.** `usort` su `da`, crescente. La più vecchia in cima, sempre, in ogni coda.

**Il pulsante acceso — uno solo per coda.**

```php
// Solo la PRIMA voce di ogni coda ha il pulsante ambra: è la più vecchia.
// Se sono ambra tutti, l'ambra non vuol più dire niente e l'occhio non sa
// dove posarsi — è il difetto delle due bozze da cui questo disegno nasce.
// Sei pulsanti accesi in tutto lo schermo, uno per coda.
$acceso = ( 0 === $i );
```

Eccezione: le voci `'tipo' => 'avanti'` (Approva, Conferma) restano **verdi sempre**, anche quando non sono le prime. Il verde non è "guardami", è "questo porta avanti".

**La pastiglia «e altre 2 cose sue».**

```php
// Una sfoglina compare spesso in più code — nelle bozze di Ennio, Anna
// Ruggeri stava in tre (iscritti, pagamenti, mail) e nessuno lo diceva:
// tre righe in tre posti, e la si scrive tre volte o la si ignora tre volte.
// Si contano gli uid su TUTTE le code, una volta per pagina.
$quante = array_count_values( array_column( $tutte_le_voci, 'chi' ) );
```

Sulla voce si mostra la pastiglia solo se `$quante[$chi] > 1`, e al clic si aprono **le sue cose insieme**.

## 4. La schermata

Segue la tavola. Le misure che contano:

- **larghezza 1600**, tre colonne. Con 1280 i pulsanti a destra escono dal riquadro — è successo in tre delle quattro bozze di Ennio.
- **la barra scura è solo la barra**: identità, ricerca, stato del gaming. ADESSO sta sul piano chiaro. Un blocco scuro alto sopra un corpo chiaro è il contrasto che stanca, ed era la richiesta di partenza.
- **i riquadri non hanno bordo**: fondo chiaro e un'ombra morbida. Meno righe sullo schermo, meno rumore.
- **i nomi in Newsreader**, il carattere del titolo: fa somigliare il pannello al registro di un'accademia invece che a un gestionale.
- **⌘K sulla ricerca**, i numeri 1-7 accanto alle code per saltarci con la tastiera.

**ADESSO** è la cosa sola sopra tutte — la scadenza più vicina che richiede una mano. Da dove viene va deciso: il corso più prossimo con qualcosa di incompleto è un buon candidato, ma **prendetela come domanda per Ennio**, non decidetela voi.

## 5. «Tutto il resto» — dove vive la classificazione

Le 62 sezioni vanno raggruppate per **quanto spesso si aprono**: ogni giorno · ogni settimana · ogni tanto · si imposta e basta.

Quel raggruppamento è **una scelta, non un dato**: va in un campo nuovo del registro delle sezioni (`sezioni.php`, `gs_sez_registry()`), accanto a `livello` e `zona`:

```php
'ricettario' => array( …, 'uso' => 'giorno' ),
'backup'     => array( …, 'uso' => 'mai' ),
```

Chi non ce l'ha finisce in «ogni tanto». **Il primo giro lo fate voi leggendo i nomi; Ennio lo correggerà usandolo**, ed è giusto così — solo lui sa cosa apre davvero.

---

## Cosa NON fare

- **Non sostituire il pannello di oggi.** Il Piano di Lavoro è la schermata che si apre per prima; da lì si arriva a tutto il resto, che resta identico. Se un giorno il nuovo non basta, il vecchio è ancora lì.
- **Non fare le azioni "da qui" al primo giro.** Nel disegno i pulsanti portano alla sezione giusta. Farli agire sul posto (approva → la riga sparisce) è meglio, ma è un secondo giro: prima si vede se l'ordine e la scelta delle code funzionano davvero.
- **Non salvare i conteggi da nessuna parte.** Si calcolano quando si apre. Un numero salvato ieri racconta una cosa falsa oggi.
- **Non aggiungere code nuove.** Sei più «tutto il resto». Se ne servisse una settima, va tolta un'altra: sette code sono già il limite di quello che si guarda in un colpo.

## Come si prova

1. Una ricetta in attesa da tre giorni e una da ieri → **la vecchia è in cima**, e ha lei il pulsante ambra.
2. Una biografia messa in attesa oggi → mostra «oggi», non «in attesa» senza data.
3. Una biografia già in attesa da prima dell'aggiornamento → mostra «in attesa» e sta **in fondo**.
4. La stessa sfoglina con una ricetta e un pagamento aperti → **la pastiglia compare su tutte e due**, e dice il numero giusto.
5. Approvare la ricetta → **la pastiglia sull'altra voce sparisce** (adesso è sola).
6. Un collaboratore che non gestisce i pagamenti → **quella coda non gli compare.**
7. Svuotare una coda → il riquadro resta con «niente da fare qui», non sparisce: uno spazio vuoto che cambia posizione ogni giorno disorienta.
8. `prova.sh`.

## Il conto

| | |
|---|---|
| `gs_coda_voci()` per le sei code | una giornata |
| Le date mancanti nelle tre code (dieci punti, uno alla volta) | mezza giornata |
| La schermata | una giornata |
| Il campo `uso` sulle 62 sezioni | due ore |
| ADESSO | mezza giornata, **dopo** che Ennio ha detto cosa ci va |

**Circa tre giornate.** Ma la prima è quella che conta: fatta `gs_coda_voci()`, il resto è disegno, e il disegno c'è già.
