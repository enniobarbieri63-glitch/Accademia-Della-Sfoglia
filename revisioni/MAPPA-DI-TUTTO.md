# La mappa di tutto — cosa resta da fare, in ordine

**26/08/2026 — stato verificato su 3.292.0**

In questa cartella ci sono **63 documenti**. Questo è l'unico che serve leggere per sapere dove siamo: ogni voce dice cosa manca, quanto costa e **in quale documento sta il dettaglio**.

Ennio ha detto che i contenuti del sito sono dimostrativi e che non ci sono scadenze. **Restano vere solo due scadenze**, e sono in cima.

---

## ✅ Quello che è fatto e verificato

Non per compiacersi: perché nessuno lo rifaccia.

| | |
|---|---|
| **I difetti sui punti** — piatto (60→20 punti), diario (90→15), sondaggi, Madrina & Allieva (50→10), token rimborsato due volte in tutte e due le forme | tutti provati eseguendo il codice |
| **Il modulo Newsletter tolto** | zero orfani, verificato con due metodi |
| **Il cancello dei trenta giorni**, metà: lo stato `gs_sfoglina_congelata()`, i 30 giorni scritti all'approvazione, il cancello dei punti, 25 pagine, 38 handler AJAX con 9 eccezioni, il pannello Abbonamenti | |
| **La batteria di prove** in `revisioni/prove/` — sintassi, funzioni mancanti, JavaScript ↔ PHP, ricorsione | si rilancia con `./prova.sh` |

---

## 🔴 A — Le due cose che dopo le iscrizioni non si possono più fare

**Sono le uniche con una scadenza vera.** Non perché il codice peggiori, ma perché il giorno in cui arrivano le sfogline si chiudono da sole.

### A1 · Il reset
Non esiste nessuna funzione. Dopo, resetteresti i dati che loro hanno appena inserito.
**Oggi ci sono sette account e li si può aprire tutti e guardare. A ottobre no.**
→ `ISTRUZIONE-IL-RESET.md` · **mezza giornata**

### A2 · Lo username fuori dalla rete
`helpers.php:854` mette ancora `user_login` nell'indirizzo pubblico della Vetrina.
Dopo, quei link sono su WhatsApp e su Google: cambiarli vuol dire romperli tutti.
→ `ISTRUZIONE-IL-RESET.md`, parte 2 · **due ore**

**Vanno fatte nella stessa giornata, in quest'ordine: backup → A2 → prova a vuoto → A1 → aprire i sette account e guardare.**

---

## 🟠 B — I trenta giorni: l'altra metà

Tutto specificato, niente di sorprendente rimasto.

| | cosa | dove | quanto |
|---|---|---|---|
| **B1** | I due pannelli dei partner ancora chiusi a un artigiano che è anche socio | `CANCELLO-DUE-PORTE-DI-TROPPO.md` | **due righe** |
| **B2** | Le email di scadenza riscritte — **adesso sono l'unico posto dove una congelata legge come rientrare**, perché «La Mia Sfoglia» si chiude | `TRENTA-GIORNI-IL-CANCELLO.md` §A6.5 | mezza giornata |
| **B3** | I quattordici agganci del cron: streak che si azzera, «hai fatto 0 punti», promemoria di lezioni chiuse | `TRENTA-GIORNI-IL-CANCELLO.md` §A5 | mezza giornata |
| **B4** | Gli undici testi «prima paghi, poi entri» | `TRENTA-GIORNI-IL-CANCELLO.md` §A4 | mezza giornata |
| **B5** | La mail di benvenuto finale, con le due date della prova | `TRENTA-GIORNI-IL-CANCELLO.md` | un'ora |
| **B6** | `gs_msg_non_partecipa()` — la frase di rifiuto in un posto solo invece di quaranta | `ISTRUZIONE-GUARDA-E-COMMENTA.md` §2 | mezz'ora |

