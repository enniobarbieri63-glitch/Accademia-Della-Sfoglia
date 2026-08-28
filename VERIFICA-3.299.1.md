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
