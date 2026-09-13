# Scrigno — istruzioni per costruire l'app per Mac

Questo documento accompagna il file **`anteprima/index.html`**: aprilo in un browser
per vedere il progetto grafico approvato, con 6 schermate e la finestra Importa. Il
compito è trasformarlo in un'app per Mac vera, **a fasi**, partendo dalla Fase 1.

Insieme a questo file trovi:

| File | A cosa serve |
|---|---|
| `anteprima/index.html` | Il progetto grafico approvato, navigabile |
| `ISTRUZIONI.md` | Questo documento: cosa costruire e come |
| `STATO.md` | Dove è arrivato il lavoro, aggiornato a fine di ogni sessione |
| `app/` | L'app vera, scritta in Swift. Da aprire con Xcode |
| `app/COME-APRIRLA.md` | Come aprirla, cosa provare, cosa non c'è ancora |

---

## 1. Come lavorare con il committente

- **Sempre in italiano**, con un tono concreto. Chi ti scrive non è un programmatore:
  spiega *cosa cambia per chi usa l'app*, non solo il codice.
- Se una cosa non si può fare o viene peggio del previsto, **dillo chiaramente** invece
  di aggirarla.
- **Test su dati reali:** importa davvero foto, video e PDF di prova e controlla il
  risultato. Non dare nulla per funzionante solo perché il codice compila.
- Alla fine di ogni fase consegna un'app che si apre e si usa, e spiega in poche righe
  cosa provare.
- **L'aspetto dell'anteprima è già approvato.** Riproducilo il più fedelmente
  possibile. Chiedi prima di cambiare qualcosa di visibile.
- **Prima di chiudere la sessione aggiorna `STATO.md`**: cosa è finito, cosa è rimasto
  a metà, dove ti eri fermato. Una fase dura più sessioni e chi riprende deve capire
  in due minuti dove mettere le mani.

## 2. Prima di iniziare

- **Xcode non è installato** su questo Mac (sono presenti solo i Command Line Tools).
  Serve Xcode dall'App Store: è gratuito e occupa circa 15 GB. Poi va aperto una volta
  per accettare la licenza.
- macOS installato: **26.5**.
- Per l'uso personale non serve un account Apple Developer a pagamento. Serve solo per
  distribuire l'app ad altri (circa 99 €/anno).
- Senza account a pagamento l'app si firma "per lo sviluppo locale": funziona su questo
  Mac, e se un giorno la si vuole dare a qualcun altro va rifirmata. Dichiaralo subito,
  non a cose fatte.

**Nomi da usare** (decisi, non cambiarli in corsa):

| Cosa | Valore |
|---|---|
| Nome dell'app | Scrigno |
| Identificativo | `it.scrigno.app` |
| Cartella del progetto | `scrigno/app/` in questo stesso deposito |
| Lingua dell'interfaccia | italiano, unica lingua per ora |

## 3. Scelte tecniche consigliate

| Tema | Scelta | Perché |
|---|---|---|
| Linguaggio / interfaccia | **Swift + SwiftUI**, app nativa macOS | Aspetto e prestazioni da vera app Mac |
| Versione minima | macOS 15 | Accesso alle API recenti (SwiftData, Vision) |
| Archivio dati | **SwiftData** (catalogo) + cartella libreria gestita dall'app | Niente server, tutto sul Mac |
| Miniature | Generate all'importazione, salvate in una cache su disco | Griglia fluida anche con 15.000+ elementi |
| Dati di scatto (EXIF, GPS) | ImageIO | Nativo, legge HEIC/JPG/RAW |
| Video | AVFoundation / AVKit | Riproduzione, taglio, esportazione, fotogrammi |
| Modifica foto | Core Image | Filtri non distruttivi |
| Testo in PDF e scansioni | PDFKit + Vision (riconoscimento testo) | Ricerca dentro i documenti |
| Mappa | MapKit | Luoghi dai dati GPS |
| Volti | Vision (rilevamento) + raggruppamento | Vedi i limiti al punto 9 |
| Doppioni | Hash del file (identici) + hash percettivo (simili) | Raffiche e formati diversi |
| Import da iPhone / scheda SD | ImageCaptureCore | Come l'app Acquisizione Immagine |

**Regola di base: l'originale non si tocca mai.** Ogni modifica (luce, colore,
ritaglio, taglio video) va salvata come *istruzioni* separate dal file, così
"Ripristina originale" funziona sempre.

**Eliminazioni sempre recuperabili:** tutto ciò che si elimina va in "Eliminati di
recente" e ci resta 30 giorni. Solo dopo si cancella davvero.

## 4. Dove finiscono i file (deciso, serve per la Fase 1)

Questa è la domanda che va risolta prima di scrivere la prima riga, perché cambia tutto
il resto.

