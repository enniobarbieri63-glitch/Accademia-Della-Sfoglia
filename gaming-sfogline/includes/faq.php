<?php
/**
 * faq.php — FAQ - Domande.
 *
 * Domande frequenti raggruppate per argomento, pubbliche (visibili anche a
 * chi non ha effettuato l'accesso, come la Vetrina e il Registro Ufficiale).
 * Il gestore può aggiungere, correggere o eliminare singole domande dal
 * pannello, oppure caricare in un colpo solo il set di base già pronto
 * (gs_faq_set_base()), coprendo tutte le sezioni del progetto — non
 * duplica se richiamato più volte: salta le domande già presenti.
 *
 * CPT gs_faq → meta:
 *  - gs_faq_categoria (stringa libera, per raggruppare le domande)
 * Domanda in post_title, risposta in post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gs_faq_register_cpt' );
function gs_faq_register_cpt() {
	register_post_type( 'gs_faq', array(
		'labels'       => array( 'name' => 'FAQ - Domande' ),
		'public'       => false,
		'show_ui'      => false,
		'show_in_menu' => false,
		'supports'     => array( 'title', 'editor' ),
	) );
}

// -----------------------------------------------------------------------------
// Helper
// -----------------------------------------------------------------------------
function gs_faq_get( $id ) {
	return array(
		'id'        => $id,
		'domanda'   => get_the_title( $id ),
		'risposta'  => get_post( $id ) ? get_post( $id )->post_content : '',
		'categoria' => (string) get_post_meta( $id, 'gs_faq_categoria', true ) ?: 'Generale',
	);
}

/** Tutte le FAQ, raggruppate per categoria nell'ordine in cui compaiono. */
function gs_faq_per_categoria() {
	$tutte = gs_solo_tipo( get_posts( array(
		'post_type'      => 'gs_faq',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
		'suppress_filters' => true,
	) ), 'gs_faq' );

	$gruppi = array();
	foreach ( $tutte as $p ) {
		$c = gs_faq_get( $p->ID );
		if ( ! isset( $gruppi[ $c['categoria'] ] ) ) { $gruppi[ $c['categoria'] ] = array(); }
		$gruppi[ $c['categoria'] ][] = $c;
	}
	return $gruppi;
}

/**
 * Il set di base delle FAQ dell'Accademia, coprendo tutte le sezioni del
 * progetto — usato dal pulsante "Carica le FAQ di base" nel pannello.
 * Ogni voce: array( 'domanda', 'risposta' ). Le categorie seguono l'ordine
 * di comparsa nel menu del sito.
 */
