# Istruzioni per Claude — Accademia della Sfoglia

Questo file riguarda **come si confeziona e si consegna il plugin**: lo zip da
caricare su WordPress, oppure il singolo file quando basta quello.

Non sostituisce il `CLAUDE.md` che sta **dentro** il plugin (citato in
`token.php`: «Nessun incasso online, vedi CLAUDE.md»). Quello contiene le
regole di prodotto ed è la fonte più autorevole: se i due si contraddicono,
vince quello del plugin. Questo file copre solo la consegna.

Il plugin **non è in questo deposito**. Qui ci sono la cartella `design/` e i
documenti di piano in `documenti/`. Il plugin arriva come zip caricato in
conversazione: vedi «Punto di partenza».

---

## Il plugin in due righe

`Gaming Sfogline` — plugin WordPress, nessuna dipendenza esterna, nessuna
tabella propria (solo Custom Post Type, user meta, shortcode, AJAX e WP-Cron).
Alla versione 3.321.30: **103 file PHP**, di cui 102 in `includes/` più il file
principale. Requisiti dichiarati: WordPress 5.8+, PHP 7.4+.

```
gaming-sfogline/
├── gaming-sfogline.php      ← file principale: intestazione, costanti, elenco moduli
├── readme.txt               ← versione dichiarata due volte + changelog
├── includes/                ← 102 moduli, uno per funzione
└── assets/
    ├── css/gaming.css
    ├── js/gaming.js
    └── img/                 ← loghi, sigilli, diplomi
```

## Punto di partenza di ogni sessione

Il plugin va caricato in conversazione come zip. Primo comando:

```bash
LAV="$SCRATCH/plugin"          # $SCRATCH = la cartella scratchpad della sessione
mkdir -p "$LAV" && cd "$LAV"
unzip -q /percorso/dello/zip/caricato.zip
PLUGIN="$LAV/gaming-sfogline"
```

Se una richiesta riguarda il codice e lo zip non è stato caricato, **chiedilo
prima di rispondere**: le supposizioni su questo plugin si sono già rivelate
sbagliate sei volte su sei (vedi `documenti/piano-percorsi-v3-*.html`, sezione 01).

---

## Le quattro regole che non si saltano

### 1. La versione sta in quattro posti, e vanno cambiati tutti insieme

```
gaming-sfogline.php:6     * Version:     3.321.30
gaming-sfogline.php:23    define( 'GS_VERSION', '3.321.30' );
readme.txt:7              Stable tag: 3.321.30
readme.txt:61             = 3.321.30 =            ← nuova voce di changelog in cima
```

Si incrementa **l'ultimo numero di uno a ogni consegna** (3.321.30 → 3.321.31).
È lo schema osservato nel changelog: una consegna, un numero.

### 2. `GS_VERSION` è anche la chiave anti-cache di CSS e JS

```php
wp_enqueue_style(  'gaming-sfogline', GS_URL . 'assets/css/gaming.css', array(), GS_VERSION );
wp_enqueue_script( 'gaming-sfogline', GS_URL . 'assets/js/gaming.js', array( 'jquery' ), GS_VERSION, true );
```

Conseguenza da non dimenticare: **se tocchi `gaming.css` o `gaming.js` e non
alzi la versione, i browser continuano a servire il file vecchio.** La modifica
è caricata sul server e invisibile sul sito, che è il modo più efficace di
perdere un pomeriggio.

### 3. Un modulo nuovo non si carica da solo

I moduli si caricano da un elenco esplicito in `gaming-sfogline.php`, non con
una scansione della cartella:

```php
$gs_modules = array( 'helpers.php', 'media-msg.php', /* … */ );
foreach ( $gs_modules as $gs_module ) {
    $gs_path = GS_INC . $gs_module;
    if ( file_exists( $gs_path ) ) { require_once $gs_path; }
}
```

Il `file_exists()` fa sì che un file dichiarato ma mancante **non dia errore**:
semplicemente non esiste. Quindi un modulo nuovo non aggiunto all'elenco è un
file caricato sul server che non fa niente, in silenzio. L'ordine dell'elenco è
l'ordine di caricamento: `helpers.php` sta per primo perché gli altri lo usano.

