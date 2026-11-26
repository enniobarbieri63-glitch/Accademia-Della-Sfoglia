# Scrigno — a che punto siamo

Aggiornato: 13 settembre 2026

> La conversazione in cui è nato il progetto è stata cancellata. Tutto quello che
> era stato detto a voce lì dentro — richieste, decisioni, motivi — è in
> **`DIARIO.md`**, nella stessa cartella. Leggilo per primo.

## In breve

Il progetto grafico è approvato, l'anteprima è stata riparata e provata, e **l'app
per Mac è scritta per intero**: circa 8.400 righe di Swift in 45 file, con tutte e
quattro le fasi coperte.

**Non è mai stata compilata.** Il lavoro è avvenuto su una macchina Linux, dove
Swift e Xcode non esistono: nessuna riga è stata provata da un compilatore, figurarsi
su dati veri. Il primo passo sul Mac è aprire il progetto e sistemare gli errori che
salteranno fuori. Sono attesi, e sono la parte normale del lavoro che resta.

## Fatto

**Anteprima** (`anteprima/index.html`) — riparata, migliorata, verificata con 31
controlli automatici in un browser vero. Vedi `anteprima/NOTE-RIPARAZIONI.md`.

**Istruzioni** (`ISTRUZIONI.md`) — completate con le decisioni che mancavano: dove
finiscono i file importati, cosa fare quando qualcosa va storto, le scorciatoie,
gli obiettivi di velocità, l'accessibilità.

**App** (`app/`) — scritta tutta. Vedi `app/COME-APRIRLA.md` per aprirla.

| Fase | Cosa c'è |
|---|---|
| 1 — Base | Finestra, barra laterale, importazione con doppioni saltati, miniature, griglia per giorno, filtri, pannello Informazioni con i dati di scatto, etichette, album, preferiti, ricerca, cestino 30 giorni, primo avvio, scorciatoie |
| 2 — Video e modifica | Lettore, taglio, velocità, salva fotogramma, esportazione 1080p. Modifica con luce, colore, geometria, dettaglio, sfocatura, vignettatura, ritaglio, otto stili, prima/dopo, annulla e ripeti, Ripristina originale |
| 3 — Funzioni intelligenti | Testo dentro PDF e scansioni, scadenze pescate dai documenti, ricerca dentro i documenti, mappa dei luoghi, doppioni identici e somiglianti con la proposta di quale tenere, schede SD |
| 4 — Persone | Volti trovati e raggruppati, nomi messi a mano, correzioni |

**In più, chiesto in corsa:**
- **Arrivo da Android** (e da qualsiasi telefono) via pagina Wi-Fi con codice QR.
- **Geometria**: raddrizza, linee cadenti, prospettiva laterale, ruota, ribalta.
- **Sfocatura** con profondità di campo, in quattro modi: banda, cerchio (sul punto),
  dalle distanze del telefono, pennello a mano libera.

## Da fare, nell'ordine

1. **Aprire il progetto in Xcode e farlo compilare.** È il lavoro vero che resta.
2. **Provarlo su file veri**: una cartella con 500 file mescolati, come dice la
   prova di fine Fase 1 nelle istruzioni.
3. Sistemare quello che non funziona come previsto.

## Decisioni prese, da non rimettere in discussione senza parlarne

- La libreria **copia** i file in `~/Immagini/Scrigno/`. Gli originali restano dove sono.
- Identificativo `it.scrigno.app`, lingua italiana, tema solo scuro.
- L'originale non viene mai riscritto: ogni modifica è un'istruzione a parte.
- Quello che si elimina resta 30 giorni in *Eliminati di recente*.
- La scatola di sicurezza (sandbox) è spenta: è un'app personale. Vedi
  `app/Scrigno.entitlements`, il perché è scritto lì dentro.

## Differenze note rispetto all'anteprima

- **La griglia è regolare, non a mosaico.** Nell'anteprima i riquadri hanno misure
  diverse. Con 15.000 elementi una griglia regolare scorre fluida, un mosaico no.
  Si può rifare, ma va chiesto.
- **Il carattere Fraunces non è dentro l'app**: il file non era scaricabile da qui.
  Finché non c'è, il nome "Scrigno" usa il serif corsivo di sistema, che somiglia.
  Per metterlo: scarica Fraunces da Google Fonts, trascina il `.ttf` in
  `app/Scrigno/Risorse/` e aggiungilo a `Info.plist` sotto `ATSApplicationFontsPath`.
- **iPhone e Android col cavo** non ci sono: solo Wi-Fi, schede SD e cartelle.
