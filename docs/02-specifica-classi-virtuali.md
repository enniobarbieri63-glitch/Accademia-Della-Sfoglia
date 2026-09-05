# Specifica funzionale — Cantieri 4 e 5
## Account, ruoli e classi virtuali (versione scuola)

Documento da consegnare a chi sviluppa. Descrive cosa deve fare il sistema, non
come scriverlo. Stima complessiva: 27 giorni/uomo (12 + 15).

---

## 1. Vincoli che vengono prima di tutto

| # | Vincolo | Conseguenza sul progetto |
|---|---|---|
| V1 | Lo studente non fornisce dati personali | niente nome, cognome, e-mail, data di nascita, foto |
| V2 | Il docente non deve creare password per gli studenti | ingresso con codice classe + nickname |
| V3 | Gira in un'ora di lezione | dall'ingresso al primo esercizio: meno di 90 secondi |
| V4 | Rete scolastica lenta, PC vecchi, Chromebook | solo browser, nessuna installazione, banda ridotta |
| V5 | Nessuna pubblicità e nessuna vendita nella versione scuola | il codice commerciale non entra in questo perimetro |
| V6 | Il docente deve poter tornare al registro elettronico con dei voti | esportazione obbligatoria, non opzionale |
| V7 | L'anno scolastico finisce e ricomincia | tutto è dentro un'annualità, con archiviazione e travaso |

V1 e V2 non sono preferenze: sono ciò che rende vendibile il prodotto senza un
percorso privacy lungo mesi.

---

## 2. Gerarchia degli oggetti

```
  LICENZA (contratto, una per istituto, con scadenza)
     │
     ├── ISTITUTO
     │      ├── codice meccanografico
     │      ├── fascia (A / B / C / D)
     │      ├── tetto studenti attivi
     │      └── ANNO SCOLASTICO (2026/27, 2027/28 …)
     │             │
     │             ├── DOCENTE ─────┐  (1..n)
     │             │                │
     │             └── CLASSE ◀─────┘  (1..n per docente)
     │                    ├── codice di ingresso (6 caratteri)
     │                    ├── materia (cucina / pasticceria / sala)
     │                    ├── STUDENTE (nickname)   (1..30)
     │                    └── ASSEGNAZIONE          (1..n)
     │                           ├── modulo o quiz
     │                           ├── apertura / scadenza
     │                           └── TENTATIVO (per studente)
     │                                  ├── punteggio 0-100
     │                                  ├── scarto sui parametri
     │                                  └── esito
     │
     └── AMMINISTRATORE (animatore digitale)  (1..2 per istituto)
```

Regola: **niente esiste fuori da un anno scolastico**. A luglio l'anno si
archivia in sola lettura, a settembre se ne apre uno nuovo. Le classi si possono
duplicare da un anno all'altro senza gli studenti.

---

## 3. Ruoli e permessi

| Azione | Amministratore | Docente | Studente |
|---|---|---|---|
| Vedere i consumi della licenza | ● | — | — |
| Creare e disattivare docenti | ● | — | — |
| Scaricare il rapporto di fine anno | ● | ○ solo le sue classi | — |
| Creare una classe | ○ | ● | — |
| Rigenerare il codice di ingresso | ○ | ● | — |
| Aggiungere o rinominare uno studente | — | ● | — |
| Assegnare moduli e quiz | — | ● | — |
| Vedere i tentativi di tutti | — | ● solo le sue classi | — |
| Esportare i voti | — | ● | — |
| Giocare i moduli assegnati | — | ● in prova | ● |
| Vedere il proprio andamento | — | — | ● |
| Vedere i punteggi dei compagni | — | — | ○ solo classifica anonima, se il docente la attiva |

● pieno · ○ facoltativo/limitato · — negato

L'amministratore **non vede i tentativi dei singoli studenti**: vede solo numeri
aggregati. È una scelta che semplifica la posizione privacy e che ai dirigenti
piace.

---

## 4. Ingresso: i tre percorsi

