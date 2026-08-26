# «Non tediamo gli iscritti» — cosa arriva davvero nella casella di una sfoglina

**Ennio, 26/08/2026.** Ho contato invece di dare un parere.

**Prima la buona notizia: il plugin è già sobrio.** Poi i tre punti dove non lo è — **e uno
dei tre è la sequenza che ti ho proposto io ieri.**

---

# 1 · In una settimana normale arriva **una** email

Una sfoglina che non fa niente di particolare riceve, in sette giorni:

| | Quante | Note |
|---|---|---|
| **Digest settimanale** — «Le novità della settimana» | **1** | va a tutte |
| Tutto il resto | **0** | arriva solo se la riguarda |

**Una email a settimana. È poco, ed è giusto così.**

## E due scelte già prese bene, che vanno riconosciute

**I compleanni non sono email.** `gs_bday_annuncio_giornaliero()` avvisa **tutte** le
sfogline dei compleanni del giorno — ma con un **aeroplanino**, non con un messaggio in
casella. Se fossero email, con cento iscritte sarebbero **quasi una al giorno per tutte, per
sempre.** Qualcuno ci ha pensato.

**Il promemoria giornaliero è a scelta sua.** *«Non hai ancora fatto la tua mossa di oggi»*
suona come il classico messaggio che stufa — ma `promemoria.php` dice che **è la sfoglina ad
attivarlo, e a scegliere l'ora.** E parte solo se quel giorno non si è collegata.

**Questi due sono già il modo giusto**, ed è utile averli come metro per tutto il resto.

---

# 2 · I primi trenta giorni sono l'eccezione — e in parte è colpa mia

Nel documento di ieri ho proposto una sequenza per la prova: benvenuto, avviso a 7 giorni
dalla fine, avviso l'ultimo giorno, accesso chiuso, riattivazione. **Cinque email.**

**Mettile insieme al resto e il conto del primo mese è questo:**

| | Quante |
|---|---|
| Verifica dell'email (già esiste) | 1 |
| La mia sequenza della prova | 5 |
| Digest settimanale × 4 settimane | 4 |
| Chiusura del mese (da ottobre) | 1 |
| **Totale nel primo mese** | **11** |

**Undici email in trenta giorni a una persona che si è appena iscritta e non ha ancora
capito bene dove si trova.** È esattamente quello che non vuoi, e la sequenza più pesante
l'ho scritta io.

## La sequenza che propongo adesso: da cinque a tre

| Quando | Manca? | Perché |
|---|---|---|
| **Il giorno dell'approvazione** | ✅ **tiene** | è il benvenuto, e dice le date |
| ~~7 giorni prima della fine~~ | ❌ **tolta** | l'ha già letta nel benvenuto |
| **3 giorni prima della fine** | ✅ **tiene** | è l'unico promemoria, e arriva quando serve decidere |
| ~~l'ultimo giorno~~ | ❌ **tolta** | due avvisi a tre giorni di distanza sono un sollecito, non un servizio |
| **Il giorno dopo la chiusura** | ✅ **tiene** | *«è tutto salvato e ti aspetta»* — è la più importante delle tre |
| **Riattivazione** | ✅ tiene | ma è causata da lei: non conta come disturbo |

**Tre email in trenta giorni**, più il benvenuto della riattivazione se decide di continuare.
**Otto in tutto nel primo mese invece di undici.**

**E i due avvisi tolti non spariscono: diventano aeroplanini.** Quando entra nel sito, vede
*«mancano 7 giorni»* nel posto dove sta già guardando — **senza che le suoni il telefono.**

---

# 3 · Il punto peggiore: chi è congelata continua a ricevere il digest

`gs_digest_settimanale()` manda a **tutte** le sfogline (`gs_sez_sfogline()`), **senza
guardare lo stato dell'abbonamento.**

Quindi una sfoglina congelata riceve, **ogni settimana e per sempre**, un elenco di ricette
nuove, lezioni nuove e corsi in arrivo — **che non può aprire.**

**È la stessa famiglia dei tre difetti di ieri** (streak, resoconto del mese, promemoria
lezioni): cose che continuano a lavorarle intorno mentre è fuori. **Ma questa è la peggiore
delle quattro**, perché le altre tre succedono una volta, questa **ogni lunedì**.

