# Griglia dei parametri tecnici

Il documento da riempire con **Rina Poletti**, **Beppe Govoni** e **Gian Paolo
Chiossi**. Senza questi numeri non esistono né la modalità professionale del
gioco né le 400 domande del quiz: il punteggio si calcola sullo scarto dal
valore corretto, e il valore corretto lo danno loro.

I campi sono volutamente vuoti. Non vanno riempiti a tavolino.

---

## 1. Perché serve una griglia e non una ricetta

```
   RICETTA                          GRIGLIA DI PARAMETRI
   ───────                          ────────────────────
   "tira la sfoglia sottile"   ──▶  spessore 0,8 mm · tolleranza ± 0,1
   "lascia riposare un po'"    ──▶  riposo 30 min · minimo 20 · massimo 60
   "impasto sodo"              ──▶  idratazione 52% · tolleranza ± 2
   "burro freddo"              ──▶  burro 14-16 °C · sopra i 19 esce

        │                                      │
        ▼                                      ▼
   non si può                            si può misurare
   trasformare in                        lo scarto, quindi
   punteggio                             dare un punteggio,
                                         quindi valutare
```

Ogni riga della griglia genera tre cose:

| Dalla riga nasce | Come |
|---|---|
| Il punteggio di un esercizio | scarto tra valore inserito e valore corretto |
| Da 3 a 5 domande di quiz | il valore, l'errore tipico, la conseguenza |
| Una scheda tecnica stampabile | per il laboratorio, formato A5 |

Venti formati × 4 domande = 80 domande solo da qui. Le altre 320 vengono dai
temi trasversali (farine, temperature, igiene, resa).

---

## 2. La struttura del record

| Campo | Tipo | Esempio di compilazione | Serve a |
|---|---|---|---|
| Formato | testo | Tagliatella bolognese | identificare |
| Famiglia | elenco | liscia / ripiena / laminata / sottile | filtrare |
| Spessore | mm | | punteggio modulo 3 |
| Tolleranza spessore | ± mm | | soglia di errore |
| Larghezza o diametro | mm | | punteggio |
| Tolleranza larghezza | ± mm | | soglia |
| Uova per kg di farina | n | | punteggio modulo 1 |
| Tipo di farina | testo | | quiz |
| Forza della farina (W) | n o intervallo | | quiz |
| Idratazione | % | | punteggio modulo 1 |
| Tempo di impasto | min | | punteggio |
| Riposo minimo | min | | punteggio modulo 1 |
| Riposo ottimale | min | | punteggio |
| Temperatura di lavorazione | °C | | punteggio |
| Peso del ripieno (se ripieno) | g a pezzo | | punteggio modulo 6 |
| Tolleranza peso ripieno | ± g | | soglia |
| Pezzi per kg di sfoglia | n | | modulo 9, resa |
| Scarto atteso | % | | modulo 9 |
| Tempo di cottura | min | | modulo 8 |
| Errore più frequente | testo | | quiz + vista errori |
| Conseguenza dell'errore | testo | | messaggio nel gioco |
| Come si riconosce a occhio | testo | | scheda per il laboratorio |
| Chi ha dettato il valore | iniziali | RP / BG / GPC | tracciabilità |
| Data di rilevazione | data | | validazione |

L'ultima coppia di campi non è burocrazia: quando fra due anni qualcuno
contesterà un valore, devi sapere chi l'ha dato e quando.

---

## 3. I venti formati da coprire

### Paste all'uovo, lisce

| # | Formato | Spess. mm | Toll. | Largh. mm | Uova/kg | Idrat. % | Riposo min | Cottura min | Rilevato da |
|---|---|---|---|---|---|---|---|---|---|
| 1 | Tagliatella | | | | | | | | |
| 2 | Tagliolino | | | | | | | | |
| 3 | Pappardella | | | | | | | | |
| 4 | Maltagliato | | | | | | | | |
| 5 | Quadretto per brodo | | | | | | | | |
| 6 | Sfoglia per lasagna | | | | | | | | |
| 7 | Garganello | | | | | | | | |
| 8 | Strichetto | | | | | | | | |
| 9 | Gramigna | | | | | | | | |

### Paste ripiene

| # | Formato | Spess. mm | Toll. | Lato/Ø mm | Ripieno g | Toll. g | Pezzi/kg | Cottura min | Rilevato da |
|---|---|---|---|---|---|---|---|---|---|
| 10 | Tortellino | | | | | | | | |
| 11 | Cappelletto | | | | | | | | |
| 12 | Tortellone | | | | | | | | |
| 13 | Anolino | | | | | | | | |
| 14 | Cappellaccio | | | | | | | | |
| 15 | Raviolo | | | | | | | | |

