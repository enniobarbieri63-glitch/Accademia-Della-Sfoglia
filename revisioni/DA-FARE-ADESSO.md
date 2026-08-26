# Cosa dare a Claude Code adesso — l'ordine di consegna

Aggiornato al **26/08/2026**, dopo la 3.281.0. Sostituisce ogni elenco precedente.

**Un documento è superato e non va consegnato**: vedi in fondo.

---

## L'ordine

| | Cosa | Documento | Perché in questa posizione |
|---|---|---|---|
| **1** | **Lo sfarfallio** | `VERIFICA-3.281.0.md` § **C** | è l'unica cosa che Ennio vede rotta, e falsa ogni prova sul menu |
| **2** | **La scelta manuale che non si disfa** | `MENU-DECISIONI-DI-ENNIO.md` § **4** | senza, il pulsante non funziona proprio dove è l'unica possibilità |
| **3** | **Il pulsante solo dove serve + il nome nuovo** | `MENU-DECISIONI-DI-ENNIO.md` § **1, 2, 3** | dopo il 2, o si prova un pulsante che ancora si disfa |
| **4** | **A1 + B1** — due difetti silenziosi | `VERIFICA-3.281.0.md` § **A1, B1** | due righe ciascuno, chiudono due modi di rompersi senza dirlo |
| **5** | **Il Tavolo senza limite giornaliero** | `TAVOLO-SENZA-LIMITE.md` | decisione di Ennio già chiusa, non ci sono domande aperte |
| **6** | **L'elenco degli utenti** (solo il Passo 1) | `RESET-RISPOSTE-DI-ENNIO.md` § **Passo 1** | non richiede decisioni e sblocca tutto il reset |
| **7** | **A2, B2, B3** | `VERIFICA-3.281.0.md` | il resto della verifica, nessuna fretta |
| **8** | **Il reset vero e proprio** | `RESET-PRIMA-DEL-1-OTTOBRE.md` + `RESET-RISPOSTE-DI-ENNIO.md` | dopo che Ennio ha guardato l'elenco del punto 6 |

**Dal 9 in poi si torna al piano di prima**: i Giri 3-6 mai iniziati (A2 il doppio clic sui
livelli, E4, A5, E5, B1-B6, E7, F1 il tetto ai punti giornalieri, F2, F3, D1-D3), e quello che
esce dalla seconda lettura mentre va avanti.

---

## I quattro documenti da consegnare

1. **`VERIFICA-3.281.0.md`** — sfarfallio (C), A1, A2, B1, B2, B3
2. **`MENU-DECISIONI-DI-ENNIO.md`** — il pulsante «Fissa il menu in alto»
3. **`TAVOLO-SENZA-LIMITE.md`** — foto libere, punti una volta al giorno
4. **`RESET-RISPOSTE-DI-ENNIO.md`** — cosa si azzera e cosa no, e la procedura sugli account

Più, come riferimento di sfondo se serve: `RESET-PRIMA-DEL-1-OTTOBRE.md`,
`LETTURA-2-BLOCCO-3-PARTNER.md`, `LETTURA-2-BLOCCO-4-PERCORSI.md`.

---

## ⛔ Non consegnare: `ORDINE-CON-GAMING-SPENTO.md`

**È superato.** Quel documento chiedeva di costruire `includes/diagnosi-author.php`, un file
usa-e-getta per scoprire **se** il tema alterava le ricerche per autore anche in produzione.

**Quella domanda ha già avuto risposta**: Claude Code ha trovato la causa
(`the_newspaper_post_author_archive()`), l'ha corretta in 13 punti nella 3.281.0, e ha
confermato gli effetti che c'erano in produzione. **Costruire adesso quella diagnosi
sarebbe lavoro per rispondere a una domanda già chiusa.**

Quello che di quel documento resta valido è **una cosa sola**, ed è già in coda come punto
**7**: la riga in Diagnostica (**A2** della verifica 3.281.0), che è la versione permanente
della stessa idea — accorgersi se un domani la correzione smette di servire.

---

## Le tre decisioni ancora ferme su Ennio

Nessuna blocca i punti 1-8. Ma **nessuna delle tre può essere decisa da Claude Code**, e due
sono ferme da parecchio.

### a) Il campanello della Posta interna — ferma dal 24/08

Oggi in Posta interna arrivano le disdette con l'acconto da restituire e, da novembre, i
rendiconti della chiusura del mese. **Non c'è nessun avviso**: né email, né aeroplanino, né
un numero di non letti. La funzione per contarli (`gs_inbox_non_letti()`) è scritta e non
collegata a niente.

Tre opzioni, dalla più leggera: un numero accanto alla voce «Posta interna»; **una riga nello
Stato Generale** (quella che consiglio); un aeroplanino solo per le disdette con acconto.

**È diventata più urgente:** con il Tavolo senza limite, quella casella riceverà più roba, e
con i partner (P4) c'è una vetrina pagata che aspetta.

### b) P4 — la vetrina del partner che si spegne quando lui la modifica

Dal blocco 3: oggi un partner che corregge un refuso **toglie da solo dal sito la vetrina
che ha pagato**, finché Ennio non riapprova. E il testo del pannello gli dice il contrario.

**Consiglio la strada 2** — la vetrina già approvata resta online mentre la modifica aspetta.
In ogni caso **la frase del pannello va corretta**, perché oggi non è vera.

### c) L'export delle opzioni del tema da produzione a guru2

`D2` della verifica 3.281.0. Guru2 non ha il tema configurato come il sito vero — l'ha
scritto Claude Code stesso nel changelog della 3.279. **È il tema che ha causato il difetto
più grosso di tutto il progetto**, e finché i due siti non si somigliano, ogni prova che
riguarda intestazione, nastro o scorrimento vale solo fino a prova contraria.

Nel tema Newspaper c'è Import/Export nelle opzioni. Mezz'ora di Ennio.

---

## Cosa faccio io nel frattempo

`voting.php` e `giuria-turno.php` — le sfide e le giurie. Sono i primi due dei **diciassette
file che toccano punti o badge**, cioè il gruppo dove, dopo L1 e L2, mi aspetto il resto.

**Non aspettate me per i punti 1-8**: sono lavori diversi e vanno in parallelo, come è stato
finora.
