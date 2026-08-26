# Istruzione: «guarda e commenta» dopo i trenta giorni

**Per Claude Code Ennio 2 — 26/08/2026, scritta su 3.290.0**

Ennio ha deciso: alla scadenza dei trenta giorni la sfoglina che non ha versato il contributo **non viene più chiusa fuori**. Vede le pagine del gaming, **può commentare sotto le sfoglie delle altre**, e non può fare nient'altro — niente punti, niente pubblicazioni, niente voti.

**Non fatelo adesso.** Prima vengono le email di scadenza, gli undici testi e la mail di benvenuto: quelle hanno la scadenza di settembre, questa no. Ma **leggete adesso il punto 1**, perché è un buco da chiudere in ogni caso, e il punto 6, perché cambia il testo di email che state per scrivere.

---

## Le eccezioni, dette prima

Mi sono accorto tre volte in due giorni di darvi una regola universale e di dimenticare un'eccezione che avevo già riconosciuto altrove. Quindi questa volta le scrivo **in cima, prima delle istruzioni**, e sono la parte più importante del documento.

**Restano fuori da tutto quello che segue, e non vanno toccate:**

| | perché |
|---|---|
| `aiuto.php` (2 punti) | è la porta da cui chiede di rientrare |
| `calendario.php` (1) | chi ha pagato un corso lo frequenta comunque |
| `biografia.php` (4) | la Vetrina si paga a parte, 49 euro |
| `artigiani.php` / `scuole-cucina.php` — i due `_cestina` (2) | roba dei partner da 490 euro |
| `letture.php` | usa `gs_lettore_puo_commentare()`, già giusto |
| **`artigiani.php:432` e `scuole-cucina.php:437`** | **i pannelli dei partner: vanno RIPORTATI a `gs_login_notice()`** — vedi `CANCELLO-DUE-PORTE-DI-TROPPO.md` |

Se durante il lavoro trovate un punto che non è in questo elenco e che vi sembra un'eccezione, **fermatevi e chiedete** invece di decidere: probabilmente ho dimenticato di nuovo qualcosa.

---

## 1. Il buco da chiudere comunque — undici handler

`gs_puo_partecipare()` è collegata a 27 handler, ma le azioni che scrivono sono una quarantina. **Undici controllano ancora solo `gs_is_approved()`** — e una congelata è ancora approvata, perché il congelamento non le toglie l'approvazione.

Oggi il danno è contenuto: le pagine sono chiuse, quindi ci si arriva solo da una scheda del browser rimasta aperta. **Ma due di quegli undici spendono un token**, e i token si comprano con un bonifico.

**È un errore mio.** Vi avevo detto «31 hanno il controllo debole, 7 hanno già quello giusto». Era vero quando l'ho scritto. Poi è nato il congelamento e ha cambiato cosa vuol dire «giusto», e non sono tornato indietro a dirlo.

Su tutti e undici, la sostituzione canonica — **sempre identica**:

```php
// prima
if ( ! $user_id || ! gs_is_approved( $user_id ) ) { wp_send_json_error( … ); }
// dopo
if ( ! $user_id || ! gs_puo_partecipare( $user_id ) ) {
    wp_send_json_error( array( 'message' => gs_msg_non_partecipa() ) );
}
```

```
voting.php:216       gs_ajax_invia_sfoglia
voting.php:297       gs_ajax_vota
voting.php:418       gs_ajax_sfoglia_commento      ← vedi il punto 4: qui è diverso
sondaggi.php:211     gs_ajax_sondaggio_vota
sondaggi.php:282     gs_ajax_sondaggio_proponi
forms.php:15         gs_ajax_aggiungi_diario
forms.php:98         gs_ajax_aggiungi_consiglio
esperti.php:365      gs_ajax_esperto_domanda
esperti.php:431      gs_ajax_esperto_domanda_privata    ← spende un token
conversazioni.php:646 gs_ajax_conv_sfoglina_richiesta   ← spende un token
compleanni.php:150   gs_ajax_augurio_invia
```

Attenzione ai nomi delle variabili: in alcuni file è `$user_id`, in altri `$uid`. **Non fate una sostituzione in blocco su tutto il file** — è quello che vi ha morso con la ricorsione. Un punto alla volta, e alla fine `prova.sh`, che adesso il controllo della ricorsione ce l'ha.

## 2. Il messaggio, in un posto solo

In `helpers.php`, accanto a `gs_puo_partecipare()`:

```php
/**
 * Cosa si dice a chi non può partecipare. In una funzione sola perché
 * compare in una quarantina di punti e Ennio vorrà cambiarne le parole
 * senza che qualcuno debba ritoccarne quaranta.
 */
function gs_msg_non_partecipa() {
    $importo = gs_settings()['registration']['importo_quota'] ?? '29,00';
    return 'Per partecipare serve il contributo di ' . $importo . ' € a sostegno dell\'Accademia. '
         . 'Puoi continuare a guardare e a commentare.';
}
```

Poi sostituite con `gs_msg_non_partecipa()` il testo generico `'Il tuo account non può fare questa cosa adesso.'` in tutti i punti dove compare. **Quella frase oggi non dice niente a chi la legge**; questa dice cosa manca e cosa si può ancora fare.

## 3. L'interruttore, e il cancello che diventa a tre risposte

**L'interruttore sta nelle impostazioni**, perché Ennio deve poter provare e tornare indietro senza chiamare nessuno. In `helpers.php`, fra i valori predefiniti:

```php
'congelata' => array(
    'guarda' => 0,   // 0 = porta chiusa (com'è adesso), 1 = guarda e commenta
),
```

e una spunta nel pannello «Abbonamenti delle sfogline», con la frase che spiega cosa fa: *«Chi non ha versato il contributo continua a vedere le pagine del gaming e può commentare, ma non può partecipare né guadagnare punti.»*

Poi il cancello. Oggi ha due risposte — passa o non passa. Gliene serve una terza:

```php
// shortcodes.php — gs_gate_riservato()
function gs_gate_riservato() {
    if ( ! is_user_logged_in() ) {
        return gs_login_notice();
    }
    if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( get_current_user_id() ) ) {
        // Terza risposta: con la modalità "guarda" accesa la pagina si apre
        // lo stesso. Non serve altro qui — le azioni continuano a rifiutarla
        // perché gs_puo_partecipare() non cambia, e i punti non le arrivano
        // perché il cancello in gs_add_points() non cambia. La fascia in cima
        // alla pagina la mette gs_sez_filtra_shortcode(), vedi sotto.
        if ( function_exists( 'gs_congelata_puo_guardare' ) && gs_congelata_puo_guardare() ) {
            return '';
        }
        return function_exists( 'gs_congelata_avviso' ) ? gs_congelata_avviso() : '';
    }
    return '';
}
```

con:

```php
/** True se la modalità "guarda e commenta" è accesa nelle impostazioni. */
function gs_congelata_puo_guardare() {
    $s = gs_settings();
    return ! empty( $s['congelata']['guarda'] );
}
```

**Non toccate `gs_puo_partecipare()` e non toccate il cancello dei punti.** Sono già esattamente quello che serve: è tutto il senso di questa modifica.

## 4. La fascia in cima, senza toccare venti pagine

La pagina si apre, ma deve dire **subito** in che stato è. Non mettetela a mano in venti posti: **c'è già un punto solo da cui passano tutti gli shortcode delle sezioni** — `gs_sez_filtra_shortcode()` in `sezioni.php:196`, agganciata a `do_shortcode_tag`, che conosce già la mappa shortcode → sezione.

```php
// sezioni.php — dentro gs_sez_filtra_shortcode(), prima del "return $output" finale
// Chi sta guardando senza poter giocare lo legge una volta per pagina, in
// cima alla prima sezione che incontra. Qui e non nei singoli shortcode:
// questo filtro è già il passaggio obbligato di tutte le sezioni, e una
// fascia sola è meglio di venti copie da tenere allineate.
static $gia_detto = false;
if ( ! $gia_detto && isset( $map[ $tag ] )
     && function_exists( 'gs_congelata_puo_guardare' ) && gs_congelata_puo_guardare()
     && function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( get_current_user_id() ) ) {
    $gia_detto = true;
    return gs_congelata_fascia() . $output;
}
```

e la fascia — **diversa dall'avviso della porta chiusa**, che dice un'altra cosa:

```php
function gs_congelata_fascia() {
    $importo = gs_settings()['registration']['importo_quota'] ?? '29,00';
    return '<div class="gs-box gs-notice gs-box-guarda">'
        . '<p><strong>Stai guardando.</strong> Il tuo mese di prova è finito, ma non ti abbiamo chiusa fuori: '
        . 'puoi vedere tutto e commentare sotto le sfoglie delle altre. Quello che non puoi fare, per ora, '
        . 'è partecipare — pubblicare, votare, guadagnare punti.</p>'
        . '<p><strong>Niente di tuo è andato perso</strong>: punti, badge, percorso, ricette e foto sono '
        . 'salvati dove li hai lasciati, e tornano tuoi il giorno in cui rientri.</p>'
        . '<p>Per rientrare serve un contributo di <strong>' . esc_html( $importo ) . ' €</strong> a sostegno '
        . 'dell\'Accademia. Trovi le istruzioni nell\'email che ti abbiamo mandato, oppure scrivici dal '
        . 'riquadro «Aiuto e Suggerimenti».</p>'
        . '</div>';
}
```