function gs_faq_set_base() {
	return array(
		'Iscrizione e account' => array(
			array( 'Come mi iscrivo all\'Accademia della Sfoglia?', 'Dalla pagina "Iscrizione": la registrazione è gratuita, l\'accesso viene approvato a mano dalla segreteria dopo un controllo della richiesta (non serve alcun pagamento). Solo se in seguito vorrai continuare a usare le sezioni di gaming oltre il primo mese, gratuito, basterà un piccolo contributo facoltativo.' ),
			array( 'Perché il mio account risulta "in attesa di approvazione"?', 'La segreteria controlla ogni richiesta a mano prima di attivare l\'account: non è un pagamento da verificare, solo un controllo. Riceverai un\'email non appena viene approvato.' ),
			array( 'Cosa succede se il mio abbonamento scade?', 'Puoi accedere solo alle sezioni di base finché non rinnovi. Chi gestisce il portale non è mai soggetto a questo limite.' ),
		),
		'Livelli, punti e streak' => array(
			array( 'Come si guadagnano punti?', 'Partecipando alle sfide, inviando ricette approvate nel Ricettario, guardando le lezioni video, completando missioni giornaliere e in molte altre attività del sito.' ),
			array( 'Cosa sono le Insegne della Sfoglia?', 'I livelli che segnano il tuo percorso nell\'Accademia: ognuno ha una soglia di punti da raggiungere, un titolo e un simbolo.' ),
			array( 'Cos\'è la streak?', 'Il conteggio delle settimane consecutive in cui hai partecipato al sito. Ogni 4 settimane di streak guadagni uno scudo salva-streak.' ),
			array( 'A cosa serve lo scudo salva-streak?', 'Se una settimana non riesci a partecipare, uno scudo copre automaticamente l\'assenza e la tua serie di settimane consecutive non si azzera.' ),
		),
		'Le Sfide della Settimana' => array(
			array( 'Come partecipo a una sfida?', 'Dalla sezione "Le Sfide" invii la tua sfoglia (foto e descrizione) entro la scadenza del turno in corso.' ),
			array( 'Cos\'è una sfida "blindata"?', 'Una sfida con ammissione limitata: può richiedere un livello minimo, oppure una lista di sfogline ammesse o escluse a discrezione del titolare.' ),
			array( 'Perché non posso partecipare a certe sfide anche se ho il livello giusto?', 'Alcune sfide escludono automaticamente le prime posizioni della classifica generale, per dare spazio a chi è più indietro. Un\'ammissione a tua scelta da parte del titolare supera comunque questa esclusione.' ),
			array( 'Chi vota le sfide?', 'Dipende da come è impostata la sfida: in alcune vota liberamente ogni sfoglina, in altre votano solo i maestri (titolare e collaboratori).' ),
		),
		'Ricettario delle Famiglie' => array(
			array( 'Come invio una ricetta di famiglia?', 'Dalla sezione "Ricettario delle Famiglie" compili il modulo con nome del piatto, regione, la storia della ricetta e una foto.' ),
			array( 'Perché la mia ricetta non compare ancora nel Ricettario pubblico?', 'È in attesa di approvazione da parte del gestore: viene evidenziata con un colore dedicato finché non viene valutata.' ),
			array( 'Cosa sono le "Ricette per il Libro"?', 'Ricette già approvate ma tenute riservate per un eventuale libro cartaceo dell\'Accademia: non compaiono nel Ricettario pubblico, le vede solo chi gestisce il portale.' ),
			array( 'Cos\'è la Mappa dei Territori?', 'Una gara: pubblicare una ricetta approvata per ognuna delle 20 regioni italiane accende quella regione sulla mappa. La prima sfoglina che le accende tutte vince 50 punti.' ),
		),
		'Lezioni Video e Percorsi Guidati' => array(
			array( 'Come funzionano le domande di verifica sotto le lezioni?', 'Ogni domanda ha una risposta esatta stabilita dal docente: viene corretta in automatico appena la scrivi.' ),
			array( 'Cosa sono i Percorsi Guidati?', 'Sequenze di lezioni che si sbloccano una alla volta, nell\'ordine deciso dal titolare: devi completare un percorso prima che il successivo diventi accessibile.' ),
			array( 'Cos\'è un Percorso Stagionale?', 'Un percorso con una finestra di date (es. "Percorso di Natale"): resta aperto a tutti solo in quel periodo, fuori dalla sequenza normale.' ),
			array( 'Cos\'è un Percorso di Squadra?', 'L\'avanzamento è collettivo: basta che una sfoglina qualsiasi della squadra veda una lezione perché conti per tutta la squadra.' ),
			array( 'Cos\'è un Percorso a Staffetta?', 'Il completamento diventa un testimone che passa di mano: solo chi lo ha in mano e ha completato il percorso può assegnarlo a un\'altra sfoglina.' ),
			array( 'Cosa succede quando completo tutti i Percorsi Guidati?', 'Sblocchi il Diploma Finale dell\'Accademia.' ),
		),
		'Corsi Online' => array(
			array( 'Cosa sono i Corsi Online?', 'Un percorso di formazione individuale e privato con un docente reale, pensato per chi non può frequentare l\'aula in presenza: compiti assegnati giorno per giorno e riscontri personalizzati.' ),
			array( 'Come ricevo il Diploma di Sfoglina Professionista?', 'Te lo assegna direttamente il docente quando hai completato l\'intero Piano di Studio Ufficiale dell\'Accademia.' ),
			array( 'Posso vedere i compiti di un\'altra sfoglina nei Corsi Online?', 'No: ogni corso è privato, con i propri compiti e riscontri visibili solo alla sfoglina interessata e a chi gestisce il portale.' ),
			array( 'Cos\'è il Corso Privato?', 'Se desideri una formazione esclusiva, a qualsiasi livello, la Maestra Rina Poletti è disponibile per te: un corso su misura per un singolo allievo, con contenuti, ritmo e durata costruiti insieme in base alle tue esigenze. Richiedi informazioni dalla pagina "Corsi".' ),
			array( '"Rina Online" e "Corsi Online" sono la stessa cosa?', 'No, sono due percorsi diversi. I "Corsi Online" sono un percorso individuale più lungo, con compiti assegnati giorno per giorno da un docente. "Rina Online" è invece un percorso breve e fisso: due incontri in videochiamata con la Maestra Rina Poletti. Trovi entrambi nella pagina "Corsi".' ),
		),
		'Calendario Corsi' => array(
			array( 'Come prenoto un corso dal vero?', 'Dalla sezione "Calendario Corsi" scegli una data e prenoti il posto: ricevi un\'email con le istruzioni per il bonifico dell\'acconto.' ),
			array( 'Cosa sono gli "Esami pratici" e i "Confronti dal vero" nel Calendario?', 'Appuntamenti presso la scuola reale, aggiunti nella stessa lista dei corsi normali con un\'etichetta dedicata, per prenotarti allo stesso modo.' ),
			array( 'Cosa succede se disdico una prenotazione?', 'Se disdici entro i giorni di preavviso stabiliti, l\'acconto ti viene restituito; oltre quel termine resta trattenuto a copertura spese.' ),
			array( 'Posso mettermi in lista d\'attesa se un corso è al completo?', 'Sì. Se si libera un posto te lo proponiamo via email, e se hai già versato l\'acconto non devi rifarlo.' ),
			array( 'Posso togliere dalla lista un corso già concluso o annullato?', 'Sì, dalle tue prenotazioni trovi un pulsante per toglierlo dalla lista: funziona solo per corsi già effettuati, annullati o rimborsati, mai per una prenotazione ancora attiva. Resta comunque recuperabile dal cestino di chi gestisce il portale.' ),
			array( 'Non posso venire in Accademia: c\'è un corso online?', 'Sì, "Rina Online": due incontri in videochiamata di 2 ore ciascuno con la Maestra Rina Poletti, il primo di lezione e il secondo di prova pratica valutata. Le date si programmano secondo l\'agenda della Maestra: contatta la segreteria per i dettagli.' ),
		),
		// Portate dalla vecchia pagina statica "FAQ" del sito (informazioni-faq),
		// mai state nel Ricettario del plugin: domande pratiche sui corsi dal
		// vero, non sulla meccanica di prenotazione online (già coperta sopra).
		'Corsi in presenza — domande pratiche' => array(
			array( 'Serve esperienza per iscriversi a un corso?', 'No, i corsi sono pensati sia per principianti che per appassionati con esperienza. Tutti gli attrezzi (mattarello, spianatoia, tagliapasta) vengono forniti in aula; basta presentarsi con un grembiule, se lo si possiede, e tanta voglia di imparare.' ),
			array( 'Quanto dura un corso o laboratorio?', 'La maggior parte dei laboratori dura tra le 3 e le 4 ore, mentre i percorsi avanzati possono estendersi su più giornate. La durata esatta è sempre indicata nella scheda di ciascun corso sul portale.' ),
			array( 'Quali metodi di pagamento sono accettati?', 'Accettiamo il bonifico bancario. Dopo la prenotazione dal Calendario Corsi ricevi un\'email con tutti i dati per l\'acconto.' ),
			array( 'Posso disdire la mia prenotazione di un corso dal vero?', 'Sì, è possibile disdire o spostare la prenotazione gratuitamente fino a 48 ore prima dell\'inizio del corso. L\'acconto versato resta valido per un\'altra data disponibile; oltre questo termine viene considerato a copertura spese e non restituito. Per richieste oltre il termine, contatta la segreteria per valutare le opzioni disponibili.' ),
			array( 'Posso regalare un corso?', 'Certamente. Sul portale è disponibile l\'acquisto di gift card e voucher regalo, validi per qualsiasi corso in calendario e utilizzabili entro 12 mesi dalla data di acquisto.' ),
			array( 'Organizzate corsi privati o per gruppi?', 'Sì, organizziamo sessioni private per famiglie, gruppi di amici, addii al celibato/nubilato ed eventi aziendali, con programmi personalizzabili in base al numero di partecipanti e agli obiettivi del gruppo.' ),
			array( 'Proponete eventi team building aziendali?', 'Sì, l\'Accademia organizza esperienze di team building basate sulla preparazione della pasta fresca, pensate per rafforzare la collaborazione tra colleghi in un contesto conviviale e fuori dal comune.' ),
			array( 'Dove si trova la sede dell\'Accademia?', 'L\'indirizzo completo della sede, insieme a indicazioni su come arrivare e disponibilità di parcheggio, è riportato nella pagina "Contatti" del portale e nella email di conferma dopo l\'iscrizione.' ),
			array( 'I corsi sono adatti per bambini o famiglie?', 'Proponiamo laboratori dedicati alle famiglie e alle scuole, pensati per coinvolgere anche i più piccoli in un\'attività pratica e divertente. L\'età minima consigliata e le eventuali limitazioni sono indicate nella scheda del corso.' ),
			array( 'Posso portare a casa la pasta preparata?', 'Sì, ogni partecipante porta a casa la pasta fresca preparata durante la lezione, oltre a una dispensa con le ricette e i procedimenti illustrati per poter rifare i piatti anche tra le mura domestiche.' ),
			array( 'Viene rilasciato un attestato?', 'Per i corsi avanzati e i percorsi multi-giornata viene rilasciato un attestato di partecipazione al termine del programma. Per i laboratori di gruppo l\'attestato non è previsto, salvo diversa indicazione sulla scheda del corso.' ),
			array( 'Le allergie alimentari vanno segnalate?', 'Sì, ti chiediamo di segnalare eventuali allergie o intolleranze alimentari al momento della prenotazione, così da poter adattare, ove possibile, gli ingredienti utilizzati durante il laboratorio.' ),
			array( 'Ci sono corsi online o solo in presenza?', 'Entrambi. Il Corso Base, l\'Intermedio e il Professionale si svolgono in presenza presso la nostra sede, per un apprendimento pratico e diretto. Per chi non può raggiungerci c\'è "Rina Online": due incontri in videochiamata con la Maestra Rina Poletti.' ),
			array( 'Come posso contattare l\'Accademia?', 'Puoi scriverci tramite il modulo di contatto presente sul portale, inviare una email all\'indirizzo indicato nella pagina "Contattaci", oppure chiamare la segreteria nei giorni e orari indicati sul sito.' ),
			array( 'Quali ingredienti utilizzate durante i corsi?', 'Utilizziamo farina, uova fresche e altri ingredienti selezionati di alta qualità, privilegiando fornitori locali. Per i ripieni e i condimenti vengono impiegati prodotti tipici della tradizione gastronomica italiana.' ),
			array( 'Quanto costano i corsi dal vero?', 'Il prezzo varia in base alla tipologia di corso, alla durata e al numero di partecipanti. I costi aggiornati sono indicati in modo trasparente nella scheda di ciascun corso sul portale, prima della conferma dell\'iscrizione.' ),
			array( 'Quanti partecipanti ci sono in ogni laboratorio?', 'Per garantire un\'attenzione adeguata a ogni allievo, i gruppi sono generalmente composti da un numero ridotto di partecipanti. La capienza esatta è indicata nella scheda di ciascun corso.' ),
			array( 'Cosa devo indossare durante il corso?', 'Consigliamo un abbigliamento comodo e maniche corte o facilmente arrotolabili. Il grembiule viene fornito da noi se non ne possiedi uno, e ti chiediamo di rimuovere anelli e bracciali durante la lavorazione della pasta.' ),
			array( 'È possibile acquistare prodotti o attrezzi?', 'Sì, è possibile acquistare attrezzi da sfoglia, grembiuli, libri di ricette e altri prodotti firmati dall\'Accademia, anche come idea regalo.' ),
			array( 'In quale lingua si svolgono i corsi?', 'I corsi si svolgono principalmente in italiano. Su richiesta, e per gruppi specifici, è possibile organizzare lezioni anche in inglese: segnalalo al momento della prenotazione.' ),
			array( 'I corsi sono accessibili a persone con disabilità?', 'La nostra sede è attenta alle esigenze di accessibilità. Se hai necessità particolari, contatta la segreteria prima della prenotazione, così da poter organizzare al meglio l\'accoglienza.' ),
			array( 'I corsi si svolgono tutto l\'anno?', 'I corsi sono attivi durante tutto l\'anno, con un calendario aggiornato periodicamente sul portale. Alcuni laboratori a tema stagionale o legati a festività vengono proposti in periodi specifici.' ),
			array( 'Se seguo più corsi ottengo uno sconto?', 'Sì, agli allievi che frequentano più corsi riserviamo sconti dedicati e percorsi di approfondimento progressivo. I dettagli sulle promozioni attive sono pubblicati sul portale o comunicati via email agli iscritti.' ),
			array( 'Sono previsti corsi per chi è celiaco?', 'Sì, organizziamo periodicamente laboratori dedicati alla pasta senza glutine, realizzata con farine alternative. La disponibilità di queste date è segnalata nella sezione corsi del portale.' ),
			array( 'Posso pagare a rate i corsi più costosi?', 'Per i percorsi avanzati e i corsi multi-giornata è possibile richiedere un pagamento in più soluzioni. Contatta la segreteria prima dell\'iscrizione per concordare le modalità.' ),
			array( 'Quali materiali didattici vengono forniti?', 'Durante la lezione riceverai una dispensa con ricette, dosi e procedimenti illustrati passo dopo passo, utile per rifare i piatti a casa. Alcuni corsi avanzati includono anche schede tecniche aggiuntive.' ),
			array( 'Posso partecipare a un corso da solo?', 'Puoi iscriverti tranquillamente anche da solo: la maggior parte degli allievi partecipa singolarmente ai laboratori di livello superiore, mentre i laboratori di gruppo sono pensati per favorire la socializzazione tra i partecipanti.' ),
			array( 'Posso portare un accompagnatore che non partecipa al corso?', 'Per motivi organizzativi e di sicurezza in laboratorio, l\'accesso è riservato ai soli partecipanti iscritti. Se hai un\'esigenza particolare, contatta la segreteria prima della prenotazione per valutare insieme la soluzione migliore.' ),
			array( 'Come trattate i miei dati personali?', 'I dati raccolti in fase di iscrizione vengono trattati nel rispetto della normativa sulla privacy, esclusivamente per la gestione del corso e, se hai dato il consenso, per l\'invio di comunicazioni promozionali. I dettagli completi sono nell\'informativa privacy sul portale.' ),
			array( 'Sono previsti corsi serali?', 'Sì, il calendario include sessioni serali e nel fine settimana proprio per agevolare chi ha impegni lavorativi durante la giornata. Le date disponibili sono sempre consultabili sul portale.' ),
			array( 'Posso scattare foto o video al corso?', 'Sì, sei libero di fotografare per condividere sui social, ma non puoi filmare la tua esperienza. Ti chiediamo solo il dovuto rispetto per la privacy degli altri partecipanti presenti in aula.' ),
			array( 'Offrite formazione agli chef professionisti?', 'Sì, proponiamo percorsi avanzati e personalizzati pensati per professionisti della ristorazione che desiderano approfondire tecniche specifiche o ampliare l\'offerta di pasta fresca del proprio locale.' ),
			array( 'Come posso lasciare una mia recensione?', 'Dopo il corso riceverai una email con un link per lasciare una recensione direttamente sul portale. Il tuo feedback aiuta a migliorare costantemente l\'esperienza offerta agli allievi.' ),
			array( 'Sono previsti pacchetti o abbonamenti per più corsi?', 'Sì, sul portale sono disponibili pacchetti che comprendono più laboratori a un prezzo agevolato, pensati per chi desidera approfondire diverse tecniche nel corso dei mesi.' ),
			array( 'Posso partecipare se sono solo di passaggio in città?', 'Certamente, molti allievi sono turisti che scelgono di vivere questa esperienza durante il loro soggiorno. Ti consigliamo di prenotare in anticipo, soprattutto nei periodi di alta stagione.' ),
			array( 'Cosa succede se c\'è maltempo il giorno del corso?', 'I corsi si svolgono al chiuso e non sono generalmente influenzati dal meteo. In caso di emergenze eccezionali che impediscano lo svolgimento, verrai contattata per riprogrammare la data senza costi aggiuntivi.' ),
			array( 'Si possono portare animali domestici?', 'Per motivi igienico-sanitari legati alla manipolazione di alimenti, l\'accesso agli animali domestici non è consentito nei locali dove si svolgono i laboratori. Se necessario, è disponibile un\'area cortilizia dove possono soggiornare.' ),
			array( 'Esiste la possibilità di parcheggio?', 'Sì, all\'interno e nei pressi della sede sono disponibili spazi per il parcheggio.' ),
			array( 'Proponete anche corsi di pasticceria?', 'Il focus principale è la pasta fresca, ma periodicamente organizziamo laboratori speciali dedicati ad altre specialità della tradizione gastronomica italiana. Le novità sono annunciate sul portale e via newsletter.' ),
			array( 'Collaborate con ristoranti e produttori del territorio?', 'Sì, per alcuni percorsi avanzati collaboriamo con ristoranti e produttori del territorio per offrire degustazioni guidate che completano l\'esperienza formativa con un momento conviviale.' ),
			array( 'Le competenze acquisite sono riconosciute?', 'I percorsi avanzati diretti dalla Maestra Rina Poletti sono strutturati secondo gli standard della tradizione artigianale della sfoglia. Rina Poletti è riconosciuta dagli enti come massima espressione della sfoglia fatta a mano.' ),
			array( 'A chi mi rivolgo se ho un problema con un corso dal vero?', 'Puoi scrivere alla segreteria tramite il modulo di contatto del portale o via email, indicando i dettagli della tua esperienza: il team si impegna a rispondere e risolvere ogni segnalazione nel più breve tempo possibile.' ),
			array( 'Organizzate corsi a tema, legati alle festività?', 'Sì, in occasione delle principali festività proponiamo laboratori a tema con ricette tradizionali legate al periodo, molto richiesti anche come attività da regalare o da vivere in famiglia.' ),
			array( 'Posso indossare gioielli e orologi durante il corso?', 'Durante l\'attività di laboratorio, sia per questioni di igiene che di praticità, consigliamo di togliere orologi, braccialetti e anelli.' ),
		),
		'Messaggi e comunicazione' => array(
			array( 'Dove trovo i messaggi della segreteria?', 'Nella tua casella "Messaggi", raggiungibile dal menu laterale del sito.' ),
			array( 'Cos\'è l\'aeroplanino?', 'Lo striscione che attraversa lo schermo per avvisarti di qualcosa — un badge, un messaggio, una risposta, una prenotazione confermata. È cliccabile: un clic ti porta dritta al contenuto da vedere.' ),
			array( 'Posso scrivere in privato a un esperto o a un docente?', 'Sì, tramite le Conversazioni, se il gestore ha attivato questa possibilità per quel canale.' ),
		),
		'Compleanni' => array(
			array( 'Come imposto la mia data di nascita?', 'Dalla pagina "I Compleanni di Oggi": se non l\'hai ancora fatto, trovi il modulo per impostarla in fondo alla pagina.' ),
			array( 'Cosa succede il giorno del mio compleanno?', 'La tua vetrina compare in evidenza nella pagina dei Compleanni e tutte le sfogline ricevono un annuncio automatico col tuo nome.' ),
		),
		'Badge e Premi per Traguardo' => array(
			array( 'Come si sbloccano i badge?', 'Automaticamente, al verificarsi di condizioni specifiche: la prima sfoglia pubblicata, la partecipazione in tutti i trimestri dell\'anno, e altre ancora.' ),
			array( 'Cosa sono i "Premi per Traguardo"?', 'Video o messaggi speciali che il gestore prepara in anticipo: li ricevi in automatico quando raggiungi un certo livello o sblocchi un certo badge, sempre annunciati dall\'aeroplanino.' ),
		),
		'La Giuria a Turno' => array(
			array( 'Come funziona la Giuria a Turno?', 'Un piccolo gruppo di giudici — sfogline o maestri, a seconda del turno — vota le opere in gara con un punteggio e una motivazione scritta, mai in forma anonima.' ),
			array( 'Posso essere sia giudice sia partecipante nello stesso turno?', 'No: se sei tra le giudici assegnate a quel turno, non invii un\'opera propria.' ),
		),
		'La Sfoglia Misurata' => array(
			array( 'Cos\'è La Sfoglia Misurata?', 'Una sfida basata su un numero — per esempio lo spessore della sfoglia — non su un voto di gusto: vince chi si avvicina di più al risultato richiesto.' ),
		),
		'La Cassaforte del Sapere' => array(
			array( 'Cos\'è la Cassaforte del Sapere?', 'Un contenuto esclusivo che si sblocca solo quando un numero sufficiente di sfogline, tutte insieme, raggiunge un certo livello minimo.' ),
			array( 'Una volta sbloccata, la Cassaforte si può richiudere?', 'No: resta visibile per sempre, anche se il conteggio delle sfogline al livello richiesto dovesse scendere sotto la soglia.' ),
		),
		'Adotta un Piatto in Via di Estinzione' => array(
			array( 'Cosa significa "adottare" un piatto?', 'Diventi la custode pubblica di un piatto tradizionale a rischio di essere dimenticato, segnalato dal titolare.' ),
		),
		'Il Matterello Parlante' => array(
			array( 'Cos\'è Il Matterello Parlante?', 'Un archivio di racconti vocali delle sfogline: ogni registrazione viene approvata dal gestore prima di comparire pubblicamente.' ),
		),
		'Dicono di Noi' => array(
			array( 'Chi può scrivere in "Dicono di Noi"?', 'Sfogline e sfoglini che hanno fatto un percorso all\'Accademia della Sfoglia, raccontando con parole loro la propria esperienza.' ),
		),
		'Il Testamento della Sfoglina' => array(
			array( 'Cos\'è il Testamento della Sfoglina?', 'Un testo che chi raggiunge il livello più alto dell\'Accademia può lasciare per chi arriva dopo: un consiglio o un\'eredità. Si può correggere in qualsiasi momento dalla propria dashboard.' ),
		),
		'La Sfida del Silenzio' => array(
			array( 'Cos\'è la Sfida del Silenzio?', 'Un periodo, impostato a mano dal titolare, in cui la classifica generale resta congelata a come era all\'inizio: serve a spostare l\'attenzione dal punteggio al lavoro fatto.' ),
		),
		'Sicurezza e privacy' => array(
			array( 'Chi vede le mie conversazioni private?', 'Solo tu e la persona con cui stai parlando. Il titolare può vederle tutte per motivi di gestione del portale; le altre sfogline non vedono mai le conversazioni altrui.' ),
			array( 'Posso recuperare qualcosa che ho eliminato per errore?', 'Quasi sempre sì: ricette, messaggi, compiti e la maggior parte dei contenuti passano da un cestino recuperabile prima di sparire davvero.' ),
		),
		'Madrina & Allieva' => array(
			array( 'Cos\'è Madrina & Allieva?', 'Un abbinamento tra sfogline con mini-missioni condivise, pensato per aiutarsi a vicenda lungo il percorso nell\'Accademia.' ),
			array( 'Come vengo abbinata a una madrina o a un\'allieva?', 'L\'abbinamento lo decide chi gestisce il portale, dal pannello dedicato.' ),
		),
		'L\'Esperto Risponde' => array(
			array( 'Come faccio una domanda all\'esperto?', 'Dalla sezione "L\'Esperto Risponde" scegli un canale e scrivi la tua domanda: la risposta sarà pubblica, visibile a tutte.' ),
			array( 'C\'è un limite a quante domande posso fare?', 'Sì: chi gestisce il portale può impostare un limite giornaliero, settimanale o mensile, per evitare abusi del servizio.' ),
			array( 'Cosa sono i token e a cosa servono?', 'Il credito con cui si pagano le consulenze private (Conversazioni ed Esperto Risponde a pagamento): ogni domanda privata ha un costo in token, deciso da chi gestisce il portale.' ),
			array( 'Come ottengo i token?', 'Te li accredita chi gestisce il portale, di solito dopo un contributo associativo ricevuto — ma possono esserti assegnati anche come regalo o premio, senza bonifico.' ),
			array( 'Se non ricevo risposta, il token viene rimborsato?', 'Sì: se una domanda privata resta senza risposta oltre un certo numero di giorni (deciso da chi gestisce il portale), il token torna automaticamente al tuo saldo.' ),
		),
		'Diplomi e Locandine' => array(
			array( 'Posso creare io la locandina di un corso?', 'No, solo chi gestisce il portale può creare diplomi e locandine, dal pannello dedicato "Diplomi e Locandine".' ),
			array( 'Posso condividere una locandina sui social?', 'Sì: il pulsante "Condividi" apre il menu nativo del tuo dispositivo con l\'immagine già allegata, pronta per essere pubblicata dove preferisci.' ),
		),
		'Registro Ufficiale' => array(
			array( 'Cos\'è il Registro Ufficiale dell\'Accademia della Sfoglia?', 'L\'elenco pubblico diviso in due rami: il Registro degli Amatori (Attestato di Corso Base, Corso Intermedio, o Rina Online) e il Registro dei Professionisti (Attestato di Corso Professionale, oppure l\'intero percorso privato dei Corsi Online fino alla Laurea in Sfoglia).' ),
		),
		'Le Letture dei Grandi Protagonisti della Cucina' => array(
			array( 'Cosa sono "Le Letture dei Grandi Protagonisti della Cucina"?', 'Racconti e riflessioni pubblicati dai giornalisti e dai collaboratori dell\'Accademia. Sono visibili a chiunque, anche senza accedere al sito.' ),
			array( 'Chi può pubblicare una lettura?', 'Solo chi gestisce il portale: titolare e collaboratori.' ),
			array( 'Chi può rispondere sotto una lettura?', 'Una sfoglina iscritta, oppure chi si è iscritto con l\'account leggero "solo per commentare". Serve sempre un account: non si può rispondere restando anonimi.' ),
			array( 'Non sono una sfoglina, posso comunque commentare una lettura?', 'Sì: puoi iscriverti con l\'account leggero "solo per commentare", gratuito e immediato, senza quota associativa. Dà accesso solo ai commenti delle Letture, non al resto del percorso.' ),
			array( 'Chi modera i commenti delle Letture?', 'Chi gestisce il portale può eliminare un commento (recuperabile dal cestino) e, per gli account "solo per commentare" che ne abusano, bloccarli a tempo indeterminato o sospenderli fino a una data.' ),
		),
		'Vetrina pubblica' => array(
			array( 'Cos\'è la mia Vetrina pubblica?', 'Una pagina che mostra il tuo profilo, i tuoi progressi e la tua biografia (se approvata), visibile anche a chi non ha effettuato l\'accesso al sito.' ),
			array( 'Come faccio approvare la mia biografia per la Vetrina?', 'La scrivi dal tuo profilo: resta in attesa di approvazione finché il gestore non la controlla e la pubblica.' ),
		),
		'Ricerca nel sito' => array(
			array( 'Cosa cerca la "Ricerca in tutto il sito"?', 'Contemporaneamente nel Ricettario, nelle Domande all\'Esperto e nelle Lezioni Video, con un\'unica ricerca invece di controllare sezione per sezione.' ),
		),
		'Il Tuo Percorso e Il Tuo Anno' => array(
			array( 'Cos\'è "Il Tuo Percorso"?', 'Una cronologia personale di tutto quello che hai fatto nell\'Accademia, in ordine di tempo: sfide, ricette, lezioni, badge e molto altro.' ),
			array( 'Cos\'è "Il Tuo Anno in Accademia"?', 'Un riepilogo narrativo dei tuoi progressi nell\'anno solare in corso.' ),
		),
		'Un Anno Fa Oggi' => array(
			array( 'Cos\'è "Un Anno Fa Oggi"?', 'Un flashback pubblico che mostra le ricette del Ricettario pubblicate esattamente un anno fa, nello stesso giorno.' ),
		),
		'Diario dell\'Impasto' => array(
			array( 'A cosa serve il Diario dell\'Impasto?', 'Un diario personale dove annotare pensieri e progressi: resta privato, solo tu lo vedi.' ),
		),
		'Consigli della Community' => array(
			array( 'Cos\'è la sezione Consigli?', 'Uno spazio dove le sfogline si scambiano consigli pratici tra loro, liberamente.' ),
		),
		'Guida Stagionale' => array(
			array( 'Cos\'è la Guida Stagionale?', 'Un contenuto che cambia in base al periodo dell\'anno, con consigli stagionali su ingredienti e tecniche.' ),
		),
		'Ingrediente Segreto' => array(
			array( 'Cos\'è l\'Ingrediente Segreto del venerdì?', 'Una missione settimanale che ti sfida a usare, nella tua sfoglia, un ingrediente specifico indicato dal titolare.' ),
		),
		'Squadre Regionali' => array(
			array( 'Come funzionano le Squadre Regionali?', 'Ogni sfoglina appartiene a una squadra in base alla propria regione. La Classifica a Squadre somma i punti di tutte le sfogline della stessa squadra.' ),
		),
		'Notifiche' => array(
			array( 'Posso scegliere come ricevere le notifiche?', 'Sì: dalla sezione "Notifiche" puoi scegliere, categoria per categoria, se riceverle via email, come avviso interno, o entrambi.' ),
		),
		'Domande generali' => array(
			array( 'Come contatto la segreteria se ho un problema?', 'Dalla sezione "Aiuto" puoi inviare una richiesta di aiuto o un suggerimento, che arriva direttamente a chi gestisce il portale.' ),
			array( 'Posso dettare i miei testi a voce invece di scriverli?', 'Sì: ovunque vedi l\'icona del microfono puoi dettare a voce invece che scrivere a mano, sui browser che supportano il riconoscimento vocale (Chrome, Edge, Opera).' ),
			array( 'Perché una sezione che vedevo prima non compare più?', 'Alcune sezioni richiedono un abbonamento attivo o un livello minimo: controlla lo stato del tuo account, oppure scrivi alla segreteria dalla sezione Aiuto.' ),
		),
		// Punti 10 e 16 della memoria dei lavori sospesi (Ennio, 21/08/2026).
		'Il sito sul telefono' => array(
			array( 'Come metto l\'icona dell\'Accademia sulla schermata Home del telefono?', 'Su Android: apri il sito con Chrome, tocca i tre puntini in alto a destra e scegli "Aggiungi a schermata Home" (a volte compare da solo un banner in basso con la stessa proposta). Su iPhone: apri il sito con Safari, tocca l\'icona di condivisione (il quadrato con la freccia verso l\'alto) e scegli "Aggiungi a Home". In entrambi i casi comparirà un\'icona come quella di un\'app vera, senza installare nulla.' ),
			array( 'Esiste un\'app per usare il sito da cellulare?', 'È un\'idea a cui stiamo pensando, ma non è in programma nel futuro immediato. Nel frattempo il sito funziona bene anche da telefono, e puoi aggiungerne l\'icona alla schermata Home come fosse un\'app vera (vedi la domanda qui sopra).' ),
		),
	);
}

