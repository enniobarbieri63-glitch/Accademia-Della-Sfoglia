# Consegna per il controllo di integrazione con Gaming Sfogline

Questo documento accompagna il pacchetto dei due plugin nuovi. È scritto
per chi li controlla direttamente sul sito, con accesso a WordPress e al
plugin `gaming-sfogline` già installato — cosa che chi ha scritto il
codice non ha avuto.

---

## 1. Cosa c'è nel pacchetto

| Cartella | Cos'è | Stato |
|---|---|---|
| `sfoglia-lab-aula/` | Classi virtuali per gli istituti alberghieri | Collaudato dal vivo, funziona da capo a fondo |
| `sfoglia-lab-pro/` | Corsi professionali, iscrizioni, attestati | Collaudato dal vivo per la parte sessioni; il resto provato per funzioni singole |
| `docs/` | Documenti di progetto, specifica, kit scuole, griglia parametri | — |

Sono due plugin WordPress separati, ognuno con il suo `readme.txt` che
elenca cosa contiene e cosa deliberatamente non contiene.

---

## 2. La cosa più importante da sapere prima di toccare qualcosa

**I due plugin sono indipendenti da Gaming Sfogline apposta, non per
pigrizia.** La separazione nasce da un vincolo commerciale preciso:

- Gaming Sfogline mostra pubblicità e vetrine di sponsor su ogni pagina, e
  non ha ruoli veri né accesso d'istituto.
- Un istituto alberghiero non compra — e in molti casi non *può* comprare —
  un prodotto che mostra pubblicità agli studenti minorenni durante l'ora di
  lezione.

Da qui la scelta: **Sfoglia Lab — Aula non deve mai mostrare pubblicità,
vetrine di sponsor o funzioni di community.** Se l'integrazione con Gaming
Sfogline le facesse rientrare dalla finestra, il prodotto per le scuole
smetterebbe di essere vendibile: sarebbe un danno commerciale, non un
miglioramento tecnico.

Quindi, integrando:

- **Si può fare**: condividere l'aspetto grafico, mettere collegamenti tra
  le pagine, far convivere i tre plugin sullo stesso sito, unificare un
  domani l'elenco dei certificati.
- **Non si deve fare**: rendere Aula o Pro dipendenti da Gaming Sfogline per
  funzionare, portare pubblicità o vetrine dentro Aula, unire i tre plugin
  in uno solo.

Se durante il controllo sembra che convenga fare una di queste ultime cose,
**è una decisione commerciale, non tecnica: va riportata a Ennio, non presa
per conto suo.**

---

## 3. Inventario dei nomi registrati (per verificare le collisioni)

I due plugin nuovi usano i prefissi `sla_` e `slp_`. Gaming Sfogline usa
`gs_`. Il primo controllo da fare è che non ci siano sovrapposizioni reali
con quello che Gaming Sfogline registra davvero sul sito.

### Sfoglia Lab — Aula (prefisso `sla_`)

| Tipo | Nomi |
|---|---|
| Shortcode | `sla_pannello_docente`, `sla_ingresso` |
| Tipi di contenuto | `sla_classe`, `sla_studente`, `sla_assegnazione`, `sla_tentativo` |
| Azioni AJAX | `sla_crea_classe`, `sla_aggiungi_studenti`, `sla_assegna_esercizio`, `sla_rigenera_codice`, `sla_libera_posto`, `sla_duplica_classe`, `sla_entra`, `sla_elenco_nickname`, `sla_consegna` |
| Capacità | `sla_gestisci_classi` |
| Script/stili | `sla-script`, `sla-stile` |
| Oggetto JS | `slaDati` |
| Cookie | `sla_sessione` |
| Costanti | `SLA_VERSION`, `SLA_FILE`, `SLA_DIR`, `SLA_URL`, `SLA_INC`, `SLA_OPTION`, `SLA_COOKIE`, `SLA_COOKIE_GIORNI` |

### Sfoglia Lab — Pro (prefisso `slp_`)

| Tipo | Nomi |
|---|---|
| Shortcode | `slp_pannello_gestore`, `slp_catalogo_corsi`, `slp_elenco_certificati` |
| Tipi di contenuto | `slp_sessione`, `slp_iscrizione`, `slp_certificato` |
| Azioni AJAX | `slp_crea_sessione`, `slp_cambia_stato_sessione`, `slp_elimina_sessione_diagnostica`, `slp_iscrivi`, `slp_registra_pagamento`, `slp_annulla_iscrizione`, `slp_rilascia_certificato`, `slp_salva_impostazioni` |
| Azione admin-post | `slp_esporta_iscritti` |
| Capacità | `slp_gestisci_corsi` |
| Script/stili | `slp-script`, `slp-stile` |
| Oggetto JS | `slpDati` |
| Opzione | `slp_settings` |
| Costanti | `SLP_VERSION`, `SLP_FILE`, `SLP_DIR`, `SLP_URL`, `SLP_INC`, `SLP_OPTION`, `SLP_ACCONTO_PERCENTUALE`, `SLP_ISCRIZIONI_PER_ORA` |

