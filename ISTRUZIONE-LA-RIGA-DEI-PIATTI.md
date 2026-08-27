# Istruzione: la riga dei piatti nell'anteprima non concorda al plurale

**Per Claude Code Ennio — 27/08/2026, scritta su 3.298.1**

Una riga sola, e l'errore è nello snippet che avevo scritto io in
`ISTRUZIONE-L-ECCEZIONE-DI-RINA.md`: la sessione l'ha copiato fedelmente, com'era giusto.

In `assets/js/gaming.js`, dentro `gsRenderResetAnteprima( d )`, il nome è al plurale ma il
verbo e l'aggettivo sono rimasti al singolare. Con più di un piatto, l'anteprima scrive:

> «3 piatti in via d'estinzione **tornerà libero**: il piatto resta, la custode di prima no…»

Non è un difetto di funzionamento, ma è **la pagina che Ennio legge prima di premere il
pulsante che non si annulla**: è l'unico punto del plugin dove una frase sgrammaticata costa
qualcosa, perché è lì che si decide se fidarsi di quello che c'è scritto.

## La correzione

Sostituisci il blocco così com'è:

```js
		if ( d.piatti_da_liberare ) {
			var unoSolo = ( 1 === d.piatti_da_liberare );
			out += '<p>' + gsEsc( d.piatti_da_liberare )
				+ ( unoSolo ? ' piatto in via d\'estinzione tornerà libero: il piatto resta, la sua custode no, '
				            : ' piatti in via d\'estinzione torneranno liberi: i piatti restano, le custodi di prima no, ' )
				+ 'e chiunque potrà adottarl' + ( unoSolo ? 'o' : 'i' ) + ' di nuovo.</p>';
		}
```

Due frasi intere invece di pezzi incollati: costa tre parole in più e non si sgrammatica al
prossimo ritocco, che è il motivo per cui il pezzo di prima si è rotto.

## Come si prova

1. Nell'anteprima con **un solo** piatto adottato: «1 piatto in via d'estinzione tornerà
   libero… potrà adottarlo di nuovo».
2. Con **tre**: «3 piatti in via d'estinzione torneranno liberi… potrà adottarli di nuovo».
3. Con **nessuno**: la riga non compare affatto.

Le prime due si vedono nel browser su guru2; se il test dedicato controlla il testo reso, vale
anche lì.

## La consegna

Se hai altro in coda, consegnalo insieme: non vale una versione per una frase. Se no, versione
**3.298.2** nei tre punti e una riga di changelog che dica cosa si leggeva prima — «3 piatti
tornerà libero» — e non «migliorato un testo».

## Una cosa da non fare

**Non eseguire il Reset.** Vale ancora, e vale finché il pulsante non lo preme Ennio dopo il
backup.
