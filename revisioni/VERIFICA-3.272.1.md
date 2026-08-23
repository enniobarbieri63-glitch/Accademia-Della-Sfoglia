# 3.272.1 verificata — resta una domanda e tre cose in sospeso

Ho letto la 3.272.1. **La protezione è al posto giusto e scritta bene**, `php -l` pulito su
tutti i file, e il confronto con la 3.272.0 mostra **solo** quel blocco più il numero di
versione: nient'altro è stato toccato. Da qui in avanti la chiusura di luglio non può più
partire.

---

## LA DOMANDA — siamo arrivati in tempo, o no?

La protezione impedisce che succeda **da adesso**. Non dice se era già successo **prima**,
nella finestra fra l'installazione della 3.272.0 e quella della 3.272.1. In quella finestra
il codice quotidiano era attivo e senza protezione: **se `gs_daily_cron` è passato in quel
momento, la chiusura di luglio è partita.**

Va accertato, e si accerta in due passi. Il primo da solo non basta.

### Passo A — leggi l'opzione

```
wp option get gs_buono_sfoglia_mese_chiuso
```

- **Vuota** → il cron non è mai passato e la protezione non è ancora scattata. Tutto a
  posto, non è successo niente. Salta il passo B.
- **`2026-07`** → non basta per capire. Può volere dire due cose opposte:
  - la protezione ha fatto il suo lavoro (l'ha scritta senza toccare nessuno) — tutto bene;
  - **oppure la chiusura era già partita davvero** — 400 messaggi già usciti.

### Passo B — distingui i due casi

Se l'opzione vale `2026-07`, conta i marcatori per utente:

```
wp user meta list --all --keys=gs_buono_mese_2026-07 2>/dev/null | wc -l
```

oppure, più semplice, cerca quanti utenti hanno quella chiave.

- **Zero marcatori** → la chiusura **non è mai entrata nel ciclo**: l'opzione è stata
  scritta dalla protezione. **Non è successo niente, siamo arrivati in tempo.**
- **Uno o più marcatori** → il ciclo è partito, e tanti messaggi quanti sono i marcatori
  (per due) sono usciti.

**Riporta il numero esatto**, non "sembra a posto". È l'unico modo per sapere se Ennio deve
aspettarsi domande dalle sfogline.

### Se era già partita

Non è un disastro e non c'è niente da riparare in fretta:

- **Nessun Buono è stato assegnato per sbaglio.** I punti di luglio sono zero per tutte
  (`gs_points_mese_2026-07` nasce solo da fine agosto), quindi la soglia non è stata
  raggiunta da nessuna. **Zero danni economici** — verificalo comunque contando quante
  sfogline hanno `gs_buono_sfoglia_pct` maggiore di zero, e riporta il numero.
- Il danno è di comunicazione: dei messaggi confusi su un mese mai giocato.
- I **messaggi interni** si possono togliere dalle caselle (sono post `gs_messaggio` con
  quell'oggetto). **Le email già partite no.**

**Non toccare niente di tutto questo di tua iniziativa**: riporta i numeri e lascia decidere
a Ennio se ripulire le caselle o lasciar perdere e scrivere due righe alle sfogline.

---

## Le tre cose rimaste in sospeso

Nessuna è urgente, ma non vanno perse.

### 1 · Il rendiconto della chiusura — non è stato fatto

Nel file precedente lo avevo chiesto e nella 3.272.1 non c'è (nessun `gs_inbox_crea` in
`gs_buono_sfoglia_chiudi_mese()`, nessun contatore `$elaborate`). **È comprensibile: c'era
un'emergenza e hai fatto bene a occuparti prima di quella.**

Ma va aggiunto **prima del 1° settembre**, perché è la prima chiusura vera. Serve a questo:
con il marcatore scritto prima degli effetti, una sfoglina interrotta proprio in mezzo
resta senza il suo Buono e viene saltata alla ripresa — è la scelta giusta, ma oggi nessuno
se ne accorgerebbe. Il codice esatto è nel file `GIRO-2-VERIFICA-FINALE.md`, sezione
«Da aggiungere prima di installare».

### 2 · La pulizia di guru2 — non confermata

Le 10 sfogline di prova create per il test: confermami che non sono rimaste, né loro né i
marcatori `gs_buono_mese_*` né le percentuali assegnate. Lo script di pulizia aveva avuto un
errore e la conferma non è mai arrivata.

### 3 · I tre controlli sul sito vero — mai riportati

Erano in sospeso dall'installazione del Giro 1:

- su `/le-sfogline/`, con Query Monitor: **la scansione della tabella utenti compare una
  volta sola o ancora due?** (se ancora due, segnala e basta: non è un guasto)
- **totale query di una pagina qualsiasi**, a freddo e ricaricando — è il numero che dice a
  Ennio se il sito è più leggero davvero
- **quante sfogline vere** si vedono ora nel nastro grande, e **se lascia vuoti** mentre
  scorre

---

## Ordine con cui affrontarle

1. La domanda qui sopra (passi A e B) — **subito, è l'unica che ha una risposta che scade**:
   più passa il tempo, più diventa difficile ricostruire cosa è uscito.
2. I tre controlli del punto 3 — sono letture, dieci minuti.
3. Il rendiconto — entro il 1° settembre.
4. La conferma su guru2 — quando capita.

Poi si apre il **Giro 3**. Non anticiparlo.
