# «La Mia Sfoglia»: tutto quello che serve, senza confondere

**Ennio, 26/08/2026:** *«voglio che nella Mia Sfoglia le sfogline trovino tutto il percorso
base del gaming, le spiegazioni e tutte le funzioni importanti per loro, ma non voglio
creare alcun tipo di confusione»*.

**Le due metà di questa frase tirano in direzioni opposte, e lo sai — l'hai scritto tu.** Per
questo ho misurato la pagina prima di rispondere, invece di dare consigli generici.

---

# 1 · Cosa c'è già su quella pagina, contato

`gs_sc_dashboard()` — `shortcodes.php:123`, 158 righe — mette insieme **sedici blocchi**:

| Fascia | Cosa contiene |
|---|---|
| *(in cima)* | la guida «Come funziona il Percorso», il promemoria del testamento, la **carta d'identità** con sei numeri, le quattro pillole di navigazione |
| **Oggi** | missioni del giorno, ingrediente segreto |
| **Il mio percorso** | il percorso, il Buono Sfoglia, lo sconto sui corsi, la madrina |
| **Le mie sfide** | le tue sfide, il cestino, lo streak e gli scudi |
| **I miei strumenti** | promemoria, vetrina pubblica, token, account |

**Non è una pagina povera: è la pagina più piena del sito.**

E la guida «Come funziona il Percorso» esiste già come modulo suo
(`come-funziona.php`), con titolo e testo **che modifichi tu dal pannello**. Spiega punti,
livelli, missioni, badge, e ha due schemi che si aggiornano da soli leggendo le soglie
impostate.

**Quindi: «tutto il percorso base e le spiegazioni» — c'è già.** Quello che chiedi non è
contenuto nuovo.

---

# 2 · Da dove viene la confusione, davvero

Da due cose precise, e nessuna delle due è «manca qualcosa».

## a) Tutto viene mostrato a tutte, nello stesso momento, con lo stesso peso

Una sfoglina **al primo giorno** apre «La Mia Sfoglia» e trova, tutti insieme e tutti uguali:

- il **Buono Sfoglia** (che si vince con 2.500 punti al mese — lei ne ha zero)
- lo **sconto sui corsi** (che si guadagna coi badge — lei non ne ha)
- la **madrina** (se non è abbinata, o non c'è, o è un riquadro vuoto)
- i **token** (che si comprano con un bonifico — lei non ne ha)
- la **vetrina pubblica** (che costa — lei non ce l'ha)
- gli **scudi salva-streak** (che si guadagnano ogni 4 settimane — lei è al giorno uno)
- il **testamento della sfoglina**

**Sette cose su sedici non la riguardano ancora**, e non c'è niente che glielo dica. Il
risultato non è che non capisce una cosa: è che **non sa quale delle sedici guardare**, e
quello è il tipo di confusione che fa chiudere la pagina.

## b) Le spiegazioni sono lunghe come articoli

Sono già lì, e sono **il motivo per cui non vengono lette**. Due esempi veri, presi dal
codice:

**L'aiuto della pagina** (`shortcodes.php:158`) è **un paragrafo unico di circa 120 parole**
che descrive la pagina che la lettrice **ha davanti agli occhi**. Comincia con *«Questa è la
tua pagina principale»* e prosegue elencando le quattro fasce e i sei numeri.

**L'aiuto dello streak** (`shortcodes.php`, fascia sfide) è **circa 200 parole**, e comincia
così:

> *«"Streak" è un termine inglese (letteralmente "striscia", "serie") usato nei giochi e
> nelle app… un'app per imparare una lingua può contare quanti giorni di fila fai un
> esercizio; una squadra sportiva ha uno "streak" quando vince partite una dietro l'altra;
> un'app per tenersi in contatto può contare i giorni di fila in cui vi scrivete…»*

**Tre esempi e un'etimologia prima di dire cosa fa qui.** È scritto con cura, si vede — ma
una persona che vuole sapere *«perché ho 3 e non 4»* non arriva alla fine.

**Questa non è una critica a chi l'ha scritto: è la prova che il problema non è "manca la
spiegazione".** La spiegazione c'è tre volte più del necessario.

---

# 3 · Due meccanismi che hai già, e che risolvono esattamente questo

**Non c'è niente da inventare.** Sono scritti, funzionano, e sono sottoutilizzati.

## `gs_onboarding_box()` — «I tuoi primi passi»

Un riquadro che compare **solo a chi è appena arrivata** e sparisce quando lei preme «Ho
capito, comincio». Dentro ci sono **tre passi concreti**, con i collegamenti: partecipa alla
Sfida, vota le sfoglie delle altre, scrivi nel Diario.

**È già la risposta a «tutto il percorso base», ridotta a quello che serve il primo
giorno.**

**Un difetto da correggere subito:** una volta chiuso, **non torna più.** Chi preme «Ho
capito» il primo giorno e poi si dimentica, non ha modo di rileggerlo. **Va reso
riapribile** — una voce «Rivedi i primi passi» fra gli strumenti, tre righe di lavoro.

## «Prossimo passo» — `prossimo-passo.php`

Una riga sola che dice **una cosa da fare**, e cambia da sola in base a dove sta la sfoglina.

**È il meccanismo giusto per il problema giusto**, e oggi è **una riga dentro la carta
d'identità**, in mezzo a sei numeri. **La cosa più utile della pagina è quella meno
visibile.**

---

# 4 · Le tre mosse che consiglio

## Mossa 1 — Far comparire le cose quando iniziano a contare

Non togliere niente: **non mostrare quello che non è ancora suo.** Sette blocchi, sette
regole semplici:

| Blocco | Compare quando |
|---|---|
| Buono Sfoglia | ha almeno **1 punto** nel mese in corso |
| Sconto sui corsi | ha vinto **il primo badge** che dà sconto |
| Madrina | è **davvero abbinata** a qualcuna |
| Token | ha **almeno un token**, o glieli sta per servire |
| Vetrina | ha **finito i primi passi** (non il primo giorno) |
| Scudi salva-streak | ha **almeno 1 settimana** di streak |
| Testamento | dopo **il primo mese** |

**Compromesso da dichiarare, ed è il vero prezzo:** una sfoglina **non scopre che il Buono
Sfoglia esiste** finché non prende un punto. **Per questo la guida «Come funziona» resta in
cima e resta completa**: lì c'è tutto, sempre. La pagina mostra *quello che è tuo adesso*, la
guida racconta *tutto quello che c'è*. **Sono due cose diverse e devono restare separate.**

## Mossa 2 — Accorciare le spiegazioni, non toglierle

**Regola:** ogni aiuto dice **cosa fa questa cosa e cosa devi fare tu**, in **due frasi**.
Il resto va nella guida.

Lo streak diventa:

> *«Ogni settimana in cui pubblichi almeno una sfoglia, la tua serie sale di uno. Se salti
> una settimana intera torna a zero — a meno che tu non abbia uno scudo, che la copre da
> solo.»*

**Trentotto parole invece di duecento, e non manca niente di quello che serve per capire il
numero che si sta guardando.** L'etimologia inglese e i tre esempi vanno nella guida, dove
chi è curioso li trova.

**E l'aiuto della pagina intera (`shortcodes.php:158`) io lo toglierei del tutto**: descrive
a parole una pagina che la lettrice sta guardando. **Le quattro pillole colorate lo dicono
già meglio di qualsiasi paragrafo.**

## Mossa 3 — Una cosa sola da fare, in cima e grande

**«Prossimo passo» esce dalla carta d'identità e diventa la prima cosa della pagina**, sopra
tutto, scritto in grande e con il collegamento che porta dritta lì.

**Perché è la mossa che vale di più:** una sfoglina che apre la pagina e trova sedici blocchi
deve *scegliere*. Una che trova **una frase che dice cosa fare adesso** non deve scegliere
niente — e sotto trova tutto il resto quando lo cerca.

---

# 5 · Perché conviene farlo adesso, e non «quando c'è tempo»

**I trenta giorni di prova.**

Dal primo invito di settembre, ogni sfoglina ha **trenta giorni per capire se questo posto
fa per lei**, e alla fine decide se versare 29 €.

**In quei trenta giorni la pagina «La Mia Sfoglia» è il posto dove passa quasi tutto il suo
tempo.** Se al terzo giorno si sente persa, non arriva al trentesimo — e nessuna mail scritta
bene rimedia a una pagina che confonde.

**Non è lavoro di rifinitura: è la cosa che decide se il regalo dei trenta giorni funziona.**

---

# 6 · Cosa NON farei, e perché lo dico

**Non aggiungerei una pagina «Guida» separata.** Ce n'è già una dentro «La Mia Sfoglia», ed è
al posto giusto: si legge dove serve. Una seconda guida altrove sarebbe una terza cosa da
tenere aggiornata, e la terza è sempre quella che invecchia.

**Non aggiungerei un giro guidato a fumetti** («clicca qui, ora clicca là»). Si guarda una
volta, non si ricorda, e chi lo salta non lo rivede mai. **I «primi passi» che hai già fanno
la stessa cosa meglio**: restano lì, si rileggono, e sono tre righe invece di dieci schermate.

**Non toglierei nessuna funzione.** Il problema non è che ce ne siano troppe: è che si vedono
tutte insieme il primo giorno. **La stessa pagina, al sessantesimo giorno, è giusta così
com'è.**

---

# Riepilogo

| | |
|---|---|
| Il contenuto che chiedi | **c'è già**, guida compresa |
| La causa della confusione | tutto mostrato a tutte insieme + spiegazioni lunghe come articoli |
| Mossa 1 | sette blocchi compaiono **quando iniziano a contare** |
| Mossa 2 | aiuti in **due frasi**; il resto nella guida |
| Mossa 3 | **«Prossimo passo» in cima**, grande |
| Correzione a parte | «I tuoi primi passi» deve poter essere **riaperto** |
| Perché adesso | è la pagina su cui si decidono i **trenta giorni** |

---

**Se ti è più facile vederlo che leggerlo, te ne faccio uno schizzo della pagina —
com'è oggi e come sarebbe al primo giorno.** Dimmelo e lo preparo.
