import AppKit
import SwiftData
import SwiftUI

struct FinestraImporta: View {
    let importatore: Importatore

    @Environment(\.dismiss) private var chiudi
    @Environment(\.modelContext) private var contesto
    @Query(sort: \Etichetta.ordine) private var etichette: [Etichetta]

    @State private var telefono = ArrivoDaTelefono()
    @State private var sorgenteScelta: Sorgente = .cartelle
    @State private var dischi: [SorgentiEsterne.Disco] = []
    @State private var cartelle: [SorgentiEsterne.Disco] = []

    @State private var albumPerGiorno = true
    @State private var riconosciTesto = true
    @State private var eliminaDallaSorgente = false
    @State private var etichettaScelta: Etichetta?

    enum Sorgente: Hashable {
        case telefono
        case disco(String)
        case cartelle
    }

    var body: some View {
        HStack(spacing: 0) {
            colonnaSorgenti
                .frame(width: 230)
            Rectangle().fill(Colori.linea).frame(width: 1)
            corpo
                .frame(width: 600)
        }
        .frame(height: 580)
        .background(Colori.pannello)
        .task { aggiornaSorgenti() }
        .onReceive(NotificationCenter.default.publisher(for: .fileTrascinati)) { avviso in
            guard let urls = avviso.object as? [URL] else { return }
            guarda(urls, nome: "Trascinati")
        }
        .onAppear {
            telefono.quandoArrivaRoba = { arrivati in
                guarda(arrivati, nome: "Dal telefono", daRipulire: true)
            }
        }
        .onDisappear {
            telefono.spegni()
            importatore.azzera()
        }
    }

    // MARK: - Colonna di sinistra

    private var colonnaSorgenti: some View {
        VStack(alignment: .leading, spacing: 0) {
            Text("Da dove")
                .stileSezione()
                .padding(.horizontal, 16)
                .padding(.top, 16)
                .padding(.bottom, 8)

            ScrollView {
                VStack(alignment: .leading, spacing: 2) {
                    voceSorgente(.telefono, titolo: "Telefono o tablet",
                                 sotto: "Android, iPhone · via Wi-Fi",
                                 simbolo: "iphone.gen3")

                    ForEach(dischi) { disco in
                        voceSorgente(.disco(disco.id), titolo: disco.nome,
                                     sotto: descrizione(disco), simbolo: "sdcard")
                    }

                    voceSorgente(.cartelle, titolo: "Cartelle e file",
                                 sotto: "Scegli o trascina", simbolo: "folder")

                    ForEach(cartelle) { cartella in
                        Button {
                            guarda([cartella.url], nome: cartella.nome)
                        } label: {
                            rigaSorgente(titolo: cartella.nome,
                                         sotto: descrizione(cartella),
                                         simbolo: "folder",
                                         attiva: false)
                        }
                        .buttonStyle(.plain)
                    }
                }
                .padding(.horizontal, 10)
            }

            zonaTrascinamento
                .padding(14)
        }
        .background(Colori.barra)
    }

    private func descrizione(_ disco: SorgentiEsterne.Disco) -> String {
        let quanti = disco.quantiFile
        if quanti >= 5000 { return "oltre 5.000 file" }
        return "\(quanti) \(quanti == 1 ? "file" : "file")"
    }

    private func voceSorgente(_ sorgente: Sorgente, titolo: String, sotto: String,
                              simbolo: String) -> some View {
        Button {
            sorgenteScelta = sorgente
            scegliSorgente(sorgente)
        } label: {
            rigaSorgente(titolo: titolo, sotto: sotto, simbolo: simbolo,
                         attiva: sorgenteScelta == sorgente)
        }
        .buttonStyle(.plain)
    }