```
 ┌─────────────────────────────────────────────────────────────────────┐
 │  DOCENTE / AMMINISTRATORE                                           │
 │                                                                     │
 │   sfoglialab.it/scuola                                              │
 │        │                                                            │
 │        ├──▶ [Accedi con Google Workspace]  ─┐                       │
 │        ├──▶ [Accedi con Microsoft 365]    ──┤─▶ verifica dominio    │
 │        └──▶ [Accedi con e-mail + codice]  ──┘   istituto → sessione │
 │                (fallback, codice usa e getta via e-mail)            │
 └─────────────────────────────────────────────────────────────────────┘

 ┌─────────────────────────────────────────────────────────────────────┐
 │  STUDENTE                                                           │
 │                                                                     │
 │   sfoglialab.it/classe                                              │
 │        │                                                            │
 │        ├─▶ digita il CODICE CLASSE (6 caratteri, es. 7KQ2MP)        │
 │        │                                                            │
 │        ├─▶ sceglie il proprio NICKNAME da un elenco già             │
 │        │   preparato dal docente  ("Alunno 12", "Rossi M.", …)      │
 │        │                                                            │
 │        └─▶ dentro. Nessuna password, nessuna e-mail.                │
 │                                                                     │
 │   Il posto occupato resta legato al dispositivo per 30 giorni       │
 │   (cookie tecnico), così alla lezione dopo non deve rifare nulla.   │
 └─────────────────────────────────────────────────────────────────────┘
```

**Perché il nickname lo prepara il docente**: se lo scegliesse lo studente
avresti soprannomi, e il docente non saprebbe a chi mettere il voto. Il docente
carica un elenco (anche solo "Alunno 1…Alunno 25") e tiene lui la
corrispondenza con i nomi veri, sul suo registro. Il sistema non la conosce.

### Casi limite da gestire

| Caso | Comportamento richiesto |
|---|---|
| Due studenti prendono lo stesso nickname | il posto si blocca al primo; il secondo vede "già occupato" |
| Uno studente sbaglia posto | il docente lo libera con un clic dal cruscotto |
| Cambio di aula o di dispositivo | reinserire codice + nickname, il progresso resta |
| Il codice classe gira fuori dalla scuola | il docente lo rigenera; i vecchi accessi decadono |
| Studente che arriva a metà anno | il docente aggiunge un posto, resta lo storico degli altri |
| Classe che supera i 30 | avviso al docente, la licenza fascia A si blocca a 30 |

---

## 5. Flusso del docente, dal primo accesso alla prima lezione

```
  ACCESSO           CREA CLASSE         PREPARA          ASSEGNA        LEZIONE
  ───────           ───────────         ───────          ───────        ───────
  SSO istituto  ─▶  nome classe    ─▶  incolla       ─▶  sceglie    ─▶  proietta
                    (es. 3A Cucina)     l'elenco          moduli 1-3     il codice
                    materia             nickname          scadenza       classe
                    anno                (o genera         venerdì        alla LIM
                                        Alunno 1-25)
       │                 │                   │                │              │
       ▼                 ▼                   ▼                ▼              ▼
   30 secondi        20 secondi         40 secondi       30 secondi     i ragazzi
                                                                         entrano
                                                                         in 60 sec

  TOTALE PREPARAZIONE: sotto i tre minuti. Se serve di più, il docente
  non lo userà una seconda volta.
```

Il cronometro è il criterio di collaudo: **un docente che non ha mai visto il
prodotto deve arrivare alla prima assegnazione in meno di tre minuti**, senza
manuale e senza chiamare nessuno.

---

## 6. Assegnazioni: come funziona un compito

| Campo | Contenuto | Obbligatorio |
|---|---|---|
| Tipo | modulo di gioco / quiz / prova di valutazione | sì |
| Contenuto | quale modulo o quale set di domande | sì |
| Apertura | data e ora da cui è visibile | sì |
| Scadenza | data e ora oltre cui non si consegna | no |
| Tentativi consentiti | 1, 3, illimitati | sì (default: 3) |
| Punteggio minimo | soglia di superamento, 0-100 | no |
| Vale come voto | sì / no | sì (default: no) |
| Modalità | libera / sorvegliata (a tempo, un tentativo) | sì |

### Stati di un'assegnazione

```
   ┌──────────┐   apertura   ┌──────────┐   scadenza   ┌──────────┐
   │  BOZZA   │─────────────▶│  APERTA  │─────────────▶│  CHIUSA  │
   └──────────┘              └──────────┘              └──────────┘
        │                          │                        │
        │ il docente               │ gli studenti           │ nessun nuovo
        │ la può modificare        │ consegnano             │ tentativo,
        │ o cancellare             │ il docente vede        │ il docente
        │                          │ i risultati in         │ corregge ed
        │                          │ tempo reale            │ esporta
        └──────────────────────────┴────────────────────────┘
                        ▲
                        │ il docente può riaprire una CHIUSA
                        │ (utile per gli assenti) → torna APERTA
```

