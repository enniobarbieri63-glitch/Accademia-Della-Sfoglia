import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const FILE = pathToFileURL(join(dirname(fileURLToPath(import.meta.url)), 'index.html')).href;
const errors = [];
const results = [];
const ok = (n, c, extra = '') => results.push(`${c ? 'OK  ' : 'FAIL'} ${n}${extra ? ' — ' + extra : ''}`);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
page.on('pageerror', e => errors.push('pageerror: ' + e.message));
// i caratteri di Google Fonts possono non caricarsi senza rete: non è un difetto della pagina
page.on('console', m => {
  if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) errors.push('console: ' + m.text());
});

await page.goto(FILE, { waitUntil: 'load' });
await page.waitForTimeout(700);

// 1. Il foglio Importa NON deve essere visibile all'apertura
ok('foglio Importa nascosto all\'avvio', !(await page.locator('#sheet').isVisible()));

// 2. Accenti leggibili (charset)
const t = await page.locator('#s-lib .day h2').first().textContent();
ok('accenti corretti (charset)', /Sabato 12 settembre/.test(t), t);
ok('lang="it"', (await page.getAttribute('html', 'lang')) === 'it');
ok('niente quirks mode', await page.evaluate(() => document.compatMode === 'CSS1Compat'), await page.evaluate(() => document.compatMode));

// 3. Apertura e chiusura del foglio Importa
await page.click('[data-import]');
ok('foglio Importa si apre', await page.locator('#sheet').isVisible());
await page.keyboard.press('Escape');
ok('foglio Importa si chiude con Esc', !(await page.locator('#sheet').isVisible()));

// 4. Scorciatoie da tastiera
for (const [k, id] of [['1', 's-lib'], ['2', 's-vid'], ['3', 's-edit'], ['4', 's-doc'], ['5', 's-people'], ['6', 's-dup']]) {
  await page.keyboard.press('Control+' + k);
  await page.waitForTimeout(120);
  ok(`⌘${k} apre ${id}`, await page.locator('#' + id).evaluate(e => e.classList.contains('on')));
}
await page.keyboard.press('Control+1');
await page.waitForTimeout(150);
await page.keyboard.press('Control+f');
ok('⌘F porta il cursore nella ricerca', await page.evaluate(() => document.activeElement?.id === 'q'));

// 5. Filtri
const visibili = () => page.locator('#s-lib .tile:not([hidden])').count();
ok('griglia piena all\'avvio', (await visibili()) === 19, String(await visibili()));
await page.click('#filters [data-filter="type:video"]');
await page.waitForTimeout(100);
ok('filtro Video', (await visibili()) === 3, String(await visibili()));
await page.click('.side nav .it[data-filter="tag:lav"]');
await page.waitForTimeout(100);
ok('filtro etichetta Lavoro', (await visibili()) === 2, String(await visibili()));
await page.click('.side nav .it[data-filter="type:altri"]');
await page.waitForTimeout(100);
ok('stato vuoto su "Altri file"', await page.locator('#empty').isVisible());
await page.click('#empty button');
await page.waitForTimeout(100);
ok('"Mostra tutto" ripristina', (await visibili()) === 19, String(await visibili()));

// 6. Ricerca per nome
await page.fill('#q', 'traghetto');
await page.waitForTimeout(100);
ok('ricerca per nome', (await visibili()) === 1, String(await visibili()));
await page.fill('#q', '');
await page.waitForTimeout(100);

// 7. Selezione e pannello Informazioni
await page.click('#s-lib .tile[data-name="Presentazione.key"]');
await page.waitForTimeout(150);
ok('Informazioni segue la selezione', (await page.locator('.insp h3').textContent()) === 'Presentazione.key');
ok('EXIF nascosto per un documento', !(await page.locator('.insp .exif').isVisible()));
await page.click('#s-lib .tile[data-name="IMG_4821.HEIC"]');
await page.waitForTimeout(150);
ok('EXIF torna per una foto', await page.locator('.insp .exif').isVisible());

// 8. Modifica: vignettatura e dettaglio
await page.keyboard.press('Control+3');
await page.waitForTimeout(300);
await page.evaluate(() => document.querySelectorAll('details.adj').forEach(d => d.open = true));
await page.waitForTimeout(100);
await page.locator('.sl input[data-k="vig"]').fill('80');
await page.waitForTimeout(150);
const vigOp = await page.locator('#vigOv').evaluate(e => getComputedStyle(e).opacity);
ok('vignettatura visibile', parseFloat(vigOp) > 0.3, 'opacity=' + vigOp);
await page.locator('.sl input[data-k="sha"]').fill('60');
await page.waitForTimeout(150);
const f = await page.locator('#after').evaluate(e => e.style.filter);
ok('nitidezza collegata al filtro SVG', /url\("?#fxDetail/.test(f), f.slice(-40));
const kern = await page.locator('#fxSharp').getAttribute('kernelMatrix');
ok('kernel di nitidezza aggiornato', kern !== '0 0 0 0 1 0 0 0 0', kern);
await page.click('#reset');
await page.waitForTimeout(150);
ok('Ripristina originale azzera la vignetta', parseFloat(await page.locator('#vigOv').evaluate(e => getComputedStyle(e).opacity)) === 0);

// 9. Ricerca documenti
await page.keyboard.press('Control+4');
await page.waitForTimeout(250);
await page.fill('#qdoc', 'noleggio');
await page.waitForTimeout(120);
ok('ricerca nei documenti', (await page.locator('#s-doc .ft').first().locator('tbody tr:not([hidden])').count()) === 1);
ok('intestazione risultati aggiornata', (await page.locator('#s-doc .found span').textContent()).includes('1 documento'));

// 10. Navigazione tab da tastiera
await page.keyboard.press('Control+1');
await page.waitForTimeout(150);
await page.locator('#tab-lib').focus();
await page.keyboard.press('ArrowRight');
await page.waitForTimeout(150);
ok('frecce fra i tab', await page.locator('#s-vid').evaluate(e => e.classList.contains('on')));

// 11. Nessuna barra di scorrimento orizzontale sul corpo a 1440
ok('niente scorrimento orizzontale della pagina',
  await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1));

// 12. La foto in Modifica non deve invadere il pannello dei cursori
await page.keyboard.press('Control+3');
await page.waitForTimeout(300);
const sconf = await page.evaluate(() => {
  const p = document.querySelector('.photo').getBoundingClientRect();
  const s = document.querySelector('.eside').getBoundingClientRect();
  return Math.round(p.right - s.left);
});
ok('la foto non copre il pannello Regolazioni', sconf <= 0, 'sconfinamento ' + sconf + 'px');

await page.keyboard.press('Control+1');
await page.waitForTimeout(400);
await page.screenshot({ path: 'libreria.png' });
await page.keyboard.press('Control+3');
await page.waitForTimeout(500);
await page.screenshot({ path: 'modifica.png' });

console.log(results.join('\n'));
console.log('\n--- errori JS in pagina: ' + (errors.length || 'nessuno'));
errors.forEach(e => console.log('  ' + e));
const failed = results.filter(r => r.startsWith('FAIL')).length;
console.log(`\n${results.length - failed}/${results.length} verifiche passate`);
await browser.close();
process.exit(failed || errors.length ? 1 : 0);
