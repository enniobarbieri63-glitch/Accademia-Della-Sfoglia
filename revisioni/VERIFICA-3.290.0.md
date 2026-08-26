# Verifica di 3.290.0

**39 file cambiati, uno nuovo. Il cancello dei trenta giorni è fatto bene. Resta la cosa che vi ho segnalato, ed è confermata.**

---

## ⚠️ Le due porte dei partner sono chiuse davvero

Il documento che vi ho mandato è arrivato dopo che avevate già costruito il pacchetto, quindi era prevedibile. Lo confermo sul codice consegnato:

```php
artigiani.php:432      function gs_sc_art_pannello() {
                           if ( $g = gs_gate_riservato() ) { return $g; }

scuole-cucina.php:437  function gs_sc_scu_pannello() {
                           if ( $g = gs_gate_riservato() ) { return $g; }
```

Un artigiano o una scuola che è **anche** socio viene chiuso fuori dalla vetrina pagata **490 euro** il giorno in cui finisce la prova del gaming, con l'abbonamento da partner in corso — e legge un messaggio che gli parla di 29 euro e di gaming, che con la sua vetrina non c'entrano.

La correzione e il motivo stanno in `CANCELLO-DUE-PORTE-DI-TROPPO.md`. In breve: rimettete `if ( ! is_user_logged_in() ) { return gs_login_notice(); }` in quei due punti, **con il commento che spiega perché restano diversi**, o fra sei mesi qualcuno li «uniforma» di nuovo in buona fede. Il controllo che serve davvero c'è già una riga sotto: `gs_art_owner_post()` / `gs_scu_owner_post()`.

**È l'unica cosa che ho trovato.** Tutto il resto è a posto.

---

## Il cancello: verificato punto per punto

### La funzione è pulita — niente ricorsione

```php
shortcodes.php:41-49
function gs_gate_riservato() {
    if ( ! is_user_logged_in() ) { return gs_login_notice(); }
    if ( function_exists( 'gs_sfoglina_congelata' ) && gs_sfoglina_congelata( get_current_user_id() ) ) {
        return function_exists( 'gs_congelata_avviso' ) ? gs_congelata_avviso() : '';
    }
    return '';
}
```

Nessuna traccia del guasto dello script. E il doppio `function_exists()` è una cautela in più rispetto a come l'avevo scritta io: se per un ordine di caricamento sbagliato mancasse `gs_congelata_avviso()`, il cancello lascia passare invece di andare in errore fatale. Verso giusto per una funzione che sta su ogni pagina riservata.

### Le quattro eccezioni sono esatte

27 handler AJAX passano da `gs_puo_partecipare()`. Un solo controllo debole rimasto, ed è dove doveva restare. Le eccezioni non sono state «saltate»: sono state scritte con il controllo giusto — `gs_is_approved()` da solo, senza il congelamento — **e con il commento che dice perché**:

| | | |
|---|---|---|
| `aiuto.php:145,153,393` | la porta da cui una congelata chiede di rientrare | ✅ |
| `calendario.php:553,562` | chi ha pagato un corso lo frequenta comunque | ✅ |
| `biografia.php:247,256…` | la Vetrina si paga a parte, 49 euro | ✅ |
| `letture.php:73,286` | usa `gs_lettore_puo_commentare()`, era già giusto | ✅ non toccato |

**«Aiuto e Suggerimenti» resta raggiungibile da congelata.** Era la cosa che mi premeva di più adesso che «La Mia Sfoglia» si chiude: è l'unico posto dentro il sito dove una sfoglina fuori può ancora scrivervi. Senza quello, il congelamento sarebbe una stanza senza maniglia.

### La batteria

```
97 file — 0 errori di sintassi
1366 funzioni definite — 0 chiamate e non definite — 0 doppie
342 azioni AJAX ↔ 289 dal JavaScript — 0 pulsanti morti, 0 gestori rotti
```

---

## La prova che avevo promesso, e la prova della prova

Ho aggiunto alla batteria il controllo che il vostro quasi-incidente ha mostrato mancare: **una funzione che chiama se stessa**.

Non basta cercare il nome dentro il corpo, perché la ricorsione vera è legittima e in questo plugin ce n'è una. Il controllo distingue: segnala solo la chiamata a se stessa **senza argomenti**, che è la firma di una sostituzione andata storta — una ricorsione vera cambia sempre qualcosa a ogni giro, o non finirebbe mai.

**E l'ho provato rimettendo il guasto**, perché una prova che non ho mai visto fallire non è una prova. Ho ricreato nel file esattamente la riga che vi aveva morso:

```
=== il file rotto passa il controllo di sintassi? ===
No syntax errors detected in shortcodes.php          ← php -l non ha niente da ridire

=== e la prova nuova? ===
✗✗ includes/shortcodes.php:42  gs_gate_riservato() chiama gs_gate_riservato()
   senza argomenti (definita a riga 41)
```

È in `revisioni/prove/test_ricorsione.php` ed è già dentro `prova.sh`, come quarto controllo. **Dopo una sostituzione in blocco è la rete che serve**, e costa un secondo.

Vi rimando la cartella `prove` aggiornata.

---

## Quello che non ho visto

**I due link di condivisione — email e WhatsApp — non sono in questo pacchetto.** In `spiegazioni.php` (527 righe) non c'è né `mailto:` né `wa.me`: li avete aggiunti dopo aver costruito lo zip. Quindi su quelli non ho verificato niente, e il difetto di `esc_url()` che avete descritto resta un vostro risultato, non una cosa che io possa confermare.

Quando arriveranno in un pacchetto li guardo. L'unica cosa che vi chiedo di controllare intanto, perché è il punto delicato di quel genere di link: **il testo della spiegazione finisce dentro un URL**, quindi va passato da `rawurlencode()` prima di entrare nel link, non solo da `esc_url()` sull'insieme. Sono due cose diverse — la prima rende il testo trasportabile, la seconda ripulisce l'indirizzo — e se manca la prima, una spiegazione che contiene una `&` o un `#` arriva troncata.

---

## Dove siamo con i trenta giorni

| | |
|---|---|
| `gs_sfoglina_congelata()` — il nuovo stato | ✅ |
| I trenta giorni scritti all'approvazione, una volta sola | ✅ |
| Il cancello dei punti in `gs_add_points()` | ✅ |
| Il cancello delle pagine (25 punti + dashboard) | ✅ **meno le due porte dei partner** |
| Il cancello dell'AJAX — voce L (27 handler + 4 eccezioni) | ✅ |
| Il pannello «Abbonamenti» riscritto | ✅ |
| I quattordici agganci del cron giornaliero | ⬜ |
| Le email di scadenza riscritte | ⬜ |
| Gli undici testi «prima paghi, poi entri» | ⬜ |
| La mail di benvenuto finale | ⬜ |

**Metà fatta, e la metà difficile è quella fatta.** Quello che resta è lavoro di testo e un giro di controllo sul cron: nessuna scoperta di architettura, nessuna sorpresa attesa.

Una nota sull'ordine, adesso che la dashboard si chiude: **le email di scadenza sono diventate la cosa più importante di quelle che restano.** Prima erano un avviso; adesso sono l'unico posto dove una sfoglina congelata può leggere come rientrare, perché la pagina dove stavano quelle istruzioni non si apre più. Se doveste fermarvi a metà del resto, fermatevi dopo quelle.
