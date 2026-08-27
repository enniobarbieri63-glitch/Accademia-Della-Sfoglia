# Come si apre una chat nuova senza perdere niente

**Per Ennio — 27/08/2026**

Una conversazione lunga costa, perché ad ogni messaggio si rilegge tutto
quello che c'e stato prima. Ma la memoria del lavoro **non e nella
conversazione: e in questo repository.** Sessantanove documenti, la mappa, le
prove che girano.

Quindi una chat nuova non riparte da zero. Riparte dalla lettura.

Sotto c'e il messaggio da incollare come primo messaggio. E lungo apposta:
quel minuto di lettura fa risparmiare tutto il resto.

---

## Il messaggio da incollare

> Lavoriamo sul plugin `gaming-sfogline` di Accademia della Sfoglia.
>
> **Prima di rispondermi, leggi in questo ordine, dal ramo
> `claude/zip-analysis-report-mws6i4`:**
>
> 1. `revisioni/MAPPA-DI-TUTTO.md` — dove siamo, cosa e fatto, cosa manca
> 2. il documento che riguarda quello che ti chiedo oggi (la mappa te lo dice)
>
> **Il tuo ruolo.** Tu non hai i file del plugin. Li ha un'altra sessione
> ("Claude Code Ennio 2") che lavora su un sito di prova e mi manda gli zip.
> Io te li giro. **Tu verifichi leggendo il codice dello zip, mai fidandoti
> del riassunto che arriva insieme.** Piu di una volta il riassunto diceva
> una cosa e il codice ne diceva un'altra.
>
> **Le prove.** In `revisioni/prove/` c'e una serie di controlli che
> girano davvero. Si scompatta lo zip e si lancia `prova.sh`: sintassi di
> ogni file, funzioni chiamate ma mai scritte, pulsanti che non rispondono,
> ricorsioni per sbaglio.
>
> **Come scrivi.** In italiano, semplice. Io non sono un programmatore.
> Le eccezioni a una regola vanno dette **in cima**, non in fondo: tre volte
> ho ricevuto una regola universale a cui mancava un'eccezione, e ogni volta
> e costato una giornata.
>
> Quando finisci un lavoro, scrivi il documento in `revisioni/`, fai il
> commit sul ramo `claude/zip-analysis-report-mws6i4`, e mandamelo con
> SendUserFile perche io lo giri all'altra sessione.

---

## In piu, solo per lo zip del reset

Aggiungi questa riga al messaggio sopra:

> Oggi arriva lo zip del **reset**. Leggi `revisioni/ISTRUZIONE-IL-RESET.md`
> per intero prima di guardare il codice, e poi controlla **una riga alla
> volta**:
>
> - l'elenco delle chiavi da tenere nel codice e identico a quello del
>   documento — 63 chiavi fisse. **Una chiave dimenticata li dentro si vede
>   solo il giorno dopo**, quando una sfoglina si ritrova senza scadenza
>   dell'abbonamento;
> - i tipi di contenuto da cancellare sono ricavati a runtime con
>   `get_post_types()`, **non elencati a mano**;
> - da nessuna parte compare `wp_delete_user`. **Il reset non cancella
>   persone, mai**;
> - il contrassegno "reset iniziato" viene scritto **prima** di cancellare,
>   non dopo;
> - i due pulsanti ci sono, e il secondo chiede di digitare `RESET` a mano;
> - i tre casi particolari sono gestiti: voti dentro i sondaggi, sfide
>   ancora aperte, le tre opzioni segnaposto.
>
> Se trovi qualcosa che sembra un'eccezione e non e in quell'elenco,
> **fermati e chiedimelo**, non decidere da solo.

---

## Cosa tenere invece nella chat vecchia

Niente, in realta. I documenti sono tutti qui.

L'unico motivo per tornare in una conversazione gia aperta e se stai
finendo un discorso cominciato mezz'ora prima. Per un lavoro nuovo —
uno zip, una domanda, una grafica — **conviene sempre una chat nuova.**
