# La mail di benvenuto, il consenso, e i trenta giorni

**Ennio, 26/08/2026:** iscrizione gratuita, **30 giorni in regalo** alla parte riservata, poi
l'accesso cessa, e si riapre con un bonifico di **29 €**. E la domanda sul consenso va fatta
mentre compilano.

**Prima la cosa che va saputa**, poi la mail.

---

# ⚠️ Oggi il sito dice il contrario, in cinque punti

L'iscrizione è costruita tutta sull'idea opposta: **prima paghi, poi entri.** Non è un
dettaglio di parole — è il modo in cui funziona adesso, e se la mail parte senza toccare
questi cinque punti, **la sfoglina legge «è gratis» nella mail e «paga prima» sul sito, lo
stesso giorno.**

| Dove | Cosa dice oggi |
|---|---|
| `shortcodes.php:108` | *«L'account resta in attesa finché la segreteria non verifica l'arrivo del bonifico.»* |
| `shortcodes.php:110` | La spunta obbligatoria di adesione alla **quota associativa** |
| `registration.php:40` | *«Devi confermare l'adesione alla quota associativa.»* |
| `registration.php:101` | *«Il tuo account sarà attivato dopo la verifica della quota associativa.»* |
| `registration.php:131` | Nella mail di verifica: *«l'attivazione dipende dalla verifica della quota associativa da parte della segreteria»* |

**Vanno riscritti tutti e cinque**, e sono solo parole: nessuna logica da cambiare per
questi.

## E due meccanismi che non esistono ancora

**1. L'accesso non cessa da solo.** Questo è scritto nero su bianco in `abbonamenti.php:12`:

> *«gs_abbonamento_scadenza = data opzionale, **solo promemoria via email** (non cambia da
> sola lo stato: **resta sempre il gestore a impostare "scaduto" a mano**)»*

Oggi, alla scadenza, **non succede niente**: qualcuno deve aprire il pannello e spegnere
quella sfoglina a mano, una per una.

**Con gli inviti di settembre non è praticabile.** Se si iscrivono in cinquanta lungo tutto
il mese, sono cinquanta scadenze diverse, sparse su cinquanta giorni. **Qualcuno se ne
dimentica — e chi si dimentica regala l'accesso, oppure lo toglie a chi ha appena pagato.**

**Serve la scadenza automatica**, ed è poco lavoro: il cron giornaliero esiste già, la data
di iscrizione di ogni sfoglina esiste già (`user_registered`), e la stessa funzione che oggi
manda gli avvisi a tre fasi può spegnere l'accesso alla fine.

**2. Non c'è il modo di registrare i 29 €.** Come per i 49 € della vetrina: serve un punto
dove tu (o la segreteria) segnate il bonifico arrivato, e da lì la parte riservata si
riapre. **Con le tre protezioni dei pagamenti** — identificativo contro il doppio clic,
importo maggiore di zero, riga nel registro.

## E una collisione da decidere: l'approvazione

Oggi chi si iscrive nasce **«in attesa»** e **aspetta che una persona la approvi** — e
l'approvazione è legata proprio alla quota (`registration.php:92`).

**Se resta così, i trenta giorni non partono all'iscrizione: partono quando qualcuno ha
tempo di approvare.** E se l'approvazione arriva dopo una settimana, il regalo è di
ventitré giorni.

**Due strade, e la decisione è tua:**

- **(a) L'approvazione diventa automatica** per chi si iscrive: entra subito, i trenta giorni
  partono davvero il primo giorno. **È quello che consiglio**, perché è quello che promette
  la mail;
- **(b) Resta manuale**, e allora **i trenta giorni partono dall'approvazione, non
  dall'iscrizione** — e la mail di benvenuto va mandata in quel momento, non prima.

**Non c'è una terza strada che funzioni.** Quello che non si può fare è promettere trenta
giorni e farli partire mentre lei aspetta.

---

# La mail

Scritta per essere letta da una persona che non ha voglia di decifrare niente. **Le cose fra
parentesi quadre le riempi tu.**

---

**Oggetto:** Benvenuta all'Accademia della Sfoglia — il tuo mese comincia oggi

Ciao **[Nome]**,

benvenuta all'Accademia della Sfoglia. Da oggi sei dei nostri.

**Iscriverti non ti è costato niente, e non ti costerà niente.** L'iscrizione all'Accademia è
e resta gratuita.

E c'è un regalo di benvenuto: **per trenta giorni hai le chiavi di tutta la parte riservata
del sito.** [Le lezioni video, i percorsi guidati, il calendario dei corsi, il Tavolo di
Lavoro — dove mandi la foto del tuo lavoro e un maestro ti risponde — e i messaggi con i
docenti.] Tutto, senza limiti, **fino al [DATA]**.

**Cosa succede il [DATA]**, detto adesso e chiaramente, così non ci sono sorprese: da quel
giorno la parte riservata si chiude.

**Quello che avevi resta tuo.** Il tuo account, i tuoi dati, le tue foto, quello che hai
scritto: niente viene cancellato, e continui a usare la parte pubblica dell'Accademia. Non
perdi nulla di quello che hai fatto — semplicemente, alcune porte si chiudono.

