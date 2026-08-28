<?php
/**
 * spiegazioni.php — Raccoglitore delle spiegazioni di ogni sezione del
 * plugin (gaming e pannelli), con ricerca e invio diretto come messaggio
 * interno a qualunque persona. Richiesto da Ennio il 26/08/2026.
 *
 * I testi qui dentro sono copie di quelli già scritti accanto a ogni
 * sezione (gs_sezione_aiuto(), un riquadro per sezione): tenerli come copia
 * invece che leggerli dal vivo evita di dover eseguire ogni shortcode/
 * pannello solo per raccoglierne il testo. Prezzo di questa scelta: se il
 * testo originale cambia, questa copia non si aggiorna da sola — va
 * aggiornata a mano qui, quando capita (i testi cambiano raramente).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * L'elenco completo: array di { titolo, categoria ('sfoglina'|'gestore'),
 * testo, fonte (file di origine, solo per orientarsi in manutenzione) }.
 */
function gs_spiegazioni_elenco() {
	return array(
		array(
			'titolo'    => 'Caroselli',
			'categoria' => 'gestore',
			'testo'     => 'Quattro caroselli scorrevoli, pronti per essere incollati in Home Page o in qualunque altra pagina del sito con il relativo shortcode: copialo e incollalo nell\'editor della pagina, come un blocco di testo. Stessa grafica su tutti — icona su fondo colorato, titolo, testo, cartellino. I dati sono sempre quelli già gestiti altrove (Le Sfogline, Artigiani della Pasta, Scuole di Cucina, il Calendario Corsi): qui scegli quante schede mostrare, la velocità di scorrimento automatico, e se l\'ordine delle sfogline senza Vetrina attiva cambia a ogni caricamento della pagina.',
			'fonte'     => 'includes/caroselli.php',
		),
		array(
			'titolo'    => 'Gestione Artigiani della Pasta',
			'categoria' => 'gestore',
			'testo'     => 'Qui crei e controlli i partner de "Gli Artigiani della Pasta": aggiungi un\'attività (viene creato subito l\'account con cui accederà al proprio pannello), controlli/approvi le modifiche che inviano, registri i bonifici ricevuti e la nuova scadenza. Una vetrina compare nella sezione pubblica SOLO se è approvata E ha l\'abbonamento attivo: se l\'abbonamento scade, si nasconde da sola finché non registri un nuovo pagamento.',
			'fonte'     => 'includes/artigiani.php',
		),
		array(
			'titolo'    => 'Gestione FAQ',
			'categoria' => 'gestore',
			'testo'     => 'Gestisci le domande frequenti pubbliche del sito, raggruppate per argomento. "Carica le FAQ di base" aggiunge un set già pronto che copre tutte le sezioni dell\'Accademia — non duplica le domande già presenti, quindi puoi premerlo di nuovo in sicurezza dopo un aggiornamento.',
			'fonte'     => 'includes/faq.php',
		),
		array(
			'titolo'    => 'Gestione Ingrediente Segreto',
			'categoria' => 'gestore',
			'testo'     => 'Ogni venerdì Rina Poletti o Bruno Cingolani svelano un ingrediente a sorpresa da usare in una ricetta. Qui lo crei senza passare dall\'editor di WordPress: scrivi il nome, il testo, scegli quando si svela (di norma il venerdì alle 18:00, già proposto) e chi lo comunica. Finché la data non arriva, le sfogline vedono solo il conto alla rovescia.',
			'fonte'     => 'includes/secret-ingredient.php',
		),
		array(
			'titolo'    => 'Gestione Novità',
			'categoria' => 'gestore',
			'testo'     => 'Scrivi qui gli annunci pubblici che compaiono nella sezione "Novità" del sito: nuove sezioni, cambiamenti, avvisi. Spuntando "Avvisa subito le sfogline" arriva anche l\'aeroplanino a tutte le sfogline approvate, con un link diretto all\'annuncio.',
			'fonte'     => 'includes/novita.php',
		),
		array(
			'titolo'    => 'Gestione Scuole di Cucina',
			'categoria' => 'gestore',
			'testo'     => 'Qui crei e controlli i partner de "Le Scuole di Cucina": aggiungi una scuola (viene creato subito l\'account con cui accederà al proprio pannello), controlli/approvi le modifiche che invia, registri i bonifici ricevuti e la nuova scadenza. Una vetrina compare nella sezione pubblica SOLO se è approvata E ha l\'abbonamento attivo: se l\'abbonamento scade, si nasconde da sola finché non registri un nuovo pagamento. Il costo dell\'abbonamento è indipendente da quello degli Artigiani della Pasta: si imposta a parte nel pannello Token.',
			'fonte'     => 'includes/scuole-cucina.php',
		),
		array(
			'titolo'    => 'Gestione Sondaggi',
			'categoria' => 'gestore',
			'testo'     => 'Crea un sondaggio con una domanda e, se vuoi, qualche proposta di partenza (una per riga). "Risultati visibili alle sfogline" mostra il conteggio dei voti anche sulla pagina pubblica; lasciandolo spento i risultati restano visibili solo qui, in questo pannello, ma le sfogline possono comunque votare. "Le sfogline possono proporre nuove idee" apre un piccolo modulo nella pagina pubblica: ogni proposta scritta da una sfoglina si aggiunge a quelle esistenti e diventa votabile da tutte. "Avvisa subito le sfogline" manda l\'aeroplanino a tutte le sfogline approvate nel momento in cui crei il sondaggio, con un link che le porta dritte alla pagina Sondaggi: usalo quando vuoi che se ne accorgano subito, altrimenti resta silenzioso finché non ci passano da sole. Da qui vedi sempre il conteggio completo dei voti, anche per i sondaggi con risultati riservati.',
			'fonte'     => 'includes/sondaggi.php',
		),
		array(
			'titolo'    => 'Gestione Token',
			'categoria' => 'gestore',
			'testo'     => 'Le consulenze private con i maestri (Rina Poletti Risponde, Bruno Cingolani Risponde e altri canali futuri) si pagano a token: ogni domanda ne consuma uno, o il numero che decidi per quel singolo maestro dal pannello «L\'Esperto Risponde». Le sfogline comprano credito con un contributo associativo versato con bonifico — causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA» — e tu lo accrediti qui sotto dopo aver verificato l\'arrivo del bonifico. Se una domanda resta senza risposta, il token torna alla sfoglina: in automatico dopo i giorni che imposti qui sotto, oppure subito a mano dalla conversazione privata.',
			'fonte'     => 'includes/token.php',
		),
		array(
			'titolo'    => 'Gestione Voti della Sfida',
			'categoria' => 'gestore',
			'testo'     => 'Tutte le sfoglie inviate per la sfida attiva, in un\'unica videata: usa la ricerca in cima per trovare subito una sfoglina. Ogni scheda è rossa finché resta da controllare; clicca "✅ Segnala controllata" quando l\'hai valutata e diventa verde (puoi sempre rimetterla da controllare). Dentro ogni scheda trovi anche "Correggi punti di questa sfoglina", già pronta con il suo nome, per un errore o un caso particolare.',
			'fonte'     => 'includes/voting.php',
		),
		array(
			'titolo'    => 'Locandine e Diplomi',
			'categoria' => 'gestore',
			'testo'     => 'Crea diplomi e locandine a piacimento con la stessa grafica dei diplomi dell\'Accademia: intestazione (es. "Maestra Rina Poletti"), un titolo grande (e, se serve, un secondo titolo grande sotto il primo), il testo libero (es. il programma di un corso) e qualche foto. Ogni documento creato resta salvato qui: riaprilo quando vuoi per rivederlo, modificarlo, stamparlo/salvarlo in PDF, scaricarlo come immagine pronta per Facebook e Instagram, oppure condividerlo direttamente con il pulsante "Condividi" (apre il menu di condivisione del telefono/computer: scegli tu l\'app, es. Instagram o Facebook). Con "🔗 Prepara link per Facebook" ottieni invece un link pubblico da incollare in un post: chi lo apre vede la pagina vera, con il pulsante "Per prenotarti" funzionante (visibile solo a te, non ai visitatori).',
			'fonte'     => 'includes/locandine.php',
		),
		array(
			'titolo'    => 'Moderazione di Tutte le Chat',
			'categoria' => 'gestore',
			'testo'     => 'Qui compaiono, dal più recente, i messaggi scritti in ogni chat del progetto: Conversazioni, Messaggi privati, Posta interna, Aiuto e Suggerimenti, Calendario Corsi, Auguri di compleanno e i commenti delle Letture. Serve per controllare rapidamente cosa si scrive senza aprire ogni pannello uno per uno. "Elimina" sposta il messaggio nel cestino del suo sistema (mai una cancellazione definitiva): per gli auguri di compleanno, che sono un messaggio a sé, va nel cestino di WordPress. Il link "Vai al pannello" porta dritto alla sezione di origine per vedere il contesto completo dello scambio. Sia il titolare sia i collaboratori con accesso a questo pannello possono moderare.',
			'fonte'     => 'includes/moderazione.php',
		),
		array(
			'titolo'    => 'Preferenze di Notifica',
			'categoria' => 'gestore',
			'testo'     => 'Per ogni persona e per ogni tipo di email, scegli i canali: <strong>Email</strong> manda la vera email; <strong>Interno</strong> la fa comparire come messaggio dalla segreteria nella sua area, senza toccare la sua posta. Puoi tenerle entrambe attive (es. per le scadenze dell\'iscrizione, che restano anche via mail), solo una, o nessuna. Chi non ha mai avuto preferenze impostate riceve tutto via email, come sempre. Vale anche per te e per i collaboratori: trovi la vostra tabella più sotto.',
			'fonte'     => 'includes/notifiche-pref.php',
		),
		array(
			'titolo'    => 'Premi per Traguardi',
			'categoria' => 'gestore',
			'testo'     => 'Prepara in anticipo un premio da consegnare in automatico quando una sfoglina raggiunge un livello o sblocca un badge: un video (link YouTube o Vimeo), un messaggio di testo, oppure — novità — una percentuale di sconto sui corsi di Rina Poletti, che si accumula badge dopo badge finché non viene spesa iscrivendosi a un corso vero. Lo sconto si riferisce sempre al livello corso su cui la sfoglina si trova (parte da Base, poi Avanzato, poi Professionale: vedi il pannello Iscrizioni in Calendario Corsi per applicarlo e far avanzare il livello). Ogni premio arriva nella sua casella "Messaggi" ed è sempre annunciato da un aeroplanino cliccabile, che la porta dritta lì; lo vede anche nel suo pannello personale, in "🎁 Premi e sconti sui corsi". Puoi impostare più premi per lo stesso traguardo: arrivano tutti insieme. È anche il posto giusto per presentare, a chi finisce il percorso (livello massimo), le opportunità vere — corso in presenza, corso online personalizzato, corso professionale — con un video di presentazione: puoi riusare uno dei video già caricati in "Libreria Video delle Lezioni" qui sopra, o incollarne uno nuovo.',
			'fonte'     => 'includes/premi-traguardi.php',
		),
		array(
			'titolo'    => 'Ricette Riservate',
			'categoria' => 'gestore',
			'testo'     => 'Ricette di famiglia scelte per un eventuale libro cartaceo dell\'Accademia: restano riservate — non compaiono nel Ricettario pubblico, le vede solo chi gestisce il portale. Da qui puoi spostarle nel Ricettario pubblico in qualunque momento (diventa visibile al pubblico in tutto il sito), oppure rifiutarle. Sotto ogni ricetta trovi anche il modulo per rispondere direttamente alla sfoglina che l\'ha condivisa, per esempio per ringraziarla o chiederle qualche dettaglio in più: il messaggio avvia una conversazione privata con lei.',
			'fonte'     => 'includes/ricettario.php',
		),
		array(
			'titolo'    => 'Scheda Iscritti ai Corsi',
			'categoria' => 'gestore',
			'testo'     => 'Una scheda unica per ogni persona iscritta a un corso: dati, corso e calendario, pagamenti, token, comunicazioni, diploma, note riservate e proposta di soggiorno. Cerca un nome o filtra per stato, poi clicca sulla riga per aprire la scheda completa — è la stessa scheda che trovi già in "Cerca sfoglina", con in più le sezioni dedicate ai corsi.',
			'fonte'     => 'includes/regia-iscritti.php',
		),
		array(
			'titolo'    => 'Sfide Blindate ed Esclusioni',
			'categoria' => 'gestore',
			'testo'     => 'Due strumenti diversi per limitare chi partecipa a una sfida, a tua scelta: le "sfide blindate" (livello minimo + ammissioni/esclusioni una per una) e, per QUALSIASI sfida, l\'esclusione automatica delle prime posizioni della classifica generale — utile per dare spazio a chi è più indietro, senza dover escludere una per una le sfogline in testa.',
			'fonte'     => 'includes/control-panel.php',
		),
		array(
			'titolo'    => 'Struttura del Menu',
			'categoria' => 'gestore',
			'testo'     => 'Riorganizza in un clic il menu scelto secondo la struttura concordata: crea le voci-contenitore L\'Accademia, Corsi, Community e Contenuti, e sposta sotto di loro le voci già esistenti nel menu (cercandole per titolo, mai indovinando un indirizzo). "Home" e "Sostieni l\'Accademia" restano dove sono, come voci singole. Non tocca mai il menu in alto (Home / Appello per il Governo / Disciplinare / Scrivici): se una voce che serve — come "L\'Esperto Risponde" — vive lì, ne crea una copia nel menu scelto, lasciando l\'originale al suo posto. Si può rilanciare più volte senza creare doppioni: quello che è già a posto viene lasciato com\'è. <strong>Va scelto un menu solo per volta</strong>: se il sito mostra più di un menu contemporaneamente (es. una barra sottile in alto e il menu principale sotto il logo, come nel tema Newspaper), applicarla al menu sbagliato crea gli stessi gruppi anche lì — è già successo il 03/08/2026, corretto con lo strumento di pulizia qui sotto. "Correggi voci trovate" sistema invece tre errori individuati con un controllo del 2026-08-02: una voce etichettata per errore "miao" (punta davvero alla pagina di Rina Poletti); una voce "FAQ" che punta a una pagina vuota e abbandonata invece delle vere FAQ; e la voce "Dicono di noi" (minuscolo) che punta alla vecchia pagina su sede/B&B — da cestinare a parte in Pagine — trasformata qui in "La Nostra Sede", la pagina nuova sullo stesso argomento. Solo questa seconda operazione (le tre correzioni puntuali, mai la struttura completa) parte anche da sola, una volta sola, su tutti i menu del sito.',
			'fonte'     => 'includes/menu-struttura.php',
		),
		array(
			'titolo'    => 'Accesso',
			'categoria' => 'sfoglina',
			'testo'     => 'Inserisci lo username (o l\'email) e la password scelti in fase di iscrizione. Se non hai ancora un account, iscriviti dalla pagina "Iscrizione"; se hai dimenticato la password, apri "Password dimenticata?" qui sotto e scrivi la tua email — arriverà un link per sceglierne una nuova. Per proteggere gli account, dopo alcuni tentativi con password sbagliata dallo stesso indirizzo l\'accesso resta temporaneamente bloccato: se succede, riprova tra qualche minuto.',
			'fonte'     => 'includes/login.php',
		),
		array(
			'titolo'    => 'Adotta un Piatto in Via di Estinzione',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui trovi i piatti tradizionali che i maestri hanno segnalato come a rischio di essere dimenticati. Un piatto "libero" può essere adottato: diventi la sua custode pubblica, il tuo nome compare accanto al piatto per tutte. Se fai parte di una squadra, puoi adottarlo anche a nome della squadra. Ogni piatto ha una sola custode alla volta.',
			'fonte'     => 'includes/piatti-estinzione.php',
		),
		array(
			'titolo'    => 'Aiuto e Suggerimenti',
			'categoria' => 'sfoglina',
			'testo'     => 'Scegli se è una richiesta di aiuto o un suggerimento, scrivi il messaggio e invia: arriva subito a chi gestisce il portale. I messaggi che hai già inviato, con lo stato (in attesa/gestito), sono elencati più sotto con titolo cliccabile: se hanno ricevuto una risposta, la trovi subito sotto il tuo messaggio, a bolle come nelle Conversazioni, e puoi rispondere ancora da lì per continuare lo scambio. Il titolo di questo riquadro (e il messaggio interessato) lampeggiano in rosso quando c\'è una risposta che non hai ancora visto.',
			'fonte'     => 'includes/aiuto.php',
		),
		array(
			'titolo'    => 'Area Professionale',
			'categoria' => 'sfoglina',
			'testo'     => 'Segui i compiti nell\'ordine proposto: segna quelli completati e lascia una nota, chi ti segue ti risponde con il suo riscontro. I parametri del corso li imposta il titolare in forma privata, tu vedi solo il tuo percorso personale.',
			'fonte'     => 'includes/area-pro.php',
		),
		array(
			'titolo'    => 'Attiva la Vetrina',
			'categoria' => 'sfoglina',
			'testo'     => 'La Vetrina è il tuo profilo pubblico, condivisibile anche fuori dal sito: foto, livello, badge, biografia e le tue sfoglie. Si attiva una volta sola spendendo dei token dal tuo saldo — lo stesso credito che usi per le consulenze private con i maestri.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Badge',
			'categoria' => 'sfoglina',
			'testo'     => 'Pagina di sola consultazione: i medaglioni già sbloccati sono a colori pieni, quelli ancora da sbloccare sono in grigio. Ogni scheda spiega cosa serve fare per sbloccarlo.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Barometro Stagionale',
			'categoria' => 'sfoglina',
			'testo'     => 'Pagina di sola consultazione: le guide a cui hai già accesso sono aperte, quelle bloccate mostrano un lucchetto con la condizione per sbloccarle. Si sblocca partecipando a una sfida nel trimestre giusto, non c\'è nulla da cliccare.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Buono Sfoglia',
			'categoria' => 'sfoglina',
			'testo'     => 'Raggiungi 2500 punti in un mese e vinci il Buono Sfoglia: 2,5% di sconto su un Corso Base o Avanzato di gennaio, cumulabile ogni mese in cui raggiungi di nuovo la soglia. Va speso entro l\'anno in corso: se non riesci a partecipare al corso di gennaio stabilito, per qualsiasi motivo, il Buono non è più riutilizzabile.',
			'fonte'     => 'includes/buono-sfoglia.php',
		),
		array(
			'titolo'    => 'Calendario Corsi',
			'categoria' => 'sfoglina',
			'testo'     => 'Scegli una data e prenota: riceverai un\'email con le istruzioni per il bonifico dell\'acconto e, il giorno prima del corso, un promemoria automatico. Se un corso è al completo puoi metterti in lista d\'attesa. Oltre ai corsi trovi qui anche gli esami pratici e i confronti dal vero presso la scuola reale, segnalati con un\'etichetta dedicata. Il "🎖️ Corso Professionale (una settimana)" rilascia, a chi lo frequenta, l\'Attestato di Corso Professionale: lo trovi pronto da aprire e stampare in "Le mie prenotazioni" non appena l\'Accademia te lo assegna. Le condizioni di disdetta sono scritte in fondo alla pagina.',
			'fonte'     => 'includes/calendario.php',
		),
		array(
			'titolo'    => 'Classifica',
			'categoria' => 'sfoglina',
			'testo'     => 'La tabella è paginata: usa "Vedi tutti" per l\'elenco completo. Clicca sul nome di una sfoglina per aprire la sua Vetrina pubblica. Più sotto trovi la classifica a squadre e, se non ne hai ancora una, il menu per sceglierla. Durante la Sfida del Silenzio questa classifica resta ferma a com\'era all\'inizio del periodo: i punti continuano ad arrivare normalmente, semplicemente non li vedi muoversi qui finché il periodo non finisce.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Compleanni',
			'categoria' => 'sfoglina',
			'testo'     => 'Pagina che si aggiorna da sola ogni giorno. Se qualcuna festeggia, lo scopri anche senza aprire questa pagina: un annuncio "🎂 Oggi festeggia..." attraversa lo schermo di tutte le sfogline, e cliccandoci sopra si arriva dritti qui. Più sotto trovi la vetrina e la bacheca degli auguri, dove puoi lasciarle un messaggio. Se non hai ancora impostato la tua data di nascita, in fondo trovi il modulo per farlo.',
			'fonte'     => 'includes/compleanni.php',
		),
		array(
			'titolo'    => 'Consigli della Community',
			'categoria' => 'sfoglina',
			'testo'     => 'Se il tuo account è approvato puoi condividere un consiglio dal modulo qui sopra. I consigli di tutta la community sono qui sotto: titolo cliccabile per aprirli, e sono ricercabili.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Conversazioni',
			'categoria' => 'sfoglina',
			'testo'     => 'Clicca su una conversazione per aprirla e rispondere. Le richieste nuove restano in attesa finché l\'esperto non le approva; da quel momento potete scrivervi a vicenda in privato.',
			'fonte'     => 'includes/conversazioni.php',
		),
		array(
			'titolo'    => 'Cronologia',
			'categoria' => 'sfoglina',
			'testo'     => 'Pagina di sola consultazione, pensata per ripercorrere quello che hai già fatto: nessun modulo da compilare. Scorri i riquadri per vedere livello, badge, cronologia punti, ricette approvate e lezioni guardate. La tabella della cronologia punti è paginata, usa "Vedi tutti" per vederla tutta.',
			'fonte'     => 'includes/cronologia.php',
		),
		array(
			'titolo'    => 'Diario dell\'Impasto',
			'categoria' => 'sfoglina',
			'testo'     => 'Scrivi un titolo (facoltativo) e il testo, poi "Salva nel diario". Le voci che hai già scritto sono più sotto, con titolo cliccabile: aprine una per modificarla o eliminarla. Puoi segnare una voce come "ricetta di famiglia originale" per farla notare ai maestri. Se un anno fa, in questo stesso giorno, avevi già scritto qualcosa, lo trovi in un riquadro qui sopra: è privato, solo tu lo vedi.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'FAQ',
			'categoria' => 'sfoglina',
			'testo'     => 'Le domande più frequenti sull\'Accademia della Sfoglia, raggruppate per argomento. Clicca su una domanda per aprire la risposta. Se non trovi quello che cerchi, scrivi alla segreteria dalla sezione "Aiuto".',
			'fonte'     => 'includes/faq.php',
		),
		array(
			'titolo'    => 'Galleria della Sfida (Voto)',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui trovi tutte le sfoglie inviate per la sfida attiva. Di solito, se sei loggata e la richiesta è già stata approvata, puoi votare quelle delle altre (non la tua) assegnando un punteggio per ogni criterio: la media dei voti ricevuti aggiorna la posizione in classifica. Per alcune sfide il titolare può però scegliere di far giudicare solo i maestri (titolare e collaboratori): in quel caso non trovi il modulo di voto, solo la galleria.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Galleria delle Sfoglie',
			'categoria' => 'sfoglina',
			'testo'     => 'Usa i tre menu a tendina (stagione, ingrediente, squadra) e premi "Filtra" per restringere i risultati. Clicca sul nome dell\'autrice sotto ogni foto per aprire la sua Vetrina pubblica.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'I Testamenti delle Maestre',
			'categoria' => 'sfoglina',
			'testo'     => 'Chi raggiunge il livello più alto dell\'Accademia può lasciare un testamento: un consiglio o un\'eredità per chi arriva dopo. Qui trovi quelli già scritti, proposti anche alle nuove iscritte.',
			'fonte'     => 'includes/testamento-sfoglina.php',
		),
		array(
			'titolo'    => 'I Tuoi Lavori Inviati',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui trovi tutti i lavori che hai inviato finora, in tutte le sezioni. Usa la ricerca per ritrovarne uno per nome; da ogni riga puoi spostarlo nel cestino, recuperabile in ogni momento dal riquadro "Il Mio Cestino" qui sotto.',
			'fonte'     => 'includes/sfogline-extra.php',
		),
		array(
			'titolo'    => 'I Tuoi Promemoria Personali',
			'categoria' => 'sfoglina',
			'testo'     => 'I tuoi promemoria personali: scrivili, spuntali quando li hai fatti, cancellali. Anche quelli eliminati restano recuperabili, in "🗑️ Cose eliminate" qui sotto.',
			'fonte'     => 'includes/sfogline-extra.php',
		),
		array(
			'titolo'    => 'Il Matterello Parlante',
			'categoria' => 'sfoglina',
			'testo'     => 'Un archivio di ricordi e consigli registrati a voce, non scritti: la voce di chi li racconta fa parte del ricordo. Registra direttamente dal browser (se supportato) oppure carica un file audio già pronto: puoi sempre riascoltare quello scelto e rimuoverlo per rifarlo, prima di inviarlo. Le registrazioni vengono ascoltate dai maestri prima di comparire nell\'archivio pubblico. Sotto ogni registrazione pubblicata si può chattare a testo per commentarla — non è possibile aggiungere altro audio nei commenti, solo nella registrazione originale. La registrazione diretta funziona sui browser più comuni (Chrome, Edge, Opera, Firefox recenti); se il tuo browser non la supporta, usa comunque il caricamento file.',
			'fonte'     => 'includes/matterello-parlante.php',
		),
		array(
			'titolo'    => 'Il Tavolo di Lavoro',
			'categoria' => 'sfoglina',
			'testo'     => 'Carica una foto del tuo lavoro di oggi — la sfoglia tirata, un piatto, un dettaglio — non serve che sia perfetta. È privata: la vedi solo tu, insieme al commento che un maestro ti lascerà apposta appena la vede. Puoi caricarne quante vuoi: ogni foto avrà il suo commento. I punti del gioco si prendono una volta al giorno, ma le foto no — carica pure quando ti va.',
			'fonte'     => 'includes/tavolo.php',
		),
		array(
			'titolo'    => 'Il Tuo Account',
			'categoria' => 'sfoglina',
			'testo'     => 'Da qui puoi cambiare la tua password, aggiornare l\'email con cui accedi, scaricare un riepilogo dei tuoi dati (nome, punti, livello, squadra…) o chiedere alla segreteria di eliminare il tuo account. La cancellazione non è mai immediata: la richiesta arriva alla segreteria, che la gestisce a mano, così nessun account sparisce per un clic sbagliato.',
			'fonte'     => 'includes/account.php',
		),
		array(
			'titolo'    => 'Il Tuo Anno in Accademia',
			'categoria' => 'sfoglina',
			'testo'     => 'Un racconto (non una classifica) di quello che hai fatto quest\'anno: punti guadagnati, badge sbloccati, ricette inviate, opere pubblicate e la tua posizione nella classifica dell\'anno. Si aggiorna via via che l\'anno prosegue.',
			'fonte'     => 'includes/riepilogo-anno.php',
		),
		array(
			'titolo'    => 'Il Tuo Cestino',
			'categoria' => 'sfoglina',
			'testo'     => 'Quando cancelli un tuo lavoro, finisce qui invece di sparire per sempre. Cercalo per nome e premi "Ripristina" per riportarlo esattamente com\'era prima.',
			'fonte'     => 'includes/sfogline-extra.php',
		),
		array(
			'titolo'    => 'Il Tuo Profilo',
			'categoria' => 'sfoglina',
			'testo'     => 'Il tuo profilo: foto, livello raggiunto, squadra, barra di avanzamento verso il livello successivo e i tuoi badge. Sale da solo appena raggiungi la soglia di punti del livello successivo.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Indovina la Sfoglia',
			'categoria' => 'sfoglina',
			'testo'     => 'Una domanda-lampo al giorno, uguale per tutte: rispondi con le tue parole, il sistema ti dice subito se è esatta e in quel caso ti dà qualche punto. Una sola risposta al giorno; la domanda cambia da sola a mezzanotte — torna domani per la prossima.',
			'fonte'     => 'includes/indovina.php',
		),
		array(
			'titolo'    => 'Ingrediente Segreto',
			'categoria' => 'sfoglina',
			'testo'     => 'Ogni venerdì un ingrediente a sorpresa da usare in una ricetta. Finché non è svelato vedi solo il conto alla rovescia; puoi anche attivare "Avvisami quando si svela" per ricevere una notifica appena viene pubblicato.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Iscrizione',
			'categoria' => 'sfoglina',
			'testo'     => 'Compila tutti i campi richiesti e invia: il tuo account resta in attesa finché la segreteria non lo controlla e lo approva. Riceverai accesso completo solo dopo l\'approvazione.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'L\'Esperto Risponde',
			'categoria' => 'sfoglina',
			'testo'     => 'Questa è una consulenza privata: la tua domanda e la risposta le vedete solo tu e il maestro, mai le altre sfogline. Ogni domanda consuma dei token dal tuo credito (il numero lo decide il maestro per il suo canale). Scrivi la domanda vera e propria nell\'Oggetto: se nel testo scrivi più di una domanda, viene considerata solo quella dell\'Oggetto. Se non hai token, fai un contributo associativo con bonifico (causale «CONTRIBUTO A SOSTEGNO DELL\'ASSOCIAZIONE ACCADEMIA DELLA SFOGLIA») e chiedi alla segreteria di accreditarti il credito. Se la domanda resta senza risposta, il token ti viene restituito.',
			'fonte'     => 'includes/esperti.php',
		),
		array(
			'titolo'    => 'La Cassaforte del Sapere',
			'categoria' => 'sfoglina',
			'testo'     => 'Ogni cassaforte si apre solo quando un numero sufficiente di sfogline raggiunge insieme un livello minimo — non basta il tuo livello personale, conta quante siete tutte insieme. Finché non si apre vedi solo quante ne mancano; una volta aperta, il contenuto resta visibile a tutte per sempre.',
			'fonte'     => 'includes/cassaforte-sapere.php',
		),
		array(
			'titolo'    => 'La Giuria a Turno',
			'categoria' => 'sfoglina',
			'testo'     => 'Ogni turno ha dei giudici assegnati dal titolare: o una piccola giuria di sfogline a rotazione, o i maestri (titolare e collaboratori), a seconda del turno. Se tocca a te giudicare, il tuo voto non è anonimo: il tuo nome resta accanto al punteggio, insieme alla motivazione che scrivi. Se non sei tra le giudici di questo turno, puoi comunque partecipare inviando la tua opera e leggere i voti ricevuti.',
			'fonte'     => 'includes/giuria-turno.php',
		),
		array(
			'titolo'    => 'La Mia Sfoglia',
			'categoria' => 'sfoglina',
			'testo'     => 'Questa è la tua pagina principale. In testa trovi la tua "carta d\'identità": foto, livello e tutti i tuoi numeri insieme (punti, streak, scudi salva-streak, token, badge, sconto sui corsi), con "Prossimo passo" — un piccolo suggerimento su cosa fare, che cambia da solo in base alla tua situazione. Sotto, quattro fasce colorate: "Oggi" (missioni e ingrediente segreto — il motivo per cui apri la pagina ogni giorno), "Il tuo percorso" (livelli, badge, premi), "Le tue sfide" e "I tuoi strumenti" (promemoria, cestino, vetrina, account) — richiudibili una per una. Le pillole in alto ti portano dritta alla fascia che cerchi. Ogni 4 settimane di streak consecutive guadagni uno scudo salva-streak: se una settimana la salti, uno scudo la copre in automatico e la tua serie non si azzera.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'La Sfoglia Misurata',
			'categoria' => 'sfoglina',
			'testo'     => 'Sfide di tecnica pura, non di gradimento: dichiara la tua misura (a fiducia, come per tutte le altre missioni del sito) e la classifica si ordina da sola in base al numero. Puoi rimandare una misura nuova finché la sfida non viene chiusa: l\'ultima che invii sostituisce la precedente.',
			'fonte'     => 'includes/sfoglia-misurata.php',
		),
		array(
			'titolo'    => 'La Sfoglia che Insegna Se Stessa',
			'categoria' => 'sfoglina',
			'testo'     => 'Racconta onestamente un errore che hai fatto e cosa hai imparato: sbagliare fa parte del percorso. Se il titolare lo ritiene un errore comune e utile alle altre, lo promuove a materiale didattico ufficiale — con il credito a te, che l\'hai segnalato. Qui sotto trovi quelli già promossi.',
			'fonte'     => 'includes/sfoglia-insegna.php',
		),
		array(
			'titolo'    => 'La Tua Biografia (Vetrina)',
			'categoria' => 'sfoglina',
			'testo'     => 'Scrivi qui il testo e carica la foto che vuoi mostrare nella tua Vetrina pubblica. Ogni modifica torna in attesa di approvazione: finché un\'amministratrice non la rivede, resta visibile solo a te, non sulla Vetrina.',
			'fonte'     => 'includes/biografia.php',
		),
		array(
			'titolo'    => 'Le Letture dei Grandi Protagonisti della Cucina',
			'categoria' => 'sfoglina',
			'testo'     => 'Riflessioni, ricordi e racconti pubblicati dai giornalisti e dai collaboratori dell\'Accademia. Sono visibili a chiunque, anche senza account. Per rispondere sotto una lettura, con una chat pubblica a bolle come su WhatsApp, serve essere una sfoglina iscritta oppure iscriversi con l\'account leggero "solo per commentare" (senza quota associativa, senza accesso al resto del gaming). Chi gestisce il portale può bloccare o sospendere temporaneamente un account "solo per commentare" in caso di abuso.',
			'fonte'     => 'includes/letture.php',
		),
		array(
			'titolo'    => 'Le Sfogline',
			'categoria' => 'sfoglina',
			'testo'     => 'Cerca una sfoglina per nome, oppure filtra per livello. In cima trovi le sfogline con la Vetrina pubblica attiva, ordinate per cognome: clicca sulla loro scheda per aprirla. Le altre schede sono di sola consultazione.',
			'fonte'     => 'includes/sfogline-extra.php',
		),
		array(
			'titolo'    => 'Libreria Video delle Lezioni',
			'categoria' => 'sfoglina',
			'testo'     => 'Cerca per nome o tecnica, poi clicca su "Guarda la lezione" per aprire il video direttamente nella pagina. Aprire una lezione la segna come vista e ti dà qualche punto. Se la lezione ha delle domande di verifica (scritte da Rina o Bruno), le trovi subito sotto al video: rispondi con le tue parole, il sistema ti dice subito se è esatta e in quel caso ti assegna dei punti; un maestro può comunque leggere le tue risposte e lasciarti un riscontro in più. Se vedi "📌 Consigliata per te" su una scheda, un maestro te l\'ha consigliata apposta: se non la apri entro qualche giorno ricevi un promemoria. Se ci sono "Percorsi Guidati", vederli tutti in ordine sblocca un badge dedicato; l\'etichetta accanto al nome del percorso (es. "Base", "Intermedio") indica quanto è impegnativo, ma non cambia l\'ordine di sblocco. Completandoli tutti sblocchi anche il Diploma Finale, stampabile. Alcune lezioni possono comparire con la scritta "🗓️ Disponibile dal…": sono pubblicate a puntate, un pezzo alla volta, e si aprono da sole a partire da quel giorno — torna a trovarle. Se vedi la sezione "🎄 Percorsi Stagionali", sono contenuti speciali disponibili solo per un periodo limitato: aperti a tutti da subito (senza dover finire prima gli altri), ma solo finché dura la finestra — dopo tornano nascosti. Se vedi la sezione "🎽 Percorsi di Squadra", l\'avanzamento è di tutta la tua squadra insieme: basta che una compagna qualsiasi veda una lezione perché conti per il gruppo, e quando la squadra li finisce tutti, il badge e i punti arrivano a ognuna.',
			'fonte'     => 'includes/lezioni-video.php',
		),
		array(
			'titolo'    => 'Madrina & Allieva',
			'categoria' => 'sfoglina',
			'testo'     => 'Voi due siete state abbinate dall\'Accademia per un percorso di affiancamento: qui potete aggiungere insieme piccole missioni, modificarne il testo e segnarle fatte quando le completate. Ogni missione completata vale qualche punto per entrambe. Anche le missioni eliminate restano recuperabili, in "🗑️ Missioni eliminate" qui sotto.',
			'fonte'     => 'includes/madrina.php',
		),
		array(
			'titolo'    => 'Messaggi',
			'categoria' => 'sfoglina',
			'testo'     => 'Apri un messaggio per leggerlo: viene segnato come letto automaticamente. Il messaggio della segreteria e le tue risposte compaiono a bolle, come nelle Conversazioni: crema il messaggio ricevuto, verde le tue risposte. Puoi rispondere direttamente sotto ogni messaggio. Qualche messaggio speciale (un premio per un livello o un badge raggiunto) porta con sé anche un video, che vedi direttamente qui dentro. Se hai anche una conversazione privata con un esperto, la trovi più sotto e puoi rispondere direttamente da lì.',
			'fonte'     => 'includes/messaggi.php',
		),
		array(
			'titolo'    => 'Missioni Giornaliere',
			'categoria' => 'sfoglina',
			'testo'     => 'Le missioni si aggiornano da sole mentre usi il sito, non c\'è nulla da avviare a parte. Clicca su una missione per andare dritta alla pagina dove si compie davvero (votare, scrivere nel Diario, condividere un consiglio, commentare la sfida). Quando la completi, la riga si segna come fatta e i punti vengono assegnati in automatico.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Novità',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui trovi gli ultimi annunci di chi gestisce il portale: nuove sezioni, cambiamenti, avvisi importanti. Clicca su un titolo per leggere il testo completo.',
			'fonte'     => 'includes/novita.php',
		),
		array(
			'titolo'    => 'Percorso di Formazione',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui vedi il percorso di formazione dell\'Accademia della Sfoglia: le tappe in cui sono raggruppate le Lezioni Video, una dopo l\'altra. Le lezioni vere e proprie si vedono solo con l\'abbonamento attivo — questa pagina è solo la mappa del cammino, pensata anche per chi non si è ancora iscritto. Se compare "🎄 Attivi ora, per tempo limitato", sono Percorsi Stagionali: contenuti speciali visibili solo per un periodo, poi tornano nascosti. Se compare "🎽 Percorsi di Squadra", si completano tutti insieme: basta che qualcuno della squadra veda una lezione perché conti per tutti i compagni.',
			'fonte'     => 'includes/percorsi-lezioni.php',
		),
		array(
			'titolo'    => 'Promemoria Giornaliero',
			'categoria' => 'sfoglina',
			'testo'     => 'Se lo attivi, quando arriva l\'ora che scegli e non ti sei ancora collegata oggi, ti mandiamo un avviso (via email o come messaggio interno, secondo le tue preferenze di notifica). Se ti sei già collegata, non arriva nulla: non è un fastidio, è solo per non dimenticarsi.',
			'fonte'     => 'includes/promemoria.php',
		),
		array(
			'titolo'    => 'Registro Ufficiale',
			'categoria' => 'sfoglina',
			'testo'     => 'Il Registro si divide in due rami, con criteri diversi: il Registro degli Amatori (Attestato di Corso Base o Corso Intermedio dal Calendario Corsi, oppure Attestato di Frequenza Intermedia da Rina Online) e il Registro dei Professionisti (Attestato di Corso Professionale, oppure il percorso privato dei Corsi Online fino alla Laurea in Sfoglia). Tutte le iscritte al sito, invece, sono nella sezione «Le Sfogline» — un elenco ancora diverso.',
			'fonte'     => 'includes/registro.php',
		),
		array(
			'titolo'    => 'Ricerca nel Sito',
			'categoria' => 'sfoglina',
			'testo'     => 'Scrivi almeno 3 lettere nel campo qui sotto: dopo una breve pausa la ricerca parte da sola, non serve premere Invio. I risultati compaiono raggruppati per sezione, ciascuno con un pulsante "Vai alla sezione" per aprire direttamente quella giusta.',
			'fonte'     => 'includes/ricerca-globale.php',
		),
		array(
			'titolo'    => 'Ricettario delle Famiglie',
			'categoria' => 'sfoglina',
			'testo'     => 'Usa la lente di ricerca per trovare una ricetta per nome, provenienza o famiglia. Per inviarne una nuova, compila il modulo qui sotto: le tue ricette inviate compaiono subito dopo, con lo stato aggiornato (in attesa, approvata, non approvata). Se indichi la regione italiana nel modulo, quando la ricetta è approvata colora quella regione sulla tua Mappa dei Territori, in cima alla pagina: pubblica una ricetta approvata per ognuna delle 20 regioni e, se sei la prima ad accenderle tutte, vinci 50 punti. In cima trovi anche la Ricetta del Mese, scelta dai maestri tra quelle approvate; sotto, un archivio con quelle dei mesi passati. Se la tua ricetta nasce da un\'altra già nel Ricettario (una variante, un\'evoluzione), puoi indicarlo nel modulo: comparirà "Nata da…" sotto la ricetta, e quella originale mostrerà a sua volta quali ricette sono nate da lei.',
			'fonte'     => 'includes/ricettario.php',
		),
		array(
			'titolo'    => 'Sconto sui Corsi',
			'categoria' => 'sfoglina',
			'testo'     => 'Vincendo badge speciali, guadagni sconti sui corsi di Rina Poletti: si accumulano finché non ti iscrivi davvero a un corso di quel livello. Si parte dal corso Base; una volta usato lo sconto su un corso, quello successivo che guadagni si riferisce al corso Avanzato, poi al Professionale. Attenzione: lo sconto accumulato durante l\'anno va speso entro il 24 dicembre, altrimenti si azzera.',
			'fonte'     => 'includes/sconto-corsi.php',
		),
		array(
			'titolo'    => 'Sfida della Settimana',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui trovi la sfida della settimana attiva, con il tempo rimanente. Se puoi partecipare, in fondo trovi il modulo per inviare la tua sfoglia (titolo, descrizione, foto). Nella galleria sotto puoi votare le sfoglie delle altre, non la tua, una sola volta per sfoglia.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Sondaggi',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui trovi i sondaggi aperti dalla segreteria: scegli la proposta che preferisci e vota, un solo voto per sondaggio. Se il sondaggio lo permette, puoi anche scrivere una tua proposta: si aggiunge a quelle già presenti e diventa votabile da tutte. Se i risultati sono pubblici li vedi subito qui sotto; se sono riservati, restano visibili solo alla segreteria dal Pannello Generale, ma il tuo voto conta comunque.',
			'fonte'     => 'includes/sondaggi.php',
		),
		array(
			'titolo'    => 'Squadre Regionali',
			'categoria' => 'sfoglina',
			'testo'     => 'I punti di ogni squadra sono la somma dei punti di tutte le sue sfogline. La mappa dell\'Italia parte bianca: si colorano solo le regioni della squadra in testa alla classifica, nel suo colore — finché nessuna squadra ha punti resta tutta bianca. Se non fai ancora parte di una squadra, più sotto trovi il modulo per scegliere la tua.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Streak del Matterello',
			'categoria' => 'sfoglina',
			'testo'     => '"Streak" è un termine inglese (letteralmente "striscia", "serie") usato nei giochi e nelle app per indicare una sequenza di volte consecutive in cui fai una certa cosa, senza interruzioni — più vai avanti senza saltare un turno, più lo streak cresce; se salti una volta, di solito si azzera e riparti da capo. Esempi comuni: un\'app per imparare una lingua può contare quanti giorni di fila fai un esercizio; una squadra sportiva ha uno "streak" quando vince partite una dietro l\'altra senza interruzioni; un\'app per tenersi in contatto con qualcuno può contare i giorni di fila in cui vi scrivete almeno un messaggio. Qui conta le settimane invece dei giorni: ogni settimana (da lunedì a domenica) in cui pubblichi almeno una sfoglia, lo Streak sale di 1. Se invece salti una settimana intera senza pubblicare nulla, lo Streak torna a zero — a meno che tu non abbia uno scudo salva-streak: ne guadagni uno ogni 4 settimane di fila, e copre da solo la settimana saltata, senza far tornare lo Streak a zero.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Testimonianze',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui sotto trovi le impressioni di sfogline e sfoglini che hanno fatto un percorso all\'Accademia della Sfoglia, raccontate con parole loro. Se anche tu hai seguito un percorso e hai effettuato l\'accesso, più in basso trovi il modulo per lasciare la tua testimonianza (testo libero + come vuoi firmarti, facoltativo) e l\'elenco di quelle che hai già inviato.',
			'fonte'     => 'includes/testimonianze.php',
		),
		array(
			'titolo'    => 'Traguardi',
			'categoria' => 'sfoglina',
			'testo'     => 'Qui vedi gli ultimi risultati raggiunti dalle sfogline di tutta l\'Accademia: diplomi conseguiti e badge sbloccati, i più recenti in cima. Pagina pubblica, come la Vetrina e il Registro Ufficiale.',
			'fonte'     => 'includes/traguardi.php',
		),
		array(
			'titolo'    => 'Un Anno Fa Oggi',
			'categoria' => 'sfoglina',
			'testo'     => 'Ogni giorno questa pagina mostra le ricette del Ricettario delle Famiglie che sono state approvate esattamente un anno fa, in questo stesso giorno. È un piccolo promemoria comunitario di quello che l\'Accademia condivideva un anno fa — cambia da solo ogni giorno, non c\'è nulla da configurare.',
			'fonte'     => 'includes/anno-fa-oggi.php',
		),
		array(
			'titolo'    => 'Vetrina (profilo pubblico)',
			'categoria' => 'sfoglina',
			'testo'     => 'La Vetrina è il tuo profilo pubblico: foto, livello, badge, biografia (se approvata) e le tue sfoglie pubbliche. Il link qui sotto è pronto da copiare e condividere anche con chi non è iscritta all\'Accademia.',
			'fonte'     => 'includes/shortcodes.php',
		),
		array(
			'titolo'    => 'Vetrina Pubblica',
			'categoria' => 'sfoglina',
			'testo'     => 'Questa è la pagina pubblica che puoi condividere con chi non è iscritto all\'Accademia: il link è pronto per essere copiato dalla tua pagina "La Mia Sfoglia". È di sola consultazione.',
			'fonte'     => 'includes/shortcodes.php',
		),
	);
}