**B1 e B6 conviene farli subito**: B1 perché è un difetto e sono due righe, B6 perché il momento buono per accorpare una frase ripetuta quaranta volte è quando le quaranta sono appena state toccate.

**B2 va fatto dopo aver deciso su C4** (sotto), o va riscritto due volte.

---

## 🟡 C — Le cose nuove decise

| | cosa | dove | quanto |
|---|---|---|---|
| **C1** | **Il modulo Sponsor** — oggi i tre sponsor sono scritti nel codice, con i loghi dentro il plugin che spariscono a ogni aggiornamento. Include **N2**, il difetto del nastro | `MODULO-SPONSOR.md` | una giornata |
| **C2** | Il prezzo di listino nelle impostazioni (500 partner, 500 sponsor, 49 sfoglina, 29 quota) | qui sotto | mezz'ora |
| **C3** | **«Guarda e commenta»** — chi non ha pagato vede le pagine e commenta | `ISTRUZIONE-GUARDA-E-COMMENTA.md` | un giorno |
| **C4** | **Il voto che conta a parte** — vedi la specifica qui sotto | qui sotto | due ore |

### C2 — Il listino, in una riga

In `helpers.php`, fra i valori predefiniti, e una tabellina nel pannello Impostazioni:

```php
'listino' => array(
    'quota'            => '29,00',   // già esiste come importo_quota: spostatelo qui
    'vetrina_sfoglina' => '49,00',
    'vetrina_partner'  => '500,00',
    'nastro_sponsor'   => '500,00',
),
```

Serve a due cose: comparire nei testi pubblici, e presentarsi **già scritto** nel campo quando si registra un bonifico, così non si ridigita ogni volta. **Attenzione a `importo_quota`**: è già usato in una decina di punti, quindi o lo spostate e aggiornate tutti, o lasciatelo dov'è e aggiungete solo gli altri tre. La seconda è più prudente.

### C4 — Il voto di chi guarda conta a parte

Ennio ha chiesto che chi guarda possa **anche votare**. Il problema non è tecnico: **i voti decidono chi vince un premio vero**, quindi chi non ha versato il contributo concorrerebbe a decidere a chi va il premio dei soci.

La soluzione approvata: **vota, il voto si vede, ma conta a parte.**

Il voto è già registrato con l'identità di chi lo dà, quindi non serve nessun dato nuovo — basta contarli in due gruppi al momento di leggerli:

```php
/**
 * I voti di una sfoglia, divisi in due: chi è in regola col contributo e chi
 * sta guardando. Il premio si assegna sul primo numero (Ennio, 26/08/2026).
 * Nessun dato nuovo: il voto porta già con sé chi l'ha dato, si contano
 * diversamente al momento di leggerli. Così chi guarda partecipa alla festa
 * senza che una sfoglina che ha pagato si senta scavalcata.
 */
function gs_voti_divisi( $post_id ) {
    $socie = 0; $community = 0;
    foreach ( gs_voti_di( $post_id ) as $uid => $voto ) {
        if ( gs_sfoglina_congelata( $uid ) ) { $community++; } else { $socie++; }
    }
    return array( 'socie' => $socie, 'community' => $community );
}
```

Sotto ogni sfoglia si mostrano tutti e due — *«24 voti delle socie · 6 dalla community»* — e **la classifica e i premi usano solo il primo**.

**Una cosa da controllare e non da dare per scontata:** chi conta i voti oggi (`gs_leaderboard()`, la chiusura delle sfide, `gs_award_challenge_prizes()`) deve passare al numero delle socie, **tutti e tre**. Se uno resta sul totale, la classifica dice una cosa e il premio ne fa un'altra — ed è il genere di errore che si scopre il giorno della premiazione.

**Il calcolo si fa al momento della lettura**, non salvando un contatore: lo stato di una sfoglina cambia (paga, rientra), e un contatore salvato ieri direbbe una cosa falsa oggi.

---

## ⚪ D — I difetti trovati e non ancora corretti

