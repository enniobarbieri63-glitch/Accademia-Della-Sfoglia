# Verifica di 3.291.0 e 3.292.0

**Due pacchetti, tutti e due puliti.**

---

## 3.292.0 — gli undici handler ✅

Tutti e undici convertiti da `gs_is_approved()` a `gs_puo_partecipare()`, con il commento che spiega da dove veniva il buco (*«era rimasto con il solo controllo vecchio da prima che esistesse il congelamento»*).

**E la prova che conta di più: chi è rimasto fuori.** Ho rifatto il conto sul pacchetto consegnato — gli handler che ancora controllano solo `gs_is_approved()` sono **nove**, e sono **esattamente le nove eccezioni volute**, nessuna in più, nessuna in meno:

```
aiuto.php ×2          calendario.php ×1
biografia.php ×4      artigiani/scuole _cestina ×2
```

Le due che spendono un token — `esperto_domanda_privata` e `conv_sfoglina_richiesta` — sono chiuse.

### La batteria

```
97 file — 0 errori di sintassi
1366 funzioni — 0 chiamate e non definite — 0 doppie
342 azioni AJAX ↔ 289 dal JavaScript — 0 pulsanti morti, 0 gestori rotti
funzioni che chiamano se stesse per sbaglio: 0
```

## 3.291.0 — i link di condivisione ✅

```php
$link_mail = 'mailto:?subject=' . rawurlencode( $v['titolo'] ) . '&body=' . rawurlencode( $corpo_condiviso );
$link_wa   = 'https://api.whatsapp.com/send?text=' . rawurlencode( $corpo_condiviso );
… esc_url( $link_mail ) … esc_url( $link_wa ) … rel="noopener"
```

`rawurlencode()` sul contenuto e `esc_url()` sull'indirizzo: la cosa che vi avevo chiesto di controllare era già giusta. E il commento sul perché del separatore ` — ` invece dell'a-capo è il tipo di nota che salva mezza giornata a chi passa di lì fra un anno.

Una sola cosa, piccola: se una spiegazione diventa molto lunga, il link `mailto:` può superare il limite di lunghezza di alcuni programmi di posta e arrivare tagliato. Non è un difetto oggi — dipende da quanto lunghe le scriverà Ennio. Se un giorno una spiegazione supera le duemila lettere, è lì che va guardato.

---

## Due cose da ricordare, nessuna delle due urgente

**1. Il commento è stato chiuso, e per oggi è giusto.** `voting.php:418` è passato a `gs_puo_partecipare()` come gli altri dieci. Con la porta chiusa è la scelta corretta: una congelata non deve arrivarci comunque.

**Ma quando farete «guarda e commenta», quello va riaperto** — è l'unica eccezione nuova di quella modalità (punto 5 dell'istruzione). **La confusione è colpa mia**: nell'elenco degli undici avevo scritto «vedi il punto 4» quando l'eccezione sta al punto 5. Rinvio sbagliato, mio.

**2. `gs_msg_non_partecipa()` non è stata aggiunta** (punto 2 dell'istruzione). I quaranta handler ripetono la frase generica *«Il tuo account non può fare questa cosa adesso.»*, che a chi la legge non dice né cosa manca né cosa può ancora fare.

Non è un difetto e non rompe niente. Ma quando Ennio vorrà cambiare quella frase — e la vorrà cambiare, perché è la frase che una sfoglina legge nel momento in cui vorrebbe partecipare — saranno quaranta punti invece di uno. **Il momento buono per accorparla è adesso che li avete appena toccati tutti**, non fra tre mesi.

---

## Quello che resta aperto dai documenti precedenti

| | dove | |
|---|---|---|
| **I due pannelli dei partner** ancora chiusi a un artigiano che è anche socio | `artigiani.php:432`, `scuole-cucina.php:437` | ⬜ non ancora corretto |
| Le email di scadenza | | ⬜ **settembre** |
| Il giro sui quattordici agganci del cron | | ⬜ **settembre** |
| Gli undici testi «prima paghi, poi entri» | | ⬜ **settembre** |
| La mail di benvenuto finale | | ⬜ **settembre** |
| **Il reset** — non esiste nessuna funzione | | ⬜ **prima delle iscrizioni** |
| **I dati di accesso in rete** — `helpers.php:854` mette ancora `user_login` nell'indirizzo della Vetrina | | ⬜ **prima delle iscrizioni** |
