# GIRO 1 — istruzioni corrette, sostituiscono le precedenti

**Queste istruzioni annullano e sostituiscono la sezione GIRO 1 del documento principale.**
Se c'è una differenza, vale quello che è scritto qui.

**Non installare niente sul sito vero** finché non hai finito tutte le verifiche in fondo
e ricevuto il via libera.

---

## Cosa resta com'è: la parte 1a

**La memoria di 15 minuti che hai già applicato va bene. Non toccarla.**

Hai fatto bene anche ad aggiungere `'number' => 500` alla `get_users()`, e a controllare che
nel transient non finiscano oggetti `WP_User` ma solo testo.

Un'unica correzione al modo di riportare il risultato, non al codice: **«0 query al secondo
caricamento» non è esatto.** Rileggere un transient costa comunque una o due letture
(`_transient_…` e `_transient_timeout_…`, che non sono autocaricate). Hai quasi certamente
contato solo le query del nastro, che è la misura sensata — ma il numero da riportare è
quello **totale della pagina**: è quello che dice se il sito è più leggero davvero.
Rimisuralo e riportalo così.

---

## Cosa va rifatto: la parte 1b

### Perché

Le istruzioni che ti avevo dato erano sbagliate, ed è un errore mio, non tuo. Ti avevo
detto di agganciare il controllo a `get_option( 'gs_page_sfogline' )`. **La tua prova su
guru2 lo ha già smascherato**, solo che è stata letta come una conferma:

> su «Le Sfogline» (guru2): il nastro piccolo non c'è più, come previsto
>
> quella pagina di prova ha solo `[gs_sfogline]`, non lo shortcode del nastro grande

Messe insieme, quelle due frasi dicono che **quella pagina adesso non ha più nessun
nastro**: né il piccolo, che hai tolto, né il grande, che lì non c'è.

La causa: la pagina «Le Sfogline» del plugin (`gaming-sfogline.php:329`) nasce con dentro
solo `[gs_sfogline]`. **Il nastro grande ce lo ha incollato a mano Ennio**, e non è detto
che sia nella stessa pagina. Agganciandosi all'id si sbaglia due volte insieme:

- sulla pagina che ha davvero il nastro grande **il doppio calcolo resta** — 1b non
  correggerebbe niente;
- sulla pagina del plugin **sparisce il nastro piccolo senza che compaia nulla al suo
  posto** — quello che si vede su guru2.

### Cosa fare

In `gs_render_nastro_vetrine()`, **togli il blocco che avevi aggiunto**:

```php
	$pagina_sfogline = (int) get_option( 'gs_page_sfogline' );
	if ( $pagina_sfogline && is_page( $pagina_sfogline ) ) {
		return;
	}
```

e **mettine questo al suo posto**, nella stessa posizione — dopo il controllo sul blackout,
prima della riga `$voci = gs_nastro_raccogli_voci( ... );`:

```php
	// Dove c'è già il nastro grande, non montare anche il piccolo: si
	// sovrapporrebbero. Prima questo veniva evitato nascondendo il piccolo
	// col CSS (gaming.css:2174, body.page-id-64342), che però lo faceva
	// comunque calcolare — una scansione completa della tabella utenti
	// buttata via a ogni visita di quella pagina.
	// Il controllo guarda il CONTENUTO e non l'id della pagina: lo shortcode
	// del nastro grande è incollato a mano, quindi può stare su qualunque
	// pagina, e l'id cambia da un sito all'altro.
	if ( is_singular() ) {
		$post_corrente = get_post();
		if ( $post_corrente && has_shortcode( (string) $post_corrente->post_content, 'gs_nastro_grande_sfogline' ) ) {
			return;
		}
	}
```

`has_shortcode()` è una funzione di WordPress: non serve aggiungere altro.

### Perché questa versione è giusta

- **Funziona su qualsiasi sito** senza sapere gli id: guru2, Local, produzione.
- **Non spegne mai un nastro senza sostituirlo**: salta il piccolo solo dove il grande c'è
  davvero. Su guru2, dopo questa correzione, la pagina «Le Sfogline» **tornerà ad avere il
  nastro piccolo** — ed è giusto così, visto che lì il grande non c'è.
- **Non va più aggiornata** se Ennio sposta lo shortcode su un'altra pagina.

### Cosa NON fare

**Non togliere la riga di CSS** `body.page-id-64342 #gs-nastro-fisso { display: none !important; }`
(`assets/css/gaming.css:2174`). Diventa superflua, ma finché il controllo PHP non è stato
visto funzionare **in produzione** è l'unica rete rimasta. Si toglie a un giro successivo.

---

## Verifiche — tutte e cinque, prima di riportare

1. **`php -l includes/nastro-vetrine.php`** → nessun errore di sintassi.

2. **Su guru2, pagina «Le Sfogline»: il nastro piccolo deve essere TORNATO.**
   Lì il nastro grande non c'è, quindi il piccolo ci deve stare. Se resta assente, il
   controllo non funziona: fermati e capisci perché, non consegnare.

3. **La prova che finora mancava.** Crea su guru2 una pagina nuova con dentro
   `[gs_nastro_grande_sfogline]` e aprila. Lì devono valere **tutte e tre**:
   - il nastro **grande** c'è;
   - il nastro **piccolo** non c'è;
   - in Query Monitor, la scansione della tabella utenti compare **una volta sola**.

   Questa è la situazione vera della produzione, e su guru2 non era mai stata riprodotta.

4. **Home e un'altra pagina qualsiasi:** il nastro piccolo c'è e scorre. È l'errore più
   probabile del giro — spegnerlo dappertutto invece che solo dove serve.

5. **La memoria funziona:** carica una pagina, annota il **totale query della pagina**,
   ricarica: il secondo caricamento deve farne sensibilmente meno. Se il numero non cambia,
   il transient non sta lavorando.

---

## Poi fermati e riporta

Scrivi a Ennio l'esito delle cinque verifiche, con i numeri veri delle query (totale
pagina, non solo quelle del nastro), e **aspetta il via libera prima di installare**.

Un'ultima nota, perché non tragga conclusioni sbagliate dalle prove: le 128 pillole che hai
contato sulla home di guru2 corrispondono a **due file** di nastro. Vuol dire che guru2 è in
modalità «doppio», mentre il sito vero è a **corsia singola**. Le verifiche restano valide
— servono a dire *se* il nastro c'è, non *com'è fatto* — ma **guru2 non riproduce la
configurazione di produzione**, quindi non usarlo per giudicare l'aspetto del nastro.
