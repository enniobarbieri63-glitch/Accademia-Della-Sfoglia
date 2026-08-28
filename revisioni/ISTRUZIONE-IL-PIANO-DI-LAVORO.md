# Istruzione: il Piano di Lavoro — il nuovo pannello generale

**Per Claude Code Ennio 2 — 28/08/2026**

Ennio ha chiesto un pannello «veramente funzionale, ben leggibile, che non
stanca gli occhi, intuitivo anche per chi non conosce i meccanismi, con le cose
da fare del giorno e tutto quello che serve **in una sola schermata**».

Ha fatto quattro esperimenti suoi, io ne ho fatti altri, e ne e uscito un
disegno solo. Il disegno e nel repository:

```
design/pannello/anteprime/Piano.png          <- guardalo prima di leggere oltre
design/pannello/anteprime/PianoScuro.png
design/pannello/Piano.dc.html                <- il codice del disegno
```

**Guarda il PNG prima di continuare.** Quello che segue spiega *perche* e
fatto cosi e *cosa serve nel codice* per farlo funzionare; senza avere
davanti l'immagine, meta delle frasi non si capiscono.

---

## Le eccezioni, dette prima

**Questo lavoro non tocca niente di quello che esiste.**

- **Il pannello di oggi resta dov'e, intero e funzionante.** Il Piano di
  Lavoro e una pagina **nuova e in piu**. Non si cancella `pannello-nuovo.php`,
  non si spostano riquadri, non si tocca nessun `add_menu_page` esistente.
  Quando Ennio avra usato il nuovo per un mese e dira che va, allora si
  decidera cosa fare del vecchio. Non prima, e non da soli.
- **Nessuna logica di gioco cambia.** Questo e un lavoro di lettura e di
  presentazione. Se durante il lavoro ti accorgi che una funzione che leggi
  ha un difetto, **scrivilo e basta**: si corregge a parte, non dentro
  questo lavoro.
- **Niente query nuove sul database finche non e dimostrato che servono.**
  Il motivo e nel paragrafo «La cosa che puo andare storta».

---

## Cosa c'e oggi, misurato

Il pannello di oggi (`pannello-nuovo.php`) e **73 riquadri in 10 gruppi**,
ordinati **per argomento**: le sfide di qua, i messaggi di la, i contenuti
piu sotto, le impostazioni in fondo.

Ordinare per argomento e comodo per chi ha scritto il codice, perche
rispecchia i file. Non e comodo per Ennio, perche la sua domanda alle otto
del mattino non e «dove sono le sfide»: e **«cosa devo fare oggi»**. E per
rispondere a quella domanda, oggi, deve aprire sette riquadri diversi in
quattro gruppi diversi e ricordarsi a mente cosa ha visto.

### E qui c'e la scoperta che rende tutto il resto facile

`includes/control-panel.php`, funzione `gs_riepilogo_dati()`, riga ~539:

```php
$ricette = count( gs_ricette_in_attesa() );
$bio     = count( gs_bio_in_attesa() );
```

**Il pannello carica le cose che aspettano, le conta, e poi le butta via.**
Tiene solo il numero. Lo fa **quattordici volte**, per quattordici code
diverse.

Vuol dire che il lavoro piu costoso — andare a cercare nel database chi
aspetta — **e gia fatto oggi, ad ogni caricamento del pannello**. Quello che
manca non e la fatica: e non buttare via il risultato.

Questa e la chiave dell'intero lavoro. Tutto il resto e disegno.

---

## Il principio, in una riga

> Il pannello di oggi e ordinato **per argomento**.
> Il Piano di Lavoro e ordinato **per quando serve**.

Tre fasce, dall'alto:

1. **Cosa ti aspetta** — le sei code, affiancate, con dentro i nomi veri e
   da quanti giorni aspettano.
2. **Il polso** — una riga sola: sfogline attive, prove in scadenza, sponsor
   scaduti, sfida in corso.
3. **Tutto il resto** — le 62 sezioni, raggruppate non per argomento ma per
   **quanto spesso le apri**: ogni giorno / ogni settimana / ogni tanto /
   si imposta una volta e basta (questi ultimi chiusi, si aprono cliccando).

---

## TAPPA 1 — Non buttare via quello che hai gia in mano

**E l'unica tappa che tocca il codice esistente, ed e piccola.**

