import Foundation

/// La pagina che si apre sul telefono. Deve funzionare su Android e su iPhone,
/// dentro qualsiasi browser, senza installare niente.
enum PaginaTelefono {

    static func html(codice: String, quantiArrivati: Int) -> String {
        """
        <!doctype html>
        <html lang="it">
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>Manda a Scrigno</title>
        <style>
          :root{ color-scheme: dark; }
          *{ box-sizing:border-box; -webkit-tap-highlight-color:transparent }
          body{ margin:0; min-height:100dvh; padding:28px 20px 40px;
                background:#16171c; color:#ece9e3;
                font:16px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
                display:flex; flex-direction:column; gap:22px }
          h1{ font-size:26px; margin:0; font-weight:600; letter-spacing:-.01em }
          p{ margin:0; color:#8e8c96; font-size:14.5px }
          .scegli{ display:block; border:1.5px dashed #393b46; border-radius:16px;
                   padding:30px 18px; text-align:center; background:#1b1c22 }
          .scegli b{ display:block; font-size:17px; margin-bottom:4px; font-weight:600 }
          .scegli span{ color:#8e8c96; font-size:13.5px }
          input[type=file]{ display:none }
          ul{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:6px }
          li{ background:#202128; border-radius:10px; padding:10px 12px; font-size:14px;
              display:flex; justify-content:space-between; gap:10px; align-items:center }
          li span{ color:#8e8c96; font-size:12px; white-space:nowrap;
                   font-variant-numeric:tabular-nums }
          li b{ font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap }
          button{ font:inherit; width:100%; padding:15px; border:0; border-radius:12px;
                  background:#e9ae4f; color:#1d1506; font-weight:600; font-size:17px }
          button:disabled{ background:#2c2e37; color:#5d5c66 }
          .barra{ height:6px; border-radius:4px; background:#2c2e37; overflow:hidden; display:none }
          .barra i{ display:block; height:100%; width:0; background:#e9ae4f; transition:width .2s }
          .esito{ padding:14px; border-radius:12px; font-size:14.5px; display:none }
          .bene{ background:rgba(88,179,156,.12); border:1px solid rgba(88,179,156,.35); color:#cfe6de }
          .male{ background:rgba(224,109,92,.12); border:1px solid rgba(224,109,92,.35); color:#f0cfc9 }
          footer{ margin-top:auto; color:#5d5c66; font-size:12.5px; text-align:center }
        </style>
        </head>
        <body>
          <div>
            <h1>Manda a Scrigno</h1>
            <p>Scegli foto, video o file dal telefono: finiscono sul Mac, nella finestra Importa.</p>
          </div>

          <form id="modulo">
            <label class="scegli">
              <input type="file" id="scelta" multiple>
              <b>Scegli dal telefono</b>
              <span>Foto, video, PDF, qualsiasi cosa</span>
            </label>
          </form>

          <ul id="elenco"></ul>
          <div class="barra" id="barra"><i id="riempimento"></i></div>
          <button id="invia" disabled>Manda</button>
          <div class="esito" id="esito"></div>

          <footer>Arrivati finora in questa sessione: \(quantiArrivati)</footer>

        <script>
        (function(){
          var scelta = document.getElementById('scelta');
          var elenco = document.getElementById('elenco');
          var invia = document.getElementById('invia');
          var barra = document.getElementById('barra');
          var riempimento = document.getElementById('riempimento');
          var esito = document.getElementById('esito');

          function peso(n){
            if (n < 1024) return n + ' B';
            if (n < 1048576) return (n/1024).toFixed(0) + ' KB';
            if (n < 1073741824) return (n/1048576).toFixed(1).replace('.', ',') + ' MB';
            return (n/1073741824).toFixed(2).replace('.', ',') + ' GB';
          }

          scelta.addEventListener('change', function(){
            elenco.textContent = '';
            var totale = 0;
            for (var i = 0; i < scelta.files.length; i++){
              var f = scelta.files[i];
              totale += f.size;
              var riga = document.createElement('li');
              var nome = document.createElement('b');
              nome.textContent = f.name;
              var misura = document.createElement('span');
              misura.textContent = peso(f.size);
              riga.appendChild(nome); riga.appendChild(misura);
              elenco.appendChild(riga);
            }
            invia.disabled = scelta.files.length === 0;
            invia.textContent = scelta.files.length
              ? 'Manda ' + scelta.files.length + (scelta.files.length === 1 ? ' file' : ' file') + ' (' + peso(totale) + ')'
              : 'Manda';
          });

          invia.addEventListener('click', function(){
            if (!scelta.files.length) return;
            var dati = new FormData();
            for (var i = 0; i < scelta.files.length; i++) dati.append('file', scelta.files[i]);

            var richiesta = new XMLHttpRequest();
            richiesta.open('POST', '/c/\(codice)/invia');
            barra.style.display = 'block';
            invia.disabled = true;
            invia.textContent = 'Sto mandando…';
            esito.style.display = 'none';

            richiesta.upload.onprogress = function(e){
              if (e.lengthComputable) riempimento.style.width = (e.loaded / e.total * 100) + '%';
            };
            richiesta.onload = function(){
              barra.style.display = 'none';
              riempimento.style.width = '0';
              if (richiesta.status === 200){
                esito.className = 'esito bene';
                esito.textContent = 'Fatto. Ora guarda la finestra Importa sul Mac.';
                elenco.textContent = '';
                scelta.value = '';
                invia.textContent = 'Manda altro';
              } else {
                esito.className = 'esito male';
                esito.textContent = 'Non ha funzionato: ' + (richiesta.responseText || richiesta.status);
                invia.textContent = 'Riprova';
              }
              esito.style.display = 'block';
              invia.disabled = false;
            };
            richiesta.onerror = function(){
              barra.style.display = 'none';
              esito.className = 'esito male';
              esito.textContent = 'Il Mac non risponde. Controlla di essere sulla stessa rete Wi-Fi.';
              esito.style.display = 'block';
              invia.disabled = false;
              invia.textContent = 'Riprova';
            };
            richiesta.send(dati);
          });
        })();
        </script>
        </body>
        </html>
        """
    }

    static let paginaSbagliata = """
        <!doctype html>
        <html lang="it"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Codice sbagliato</title>
        <style>body{margin:0;min-height:100dvh;display:grid;place-items:center;
        background:#16171c;color:#ece9e3;font:16px/1.5 -apple-system,sans-serif;padding:24px;
        text-align:center}p{color:#8e8c96;font-size:14px}</style></head>
        <body><div><h1>Codice sbagliato</h1>
        <p>Inquadra di nuovo il codice QR che vedi sul Mac.</p></div></body></html>
        """
}