Il `static $gia_detto` serve a non ripeterla tre volte su una pagina che monta tre shortcode.

## 5. Il commento resta aperto — è l'unica eccezione nuova

`voting.php:418`, `gs_ajax_sfoglia_commento()`: **NON mettete `gs_puo_partecipare()`.** Lasciate `gs_is_approved()`, con il commento che dice perché:

```php
// Il commento resta aperto anche a chi è congelata (Ennio, 26/08/2026):
// una che commenta è una che torna, e chi resta nella conversazione rientra
// molto più facilmente di chi ha trovato la porta chiusa. I punti però non
// le arrivano: ci pensa il cancello in gs_add_points(), qui non serve nulla.
if ( ! $user_id || ! gs_is_approved( $user_id ) ) { … }
```

**Ma il messaggio di risposta va corretto.** Oggi dice «+5 punti»: a una congelata non ne arrivano, e una promessa scritta che non si avvera è peggio del limite stesso. È lo stesso caso del Diario. Il testo giusto è qualcosa come *«Commento pubblicato. I punti tornano quando rientri.»*

Verificate anche cosa succede alla missione: il commento fa scattare `gs_commento_sfoglia`, che avanza la missione «vota 3 e commentane una». **Non è un problema** — la missione non può completarsi perché il voto è chiuso — ma controllate che non le venga mostrato un avanzamento che non porta da nessuna parte.

## 6. Le email cambiano — e per questo va deciso adesso

Con la modalità accesa, **due testi già scritti diventano falsi**:

- `gs_congelata_avviso()` (`abbonamenti.php:80`) dice *«Per riaprire questa parte del sito»* e elenca solo le pagine pubbliche. Con la modalità accesa non viene nemmeno più mostrato — al suo posto c'è la fascia. Lasciatelo dov'è: serve ancora se Ennio spegne l'interruttore.
- **Le email di scadenza che state per scrivere.** Con la porta chiusa dicono «l'accesso cessa». Con questa modalità devono dire *«puoi continuare a guardare e a commentare, ma non a partecipare»*. **Sono due lettere diverse.**

Il modo per non scriverle due volte: **scrivetele una volta sola, con la frase che cambia presa da una funzione** — la stessa `gs_congelata_puo_guardare()` decide quale delle due frasi entra nel corpo. Così l'interruttore di Ennio cambia anche le email, e non resta un testo che dice il contrario di quello che il sito fa.

---

## Come si prova

Su guru2, con una sfoglina finta e la data di scadenza a ieri:

**Con l'interruttore spento** — tutto deve comportarsi come oggi: pagine chiuse, avviso del mese finito.

**Con l'interruttore acceso:**
- le pagine del gaming si aprono, con la fascia in cima **una volta sola** anche su una pagina con più sezioni;
- il commento sotto una sfoglia **si pubblica**, e il messaggio non promette punti;
- i punti dopo il commento sono **gli stessi di prima**;
- votare, pubblicare, il diario, i consigli, i sondaggi: tutti rifiutano, e il messaggio dice il contributo e cosa si può ancora fare;
- «Aiuto e Suggerimenti» funziona;
- una domanda privata all'esperto **non spende il token**;
- il pannello dei partner si apre per un artigiano in regola, anche se come socio è congelato.

E alla fine `prova.sh`, che adesso ha anche il controllo della ricorsione.

---

## Cosa non fare

- **Non toccare `sezioni.php:141`.** Quel controllo (`'superiore'` + abbonamento scaduto a mano) è un'altra cosa, precedente, e continua a servire. Il congelamento vive accanto, non al suo posto.
- **Non nascondere i pulsanti**, per ora. Ennio ha scelto di lasciarli visibili con il messaggio giusto: una pagina che si vede racconta ogni giorno cosa manca, una porta chiusa non racconta niente. Se poi darà fastidio, si nasconderanno dopo, una pagina per volta — ma è un lavoro di venti pagine e non va fatto a scatola chiusa.
- **Non fatelo prima delle email di scadenza, degli undici testi e della mail di benvenuto.** Solo il punto 1 va fatto subito, perché è un buco vero.