`gs_riepilogo_dati()` oggi restituisce numeri. Deve restituire **anche** gli
elenchi che ha gia caricato. Non al posto dei numeri: **in piu**, cosi
nessuna delle 73 caselle di oggi si accorge del cambiamento.

```php
// PRIMA
$ricette = count( gs_ricette_in_attesa() );

// DOPO
$lista_ricette = gs_ricette_in_attesa();
$ricette       = count( $lista_ricette );   // il vecchio numero, identico
// ...e in fondo, dentro l'array restituito:
'liste' => array(
    'ricette' => $lista_ricette,
    'bio'     => $lista_bio,
    // ...tutte e quattordici
),
```

**Verifica obbligatoria dopo questa tappa:** apri il pannello vecchio e
controlla che **tutti i numeri siano identici a prima**. Se uno solo e
cambiato, hai spostato una chiamata di posto e va rimesso. Questa e la sola
tappa che puo rompere qualcosa di esistente, ed e per questo che si fa da
sola, si prova, e poi si va avanti.

---

## TAPPA 2 — Da quanti giorni aspetta

Il disegno mostra, su ogni riga, **da quanti giorni quella cosa e li**. E la
differenza fra «hai 6 ricette» e «una ricetta aspetta da 11 giorni».

Per le cose che sono **contenuti** (ricette, testimonianze, biografie come
post) la data c'e gia: e `post_date`. Nessun lavoro.

**Per tre code la data non esiste.** Sono quelle tenute come dato dell'utente,
non come contenuto: quando l'utente entra nello stato «in attesa» non viene
scritto da nessuna parte *quando*.

**Non inventare una data.** Non usare la data di registrazione, non usare
l'ultimo accesso, non stimare. Si fa cosi:

1. **Da adesso in poi**, nel punto in cui la cosa entra in attesa, si scrive
   anche il momento: `gs_in_attesa_dal` (un timestamp).
2. **Per le cose gia in attesa oggi**, nella colonna dei giorni si scrive
   **«—»**, non un numero.

Una lineetta e onesta e non costa niente. Un numero inventato invece verra
letto come vero, e fra due mesi Ennio prendera una decisione basandosi su un
dato che nessuno ha mai misurato.

**Ordinamento:** dentro ogni coda, **il piu vecchio in cima**. Le righe senza
data («—») vanno **in fondo**, non in cima: non sappiamo quanto aspettano, e
mettere in testa una cosa che non sappiamo misurare sposta l'attenzione sul
posto sbagliato.

---

## TAPPA 3 — Un solo pulsante acceso per coda

Questa e una scelta di disegno, e va rispettata anche se sembra strana.

In ogni coda, **un solo pulsante e colorato**: quello della riga piu in alto,
cioe la cosa che aspetta da piu tempo. Tutte le altre righe hanno il pulsante
in grigio chiaro — funzionano, si possono premere, ma non tirano l'occhio.

**Sei punti colorati in tutto lo schermo, non venti.**

Il motivo detto semplice: se tutto e urgente, niente e urgente. Un pannello
con venti pulsanti accesi si guarda per due secondi e si chiude. Uno con sei
si legge.

**Un verbo diverso su ogni pulsante** — e un'idea di Ennio, ed e giusta:

| coda | pulsante |
|---|---|
| iscrizioni | **Approva** |
| ricette | **Leggi** |
| biografie | **Controlla** |
| testimonianze | **Leggi** |
| conversazioni | **Rispondi** |
| vetrine partner | **Verifica** |

Non sei volte «Vai». La parola dice gia cosa succede dopo, e chi non conosce
i meccanismi non deve indovinare.