### Stati di un tentativo

```
  NON INIZIATO ──▶ IN CORSO ──▶ CONSEGNATO ──▶ VALUTATO
                       │                          ▲
                       └── ABBANDONATO ───────────┘
                           (chiude la scheda,     (il docente
                            si salva comunque      può correggere
                            il parziale)           il punteggio a mano)
```

Il salvataggio del parziale è obbligatorio: nelle scuole cade la rete, salta la
corrente, suona la campanella. Un tentativo perso è un docente perso.

---

## 7. Cruscotto del docente

Tre viste, nient'altro. Ogni schermata in più è una schermata che nessuno apre.

### Vista 1 — La classe oggi

```
 ┌──────────────────────────────────────────────────────────────────┐
 │  3A CUCINA · codice 7KQ2MP · 24 studenti          [Rigenera]     │
 ├──────────────────────────────────────────────────────────────────┤
 │  Assegnazione: "Modulo 2 — Mattarello"      scade ven 12/03      │
 ├──────────────────────────────────────────────────────────────────┤
 │  ████████████████████░░░░░░░░  18 su 24 hanno consegnato         │
 │                                                                  │
 │  Alunno 03  ██████████ 92   Alunno 11  ██████░░░░ 61            │
 │  Alunno 07  █████████░ 88   Alunno 02  █████░░░░░ 54            │
 │  Alunno 15  █████████░ 85   Alunno 19  ████░░░░░░ 43  ⚠         │
 │  …                                                               │
 │                                                                  │
 │  NON HANNO ANCORA CONSEGNATO: 04, 09, 13, 17, 21, 22            │
 │                                                    [Sollecita]   │
 └──────────────────────────────────────────────────────────────────┘
```

### Vista 2 — Dove sbaglia la classe

Questa è la vista che fa comprare il prodotto. Non mostra i voti: mostra
**l'errore ricorrente**, cioè quello su cui il docente deve tornare in
laboratorio.

```
 ┌──────────────────────────────────────────────────────────────────┐
 │  ERRORI PIÙ FREQUENTI · 3A Cucina · ultime 4 settimane           │
 ├──────────────────────────────────────────────────────────────────┤
 │  Spessore troppo alto sulle tagliatelle    ███████████  71%      │
 │  Riposo dell'impasto troppo breve          ████████░░░  52%      │
 │  Peso del ripieno fuori tolleranza         ██████░░░░░  38%      │
 │  Farina sbagliata per il formato           ███░░░░░░░░  19%      │
 │                                                                  │
 │  ▸ Suggerimento: il 71% chiude a 1,2 mm invece che a 0,8 mm.     │
 │    Scheda tecnica da stampare per il laboratorio: [Tagliatelle]  │
 └──────────────────────────────────────────────────────────────────┘
```

### Vista 3 — Voti da esportare

```
 ┌──────────────────────────────────────────────────────────────────┐
 │  REGISTRO · 3A Cucina · secondo quadrimestre                     │
 ├──────────────────────────────────────────────────────────────────┤
 │  Nickname   Mod.1  Mod.2  Quiz1  Prova  MEDIA  VOTO /10          │
 │  Alunno 01    88     92     75     84     85      8,5            │
 │  Alunno 02    64     54     70     61     62      6,0            │
 │  Alunno 03    91     92     88     90     90      9,0            │
 │  …                                                               │
 ├──────────────────────────────────────────────────────────────────┤
 │  Conversione punteggio → voto:  [modificabile dal docente]       │
 │  0-39 → 4 · 40-54 → 5 · 55-64 → 6 · 65-74 → 7                    │
 │  75-84 → 8 · 85-94 → 9 · 95-100 → 10                             │
 │                                                                  │
 │  [Esporta CSV]  [Esporta per Argo]  [Esporta per Spaggiari]      │
 └──────────────────────────────────────────────────────────────────┘
```

La tabella di conversione dev'essere **modificabile**: ogni docente ha la sua, e
imporgliene una è il modo più rapido per farsi rifiutare.

---

## 8. Esportazione verso i registri elettronici

