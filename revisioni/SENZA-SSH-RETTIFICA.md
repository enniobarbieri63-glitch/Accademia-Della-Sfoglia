# Niente SSH: i due numeri e la rettifica si fanno dal plugin

Hai ragione su tutto: nessun accesso a produzione, e **hai fatto benissimo a non usare la
copia Local del 17 agosto** per rispondere. Un numero vecchio di sei giorni spacciato per
attuale sarebbe stato peggio di nessun numero — è esattamente il tipo di scorciatoia che
manda fuori strada chi legge.

Ma i due comandi non servono. **Entrambe le risposte possono arrivare dal plugin stesso**,
e per una di esse il codice è già scritto.

---

## Domanda 2 — quante sfogline vere: la risolve il contatore

È esattamente il numero che il contatore dello Stato Generale mostrerà
(`EXTRA-CONTATORE-SFOGLINE.md`, prima riga: *«N sfogline attive in tutto»*). Appena Ennio
installa la versione con quel blocco, apre il pannello e legge il numero.

**Non serve nient'altro per la domanda 2.** Toglila dalla lista delle cose bloccate.

---

## Domanda 3 — la rettifica alle sei: una funzione usa-e-getta

Identificare i nomi per poi scrivere a mano a sei persone è la strada lunga. La strada
breve: **far raggiungere quelle sei dallo stesso meccanismo che le ha raggiunte la prima
volta**, una volta sola, e farsi dire da lui a chi è arrivata.

### Prima: Ennio deve approvare il testo

**Non scrivere questa funzione prima che Ennio abbia approvato le parole.** Sono sue
associate e il messaggio esce a suo nome. Bozza:

> **Oggetto:** Un messaggio arrivato per errore
>
> Ciao, nei giorni scorsi ti è arrivato un «resoconto di luglio» che diceva che avevi
> totalizzato 0 punti. Era un errore nostro: il gioco del mese è partito ad agosto, e luglio
> non è mai stato un mese di gara. Quel messaggio non riguardava niente di reale e non hai
> perso nessuna occasione.
>
> Il primo resoconto vero arriverà a inizio settembre e riguarderà agosto.
>
> Scusa la confusione — Accademia della Sfoglia

### Poi: il codice, in un file nuovo

Mettilo in un file suo — `includes/rettifica-luglio.php` — e non dentro `buono-sfoglia.php`:
è roba temporanea, e deve essere ovvio dove sta e cosa cancellare quando ha finito.
Aggiungi il file all'elenco dei moduli in `gaming-sfogline.php`.

```php
<?php
/**
 * rettifica-luglio.php — USA E GETTA, da cancellare dopo l'esecuzione.
 *
 * Il 22/08/2026, nella finestra fra la 3.272.0 e la 3.272.1, la chiusura
 * del mese (allora senza la protezione del primo avvio) ha chiuso luglio
 * 2026: sei sfogline hanno ricevuto un resoconto di un mese in cui il
 * gioco mensile non esisteva ancora. Nessun Buono è stato assegnato — i
 * punti di luglio erano zero per tutte — ma il messaggio era confuso.
 *
 * Questo file manda a quelle sei, e solo a loro, una rettifica. Gira una
 * volta sola e si spegne da solo.
 *
 * DA CANCELLARE (file + riga nell'elenco moduli) dopo aver verificato in
 * Posta interna che il resoconto sia arrivato.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'gs_daily_cron', 'gs_rettifica_luglio_2026' );
function gs_rettifica_luglio_2026() {
	if ( get_option( 'gs_rettifica_luglio_fatta' ) ) {
		return;
	}

	$oggetto = 'Un messaggio arrivato per errore';
	$corpo   = "Ciao,\n\n"
		. "nei giorni scorsi ti è arrivato un «resoconto di luglio» che diceva che avevi totalizzato 0 punti. "
		. "Era un errore nostro: il gioco del mese è partito ad agosto, e luglio non è mai stato un mese di gara. "
		. "Quel messaggio non riguardava niente di reale e non hai perso nessuna occasione.\n\n"
		. "Il primo resoconto vero arriverà a inizio settembre e riguarderà agosto.\n\n"
		. "Scusa la confusione — Accademia della Sfoglia";

	$raggiunte = array();

	foreach ( get_users( array( 'meta_key' => 'gs_buono_mese_2026-07', 'fields' => 'ID' ) ) as $uid ) {
		$uid = (int) $uid;

		// Marcatore per persona scritto PRIMA dell'invio: stessa regola
		// imparata sulla chiusura del mese. Se questo giro muore a metà, la
		// ripresa non rimanda la rettifica a chi l'ha già ricevuta.
		if ( get_user_meta( $uid, 'gs_rettifica_luglio_inviata', true ) ) {
			continue;
		}
		update_user_meta( $uid, 'gs_rettifica_luglio_inviata', 1 );

		$u = get_userdata( $uid );
		if ( ! $u ) {
			continue;
		}
		if ( function_exists( 'gs_invia_messaggio' ) ) {
			gs_invia_messaggio( $uid, $oggetto, $corpo );
		}
		$raggiunte[] = $u->display_name;
	}

	update_option( 'gs_rettifica_luglio_fatta', current_time( 'mysql' ) );

	// Il resoconto a Ennio: chi è stato toccato dall'errore e ha ricevuto la
	// rettifica. È l'unica forma in cui questi nomi devono esistere — un
	// messaggio una tantum, non una voce di pannello: fra una settimana non
	// interesseranno più a nessuno.
	if ( function_exists( 'gs_inbox_crea' ) ) {
		gs_inbox_crea(
			'Rettifica di luglio inviata',
			$raggiunte
				? ( count( $raggiunte ) . " sfogline avevano ricevuto il resoconto di luglio per errore, e hanno ora ricevuto la rettifica:\n\n• "
					. implode( "\n• ", $raggiunte )
					. "\n\nLe email del messaggio sbagliato erano già partite e non si possono richiamare." )
				: 'Nessuna sfoglina risultava toccata: non è stata mandata nessuna rettifica.',
			array( 'from' => 'Sistema', 'link_pren' => 0 )
		);
	}
}
```

