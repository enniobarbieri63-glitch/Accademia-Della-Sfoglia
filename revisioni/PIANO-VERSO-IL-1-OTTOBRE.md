# Il 1° ottobre cambia il piano — e c'è una cosa da decidere entro il 1° settembre

Ennio ha detto due cose nuove (22/08/2026): **il gaming parte il 1° ottobre**, la data si
può spostare, e **non si salta niente**.

La prima crea un problema immediato che nessuno aveva visto, perché nessuno sapeva della
data. Le altre due cambiano l'ordine del lavoro.

---

# PARTE 1 — Il 1° settembre rifà il danno di luglio

## Cosa succede fra nove giorni

Il 1° settembre il cron chiude **agosto**: `$mese_scorso` vale `2026-08`, l'opzione vale
`2026-07`, quindi la chiusura parte. Ogni sfoglina riceve **due messaggi**:

> «A agosto 2026 hai totalizzato N punti nel gioco del mese. Non hai raggiunto la soglia
> di 2500 punti per il Buono Sfoglia: mancavano X punti.»

**Ma il gioco non è ancora partito.** Se le sfogline non sanno di stare giocando — e il 1°
ottobre è la data in cui glielo si dice — quel messaggio è esattamente il messaggio di
luglio: un resoconto di una gara che, per loro, non è mai cominciata.

**Stesso identico problema, per una ragione diversa.** A luglio il mese non esisteva nel
codice; ad agosto e settembre esiste nel codice ma non nella testa delle sfogline.

E si ripete: il 1° ottobre chiuderebbe **settembre**, che è ancora pre-lancio. Il primo
resoconto sensato è quello di **ottobre, che parte il 1° novembre**.

## La correzione: dire al plugin quando comincia il gioco

Non seminare a mano l'opzione mese per mese — funziona finché la data non si sposta, e
Ennio ha detto che potrebbe spostarsi. Meglio dichiarare la data una volta sola.

In `includes/buono-sfoglia.php`:

```php
/**
 * Primo mese in cui il gioco mensile conta davvero (formato "AAAA-MM").
 * Prima di questo mese la chiusura non fa niente: i punti si accumulano già
 * (il sistema è in funzione da agosto 2026), ma le sfogline non sanno ancora
 * di stare giocando — il gioco viene presentato il 1° ottobre 2026 — e un
 * resoconto di una gara che per loro non è cominciata è esattamente il
 * messaggio sbagliato che è già partito per luglio.
 *
 * Se Ennio sposta la data di partenza, si cambia SOLO questa riga.
 */
function gs_buono_sfoglia_primo_mese() {
	return '2026-10';
}
```

e in `gs_buono_sfoglia_controlla_chiusura_mese()`, subito dopo il calcolo di
`$mese_scorso` e **prima** di ogni altra cosa:

```php
	// Non chiudere i mesi precedenti alla partenza ufficiale del gioco.
	if ( $mese_scorso < gs_buono_sfoglia_primo_mese() ) {
		update_option( 'gs_buono_sfoglia_mese_chiuso', $mese_scorso );
		return;
	}
```

Il confronto fra stringhe `"2026-08" < "2026-10"` funziona perché il formato è
`AAAA-MM`: si ordina da solo.

Scrivere comunque l'opzione serve a far avanzare lo stato mese per mese, così quando
arriva novembre la chiusura di ottobre parte normalmente.

## Cosa NON fare

**Non disattivare il cron** e non commentare l'aggancio: si dimentica di riaccenderlo, ed
è il modo in cui il 1° novembre non chiuderebbe niente.

**Non spegnere il conteggio dei punti.** I punti devono continuare ad accumularsi: agosto e
settembre servono da collaudo vero, con dati veri. È solo il *resoconto* che non deve
partire.

## Da decidere tu, Ennio

C'è una domanda che il codice non può decidere: **le sfogline che hanno già accumulato
punti ad agosto e settembre, li tengono?**

- **Sì** → i punti di ottobre partono da zero comunque (il conteggio mensile si azzera da
  solo), quindi non cambia niente. È la scelta naturale.
- **Se invece qualcuna arrivasse a 2500 a ottobre grazie a un'abitudine presa a settembre**,
  è un vantaggio meritato: ha partecipato prima degli altri.

**Non c'è niente da fare**, ma vale la pena averci pensato invece di scoprirlo dopo.