**Il verde solo per cio che porta avanti il lavoro** (l'altra idea di Ennio):
approva, pubblica, conferma. Mai per «annulla», mai per «torna indietro»,
mai per decorazione.

---

## TAPPA 4 — «e altre 2 cose sue»

La parte che nei disegni di Ennio non c'era, e che secondo me e la piu utile.

**La stessa persona compare in piu code contemporaneamente.** Ha mandato una
ricetta, aspetta l'approvazione della biografia, e ti ha scritto un
messaggio. Oggi la incontri tre volte in tre punti diversi del pannello e
non ti accorgi che e la stessa.

Nel disegno, accanto al nome c'e una pastiglia piccola: **«e altre 2 cose
sue»**. Ci si clicca e si vedono tutte insieme, e si sbrigano in una volta.

Nel codice e facile, **perche le liste ce le hai gia in mano dalla Tappa 1**:
si raccolgono gli ID utente di tutte e quattordici le liste, si contano, e
chi compare piu di una volta si segna.

```php
// Le liste sono gia caricate: qui non si tocca il database.
$conta = array();
foreach ( $liste as $coda => $righe ) {
    foreach ( $righe as $r ) { $conta[ $r->user_id ][] = $coda;  }
}
// $conta[ 42 ] = array( 'ricette', 'bio', 'messaggi' )  ->  «e altre 2 cose sue»
```

**Nessuna query in piu.** E il motivo per cui la Tappa 1 viene prima di
tutto: una volta che le liste non si buttano via, questa cosa costa dieci
righe.

---

## Come deve essere fatto, graficamente

Sono le richieste di Ennio, tradotte in numeri. Nel disegno ci sono gia;
qui sono scritte perche non si perdano nel rifacimento.

- **Colori presi dal plugin vero**, non inventati:
  `#CD8B0C` (oro), `#1F6E37` (verde), `#8A5A2F` (marrone), `#C23B3B` (rosso),
  `#8C4A7A` (viola), `#2B7A9E` (azzurro). Sono gia quelli che il sito usa:
  un pannello con colori suoi sembrerebbe un altro programma.
- **Fondo color farina** `#F4EFE4`, non bianco pieno. Testo marrone scuro,
  non nero. E la richiesta «non deve stancare gli occhi»: il bianco puro con
  il nero puro e la combinazione che stanca di piu.
- **Testo grande davvero**: le righe delle code a **19px**, non a 13. Ennio
  legge questo pannello tutti i giorni.
- **Niente emoji.** Cambiano faccia su ogni dispositivo, e a schermo grande
  sembrano appiccicate. Disegni SVG, tutti nello stesso stile, tutti dello
  stesso spessore di linea.
- **Versione scura** compresa (`PianoScuro.png`). Non e un vezzo: Ennio
  lavora anche di sera.

---

## La cosa che puo andare storta

**Il pannello che diventa lento.**

Oggi quelle quattordici chiamate caricano gia tutto, quindi mostrare i primi
tre nomi di ogni coda **non costa niente in piu**. Ma se qualcuno, per fare
la pastiglia o l'ordinamento, aggiunge una `get_user_meta()` dentro un ciclo
su ogni riga, con duecento sfogline si arriva a centinaia di letture per ogni
apertura del pannello.

**La regola:** dentro i cicli che disegnano le righe, **nessuna chiamata al
database**. Tutto quello che serve si prende dalle liste gia caricate.

**Come si prova, senza dover indovinare:** WordPress conta le query da solo.
Si stampa `get_num_queries()` in fondo alla pagina, si apre il pannello
vecchio e si segna il numero; poi si apre il Piano di Lavoro e si guarda.
**Deve essere dello stesso ordine.** Se e il doppio, c'e una query in un
ciclo, e va trovata prima di andare avanti.

---

## L'ordine di lavoro

1. **Tappa 1** (le liste restituite) — e la sola che tocca codice esistente.
   Poi: apri il pannello vecchio, controlla che i 73 riquadri mostrino gli
   stessi numeri di prima. Zip.
2. **La pagina nuova**, vuota, in una voce di menu nuova. Zip.
3. **Le sei code** con i nomi, i verbi, il pulsante acceso solo sul primo.
   Ancora senza i giorni. Zip: a questo punto Ennio la puo gia guardare e
   dire se la direzione va bene, **prima** che tu abbia fatto il resto.
4. **Tappa 2** (i giorni di attesa, e la lineetta dove non si sa).
5. **Tappa 4** (la pastiglia).
6. **Il polso** e le **62 sezioni per frequenza**.
7. `prova.sh`, e il conteggio delle query.

**Fermati al punto 3 e manda lo zip.** Se la direzione non e quella giusta,
e meglio scoprirlo li che alla fine.

---

## Una cosa da non fare

**Non spegnere il pannello vecchio, e non spostare le sue voci di menu.**

Ennio ci lavora tutti i giorni e sa dove sono le cose. Un pannello nuovo che
arriva togliendo il vecchio non e un miglioramento: e un pannello nuovo piu
un giorno perso a ritrovare tutto.

I due convivono. Poi decide lui, quando avra usato il nuovo abbastanza da
sapere se gli serve davvero.