### 4. `php -l` su tutto, prima di consegnare

È una regola già scritta nel `readme.txt` del plugin: «Tutti i file PHP sono
verificati con `php -l` prima della consegna». In questo ambiente PHP c'è.

---

## Cosa consegnare: zip intero o file singolo

| Cosa hai toccato | Consegna |
|---|---|
| Uno o più moduli in `includes/`, niente altro | **I file singoli** + `gaming-sfogline.php` e `readme.txt` con la versione alzata |
| Un modulo **nuovo** | **Zip intero** — l'elenco moduli è cambiato e serve la cartella coerente |
| `gaming.css` o `gaming.js` | **I file** + `gaming-sfogline.php` con `GS_VERSION` alzata (vedi regola 2) |
| Immagini in `assets/img/` | **Zip intero** — più semplice che spiegare dove va ciascun file |
| Tre file o più | **Zip intero** — sotto i tre file il singolo è più veloce, sopra si sbaglia |

Il file singolo si carica via FTP o dall'editor di file del pannello di
WordPress, in `wp-content/plugins/gaming-sfogline/includes/`. Lo zip si installa
da **Plugin → Aggiungi nuovo → Carica plugin**, scegliendo «Sostituisci il file
corrente» quando WordPress lo chiede.

**Dopo ogni installazione da zip**: visitare una volta *Impostazioni →
Permalink* e salvare senza modificare niente. Serve a rigenerare i link della
Vetrina, ed è già scritto nelle istruzioni di installazione del plugin.

---

## Fare lo zip

Lo zip deve contenere **la cartella `gaming-sfogline/`**, non il suo contenuto:
è ciò che WordPress si aspetta. Si comprime da un livello sopra.

```bash
cd "$(dirname "$PLUGIN")"        # la cartella che CONTIENE gaming-sfogline/
VER=$(grep -oP "GS_VERSION', '\K[0-9.]+" gaming-sfogline/gaming-sfogline.php)
zip -r -q -X "gamingsfogline${VER}.zip" gaming-sfogline \
    -x "*.DS_Store" "*__MACOSX*" "*/.git/*"
unzip -l "gamingsfogline${VER}.zip" | tail -3
```

`-X` toglie gli attributi di sistema, l'esclusione toglie i file spazzatura di
macOS. Il nome `gamingsfogline3.321.30.zip` è la convenzione usata finora —
WordPress non lo legge, serve a voi per riconoscere la versione.

Riferimento: lo zip della 3.321.30 contiene **123 voci** (117 file + 6 cartelle)
per circa 6,4 MB, di cui buona parte sono le immagini in `assets/img/`.

---

## Controlli prima della consegna

Da eseguire sempre, tutti e quattro. Se uno fallisce, non si consegna.

```bash
cd "$PLUGIN"

# 1 — sintassi di tutti i file PHP
err=0
for f in $(find . -name "*.php"); do
  php -l "$f" >/dev/null 2>&1 || { echo "SINTASSI: $f"; php -l "$f"; err=1; }
done
[ $err -eq 0 ] && echo "OK sintassi: $(find . -name '*.php' | wc -l) file"

# 2 — la versione è la stessa in tutti e quattro i posti
a=$(grep -oP "^ \* Version:\s*\K[0-9.]+" gaming-sfogline.php)
b=$(grep -oP "GS_VERSION', '\K[0-9.]+"     gaming-sfogline.php)
c=$(grep -oP "^Stable tag:\s*\K[0-9.]+"    readme.txt)
d=$(grep -oPm1 "^= \K[0-9.]+"               readme.txt)
printf 'intestazione %s | costante %s | stable tag %s | changelog %s\n' "$a" "$b" "$c" "$d"
[ "$a" = "$b" ] && [ "$b" = "$c" ] && [ "$c" = "$d" ] \
  && echo "OK versione coerente" || echo "ERRORE: le quattro versioni non coincidono"

# 3 — elenco moduli allineato ai file presenti
python3 - <<'PY'
import re, os
s = open("gaming-sfogline.php", encoding="utf-8").read()
blk = re.search(r'\$gs_modules = array\((.*?)\n\);', s, re.S).group(1)
dic = re.findall(r"'([\w\-]+\.php)'", blk)
pre = sorted(os.listdir("includes"))
print("dichiarati:", len(dic), "| in includes/:", len(pre))
print("dichiarati ma assenti :", [m for m in dic if m not in pre] or "nessuno")
print("presenti ma non caricati:", [f for f in pre if f not in dic] or "nessuno")
PY

# 4 — nessun file spazzatura finito dentro
find . \( -name ".DS_Store" -o -name "*.orig" -o -name "*.rej" -o -name "*~" \) -print
```

