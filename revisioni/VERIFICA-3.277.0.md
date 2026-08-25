# 3.277.0 verificata — P1, P2, P5, P6 corrette. Una data sbagliata in P3, e una cosa da spostare in cima alla lista.

Letto il codice, non il riassunto. Diff completo contro la 3.276.0: tocca **esattamente** i
file che doveva toccare — `artigiani.php`, `scuole-cucina.php`, `abbonamenti.php`,
`helpers.php`, `sfogline-extra.php`, `esperti.php`, `gaming.js` — `php -l` pulito su tutti
e sette, `node --check` pulito sul JavaScript, e i due file gemelli sono ancora **identici
riga per riga** dopo le correzioni (l'ho riverificato normalizzando i prefissi: le uniche
differenze restano i nomi, le classi CSS e l'emoji).

**P1, P2, P5, P6 sono corrette bene.** P3 è corretta nella struttura ma **sbaglia di uno o
due giorni**, e nel verso che conta. E la cosa trovata di iniziativa — il tema che altera le
query — **non è una cosa da guardare con calma: è la più urgente di tutto questo giro.**

---

## Quello che è stato fatto meglio della mia proposta, e va detto

**Tre cose, tutte trovate applicando il documento, non copiandolo.**

**1. L'ordine dell'identificativo in P1.** Io avevo scritto di controllare e scrivere `rif`
**prima** dei controlli. Il codice lo mette **dopo**, e la ragione è scritta nel commento:

> *«DOPO i controlli sopra, altrimenti una richiesta respinta per la scadenza segnerebbe il
> rif come "già visto" e il reinvio confermato dallo stesso clic verrebbe rifiutato per
> errore.»*

**È giusto, e la mia proposta era rotta.** Con il mio ordine, il primo tentativo veniva
respinto per la scadenza *dopo* aver già bruciato il `rif`; poi Ennio confermava, il
JavaScript rimandava lo stesso `rif`, e si vedeva *«Questo pagamento risulta già
registrato»* su un pagamento mai registrato. Sarebbe stata una fessura scoperta in
produzione, sul pannello dei soldi.

**2. La distinzione in `abbonamenti.php`.** Il documento diceva «stesso difetto, stesso
schema». Il codice invece si è accorto che lì la scadenza **non spegne niente da sola** —
è il gestore che imposta «Scaduto» a mano — e quindi la fase `scaduto` **avvisa Ennio, non
la sfoglina**, dicendo *«il promemoria è passato e lo stato non è stato aggiornato»*.
Copiare il mio testo avrebbe mandato a una socia in regola *«il tuo abbonamento è
scaduto»*. **Questo è leggere il modulo invece di applicare la ricetta.**

**3. Il `toggle` sul JavaScript.** Aprire il modulo del bonifico richiedeva di allargare il
listener a `.gs-sezione-aiuto` — che però è la classe di tutti i riquadri «Come funziona»
del plugin. Il codice se n'è accorto e ha separato i due comportamenti, invece di far
chiudere a fisarmonica mezzo pannello.

E la correzione del cooldown è esatta: le `set_transient` ora stanno **dopo** i controlli
sulla lunghezza — che sono letture pure — e **prima** del primo effetto, in tutte e due le
funzioni gemelle.

---

## L'unica cosa sbagliata: la fase «scaduto» arriva uno o due giorni prima del vero

**File:** `includes/artigiani.php:921-928`, `includes/scuole-cucina.php` (stesse righe),
`includes/abbonamenti.php:121-127` — VERIFICATO, e provato facendo girare il conto

### Il conto, con date vere

Ho eseguito l'aritmetica esatta del codice nuovo su una scadenza al **1° ottobre 2026**,
mettendo a fianco quello che `gs_art_attivo()` decide **nello stesso momento**:

| Giorno del cron | `$giorni_mancanti` | fase scelta | vetrina davvero online? |
|---|---|---|---|
| 24 settembre, h9 | 6 | preavviso | **sì** |
| 29 settembre, h9 | 1 | ultimo | **sì** |
| 30 settembre, h9 | 0 | ultimo | **sì** |
| **30 settembre, h23** | **−1** | **scaduto** | **sì** |
| **1 ottobre, h9** | **−1** | **scaduto** | **sì** |
| 2 ottobre, h9 | −2 | scaduto | no |

Due righe sbagliate, e la seconda è quella che pesa:

- **il 1° ottobre**, giorno della scadenza, la vetrina è **ancora online** —
  `gs_art_attivo()` fa `'2026-10-01' >= '2026-10-01'`, che è vero — e il partner riceve
  *«la tua vetrina non è più visibile»*. **L'email dice il falso.**
- **il 2 ottobre**, il giorno in cui la vetrina sparisce davvero, il marcatore
  `2026-10-01|scaduto` è già stato scritto il giorno prima: **non parte niente.** L'avviso
  che serviva di più è l'unico che non arriva nel momento giusto.
- e se il cron gira dopo le 22 di sera (l'ora legale sposta di due ore), l'errore diventa di
  **due giorni**: il 30 settembre alle 23 il partner riceve già il messaggio di vetrina
  spenta.

### La causa è la stessa di prima

```php
$ts = strtotime( $scadenza . ' 00:00:00' );
$giorni_mancanti = (int) floor( ( $ts - $oggi ) / DAY_IN_SECONDS );
```

Sono i **confini** che sono stati spostati, non il conto. E il conto ha due difetti:

1. `$ts` è **mezzanotte**, `$oggi` è **adesso**: la differenza è quasi sempre una frazione
   negativa, e `floor()` di −0,375 fa **−1**. Il giorno stesso viene contato come già
   passato.
2. `strtotime()` lavora nel fuso del server (di norma UTC), `current_time('timestamp')`
   restituisce UTC **più lo scarto di WordPress** (Roma, +2 d'estate). **Non sono nello
   stesso fuso**, e due ore bastano a far scavalcare il confine.

Il fatto che l'oggetto dell'email in `abbonamenti.php` sia stato scritto *«scade **oggi o
domani**»* dice che l'ambiguità si era sentita — ma è stata aggirata nel testo invece che
tolta dal conto.

### Correzione

**Confrontare due date, non un timestamp e una mezzanotte** — così il conto usa
esattamente lo stesso metro di `gs_art_attivo()`, che legge `current_time( 'Y-m-d' )`:

```php
		// Due date attraverso la stessa funzione: così il conto usa lo stesso
		// metro di gs_art_attivo(), che confronta gs_art_scadenza con
		// current_time('Y-m-d'). Con un timestamp "adesso" contro una
		// mezzanotte, floor() di una frazione negativa conta come già passato
		// il giorno stesso della scadenza — e strtotime() (fuso del server)
		// e current_time('timestamp') (fuso di WordPress) non sono nemmeno
		// nello stesso fuso: bastano due ore a scavalcare il confine.
		// round() e non floor(): nei giorni del cambio d'ora la differenza è
		// di 23 o 25 ore, e floor() sbaglierebbe di un giorno.
		$giorni_mancanti = (int) round(
			( strtotime( $scadenza ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS
		);

		if ( $giorni_mancanti > $giorni_preavviso || $giorni_mancanti < -3 ) { continue; }
		if ( 0 === $giorni_mancanti )      { $fase = 'ultimo'; }   // scade oggi: ancora online
		elseif ( $giorni_mancanti < 0 )    { $fase = 'scaduto'; }  // da ieri non è più visibile
		else                               { $fase = 'preavviso'; }
```

Con questa versione, sulla stessa scadenza del 1° ottobre:

| Giorno del cron | `$giorni_mancanti` | fase | vetrina online? |
|---|---|---|---|
| 24 settembre | 7 | preavviso | sì |
| 30 settembre | 1 | preavviso | sì |
| **1 ottobre** | **0** | **ultimo** | **sì** ✅ |
| **2 ottobre** | **−1** | **scaduto** | **no** ✅ |
| 5 ottobre | −4 | (niente) | no |

Ogni avviso cade nel giorno in cui il suo testo è vero.

**Il testo della fase `ultimo` va adeguato**, perché adesso parte il giorno stesso:

> *«…scade oggi: **da domani**, se non rinnovi, non sarà più visibile nella sezione
> pubblica.»*

e in `abbonamenti.php` l'oggetto diventa *«Il tuo abbonamento scade oggi»*, senza il
«o domani».

**Compromesso da dichiarare:** con `ultimo` legato al giorno esatto, se il cron salta
proprio quel giorno l'avviso «scade oggi» non parte. Non è grave — resta il preavviso a
sette giorni e lo `scaduto` il giorno dopo, che è quello che conta — ma va detto invece che
scoperto.

**Da fare su tutti e tre i file** (`artigiani.php`, `scuole-cucina.php`, `abbonamenti.php`):
il conto è copiato identico in tutti e tre.

**Verifica:** metti una scadenza a oggi su una vetrina di prova, fai girare
`gs_daily_cron` a mano, e controlla che l'email dica *«scade oggi»* e non *«non è più
visibile»* — mentre la vetrina, aperta in un'altra scheda, è ancora lì.

---

## La cosa da spostare in cima: il tema che altera le query non è «da guardare con calma»

Questa è la parte importante di questa verifica.

Hai trovato che il tema altera in silenzio le query con il parametro `author` di WordPress,
e hai corretto le due funzioni dei partner. **Giusto, e ben verificato** — il commento cita
il confronto dell'SQL generato, che è la prova giusta, e `gs_solo_tipo()` documentava già
dal 16 agosto che `pre_get_posts` passa oltre `suppress_filters`.

Ma la conclusione — *«una decina di altri punti… è una cosa da guardare con calma a
parte»* — **non regge, e la ragione sta in una funzione sola.**

### `gs_tavolo_di_oggi()`

`includes/tavolo.php:57-69`. Usa `'author'`, e **non è protetta da `gs_solo_tipo()`**:

```php
$ultimi = get_posts( array(
	'post_type'      => 'gs_tavolo',
	'author'         => $uid,
	'posts_per_page' => 1,
	...
) );
if ( ! $ultimi ) { return null; }
```

È l'unica cosa che tiene chiusa questa porta, a `tavolo.php:172`:

```php
if ( gs_tavolo_di_oggi( $uid ) ) { wp_send_json_error( array( 'message' => 'Hai già caricato la tua foto di oggi. Torna domani per la prossima.' ) ); }
...
gs_add_points( $uid, gs_get_points_value( 'tavolo_foto', 5 ), 'Il Tavolo di Lavoro: foto del giorno' );
```

**Se quella query restituisce zero risultati per la ragione che hai trovato, il controllo
non controlla niente: una sfoglina può caricare foto tutto il giorno e prendere +5 punti
ogni volta.** Su una soglia da 2500 punti al mese per il Buono Sfoglia.

Non è teoria: è **esattamente il sintomo che hai osservato** — «restituisce sempre zero
risultati» — applicato all'altra funzione che ha la stessa forma.

**È F1 del primo documento — il tetto ai punti giornalieri — che arriva da un'altra
porta.**

### E una seconda senza rete: il diario

`includes/forms.php:66-77` (`gs_get_diario()`). Usa `'author'`, non ha `gs_solo_tipo()`, e
**non ha nemmeno `suppress_filters`**. Legge post `private`. Se la query viene alterata, il
diario personale di una sfoglina o è vuoto, o mostra qualcosa che non è suo.

### Le altre otto sono meno gravi, ma vanno contate

`aiuto.php:69`, `ricettario.php:109`, `testimonianze.php:74`, `sfoglia-insegna.php:64`,
`matterello-parlante.php:69`, `tavolo.php:77`, `sfogline-extra.php:179` e `:195` **sono
tutte protette da `gs_solo_tipo()`**. Il loro modo di rompersi è quindi *«l'elenco è
vuoto»*: il ricettario di una sfoglina senza le sue ricette, il tavolo senza le sue foto,
le testimonianze sparite. Niente di pericoloso — ma se sta succedendo, **sta succedendo
adesso sul sito vero**, e nessuno l'ha collegato al tema.

### Cosa fare, in questo ordine

**Primo: sapere se succede davvero in produzione, non solo su guru2.** La prova non richiede
codice né SSH, e la può fare Ennio in due minuti:

> Apri il sito da socia (o guarda con lei) e controlla: **una sfoglina che ha già caricato
> la foto di oggi, se prova a caricarne un'altra, viene fermata?** E il suo Ricettario e il
> suo Tavolo di Lavoro mostrano le cose che ha già mandato, o sono vuoti?

Se sono vuoti, o se la seconda foto passa, **è confermato in produzione** e diventa la cosa
più urgente del progetto. Se invece tutto si vede e la seconda foto viene fermata, allora
il tema di guru2 non è configurato come quello vero, e la faccenda rientra — ma va comunque
chiusa, perché due installazioni che si comportano in modo diverso rendono inutili le prove
su guru2.

**Secondo: non correggere le altre dieci una per una come le due dei partner.** Per gli
artigiani il giro completo su `gs_art_elenco()` va benissimo — sono decine di righe. Per
`gs_tavolo` o `gs_ricetta` sarebbero **migliaia**, e leggerle tutte a ogni caricamento di
pagina è un rimedio peggiore del male.

La correzione che le sistema tutte insieme è **rimettere a posto il tipo dopo che il tema
l'ha toccato**, in `helpers.php`:

```php
/**
 * Il tema altera le query con il parametro "author" via pre_get_posts, che è
 * un'azione e passa oltre "suppress_filters" (stessa causa già documentata in
 * gs_solo_tipo(); verificato il 25/08/2026 confrontando l'SQL generato).
 * Questo aggancio gira DOPO chiunque altro e rimette il tipo che il plugin
 * aveva chiesto: le query del plugin aggiungono 'gs_tipo_atteso' accanto a
 * 'post_type', e da lì in poi nessun tema può più svuotarle.
 */
add_action( 'pre_get_posts', 'gs_ripristina_tipo_query', PHP_INT_MAX );
function gs_ripristina_tipo_query( $q ) {
	$atteso = $q->get( 'gs_tipo_atteso' );
	if ( $atteso ) {
		$q->set( 'post_type', $atteso );
	}
}
```

e in ognuna delle dieci query, **una riga in più** accanto a quella che c'è già:

```php
	'post_type'      => 'gs_tavolo',
	'gs_tipo_atteso' => 'gs_tavolo',
```

Dieci righe in tutto, nessuna query riscritta, nessuna lettura in più. E `gs_solo_tipo()`
resta dov'è: è la rete sotto, non il rimedio.

**Compromesso da dichiarare:** se il tema alterasse anche `post_status` o l'autore stesso,
questo non basterebbe. Ripara il sintomo che hai misurato — il tipo reso irrilevante — e
non pretende di riparare quello che non hai visto.

**Terzo:** una volta chiuso, **`gs_tavolo_di_oggi()` merita comunque una seconda serratura**,
perché è l'unica cosa fra una sfoglina e punti illimitati. Il modo che il plugin usa già
altrove è un contrassegno con la data:

```php
	// Seconda serratura, indipendente dalla query: un contrassegno con la
	// data di oggi. Se per qualsiasi ragione la lettura dei post fallisce,
	// questo regge lo stesso. Scritto PRIMA dei punti, non dopo.
	$oggi_ymd = current_time( 'Y-m-d' );
	if ( get_user_meta( $uid, 'gs_tavolo_ultimo_giorno', true ) === $oggi_ymd ) {
		wp_send_json_error( array( 'message' => 'Hai già caricato la tua foto di oggi. Torna domani per la prossima.' ) );
	}
	update_user_meta( $uid, 'gs_tavolo_ultimo_giorno', $oggi_ymd );
```

subito prima di `wp_insert_post()`. **Compromesso:** se il caricamento fallisce dopo questa
riga, la sfoglina perde la foto di oggi. È il prezzo di non regalare punti, ed è lo stesso
compromesso che abbiamo accettato per il cooldown delle domande.

**Questa terza parte non è per questo giro.** Il primo passo è la prova di Ennio.

---

## Due cose piccole, per completezza

- **Vetrina nel cestino e controllo «una sola per account».** `gs_art_owner_post()` ora
  scorre `gs_art_elenco()`, che legge solo i post `publish`. Se la vetrina di un account è
  **nel cestino**, il controllo non la vede e se ne può creare una seconda; ripristinando
  la prima, l'account ne ha due e il partner ne modifica una sola. Caso raro (bisogna
  cestinare e poi ricreare), nessun danno ai soldi. **Da sistemare quando ci si ritorna**,
  passando `gs_art_elenco( array( 'publish', 'trash' ) )` solo per quel controllo.
- **P7 rimandato: confermo.** Il documento lo dice, e farlo insieme a E7 è la scelta giusta.

---

## Riepilogo

| | Stato |
|---|---|
| Cooldown domande (correzione della verifica scorsa) | ✅ esatta, in tutte e due le gemelle |
| **P1** · bonifico: doppio clic, importo, scadenza | ✅ **e l'ordine del `rif` corretto meglio della mia proposta** |
| **P2** · partner su email esistente + una vetrina per account | ✅ (resta il caso «vetrina nel cestino») |
| **P3** · avvisi a tre fasi | ⚠ **struttura giusta, data sbagliata di 1-2 giorni — vedi sopra** |
| P5 · «il SÌ definitivo» | ✅ in tutti e due i punti |
| P6 · contesto antispam | ✅ |
| P7 · foto per posizione | ⏸ rimandato con E7, d'accordo |
| Gemellaggio dei due file | ✅ ancora identici |
| **Tema che altera le query con `author`** | 🔴 **da promuovere: prova in produzione prima di tutto il resto** |

**Da fare adesso, in ordine:**

1. **Ennio fa la prova delle due foto** (due minuti, nessun codice).
2. **La data di P3** sui tre file — è una riga di conto e due testi.
3. Il resto dipende da com'è andata la 1.

---

## La decisione su P4 — la mia raccomandazione a Ennio

La domanda è: quando un partner corregge un refuso, la sua vetrina deve sparire dal sito
finché non la riapprovi?

**Consiglio la strada 2** — la vetrina già approvata resta online mentre la modifica
aspetta — per tre ragioni concrete:

1. **Non sono estranei.** Sono partner che hai già accettato tu, uno per uno, e che ti
   pagano 150 € l'anno. La moderazione preventiva serve contro chi non conosci.
2. **Il freno esiste ed è immediato.** «Sospendi» è a un clic e ha effetto subito. Il
   rischio non è «contenuto sbagliato online per sempre», è «contenuto sbagliato online
   finché non lo vedi» — che è la stessa finestra che hai già su tutto il resto del sito.
3. **Il danno dell'altra strada è certo, quello di questa è possibile.** Con la strada 1,
   **ogni** correzione di **ogni** partner spegne una vetrina pagata per ore o giorni.
   Con la strada 2, il rischio si materializza solo se un partner che hai scelto tu manda
   qualcosa di sbagliato.

**Se invece scegli la 1**, allora la parte del testo va corretta comunque: oggi il pannello
dice *«La vetrina resta online finché l'abbonamento è attivo»*, e non è vero nel momento in
cui il partner salva. **Quella frase va sistemata in tutti e due i casi** — cambia solo se
la si corregge o la si rende vera.

E c'è una cosa che vale per entrambe le strade: **la Posta interna non ha ancora un
campanello.** Con la strada 1, un avviso che nessuno vede è una vetrina spenta che nessuno
riaccende. È la ragione più concreta, finora, per chiudere quella decisione.

---

## Cosa leggo adesso

`area-pro.php` e `percorsi-lezioni.php` — corsi online, diplomi e punti automatici dei
percorsi. Ci arrivo sapendo cosa cercare: è dove A2, il doppio clic che brucia due livelli,
era già stato trovato dalla prima lettura. E adesso ho un secondo motivo per guardarci —
tutti i punti automatici, dopo quello che è saltato fuori sul Tavolo di Lavoro.
