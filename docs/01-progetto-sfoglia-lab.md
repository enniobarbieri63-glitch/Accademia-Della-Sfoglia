# SFOGLIA LAB — Progetto di riconversione

Dal gioco "sfogline" a due mercati: corsi professionali per pasticceri e chef,
licenza didattica per gli istituti alberghieri.

> I numeri sono ipotesi di lavoro coerenti con il mercato italiano.
> Vanno tarati sui costi reali prima di essere usati per decidere.

---

## Schema generale: da un gioco a due mercati

```
                        ┌───────────────────────────────┐
                        │   MOTORE DI GIOCO ESISTENTE   │
                        │   impasto · mattarello ·      │
                        │   spessore · formati          │
                        └───────────────┬───────────────┘
                                        │
                    ┌───────────────────┴───────────────────┐
        ┌───────────▼────────────┐            ┌─────────────▼──────────────┐
        │  TRACK A — PRO         │            │  TRACK B — SCUOLA          │
        │  Modalità professionale│            │  Versione didattica        │
        │  + laminazione         │            │  + classi virtuali         │
        │  + food cost / resa    │            │  + quiz e valutazione      │
        └───────────┬────────────┘            └─────────────┬──────────────┘
        │ Chi paga: pasticceri,  │            │ Chi paga: istituti         │
        │ chef, titolari         │            │ alberghieri (IPSEOA), reti │
        │ Cosa compra: corsi,    │            │ Cosa compra: licenza       │
        │ certificazione,        │            │ annuale per istituto       │
        │ formazione in azienda  │            │                            │
        │ Ricavo: a evento       │            │ Ricavo: ricorrente, si     │
        │ + abbonamento          │            │ rinnova ogni settembre     │
        └───────────┬────────────┘            └─────────────┬──────────────┘
                    └──────────────────┬────────────────────┘
                        ┌──────────────▼──────────────┐
                        │  ASSET COMUNE               │
                        │  Elenco certificati         │
                        │  → i ristoranti cercano lì  │
                        └─────────────────────────────┘
```

Un solo sviluppo, due listini. La versione scuola è la stessa base con ruoli
docente/studente e un registro dei punteggi sopra.

---

# PARTE 1 — TRACK A: dal gioco ai corsi per pasticceri e chef

## 1.1 Perché "sfoglia" copre già la pasticceria

La parola lavora su due mestieri: la sfoglia all'uovo tirata al mattarello e la
sfoglia laminata al burro. Le competenze sotto sono le stesse — controllo dello
spessore, temperatura, riposo, comportamento del glutine. Il gioco attuale copre
la prima metà. La riconversione aggiunge la seconda e cambia il criterio di
punteggio: non più "quanto sei stato veloce", ma quanto ti sei avvicinato al
parametro corretto.

## 1.2 Riposizionamento del punteggio

| | Versione attuale (hobby) | Versione professionale |
|---|---|---|
| Obiettivo | divertimento, velocità | precisione sul parametro |
| Punteggio | punti arcade | scarto % dal valore corretto |
| Errore | si perde una vita | si vede la conseguenza (sfoglia rotta, burro uscito, ripieno che esce in cottura) |
| Parametri | nascosti | espliciti: idratazione, °C, tempo, spessore in mm |
| Risultato finale | classifica | scheda tecnica esportabile + resa e food cost |
| Durata sessione | 2-3 minuti | 8-15 minuti per modulo |

## 1.3 Moduli di gioco e competenza corrispondente