**Scrigno copia i file dentro una sua cartella.** Non lascia gli originali sparsi in
giro e non li sposta.

- La libreria sta in `~/Immagini/Scrigno/` e dentro è organizzata per data:
  `Originali/2026/09/12/IMG_4821.HEIC`.
- Le miniature stanno in `~/Libreria/Caches/it.scrigno.app/` — si possono buttare senza
  perdere niente, l'app le rigenera.
- Il catalogo (nomi, date, etichette, album, modifiche) è un file SwiftData dentro la
  cartella della libreria, non nei Caches: va salvato nei backup.
- Se importi un file già presente (stesso contenuto), l'app non ne fa una seconda copia:
  lo segnala come doppione e lo salta.

**Perché copiare e non collegare:** se l'app si limitasse a puntare ai file dove sono,
basterebbe rinominare una cartella nel Finder per svuotare mezza libreria. Copiando,
Scrigno risponde sempre di quello che ha dentro. Il prezzo è lo spazio su disco: va
detto all'utente durante l'importazione, con lo spazio richiesto e quello disponibile.

**Conseguenze da gestire, non da scoprire dopo:**

- **Disco pieno durante un'importazione:** l'app si ferma, dice quanti file ha importato
  e quali no, e non lascia file a metà.
- **L'utente sposta la cartella `Scrigno`:** al primo avvio successivo l'app non la
  trova e chiede dove è finita, invece di mostrare una libreria vuota.
- **Permessi:** per leggere le cartelle da cui si importa serve il consenso dell'utente,
  e va conservato (segnalibro con ambito di sicurezza) o al riavvio l'app non può più
  rileggere quelle cartelle.
- **Backup:** basta che Time Machine copi `~/Immagini/Scrigno/`. Scrivilo nelle note
  dell'app, in chiaro.

## 5. Aspetto grafico (preso dall'anteprima)

L'app ha un solo tema scuro, voluto: le foto risaltano meglio.

**Colori**
| Nome | Valore | Uso |
|---|---|---|
| Fondo finestra | `#1b1c22` | area centrale |
| Barra laterale | `#16171c` | colonna sinistra e pannelli destri |
| Pannello | `#202128` | riquadri, schede |
| Rialzato | `#282a32` | pulsante attivo, etichette |
| Linea | `#2c2e37` | bordi e separatori |
| Linea chiara | `#393b46` | bordi dei campi, cornici dei cursori |
| Testo | `#ece9e3` | testo principale |
| Testo secondario | `#8e8c96` | sottotitoli, conteggi |
| Testo tenue | `#5d5c66` | intestazioni di gruppo, numeri piccoli |
| Accento ambra | `#e9ae4f` | selezione, pulsanti principali |
| Ambra velata | `#e9ae4f` al 16% | sfondo della voce selezionata |
| Scritte sull'ambra | `#1d1506` | testo dentro i pulsanti ambra |
| Etichetta Famiglia | `#e06d5c` | |
| Etichetta Lavoro | `#6f8cf0` | |
| Etichetta Viaggi | `#58b39c` | |
| Etichetta Da stampare | `#d6a23f` | |
| Audio | `#b77fd6` | note vocali, onde sonore |

**Caratteri**
- **Nome "Scrigno"** e numeri grandi in evidenza: *Fraunces corsivo* (Google Fonts,
  licenza libera, da includere nell'app — non scaricarlo a ogni avvio).
- **Interfaccia:** nell'anteprima è *Onest*. Nell'app va bene anche il carattere di
  sistema (SF Pro), che è più nativo. Proponi la scelta al committente.
- **Dati tecnici** (f/1.8, 1/250, ISO, durate, pesi): carattere a spaziatura fissa
  (*JetBrains Mono* o SF Mono).

**Dettagli ricorrenti**
- Elemento selezionato: bordo ambra con un cerchietto di spunta.
- Video nella griglia: durata in basso a sinistra (es. `3:18`).
- Documenti nella griglia: etichetta del tipo colorata (PDF, DOCX, M4A, ZIP, KEY).
- Etichette: pallini colorati in basso a destra sulla miniatura.
- Icona dell'app: un cassetto scuro su fondo ambra sfumato (vedi l'anteprima in alto a sinistra).