---

# PARTE 2 — Cosa vuol dire «non saltiamo nulla»

## Dove siamo davvero

L'analisi consegnata copre i sei punti del briefing: 36 voci, tutte con file, riga,
correzione e compromesso. **Ma era parziale in un modo che avevo dichiarato e che ora
conta di più**, perché prima non c'era una data:

- **incroci meccanici su tutti e 100 i moduli** — endpoint AJAX, cron, chiavi di permesso,
  funzioni, opzioni, classi CSS: completo;
- **lettura riga per riga su circa 25 moduli** — quelli citati nelle voci.

I controlli meccanici trovano quello che **manca**. Non trovano i **ragionamenti sbagliati**:
A2 (il doppio clic che brucia due livelli), E7 (le risposte indirizzate per posizione),
B5 (la salita di livello che consegna due sconti) sono usciti tutti **leggendo**, non
incrociando.

## Il buco più grosso

**`calendario.php`, 1.803 righe: il file più grande del plugin.** Il briefing lo nominava
esplicitamente fra i moduli che muovono soldi — *«acconti e saldi»* — e ne ho letto solo
gli endpoint AJAX, forse un quarto. È lì dentro che è saltata fuori la variabile non
definita (E1) leggendo poche decine di righe.

Non letti per intero, in ordine di quanto pesano davvero:

| Modulo | Righe | Perché conta |
|---|---|---|
| `calendario.php` | 1.803 | acconti, saldi, prenotazioni, attestati |
| `esperti.php` | 709 | consulenze a token — crediti comprati |
| `artigiani.php` | 853 | abbonamenti dei partner, a pagamento |
| `scuole-cucina.php` | 852 | idem |
| `area-pro.php` | 778 | corsi online, diplomi |
| `percorsi-lezioni.php` | 1.229 | badge e punti automatici |
| `lezioni-video.php` | 929 | punti, promemoria, assegnazioni |
| `sfogline-extra.php` | 1.140 | cestino, blackout, ricerca |

Tre grossi che **non rileggerei**: `control-panel.php`, `admin.php`, `pannello-nuovo.php`.
Sono impalcatura di disegno, gli incroci meccanici hanno già coperto i modi in cui si
rompono davvero (funzioni mancanti, chiavi non registrate), e **stai per riprogettarli**.

## Il piano che propongo

Cinque fasi. La data si sposta se serve, ma l'ordine no.

**Fase 1 — i giri 3, 4, 5, 6.** Già specificati, li esegue Claude Code. Non si fermano.

**Fase 2 — la seconda lettura, e comincio adesso.** Non compete con la Fase 1: quella è
lavoro di Claude Code, questa è mio. Consegno a blocchi — prima i moduli che muovono
soldi — così non aspetti la fine per avere le prime voci.

**Fase 3 — correggere quello che la Fase 2 trova**, con lo stesso metodo: un giro per volta,
una prova per correzione, niente installazioni al buio.

**Fase 4 — il pannello nuovo.** Dopo la Fase 2, non prima: così nasce sapendo delle cinque
chiavi di permesso non registrate e dei tre interruttori morti, invece di ereditarli.

**Fase 5 — la prova generale.** Una settimana prima del lancio, su Local, con dati veri
copiati: far girare una chiusura di mese completa, una scadenza, una registrazione, una
prenotazione con acconto. Non «funziona», ma **guardarlo funzionare**.

## Perché la Fase 2 va fatta adesso e non dopo

Il difetto di luglio è costato sei messaggi sbagliati. Non perché fosse grave, ma perché
è stato **trovato tardi**, quando era già in produzione.

Con una data mobile e la richiesta di non saltare niente, la cosa più preziosa è **sapere
tutto prima di decidere cosa correggere**. Se `calendario.php` nasconde qualcosa di grosso,
è meglio scoprirlo adesso — quando spostare il 1° ottobre costa una frase — che a fine
settembre, quando costa una figuraccia.

---

# Cosa serve da te, in ordine

1. **Entro pochi giorni: approvi il blocco della chiusura di agosto e settembre?** È
   l'unica cosa con una scadenza vera. Senza, il 1° settembre partono i resoconti.
2. **Confermi che parto con la Fase 2?** Da `calendario.php`, e ti consegno a blocchi.
3. Nel frattempo Claude Code continua con il Giro 3, che è indipendente da tutto questo.