| # | Modulo | Meccanica | Cosa si impara | Chef | Pasticcere |
|---|---|---|---|---|---|
| 1 | Impasto all'uovo | dosaggio uova/farina, forza della farina (W) | reologia, assorbimento | ● | ○ |
| 2 | Mattarello | gesto, pressione, uniformità | manualità, lettura della sfoglia | ● | ○ |
| 3 | Spessore e formato | tarare i mm per ogni formato | tolleranze di produzione | ● | ○ |
| 4 | Laminazione al burro | pieghe a 3 e a 4, giri, burro 14-16 °C | pasta sfoglia, croissant | ○ | ● |
| 5 | Catena del freddo | riposi e temperature tra i giri | difetti da burro fuso | ○ | ● |
| 6 | Paste ripiene | peso ripieno, chiusura, tenuta | standard di produzione | ● | ○ |
| 7 | Paste sottili in pasticceria | strudel, phyllo, sfogliatella | tecniche di sfoglia dolce | ○ | ● |
| 8 | Cottura | tempo/temperatura per spessore | resa e difetti | ● | ● |
| 9 | Resa e scarto | pezzi per kg, scarto | produttività | ● | ● |
| 10 | Food cost | prezzo, margine, ricarico | conto economico del piatto | ● | ● |

● modulo centrale · ○ modulo di completamento

I moduli 9 e 10 sono quelli che rendono il gioco accettabile a un titolare come
strumento da far usare durante l'orario di lavoro.

## 1.4 Catalogo corsi

| Codice | Corso | Destinatario | Durata | Posti | Quota (+ IVA) | Ricavo pieno |
|---|---|---|---|---|---|---|
| PRO-1 | Sfoglia all'uovo, livello professionale | chef, cuochi | 1 g (8 h) | 12 | 290 € | 3.480 € |
| PRO-2 | Paste ripiene e standard di produzione | chef, capi partita | 2 g | 10 | 540 € | 5.400 € |
| PAS-1 | Laminazione: pasta sfoglia e croissant | pasticceri | 2 g | 10 | 590 € | 5.900 € |
| PAS-2 | Paste sottili: strudel, phyllo, sfogliatella | pasticceri | 1 g | 10 | 320 € | 3.200 € |
| GES-1 | Resa, scarto e food cost della pasta fresca | titolari, F&B manager | 1 g | 15 | 240 € | 3.600 € |
| CER-1 | Percorso completo Addetto Pasta Fresca | apprendisti, chi cerca lavoro | 5 g (40 h) | 10 | 1.200 € | 12.000 € |
| AZ-1 | Formazione in azienda, sulla brigata | ristoranti, pasticcerie, hotel | 1 g in loco | brigata | 1.750 €/g | 1.750 € |
| ABB | Abbonamento piattaforma professionale | singoli | annuale | — | 168 €/anno | ricorrente |
| ESA | Esame di certificazione | tutti | 3 h | 12 | 140 € | 1.680 € |

## 1.5 Economia di una giornata tipo (PRO-1, 12 posti)

| Voce | Importo | Nota |
|---|---|---|
| Ricavo | 3.480 € | 12 × 290 € |
| Compenso maestro | −900 € | giornata |
| Materie prime | −180 € | 15 € a persona |
| Uso spazio / utenze | −250 € | costo figurativo se in bottega propria |
| Attestato, grembiule, stampe | −120 € | |
| Acquisizione partecipante | −240 € | 20 € a testa dal gioco; 60-80 € da pubblicità |
| **Margine lordo** | **1.790 €** | 51% |

Il numero da guardare non è il prezzo: è il costo di acquisizione. È l'unica
ragione per cui il gioco esiste.

## 1.6 Percorso di certificazione

```
   ┌──────────────┐   ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
   │  LIVELLO 0   │   │  LIVELLO 1   │   │  LIVELLO 2   │   │  LIVELLO 3   │
   │  Praticante  │──▶│  Addetto     │──▶│  Specialista │──▶│  Maestro     │
   │ solo gioco   │   │ 40 h + esame │   │ 80 h + prova │   │ su selezione │
   │ gratuito     │   │ pratica      │   │ in produzione│   │ + insegna    │
   │ non appare   │   │ appare       │   │ appare in    │   │ evidenza +   │
   │ nell'elenco  │   │ nell'elenco  │   │ evidenza     │   │ docenza      │
   └──────┬───────┘   └──────┬───────┘   └──────┬───────┘   └──────┬───────┘
          └──────────────────┴─────────┬────────┴──────────────────┘
                          ┌────────────▼───────────┐
                          │  ELENCO PUBBLICO       │
                          │  consultabile dai      │
                          │  ristoranti → ricavo   │
                          │  B2B                   │
                          └────────────────────────┘
```