// -----------------------------------------------------------------------------
// [gs_faq] — pubblico, senza login richiesto
// -----------------------------------------------------------------------------
add_shortcode( 'gs_faq', 'gs_sc_faq' );
function gs_sc_faq() {
	$out = gs_box_open( '❓ FAQ - Domande' );
	$out .= gs_sezione_aiuto( 'Le domande più frequenti sull\'Accademia della Sfoglia, raggruppate per argomento. Clicca su una domanda per aprire la risposta. Se non trovi quello che cerchi, scrivi alla segreteria dalla sezione "Aiuto".' );

	$gruppi = gs_faq_per_categoria();
	if ( ! $gruppi ) {
		$out .= '<p class="gs-hint">Le domande frequenti non sono ancora state preparate.</p>';
		$out .= gs_box_close();
		return $out;
	}

	$out .= '<input type="text" class="gs-cerca-input" data-target=".gs-faq-lista" placeholder="🔍 Cerca una domanda…" style="width:100%;max-width:420px;margin-bottom:14px">';

	foreach ( $gruppi as $categoria => $voci ) {
		$out .= '<h4 style="margin-top:18px">' . esc_html( $categoria ) . '</h4>';
		$out .= '<div class="gs-inbox-lista gs-faq-lista gs-todo-riquadro gs-riquadro-vetrina">';
		foreach ( $voci as $c ) {
			$out .= '<details class="gs-inbox-item"><summary class="gs-inbox-oggetto">' . esc_html( $c['domanda'] ) . '</summary>';
			$out .= '<div class="gs-inbox-corpo"><div class="gs-inbox-testo">' . nl2br( esc_html( $c['risposta'] ) ) . '</div></div></details>';
		}
		$out .= '</div>';
	}

	$out .= gs_box_close();
	return $out;
}

