# Via libera: installa Giro 0 + Giro 1 sul sito vero

Le cinque verifiche sono superate e fatte bene. In particolare la n. 3 — la pagina di prova
creata apposta con dentro lo shortcode del nastro grande — era quella che mancava, ed è
quella che dimostra che la correzione fa davvero il suo mestiere.

Bene anche aver misurato con **due richieste HTTP separate** invece di due chiamate nello
stesso processo: è la differenza fra misurare la cache e misurare sé stessi.

**Installa.**

---

## Sul punto 5, e non serve rifarlo

Hai ragione a distinguere: 12 → 1 è il costo della sola funzione del nastro, non il totale
della pagina. **Va benissimo così, non tornare su guru2 a inseguire quel numero.** Il
totale di pagina è più significativo se misurato in produzione, dove il tema funziona: lo
prendi lì, subito dopo l'installazione (vedi sotto).

Il salto da 12 a 1 è esattamente quello previsto: la query che resta è la rilettura del
transient.

---

## Subito dopo l'installazione: tre cose, in quest'ordine

### 1 · La verifica che su guru2 non era possibile fare

Il controllo nuovo usa `has_shortcode()` sul **contenuto** della pagina. Funziona se lo
shortcode `[gs_nastro_grande_sfogline]` sta scritto in `post_content`. **Sul sito vero
quella pagina è stata composta da Ennio**, e alcuni costruttori di pagine salvano il
contenuto altrove (nei meta) invece che in `post_content`: in quel caso `has_shortcode()`
non lo trova.

Sul sito vero, verifica che su `/le-sfogline/`:

- il **nastro grande** ci sia (deve esserci, non l'abbiamo toccato);
- il **nastro piccolo** non ci sia (era già così: lo nascondeva il CSS);
- e soprattutto, con Query Monitor, che **la scansione della tabella utenti compaia una
  volta sola** e non due.

**È il terzo punto quello che conta.** Se ne vedi ancora due, vuol dire che
`has_shortcode()` non trova lo shortcode su quella pagina.

**Non è un guasto e non c'è da correre ai ripari.** Nel caso peggiore la correzione
semplicemente non si applica su quella pagina, che resta esattamente com'era prima —
il nastro piccolo continua a essere calcolato e poi nascosto dal CSS, come da mesi. Tutto
il resto del sito è già più leggero. **Segnalalo e basta**, sarà una riga da aggiungere a
un giro successivo, non un'urgenza.

### 2 · Il numero che interessa a Ennio

In produzione, con Query Monitor, prendi il **totale delle query di una pagina qualsiasi**
(la home va bene), a freddo e poi ricaricando. È la misura che dice se il sito è più
leggero davvero, e su guru2 non era ottenibile. Riportagli i due numeri.

### 3 · La conta rimasta in sospeso dal Giro 0

Apri `/le-sfogline/` sul sito vero e riporta a Ennio:

- **quante sfogline vere** compaiono ora nel nastro grande, tolti i nomi inventati;
- **se il nastro lascia dei vuoti** mentre scorre.

Il vuoto può capitare perché la pista stampa l'elenco due volte e scorre di metà larghezza:
se le sfogline sono due o tre, l'elenco raddoppiato non copre lo schermo e si vede un buco
passare. Su guru2, con sei account di prova, non si nota.

**Se il vuoto c'è**, la correzione è far dipendere il numero di ripetizioni da quante
sfogline ci sono — cioè ripetere **le stesse persone** più volte:

```php
// Il ciclo dell'animazione (translateX -50%) è continuo solo se l'elenco
// raddoppiato supera la larghezza dello schermo. Con poche sfogline servono
// più ripetizioni delle STESSE persone: un nastro ripete, non inventa.
$giri = max( 2, (int) ceil( 12 / max( 1, count( $sfogline ) ) ) );
for ( $giro = 0; $giro < $giri; $giro++ ) {
```

**Applicala solo se il vuoto c'è davvero.** E niente altro per riempire il nastro: non
allargare i criteri, non includere sfogline senza Vetrina attiva, e nessun nome di esempio
in nessuna forma. Se la pagina resta povera è un'informazione vera che Ennio deve avere.

---

## Poi fermati di nuovo

Riporta i tre punti e aspetta. Il Giro 2 (la chiusura del mese, che può raddoppiare gli
sconti) parte dopo.

---

## Una nota sul lavoro, non sul codice

Hai fatto bene a fermarti all'inizio e a verificare che le modifiche descritte come «già
applicate» esistessero davvero, invece di fidarti del documento. È esattamente il
comportamento giusto quando si eredita un lavoro da un'altra sessione: **il documento
descrive, il codice decide.** Continua così anche nei giri successivi.