Attestato con numero progressivo, anno, livello e firma dei maestri. Validità tre
anni, poi aggiornamento a pagamento (90 €). Il rinnovo obbligatorio è ciò che
rende l'elenco un ricavo e non un archivio.

## 1.7 Come il gioco porta al corso

```
  GIOCA GRATIS                 SI QUALIFICA                 CONVERTE
  moduli 1-3 aperti     ──▶    supera il modulo 3     ──▶   sblocco modulo 4
  nessuna registrazione        chiede e-mail per            solo con account
                               salvare il punteggio         professionale
        │                            │                            │
        ▼                            ▼                            ▼
   TRAFFICO SEO            LISTA CONTATTI               PROPOSTA MIRATA
   dalle schede            segmentata per ruolo,        laminazione → PAS-1
   dei formati             città, punteggio             ripieni     → PRO-2
                                 │                            │
                                 ▼                            ▼
                          CODICE SCONTO 15%           ISCRIZIONE CON
                          valido 21 giorni            ACCONTO 30%
```

Tre regole da non rompere:

1. Nessun pagamento dentro il gioco. Resta gratis, sempre.
2. Lo sconto scade in 21 giorni. Senza scadenza non converte.
3. L'acconto è del 30% e non si restituisce: evita il 40% di assenti.

---

# PARTE 2 — TRACK B: la licenza per gli istituti alberghieri

## 2.1 Il mercato in cifre (stime da verificare)

| Dato | Stima | Fonte da verificare |
|---|---|---|
| Istituti alberghieri statali (IPSEOA) | ~320 | anagrafe scuole MIM |
| Istituti paritari con indirizzo alberghiero | ~90 | anagrafe scuole MIM |
| Studenti enogastronomia e ospitalità | ~170.000 | rilevazioni MIM |
| Docenti di laboratorio cucina/pasticceria | ~9.000 | stima |
| Centri IeFP con cucina | ~250 | Regioni |
| **Bacino contattabile** | **~660 sedi** | |

Non serve conquistare un mercato: servono cinquanta scuole.

## 2.2 Cosa contiene la licenza

| Componente | Descrizione | Chi lo usa |
|---|---|---|
| Gioco in versione didattica | moduli 1-8, senza pubblicità né vendita | studente |
| Classe virtuale | il docente crea la classe, iscrive con codice, assegna moduli | docente |
| Quiz sui formati | banca di 400 domande | docente |
| Prova di valutazione | esercizio a tempo, punteggio convertibile in voto /10 | docente |
| Cruscotto docente | chi ha fatto cosa, dove sbaglia la classe | docente |
| Esportazione voti | file per Argo, Spaggiari, Nuvola | docente |
| Schede tecniche stampabili | una per formato, per il laboratorio | docente |
| Formazione all'uso | 2 h online all'attivazione | scuola |
| Assistenza | e-mail, risposta entro 2 giorni lavorativi | scuola |
| Rapporto di fine anno | dati aggregati, utile per la rendicontazione | dirigente |

L'ultima riga vale più di quanto sembri: la scuola deve rendicontare ciò che
compra con i fondi. Consegnarlo pronto rende il rinnovo quasi automatico.

## 2.3 Architettura dei ruoli

