# Extra · Il contatore della comunità nello Stato Generale

Ennio l'ha chiesto esplicitamente, quindi **si fa** — ma come voce decisa apposta, non di
rimbalzo mentre si risponde a una domanda diagnostica.

**Prima esegui i due comandi per l'incidente** (il conteggio e i nomi delle sei), **poi**
questa. Non invertire: l'incidente ha una risposta che scade, questa no.

---

## Cosa aggiungere

Quattro numeri, non uno. Ognuno risponde a una domanda che Ennio si è già posto in questi
giorni e che finora nessun pannello sapeva rispondere:

| Numero | A cosa risponde |
|---|---|
| Sfogline attive in tutto | *quante sono davvero?* — la domanda aperta dal Giro 0 |
| Con la Vetrina accesa | *perché il Nastro sembra vuoto?* |
| Collegate negli ultimi 30 giorni | *la comunità è viva o è un elenco?* |
| Sopra i 2500 punti questo mese | *quante vinceranno il Buono alla chiusura?* |

L'ultimo è quello che oggi serve di più: **è l'anteprima della chiusura del 1° settembre.**
Se il 30 agosto quel numero è zero, Ennio sa in anticipo che la soglia è tarata troppo alta
— invece di scoprirlo dai messaggi che partono.

---

## Dove

`includes/stato-generale.php`, dentro `gs_pannello_stato_generale()`, nel blocco
`.gs-stato-principali`: **subito dopo la riga «sfogline online adesso»** e prima
dell'interruttore del Nastro. Stessa forma delle righe già lì
(`gs-stato-riga gs-stato-riga-grande`), così non introduce uno stile nuovo.

## Il codice

```php
	// Fotografia della comunità (richiesta da Ennio, 22/08/2026). Nasce da una
	// domanda rimasta senza risposta per due giri di correzioni: "quante
	// sfogline vere ci sono davvero?". Quattro numeri invece di uno perché
	// ognuno risponde a una domanda diversa già emersa: quante sono, quante
	// compaiono nel Nastro, quante sono ancora vive, e quante stanno per
	// vincere il Buono alla prossima chiusura.
	//
	// NON è messo in cache, di proposito: è un numero che si guarda per
	// decidere se agire — stessa regola della Torre di controllo — e il
	// pannello lo apre solo chi gestisce. Il costo è comunque quasi nullo:
	// gs_sez_sfogline() ha una cache per richiesta ed è già stata chiamata da
	// control-panel.php nella stessa pagina, e la get_users() che c'è dentro
	// ha già caricato in memoria i meta di tutte le sfogline — le letture qui
	// sotto non tornano sul database.
	$sfogline_tutte = function_exists( 'gs_sez_sfogline' ) ? gs_sez_sfogline() : array();
	$soglia_buono   = 2500;
	$limite_vive    = current_time( 'timestamp' ) - ( 30 * DAY_IN_SECONDS );
	$n_vetrina = 0;
	$n_vive    = 0;
	$n_soglia  = 0;

	foreach ( $sfogline_tutte as $u ) {
		if ( function_exists( 'gs_vetrina_disponibile' ) && gs_vetrina_disponibile( $u->ID ) ) {
			$n_vetrina++;
		}
		$ultimo = get_user_meta( $u->ID, 'gs_ultimo_accesso', true );
		if ( $ultimo && strtotime( $ultimo ) >= $limite_vive ) {
			$n_vive++;
		}
		if ( function_exists( 'gs_get_month_points' ) && gs_get_month_points( $u->ID ) >= $soglia_buono ) {
			$n_soglia++;
		}
	}

	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">👭 ' . count( $sfogline_tutte ) . ' sfogline attive in tutto</span></p>';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎗️ ' . (int) $n_vetrina . ' con la Vetrina accesa — sono quelle che compaiono nel Nastro</span></p>';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">📅 ' . (int) $n_vive . ' si sono collegate negli ultimi 30 giorni</span></p>';
	echo '<p class="gs-stato-riga gs-stato-riga-grande" style="opacity:.85"><span class="gs-stato-nome">🎁 ' . (int) $n_soglia . ' sopra i ' . (int) $soglia_buono . ' punti questo mese — vincono il Buono Sfoglia alla chiusura</span></p>';
```

---

## Perché stavolta la cache non ci va

Sembra in contraddizione con il Nastro, dove la cache l'abbiamo messa. Non lo è, ed è utile
tenere ferma la distinzione, perché tornerà:

- **Il Nastro** è una vetrina decorativa che gira su ogni pagina del sito, per ogni
  visitatore, migliaia di volte al giorno. Nessuno la guarda per decidere niente. → **cache.**
- **Questo contatore** lo apre chi gestisce, poche volte al giorno, e lo guarda **per
  decidere se agire** — se la soglia è tarata bene, se la comunità si sta spegnendo. Un
  numero vecchio di un quarto d'ora qui è peggio di nessun numero. → **niente cache.**

È la stessa regola della Torre di controllo, applicata al caso giusto invece che a
tappeto: *non congelare un dato che una persona guarda per decidere; congela senz'altro un
dato che un browser richiede da solo.*

---

## Verifiche

1. **`php -l includes/stato-generale.php`**.
2. **Apri il Pannello di Controllo** e controlla che le quattro righe compaiano sotto
   «sfogline online adesso», nello stesso stile delle altre.
3. **Controllo incrociato che vale più di tutti:** il numero *«con la Vetrina accesa»* deve
   corrispondere alle sfogline che si vedono davvero nel Nastro — togliendo Ennio Barbieri
   (voce fissa) e Mulino Marino (lo sponsor intercalato). Se non torna, uno dei due sbaglia
   e va capito quale prima di consegnare.
4. **Con Query Monitor, il costo:** apri il pannello prima e dopo la modifica e confronta il
   **totale query della pagina**. Deve essere praticamente **identico** — se sale in modo
   sensibile, la cache per richiesta di `gs_sez_sfogline()` non sta lavorando come previsto,
   e va capito perché prima di consegnare. È l'unica cosa che può andare storta qui.

---

## Cosa non fare

- **Niente pannello per «chi è stato toccato a luglio».** È un incidente, non
  un'informazione: fra una settimana non serve a nessuno. Quei nomi si leggono con il
  comando, una volta sola.
- **Non aggiungere altri numeri** perché «già che ci siamo». Questi quattro rispondono a
  domande vere e già poste; ogni altro sarebbe decorazione, e ogni riga in più è una riga da
  mantenere per sempre.
- **Non toccare la riga «sfogline online adesso»** che c'è già: funziona, ed è di un altro
  autore.

---

## Poi

Riporta i quattro numeri veri di produzione a Ennio — sono la risposta finale alla domanda
aperta dal Giro 0 — e **fermati lì**. Restano il rendiconto della chiusura (entro il
1° settembre) e i tre controlli del sito vero, poi si apre il Giro 3.