Nessuno urgente. In ordine di quanto danno fanno.

| | cosa | dove |
|---|---|---|
| **D1** | **Il Premio di Fine Anno non registra chi ha vinto**: si salva solo la data, i nomi si perdono. Una riassegnazione può premiare persone diverse, e fra un anno nessuno sa più chi ha vinto | `LETTURA-2-BLOCCO-7-FINE.md` |
| **D2** | **«Il Tuo Anno» mescola due contatori**: mostra i punti dell'anno solare e la posizione calcolata sull'anno di gioco. Il numero che la sfoglina vede non è quello che vince il premio | `LETTURA-2-BLOCCO-7-FINE.md` |
| **D3** | Indovinello: doppia risposta = doppi punti (5 al giorno) | `LETTURA-2-BLOCCO-6-PUNTI.md` |
| **D4** | Indovinello: aggiungere una domanda a metà giornata cambia quella di oggi, e il riepilogo mostra la risposta sbagliata | idem |
| **D5** | Missioni e streak: legge-modifica-riscrive, stessa famiglia, danno piccolo | idem + blocco 7 |

**D1 e D2 hanno una data**: il primo anno di gioco vero si chiude il **13 dicembre**. Prima di allora vanno fatti tutti e due, o la premiazione arriva con un elenco che non esiste e un numero che non torna.

---

## Le decisioni ancora aperte per Ennio

Tre, e sono rimaste indietro fra una cosa e l'altra.

1. **MailPoet nel menu delle sfogline.** Togliere la Newsletter è stato giusto — quel modulo non mandava niente. Ma adesso una sfoglina non ha più nessun posto dove chiedere di ricevere le tue novità. **Se le newsletter le mandi davvero con MailPoet**, lì va messo il modulo di iscrizione vero (cinque minuti, MailPoet dà uno shortcode). Se non le mandi, va bene così.

2. **Il pari merito nelle sfide.** Avevi detto *«fanno una ulteriore sfida»*. Restano tre domande: se anche lo spareggio finisce pari, chi non partecipa allo spareggio cosa succede, e se lo spareggio dà punti suoi. → `PARI-MERITO-SPAREGGIO.md`

3. **La vetrina del partner che va offline quando la modifica**. Oggi ogni modifica torna «in attesa di approvazione» e la vetrina sparisce dal pubblico finché non la riapprovi. Per chi ha pagato 500 euro è ruvido: cambia un numero di telefono e sparisce dal sito. → `LETTURA-2-BLOCCO-3-PARTNER.md`, P4

---

## L'ordine che consiglio

**Oggi:** A1 + A2, nella stessa giornata. Sono le uniche che si chiudono da sole.

**Poi, in una settimana:** B1 e B6 (mezz'ora in tutto) → C4 (perché B2 dipende da lui) → B2, B3, B4, B5.

**Poi, con calma:** C1 (che chiude anche N2) → C3 → C2 → D1 e D2 prima di dicembre → D3, D4, D5 quando capita.

**E in mezzo, mai saltata: `./prova.sh` prima di ogni consegna.** Dieci secondi, e prende l'intera classe di errori che nasce quando si toglie o si rinomina qualcosa — che è esattamente quello che il reset e il modulo sponsor stanno per fare.

---

## Una nota sul metodo, per chi legge fra sei mesi

Tre volte in due giorni ho dato una regola universale dimenticando un'eccezione che avevo già riconosciuto altrove: la cache dei meta dei post, i pannelli dei partner, gli handler ancora aperti. Ogni volta il danno è stato piccolo perché qualcuno ha provato invece di fidarsi.

Da qui due abitudini che valgono più di qualunque documento in questa cartella:

**Le eccezioni si scrivono in cima, non in fondo.**

**E quando un documento dice «cambiate tutti questi punti allo stesso modo», la domanda giusta prima di cominciare è: quali sono le eccezioni?** Anche se il documento non le nomina. Soprattutto se non le nomina.