```
 ┌────────────────────────────────────────────────────────────────────┐
 │                        ISTITUTO (licenza)                          │
 │  ┌───────────────┐    ┌──────────────┐      ┌──────────────┐      │
 │  │ AMMINISTRATORE│    │   DOCENTE    │      │   STUDENTE   │      │
 │  │ (animatore    │    │ (laboratorio │      │  (minorenne) │      │
 │  │  digitale)    │    │  cucina/past)│      │              │      │
 │  │ crea account  │─┬─▶│ crea classi  │──┬──▶│ entra con    │      │
 │  │ docenti       │ │  │ assegna      │  │   │ codice classe│      │
 │  │ vede consumi  │ │  │ moduli       │  │   │ + nickname   │      │
 │  │ scarica il    │ │  │ vede errori  │  │   │              │      │
 │  │ rapporto      │ │  │ esporta voti │  │   │ NESSUN dato  │      │
 │  └───────────────┘ │  └──────────────┘  │   │ personale    │      │
 └────────────────────┼────────────────────┼───┴──────────────┴──────┘
                      ▼                    ▼
             ┌─────────────────────────────────────┐
             │  Accesso con Google Workspace for   │
             │  Education o Microsoft 365 (SSO)    │
             │  nessuna password nuova da gestire  │
             └─────────────────────────────────────┘
```

Lo studente non registra dati personali: nickname assegnato dal docente. È la
scelta che evita metà dei problemi di privacy e nove decimi delle obiezioni.

## 2.4 Listino a fasce

| Fascia | Perimetro | Studenti | Prezzo annuo (+ IVA) | A studente |
|---|---|---|---|---|
| A — Classe | 1 docente, 1 classe | fino a 30 | 450 € | 15,00 € |
| B — Indirizzo | tutte le classi di cucina o pasticceria | fino a 150 | 1.400 € | 9,33 € |
| C — Istituto | tutto l'istituto, docenti illimitati | fino a 500 | 2.900 € | 5,80 € |
| D — Rete | accordo di rete, scuola capofila | 5+ istituti | 9.000 – 15.000 € | 3,00 – 4,50 € |

### Componenti aggiuntive

| Voce | Prezzo | Nota |
|---|---|---|
| Formazione docenti in presenza | 800 €/giornata | + rimborso spese |
| Masterclass con maestro in istituto | 1.400 €/giornata | forte leva sul dirigente |
| Esame di certificazione studenti | 25 €/studente | attestato spendibile |
| Percorso PCTO strutturato (30 h) | 1.200 €/classe | capitolo di spesa separato |
| Personalizzazione contenuti | 2.500 € una tantum | solo fascia D |

La fascia B è quella su cui puntare: sta sotto i 2.000 €, soglia entro cui nella
maggioranza degli istituti il dirigente affida direttamente.

## 2.5 Chi decide dentro una scuola

| Ruolo | Cosa fa | Cosa vuole sentirsi dire | Come lo raggiungi |
|---|---|---|---|
| Docente di laboratorio | prova, chiede | "i ragazzi si annoiano meno e io correggo meno" | fiere, gruppi di docenti, passaparola |
| Animatore digitale | verifica che funzioni | "SSO, niente installazioni, gira da browser" | e-mail diretta |
| Dirigente scolastico | firma | "è su MePA, la rendicontazione è pronta" | lettera + demo di 20 minuti |
| DSGA | gestisce l'acquisto | "CIG, DURC, fattura elettronica, split payment" | PEC, documentazione ordinata |
| Referente PCTO | usa le ore come PCTO | "trenta ore certificate senza spostare i ragazzi" | è la porta più larga |

Ordine di attacco: docente → animatore digitale → dirigente → DSGA. Partire dal
dirigente è partire dal punto sbagliato.

## 2.6 Con quali fondi paga la scuola

| Fonte | Cosa copre | Finestra | Difficoltà |
|---|---|---|---|
| PNRR — Scuola 4.0 (DM 65/2023) | ambienti didattici innovativi, contenuti digitali | fino a chiusura progetti | media |
| PNRR — formazione digitale (DM 66/2023) | formazione docenti | annuale | bassa |
| Fondi PCTO | percorsi per le competenze trasversali | annuale | bassa |
| Programma Annuale, scheda A/P | software didattico | delibera entro 31 dicembre | bassa |
| PN "Scuola e competenze" 2021-2027 | progetti su bando | a bando | alta |
| Contributo volontario famiglie | materiali didattici | annuale | bassa |
| Carta del Docente | il singolo docente compra la fascia A | tutto l'anno | molto bassa |
| Fondi regionali IeFP | centri di formazione | a bando | media |