**Va nella stessa correzione:** il `continue` per le congelate, dentro `gs_digest_settimanale()`.

**Compromesso da dichiarare, e va deciso:** un digest è anche **il modo di far tornare
qualcuno**. Se lo togli del tutto a chi è congelata, non le ricordi più che l'Accademia
esiste.

**Io farei così:** niente digest settimanale, ma **una email sola, dopo un mese di
congelamento**, del tipo *«sono passate quattro settimane: quello che avevi è sempre lì»*.
**Una, non ventisei all'anno.**

---

# 4 · La cosa che manca davvero: non può spegnere niente da sola

Le preferenze per categoria **esistono già** (`notifiche-pref.php`): digest, promemoria,
livelli, iscrizione… ognuna attivabile per email o per messaggio interno, **per singola
sfoglina.**

**Ma il pulsante per cambiarle è di là dalla scrivania.** `gs_ajax_notifiche_pref_salva()`,
riga 123:

```php
	if ( ! gs_can_manage() ) {
```

**Solo tu e i collaboratori potete cambiarle. La sfoglina no.**

**Questa è la risposta più diretta a «non tediamo gli iscritti»:** il modo per non tediare
qualcuno non è indovinare quante email tollera — **è lasciargli il pulsante.**

E c'è una ragione in più: una email che parte da sola e ripetutamente **deve avere un modo
di dire basta**. Il digest è quel tipo di email.

## Cosa serve

**Il meccanismo c'è già tutto** — categorie, preferenze, salvataggio. Manca solo un riquadro
in «La Mia Sfoglia», fra gli strumenti, che mostri le stesse caselle **con lei come
destinataria di se stessa**:

> **Cosa vuoi ricevere per email**
> ☑ Le novità della settimana
> ☑ Quando qualcuno ti scrive
> ☐ I promemoria giornalieri
> *Il resto lo trovi comunque nel sito, negli aeroplanini e nella tua posta.*

**E una riga in fondo a ogni email che parte da sola:** *«Puoi scegliere cosa ricevere dalla
tua pagina La Mia Sfoglia»*, con il collegamento.

**Non su tutte:** una risposta a una sua domanda o la conferma di un pagamento non sono cose
da cui ci si disiscrive. **Solo su quelle ricorrenti** — digest e promemoria.

---

# 5 · La regola, in una riga

Da usare come metro quando si aggiunge qualcosa:

> **Un'email solo se: la riguarda personalmente, ed è successo qualcosa che deve sapere anche
> se non entra nel sito per una settimana.**
>
> **Tutto il resto è un aeroplanino.**

Provata sulle mail di oggi:

| | Email? | Perché |
|---|---|---|
| «La tua ricetta è stata approvata» | **sì** | la riguarda, e le fa piacere saperlo subito |
| «Il tuo corso è domani» | **sì** | se non entra, lo perde |
| «Fra 3 giorni si chiude l'accesso» | **sì** | c'è una decisione da prendere |
| «Le novità della settimana» | **sì, ma spegnibile** | è utile, ma è la sola ricorrente |
| «Hai completato un percorso» | **aeroplanino** | è bello, ma lo vede entrando |
| «I compleanni di oggi» | **aeroplanino** | ✅ già così |
| «Il tuo scudo ha salvato la streak» | **aeroplanino** | ✅ già così |
| «Non hai fatto la mossa di oggi» | **solo se l'ha chiesto** | ✅ già così |

---

# Riepilogo

| | |
|---|---|
| Settimana normale, oggi | **1 email** — è già sobrio |
| Compleanni e scudi | ✅ già aeroplanini, non email |
| Promemoria giornaliero | ✅ già a scelta sua |
| **Primo mese con la mia sequenza** | 🔴 **11 → la riduco a 8** |
| **Digest a chi è congelata** | 🔴 **ogni lunedì, per sempre** — un `continue`, più una email sola dopo un mese |
| **Non può spegnere niente da sola** | 🔴 **il meccanismo c'è, manca il riquadro** |
| La regola per il futuro | email solo se la riguarda **e** se perderla è un danno |

**Le prime due sono correzioni. La terza è la più importante**, perché è l'unica che non
dipende da quanto bene indoviniamo noi.
