# I trenta giorni, il «congelato», e il pannello delle mail

**Ennio, 26/08/2026.** Quattro risposte e una richiesta nuova. Ho verificato le prime nel
codice — **e su «congelato» c'è qualcosa che non torna, ma non dove ci si aspetta.**

---

# 1 · I trenta giorni partono dall'approvazione — con una cosa da chiarire

> *«i trenta giorni partono dal momento dell'approvazione della sfoglina quindi legata al
> bonifico e non dal momento della registrazione»*

**La scelta è chiara ed è quella giusta:** l'approvazione resta manuale, e i trenta giorni
partono da lì. Così nessuna perde giorni mentre aspetta.

**Ma «legata al bonifico» ha due letture, e una delle due rovinerebbe tutto.** Nel sistema di
oggi l'approvazione *è* legata al bonifico — è la sua funzione attuale. Se restasse così, la
sfoglina dovrebbe **pagare per ottenere i trenta giorni gratis**, che è l'opposto di quello
che hai detto due righe dopo:

> *«Per entrare nel sito non si paga, è gratis»*

**Io leggo così, e mi baso sulla tua stessa frase:**

| Momento | Chi fa cosa | Soldi |
|---|---|---|
| **Iscrizione** | la sfoglina compila | **nessuno** |
| **Approvazione** | **tu** guardi ed approvi — «sì, è una persona vera, benvenuta» | **nessuno** |
| **Da qui partono i 30 giorni** | automatico | — |
| **Giorno 30** | l'accesso si chiude **da solo** | — |
| **Riattivazione** | **tu** controlli il bonifico e riapri | **29 €** |

**Quindi il manuale resta in due punti** — esattamente i due che hai detto: *«controlliamo
solo il bonifico e l'attivazione della sfoglia all'area riservata»*. Il primo di quei due,
però, **non è un pagamento: è un benvenuto.**

**Se invece intendevi che serve un bonifico anche per entrare, dimmelo subito** — perché
allora la mail va riscritta da capo e i «trenta giorni in regalo» non sono un regalo.

---

# 2 · «Congelato significa salvato» — i dati sono al sicuro. È quello che le succede intorno che non lo è.

**Per i dati hai ragione e non serve fare niente:** il plugin non cancella niente da solo.
Foto, ricette, biografia, diario, punti, badge, avanzamento nei percorsi — **restano dove
sono**, e alla riattivazione sono tutti lì.

**Ma «salvato» per una persona non vuol dire «i byte sono ancora nel database».** Vuol dire
*«ritrovo le cose come le avevo lasciate»*. E qui il codice non mantiene la promessa in tre
punti — **non perché cancelli qualcosa, ma perché continua a lavorare su di lei mentre lei
non c'è.**

## a) Lo streak le si azzera mentre è fuori — VERIFICATO

`streak.php`, `gs_check_streaks()`, che gira **ogni notte**:

```php
	$users = get_users( array(
		'meta_key'     => 'gs_streak',
		'meta_compare' => 'EXISTS',
		'number'       => -1,
	) );
```

**Prende tutte le sfogline che hanno uno streak. Nessun filtro sullo stato dell'abbonamento.**

Quindi una sfoglina congelata:

1. la prima settimana **brucia uno scudo salva-streak** (e riceve pure l'aeroplanino *«IL TUO
   SCUDO HA SALVATO LA STREAK!»*, mentre non può entrare);
2. finiti gli scudi, **`gs_streak` va a zero**.

Torna dopo due mesi, versa i 29 €, e **le dodici settimane consecutive che aveva sono
sparite** — insieme agli scudi che aveva guadagnato in quattro mesi. **Nessuno le ha
cancellato niente: gliele ha consumate il calendario.**

## b) Riceve il resoconto di fine mese — ed è il difetto di luglio, di nuovo

Il 1° del mese la chiusura scrive a tutte le sfogline: *«a [mese] hai totalizzato N punti…
mancavano X»*.