La Carta del Docente è la scorciatoia d'ingresso: un docente compra la fascia A
con soldi suoi, la usa, e a maggio chiede lui al dirigente la fascia B.

## 2.7 Calendario scolastico di vendita

```
 SET   OTT   NOV   DIC   GEN   FEB   MAR   APR   MAG   GIU   LUG   AGO
 ├─────┴──┐  │     │     │     │     │     │     │     │     │     │
 │ATTIVAZIONI│     │     │     │     │     │     │     │     │     │
 │formazione │     │     │     │     │     │     │     │     │     │
 │docenti    │     │     │     │     │     │     │     │     │     │
 └───────────┤     │     │     │     │     │     │     │     │     │
             ├─────┴─────┤     │     │     │     │     │     │     │
             │ BILANCIO  │     │     │     │     │     │     │     │
             │ Programma Annuale: si prepara a novembre, si approva │
             │ entro il 31 dicembre → CI DEVI ESSERE DENTRO         │
             └───────────┤     │     │     │     │     │     │     │
                         ├─────┴─────┴─────┤     │     │     │     │
                         │ DEMO E TRATTATIVE     │     │     │     │
                         │ i docenti provano     │     │     │     │
                         └───────────────────────┤     │     │     │
                                     ├───────────┴─────┤     │     │
                                     │ PCTO: si programmano le ore │
                                     └─────────────────┤     │     │
                                                 ├─────┴─────┤     │
                                                 │ ORDINI per l'anno│
                                                 │ successivo       │
                                                 └──────────────────┘
```

Chi non è dentro il Programma Annuale entro dicembre aspetta un anno intero. Da
qui il vincolo: il prodotto deve essere vendibile entro settembre.

## 2.8 Requisiti tecnici e amministrativi

| Requisito | Perché serve | Costo/impegno |
|---|---|---|
| Gira da browser, senza installazione | i PC delle scuole sono bloccati | vincolo di progetto |
| Funziona su tablet e Chromebook | metà dei laboratori usa quelli | test obbligatorio |
| Consuma poca banda | reti scolastiche lente | ottimizzazione |
| SSO Google Workspace / Microsoft 365 | nessuna password nuova | 3-5 giorni |
| Nessun dato personale dello studente | evita il grosso degli adempimenti sui minori | scelta di progetto |
| Nomina a responsabile del trattamento (art. 28 GDPR) | la scuola la esige | ~800 € una tantum |
| Informativa e registro dei trattamenti | obbligo | incluso |
| Accessibilità WCAG 2.1 AA + dichiarazione AgID | obbligo per fornitori PA | 3.000-5.000 € |
| Abilitazione al MePA | senza, molte scuole non possono comprare | 2-3 settimane |
| Fattura elettronica PA, split payment, CIG, DURC | senza non ti pagano | commercialista |
| Zero pubblicità e zero vendita nella versione scuola | la scuola rifiuta chi vende ai minori | scelta di progetto |

Blocco noioso, e decide se vendi o no. Una scuola non compra un bel prodotto non
conforme: compra un prodotto conforme anche se è meno bello.

---

# PARTE 3 — Sviluppo, tempi, soldi

## 3.1 Cosa va costruito

| # | Cantiere | Contenuto | Giorni | Costo | Track |
|---|---|---|---|---|---|
| 1 | Modalità professionale | punteggio per scarto, parametri espliciti | 15 | 7.500 € | A |
| 2 | Moduli laminazione (4, 5, 7) | temperatura e pieghe | 25 | 12.500 € | A |
| 3 | Moduli resa e food cost (9, 10) | calcolo, schede esportabili | 10 | 5.000 € | A+B |
| 4 | Account e ruoli | docente, studente, amministratore | 12 | 6.000 € | B |
| 5 | Classi virtuali e assegnazioni | codice classe, compiti | 15 | 7.500 € | B |
| 6 | Quiz e banca domande | 400 domande + motore | 12 | 5.000 € | B |
| 7 | Cruscotto docente + export voti | report, CSV per registri | 12 | 6.000 € | B |
| 8 | SSO Google/Microsoft | autenticazione scolastica | 5 | 2.500 € | B |
| 9 | Accessibilità e conformità | WCAG, dichiarazione, GDPR | 10 | 4.500 € | B |
| 10 | Contenuti didattici | video maestri, 20 schede | 15 | 8.000 € | A+B |
| 11 | Sito, pagine corso, prenotazione, pagamenti | conversione | 12 | 5.500 € | A |
| 12 | MePA, contratti, modelli, esame | amministrativo | 8 | 3.000 € | B |
| | **Totale completo** | | **151** | **73.000 €** | |