**Nota**: entrambi i file JS leggono l'attributo `data-sla-azione` (anche
quello di Pro, per ragioni storiche). Non è un errore e non causa
conflitti, perché i due script agiscono su pagine diverse e ciascuno
riconosce solo le proprie azioni — ma se in Gaming Sfogline esiste un
attributo con lo stesso nome, va verificato che non si pestino i piedi.

---

## 4. Cosa controllare sul sito, in ordine

1. **Attivare tutti e tre i plugin insieme** e guardare se WordPress segnala
   errori (`WP_DEBUG` acceso). Nessuna funzione dovrebbe risultare
   ridichiarata.
2. **Aprire le pagine di Gaming Sfogline** e verificare che continuino a
   funzionare come prima (le classifiche, la community, gli sponsor).
3. **Aprire il pannello docente di Aula e il pannello gestore di Pro** e
   verificare che non compaia nessun elemento di Gaming Sfogline: né
   pubblicità, né vetrine, né inviti alla community. Se compaiono, il tema o
   un hook globale di Gaming Sfogline sta iniettando contenuto in tutte le
   pagine: è esattamente il problema che ha portato a separare i prodotti, e
   va segnalato.
4. **Controllare la console del browser** sulle pagine dove convivono più
   plugin: errori JavaScript di uno possono fermare l'altro.
5. **Verificare i ruoli**: dopo l'attivazione di Pro, un utente con ruolo
   Editor **non** deve più vedere il pannello gestore (mostra contatti dei
   clienti e incassi). Se lo vede ancora, il plugin non è stato riattivato
   dopo l'aggiornamento.

---

## 5. Punti aperti su cui serve un occhio esterno

Sono cose che chi ha scritto il codice non ha potuto verificare, non avendo
accesso al sito reale:

- **Aspetto grafico dentro il tema The Newspaper (CMSMasters)**: i due
  plugin hanno uno stile proprio, calibrato sulla tavolozza del volantino
  dell'Accademia (rosso `#A93B27`, toni caldi). Va guardato come cade dentro
  il tema vero, soprattutto tabelle e pulsanti.
- **Email**: in Pro l'invio è spento finché non si accende in Impostazioni.
  Va provato che `wp_mail()` funzioni davvero su quel sito (molti hosting lo
  bloccano senza un servizio SMTP) prima di accenderlo su un sito con
  iscritti veri.
- **Le sessioni di prova già in archivio**: sul sito di prova ci sono
  sessioni salvate con il codice corso in minuscolo (`pro-1`), residuo di un
  errore già corretto. Non sono raggiungibili dall'elenco normale e si
  eliminano dal riquadro Diagnostica in fondo al pannello gestore.
- **Contenuti veri**: gli esercizi e i quiz di Aula sono segnaposto. I valori
  reali arrivano dalla giornata di rilevazione con Rina Poletti, Beppe Govoni
  e Gian Paolo Chiossi (vedi `docs/04-griglia-parametri-tecnici.md`). Non
  vanno inventati.

---

## 6. Come sono fatti i due plugin, in breve

Entrambi seguono le stesse scelte, utili da conoscere prima di metterci mano:

- **Nessuna libreria esterna.** Solo `fetch()` e `FormData`, niente jQuery,
  niente pacchetti da installare.
- **Logica di calcolo separata da WordPress.** I punteggi, i prezzi, gli
  acconti e le scadenze stanno in funzioni che non chiamano nessuna funzione
  di WordPress, e si provano da riga di comando senza un sito:
  `php tests/test-prezzi.php` e simili. Sono sette file di test, tutti
  verdi. **Se si tocca quella logica, i test vanno rieseguiti.**
- **I soldi sono sempre in centesimi interi**, mai in virgola mobile.
- **Lo storico dei pagamenti si aggiunge, non si sovrascrive mai.**
- **Ogni chiamata AJAX controlla il nonce e i permessi** lato server, non
  solo nell'interfaccia.
- **I commenti nel codice sono in italiano** e spiegano il perché, non il
  cosa. Conviene leggerli prima di cambiare qualcosa: diversi spiegano
  errori già commessi e corretti, e sarebbe un peccato rifarli.

---

## 7. Se qualcosa non funziona

Il pannello gestore di Pro ha in fondo un riquadro **Diagnostica**
(ripiegato) che elenca tutte le sessioni presenti nel database, comunque
siano messe. È lo strumento che ha permesso di trovare l'errore più
insidioso di tutto il progetto: serve a distinguere "il dato non è stato
salvato" da "il dato c'è ma qualcosa lo nasconde". Vale la pena guardarlo
prima di mettersi a leggere il codice.