### Paste laminate e sottili (pasticceria)

| # | Formato | Giri e pieghe | T. burro °C | T. ambiente °C | Riposo tra i giri | Spess. finale mm | Cottura °C / min | Rilevato da |
|---|---|---|---|---|---|---|---|---|
| 16 | Pasta sfoglia classica | | | | | | | |
| 17 | Croissant sfogliato | | | | | | | |
| 18 | Strudel tirato a mano | | | | | | | |
| 19 | Pasta phyllo | | | | | | | |
| 20 | Sfogliatella riccia | | | | | | | |

Se i maestri non coprono tutti e cinque i formati di pasticceria, meglio
fermarsi a tre riempiti bene che averne cinque con valori inventati.

---

## 4. Le tolleranze: la parte che decide il punteggio

Per ogni parametro servono **tre soglie**, non una.

```
        VALORE CORRETTO
              │
   ───────────┼───────────────────────────────────────────▶
              │
    ┌─────────┴─────────┐
    │   VERDE  ± X      │  fatto bene            punteggio 85-100
    └───────────────────┘
  ┌───────────────────────┐
  │   GIALLO  ± Y         │  accettabile         punteggio 55-84
  └───────────────────────┘
 ┌─────────────────────────────┐
 │   ROSSO  oltre Y            │  il prodotto non riesce   punteggio 0-54
 └─────────────────────────────┘
```

Esempio della domanda da fare al maestro, formato per formato:

> "A che spessore va chiusa la tagliatella? — E fino a che spessore è ancora
> accettabile? — E oltre quale spessore il piatto non è più quello?"

Le tre risposte danno verde, giallo e rosso. È l'unica intervista che serve
fare, ripetuta venti volte.

### Tabella delle soglie da compilare

| Parametro | Verde ± | Giallo ± | Oltre: cosa succede |
|---|---|---|---|
| Spessore | | | |
| Larghezza | | | |
| Idratazione | | | |
| Riposo | | | |
| Peso del ripieno | | | |
| Temperatura del burro | | | |
| Tempo di cottura | | | |

---

## 5. Come il parametro diventa punteggio

```
   scarto = | valore inserito − valore corretto |

   punteggio =  100                          se scarto ≤ verde
                100 − 45 × (scarto − verde)  se verde < scarto ≤ giallo
                       ────────────────────
                        (giallo − verde)

                max(0, 54 − penalità)        se scarto > giallo
```

Il gioco non dice "sbagliato": mostra **cosa succede** al prodotto. Ogni riga
rossa della griglia deve avere una conseguenza scritta dal maestro, in una
frase, con le sue parole.

| Esempio di conseguenza | Buona | Perché |
|---|---|---|
| "Errore: spessore fuori tolleranza" | no | non insegna niente |
| "Troppo spessa: in cottura resta cruda dentro e il sugo scivola via" | sì | descrive il difetto reale |
| "Burro sopra i 19 °C: esce dalle pieghe e la sfoglia non si alza" | sì | causa ed effetto |

---

## 6. Protocollo della giornata di rilevazione

Una giornata per maestro, non di più. Registrare tutto, misurare mentre lavora,
mai chiedere i numeri a memoria e a tavolino: i numeri detti a memoria sono
sbagliati, quelli misurati mentre le mani lavorano sono giusti.

```
  09:00  ┌──────────────────────────────────────────────┐
         │ IMPASTO — si pesa tutto quello che entra     │
         │ farina, uova (peso, non numero), acqua       │
         │ temperatura dell'ambiente e dell'impasto     │
  10:00  └──────────────────────────────────────────────┘
         ┌──────────────────────────────────────────────┐
         │ RIPOSO — cronometro, e si chiede il minimo   │
         │ e il massimo oltre cui non va bene           │
  10:30  └──────────────────────────────────────────────┘
         ┌──────────────────────────────────────────────┐
         │ TIRATURA — calibro sulla sfoglia in tre      │
         │ punti diversi, tre volte durante il lavoro   │
  11:30  └──────────────────────────────────────────────┘
         ┌──────────────────────────────────────────────┐
         │ TAGLIO E FORMATURA — larghezze col calibro,  │
         │ pesi del ripieno con bilancia allo 0,1 g,    │
         │ su dieci pezzi consecutivi                   │
  13:00  └──────────────────────────────────────────────┘
         ┌──────────────────────────────────────────────┐
         │ RESA — si pesa la sfoglia di partenza e si   │
         │ contano i pezzi ottenuti, si pesa lo scarto  │
  14:00  └──────────────────────────────────────────────┘
         ┌──────────────────────────────────────────────┐
         │ COTTURA — cronometro, e si assaggia          │
  15:00  └──────────────────────────────────────────────┘
         ┌──────────────────────────────────────────────┐
         │ INTERVISTA SUGLI ERRORI — la parte più       │
         │ preziosa: "cosa sbagliano sempre i ragazzi?" │
  16:00  └──────────────────────────────────────────────┘
```