Versione ridotta per partire prima (cantieri 1, 3, 4, 5, 6, 8, 11, 12):

| | Giorni | Costo |
|---|---|---|
| Versione ridotta | 79 | 35.500 € |
| Completamento successivo | 72 | 37.500 € |

Consiglio: parti dalla ridotta, chiudi il primo anno scolastico con vendite
reali, e costruisci la laminazione con i soldi delle scuole.

## 3.2 Roadmap 12 mesi

```
 MESE  1     2     3     4     5     6     7     8     9    10    11    12
 ┌─────┴─────┴─────┴─────┐
 │ T1 — COSTRUZIONE      │  modalità pro · ruoli e classi · conformità · MePA
 └───────────────────────┘
             ┌───────────┴─────────────────┐
             │ T2 — PROVA SUL CAMPO        │  3 scuole pilota gratuite
             │                             │  2 corsi PRO-1 di collaudo
             └─────────────────────────────┘
                               ┌───────────┴───────────────┐
                               │ T3 — VENDITA (giu-set)    │  campagna docenti
                               │                           │  Carta del Docente
                               └───────────────────────────┘
                                                 ┌─────────┴────────────┐
                                                 │ T4 — ATTIVAZIONI     │
                                                 │ formazione docenti,  │
                                                 │ Programma Annuale    │
                                                 └──────────────────────┘

 VINCOLO: prodotto vendibile entro il mese 9, altrimenti si perde
 un'intera annualità scolastica.
```

| Trimestre | Obiettivo | Segnale di riuscita |
|---|---|---|
| T1 | prodotto utilizzabile, conforme, su MePA | un docente lo usa senza che tu sia in stanza |
| T2 | 3 scuole pilota + 2 corsi | 2 pilota chiedono di continuare a pagamento |
| T3 | 15 licenze vendute | 5 di fascia B o superiore |
| T4 | 25 licenze attive, 12 corsi erogati | rinnovo dichiarato dall'80% dei pilota |

## 3.3 Conto economico, tre scenari — Anno 1

| Voce | Prudente | Centrale | Buono |
|---|---|---|---|
| Licenze fascia A | 8 × 450 = 3.600 € | 15 × 450 = 6.750 € | 25 × 450 = 11.250 € |
| Licenze fascia B | 4 × 1.400 = 5.600 € | 10 × 1.400 = 14.000 € | 18 × 1.400 = 25.200 € |
| Licenze fascia C | 1 × 2.900 = 2.900 € | 3 × 2.900 = 8.700 € | 6 × 2.900 = 17.400 € |
| Formazione docenti e masterclass | 3.200 € | 7.000 € | 14.000 € |
| Corsi professionali | 22.000 € | 46.000 € | 76.000 € |
| Formazione in azienda | 7.000 € | 17.500 € | 31.500 € |
| Abbonamenti | 6.720 € | 20.160 € | 42.000 € |
| **Ricavi** | **51.020 €** | **120.110 €** | **217.350 €** |
| Costi diretti | −18.000 € | −41.000 € | −73.000 € |
| Sviluppo (quota anno 1) | −35.500 € | −35.500 € | −35.500 € |
| Commerciale e marketing | −8.000 € | −14.000 € | −22.000 € |
| Struttura, hosting, assistenza | −9.000 € | −12.000 € | −18.000 € |
| **Risultato** | **−19.480 €** | **+17.610 €** | **+68.850 €** |