// -----------------------------------------------------------------------------
// PANNELLO — gestione delle FAQ (titolare)
// -----------------------------------------------------------------------------
function gs_pannello_faq() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '❓ FAQ - Domande', '', 'gs-box-faq' );
	echo gs_sezione_aiuto( 'Gestisci le domande frequenti pubbliche del sito, raggruppate per argomento. "Carica le FAQ di base" aggiunge un set già pronto che copre tutte le sezioni dell\'Accademia — non duplica le domande già presenti, quindi puoi premerlo di nuovo in sicurezza dopo un aggiornamento.' );

	$tot_base = 0;
	foreach ( gs_faq_set_base() as $voci ) { $tot_base += count( $voci ); }
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-faq-carica-base">📥 Carica le FAQ di base (' . (int) $tot_base . ' domande)</button> <span class="gs-faq-base-msg gs-richiesta-esito"></span></p>';

	echo '<form class="gs-form gs-form-faq-crea" onsubmit="return false" style="background:var(--gs-uovo);padding:12px;border-radius:6px;margin-bottom:14px">';
	echo '<strong>Nuova domanda</strong>';
	echo '<p><label>Categoria<br><input type="text" name="categoria" autocomplete="off" style="width:100%" placeholder="Es. Le Sfide della Settimana" list="gs-faq-categorie"></label></p>';
	echo '<datalist id="gs-faq-categorie">';
	foreach ( array_keys( gs_faq_per_categoria() ) as $cat ) { echo '<option value="' . esc_attr( $cat ) . '">'; }
	echo '</datalist>';
	echo '<p><label>Domanda<br><input type="text" name="domanda" autocomplete="off" style="width:100%" required></label></p>';
	echo '<p><label>Risposta<br><textarea name="risposta" rows="3" style="width:100%" required></textarea></label></p>';
	echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-faq-crea">Crea domanda</button> <span class="gs-faq-crea-msg gs-richiesta-esito"></span></p>';
	echo '</form>';

	$gruppi = gs_faq_per_categoria();
	$tot = 0; foreach ( $gruppi as $voci ) { $tot += count( $voci ); }
	echo '<h4>Domande impostate (' . (int) $tot . ')</h4>';
	if ( ! $gruppi ) {
		echo '<p class="gs-hint">Nessuna domanda ancora creata.</p>';
	} else {
		echo '<input type="text" class="gs-cerca-input" data-target=".gs-faq-lista-admin" placeholder="🔍 Cerca una domanda…" style="width:100%;max-width:320px;margin-bottom:10px">';
		echo '<div class="gs-inbox-lista gs-lista-risultati gs-faq-lista-admin gs-paginate" data-per-page="10">';
		foreach ( $gruppi as $categoria => $voci ) {
			foreach ( $voci as $c ) {
				echo '<details class="gs-inbox-item" data-faq="' . (int) $c['id'] . '">';
				echo '<summary class="gs-inbox-oggetto">' . esc_html( $c['domanda'] ) . ' <span class="gs-msg-data">' . esc_html( $categoria ) . '</span></summary>';
				echo '<div class="gs-inbox-corpo">';
				echo '<form class="gs-form gs-form-faq-modifica" data-faq="' . (int) $c['id'] . '" onsubmit="return false">';
				echo '<p><label>Categoria<br><input type="text" name="categoria" autocomplete="off" value="' . esc_attr( $categoria ) . '" style="width:100%" list="gs-faq-categorie"></label></p>';
				echo '<p><label>Domanda<br><input type="text" name="domanda" autocomplete="off" value="' . esc_attr( $c['domanda'] ) . '" style="width:100%" required></label></p>';
				echo '<p><label>Risposta<br><textarea name="risposta" rows="3" style="width:100%" required>' . esc_textarea( $c['risposta'] ) . '</textarea></label></p>';
				echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-faq-salva">Salva modifiche</button> ';
				echo '<button class="gs-btn gs-btn-sm gs-btn-ghost gs-faq-elimina" data-faq="' . (int) $c['id'] . '">Elimina</button> <span class="gs-faq-riga-msg gs-richiesta-esito"></span></p>';
				echo '</form>';
				echo '</div></details>';
			}
		}
		echo '</div>';
	}

	// Cestino.
	$trash = get_posts( array( 'post_type' => 'gs_faq', 'post_status' => 'trash', 'posts_per_page' => 50, 'suppress_filters' => true ) );
	echo '<div class="gs-sezione-cestino">';
	echo '<h4 class="gs-titolo-cestino">🗑️ Cestino</h4>';
	if ( ! $trash ) {
		echo '<p class="gs-hint">Il cestino è vuoto.</p>';
	} else {
		echo '<table class="gs-table gs-paginate" data-per-page="8"><thead><tr>' . gs_cestino_th_checkbox() . '<th>Domanda</th><th></th></tr></thead><tbody>';
		foreach ( $trash as $p ) {
			echo '<tr data-faq="' . (int) $p->ID . '">' . gs_cestino_td_checkbox( $p->ID ) . '<td>' . esc_html( get_the_title( $p ) ) . '</td>';
			echo '<td><button class="gs-btn gs-btn-sm gs-btn-verde gs-faq-ripristina" data-faq="' . (int) $p->ID . '">Ripristina</button> <span class="gs-faq-trow-msg gs-richiesta-esito"></span></td></tr>';
		}
		echo '</tbody></table>';
		echo gs_cestino_azioni_bulk( 'gs_faq' );
	}
	echo '</div>';

	echo gs_box_close();
}

