# Istruzione: la quota associativa che resta nei testi

**Per Claude Code Ennio — 28/08/2026, scritta su 3.299.1**

3.299.0 ha tolto la quota associativa come condizione dell'iscrizione; 3.299.1 ha corretto i
quattro punti rimasti indietro nei pannelli. **Tutte e due le correzioni sono giuste e
verificate**: il checkbox bloccante e il suo controllo lato server sono spariti insieme — non
c'è nessun campo orfano e nessun controllo orfano, quindi l'iscrizione funziona — e nel plugin
non resta più nessun «verifica il pagamento della quota».

Resta però un'altra cosa, che una ricerca su quella frase non poteva trovare: **la quota
associativa continua a esistere come concetto in sette punti**, e cinque di questi si leggono
a schermo. Contraddicono «l'iscrizione è gratuita» esattamente come i quattro corretti in
3.299.1, solo con parole diverse.

## Le cinque che si vedono

### 1 e 2 — la frase gemella dell'account leggero, identica in due file

`includes/letture.php:166` e `includes/spiegazioni.php:352` contengono **la stessa identica
frase**, copiata in due posti:

> «…serve essere una sfoglina iscritta oppure iscriversi con l'account leggero "solo per
> commentare" (**senza quota associativa**, senza accesso al resto del gaming).»

Quel «senza quota associativa» aveva senso quando l'iscrizione normale una quota ce l'aveva.
Adesso dice a chi legge che l'iscrizione normale costa. Va sostituito in **tutti e due i file**
con la stessa formula — per esempio:

> «(dà accesso solo ai commenti delle Letture, non al resto del percorso)»

**Correggerne uno solo e non l'altro è la trappola di questo lavoro**: sono due copie della
stessa frase in file diversi, esattamente come i due pannelli gemelli di 3.299.1. Dopo la
correzione, `grep -rn "senza quota associativa" .` non deve trovare più niente.

### 3 — la FAQ

`includes/faq.php:232`:

> «Sì: puoi iscriverti con l'account leggero "solo per commentare", gratuito e immediato,
> **senza quota associativa**.»

Qui la contraddizione è ancora più netta, perché la frase mette «gratuito» in contrapposizione
a un'iscrizione che ormai è gratuita anche lei. Basta togliere le due parole finali: resta
«gratuito e immediato», che è vero e sufficiente.

### 4 e 5 — l'altra frase gemella, nei pannelli dei pagamenti

`includes/abbonamenti.php:103` e `includes/token.php:274`, di nuovo **la stessa frase in due
file**:

> «Sono versamenti volontari che le sfogline possono decidere di fare **oltre alla quota
> associativa**, ad esempio per le consulenze private con i maestri…»

Va detto senza nominare una quota che non c'è più — per esempio «Sono versamenti volontari,
separati da tutto il resto, che le sfogline possono decidere di fare per le consulenze
private…». Anche qui: **tutti e due i file, stessa formula**.

## Le due che non si vedono, ma vanno sistemate lo stesso

Sono commenti nel codice. Non li legge nessun utente, ma li legge la prossima persona che apre
quei file, e le raccontano un mondo che non esiste più:

- `includes/letture.php:21` — «Iscrizione leggera e immediata (niente quota associativa,
  niente…)».
- `includes/token.php:7` — «stesso schema manuale della quota associativa».

## Quello che NON devi toccare

- **`importo_quota`**, la chiave dell'impostazione (`helpers.php:159`, `admin.php:1124`,
  `abbonamenti.php:81`). È il nome interno di un'opzione già salvata nel database:
  rinominarla farebbe perdere il valore che c'è dentro. L'etichetta mostrata è già stata
  cambiata in «Importo contributo gaming dopo la prova», ed è quello che conta.
- **La pagina Supporter** (`includes/pagina-supporter.php`): vedi qui sotto.

## Quello che NON devi decidere tu

`includes/pagina-supporter.php` dice, in due punti (righe 349 e 511):

> «29,00 € — **quota annuale**» · «La **quota di adesione come Supporter** è di 29,00 euro
> all'anno e contribuisce direttamente al sostegno delle attività culturali…»

**È lo stesso importo del contributo gaming dopo la prova (29,00 €).** Delle due l'una: o sono
due cose distinte che per caso costano uguale, o sono la stessa cosa chiamata in due modi — e
in questo secondo caso una delle due pagine sta dicendo la cosa sbagliata a chi paga.

**Non indovinare.** Chiedi a Ennio, in una riga:

> *«I 29 € del Supporter e i 29 € del contributo gaming dopo la prova sono la stessa cosa o due
> cose diverse? Se sono diverse, chi versa la quota Supporter ha anche il gaming, o no?»*

E riporta la risposta senza applicarla da solo: qui si parla di soldi che qualcuno versa
davvero, ed è il tipo di cosa su cui una risposta sbagliata si scopre da una persona arrabbiata,
non da un test.

## Come si prova

Non serve un browser: sono testi. Dopo le correzioni, questi tre censimenti devono tornare
vuoti (esclusi `readme.txt`, che è la storia, e la pagina Supporter finché Ennio non risponde):

```bash
grep -rn "quota associativa" --include=*.php --include=*.js . | grep -v pagina-supporter
grep -rn "senza quota associativa" --include=*.php .
grep -rni "quota" --include=*.php . | grep -v "importo_quota\|pagina-supporter\|calendario.php\|shortcodes.php:642\|gaming.css"
```

L'ultimo lascia fuori apposta il calendario dei corsi, dove «quota» vuol dire il prezzo di un
corso e va benissimo così.

Poi: `php -l` sui file toccati, e la suite intera per controllare che nessun test si aspettasse
una di quelle frasi (`test-faq.php` o simili potrebbero cercarla alla lettera — se un test
diventa rosso, è il test che va aggiornato, non la frase da rimettere).

## La consegna

Versione **3.299.2** nei tre punti, changelog che dica **cosa leggeva una persona**: che dopo
aver letto «l'iscrizione è gratuita» trovava scritto, in cinque punti del sito, che esiste una
quota associativa. Non «sistemati alcuni testi».

## Una cosa da non fare

**Non correggere una sola delle due copie di una frase gemella.** Sono quattro frasi in cinque
file, e due di loro esistono in doppia copia: è già successo in 3.299.0, corretto in 3.299.1, e
succederebbe di nuovo. Alla fine, il censimento — non la memoria di quello che hai toccato.
