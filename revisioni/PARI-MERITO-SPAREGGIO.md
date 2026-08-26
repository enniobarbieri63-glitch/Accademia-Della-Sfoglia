# Pari merito: si fa uno spareggio — cosa comporta

**Deciso da Ennio, 26/08/2026:** a parità di punteggio, le pari merito **fanno un'ulteriore
sfida.**

**Non era fra le tre opzioni che avevo proposto, ed è migliore di tutte e tre:** una gara si
risolve con una gara, non con una regola scritta piccola in fondo. E per una comunità è pure
un momento in più da vivere, invece di una delusione da spiegare.

**Ma non è una riga.** Sotto c'è cosa comporta, cosa si può fare subito, e le tre cose che
deve decidere ancora Ennio.

---

## La buona notizia: il pezzo difficile esiste già

Una gara riservata a due persone **è già nel plugin**. `voting.php:99-124`:

```php
function gs_sfida_blindata( $sfida_id ) { … }
function gs_sfida_whitelist( $sfida_id ) { … }
function gs_set_ammissione( $sfida_id, $user_id, $stato ) { … }
```

**Uno spareggio è una sfida blindata con dentro solo le pari merito.** Non c'è nessun
concetto nuovo da inventare: c'è da collegare quello che c'è.

---

## Ma una cosa cambia, e va detta chiaro

**Oggi la chiusura di una sfida è completamente automatica**: il cron vede la data passata,
assegna 100/60/30, chiude. **Con lo spareggio non può più esserlo**, e non per un limite
tecnico:

**Uno spareggio è un evento.** Va annunciato, ha una durata che qualcuno decide, e le due
sfogline devono sapere che sta succedendo. **Un sito non può aprire una gara fra due persone
senza dirglielo.**

Quindi il disegno giusto è: **il cron si accorge della parità e si ferma lì; il resto lo fa
Ennio con un clic.**

### Come funzionerebbe

1. **Il cron chiude la sfida** e scrive il marcatore (già corretto con V1);
2. **assegna i premi delle posizioni non contese** — se la parità è per il primo posto, il
   terzo posto è comunque suo e lo prende subito;
3. **mette da parte i premi contesi** e scrive in Posta interna:

   > **Sfida «Le tagliatelle di settembre» chiusa — parità per il 1° posto**
   > Anna Rossi e Maria Bianchi hanno la stessa media (17,50 su cinque voti ciascuna).
   > I 100 e i 60 punti sono in attesa.
   > **[Apri uno spareggio fra loro]**

4. **Ennio clicca**, sceglie titolo e durata, e il sistema crea **una sfida blindata** con
   dentro solo quelle due;
5. **quando lo spareggio chiude**, i premi messi da parte vanno a chi ha vinto.

---

## Le tre cose che deve decidere Ennio

Non sono dettagli: **sono i casi che capitano, e se non sono decisi prima li decide chi
scrive il codice.**

### 1. E se lo spareggio finisce di nuovo in parità?

- **si rifà** — pulito ma può non finire mai;
- **decide la giuria/Ennio** — sempre risolutivo, ma è una decisione umana da spiegare;
- **a quel punto pari merito vero**, entrambe prime, nessuna seconda.

**Io direi la terza:** lo spareggio è già la seconda occasione, e una terza gara fra le
stesse due persone stanca tutti. **Ma è una scelta di regolamento.**

### 2. E se una delle due non partecipa allo spareggio?

- **vince chi partecipa** — la più semplice, e mi sembra giusta: chi si presenta vince;
- **si torna alla classifica di prima** e decide un altro criterio.

**Io direi la prima**, e va scritta nel messaggio che ricevono: *«se una delle due non
partecipa entro il [data], vince l'altra.»* **Detto prima, non dopo.**

### 3. Lo spareggio dà punti suoi, oltre al premio messo da parte?

Una sfida normale dà 20 punti per aver pubblicato e punti per le stelle ricevute.
**Nello spareggio quei punti si sommano ai 100 del premio.** Va bene? O lo spareggio è solo
per decidere, senza punti propri?

**Io li lascerei**: hanno pubblicato una sfoglia in più davvero, ed è giusto che valga come
le altre.

---

# ⚠️ Quello che va fatto SUBITO, che è poco e va nello zip di adesso

**Il resto può aspettare. Questo no.**

Finché la parità non viene nemmeno **notata**, alla prima che capita il sistema assegna
**100 punti a una delle due, scelta dall'ordinamento** — e nessuno se ne accorge, perché non
c'è nessun messaggio, nessun avviso, niente.

**Il minimo indispensabile è accorgersene e fermarsi:**

```php
	// Pari merito: Ennio ha deciso (26/08/2026) che si risolve con uno
	// spareggio — una gara si risolve con una gara. Lo spareggio va aperto da
	// lui, perché è un evento da annunciare, non una cosa che un sito fa da
	// solo fra due persone senza dirglielo. Qui il cron fa solo la parte che
	// può fare da solo: assegna le posizioni NON contese, e mette da parte le
	// altre invece di darle a caso.
	$contese = array();   // posizioni con più di una sfoglia a pari media
	…
	if ( $contese ) {
		update_post_meta( $sfida_id, 'gs_premi_in_attesa', $contese );
		gs_inbox_crea(
			'Parità in «' . get_the_title( $sfida_id ) . '»: serve uno spareggio',
			'…i nomi, la media, e quali premi restano in attesa…',
			array()
		);
	}
```

**Così, anche prima che lo spareggio esista, non succede mai la cosa peggiore:** che i 100
punti vadano a caso e nessuno lo sappia.

**Vale per tutte e tre le gare** — Sfide, Giuria a Turno, Sfoglia Misurata — perché il pari
merito è lo stesso in tutte e tre (V2, G3, e quello di `sfoglia-misurata.php`).

**Compromesso da dichiarare:** finché lo spareggio non è costruito, in caso di parità **i
premi contesi restano fermi finché Ennio non decide a mano.** È scomodo, ed è molto meglio
di assegnarli a caso.

---

## E una cosa sul tempismo, per lo zip di adesso

**M1 — il gemello di G1 in `sfoglia-misurata.php:158`** — è arrivata dopo che le correzioni
erano già scritte. **È una riga spostata**, identica a G1 che è già fatta.

**Se lo zip non è ancora chiuso, mettila dentro.** Se è già chiuso, va benissimo lo stesso —
ma allora **non lasciarla in coda a lungo**: è l'unica cosa che separa quel file dall'essere
uguale al suo gemello già corretto, e le voci che restano sole si perdono.