| Registro | Formato | Priorità |
|---|---|---|
| CSV generico (nickname, prova, punteggio, voto, data) | CSV UTF-8 con BOM, separatore `;` | 1 — obbligatorio |
| Argo | CSV secondo il tracciato di importazione voti | 2 |
| Spaggiari (Classeviva) | CSV secondo il tracciato di importazione voti | 2 |
| Nuvola | CSV | 3 |
| PDF stampabile del tabellone | PDF A4 orizzontale | 2 |

Il BOM e il punto e virgola non sono un dettaglio: senza, Excel in italiano
apre il file tutto in una colonna e il docente pensa che il prodotto sia rotto.

---

## 9. Quiz: struttura della domanda

| Campo | Contenuto |
|---|---|
| Testo | la domanda |
| Tipo | scelta singola / scelta multipla / abbinamento formato-immagine / valore numerico con tolleranza |
| Opzioni | 3-5 risposte |
| Corretta | una o più |
| Tolleranza | solo per il tipo numerico (es. 0,8 mm ± 0,1) |
| Spiegazione | testo che compare dopo la risposta, sempre |
| Modulo collegato | 1-10 |
| Difficoltà | base / intermedia / avanzata |
| Tag | formato, farina, temperatura, resa, sicurezza |

La spiegazione dopo ogni risposta è obbligatoria anche quando la risposta è
giusta: è la parte didattica, senza quella il quiz è un test e basta.

**Banca iniziale: 400 domande**, così distribuite:

| Area | Domande |
|---|---|
| Formati e loro misure | 90 |
| Farine e impasti | 70 |
| Temperature e riposi | 60 |
| Laminazione | 50 |
| Ripieni e chiusure | 50 |
| Cottura | 40 |
| Resa, scarto, food cost | 25 |
| Igiene e sicurezza (HACCP) | 15 |

---

## 10. Dati conservati e per quanto

| Dato | Conservazione | Note |
|---|---|---|
| Nickname studente | fino a fine anno scolastico + 12 mesi | non identifica una persona |
| Punteggi e tentativi | idem | collegati al nickname |
| Codice classe | fino a rigenerazione | |
| E-mail e nome del docente | durata della licenza + 24 mesi | dato di un adulto, contrattuale |
| Log tecnici | 6 mesi | |
| Dati aggregati per il rapporto | 5 anni | anonimi, non riconducibili |

Alla chiusura dell'anno l'istituto riceve un file con tutto ciò che lo riguarda
e può chiedere la cancellazione anticipata. Metterlo per iscritto nel contratto
chiude in anticipo la discussione con il DPO della scuola.

---

## 11. Criteri di collaudo

Il cantiere si considera finito quando tutte queste frasi sono vere, verificate
davanti a un docente vero, in una scuola vera, sulla rete della scuola.

| # | Criterio | Come si verifica |
|---|---|---|
| C1 | Un docente nuovo crea classe e prima assegnazione in meno di 3 minuti | cronometro, senza aiuto |
| C2 | 25 studenti entrano in classe in meno di 3 minuti | prova su una classe intera |
| C3 | Funziona su Chromebook e su un PC con 4 GB di RAM | prova sul parco macchine reale |
| C4 | La caduta della rete non perde il tentativo | staccare la rete a metà esercizio |
| C5 | Il CSV si apre correttamente in Excel italiano | doppio clic, colonne separate |
| C6 | Nessun campo del sistema chiede dati personali dello studente | controllo su tutte le maschere |
| C7 | Nessun elemento commerciale è visibile nella versione scuola | controllo su tutte le maschere |
| C8 | Si naviga da tastiera e il contrasto rispetta WCAG 2.1 AA | verifica con strumento automatico + manuale |
| C9 | Il docente ritrova le sue classi dell'anno prima e le duplica | prova di passaggio d'anno |
| C10 | Il rapporto di fine anno si scarica in un clic | prova dal profilo amministratore |

---

## 12. Ripartizione del lavoro

| Blocco | Contenuto | Giorni |
|---|---|---|
| Account, SSO, ruoli, permessi | cantiere 4 | 12 |
| Classi, nickname, codice di ingresso | cantiere 5, parte 1 | 5 |
| Assegnazioni e stati | cantiere 5, parte 2 | 4 |
| Cruscotto, tre viste | cantiere 5, parte 3 | 4 |
| Esportazioni CSV e registri | cantiere 5, parte 4 | 2 |
| **Totale** | | **27** |

Il motore del quiz (cantiere 6) e la scrittura delle 400 domande sono un lavoro
separato e possono correre in parallelo, perché non dipendono da questo.