**Scorciatoie da tastiera** (nell'anteprima sono scritte accanto ai nomi delle schermate,
quindi nell'app devono esistere davvero)

| Tasti | Cosa fa |
|---|---|
| ⌘1 … ⌘6 | Libreria, Video, Modifica, Documenti, Persone e luoghi, Doppioni |
| ⌘F | Porta il cursore nel campo di ricerca della schermata aperta |
| ⌘I | Apre la finestra Importa |
| Spazio | Anteprima grande dell'elemento selezionato |
| Invio | Apre l'elemento selezionato (le foto in Modifica) |
| ⌘⌫ | Sposta in "Eliminati di recente" |
| Esc | Chiude la finestra Importa o l'anteprima grande |

## 6. Le schermate

La finestra ha una barra laterale sinistra fissa e l'area principale a destra.

**Barra laterale:** Libreria (Tutto, Foto, Video, Documenti, Audio, Altri file) ·
Cassetti (Persone, Luoghi, Preferiti) · Raccolte intelligenti (Senza etichetta, Possibili
doppioni, Scontrini e ricevute) · Album · Etichette colorate · Eliminati di recente.
In fondo c'è la barra dello spazio su disco, divisa per tipo.
Ogni voce deve fare qualcosa: o cambia schermata, o filtra la libreria.

1. **Libreria.** Griglia raggruppata per giorno, con data e luogo nell'intestazione.
   Vista per Anni / Mesi / Giorni / Tutti. Le foto convivono con qualsiasi tipo di file.
   In alto i filtri per tipo, con i conteggi. A destra il pannello **Informazioni**:
   anteprima, nome, data, dati di scatto (diaframma, tempo, ISO, focale), fotocamera,
   dimensioni, peso, etichette, parole riconosciute in automatico, album, mappa del luogo.
   Il pannello segue la selezione, e per un documento o un audio nasconde i dati di
   scatto invece di mostrarli vuoti.
2. **Video.** Lettore grande (play, tempo, velocità, volume, sottotitoli). Strumenti:
   Taglia, Velocità, Stabilizza, Salva fotogramma, Titolo, Colore, Esporta 1080p.
   Timeline con i fotogrammi, parte da tenere evidenziata in ambra, traccia audio e
   capitoli. A destra, i video recenti divisi per giorno.
3. **Modifica.** Foto al centro con griglia dei terzi e confronto prima/dopo
   trascinabile. Sotto, gli stili pronti (Originale, Vivido, Caldo, Freddo, Pellicola,
   Drammatico, Bianco e nero, Argento). A destra: istogramma, strumenti (Regola,
   Ritaglia, Ritocco, Auto) e cursori a gruppi apribili: Luce (Esposizione, Contrasto,
   Alte luci, Ombre), Colore (Temperatura, Saturazione), Dettaglio, Vignettatura.
   Pulsanti: Annulla, Ripeti, Ripristina originale, Fatto.
   Ogni cursore deve avere un effetto visibile: se uno non fa niente, va tolto dal
   pannello, non lasciato lì per bellezza.
4. **Documenti.** Elenco con nome, data, peso ed etichetta. La ricerca trova il testo
   *dentro* PDF, documenti e scansioni e mostra la frase trovata evidenziata. A destra
   l'anteprima con le pagine, un promemoria ricavato dal documento (es. scadenza di una
   garanzia) e il collegamento ad altri file (es. lo scontrino).
5. **Persone e luoghi.** Volti riconosciuti con nome e numero di foto, più il gruppo
   "Da riconoscere". Mappa con le foto raggruppate per luogo e classifica dei luoghi più
   fotografati. Pulsante "Assegna un luogo a mano" per i file senza GPS.
6. **Doppioni.** Gruppi per tipo (raffiche, stessa foto in due formati, video esportato
   due volte). L'app propone quale tenere (la più nitida, l'originale, la risoluzione
   più alta). Un clic su una miniatura cambia la scelta; "Tienile tutte" annulla. A
   destra lo spazio che si libera, diviso per tipo.

**Finestra Importa.** Sorgenti (iPhone, scheda SD, cartelle, trascinamento), miniature
dei nuovi elementi con i doppioni già marcati, riepilogo dello smistamento (foto, video,
documenti, doppioni saltati), interruttori (album per giorno di viaggio, testo nelle
scansioni, eliminazione dal dispositivo, etichetta automatica) e barra di avanzamento.
Si chiude con ✕, clic fuori o Esc.

**Primo avvio, a libreria vuota.** L'anteprima mostra l'app piena. Serve anche la
schermata di partenza: il nome, una riga che spiega cosa fa, un pulsante grande
"Importa" e l'area su cui trascinare cartelle. Senza questa schermata la prima
impressione è una finestra vuota.

> I nomi di persone, luoghi e file che compaiono nell'anteprima sono **esempi
> inventati** per mostrare l'app piena di contenuti. Non vanno messi nell'app.

## 7. Quando le cose vanno storte

Da decidere adesso, non alla prima segnalazione:

- **File illeggibile o rovinato:** entra lo stesso in libreria, con l'icona del tipo al
  posto della miniatura e un avviso nel pannello Informazioni. Non blocca l'importazione.
