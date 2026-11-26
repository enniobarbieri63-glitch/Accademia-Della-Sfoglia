# Scrigno — diario di lavoro

Questo file esiste perché la conversazione in cui è nato il progetto è stata
cancellata. Qui dentro c'è tutto quello che serve a riprendere in mano il lavoro
senza avere quella chat sotto gli occhi: cosa è stato chiesto, cosa è stato deciso
e perché, cosa resta da fare.

**Sessione del 13 settembre 2026.** Modello usato: Opus 5.

---

## 1. Da dove si è partiti

Due file consegnati all'inizio:

- `scrigno-anteprima.html` — il progetto grafico approvato di un'app per Mac
  chiamata **Scrigno**: sei schermate e la finestra Importa.
- `scrigno-istruzioni-costruzione.md` — le istruzioni per trasformarlo in un'app
  vera, a fasi.

La richiesta iniziale, parola per parola: *«Leggi ripara e costruisci e migliora»*.

Poi, a lavoro iniziato: *«Termina tutto il progetto, poi lo faccio testare da claude
desktop. puo scaricare foto e file anche da android»*.

E strada facendo, quattro richieste aggiunte una dopo l'altra:

1. «ci sono strumenti di editing delle foto?»
2. «puoi aggiungere strumenti per raddrizzare le linee cadenti, oblique, verticale,
   orizzontale, ribaltare foto»
3. «effetto sfocatura, con regolazione di profondità di campo e intensità della
   sfocatura»
4. «applicabile su un punto oppure una maschera»

Tutte e quattro sono state fatte e sono nel codice.

---

## 2. Dove sta tutto

| Cosa | Dove |
|---|---|
| Deposito | `enniobarbieri63-glitch/Accademia-Della-Sfoglia` su GitHub |
| Ramo | `claude/friendly-babbage-ouokhv` |
| Tutto il progetto | cartella `scrigno/` |
| L'app | `scrigno/app/` — si apre con `Scrigno.xcodeproj` |
| L'anteprima riparata | `scrigno/anteprima/index.html` |

L'anteprima è anche pubblicata come pagina che si apre dal telefono o dal browser:

**https://claude.ai/code/artifact/0603240c-2cb7-494e-aab5-abd71e77ac45**

Gli altri documenti della cartella:

- `ISTRUZIONI.md` — cosa costruire e come, con tutte le decisioni prese
- `STATO.md` — a che punto è il lavoro
- `app/COME-APRIRLA.md` — come aprirla in Xcode e cosa provare
- `anteprima/NOTE-RIPARAZIONI.md` — i difetti trovati nell'anteprima e come sono stati sistemati

---

## 3. L'anteprima: cosa era rotto

L'aspetto non è stato toccato: era approvato. Sono stati sistemati i difetti di
funzionamento. I tre più seri:

**La finestra Importa copriva tutta l'app e non si chiudeva.** Il foglio era marcato
come nascosto, ma una regola di stile lo rendeva visibile lo stesso. All'apertura
della pagina copriva tutto, e né la ✕ né Esc né il clic fuori riuscivano a toglierlo.
Tutto il resto dell'anteprima era di fatto irraggiungibile.

**Mancava l'intestazione del documento.** Niente dichiarazione del tipo di file,
niente codifica, niente lingua. Gli accenti potevano uscire come `VenerdÃ¬`, e il
lettore vocale leggeva l'italiano con pronuncia inglese.

**Nella schermata Modifica la foto copriva i cursori.** Veniva calcolata larga 855 px
dentro uno spazio da 710 e si mangiava la colonna di destra: *Esposizione*,
*Contrasto*, *Temperatura* sparivano sotto l'immagine. Verificato che il difetto
c'era già nel file originale, non è stato introdotto durante il lavoro.

Più otto difetti minori: le scorciatoie ⌘1–⌘6 e ⌘F erano stampate sullo schermo ma
non esistevano; metà della barra laterale non rispondeva al clic; i filtri in alto
non erano cliccabili; tre cursori su undici non facevano niente; le intestazioni
dicevano «18 elementi» sopra otto miniature. L'elenco completo è in
`anteprima/NOTE-RIPARAZIONI.md`.

Sono stati aggiunti **31 controlli automatici** (`anteprima/prova.mjs`), uno per ogni
difetto, così non tornano indietro. Girano su un browser vero:

```
npm i -D playwright && npx playwright install chromium
node prova.mjs
```

---

## 4. Le decisioni prese, e perché

Queste erano aperte e andavano chiuse prima di scrivere codice. Non rimetterle in
discussione senza parlarne.

**Scrigno copia i file, non li collega.** La libreria sta in `~/Immagini/Scrigno/`,
dentro organizzata per data. Se l'app si limitasse a puntare ai file dove sono,
basterebbe rinominare una cartella nel Finder per svuotare mezza libreria. Il prezzo
è lo spazio su disco, e l'app lo dice durante l'importazione.

**L'originale non viene mai riscritto.** Ogni modifica — luce, colore, ritaglio,
geometria, sfocatura, taglio video — è salvata come istruzione separata dal file.
«Ripristina originale» svuota quelle istruzioni e la foto torna com'era.

**Quello che si elimina resta 30 giorni** in *Eliminati di recente*, poi sparisce
davvero. La pulizia gira a ogni avvio, l'utente non deve ricordarsene.

**La scatola di sicurezza di macOS (sandbox) è spenta.** È un'app personale,
costruita e usata sullo stesso Mac. Con la sandbox accesa ogni cartella va
autorizzata a mano e i permessi vanno riconquistati a ogni avvio: più pezzi che si
rompono, nessun vantaggio per chi la usa da solo. Va riaccesa se un giorno l'app va
data ad altri: le righe che servono sono già scritte e commentate in
`app/Scrigno.entitlements`.

**Altro, deciso e non più toccato:** identificativo `it.scrigno.app`, lingua
italiana unica, tema solo scuro, minimo macOS 15.

---

## 5. L'app: cosa c'è dentro

45 file Swift, circa 8.400 righe. Tutte e quattro le fasi delle istruzioni.

**Fase 1 — Base.** Finestra e barra laterale come nell'anteprima. Importazione di
file e cartelle per trascinamento o col pulsante, qualunque tipo di file, con i
doppioni riconosciuti e scartati *prima* che entrino. Miniature. Griglia per giorno.
Filtri per tipo. Pannello Informazioni con i dati di scatto. Etichette colorate,
album, preferiti, ricerca, cestino a 30 giorni. Schermata di primo avvio.
Scorciatoie.

**Fase 2 — Video e modifica.** Lettore, taglio, velocità, salva fotogramma,
esportazione 1080p. Schermata Modifica con luce, colore, dettaglio, vignettatura,
ritaglio, otto stili pronti, confronto prima/dopo trascinabile, annulla e ripeti.

**Fase 3 — Funzioni intelligenti.** Testo letto dentro PDF e scansioni, scadenze
pescate dai documenti, ricerca che guarda dentro i documenti, mappa dei luoghi dai
dati GPS, doppioni identici e somiglianti con la proposta di quale tenere, schede SD.

**Fase 4 — Persone.** Volti trovati, raggruppati, nomi messi a mano, correzioni.

### Le quattro aggiunte chieste in corsa

**Arrivo da Android** (e da qualsiasi telefono). macOS non sa leggere un telefono
Android collegato col cavo: servirebbe un programma di terze parti. Scrigno prende
un'altra strada: apre una pagina sulla rete di casa, il telefono inquadra un codice
QR, sceglie foto e file dal browser e li manda. Niente da installare sul telefono,
funziona uguale con iPhone e tablet. C'è un codice di quattro cifre nell'indirizzo,
così chi è sulla stessa rete non può mandare roba senza averlo letto sullo schermo
del Mac. La pagina sta in piedi solo finché la finestra Importa è aperta.

**Geometria.** Raddrizza l'orizzonte storto, linee cadenti (i palazzi che si chiudono
verso l'alto), prospettiva laterale, ruota a scatti di 90°, ribalta in orizzontale e
in verticale.

**Sfocatura con profondità di campo.** Tre cursori — intensità, punto a fuoco,
profondità di campo — e quattro modi di lavorare: **banda** inclinabile (paesaggi,
tavole), **cerchio** attorno al punto su cui clicchi (ritratti, oggetti), **dal
telefono** (segue le distanze vere che i ritratti dell'iPhone si portano dietro, e
quando non ci sono ricade sul cerchio invece di inventarsele), **pennello** a mano
libera con dimensione, morbidezza, inverti e pulisci. La maschera dipinta si salva
in un file suo, accanto alla foto.