**Una congelata riceve «hai totalizzato 0 punti»** — per un mese in cui non poteva entrare.

**È esattamente il messaggio di luglio**, quello andato alle sei: un resoconto di una gara a
cui la persona non ha partecipato. **Lo abbiamo già fatto una volta.**

## c) Riceve i promemoria delle lezioni non viste

`lezioni-video.php`, `gs_lezioni_promemoria_non_viste()`, sul cron giornaliero: le ricorda le
lezioni che non ha ancora guardato. **Lezioni che, congelata, non può aprire.**

## Cosa serve, ed è poco

**Una funzione sola, e tre righe messe in tre posti:**

```php
/**
 * True se la sfoglina è "congelata": trenta giorni finiti e contributo non
 * ancora versato. Congelato vuol dire che ritrova tutto com'era — quindi
 * niente di automatico deve continuare a lavorarle intorno mentre è fuori:
 * lo streak non cala, i resoconti non partono, i promemoria non arrivano
 * (Ennio, 26/08/2026: "congelato significa salvato").
 */
function gs_sfoglina_congelata( $uid ) {
	return function_exists( 'gs_abbonamento_scaduto' ) && gs_abbonamento_scaduto( $uid );
}
```

e poi, all'inizio dei tre cicli:

```php
	if ( gs_sfoglina_congelata( $user->ID ) ) { continue; }
```

- in `gs_check_streaks()` — **lo streak si ferma dov'era**, non si azzera e non brucia scudi;
- nella chiusura del mese — **niente resoconto** a chi non poteva giocare;
- nei promemoria delle lezioni — **niente solleciti** per porte chiuse.

**Compromesso da dichiarare:** lo streak «fermo» non è lo streak «attivo». Se torna dopo due
mesi con 12 settimane ferme, riparte da 12 — **non ha giocato, ma non ha nemmeno perso**. È
il significato di «congelato», e va scritto nella mail così com'è: *«la tua serie resta ferma
dov'era e riparte da lì»*.

**Da cercare gli altri:** questi tre li ho trovati leggendo, ma **il cron giornaliero ha
quindici agganci**. Vanno guardati tutti e quindici e deciso per ognuno se deve saltare una
congelata. **È mezz'ora, e va fatta prima degli inviti**, non dopo.

---

# 3 · «È gratis» va scritto ovunque

Confermo l'elenco dei cinque punti del documento precedente, e aggiungo la frase che hai dato
tu, che vale più di qualsiasi riscrittura mia:

> **«Siamo una communitas che difende l'arte e la professione della sfoglia a mano.»**

**Quella frase va nel modulo di iscrizione**, dove oggi c'è la spunta sulla quota
associativa. Dice in una riga perché il sito esiste, e toglie di mezzo la domanda *«quanto mi
costa?»* prima ancora che venga in mente.

## La mail aggiornata con le tue parole

Nella mail che ti ho mandato, il paragrafo dopo «si chiude» diventa:

> **Quello che hai fatto non si perde: si mette da parte.** I tuoi percorsi, le tue foto,
> quello che hai scritto nella Mia Sfoglia, i tuoi punti e la tua serie di settimane restano
> tutti lì, **fermi come li hai lasciati**, e ti aspettano. Il giorno in cui decidi di
> continuare, li ritrovi esattamente com'erano — non riparti da capo.

**E in cima, prima di tutto**, la riga sulla communitas:

> Benvenuta all'Accademia della Sfoglia: **una communitas che difende l'arte e la professione
> della sfoglia a mano.** Da oggi sei dei nostri.

---

# 4 · Il pannello delle mail — si può fare, e c'è già la porta giusta

> *«voglio nel pannello di controllo una sezione dove posso controllare tutte le mail che
> partono automaticamente dal sito e la possibilità di modificarne il testo in piena
> autonomia»*

**Ho contato prima di rispondere.**

