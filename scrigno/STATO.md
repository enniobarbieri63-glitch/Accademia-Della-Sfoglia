# Scrigno — a che punto siamo

Aggiornato: 13 settembre 2026

## In breve

Il progetto grafico è approvato e l'anteprima navigabile è stata riparata e provata.
**L'app per Mac non è ancora iniziata.** La Fase 1 parte da zero.

## Fatto

- **Anteprima** (`anteprima/index.html`) — riparata, migliorata e verificata con 31
  controlli automatici in un browser vero. Vedi `anteprima/NOTE-RIPARAZIONI.md`.
- **Istruzioni** (`ISTRUZIONI.md`) — completate con le decisioni che mancavano: dove
  finiscono i file importati, cosa fare quando qualcosa va storto, le scorciatoie da
  tastiera, gli obiettivi di velocità, l'accessibilità.

## Non ancora iniziato

- **Fase 1 — Base.** Nessuna riga di codice Swift scritta. La cartella `app/` non esiste.
- Fasi 2, 3 e 4.

## Da sapere prima di ripartire

- L'app va costruita **su un Mac con Xcode installato**. Non è stato possibile farlo
  finora: il lavoro su questo progetto è avvenuto su una macchina Linux, dove Swift e
  Xcode non esistono e nulla di macOS si può compilare o provare.
- Le decisioni già prese e da non rimettere in discussione senza parlarne: la libreria
  **copia** i file in `~/Immagini/Scrigno/`, l'identificativo è `it.scrigno.app`, la
  lingua è l'italiano, il tema è solo scuro.

## Primo passo della prossima sessione

1. Verificare che Xcode sia installato e aperto almeno una volta.
2. Creare il progetto in `scrigno/app/`, app macOS, SwiftUI, minimo macOS 15.
3. Montare finestra e barra laterale come nell'anteprima, con le voci giuste ma ancora
   senza dati.
4. Aggiornare questo file prima di chiudere.