C'è anche l'icona dell'app, generata qui: il cassetto scuro su fondo ambra sfumato,
come nell'anteprima.

---

## 6. La cosa da sapere prima di provarla

**Il codice non è mai stato compilato.** Questa sessione girava su Linux, dove Swift
e Xcode non esistono: nessuna riga è passata da un compilatore, figurarsi da una
prova su file veri.

Quello che si poteva fare da lì è stato fatto: un controllo automatico su tutti i
file, che ha trovato e fatto correggere quattro difetti veri — un nome di parametro
sbagliato, tre import mancanti, un grassetto che non si sarebbe visto.

Il primo passo sul Mac è aprire il progetto in Xcode e sistemare gli errori che
salteranno fuori. Sono attesi, e sono il lavoro normale che resta.

Se il file di progetto (`Scrigno.xcodeproj`) non si apre — è stato scritto a mano,
senza Xcode per provarlo — in `app/COME-APRIRLA.md` c'è la ricetta per rifarlo in due
minuti senza perdere una riga di codice.

---

## 7. Cosa resta da fare, in ordine

1. **Aprire il progetto in Xcode e farlo compilare.** È il lavoro vero che resta.
2. **Provarlo su file veri.** La prova di fine Fase 1 scritta nelle istruzioni:
   importa una cartella con 500 file mescolati, controlla che siano divisi per giorno,
   etichettane qualcuno, cercali, eliminane uno e recuperalo. Poi chiudi e riapri
   l'app: deve esserci tutto.
3. Sistemare quello che non funziona come previsto.

---

## 8. Differenze note rispetto all'anteprima approvata

Tre cose nell'app non sono identiche al progetto grafico. Vanno dette, non nascoste.

**La griglia è regolare, non a mosaico.** Nell'anteprima i riquadri hanno misure
diverse. Con 15.000 elementi una griglia regolare scorre fluida, un mosaico no. Si
può rifare se si preferisce l'aspetto alla scorrevolezza, ma va chiesto.

**Il carattere Fraunces non è dentro l'app.** Il file non era scaricabile da dove si
lavorava. Finché non c'è, il nome «Scrigno» usa il serif corsivo di sistema, che
somiglia. Per metterlo: scarica Fraunces da Google Fonts, trascina il `.ttf` in
`app/Scrigno/Risorse/` e aggiungi `ATSApplicationFontsPath` a `Info.plist`.

**iPhone e Android col cavo non ci sono.** Solo Wi-Fi, schede SD e cartelle.

Altre cose che non ci sono ancora, e il perché: la **stabilizzazione video** (è la
parte più lenta da elaborare, rimandata); il **ritocco a togliere** un neo o un
granello (macOS non offre niente di pronto, e lo strumento *Ritocco* oggi dipinge la
maschera della sfocatura, non cancella oggetti); la **ricerca dentro Word, Excel e
Keynote** (sono archivi compressi, leggerli è un lavoro a parte — dentro PDF e
scansioni invece funziona).

---

## 9. Quale modello usare

Richiesta fissa del committente: farglielo sapere ogni volta.

**Sonnet** per il lavoro che viene: correggere errori di compilazione è meccanico —
si legge il messaggio, si aggiusta la riga. Lo fa bene e costa molto meno.

**Opus** quando qualcosa non funziona e non si capisce perché, o quando ci sono
decisioni di progetto da prendere. Questa sessione è stata fatta con Opus: trovare
difetti nascosti in codice altrui e decidere l'impianto dell'app è esattamente il
suo lavoro.

---

## 10. Come si lavora con il committente

Dalle istruzioni, e vale per chi riprende in mano il progetto:

- **Sempre in italiano**, tono concreto. Chi scrive non è un programmatore: spiega
  cosa cambia per chi usa l'app, non solo il codice.
- Se una cosa non si può fare, o viene peggio del previsto, **dillo** invece di
  aggirarla.
- **Prova su dati veri.** Non dare niente per funzionante solo perché il codice
  compila.
- L'aspetto dell'anteprima è approvato: riproducilo, e **chiedi prima** di cambiare
  qualcosa di visibile.
- **Fai vedere il risultato prima di proseguire.**
- Prima di chiudere una sessione, aggiorna `STATO.md`.
