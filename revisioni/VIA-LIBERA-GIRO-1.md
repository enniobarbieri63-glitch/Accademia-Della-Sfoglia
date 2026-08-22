# Via libera: installa il Giro 0, poi fai il Giro 1

Giro 0 accettato. Verifica pulita, modifica corretta, e hai fatto bene a fermarti prima di
installare.

## 1 · Installa il Giro 0 sul sito vero, adesso

I nomi inventati sono ancora visibili su `accademiadellasfoglia.it/le-sfogline/` in questo
momento. Non aspettare il Giro 1: installa subito questa modifica da sola, così la pagina
è a posto mentre lavori al resto.

## 2 · Subito dopo, guarda la pagina e conta

**Il «6 sfogline vere» che hai riportato è di Local, dove ci sono account di prova. Non
dice niente su quante ne compaiano in produzione.** Dalle schermate del sito vero risultano
almeno Bruno Cingolani e Giuseppe Govoni, ma il numero esatto non lo sappiamo.

Dopo l'installazione, apri `/le-sfogline/` e riporta a Ennio **quante sfogline vere
compaiono davvero** e **se il nastro grande si vede bene o lascia buchi**.

### Cosa potrebbe succedere, e perché

Il nastro grande stampa l'elenco **due volte** e poi lo fa scorrere con
`translateX(0) → translateX(-50%)` su una pista `width: max-content`. Il ciclo risulta
continuo **solo se l'elenco raddoppiato è più largo dello schermo**.

Con sei sfogline (Local) la pista è larga circa 4.000 px: nessun problema. Con due o tre —
possibile in produzione — scende sotto i 2.000 px, e su uno schermo da 1.400 px **si vede
un vuoto che scorre** invece di una fila continua. Su Local non lo noteresti mai.

### Se succede, la correzione è una riga — e non sono nomi finti

In `gs_sc_nastro_grande_sfogline()`, dove oggi c'è:

```php
for ( $giro = 0; $giro < 2; $giro++ ) {
```

far dipendere il numero di ripetizioni da quante sfogline ci sono davvero:

```php
// Il ciclo dell'animazione (translateX -50%) è continuo solo se l'elenco
// raddoppiato supera la larghezza dello schermo. Con poche sfogline servono
// più ripetizioni delle STESSE persone: un nastro ripete, non inventa.
$giri = max( 2, (int) ceil( 12 / max( 1, count( $sfogline ) ) ) );
for ( $giro = 0; $giro < $giri; $giro++ ) {
```

**Non proporre altre soluzioni per riempire il nastro.** In particolare: non aggiungere
sfogline senza Vetrina attiva, non allargare i criteri, e ovviamente non reintrodurre nomi
di esempio in nessuna forma. Se la pagina resta povera, è un'informazione vera che Ennio
deve vedere — vuol dire che poche sfogline hanno attivato la Vetrina, ed è una cosa da
risolvere parlando con loro, non nel codice.

**Applica questa correzione solo se il vuoto c'è davvero.** Se il nastro scorre bene,
lascia stare: è la voce G2-bis del documento e si vedrà al Giro 6 insieme al resto.

## 3 · Poi fai il Giro 1

Trovi tutto nel documento, sezione ORDINE DI LAVORO → GIRO 1. Le due correzioni
(la memoria di 15 minuti e il controllo di pagina) vanno insieme, sono nello stesso file.

**I tre punti su cui si sbaglia più facilmente, ripetuti perché contano:**

1. **`get_option( 'gs_page_sfogline' )`, mai il numero 64342.** Su Local quell'id è diverso:
   scriverlo a mano farebbe funzionare la correzione in produzione e non in prova.
2. **Non togliere ancora la riga di CSS** `body.page-id-64342 #gs-nastro-fisso { display: none !important; }`.
   Diventa superflua, ma finché il controllo PHP non è stato visto funzionare in produzione
   è l'unica rete rimasta.
3. **Verifica che il nastro piccolo si veda ancora sulle pagine normali.** È l'errore più
   probabile del giro: spegnerlo dappertutto invece che solo su «Le Sfogline». Provalo
   davvero, aprendo la home e un'altra pagina qualsiasi.

E **misura con Query Monitor**: carica una pagina, annota le query, ricarica. Se il secondo
caricamento non ne fa molte di meno, il transient non sta lavorando — fermati e capisci
perché, non consegnare.

Poi fermati e riporta, come hai fatto adesso.