add_action( 'wp_ajax_gs_faq_crea', 'gs_ajax_faq_crea' );
function gs_ajax_faq_crea() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$domanda  = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	$risposta = isset( $_POST['risposta'] ) ? sanitize_textarea_field( wp_unslash( $_POST['risposta'] ) ) : '';
	$categoria = isset( $_POST['categoria'] ) ? sanitize_text_field( wp_unslash( $_POST['categoria'] ) ) : '';
	if ( '' === trim( $domanda ) ) { wp_send_json_error( array( 'message' => 'Scrivi la domanda.' ) ); }
	if ( '' === trim( $risposta ) ) { wp_send_json_error( array( 'message' => 'Scrivi la risposta.' ) ); }
	if ( '' === trim( $categoria ) ) { $categoria = 'Generale'; }

	$id = wp_insert_post( array( 'post_type' => 'gs_faq', 'post_status' => 'publish', 'post_title' => $domanda, 'post_content' => $risposta ) );
	if ( is_wp_error( $id ) || ! $id ) { wp_send_json_error( array( 'message' => 'Errore nella creazione.' ) ); }
	update_post_meta( $id, 'gs_faq_categoria', $categoria );

	wp_send_json_success( array( 'message' => 'Domanda creata.' ) );
}

add_action( 'wp_ajax_gs_faq_modifica', 'gs_ajax_faq_modifica' );
function gs_ajax_faq_modifica() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['faq'] ) ? (int) $_POST['faq'] : 0;
	if ( 'gs_faq' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Domanda non valida.' ) ); }
	$domanda  = isset( $_POST['domanda'] ) ? sanitize_text_field( wp_unslash( $_POST['domanda'] ) ) : '';
	$risposta = isset( $_POST['risposta'] ) ? sanitize_textarea_field( wp_unslash( $_POST['risposta'] ) ) : '';
	$categoria = isset( $_POST['categoria'] ) ? sanitize_text_field( wp_unslash( $_POST['categoria'] ) ) : '';
	if ( '' === trim( $domanda ) ) { wp_send_json_error( array( 'message' => 'Scrivi la domanda.' ) ); }
	if ( '' === trim( $risposta ) ) { wp_send_json_error( array( 'message' => 'Scrivi la risposta.' ) ); }
	if ( '' === trim( $categoria ) ) { $categoria = 'Generale'; }

	wp_update_post( array( 'ID' => $id, 'post_title' => $domanda, 'post_content' => $risposta ) );
	update_post_meta( $id, 'gs_faq_categoria', $categoria );

	wp_send_json_success( array( 'message' => 'Domanda aggiornata.' ) );
}