// -----------------------------------------------------------------------------
// PANNELLO — raccoglitore con ricerca e invio (titolare/collaboratori)
// -----------------------------------------------------------------------------
function gs_pannello_spiegazioni() {
	if ( ! gs_can_manage() ) { return; }

	echo gs_box_open( '📖 Spiegazioni delle Sezioni', '', 'gs-box-spiegazioni' );
	echo gs_sezione_aiuto( 'Il testo che ogni sfoglina vede come aiuto dentro ciascuna sezione, tutto in un posto solo. Cerca per nome o per una parola del testo, apri la voce che ti serve: dentro trovi il testo pronto (puoi modificarlo prima di mandarlo) e tre modi per farlo arrivare — come messaggio privato a chi vuoi (con lo stesso sistema della Posta Interna, lo trova nella sua casella «Messaggi»), oppure fuori dal sito via email o WhatsApp, a chiunque, anche a chi non è iscritta.' );

	echo '<input type="text" class="gs-cerca-input" data-target=".gs-spiegazioni-lista" placeholder="🔍 Cerca una sezione o una parola nel suo testo…" style="width:100%;max-width:420px;margin-bottom:10px">';

	$voci = gs_spiegazioni_elenco();
	usort( $voci, function ( $a, $b ) { return strcasecmp( $a['titolo'], $b['titolo'] ); } );

	$utenti = get_users( array( 'orderby' => 'display_name' ) );

	echo '<div class="gs-inbox-lista gs-spiegazioni-lista gs-paginate" data-per-page="15">';
	foreach ( $voci as $i => $v ) {
		$badge = 'gestore' === $v['categoria'] ? '🛠️ pannello' : '🧑‍🍳 sfoglina';
		echo '<details class="gs-inbox-item" data-nome="' . esc_attr( strtolower( $v['titolo'] . ' ' . $v['testo'] ) ) . '">';
		echo '<summary class="gs-inbox-oggetto"><span class="gs-mod-badge">' . esc_html( $badge ) . '</span> ' . esc_html( $v['titolo'] ) . '</summary>';
		echo '<div class="gs-inbox-corpo">';
		echo '<form class="gs-form gs-form-messaggio" onsubmit="return false">';
		// Niente opzione vuota: gs_invia_messaggio() tratta il destinatario 0
		// come "manda a TUTTE" (è così che funziona la Posta Interna), quindi
		// un campo lasciato vuoto per distrazione manderebbe il messaggio a
		// tutti invece che a chi si voleva davvero. La prima sfoglina in
		// ordine alfabetico resta selezionata di default: sempre un
		// destinatario vero, mai un valore ambiguo (trovato 26/08/2026).
		echo '<p><label>Destinatario<br><select name="dest" style="min-width:260px">';
		foreach ( $utenti as $u ) {
			echo '<option value="' . (int) $u->ID . '">' . esc_html( $u->display_name ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p><label>Oggetto<br><input type="text" name="oggetto" autocomplete="off" style="width:100%;max-width:420px" value="' . esc_attr( $v['titolo'] ) . '"></label></p>';
		echo '<p><label>Testo (puoi modificarlo prima di mandarlo)<br><textarea name="testo" rows="5" style="width:100%">' . esc_textarea( $v['testo'] ) . '</textarea></label></p>';
		echo '<p><button class="gs-btn gs-btn-sm gs-btn-verde gs-invia-messaggio">Invia come messaggio</button> <span class="gs-messaggio-msg gs-richiesta-esito"></span></p>';
		echo '</form>';

		// Fuori dal sito, a chiunque — non solo a chi ha un account: due link
		// diretti, nessun JavaScript nuovo, sempre funzionanti perché non
		// dipendono da nessuna funzione del browser (a differenza della Web
		// Share API già usata altrove nel plugin per le locandine, che su
		// molti browser desktop non supporta ancora la condivisione).
		// api.whatsapp.com/send, senza numero, apre WhatsApp e lascia
		// scegliere il contatto: è il link di condivisione pubblico usato
		// ovunque sul web per questo scopo, non un endpoint privato.
		//
		// Separatore " — " invece di un a-capo: esc_url() toglie di proposito
		// gli a-capo codificati (%0a) dai link https:// per sicurezza (non
		// dai link mailto:, che li lascia), quindi un a-capo qui sparirebbe
		// SOLO dal link WhatsApp e non da quello email — titolo e testo si
		// sarebbero attaccati senza spazio, un difetto trovato provando il
		// link davvero, non leggendo il codice (26/08/2026).
		$corpo_condiviso = $v['titolo'] . ' — ' . $v['testo'];
		$link_mail = 'mailto:?subject=' . rawurlencode( $v['titolo'] ) . '&body=' . rawurlencode( $corpo_condiviso );
		$link_wa   = 'https://api.whatsapp.com/send?text=' . rawurlencode( $corpo_condiviso );
		echo '<p>';
		echo '<a class="gs-btn gs-btn-sm gs-btn-ghost" href="' . esc_url( $link_mail ) . '">📧 Invia per email</a> ';
		echo '<a class="gs-btn gs-btn-sm gs-btn-ghost" href="' . esc_url( $link_wa ) . '" target="_blank" rel="noopener">💬 Invia su WhatsApp</a>';
		echo '</p>';

		echo '</div></details>';
	}
	echo '</div>';
	echo gs_box_close();
}

