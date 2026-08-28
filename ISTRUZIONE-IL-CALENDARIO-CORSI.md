# Istruzione: il Calendario Corsi è operativo?

**Per Claude Code Ennio — 28/08/2026, scritta su 3.299.1**

Ennio ha chiesto se il Calendario Corsi è operativo. **Dal codice sì, ed è completo**:
`calendario.php` (1918 righe) è incluso nel plugin, il pannello «📅 Calendario Corsi» è
agganciato in tutti e tre i posti (Plancia `admin.php:235`, Pannello di Controllo
`control-panel.php:75`, Torre di controllo `pannello-nuovo.php:63`), e ci sono diciotto handler
AJAX che coprono l'intero giro: prenotare, disdire, acconti e saldi, lista d'attesa, blocco di
un corso con avviso a tutti, messaggi al cliente, offerta di una data alternativa, attestato.

**Ma «operativo» non lo decide il codice: lo decidono tre dati che stanno nel sito**, e nessuno
dei tre dà errore se manca. Questo documento serve a guardarli, in un quarto d'ora, prima su
guru2 e poi in produzione.

---

## PARTE 1 — I tre controlli

### 1. L'IBAN — è quello che rompe tutto senza dirlo

`gs_cal_settings()` (`calendario.php:32`) nasce con **IBAN vuoto**. La mail di prenotazione
(`calendario.php:478`) lo stampa così:

```php
if ( $cfg['iban'] ) { $corpo .= "• IBAN: " . $cfg['iban'] . "\n"; }
```

Beneficiario e causale invece si stampano **sempre**. Quindi con l'IBAN vuoto il cliente riceve
una mail intitolata «COME CONFERMARE IL POSTO — BONIFICO», con l'importo dell'acconto, il
beneficiario e la causale, e **senza il numero su cui fare il bonifico**. Non è un errore: è una
mail che sembra completa e non lo è. Chi la riceve scrive per chiedere l'IBAN, o non paga.

Controllo, dal pannello del Calendario o da riga di comando:

```bash
wp eval 'print_r( gs_cal_settings() );'
```

Devono essere pieni e giusti: **`iban`**, `beneficiario`, `causale`, `istruzioni`, e
`giorni_disdetta` (14 di default — è il numero scritto nelle condizioni della mail, quindi deve
essere quello vero).

### 2. Almeno una data pubblicata

```bash
wp eval 'foreach ( get_posts( array( "post_type" => "gs_corso_cal", "post_status" => "any", "posts_per_page" => -1 ) ) as $p ) { echo $p->ID . "  " . get_post_meta( $p->ID, "gs_data", true ) . "  posti:" . get_post_meta( $p->ID, "gs_posti", true ) . "  quota:" . get_post_meta( $p->ID, "gs_prezzo", true ) . "  acconto:" . get_post_meta( $p->ID, "gs_acconto", true ) . "  " . $p->post_title . "\n"; }'
```

Guarda tre cose: che ci sia **almeno una data futura**, che abbia **posti > 0**, e che
**acconto e quota** siano quelli veri. Un corso con acconto a 0 manda una mail che chiede un
bonifico di zero euro.

### 3. Una pagina che porti lo shortcode

Il pubblico prenota da `[gs_calendario]` (ci sono anche `[gs_calendario_griglia]` e
`[gs_calendario_ruota]`, due modi diversi di mostrarlo). Se nessuna pagina lo contiene, il
calendario esiste solo nel pannello.

```bash
wp post list --post_type=page --fields=ID,post_title,post_status --format=table
wp eval 'foreach ( get_posts( array( "post_type" => "page", "posts_per_page" => -1 ) ) as $p ) { if ( false !== strpos( $p->post_content, "gs_calendario" ) || false !== strpos( $p->post_content, "gs_corsi_pulsanti" ) ) { echo $p->ID . "  " . $p->post_status . "  " . $p->post_title . "\n"; } }'
```

La pagina dev'essere **pubblicata**, non bozza.

### Poi la prova vera, nel browser

Su guru2, con un account di prova che **non** sia uno dei sette veri: apri la pagina del
calendario, prenota un posto, e **leggi la mail che arriva** (in Local si vede da Mailhog, o
dal registro delle mail del plugin se c'è). Nella mail devono esserci, in ordine: la data del
corso, l'importo dell'acconto, il beneficiario, **l'IBAN**, la causale, e il numero di giorni
per la disdetta. Se manca una di queste sei righe, fermati e scrivi quale.

Poi, dal pannello, registra l'acconto e controlla che il posto risulti confermato e i posti
rimasti scendano di uno.

---

## PARTE 2 — Una cosa da chiedere a Ennio, non da correggere

**La causale dell'acconto del corso e la causale dei token sono la stessa frase**, parola per
parola:

- `calendario.php:37` (acconto del corso): *«Contributo a sostegno dell'Associazione Accademia
  della Sfoglia»*
- `token.php`, `esperti.php`, `spiegazioni.php` (acquisto token): *«CONTRIBUTO A SOSTEGNO
  DELL'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA»*

Sull'estratto conto **arrivano identici**. Chi riconcilia i pagamenti a mano — e in questo
plugin si fa tutto a mano — non ha modo di distinguere l'acconto di un corso da una ricarica di
token, se non dall'importo, che può coincidere. Il giorno che due bonifici arrivano lo stesso
giorno, uno dei due viene accreditato sulla cosa sbagliata.

**Non cambiarlo di tua iniziativa: la causale è un dato contabile.** Chiedi a Ennio:

> *«La causale del bonifico per l'acconto di un corso è identica a quella dei token
> ("Contributo a sostegno dell'Associazione…"). Sull'estratto conto non si distinguono. Vuoi
> che quella dei corsi diventi diversa — per esempio "Acconto corso del GG/MM/AAAA — Nome
> Cognome" — o la lasciamo com'è perché il commercialista la vuole così?»*

Se dice di cambiarla, il valore si modifica **dal pannello** (è un'impostazione, non codice):
il default in `calendario.php:37` si tocca solo se vuole che parta già giusta sui siti nuovi.

---

## Cosa riferire

Cinque righe:

1. `iban`, `beneficiario`, `causale`, `giorni_disdetta`: pieni e giusti, sì o no.
2. Quante date future ci sono, e se hanno posti, quota e acconto sensati.
3. Quale pagina porta `[gs_calendario]`, e se è pubblicata.
4. La mail di prova: c'erano tutte e sei le righe?
5. La risposta di Ennio sulla causale.

## Le cose da non fare

- **Non toccare prenotazioni vere** per provare: crea una data di prova e prenota con un
  account di prova. `gs_prenotazione` contiene acconti e saldi versati davvero — è la ragione
  per cui il Reset la tiene.
- **Non cambiare la causale** senza la risposta di Ennio.
- **Non scrivere l'IBAN in questo repository, né in un commit, né in un documento.** Va messo
  nel pannello del sito e basta: qui dentro si scrive «l'IBAN è configurato», non qual è.