add_action( 'wp_ajax_gs_faq_elimina', 'gs_ajax_faq_elimina' );
function gs_ajax_faq_elimina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['faq'] ) ? (int) $_POST['faq'] : 0;
	if ( 'gs_faq' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Domanda non valida.' ) ); }
	wp_trash_post( $id );
	wp_send_json_success( array( 'message' => 'Spostata nel cestino.' ) );
}

add_action( 'wp_ajax_gs_faq_ripristina', 'gs_ajax_faq_ripristina' );
function gs_ajax_faq_ripristina() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$id = isset( $_POST['faq'] ) ? (int) $_POST['faq'] : 0;
	if ( 'gs_faq' !== get_post_type( $id ) ) { wp_send_json_error( array( 'message' => 'Domanda non valida.' ) ); }
	wp_untrash_post( $id );
	wp_send_json_success( array( 'message' => 'Domanda ripristinata.' ) );
}

/**
 * Inserisce il set di base, saltando le domande già presenti (stesso testo
 * esatto): richiamabile in sicurezza più volte, sia dal pulsante del
 * pannello sia in automatico all'attivazione del plugin. Restituisce quante
 * domande ha aggiunto davvero.
 *
 * Il controllo dei duplicati guarda anche le domande nel cestino, non solo
 * quelle pubblicate: altrimenti una domanda eliminata dal gestore tornerebbe
 * ogni volta che il plugin viene riattivato (cosa che succede a ogni
 * consegna, per procedura), vanificando la cancellazione.
 */
