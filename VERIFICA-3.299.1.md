# Verifica di gaming-sfogline 3.299.1 — la quota associativa

**28/08/2026.** Confronto `3.298.2` contro `3.299.1`: la 3.299.0 non è mai passata da qui,
quindi ho verificato **le due versioni insieme**, sul pacchetto intero.

## Cosa è cambiato

Tredici file. **`reset.php` e `gaming.js` non sono fra questi**: il Reset, chiuso ieri, non è
stato toccato da questo lavoro.

## La cosa che poteva rompere tutto, e non è rotta

Quando si toglie un campo obbligatorio da un modulo, il modo di sbagliare è togliere il campo e
lasciare il controllo — o il contrario: nessuno riesce più a iscriversi, e non c'è nessun errore
da nessuna parte, solo un messaggio che chiede di confermare una cosa che a schermo non esiste.

**Sono spariti insieme**, l'ho verificato censendo e non rileggendo: `name="quota"` non compare
in nessun file del plugin, e il controllo `if ( ! $quota )` con il suo messaggio
«Devi confermare l'adesione alla quota associativa» è stato tolto da `registration.php`.
Nessun campo orfano, nessun controllo orfano.

## I quattro punti di 3.299.1

Confermati tutti e quattro (`admin.php`, `control-panel.php`, `pannello-nuovo.php` ×2), e il
censimento su «verifica … quota» in tutto il plugin non trova più niente. `php -l` pulito sui
file toccati, versione 3.299.1 nei tre punti, changelog che racconta cosa avrebbe letto chi
approvava un'iscrizione.

## Quello che il censimento fa emergere, e che la ricerca sulla frase non poteva vedere

3.299.1 ha cercato «verifica … quota». Ma la **quota associativa continua a esistere come
concetto in sette punti** del plugin, cinque dei quali si leggono a schermo — e dicono a chi
legge che l'iscrizione una quota ce l'ha:

| dove | cosa dice |
|---|---|
| `letture.php:166` e `spiegazioni.php:352` | la stessa frase in due file: l'account leggero è «**senza quota associativa**» |
| `faq.php:232` | «gratuito e immediato, **senza quota associativa**» |
| `abbonamenti.php:103` e `token.php:274` | la stessa frase in due file: versamenti «**oltre alla quota associativa**» |
| `letture.php:21`, `token.php:7` | due commenti nel codice, stessa cosa |

È la stessa contraddizione che 3.299.0 voleva togliere, sopravvissuta con parole diverse. E
**due delle quattro frasi esistono in doppia copia in file diversi** — la trappola che ha già
prodotto la 3.299.1.

Le correzioni, con il testo esatto e i censimenti per provarle, sono in
`ISTRUZIONE-LA-QUOTA-CHE-RESTA.md`.

## Una cosa da chiedere a Ennio, non da correggere

`pagina-supporter.php` (righe 349 e 511) parla di una «quota di adesione come Supporter» di
**29,00 € all'anno** — lo stesso importo del contributo gaming dopo la prova. O sono due cose
distinte che costano uguale, o sono la stessa cosa chiamata in due modi, e allora una delle due
pagine dice la cosa sbagliata a chi paga. La domanda è scritta nell'istruzione; la risposta è
di Ennio.

## Da non toccare

La chiave `importo_quota` (`helpers.php:159`) resta com'è: è il nome interno di un'opzione già
salvata: rinominarla perderebbe il valore. L'etichetta mostrata è già stata corretta.

---

# Seguito: 3.299.2 e 3.299.3 — verificate

**28/08/2026.** La 3.299.2 non è passata da qui, quindi ho verificato le due insieme contro
3.299.1. Otto file cambiati.

## 3.299.2 — la quota nei testi

**Corrette tutte e sei le copie**, comprese le due coppie gemelle che erano la trappola di
questo lavoro (`letture.php` + `spiegazioni.php`, `abbonamenti.php` + `token.php`): nessuna è
rimasta indietro. Le frasi nuove non nominano più una quota che non c'è —
«(dà accesso solo ai commenti delle Letture, non al resto del percorso)» e «versamenti
volontari, separati da tutto il resto».

Censimento rifatto da capo: **«quota associativa» non compare più da nessuna parte** nel
codice. Restano tre occorrenze della sola parola «quota», tutte legittime: il prezzo di un
corso nel calendario e nel generatore di locandine, e «a quota 50» come soglia di un badge.

**La pagina Supporter è rimasta intatta**, ed è la cosa giusta: la domanda sui due importi da
29 € è nel changelog e aspetta la risposta di Ennio, non è stata decisa strada facendo.

## 3.299.3 — «📄 Scarica PDF» nel pannello delle mail

Codice nuovo, quindi l'ho letto come si legge il codice nuovo:

- **Il permesso c'è** (`current_user_can( 'manage_options' )`) **e il nonce anche**
  (`check_admin_referer`), su una pagina che si apre da un indirizzo — cioè il posto dove è
  più facile dimenticarli.
- La chiave arriva da `sanitize_key()` e viene cercata nel registro delle mail, con `wp_die()`
  se non esiste: non si può farsi stampare qualcosa che non sia una di quelle mail.
- Il corpo esce senza `esc_html()`, ed è **giusto così**: è l'HTML del modello, lo stesso che
  `wp_mail()` manda davvero, e chi può vederlo è la stessa persona che può modificarlo.
- L'utente finto (`display_name` = «Prova», niente `->ID`) è gestito: `gs_mail_template_render()`
  controlla `isset( $user->ID )` prima di leggere i meta, e per le date e il link di conferma
  usa valori finti ma realistici. Nessun avviso PHP, nessun segnaposto vuoto.

**Ho controllato la cosa che avrebbe reso la funzione inutile**: la pagina stampa l'oggetto da
`$def['oggetto']` mentre il corpo lo prende da `gs_mail_template_corpo_attivo()`, cioè da
quello eventualmente modificato. Se gli oggetti fossero modificabili, si stamperebbe un oggetto
diverso da quello spedito — e si controllerebbe una mail che non esiste. **Non lo sono**: il
pannello mostra l'oggetto in sola lettura e `wp_mail()` spedisce lo stesso `$def['oggetto']`.
Quindi oggi è corretto.

> **Da ricordare se un giorno si rendono modificabili gli oggetti delle mail**: va aggiornata
> anche questa pagina, o inizierà a stampare l'oggetto sbagliato in silenzio.

`php -l` pulito sui sei file toccati. Versione 3.299.3 nei tre punti, changelog che racconta
cosa leggeva una persona.

**Niente da correggere.** Resta aperta la sola domanda dei due importi da 29 €.
