# Il pannello delle mail — e la newsletter che diventa un tavolo da scrittura

**Per Claude Code Ennio 2 — 26/08/2026, misurato su 3.292.0**

Ennio ha deciso: **MailPoet manda, il plugin prepara.** E vuole *«una sezione del pannello generale»* dove vedere le mail.

---

## Quello che questa decisione fa sparire

La specifica precedente (`NEWSLETTER-DALLO-ZIP.md`) prevedeva una coda, l'invio a lotti sul cron, il contrassegno per non mandare due volte, il piede con la disiscrizione, la pagina che spegne la categoria, il freno dei sette giorni. **Circa due giornate di lavoro, tutte sulla parte rischiosa.**

**Con la decisione di Ennio spariscono tutte.** Il plugin non manda niente in blocco, quindi:

- niente limite orario dell'hosting da schivare;
- niente rischio che il dominio venga segnato e smettano di arrivare **le email del trentesimo giorno e del recupero password**, che è il pericolo vero di cui parlavo;
- niente disiscrizione da gestire, niente registro dei consensi: **lo fa MailPoet, che è il suo mestiere e lo fa per legge.**

Il plugin diventa **un tavolo da scrittura**: si scrive la mail, si controlla come viene, e si porta a MailPoet. Molto meno codice, e la parte che poteva far danno non c'è più.

**Resta però una cosa del documento precedente**, e resta valida per un'altra ragione: **far uscire la posta del sito da un servizio SMTP vero.** Non serve più per la newsletter — che non passa da lì — ma serve per le 87 email che il sito manda comunque da solo: il benvenuto, la scadenza, le prenotazioni, il recupero password. Mezz'ora, fuori dal plugin, e vale per tutte.

---

## 1. Il pannello delle mail — i numeri veri

Li ho contati sul codice, non a memoria:

| | |
|---|---|
| email dirette a una persona, attraverso `gs_mail_progetto()` | **39** |
| `wp_mail()` diretti (quasi tutti avvisi a Ennio) | **48** |
| testi già modificabili dal pannello oggi | **3** |

**Ce ne sono tre su quarantacinque.** Il registro esiste già ed è fatto bene — `gs_mail_template_registro()` in `mail-area-riservata.php:28`, con il testo modificabile e l'invio di prova. **Non va reinventato: va riempito.**

### La cosa che rende il lavoro lungo (ma non difficile)

**Le mail non si possono catalogare da sole.** Ho provato: solo sedici delle trentanove hanno l'oggetto scritto per esteso nel codice. Le altre lo costruiscono mentre girano:

```php
$oggetto = 'ultimo' === $fase
    ? 'Il tuo abbonamento scade oggi o domani — Accademia della Sfoglia'
    : 'Il tuo abbonamento sta per scadere — Accademia della Sfoglia';
```

Nessun programma può estrarre quelle due frasi e capire che sono due mail diverse della stessa famiglia. **Vanno registrate a mano, una per una.**

Quindi il conto onesto: **quarantacinque voci da scrivere.** Cinque minuti l'una fatte bene — con la chiave, l'oggetto, il testo, e la spiegazione di *quando parte* — sono **circa quattro ore**. Non è difficile: è lungo, ed è il tipo di lavoro dove ci si distrae e si sbaglia una chiave.

### 💡 Come non farlo due volte

**Non costruite il pannello come un lavoro a parte.**

Nel piano ci sono già tre voci che riscrivono delle mail: **B2** (le email di scadenza), **B4** (gli undici testi), **B5** (la mail di benvenuto). Sono le mail più importanti che il sito manda — quelle intorno ai trenta giorni.

**Riscrivetele direttamente dentro il registro, non nel codice.** Il pannello nasce come contenitore di un lavoro che va fatto comunque, invece che come un secondo giro sulle stesse mail.

Ordine consigliato, in tre tappe:

| tappa | quali mail | quante | quando |
|---|---|---|---|
| **1** | quelle dei trenta giorni: benvenuto, i tre avvisi di scadenza, «La Mia Sfoglia», «Accesso e Vetrina» | ~8 | **insieme a B2/B4/B5**, non dopo |
| **2** | il resto di quelle che riceve una sfoglina: corsi, ricette, biografie, messaggi, testimoni | ~31 | con calma |
| **3** | gli avvisi a Ennio (`wp_mail` diretti) | ~48 | ultimi, o mai: **non li legge nessuno tranne lui**, e cambiarne il testo serve a poco |

**La tappa 3 forse non vale il lavoro.** Ditelo a Ennio prima di farla: sono email che manda a sé stesso, e il valore di poterne cambiare il testo è vicino a zero. Se le vuole comunque nel pannello, che sia una scelta consapevole e non un automatismo.

### Come deve essere fatto il pannello

Una tabella sola, raggruppata per **quando parte** — non per file, che a Ennio non dice niente:

```
📬 LE MAIL CHE PARTONO DAL SITO

▸ QUANDO SI ISCRIVE  (3)
   Conferma dell'indirizzo email          [modifica] [prova] · parte subito
   Benvenuto, iscrizione approvata        [modifica] [prova] · all'approvazione
   Richiesta non accolta                  [modifica] [prova] · se rifiuti

▸ NEI PRIMI GIORNI  (2)
   La Mia Sfoglia                         [modifica] [prova] · 2 giorni dopo
   Accesso e Vetrina                      [modifica] [prova] · 5 giorni dopo

▸ VERSO LA SCADENZA  (3)
   Fra una settimana finisce la prova     [modifica] [prova] · 7 giorni prima
   Ultimo giorno                          [modifica] [prova] · il giorno stesso
   La prova è finita                      [modifica] [prova] · il giorno dopo
   …
```

Tre cose che il pannello deve fare, e che sono più importanti della grafica:

**«Prova» manda a Ennio**, con dati finti ma realistici — un nome, delle date vere. Esiste già in `gs_mail_area_riservata`: riusatelo. **È l'unico modo per accorgersi che una data viene fuori storta prima che la legga una sfoglina.**

**«Quando parte» scritto accanto a ogni mail.** Senza, fra sei mesi nessuno sa più se «Accesso e Vetrina» arriva il secondo o il quinto giorno, e per saperlo bisogna leggere il codice.

**I segnaposto elencati sotto ogni testo** — `%NOME%`, `%DATA_INIZIO%`, `%DATA_FINE%`, `%LINK%` — con l'avvertenza che toglierne uno significa che quella cosa **non comparirà**. Se Ennio cancella `%DATA_FINE%` dalla mail di benvenuto, la sfoglina non sa più quando scade la prova, e nessuno se ne accorge.

**Il testo modificato non sostituisce quello scritto nel codice: lo copre.** Un pulsante «Torna al testo originale» per ogni mail. Serve il giorno in cui si cancella per sbaglio mezza frase e non ci si ricorda com'era.

---

## 2. La newsletter: il tavolo da scrittura

Nella stessa sezione, un riquadro a parte.

```
✍️  PREPARA UNA NEWSLETTER

Oggetto  [ Le novità di settembre                        ]
Testo    [ …                                             ]

[ Anteprima ]  [ Mandala a me per prova ]  [ 📋 Copia per MailPoet ]

Destinatarie: ● tutte le sfogline (47)  ○ solo chi è in regola (31)  ○ solo chi guarda (16)
              [ 📋 Copia gli indirizzi ]  [ ⬇ Scarica CSV ]
```

**Cosa fa il plugin:** aiuta a scrivere, fa vedere come viene, manda una prova a Ennio, e prepara **due cose da portare in MailPoet** — il testo e l'elenco degli indirizzi.

**Cosa non fa:** mandare. Mai.

### I tre elenchi, e perché sono tre

Perché sono tre domande diverse che Ennio si farà davvero:

- **tutte** — un annuncio che riguarda l'Accademia;
- **solo chi è in regola** — qualcosa riservato a chi ha versato il contributo;
- **solo chi guarda** — *«sei ancora in tempo a rientrare»*. **È la più utile delle tre**, ed è quella che nessun altro strumento può costruire, perché solo il plugin sa chi è congelata.

**Gli elenchi si costruiscono al momento del clic, mai salvati.** Chi è congelata oggi può aver pagato domani: un elenco fatto ieri scriverebbe alla persona sbagliata.

### La cosa che non va dimenticata

**Chi ha spento le notifiche via email non deve comparire negli elenchi.** Il plugin lo sa (`gs_notifiche_pref`), MailPoet no. Se l'elenco esce senza quel filtro, MailPoet scriverà a chi aveva chiesto di non ricevere niente — e la responsabilità è nostra, non sua.

Sotto ogni elenco, scritto: *«3 sfogline escluse perché hanno spento le email»*, così Ennio vede che il filtro sta funzionando invece di doverci credere.

---

## 3. Come si prova

1. Modificare il testo di una mail dal pannello → **«Prova»** la manda con il testo nuovo.
2. **«Torna al testo originale»** → riappare quello di partenza.
3. Togliere `%DATA_FINE%` dalla mail di benvenuto → la prova arriva senza la data, **e il pannello lo dice prima** invece di lasciarlo scoprire.
4. Approvare una sfoglina di prova → arriva la mail **col testo del pannello**, non con quello del codice. *Questa è la prova che conta: senza, il pannello è una finestra su niente.*
5. Copiare l'elenco «solo chi guarda» con una congelata e una attiva → **c'è solo la congelata**.
6. Spegnere le email a una sfoglina → **sparisce dall'elenco**, e il conteggio degli esclusi sale di uno.
7. `prova.sh`.

---

## Il conto

| | |
|---|---|
| Il pannello, la struttura, il «torna all'originale» | mezza giornata |
| Le 8 mail della tappa 1 | **incluse in B2/B4/B5**, se fatte insieme |
| Le ~31 della tappa 2 | mezza giornata, quando capita |
| Il tavolo della newsletter con i tre elenchi | mezza giornata |
| L'impostazione SMTP (fuori dal plugin) | mezz'ora |

**Circa una giornata e mezza**, contro le due e mezza di prima — e senza la parte che poteva rompere le email del gaming.

**La decisione di Ennio ha tolto il lavoro più rischioso e ha lasciato quello utile.** Non capita spesso.
