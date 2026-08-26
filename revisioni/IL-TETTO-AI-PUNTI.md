# Il tetto ai punti: la decisione, e i numeri che ci stanno sotto

**Per Ennio e per Claude Code Ennio 2 — 26/08/2026 — misurato su 3.284.4**

Ennio mi ha chiesto di decidere io il tetto del diario, in base ai test. **Non ho un sito su cui far girare niente**: quello che ho fatto è aritmetica sui valori veri che stanno nel codice. Lo dico subito perché «test» non voglia dire più di quello che è.

Cercando i numeri ho trovato due cose che cambiano la domanda.

---

## Prima: una cosa che avevo detto male nel blocco 6

Avevo scritto che il diario *«non ha nessun tetto»* e che «venti voci in un pomeriggio sono 300 punti».

**Un tetto c'è.** `forms.php:23` e `forms.php:91` chiamano tutti e due `gs_antispam_check()`, e dentro (`antispam.php:47`) c'è un limitatore vero:

```php
'min_seconds'   => 3,    // secondi minimi fra il caricamento della pagina e l'invio
'max_per_hour'  => 10,   // invii massimi per IP nell'arco di un'ora
```

Dieci invii all'ora, per indirizzo internet, per ogni tipo di modulo. Quindi venti voci in un pomeriggio **non passano**: passano dieci in un'ora, poi bisogna aspettare.

Non cambia la conclusione, ma cambia la misura, e la misura è il motivo per cui Ennio mi ha chiesto di decidere. Rifaccio i conti da capo.

---

## Quanto vale una giornata onesta

Questi sono i valori veri (`helpers.php:87-110` e `helpers.php:123-127`), non stime:

| Cosa fa una sfoglina in una giornata normale | Punti |
|---|---|
| Vota tre sfoglie (5 × 3) | 15 |
| Commenta la sfida della settimana | 5 |
| Scrive una voce nel Diario | 15 |
| Condivide un consiglio | 20 |
| Indovina la Sfoglia, risposta esatta | 5 |
| Una foto al Tavolo di Lavoro | 5 |
| Le quattro missioni del giorno (10+15+20+5) | 50 |
| **Totale di una giornata piena e onesta** | **115** |

Trenta giorni così fanno **3.450 punti**, contro i **2.500** che servono per il Buono Sfoglia del mese (`buono-sfoglia.php:85`). Il gioco è tarato bene: chi gioca davvero tutti i giorni vince, con un margine ragionevole. Questo non va toccato.

## Quanto vale un pomeriggio disonesto

Con il limitatore attuale, sempre numeri veri:

| Via | Punti all'ora |
|---|---|
| Diario, 10 voci all'ora × 15 | **150** |
| Consigli, 10 all'ora × 20 (è un contesto separato, il conto riparte) | **200** |
| **Insieme** | **350** |

I 2.500 punti del mese si fanno in **sette ore di digitazione** spalmate su trenta giorni — **quattordici minuti al giorno**. Senza impastare mai.

E il limitatore ha due difetti che a settembre peseranno:

**È per indirizzo internet, non per persona.** Madre e figlia iscritte tutte e due, stessa casa, stesso wifi: si mangiano il budget a vicenda e si bloccano fra loro. Con quaranta sfogline capita a qualcuna; con quattrocento capita ogni settimana, e arriva come una segnalazione di malfunzionamento.

**Si aggira senza saperlo.** Il telefono staccato dal wifi è un altro indirizzo, e il conto riparte da zero. Non serve malizia: basta uscire di casa.

Il limitatore è nato per tenere fuori i robot, e per quello va benissimo. **Non è un tetto di gioco, ed è sbagliato usarlo come tale.**

---

## La decisione

**I punti si prendono una volta al giorno per ogni fonte. Scrivere resta libero.**

Il Diario paga 15 punti alla prima voce della giornata; dalla seconda in poi la voce si salva, si legge, resta nel Diario, e non dà punti. Lo stesso il Consiglio, con i suoi 20.