### Anni 2 e 3, scenario centrale

| Voce | Anno 2 | Anno 3 |
|---|---|---|
| Rinnovi licenze (80%) | 23.560 € | 41.000 € |
| Nuove licenze | 34.000 € | 48.000 € |
| Corsi professionali | 62.000 € | 78.000 € |
| Formazione in azienda | 26.000 € | 35.000 € |
| Abbonamenti | 33.600 € | 50.400 € |
| Accesso elenco certificati (B2B) | 9.000 € | 22.000 € |
| **Ricavi** | **188.160 €** | **274.400 €** |
| Costi totali | −112.000 € | −152.000 € |
| **Risultato** | **+76.160 €** | **+122.400 €** |

Il pareggio si trova intorno a 95.000 € di ricavi nel primo anno.

## 3.4 Indicatori mensili

| Indicatore | Calcolo | Bersaglio anno 1 | Se sta sotto |
|---|---|---|---|
| Contatti dal gioco | e-mail / giocatori | 12% | il momento della richiesta è sbagliato |
| Contatto → iscritto | iscritti / contatti | 3% | l'offerta non è mirata al modulo giocato |
| Riempimento corsi | posti venduti / posti | 85% | troppe date, riducile |
| Assenti dopo iscrizione | assenti / iscritti | < 8% | acconto troppo basso |
| Demo scuola → licenza | licenze / demo | 25% | stai parlando col ruolo sbagliato |
| Rinnovo licenze | rinnovi / scadenze | 80% | manca la formazione iniziale |
| Uso reale in classe | classi attive / create | 70% | prodotto troppo complicato per un'ora |
| Costo di acquisizione corsi | spesa marketing / iscritti | < 45 € | dipendi troppo dalla pubblicità |

## 3.5 Rischi e contromisure

| Rischio | Peso | Contromisura |
|---|---|---|
| La scuola compra e nessuno usa | alto, uccide il rinnovo | formazione docenti obbligatoria all'attivazione, inclusa |
| Tempi PA: ordini lenti, pagamenti a 60-90 giorni | alto sulla cassa | vendere anche via Carta del Docente, che paga subito |
| Prodotto non conforme | blocca la vendita | cantiere 9 dentro il T1, non rimandabile |
| Dipendenza dai singoli maestri | alto sul track A | filmare i moduli, formare due docenti di secondo livello |
| Il gioco appare "da bambini" ai professionisti | medio | parametri veri e food cost in evidenza |
| Piattaforme generiche concorrenti | medio | il vantaggio non è il software: sono i maestri e l'elenco |
| Stagionalità: tutto si decide in tre mesi | medio | i corsi coprono i mesi in cui le scuole sono ferme |
| Poche scuole rispondono | medio | partire dai docenti, non dai dirigenti |

## 3.6 I primi 30 giorni

| Giorni | Azione | Risultato |
|---|---|---|
| 1-3 | Elencare i moduli già pronti e quelli mancanti | stato reale del codice |
| 4-7 | Scrivere le specifiche della modalità professionale | documento per lo sviluppatore |
| 8-10 | Contattare 5 docenti di laboratorio | 3 sì |
| 11-14 | Registrare i 20 parametri tecnici con i maestri | tabella dei valori corretti |
| 15-18 | Listino, contratto tipo, nomina responsabile | cartella pronta per il DSGA |
| 19-22 | Avviare l'abilitazione al MePA | pratica depositata |
| 23-26 | Prima data PRO-1, aprire le iscrizioni | acconti incassati |
| 27-30 | Prima demo in una scuola, in laboratorio | verbale di ciò che non ha funzionato |

---

## In una riga

Il track A dà cassa subito e tiene vivi i maestri; il track B dà ricavo che si
rinnova ogni settembre e mette il marchio davanti ai ragazzi tre anni prima che
entrino nel mestiere. Il gioco non è il prodotto in nessuno dei due: è il modo
di non pagare l'acquisizione dei clienti.
