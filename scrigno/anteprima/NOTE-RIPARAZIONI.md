# Anteprima di Scrigno — cosa è stato riparato

L'aspetto approvato non è cambiato. Sono stati sistemati difetti di funzionamento e
aggiunte le cose che l'interfaccia prometteva ma non faceva.

## Difetti riparati

1. **La finestra Importa copriva tutto e non si chiudeva.** Era il problema più grosso:
   il foglio era marcato come nascosto, ma una regola di stile lo rendeva comunque
   visibile. All'apertura della pagina copriva l'intera app e nemmeno ✕, Esc o il clic
   fuori riuscivano a toglierlo. Ora si apre solo con "Importa" e si chiude in tutti e
   tre i modi previsti.

2. **Mancava l'intestazione del documento.** Niente `<!doctype>`, niente indicazione
   della codifica, niente lingua. Conseguenze: accenti e virgolette potevano uscire
   come segni strani (`Venerdì` → `VenerdÃ¬`), il browser passava in modalità di
   compatibilità vecchia, e chi usa il lettore vocale si sentiva leggere l'italiano con
   pronuncia inglese. Aggiunti doctype, codifica UTF-8, `lang="it"` e la riga per gli
   schermi stretti.

3. **La foto in Modifica invadeva il pannello dei cursori.** La foto veniva calcolata
   larga 855 px dentro uno spazio di 710 e copriva la colonna di destra: i nomi dei
   cursori (Esposizione, Contrasto, Temperatura…) sparivano sotto l'immagine. Ora la
   foto sta nello spazio che ha, centrata, mantenendo le proporzioni 3:2.

4. **Le scorciatoie ⌘1–⌘6 e ⌘F erano scritte ma non esistevano.** Erano stampate
   accanto ai nomi delle schermate e nel campo di ricerca, e non facevano nulla. Ora
   funzionano tutte e sei, e ⌘F porta il cursore nella ricerca della schermata aperta.

5. **Il cursore Vignettatura non si vedeva.** L'ombra veniva disegnata sotto
   l'immagine invece che sopra, quindi spostare il cursore non cambiava niente. Ora
   l'effetto è visibile.

6. **I cursori Nitidezza e Riduzione rumore non facevano niente.** Le funzioni grafiche
   di base non sanno fare né l'una né l'altra: sono stati collegati a un filtro vero.

7. **Metà della barra laterale era morta.** Audio, Altri file, Preferiti, Senza
   etichetta, Scontrini, i tre Album e le quattro Etichette non rispondevano al clic, e
   l'evidenziazione restava indietro. Ora ogni voce filtra la libreria o cambia
   schermata, e resta evidenziata quella giusta.

8. **I filtri in alto non erano cliccabili.** Erano scritte, non pulsanti. Ora filtrano
   davvero la griglia, i conteggi di ogni giornata si aggiornano e i giorni che restano
   senza elementi spariscono invece di lasciare intestazioni vuote.

9. **"Foto" nella barra laterale apriva l'editor.** Cliccare Foto portava nella
   schermata Modifica invece di mostrare le foto. Ora filtra la libreria; per modificare
   si fa doppio clic su una foto, come ci si aspetta.

10. **I conteggi delle giornate erano sbagliati.** L'intestazione diceva "18 elementi"
    sopra una griglia di 8. Ora il numero è quello vero.

11. **Niente era raggiungibile da tastiera.** Le miniature non si potevano selezionare,
    i nomi delle schermate non rispondevano alle frecce. Ora sì, con il bordo ambra che
    mostra dove sei.

## Aggiunte

- **Ricerca per nome nella Libreria**, che filtra mentre si digita.
- **Ricerca nei Documenti** che filtra l'elenco e aggiorna la riga dei risultati.
- **Ricerca nei Video** e **fra le Persone**.
- **Il pannello Informazioni segue la selezione**: clic su una miniatura e cambiano
  nome, giorno e anteprima. Per un documento o un audio i dati di scatto vengono
  nascosti, invece di mostrare diaframma e ISO di una foto che non c'è.
- **Schermata "niente qui"** quando un filtro non trova nulla, con un pulsante per
  tornare indietro. "Eliminati di recente" e "Altri file" sono vuoti apposta, e lo
  dicono.

## Come provarlo

Apri `index.html` in Safari o Chrome. Da provare in un minuto:

1. Premi ⌘2, ⌘3, ⌘4: cambiano le schermate.
2. Clicca **Viaggi** fra i filtri in alto: restano tre elementi.
3. Clicca **Eliminati di recente** in fondo alla barra laterale: appare il messaggio.
4. Scrivi `traghetto` nella ricerca in alto.
5. Clicca **Importa**, poi Esc.
6. ⌘3, apri "Vignettatura" in basso a destra e muovi il cursore.

## Verifiche automatiche

`prova.mjs` esegue 31 controlli su un browser vero (Chromium) e controlla, fra le altre
cose, tutti i difetti elencati sopra, così non tornano indietro.

```
npm i -D playwright && npx playwright install chromium
node prova.mjs
```

Serve una connessione per i caratteri di Google Fonts. Senza, la pagina funziona
comunque e usa i caratteri di riserva.