### Perché la rettifica non manda l'email

`gs_invia_messaggio()` scrive il messaggio privato sul sito, **senza passare da
`gs_mail_progetto()`**. È deliberato: le sfogline hanno già ricevuto un'email di troppo, e
una seconda email su un errore rischia di confondere più di quanto chiarisca. Il messaggio
sul sito basta, e chi apre l'email sbagliata lo trova lì.

**Se Ennio preferisce che parta anche l'email**, si aggiunge
`gs_mail_progetto( $uid, 'livelli', $oggetto, $corpo )` — ma chiediglielo, non deciderlo tu.

---

## E il cestino del messaggio sbagliato?

**Non farlo con il codice.** Sono sei messaggi: Ennio può toglierli a mano dal pannello
**«Messaggi di ogni sfoglina»**, che elenca ogni sfoglina con tutti i suoi messaggi ricevuti,
apribili uno a uno — li trova come *«Il tuo resoconto di luglio 2026»*.

Scrivere codice che cancella messaggi dalle caselle di persone vere, per risparmiare sei
clic, non vale il rischio. E quel pannello serve anche a **verificare che la rettifica sia
arrivata davvero**, il che è più utile di fidarsi del log.

---

## Verifiche

1. `php -l includes/rettifica-luglio.php`.
2. **Su guru2, la prova a vuoto:** senza nessun utente che abbia il marcatore
   `gs_buono_mese_2026-07`, esegui la funzione. **Non deve mandare niente**, e in Posta
   interna deve arrivare il messaggio «Nessuna sfoglina risultava toccata».
3. **Su guru2, la prova piena:** metti il marcatore a due utenti di prova, esegui,
   verifica che ricevano **un solo** messaggio ciascuno e che il resoconto elenchi
   entrambi i nomi. **Poi esegui una seconda volta: non deve succedere niente.**
4. **Pulisci guru2 dopo la prova** — e stavolta riporta il conteggio prima/dopo, come hai
   fatto bene l'ultima volta.

---

## Dopo l'esecuzione

Quando Ennio conferma di aver visto il resoconto in Posta interna: **cancella
`includes/rettifica-luglio.php` e la sua riga nell'elenco dei moduli.** Le due opzioni
(`gs_rettifica_luglio_fatta`) e i meta (`gs_rettifica_luglio_inviata`) restano: sono
innocui e documentano che è successo.

Un file usa-e-getta che resta nel plugin dopo aver fatto il suo lavoro diventa, con il
tempo, esattamente il codice morto che al Giro 6 andremo a togliere. Meglio non crearlo.
