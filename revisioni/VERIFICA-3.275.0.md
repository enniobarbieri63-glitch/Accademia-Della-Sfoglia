# 3.275.0 verificata — C1-C5 corrette, due cose da chiudere

Letto il codice, non il riassunto. **C1, C2, C3, C4 e C5 sono tutte implementate bene**,
`php -l` pulito su tutti i file, e il diff contro la 3.272.1 non contiene sorprese: le
correzioni, il contatore, l'interruttore del percorso mensile, e una serie di cambi di
parole «gioco» → «percorso» che sono lavoro parallelo.

**Nessuna delle cinque va rifatta.** Restano due punti, uno piccolo e uno che riguarda
qualcosa che abbiamo appena costruito.

---

## Quello che è stato fatto bene, e perché lo dico

**C1** è la correzione migliore del lavoro finora. Tre cose giuste insieme:

- l'identificativo è controllato e **scritto prima di sommare**, non dopo — la lezione
  della chiusura del mese applicata senza che nessuno la ricordasse;
- la correzione negativa è riservata al titolare vero, non ai collaboratori;
- **e questo non l'avevo chiesto io**: una correzione negativa non fa più scattare la
  conferma della prenotazione (`$importo > 0` nella condizione). Era un difetto che avrei
  introdotto io con la mia proposta, ed è stato visto e chiuso.

**C2/C3** sono esatte, e il testo dell'avviso in Posta interna distingue i tre casi —
disdetta nei termini con acconto, nei termini senza, fuori termine — che è quello che serve
per agire senza dover andare a controllare.

## E l'interruttore del percorso mensile è meglio della mia proposta

Io avevo proposto una funzione con dentro il mese di partenza scritto a mano (`'2026-10'`).
La soluzione adottata è **un interruttore nello Stato Generale**, spento di default, che
Ennio accende quando il percorso parte davvero.

È migliore per una ragione precisa: **la data di partenza è una decisione di Ennio, e deve
stare dove Ennio la può cambiare** — non in una riga di codice che richiede un
aggiornamento del plugin ogni volta che sposta il lancio. Ed è collegato correttamente:
finché è spento il segnaposto avanza ogni giorno senza toccare nessuna sfoglina, così
all'accensione la prima chiusura vera è quella del primo mese davvero giocato.

**Il 1° settembre non partirà niente.** Il problema è chiuso.

---

## Punto 1 · Una domanda: la rettifica alle sei è partita?

`includes/rettifica-luglio.php` **non c'è nella 3.275.0.**

Le possibilità sono due, e sono opposte:

- **è stata installata, ha girato, e il file è stato cancellato** come previsto → le sei
  hanno ricevuto la rettifica, tutto a posto;
- **non è mai stata installata** → le sei hanno ancora solo il messaggio sbagliato.

**Verifica e riporta:** leggi l'opzione `gs_rettifica_luglio_fatta` sul sito vero. Se c'è
una data, ha girato. Se è vuota, non è mai partita e va rimessa in lista.

Se ha girato, in Posta interna deve esserci il messaggio «Rettifica di luglio inviata» con
i nomi: **quello è anche l'unico posto dove quei nomi esistono.** Riportali a Ennio prima
che si perdano nella lista.

---

## Punto 2 · Un buco residuo in C1: senza identificativo, nessuna protezione

```php
if ( $rif && in_array( $rif, $visti, true ) ) {
	wp_send_json_error( array( 'message' => 'Questo pagamento risulta già registrato.' ) );
}
```

Se `$rif` **arriva vuoto, il controllo viene saltato del tutto** e il doppio clic torna a
funzionare come prima.

Non è teorico: **SiteGround Optimizer combina e mette in cache i JavaScript.** Nei giorni
dopo un aggiornamento, un browser può servire il `gaming.js` vecchio — quello che non manda
`rif` — mentre il PHP è già quello nuovo. In quella finestra la protezione non c'è, e
nessuno se ne accorge perché il pagamento va a buon fine.

### Non renderlo obbligatorio

Rifiutare i pagamenti senza `rif` proteggerebbe, ma **bloccherebbe l'incasso** a chi ha il
file vecchio in cache: il gestore vedrebbe un errore su un'operazione legittima, e la cosa
peggiore che può fare un pannello di pagamenti è rifiutare un pagamento vero.

### La rete di sicurezza: il registro che ora esiste

`gs_pagamenti_log` è stato appena creato, e contiene già tutto quello che serve. Basta
guardare l'ultima voce:

```php
	// Rete di sicurezza per quando l'identificativo non arriva (browser con
	// il JavaScript vecchio in cache: SiteGround Optimizer li combina e li
	// tiene, quindi capita davvero nei giorni dopo un aggiornamento). Due
	// pagamenti identici a pochi secondi l'uno dall'altro sono un doppio
	// clic, non due versamenti veri.
	if ( ! $rif ) {
		$log_ora = get_post_meta( $pid, 'gs_pagamenti_log', true );
		$ultima  = is_array( $log_ora ) && $log_ora ? end( $log_ora ) : null;
		if ( $ultima
			&& (string) $ultima['tipo'] === (string) $tipo
			&& abs( (float) $ultima['importo'] - $importo ) < 0.005
			&& ( current_time( 'timestamp' ) - strtotime( $ultima['data'] ) ) < 15 ) {
			wp_send_json_error( array( 'message' => 'Un pagamento identico è stato registrato pochi secondi fa: se è davvero un secondo versamento, riprova fra un minuto.' ) );
		}
	}
```

**Compromesso, da dichiarare invece che nascondere:** due versamenti realmente identici a
meno di 15 secondi di distanza vengono rifiutati. È un caso raro e il messaggio dice cosa
fare; il caso opposto — un doppio clic che passa — è più frequente e non dice niente.

**Priorità media.** Il difetto grosso è chiuso; questa è la fessura che resta.

---

## Punto 3 · Gli avvisi che abbiamo appena costruito finiscono in una casella senza campanello

Questo non è un difetto di quello che è stato fatto: è una conseguenza, e si vede solo
adesso che gli avvisi esistono.

`gs_inbox_crea()` scrive un messaggio nella Posta interna e basta — **nessuna email, nessun
aeroplanino, nessun contatore.** In quella casella arrivano ora:

- **«Disdetta: …» con scritto se c'è un acconto da restituire** (appena aggiunta, C3);
- il rendiconto della chiusura del mese (da aggiungere);
- prenotazioni nuove, corsi bloccati, abbonamenti dei partner in scadenza.

Cioè: **quasi tutto quello che richiede un'azione da parte di Ennio.**

La Torre di controllo mostra un blocco «Posta interna — ultimi arrivi»
(`pannello-nuovo.php:779`), quindi non è un pozzo nero. Ma **non c'è nessun numero di non
letti da nessuna parte**: niente che dica «guarda qui, è arrivata una cosa».

### E la funzione per contarli esiste già

`gs_inbox_non_letti()` è definita in `inbox.php:58` e **non è chiamata da nessuna parte in
tutto il plugin.**

Nel primo documento l'avevo messa fra le funzioni morte (voce **G1**), con una nota:
*«non sembra un avanzo, sembra una funzionalità mai collegata — chiedere se era dimenticata
o rinviata prima di cancellarla»*. **Adesso la risposta è chiara: era dimenticata, e
serve.**

### Cosa proporre a Ennio

Non farlo di tua iniziativa — è una scelta sua. Le opzioni, dalla più leggera:

1. **Un numero accanto alla voce «Posta interna»** nell'elenco delle zone del pannello,
   quando ci sono messaggi non letti. Usa `gs_inbox_non_letti()`, che è già scritta.
2. **Una riga nello Stato Generale**, dove sono già gli altri numeri: *«📬 N messaggi non
   letti in Posta interna»*. Coerente con quello che c'è, e Ennio lo Stato Generale lo apre.
3. **Un aeroplanino** quando arriva un messaggio che richiede un'azione (una disdetta con
   acconto da restituire). Più invadente, ma è l'unico che funziona se il pannello resta
   chiuso per giorni.

**La 2 è quella che consiglio**, e la 3 solo per le disdette con acconto: sono l'unica cosa
in quella casella che ha una scadenza vera, perché c'è un soldo da restituire a qualcuno
che aspetta.

**Metti la proposta davanti a Ennio e fermati.** Non scrivere codice prima della risposta.

---

## Riepilogo

| | Stato |
|---|---|
| C1 · doppio clic sul pagamento | ✅ corretta, resta la fessura del punto 2 |
| C2 · disdetta in stato chiuso | ✅ corretta |
| C3 · disdetta silenziosa | ✅ corretta |
| C4 · query pesante | ✅ corretta |
| C5 · funzione morta | ✅ cancellata |
| Blocco del 1° settembre | ✅ risolto, meglio di come l'avevo proposto |
| Contatore della comunità | ✅ installato |
| Rettifica alle sei | ❓ **da verificare se è partita** |
| Fessura senza `rif` | ⚠ da chiudere |
| Campanello della Posta interna | ⚠ da proporre a Ennio |

Poi si torna alla seconda lettura: **`esperti.php`**, le consulenze a token.
