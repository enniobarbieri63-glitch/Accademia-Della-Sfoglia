# Scrigno — come aprirla e provarla sul Mac

## In due minuti

1. Installa **Xcode** dall'App Store (gratuito, circa 15 GB) e aprilo una volta per
   accettare la licenza.
2. Fai doppio clic su **`Scrigno.xcodeproj`**.
3. In alto a sinistra premi il triangolo ▶ (oppure ⌘R).

La prima compilazione ci mette qualche minuto. Poi si apre la finestra di Scrigno.

## Se Xcode non apre il progetto

Il file di progetto è stato scritto a mano, senza Xcode sottomano per provarlo. Se
dà problemi, si rifà in due minuti e non si perde niente del codice:

1. Xcode › **File › New › Project…** › macOS › **App**
2. Nome `Scrigno`, Interface **SwiftUI**, Language **Swift**, Storage **None**
3. Salva il progetto da un'altra parte, poi **cancella** la cartella `Scrigno` che
   Xcode ha creato dentro e **trascina al suo posto** la cartella `Scrigno` che
   trovi qui, spuntando *Copy items if needed* e *Create groups*
4. Nelle impostazioni del target (scheda **Signing & Capabilities**):
   - togli la spunta a **App Sandbox** (vedi più sotto il perché)
   - in **Info** aggiungi la chiave `NSLocalNetworkUsageDescription` con il testo
     che trovi in `Info.plist`
5. Minimo di sistema: **macOS 15**

## La prima volta che parte

La finestra è vuota, e lo dice. Da lì puoi:

- **trascinare dentro** una cartella di foto,
- premere **Importa una cartella…**,
- oppure **Manda dal telefono** per mandare roba da Android o iPhone.

I file vengono **copiati** in `~/Immagini/Scrigno/`. Gli originali restano dove sono:
Scrigno non li sposta e non li cancella.

## Cosa provare, in ordine

1. **Importa** una cartella con dentro roba mescolata: foto, video, PDF, un audio.
   Guarda che i doppioni vengano segnati prima ancora di entrare.
2. In **Libreria**, controlla che tutto sia diviso per giorno. Clicca una foto:
   a destra compaiono diaframma, tempo, ISO, luogo.
3. Mettile un'**etichetta** dal menu che si apre col tasto destro.
4. **Eliminane** una con ⌘⌫, vai in *Eliminati di recente*, rimettila a posto.
5. **⌘3** su una foto: prova i cursori, la **Geometria** (raddrizza, linee cadenti,
   ribalta) e la **Sfocatura**. Premi *Ripristina originale*: deve tornare com'era.
6. **Chiudi e riapri l'app.** Deve esserci tutto.

## Le scorciatoie

| Tasti | Cosa fa |
|---|---|
| ⌘1 … ⌘6 | Libreria, Video, Modifica, Documenti, Persone e luoghi, Doppioni |
| ⌘F | Porta il cursore nella ricerca |
| ⌘I | Apre la finestra Importa |
| ⌘⌫ | Sposta in *Eliminati di recente* |
| ⌘Z / ⇧⌘Z | Annulla e ripeti, nella schermata Modifica |

## Mandare foto da un telefono Android

macOS non sa leggere un telefono Android collegato col cavo: servirebbe un programma
di terze parti. Scrigno prende un'altra strada, che funziona sempre:

1. Apri **Importa** e scegli **Telefono o tablet**.
2. Sul Mac compare un **codice QR**.
3. Inquadralo col telefono: si apre una pagina.
4. Scegli foto, video o file e premi **Manda**.

Telefono e Mac devono stare sulla stessa rete Wi-Fi. La pagina resta aperta solo
finché la finestra Importa è aperta, e vuole un codice di quattro cifre che leggi
sullo schermo: chi è sulla stessa rete non può mandarti niente senza averlo visto.

La prima volta macOS chiede il permesso di usare la **rete locale**: va dato, o la
pagina non si apre.

Funziona uguale con iPhone, iPad e qualsiasi cosa abbia un browser.

## Perché la scatola di sicurezza è spenta

Il file `Scrigno.entitlements` tiene la *sandbox* di macOS spenta. È una scelta,
non una dimenticanza: questa è un'app personale, costruita e usata sullo stesso Mac.
Con la sandbox accesa ogni cartella va autorizzata a mano e i permessi vanno
riconquistati a ogni avvio — più cose che si rompono, nessun vantaggio per chi la
usa da solo.

Va riaccesa se un giorno l'app va data ad altri. Le righe che servono sono già
scritte nel file, commentate.

## Cosa non c'è ancora

- **iPhone e Android col cavo.** Solo Wi-Fi, schede SD e cartelle.
- **Stabilizzazione video.** È la parte più lenta da elaborare, rimandata.
- **Ritocco a togliere** (il neo, il granello). macOS non offre niente di pronto:
  lo strumento *Ritocco* dipinge la maschera della sfocatura, non cancella oggetti.
- **Word, Excel e Keynote**: la ricerca non legge dentro. Dentro i PDF e le
  scansioni sì.
- **La griglia a mosaico** dell'anteprima, con i riquadri di misure diverse. Qui
  sono tutti uguali: con 15.000 elementi la griglia regolare scorre, il mosaico no.
  Se lo preferisci si può rifare, dimmelo.
