# Verifica di 3.288.0

**Due file cambiati. La correzione è giusta, e l'ho riprodotta su un banco di prova diverso dal loro.**

---

## Le due modifiche

**`conversazioni.php:798-812`** — il contrassegno è scritto e salvato **prima** di `gs_token_movimento()`:

```php
$msgs[ $trovato ]['rimborsato'] = true;
update_post_meta( $cid, 'gs_msgs', $msgs );
$nuovo = gs_token_movimento( $sfoglina, (int) $m['token_costo'], 'rimborso', '…' );
```

**`token.php:393-410`** — nel cron, la scrittura è entrata **dentro** il ciclo, una per messaggio, prima del movimento e prima dell'email. La variabile `$cambiato` e la scrittura finale sono sparite; **nessun residuo nel file** (controllato).

Il commento spiega la causa, non solo il rimedio, e cita il precedente: *«la stessa causa già descritta in buono-sfoglia.php»*. È il modo giusto di lasciare la lezione a chi legge fra un anno.

---

## La prova, rifatta da me

Loro hanno simulato la morte del processo con dati veri sul loro sito. Io ho rifatto la stessa prova con un banco diverso — il finto WordPress, la funzione del cron estratta **dal pacchetto consegnato**, e un'eccezione lanciata dentro l'invio dell'email dopo il secondo rimborso. Tre domande scadute nella stessa conversazione.

**Non l'ho fatto perché dubitassi del loro risultato**, ma perché due strumenti diversi che dicono la stessa cosa valgono più di uno solo — e perché il difetto riguarda i soldi.

```
=== 3.287.0 (prima) ===
  ☠ processo ucciso dal limite di posta
  token già usciti: 2 — messaggi segnati «rimborsato»: 0
  ✗✗ 2 usciti, 0 segnati: il resto uscirà di nuovo

  Il giorno dopo il cron riparte:
  nuovi rimborsi: 3 — token restituiti in tutto: 5
  ✗✗ 5 token per 3 domande: due sfogline pagate due volte

=== 3.288.0 (dopo) ===
  ☠ processo ucciso dal limite di posta
  token già usciti: 2 — messaggi segnati «rimborsato»: 2
  ✓ il registro corrisponde ai soldi usciti

  Il giorno dopo il cron riparte:
  nuovi rimborsi: 1 — token restituiti in tutto: 3
  ✓ tre domande, tre token. Nessun doppio rimborso.
```

**Cinque token per tre domande prima, tre dopo.** Il risultato coincide con il loro, ottenuto per un'altra strada.

La prova è in `revisioni/prove/test_cron_token.php`, e si rilancia con `php test_cron_token.php <cartella>`.

## La batteria sul pacchetto

```
96 file — 0 errori di sintassi
1361 funzioni definite — 0 chiamate e non definite — 0 doppie
342 azioni AJAX ↔ 289 dal JavaScript — 0 pulsanti morti, 0 gestori rotti
```

Identico a 3.287.0: il riordino non ha portato via niente. Versione coerente fra intestazione e `GS_VERSION`.

---

## Il giro dei difetti sui punti è chiuso

Con questo pacchetto tutto quello che avevo segnato «adesso» è fatto e verificato:

| | |
|---|---|
| Punti infiniti del piatto | ✅ provato (60 → 20) |
| Due custodi per un piatto | ✅ lucchetto + `try/finally` |
| Tetto a Diario e Consigli | ✅ provato (90 → 15) |
| Voti e proposte che sparivano | ✅ lucchetto condiviso + cache |
| Madrina & Allieva, punti infiniti | ✅ provato (50 → 10) |
| Token rimborsato due volte (insieme) | ✅ lucchetto + cache |
| Token rimborsato due volte (interruzione) | ✅ provato (5 → 3) |
| Newsletter tolta | ✅ zero orfani |

**Restano solo voci senza scadenza**: l'Indovinello e le missioni (stessa famiglia, danno piccolo), la streak, il Premio di Fine Anno che non registra chi ha vinto, «Il Tuo Anno» che mescola due contatori, e il mio errore sul nastro. Più la voce **L** — i 31 controlli d'accesso deboli — che non è senza scadenza ma va fatta insieme al cancello dei trenta giorni, perché è la stessa mano di lavoro.

**Da qui in poi la coda giusta è una sola: i trenta giorni.**