**Non è una regola che invento adesso: è quella che ha già scelto Ennio.** Sul Tavolo di Lavoro ha detto, parole sue:

> *«foto e punti su una sola foto, il numero di foto caricato non influisce sul punteggio»*

Il Diario è la stessa identica forma: una cosa che si scrive quando viene, che deve restare libera, e che non deve diventare un modo per salire in classifica. Applicare due regole diverse a due cose uguali è esattamente la confusione che Ennio ha chiesto di non creare in «La Mia Sfoglia». **Una regola sola, valida ovunque, che si dice in una riga:** *«i punti arrivano una volta al giorno, poi scrivi quanto ti pare.»*

**Perché una e non due.** Ci ho pensato: due voci al giorno lascerebbero respiro a chi la sera si ricorda una cosa in più. Ma il tetto a uno coincide con l'obiettivo della missione — *«Aggiungi una voce al Diario»*, obiettivo 1 (`helpers.php:125`) — e coincide con il Tavolo. Tre cose che dicono lo stesso numero si ricordano; tre numeri diversi no.

**Cosa cambia per chi gioca onesto: niente.** La giornata piena qui sopra usa già una voce di diario e un consiglio. Il tetto non la tocca in nessun punto.

**Cosa cambia per chi vuole scavalcare:** le due vie da 350 punti all'ora diventano 35 punti al giorno, e non c'è più niente da spremere.

### Come si scrive

Non un contatore nuovo. Il plugin ha già lo schema giusto in tre posti (`indovina.php:85`, le missioni, il Tavolo): **si confronta una data, e se è cambiato il giorno si riparte.**

```php
// I punti del Diario si prendono UNA VOLTA AL GIORNO — la stessa regola che
// Ennio ha scelto per le foto del Tavolo di Lavoro ("il numero di foto
// caricato non influisce sul punteggio"). Scrivere resta libero: la voce si
// salva sempre, cambia solo se paga. Senza questo, Diario e Consigli insieme
// valevano 350 punti l'ora, cioè i 2.500 punti del Buono Sfoglia in quattordici
// minuti al giorno senza mai impastare (misurato il 26/08/2026).
$oggi   = current_time( 'Y-m-d' );
$ultimo = get_user_meta( $user_id, 'gs_diario_punti_il', true );
if ( $ultimo !== $oggi ) {
	update_user_meta( $user_id, 'gs_diario_punti_il', $oggi );   // contrassegno PRIMA
	gs_add_points( $user_id, gs_get_points_value( 'voce_diario', 15 ), 'Voce di diario aggiunta' );
}
```

Uguale per il consiglio, con `gs_consiglio_punti_il`.

**Due cose da non sbagliare:**

- Il messaggio di risposta (`forms.php:60` e `:128`) oggi dice sempre *«+15 punti!»*. Dalla seconda voce non è più vero, e una promessa scritta che non si avvera è peggio del tetto stesso. Deve dire *«Voce salvata. I punti del Diario li hai già presi oggi — torna domani.»*
- Il contrassegno va scritto **prima** di `gs_add_points()`, non dopo. È la regola nata dalla chiusura di luglio, e qui serve davvero: senza, due invii ravvicinati pagano tutti e due.

---

## Ma il buco vero non era il diario

Mentre cercavo il limitatore ho controllato quali altre vie ai punti non ne hanno nessuno. **`gs_ajax_piatto_adotta` non chiama `gs_antispam_check()` per niente**, e sotto c'è questo (`piatti-estinzione.php:125-142` e `:146-158`):

```php
// adottare
if ( get_post_meta( $pid, 'gs_custode_tipo', true ) ) { return 'ha già una custode'; }
update_post_meta( $pid, 'gs_custode_tipo', 'sfoglina' );
gs_add_points( $uid, 20, 'Hai adottato un piatto…' );      // ← +20

// rinunciare
delete_post_meta( $pid, 'gs_custode_tipo' );                // ← il posto torna vuoto
delete_post_meta( $pid, 'gs_custode_id' );
```