| | Quante |
|---|---|
| Punti del codice che mandano una mail | **88** |
| Oggetti diversi | circa **45** |
| Mail che vanno **a una sfoglina** passando da `gs_mail_progetto()` | **39** |
| Mail che vanno **a te / alla segreteria** | **21** |

## La buona notizia: la porta esiste già

`gs_mail_progetto( $uid, $categoria, $oggetto, $corpo )` — **trentanove di quelle mail
passano tutte da questa funzione.** Gestisce già le preferenze di ogni sfoglina (email o
messaggio interno).

**Quindi non è «toccare trentatré file»: è aggiungere una chiave e far cercare il testo.**

```php
function gs_mail_progetto( $uid, $categoria, $oggetto, $corpo, $chiave = '' ) {
	// Se esiste un testo scritto da Ennio per questa mail, vince su quello
	// del codice. Il testo del codice resta come riserva: se il pannello è
	// vuoto, o se un giorno la mail cambia e nessuno l'ha riscritta, parte
	// comunque qualcosa di sensato.
	if ( $chiave ) {
		$personale = gs_mail_testo_personalizzato( $chiave, $uid );
		if ( $personale ) {
			$oggetto = $personale['oggetto'];
			$corpo   = $personale['corpo'];
		}
	}
	…
```

Poi le 39 chiamate passano una chiave (`'benvenuto'`, `'scadenza_7gg'`, `'percorso_completato'`…), e il pannello elenca le chiavi con due caselle ciascuna.

## Come lo farei, in due tempi

**Primo tempo — le mail che riguardano i trenta giorni.** Sono **cinque o sei**: benvenuto,
verifica email, avviso a 7 giorni, avviso l'ultimo giorno, accesso chiuso, riattivazione
fatta. **Sono quelle che decidono se una sfoglina versa i 29 €**, e sono quelle che vuoi poter
correggere leggendo come suonano.

**Va fatto prima degli inviti.**

**Secondo tempo — tutte le altre 33.** Quando c'è tempo, e non blocca niente.

**Le 21 che arrivano a te lascerei stare**: sono avvisi di servizio, le leggi tu, e renderle
modificabili è lavoro che non torna indietro.

## Tre cose da mettere nel pannello, che non sono ovvie

1. **Le parole che si sostituiscono.** Il nome, la data, il link: `[nome]`, `[data]`,
   `[link]`. **Vanno elencate accanto a ogni casella**, altrimenti il primo testo riscritto
   arriva alla sfoglina con scritto «Ciao [nome]».
2. **Un pulsante «Mandala a me»** accanto a ogni mail. È la differenza fra scrivere un testo e
   **vederlo come lo vede lei**. In Diagnostica c'è già «Invia email di prova»: stessa idea.
3. **Il testo del codice resta sempre**, e ci deve essere «Rimetti l'originale». Un pannello
   dove si può cancellare un testo senza poterlo recuperare è un pannello che prima o poi
   manda una mail vuota.

---

# Riepilogo

| | Stato |
|---|---|
| 30 giorni dall'approvazione | ✅ deciso |
| «Legata al bonifico» — la prima approvazione è **gratis**? | ❓ **una riga, e cambia la mail** |
| Congelato = i dati sono salvi | ✅ vero, niente da fare |
| Ma streak, resoconti e promemoria **le lavorano intorno** | 🔴 **una funzione e tre righe** |
| Gli altri 12 agganci del cron | ⚠ da guardare tutti, mezz'ora |
| Scadenza automatica | ✅ deciso |
| «È gratis» nei cinque testi + la frase sulla communitas | ✅ da scrivere |
| Pannello mail — le 5-6 dei trenta giorni | prima degli inviti |
| Pannello mail — le altre 33 | quando c'è tempo |

**Le tre cose che non possono slittare oltre i primi inviti** restano: il **reset**,
l'**identificativo pubblico** staccato dal nome utente, e il **consenso** nel modulo. A queste
si aggiunge adesso **il congelamento che congela davvero** — perché la prima sfoglina che
arriva al giorno 31 lo scopre da sola.