function gs_faq_carica_set_base() {
	$esistenti = array();
	foreach ( gs_solo_tipo( get_posts( array( 'post_type' => 'gs_faq', 'post_status' => array( 'publish', 'trash' ), 'posts_per_page' => -1, 'suppress_filters' => true ) ), 'gs_faq' ) as $p ) {
		$esistenti[ $p->post_title ] = true;
	}

	$aggiunte = 0;
	foreach ( gs_faq_set_base() as $categoria => $voci ) {
		foreach ( $voci as $voce ) {
			list( $domanda, $risposta ) = $voce;
			// Il titolo salvato in DB non è mai identico a quello scritto qui
			// sopra se contiene una & (wp_insert_post normalizza in &amp;
			// al salvataggio via wp_kses — replicata qui con la stessa
			// funzione, non con sanitize_post_field(): quella aggiunge
			// anche backslash davanti agli apostrofi per il contesto SQL,
			// rompendo il confronto pure sulle domande senza &). Confrontare
			// la stringa grezza con quella già salvata falliva sempre per
			// "Cos'è Madrina & Allieva?", che si riaggiungeva a ogni clic
			// su "Carica il set base" — bug reale trovato provando davvero
			// il pulsante due volte di fila, non solo leggendo il codice
			// (22/08/2026).
			$domanda_confronto = wp_kses_normalize_entities( $domanda );
			if ( isset( $esistenti[ $domanda_confronto ] ) ) { continue; }
			$id = wp_insert_post( array( 'post_type' => 'gs_faq', 'post_status' => 'publish', 'post_title' => $domanda, 'post_content' => $risposta ) );
			if ( is_wp_error( $id ) || ! $id ) { continue; }
			update_post_meta( $id, 'gs_faq_categoria', $categoria );
			$aggiunte++;
		}
	}
	return $aggiunte;
}

add_action( 'wp_ajax_gs_faq_carica_base', 'gs_ajax_faq_carica_base' );
function gs_ajax_faq_carica_base() {
	check_ajax_referer( 'gs_ajax', 'nonce' );
	if ( ! gs_can_manage() ) { wp_send_json_error( array( 'message' => 'Permesso negato.' ) ); }
	$aggiunte = gs_faq_carica_set_base();
	wp_send_json_success( array( 'message' => $aggiunte ? $aggiunte . ' domande aggiunte.' : 'Erano già tutte presenti: nessuna nuova domanda aggiunta.' ) );
}

