# Istruzioni per Claude

## Cambio modello durante la scrittura del codice

L'utente lavora normalmente su Sonnet e cambia modello a mano col comando
`/model`. Quando in una sessione si sta scrivendo codice, segnalare
esplicitamente il momento giusto per passare a Opus, e poi avvisare quando
si può tornare a Sonnet:

- **Serve Opus** — prima di una decisione di sicurezza delicata (per
  esempio un meccanismo di sessione o di autenticazione), prima di una
  scelta di architettura che condiziona molto lavoro successivo, o per la
  revisione finale di codice che tocca dati personali o pagamenti.
- **Si torna a Sonnet** — per il resto: implementazione che segue una
  specifica già scritta, codice che rispecchia convenzioni già stabilite,
  correzioni e attività di routine.

Non cambiare modello da soli: segnalare a parole quando conviene, l'utente
decide se attivarlo con `/model`.
