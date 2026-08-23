# Le 6 sfogline: cosa fare, e una domanda che vale più della pulizia

Bene aver trovato il numero esatto invece di accontentarsi. Sei, non duecento, e nessun
Buono assegnato per errore: **la parte che poteva costare soldi non è successa.**

Ma prima di ripulire qualsiasi cosa, c'è una domanda da chiudere.

---

## PRIMA DI TUTTO — sei su quante?

«Sei sfogline toccate» può voler dire due cose molto diverse, e non sappiamo quale:

**Caso A — il ciclo è morto dopo 6.** Le sfogline vere sono molte di più, il ciclo si è
impiccato sull'invio email (esattamente il guasto che avevi riprodotto su guru2 con 10
utenti di prova) e si è fermato lì. Le altre non hanno ricevuto niente, e non lo riceveranno
più perché adesso l'opzione è scritta.

**Caso B — le sfogline vere sono sei in tutto.** Il ciclo è arrivato in fondo
regolarmente, ha scritto l'opzione da solo, e la protezione della 3.272.1 non è mai servita.
Tutte le sfogline del sito hanno ricevuto quel messaggio.

**Come distinguerli:** conta quante sfogline superano `gs_e_sfoglina_vera()` sul sito vero.
Se sono ~6, è il caso B. Se sono molte di più, è il caso A.

**Riporta quel numero a Ennio comunque**, in entrambi i casi: è la stessa domanda rimasta
aperta dal Giro 0 — *quante sfogline vere ci sono davvero* — e finora non ha mai avuto
risposta. Serve per il nastro grande, serve per capire se la soglia dei 2500 punti è
tarata bene, e serve a Ennio per sapere di che dimensioni è la sua comunità attiva.

---

## Cosa fare con le 6: non cancellare e basta

**Togliere il messaggio interno senza dire niente è la scelta peggiore delle tre
possibili**, e vale la pena capire perché.

L'email **è già partita e non si richiama.** Quelle sei persone hanno già letto, o
leggeranno, che a luglio hanno fatto 0 punti su 2500. Se poi entrano nel sito per capire e
non trovano più niente, la confusione **aumenta**: hanno una email che parla di qualcosa di
cui sul sito non c'è traccia.

Sei persone sono poche abbastanza da meritarsi una frase vera.

### La sequenza che consiglio

**1 · Identifica le sei** dal marcatore `gs_buono_mese_2026-07` — e riporta i nomi a Ennio
prima di scrivere a chiunque.

**2 · Manda a loro sei, e solo a loro, un messaggio di rettifica.** Bozza da far approvare
a Ennio, non da spedire di tua iniziativa:

> **Un messaggio arrivato per errore**
>
> Ciao, ieri ti è arrivato un «resoconto di luglio» che diceva che avevi totalizzato 0
> punti. Era un errore nostro: il gioco del mese è partito ad agosto, e luglio non è mai
> stato un mese di gara. Quel messaggio non riguardava niente di reale e non hai perso
> nessuna occasione.
>
> Il primo resoconto vero arriverà a inizio settembre, e riguarderà agosto.
>
> Scusa la confusione — Accademia della Sfoglia

**3 · Solo dopo, sposta nel cestino il messaggio sbagliato.** In quest'ordine: prima la
spiegazione, poi la rimozione. Non deve mai esistere un momento in cui una sfoglina ha
l'email confusa e sul sito non c'è né il messaggio né la rettifica.

**4 · Lascia stare i marcatori** `gs_buono_mese_2026-07`. Sono innocui: luglio non verrà
mai più chiuso, perché l'opzione ora vale `2026-07`. Cancellarli significherebbe rimettere
mano ai dati per motivi estetici, e non ne vale la pena.

### Cosa NON fare

- **Non mandare la rettifica a tutte le sfogline.** Solo le sei l'hanno ricevuto: alle
  altre sarebbe un messaggio su un problema che non hanno avuto.
- **Non cancellare definitivamente niente.** Cestino, come hai proposto: è la scelta giusta.
- **Non spedire senza il sì di Ennio.** Sono persone vere e sono sue: il testo lo approva
  lui, anche se la bozza è pronta.

---

## Una cosa che è andata bene, e conviene saperla

Se siamo nel **caso A**, quel ciclo si è fermato a 6 per un motivo preciso: la correzione
2a — il marcatore scritto per singola sfoglina **prima** degli effetti — era già installata
con la 3.272.0.

Senza di lei, ogni giorno il cron sarebbe ripartito **da capo**: le stesse sei avrebbero
ricevuto lo stesso messaggio ogni giorno, e ogni giorno se ne sarebbero aggiunte altre,
finché non le avesse coperte tutte. E se qualcuna avesse avuto punti, avrebbe accumulato
2,5% al giorno.

**La correzione che stavamo installando ha limitato il danno del difetto che ha causato il
danno.** Vale la pena dirlo a Ennio esattamente così: è la dimostrazione, su un caso vero,
del perché quel marcatore va scritto prima e non dopo.

---

## Poi si riprende da dove eravamo

Restano, nell'ordine:

1. **I tre controlli sul sito vero** dal Giro 1 (la scansione singola su `/le-sfogline/`, il
   totale query di una pagina, quante sfogline nel nastro grande) — dieci minuti di letture,
   mai riportati.
2. **Il rendiconto della chiusura** in Posta interna — entro il 1° settembre, perché quella
   è la prima chiusura vera. Il codice è in `GIRO-2-VERIFICA-FINALE.md`.
3. **La conferma della pulizia di guru2** dalle 10 sfogline di prova.

Poi il **Giro 3**.