**Da nessuna parte è scritto che quel piatto le è già stato pagato.** Adotta: +20. Rinuncia: niente. Adotta di nuovo: **+20 un'altra volta.** Sullo stesso piatto, all'infinito, due clic per giro, senza nessun limitatore di mezzo.

I 2.500 punti del mese sono **125 giri**. Qualche minuto.

È lo stesso difetto della mini-missione Madrina & Allieva del blocco 6 (M2), nello stesso identico punto cieco: `custode_tipo` dice **com'è adesso**, non dice **cosa è già successo**. E la correzione è la stessa — un contrassegno che non si toglie mai:

```php
// Il piatto si paga UNA VOLTA SOLA per persona. Il meta gs_custode_tipo dice
// chi è la custode adesso e viene cancellato dalla rinuncia: da solo non
// impedisce di adottare, rinunciare e riadottare per 20 punti a giro
// (trovato 26/08/2026, stesso punto cieco di M2 in madrina.php).
$gia_pagato = 'gs_piatto_pagato_' . (int) $pid;
if ( ! get_user_meta( $uid, $gia_pagato, true ) ) {
	update_user_meta( $uid, $gia_pagato, current_time( 'mysql' ) );   // contrassegno PRIMA
	gs_add_points( $uid, gs_get_points_value( 'piatto_adottato', 20 ), '…' );
}
```

Va fatto **insieme a PE1** (le due custodi per lo stesso piatto, blocco 6): si tocca la stessa funzione, e ha senso una volta sola.

**Priorità: questo prima del diario.** Il diario è una scelta di gioco; questo è un difetto.

## E una terza, più piccola

`gs_ajax_sondaggio_proponi` (`sondaggi.php:246`) dà 10 punti a proposta e controlla solo che il **testo** non sia già presente (riga 274). Dieci proposte diverse nello stesso sondaggio sono 100 punti. Il limitatore antispam qui c'è (contesto `sondaggio_proposta`), quindi il tetto è 10 all'ora — non infinito, ma comunque 100 punti all'ora da una sola pagina.

Stessa cura, unità diversa: **i punti della proposta si prendono una volta per sondaggio**, non una volta al giorno. Contrassegno `gs_sondaggio_proposta_pagata_{id}`.

---

## Quello che resta da decidere a Ennio

Una cosa sola, e non è tecnica: **il limitatore antispam va portato da 10 all'ora per indirizzo a un numero per persona.** Con quaranta sfogline il conto per indirizzo regge; con quattrocento, due sfogline sulla stessa linea di casa cominciano a bloccarsi a vicenda e ti arriva come «il sito non mi fa scrivere».

Non lo cambio io perché è un compromesso fra due rischi opposti — troppo stretto blocca le persone vere, troppo largo riapre la porta ai robot — e la scelta dipende da quante sfogline Ennio si aspetta e da quanto teme lo spam. **Il tetto giornaliero qui sopra funziona comunque, anche senza toccare questo.**

---

## In sintesi

| | Cosa fare | Perché | Quando |
|---|---|---|---|
| **Piatti in estinzione** | contrassegno per persona+piatto | difetto: punti infiniti, senza limitatore | **subito, insieme a PE1** |
| **Proposte nei sondaggi** | contrassegno per persona+sondaggio | 100 punti l'ora da una pagina sola | con PE1 |
| **Diario e Consigli** | punti una volta al giorno, scrittura libera | la regola che Ennio ha già scelto per il Tavolo | prima del 1° ottobre |
| **Limitatore antispam** | da «per indirizzo» a «per persona» | a settembre le sfogline sulla stessa linea si bloccano fra loro | **decide Ennio** |

Niente di tutto questo tocca il documento dei trenta giorni, e niente di tutto questo va prima di quello.