### L'ultima ora vale metà giornata

Le domande da fare, nell'ordine, registrando la voce:

1. Cosa sbagliano sempre i ragazzi su questo formato?
2. Da cosa te ne accorgi guardando la sfoglia, senza toccarla?
3. Cosa succede al piatto se quell'errore non viene corretto?
4. Come si recupera, se si recupera?
5. Cosa hai dovuto disimparare tu, all'inizio?

Le risposte diventano, nell'ordine: la vista degli errori ricorrenti, la scheda
per il laboratorio, il messaggio del gioco, il suggerimento di recupero, e il
video di apertura del modulo.

### Strumenti da portare

| Strumento | Perché |
|---|---|
| Calibro digitale (0,01 mm) | spessori e larghezze |
| Bilancia di precisione (0,1 g) | ripieni |
| Bilancia da 5 kg (1 g) | impasti e resa |
| Termometro a sonda | impasto, burro |
| Termoigrometro | ambiente: incide su tutto |
| Cronometro | riposi e cotture |
| Due telecamere | una sulle mani, una sul tagliere dall'alto |
| Registratore audio separato | l'audio delle telecamere non basta |
| Righello di riferimento nel campo | per la scala nelle riprese |

Il termoigrometro sembra superfluo e non lo è: un valore rilevato a 18 °C e
uno rilevato a 28 °C non sono lo stesso valore, e senza l'ambiente annotato la
griglia non è confrontabile.

---

## 7. Validazione

Nessun valore entra nel gioco con una sola rilevazione.

| Passaggio | Regola |
|---|---|
| Rilevazioni | tre per parametro, in momenti diversi della giornata |
| Valore adottato | la mediana delle tre, non la media |
| Scarto tra rilevazioni | se supera la soglia verde, si rileva una quarta volta |
| Doppia firma | ogni scheda formato è vista da un secondo maestro |
| Disaccordo tra maestri | si registrano entrambi i valori e si allarga la tolleranza |
| Chiusura | la scheda passa da BOZZA a VALIDATA con data e iniziali |

Il disaccordo non è un problema da nascondere: due maestri che chiudono la
tagliatella a spessori diversi ti stanno dicendo qual è la tolleranza vera. È
un'informazione, e nel gioco diventa la fascia verde.

---

## 8. Cosa produce, alla fine

| Prodotto | Quantità | Va in |
|---|---|---|
| Schede formato validate | 20 | gioco, moduli 1-9 |
| Soglie verde/giallo/rosso | 7 parametri × 20 formati | motore di punteggio |
| Frasi di conseguenza dell'errore | ~60 | messaggi nel gioco |
| Domande di quiz derivate | ~80 | banca delle 400 |
| Schede A5 per il laboratorio | 20 | licenza scuola |
| Spezzoni video dei maestri | 20 × 2-3 min | apertura dei moduli |
| Registrazioni audio delle interviste | 3 giornate | contenuti didattici |

Tre giornate di rilevazione, una per maestro, più due giornate di trascrizione
e validazione. **Cinque giornate in tutto**: è il lavoro con il rapporto
migliore tra tempo speso e valore prodotto di tutto il progetto, perché senza
questo non parte nient'altro.

---

## 9. Ordine di lavoro consigliato

```
  PRIMA GIORNATA          SECONDA GIORNATA        TERZA GIORNATA
  ──────────────          ────────────────        ──────────────
  Paste all'uovo lisce    Paste ripiene           Laminate e sottili
  (formati 1-9)           (formati 10-15)         (formati 16-20)
       │                        │                       │
       ▼                        ▼                       ▼
  sblocca i moduli        sblocca il modulo 6     sblocca i moduli
  1, 2, 3, 8, 9           e la resa               4, 5, 7
       │                        │                       │
       └────────────────────────┴───────────────────────┘
                                ▼
                    DUE GIORNATE DI TRASCRIZIONE
                    schede validate, soglie, frasi,
                    prime 80 domande di quiz
```

Se il tempo dei maestri è poco, la prima giornata da sola sblocca la versione
ridotta del prodotto e permette di vendere alle scuole. Le altre due possono
seguire a distanza di settimane.