    private func rigaSorgente(titolo: String, sotto: String, simbolo: String,
                              attiva: Bool) -> some View {
        HStack(spacing: 10) {
            Image(systemName: simbolo)
                .font(.system(size: 14))
                .frame(width: 18)
                .foregroundStyle(attiva ? Colori.accento : Colori.testoSecondario)
            VStack(alignment: .leading, spacing: 1) {
                Text(titolo)
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testo)
                Text(sotto)
                    .font(Caratteri.interfaccia(11))
                    .foregroundStyle(Colori.testoTenue)
            }
            Spacer(minLength: 0)
        }
        .padding(.horizontal, 8)
        .padding(.vertical, 7)
        .background(attiva ? Colori.accentoVelato : .clear, in: RoundedRectangle(cornerRadius: 7))
        .contentShape(Rectangle())
    }

    private var zonaTrascinamento: some View {
        VStack(spacing: 6) {
            Image(systemName: "arrow.down.circle")
                .font(.system(size: 20))
                .foregroundStyle(Colori.accento)
            Text("Oppure trascina qui qualsiasi file o cartella")
                .font(Caratteri.interfaccia(11.5))
                .foregroundStyle(Colori.testoSecondario)
                .multilineTextAlignment(.center)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 16)
        .overlay {
            RoundedRectangle(cornerRadius: 10)
                .strokeBorder(Colori.lineaChiara, style: StrokeStyle(lineWidth: 1.5, dash: [5, 4]))
        }
    }

    // MARK: - Corpo

    private var corpo: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(spacing: 10) {
                Text(titoloCorpo)
                    .font(Caratteri.interfaccia(16, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Spacer()
                Button {
                    chiudi()
                } label: {
                    Image(systemName: "xmark")
                        .font(.system(size: 12, weight: .medium))
                        .foregroundStyle(Colori.testoSecondario)
                        .frame(width: 28, height: 28)
                        .contentShape(Rectangle())
                }
                .buttonStyle(.plain)
                .keyboardShortcut(.escape, modifiers: [])
            }

            if sorgenteScelta == .telefono {
                PannelloTelefono(telefono: telefono)
            }

            switch importatore.fase {
            case .guardo:
                statoLavoro("Sto guardando cosa c'è…", importatore.nomeInLavorazione)
            case .copio:
                statoLavoro("Sto copiando in libreria…", importatore.nomeInLavorazione)
            case .fermato(let motivo):
                avviso(motivo, brutto: true)
            case .fatto:
                avviso(riepilogoFinale, brutto: false)
            case .annullato:
                avviso("Fermato. Quello che era già entrato resta in libreria.", brutto: false)
            case .fermo:
                EmptyView()
            }

            if !importatore.candidati.isEmpty {
                grigliaArrivi
                conteggi
            } else if importatore.fase == .fermo && sorgenteScelta != .telefono {
                NienteQui(titolo: "Niente da importare",
                          spiegazione: "Scegli una sorgente a sinistra, oppure trascina qui dei file.",
                          azione: ("Scegli cartelle…", { scegliCartelle() }))
                    .frame(maxHeight: 200)
            }

            Spacer(minLength: 0)

            if !importatore.daImportare.isEmpty { interruttori }
            if !importatore.problemi.isEmpty { elencoProblemi }

            piede
        }
        .padding(18)
    }

    private var titoloCorpo: String {
        if !importatore.nomeSorgente.isEmpty { return "Importa da \(importatore.nomeSorgente)" }
        return sorgenteScelta == .telefono ? "Manda dal telefono" : "Importa"
    }

    private var grigliaArrivi: some View {
        ScrollView {
            LazyVGrid(columns: Array(repeating: GridItem(.flexible(), spacing: 5), count: 7),
                      spacing: 5) {
                ForEach(importatore.candidati.prefix(35)) { candidato in
                    MiniaturaArrivo(candidato: candidato)
                        .frame(height: 62)
                }
            }
        }
        .frame(maxHeight: 200)
    }

    private var conteggi: some View {
        HStack(spacing: 1) {
            casella(importatore.quanti(.foto), "Foto")
            casella(importatore.quanti(.video), "Video")
            casella(importatore.quanti(.documento) + importatore.quanti(.altro), "Documenti")
            casella(importatore.quantiDoppioni, "Doppioni saltati")
        }
        .background(Colori.linea)
        .clipShape(RoundedRectangle(cornerRadius: 10))
    }

    private func casella(_ numero: Int, _ nome: String) -> some View {
        VStack(alignment: .leading, spacing: 1) {
            Text(numero, format: .number)
                .font(Caratteri.dati(17, peso: .medium))
                .foregroundStyle(Colori.testo)
            Text(nome)
                .font(Caratteri.interfaccia(12))
                .foregroundStyle(Colori.testoSecondario)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(.horizontal, 12)
        .padding(.vertical, 10)
        .background(Colori.barra)
    }

    private var interruttori: some View {
        Grid(alignment: .leading, horizontalSpacing: 18, verticalSpacing: 8) {
            GridRow {
                interruttore("Crea un album per ogni giorno", $albumPerGiorno)
                interruttore("Riconosci il testo nelle scansioni", $riconosciTesto)
            }
            GridRow {
                interruttore("Cancella dalla sorgente dopo", $eliminaDallaSorgente)
                menuEtichetta
            }
        }
        .font(Caratteri.interfaccia(12.5))
    }

    private func interruttore(_ testo: String, _ valore: Binding<Bool>) -> some View {
        Toggle(testo, isOn: valore)
            .toggleStyle(.switch)
            .tint(Colori.accento)
            .foregroundStyle(Colori.testo)
    }

    private var menuEtichetta: some View {
        Menu {
            Button("Nessuna") { etichettaScelta = nil }
            ForEach(etichette) { etichetta in
                Button(etichetta.nome) { etichettaScelta = etichetta }
            }
        } label: {
            HStack(spacing: 6) {
                Text("Etichetta:")
                    .foregroundStyle(Colori.testo)
                if let etichettaScelta {
                    Circle().fill(etichettaScelta.colore).frame(width: 8, height: 8)
                    Text(etichettaScelta.nome).foregroundStyle(Colori.testo)
                } else {
                    Text("nessuna").foregroundStyle(Colori.testoSecondario)
                }
            }
        }
        .menuStyle(.borderlessButton)
        .fixedSize()
    }

    private var elencoProblemi: some View {
        VStack(alignment: .leading, spacing: 3) {
            Text("\(importatore.problemi.count) file hanno dato problemi")
                .font(Caratteri.interfaccia(12, peso: .semibold))
                .foregroundStyle(Color(esadecimale: "#f0cfc9"))
            ForEach(importatore.problemi.prefix(3), id: \.self) { problema in
                Text(problema)
                    .font(Caratteri.interfaccia(11.5))
                    .foregroundStyle(Colori.testoSecondario)
                    .lineLimit(1)
            }
        }
        .padding(10)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Color(esadecimale: "#e06d5c").opacity(0.1),
                    in: RoundedRectangle(cornerRadius: 8))
    }

    private var piede: some View {
        HStack(spacing: 10) {
            VStack(alignment: .leading, spacing: 5) {
                Text(rigaStato)
                    .font(Caratteri.interfaccia(11.5))
                    .foregroundStyle(Colori.testoSecondario)

                if importatore.fase == .copio {
                    ProgressView(value: importatore.avanzamento)
                        .progressViewStyle(.linear)
                        .tint(Colori.accento)
                }
            }

            Spacer()

            if importatore.fase == .copio || importatore.fase == .guardo {
                Button("Ferma") { importatore.ferma() }
                    .buttonStyle(PulsanteContorno())
            } else if importatore.fase == .fatto {
                Button("Chiudi") { chiudi() }
                    .buttonStyle(PulsanteAccento())
            } else {
                Button("Annulla") { chiudi() }
                    .buttonStyle(PulsanteContorno())
                Button(testoPulsanteImporta) { importa() }
                    .buttonStyle(PulsanteAccento())
                    .disabled(importatore.daImportare.isEmpty)
            }
        }
    }

    private var testoPulsanteImporta: String {
        let quanti = importatore.daImportare.count
        guard quanti > 0 else { return "Importa" }
        return "Importa \(quanti) \(quanti == 1 ? "elemento" : "elementi")"
    }

    private var rigaStato: String {
        switch importatore.fase {
        case .copio:
            return "Copiati \(importatore.fatti) di \(importatore.daImportare.count)"
        case .guardo:
            return "Sto guardando… \(importatore.nomeInLavorazione)"
        default:
            let quanti = importatore.daImportare.count
            guard quanti > 0 else { return "Niente in coda" }
            return "Pronto a importare \(quanti) \(quanti == 1 ? "elemento" : "elementi") (\(Libreria.pesoLeggibile(importatore.pesoTotale)))"
        }
    }

    private var riepilogoFinale: String {
        var pezzi = ["\(importatore.fatti) \(importatore.fatti == 1 ? "elemento entrato" : "elementi entrati") in libreria"]
        if importatore.saltati > 0 { pezzi.append("\(importatore.saltati) doppioni saltati") }
        return pezzi.joined(separator: " · ")
    }

    private func statoLavoro(_ titolo: String, _ dettaglio: String) -> some View {
        HStack(spacing: 8) {
            ProgressView().controlSize(.small)
            VStack(alignment: .leading, spacing: 1) {
                Text(titolo).font(Caratteri.interfaccia(12.5)).foregroundStyle(Colori.testo)
                if !dettaglio.isEmpty {
                    Text(dettaglio)
                        .font(Caratteri.interfaccia(11))
                        .foregroundStyle(Colori.testoSecondario)
                        .lineLimit(1)
                }
            }
            Spacer()
        }
    }

    private func avviso(_ testo: String, brutto: Bool) -> some View {
        Text(testo)
            .font(Caratteri.interfaccia(12.5))
            .foregroundStyle(brutto ? Color(esadecimale: "#f0cfc9") : Color(esadecimale: "#cfe6de"))
            .padding(12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background((brutto ? Color(esadecimale: "#e06d5c") : Color(esadecimale: "#58b39c")).opacity(0.12),
                        in: RoundedRectangle(cornerRadius: 10))
    }

    // MARK: - Azioni

    private func aggiornaSorgenti() {
        dischi = SorgentiEsterne.dischiCollegati()
        cartelle = SorgentiEsterne.cartelleComuni()
    }

    private func scegliSorgente(_ sorgente: Sorgente) {
        switch sorgente {
        case .telefono:
            telefono.accendi()
        case .disco(let id):
            telefono.spegni()
            guard let disco = dischi.first(where: { $0.id == id }) else { return }
            guarda([disco.cartellaFoto ?? disco.url], nome: disco.nome)
        case .cartelle:
            telefono.spegni()
            scegliCartelle()
        }
    }

    private func scegliCartelle() {
        let pannello = NSOpenPanel()
        pannello.canChooseFiles = true
        pannello.canChooseDirectories = true
        pannello.allowsMultipleSelection = true
        pannello.prompt = "Scegli"
        pannello.message = "Scegli le cartelle o i file da mettere in Scrigno"

        guard pannello.runModal() == .OK, !pannello.urls.isEmpty else { return }
        let nome = pannello.urls.count == 1
            ? pannello.urls[0].lastPathComponent
            : "\(pannello.urls.count) elementi"
        guarda(pannello.urls, nome: nome)
    }

    private func guarda(_ urls: [URL], nome: String, daRipulire: Bool = false) {
        Task {
            await importatore.guarda(urls, nomeSorgente: nome,
                                     daRipulire: daRipulire, contesto: contesto)
        }
    }

    private func importa() {
        let opzioni = Importatore.Opzioni(
            albumPerGiorno: albumPerGiorno,
            riconosciTesto: riconosciTesto,
            eliminaDallaSorgente: eliminaDallaSorgente,
            etichetta: etichettaScelta,
            nomeAlbum: nil
        )
        Task {
            await importatore.copia(opzioni: opzioni, contesto: contesto)
            if riconosciTesto {
                await LavoriInSottofondo.leggiTestoMancante(contesto: contesto)
            }
            await LavoriInSottofondo.completaLuoghi(contesto: contesto)
        }
    }
}

/// Una miniatura nella griglia degli arrivi, coi doppioni già segnati.
struct MiniaturaArrivo: View {
    let candidato: Importatore.Candidato
    @State private var immagine: NSImage?

    var body: some View {
        ZStack {
            Colori.barra
            if let immagine {
                Image(nsImage: immagine).resizable().aspectRatio(contentMode: .fill)
            } else {
                Image(systemName: candidato.tipo.simbolo)
                    .font(.system(size: 14))
                    .foregroundStyle(Colori.testoTenue)
            }
        }
        .clipShape(RoundedRectangle(cornerRadius: 5))
        .opacity(candidato.doppione ? 0.35 : 1)
        .overlay(alignment: .bottom) {
            if candidato.doppione {
                Text("doppione")
                    .font(.system(size: 9))
                    .foregroundStyle(.white)
                    .padding(.bottom, 3)
            }
        }
        .help(candidato.url.lastPathComponent)
        .task {
            immagine = await Miniature.condivisa.miniatura(impronta: candidato.impronta,
                                                           file: candidato.url,
                                                           tipo: candidato.tipo)
        }
    }
}