- **Formato sconosciuto:** entra in "Altri file". Scrigno custodisce, non giudica.
- **Importazione interrotta** (chiusura dell'app, Mac che si addormenta): alla riapertura
  l'app dice cosa era rimasto in sospeso e propone di riprendere.
- **File identico già in libreria:** saltato e contato fra i doppioni, senza chiedere
  nulla.
- **Due file diversi con lo stesso nome:** entrambi entrano, l'app non sovrascrive mai.

## 8. Le fasi e quando una fase è finita

**Fase 1 — Base** (stima 4–6 sessioni lunghe)
- Finestra e barra laterale come nell'anteprima.
- Importazione di file e cartelle trascinandoli dentro o con il pulsante Importa,
  qualunque tipo di file.
- Miniature di foto, video (primo fotogramma), PDF (prima pagina) e icone per gli altri tipi.
- Griglia per giorno, filtri per tipo, pannello Informazioni con i dati di scatto.
- Etichette colorate, album, preferiti, ricerca per nome, Eliminati di recente (30 giorni).
- Schermata di primo avvio e scorciatoie ⌘1–⌘6, ⌘F, ⌘I, ⌘⌫.
- ✅ *Finita quando:* importi una cartella con 500 file misti, li trovi divisi per giorno,
  li etichetti, li cerchi, ne elimini uno e lo recuperi. Tutto regge dopo aver chiuso e
  riaperto l'app.

**Fase 2 — Video e modifica** (3–5 sessioni)
- Lettore video, taglio, velocità, salva fotogramma, esportazione.
- Schermata Modifica con cursori, stili pronti, prima/dopo, ritaglio, Ripristina originale.
- ✅ *Finita quando:* tagli un video ed esporti la parte scelta, modifichi una foto, chiudi
  l'app, la riapri, le modifiche ci sono e l'originale è intatto.

**Fase 3 — Funzioni intelligenti** (4–6 sessioni)
- Testo nei PDF e nelle scansioni, ricerca dentro i documenti, schermata Documenti.
- Mappa dei luoghi dai dati GPS.
- Doppioni identici e simili, con proposta di quale tenere.
- Importazione diretta da iPhone e scheda SD.
- ✅ *Finita quando:* la ricerca di una parola trova un PDF scansionato e una raffica di
  foto viene riconosciuta come doppione.

**Fase 4 — Persone** (3+ sessioni)
- Rilevamento dei volti, raggruppamento, nomi assegnati dall'utente.

## 9. Quanto deve essere veloce

Numeri da rispettare, misurati su una libreria di prova da 15.000 elementi:

| Cosa | Obiettivo |
|---|---|
| Apertura dell'app fino alla griglia | meno di 2 secondi |
| Scorrimento della griglia | fluido, senza scatti |
| Importazione di 500 foto da una cartella | meno di 2 minuti, con avanzamento visibile |
| Ricerca per nome | risultati mentre si digita |
| Memoria occupata a riposo | sotto il mezzo giga |

L'importazione lavora in sottofondo: la finestra resta usabile e si può annullare.

## 10. Accessibilità, da fare subito

Rimediare dopo costa il triplo:

- Ogni pulsante con sola icona ha un nome parlato (VoiceOver).
- Si naviga tutta l'app con il tasto Tab e si vede sempre dove si è.
- Le scorciatoie della tabella al punto 5 funzionano davvero.
- Il testo segue la dimensione scelta nelle impostazioni di sistema.
- "Riduci movimento" toglie le animazioni di passaggio fra schermate.
- Il colore non è mai l'unica informazione: le etichette hanno pallino **e** nome.

## 11. Limiti da dichiarare subito, non da nascondere

- **Riconoscimento dei volti:** macOS trova i volti in una foto, ma capire che più
  foto mostrano la stessa persona richiede un lavoro in più. La precisione sarà
  inferiore all'app Foto di Apple. L'utente dovrà correggere a mano i raggruppamenti.
- **Libreria di Apple Foto:** leggere le foto già presenti nell'app Foto è possibile, con
  il permesso dell'utente. Modificarle da Scrigno è limitato: va chiarito cosa si vuole.
- **Stabilizzazione video:** fattibile, ma è la parte più lenta da elaborare.
- **Riconoscimento automatico del contenuto** (tramonto, laguna…): macOS lo offre, ma
  le parole possono essere approssimative.
- **Testo dentro i documenti:** PDF e scansioni sì, con Vision. I file di Word, Excel e
  Keynote sono archivi compressi: leggerli richiede lavoro in più, e va messo in conto
  nella Fase 3 invece di darlo per scontato.
- **Promemoria dalle scadenze** (la garanzia che scade): funziona quando la data è
  scritta in modo riconoscibile. Su un documento scritto male non esce niente, e l'app
  deve dirlo invece di inventare una data.
- Tutto resta sul Mac: nessun servizio online, nessuna sincronizzazione tra dispositivi,
  salvo richiesta futura.