**Se vuoi continuare**, la strada è una sola e la scegli tu: **un contributo di 29 euro a
sostegno dell'Accademia**, e la parte riservata si riapre.

Non è un abbonamento che si rinnova da solo, non ti chiediamo la carta di credito, non ci
sono addebiti automatici. **È un bonifico, lo fai quando decidi tu, e basta.**

> **[Intestatario:]** …
> **[IBAN:]** …
> **[Causale:]** …

**Trenta giorni sono abbastanza per capire se questo posto fa per te.** Guardati intorno,
prova tutto, chiedi. Poi decidi con calma.

A presto,
**Accademia della Sfoglia**

---

## Perché è scritta così

**Tre scelte, che puoi cambiare ma che ti spiego:**

**1. La data del [DATA] è ripetuta due volte, e la seconda volta subito prima di quello che
succede.** Chi legge in fretta legge le date e i grassetti: deve incontrare la data proprio
lì.

**2. «Quello che avevi resta tuo» sta prima dei 29 euro, non dopo.** Chi legge «si chiude» ha
un attimo di allarme, e in quell'attimo la domanda è *«perdo tutto?»*. La risposta va data
subito, prima di parlare di soldi. **Chiedere soldi mentre qualcuno è spaventato funziona,
ma non è il modo in cui vuoi che l'Accademia parli alle sue socie.**

**3. «Non ti chiediamo la carta di credito, non ci sono addebiti automatici» è detto
esplicitamente**, anche se è ovvio per voi. Per chi legge non lo è: la parola «prova
gratuita» oggi vuol dire quasi sempre *«ti stiamo per addebitare qualcosa»*. **Dire che qui
non succede vale più di qualsiasi frase gentile.**

## Due cose da verificare prima di mandarla

- **L'elenco fra parentesi quadre**, quello delle aree che si chiudono. L'ho scritto
  guardando il codice (`abbonamenti.php:5`, che nomina *Calendario Corsi, Area
  Professionale, Messaggi, Esperto, Conversazioni*), **ma va confermato voce per voce**: se
  la mail promette qualcosa che poi resta chiuso, o annuncia una chiusura che non avviene,
  è peggio di non averlo elencato.
- **La data.** Se l'approvazione resta manuale (strada **b**), la data va calcolata
  dall'approvazione, e la mail va mandata da lì — **non alla registrazione.**

---

# La domanda sul consenso, dentro il modulo

Va **nel modulo di iscrizione**, sotto le due spunte che ci sono già, e **non deve essere
obbligatoria**: è una scelta, non una condizione.

```html
<p><label>
  <input type="checkbox" name="consenso_vetrina" value="1">
  Sì, il mio nome può comparire nella pagina pubblica «Le Sfogline» dell'Accademia.
</label></p>
<p class="gs-hint">Puoi cambiare idea quando vuoi, dalla tua pagina «La Mia Sfoglia»:
il tuo nome comparirà o sparirà subito. Se lasci questa casella vuota, il tuo nome
non viene pubblicato da nessuna parte.</p>
```

**Tre cose, e sono tutte volute:**

- **non ha l'asterisco** — le altre due spunte sono obbligatorie, questa no, e si deve vedere
  a colpo d'occhio;
- **dice dove finisce il nome** («la pagina pubblica Le Sfogline»), non «per finalità di
  comunicazione». Una persona può dare un consenso solo se capisce a cosa;
- **dice subito che si cambia idea**, e dove. È la parte che hai chiesto tu, e messa lì
  risparmia dieci email alla segreteria.

**Il contrassegno** (`gs_consenso_vetrina`) è lo stesso che poi legge il nastro, insieme al
pagamento — i tre stati del documento precedente.

---

# I tre importi, così stanno in un posto solo

| | Quanto | Che cosa apre | Come sta oggi |
|---|---|---|---|
| **Sfoglina — parte riservata** | **29 €** | corsi, area professionale, messaggi, esperto | oggi la scadenza **non spegne niente da sola** |
| **Sfoglina — vetrina pubblica** | **49 €** | il nome cliccabile e il link condivisibile | oggi si sblocca **coi token** |
| **Artigiani e Scuole** | **490 €/anno** | la vetrina del partner | oggi è **una casella che cambi tu** |

**Sono tre cose diverse e conviene che restino distinte anche nei testi:** una sfoglina può
voler entrare nei corsi (29) e non volere il nome pubblico (49), o il contrario.

---

# Cosa serve da te

**Subito, e sono due minuti ciascuna:**

1. **I 490 € nelle due caselle del pannello** — dal documento precedente, ancora da fare.
2. **I dati del bonifico** (intestatario, IBAN, causale) per la mail.
3. **L'approvazione: automatica (a) o manuale (b)?** Da questa dipende quando parte la mail e
   da quando si contano i trenta giorni.
4. **L'elenco delle aree che si chiudono**, confermato voce per voce.

**Poi, nell'ordine:** il reset → l'identificativo pubblico staccato dal nome utente → i
cinque testi riscritti + il consenso nel modulo → la scadenza automatica + i 29 € → i 49 € al
posto dei token.

**Solo dopo partono gli inviti.** Ogni pezzo di questa fila, se manca, si vede il primo
giorno.