Alla 3.321.30 i controlli davano: 103 file PHP validi, versione coerente nei
quattro posti, 102 dichiarati e 102 presenti, nessuno spaiato, nessun file
spazzatura. **Questo è lo stato di partenza corretto.**

---

## La voce di changelog

Va in cima al blocco `== Changelog ==` di `readme.txt`, sopra la voce
precedente. Il formato è fisso, lo stile pure — si scrive in italiano, per una
persona che deve capire cosa è cambiato sul suo sito, non per un programmatore:

```
= 3.321.31 =
* Nome del pannello o della sezione: cosa è cambiato e perché. Se la richiesta
  viene da Ennio, si riporta la sua frase fra virgolette. Se è un difetto, si
  dice la causa vera trovata, non il sintomo, e cosa è stato verificato.
```

Due esempi presi dal changelog vero, che mostrano il registro atteso:

> * Pannelli «🎈 Palloncini Sfogline» e «🛩️ Aeroplanini con striscione»:
>   aggiunto un pulsante «🔍 Anteprima» ad entrambi (Ennio: «voglio il pulsante
>   di anteprima»). Apre in una scheda nuova il risultato dei valori appena
>   scelti nel form — comprese le caselle spuntate — SENZA salvare nulla.

> * Palloncino Gigante: l'invio andava a buon fine ma sullo schermo non
>   succedeva nulla. Causa trovata dalla console del browser dal vivo: due
>   sezioni di gaming.js isolate in blocchi separati […] — stesso tipo di bug
>   già risolto altrove in questo file (v3.121.4), qui reintrodotto da una
>   sezione aggiunta dopo.

Una voce che dice «migliorie varie» non è una voce: fra sei mesi nessuno saprà
cosa è successo, ed è esattamente il momento in cui serve.

---

## Regole di prodotto da rispettare scrivendo codice

Non sono preferenze di stile: sono decisioni prese, con una data.

- **Mai «costo» o «prezzo» nei testi rivolti alle sfogline** — si dice sempre
  *contributo associativo* (Ennio, 30/07/2026).
- **Nessun incasso online.** Tutto per bonifico con causale, accreditato a mano
  dal pannello. Non è un ripiego: è la verifica antifrode e la traccia contabile.
- **Prefisso `gs_` per ogni funzione**, per compatibilità con la v1.0.
- **`gs_add_points()` non si tocca dall'interno.** È la porta unica dei punti
  (una definizione, 34 chiamate in 21 file) e ha già pagato due difetti veri:
  scritture perse per corsa critica (14/08/2026, risolto con UPDATE SQL atomico)
  e handler AJAX che restavano scrivibili a sfoglina congelata (26/08/2026).
  Ogni logica nuova va **prima** della chiamata, mai dentro.
- **Le tre porte del congelamento** vanno usate, non riscritte:
  `gs_gate_riservato()` per le pagine, `gs_puo_partecipare()` per le scritture
  AJAX, e il controllo dentro `gs_add_points()` per i punti. Tutte e tre leggono
  `gs_sfoglina_congelata()`.
- **Niente tabelle nuove nel database.** Custom Post Type, meta, opzioni.

---

## Al termine di ogni consegna

Elenca esplicitamente, in chat:

1. La versione nuova, e che è stata alzata in tutti e quattro i posti.
2. I file toccati, uno per uno.
3. Se serve lo zip intero o bastano i file singoli, con la ragione.
4. Se occorre risalvare i permalink (sempre, dopo un'installazione da zip).
5. L'esito dei quattro controlli.

E consegna il file con lo strumento di invio file, non solo il percorso: chi
riceve deve poterlo scaricare, non cercarlo.
