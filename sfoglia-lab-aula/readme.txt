=== Sfoglia Lab — Aula ===
Contributors: accademiadellasfoglia
Tags: education, classroom
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later

Classi virtuali per gli istituti alberghieri: il docente crea una classe,
gli studenti entrano con un codice e un nickname (nessun dato personale),
svolgono gli esercizi assegnati e il docente esporta i punteggi.

== Descrizione ==

Prima versione di lavoro (0.1.0), corrisponde a un sottoinsieme dei
cantieri 4-5 della specifica in docs/02-specifica-classi-virtuali.md del
repository Accademia-Della-Sfoglia. Progetto indipendente dal plugin
"Gaming Sfogline": nessuna community, nessuna vetrina di sponsor, nessuna
pubblicità.

Cosa c'è in questa versione:

* Il docente crea una classe e ottiene un codice di ingresso a 6 caratteri.
* Il docente incolla un elenco di nickname (uno per riga) per aprire i posti.
* Lo studente entra con codice + nickname, senza password né dati personali.
* Il docente assegna un esercizio dal catalogo (un solo esercizio di
  esempio in questa versione: "Tagliatella — spessore della sfoglia", con
  valori segnaposto, non ancora validati dai maestri).
* Lo studente consegna un valore e riceve subito un punteggio 0-100,
  calcolato per scarto dal valore corretto con tre fasce di tolleranza
  (verde/giallo/rosso), e il messaggio del maestro per quella fascia.
* Il docente vede chi ha consegnato e con quale punteggio, ed esporta un
  CSV (con BOM e punto e virgola, per aprirsi bene in Excel italiano).

Cosa NON c'è ancora, deliberatamente:

* SSO con Google Workspace / Microsoft 365 dell'istituto (cantiere 8):
  in questa versione può gestire le classi chiunque abbia un account
  WordPress con ruolo Amministratore o Editor.
* Il quiz a scelta multipla e la banca di domande (cantiere 6).
* L'esportazione diretta verso i registri elettronici (Argo, Spaggiari).
* La vista "dove sbaglia la classe" (ha bisogno di più di un esercizio nel
  catalogo per avere senso).
* La dichiarazione di accessibilità WCAG 2.1 AA e la nomina a responsabile
  del trattamento (cantiere 9): da preparare prima di proporlo a una scuola
  vera.
* Un secondo dispositivo che riprende un posto già occupato: oggi serve
  che il docente lo liberi dal cruscotto (vedi il commento in cima a
  includes/ingresso.php).

== Installazione ==

1. Copia l'intera cartella `sfoglia-lab-aula` dentro `wp-content/plugins/`
   del sito WordPress (in Local: tasto destro sul sito → *Open site
   folder* → `app/public/wp-content/plugins/`).
2. Attiva il plugin da Bacheca → Plugin.
3. Crea due pagine e inserisci uno shortcode per ciascuna:
   - una pagina "Pannello Docente" con `[sla_pannello_docente]`
   - una pagina "Ingresso Classe" con `[sla_ingresso]`
4. Accedi come Amministratore o Editor e apri "Pannello Docente" per
   creare la prima classe.
