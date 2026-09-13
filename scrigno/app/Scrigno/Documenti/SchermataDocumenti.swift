import AppKit
import PDFKit
import SwiftData
import SwiftUI

struct SchermataDocumenti: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto
    @Query(sort: \Elemento.dataScatto, order: .reverse) private var tutti: [Elemento]

    @State private var ricerca = ""
    @State private var scelto: Elemento?
    @State private var tipoScelto = "Tutti"
    @State private var stoLeggendo = false

    private let tipi = ["Tutti", "PDF", "Scansioni", "Fogli", "Presentazioni"]

    private var documenti: [Elemento] {
        tutti.filter { !$0.nelCestino && ($0.tipo == .documento || $0.tipo == .altro) }
    }

    private var risultati: [Elemento] {
        var elenco = documenti

        switch tipoScelto {
        case "PDF": elenco = elenco.filter { $0.url.pathExtension.lowercased() == "pdf" }
        case "Scansioni": elenco = elenco.filter { !$0.testoRiconosciuto.isEmpty && $0.tipo == .foto }
        case "Fogli": elenco = elenco.filter {
            ["xlsx", "xls", "numbers", "csv"].contains($0.url.pathExtension.lowercased()) }
        case "Presentazioni": elenco = elenco.filter {
            ["key", "ppt", "pptx"].contains($0.url.pathExtension.lowercased()) }
        default: break
        }

        let cercato = ricerca.trimmingCharacters(in: .whitespaces).lowercased()
        guard !cercato.isEmpty else { return elenco }
        return elenco.filter { $0.testoCercabile.contains(cercato) }
    }

    var body: some View {
        VStack(spacing: 0) {
            BarraStrumenti(titolo: "Documenti",
                           sottotitolo: "\(documenti.count) file · \(Libreria.pesoLeggibile(documenti.reduce(0) { $0 + $1.peso }))") {
                Segmenti(voci: tipi.map { ($0, $0) }, scelta: $tipoScelto)
                CampoRicerca(invito: "Cerca dentro i documenti…", testo: $ricerca, larghezza: 260)
            } destra: {
                Button("Importa") { navigazione.mostraImporta = true }
                    .buttonStyle(PulsanteAccento())
            }

            if documenti.isEmpty {
                NienteQui(titolo: "Nessun documento",
                          spiegazione: "Importa PDF, scansioni e fogli: la ricerca leggerà anche il testo che c'è dentro.",
                          azione: ("Importa", { navigazione.mostraImporta = true }))
            } else {
                HStack(spacing: 0) {
                    elenco
                        .frame(maxWidth: .infinity)
                    Rectangle().fill(Colori.linea).frame(width: 1)
                    anteprima
                        .frame(width: 380)
                }
            }
        }
        .background(Colori.finestra)
        .task { await leggiIlTestoMancante() }
    }

    // MARK: - Elenco

    private var elenco: some View {
        ScrollView {
            LazyVStack(alignment: .leading, spacing: 0) {
                rigaRisultati

                ForEach(risultati) { documento in
                    Button {
                        scelto = documento
                    } label: {
                        riga(documento)
                    }
                    .buttonStyle(.plain)
                }

                if risultati.isEmpty {
                    NienteQui(titolo: "Nessun documento trovato",
                              spiegazione: "Nessun documento contiene «\(ricerca)».",
                              azione: ("Svuota la ricerca", { ricerca = "" }))
                        .frame(height: 220)
                }
            }
            .padding(.horizontal, 20)
            .padding(.bottom, 24)
        }
    }

    private var rigaRisultati: some View {
        HStack(spacing: 10) {
            Image(systemName: "magnifyingglass").font(.system(size: 12))
            if ricerca.isEmpty {
                Text("\(risultati.count) \(risultati.count == 1 ? "documento" : "documenti") in archivio — la ricerca legge anche dentro PDF e scansioni")
            } else {
                Text("\(risultati.count) \(risultati.count == 1 ? "documento contiene" : "documenti contengono") «\(ricerca)» — anche dentro PDF e scansioni")
            }
            if stoLeggendo {
                ProgressView().controlSize(.small)
                Text("sto ancora leggendo…").foregroundStyle(Colori.testoTenue)
            }
            Spacer()
        }
        .font(Caratteri.interfaccia(13))
        .foregroundStyle(Colori.testoSecondario)
        .padding(.vertical, 16)
    }

    private func riga(_ documento: Elemento) -> some View {
        let attiva = scelto?.persistentModelID == documento.persistentModelID
        let sigla = TipoFile.sigla(per: documento.url)
        let colori = Colori.perSigla(sigla)

        return HStack(alignment: .top, spacing: 10) {
            Text(sigla)
                .font(Caratteri.dati(9.5, peso: .medium))
                .foregroundStyle(colori.testo)
                .frame(width: 40)
                .padding(.vertical, 3)
                .background(colori.sfondo, in: RoundedRectangle(cornerRadius: 4))

            VStack(alignment: .leading, spacing: 3) {
                Text(documento.nomeFile)
                    .font(Caratteri.interfaccia(13, peso: .medium))
                    .foregroundStyle(Colori.testo)
                if let frase = fraseTrovata(in: documento) {
                    Text(frase)
                        .font(Caratteri.interfaccia(12))
                        .foregroundStyle(Colori.testoSecondario)
                        .lineLimit(2)
                }
            }

            Spacer(minLength: 10)

            Text(documento.dataScatto, format: .dateTime.day().month(.twoDigits).year())
                .font(Caratteri.dati(12))
                .foregroundStyle(Colori.testoSecondario)
                .frame(width: 84, alignment: .trailing)

            Text(Libreria.pesoLeggibile(documento.peso))
                .font(Caratteri.dati(12))
                .foregroundStyle(Colori.testoSecondario)
                .frame(width: 70, alignment: .trailing)

            if let etichetta = documento.etichette.first {
                HStack(spacing: 5) {
                    Circle().fill(etichetta.colore).frame(width: 7, height: 7)
                    Text(etichetta.nome).font(Caratteri.interfaccia(11))
                }
                .foregroundStyle(Colori.testo)
                .padding(.horizontal, 7)
                .padding(.vertical, 2)
                .background(Colori.rialzato, in: Capsule())
                .frame(width: 110, alignment: .leading)
            } else {
                Color.clear.frame(width: 110)
            }
        }
        .padding(.vertical, 9)
        .padding(.horizontal, 10)
        .background(attiva ? Colori.accentoVelato : .clear, in: RoundedRectangle(cornerRadius: 8))
        .overlay(alignment: .bottom) { Rectangle().fill(Colori.linea).frame(height: 1) }
        .contentShape(Rectangle())
    }

    /// La frase in cui compare la parola cercata, come nell'anteprima.
    private func fraseTrovata(in documento: Elemento) -> String? {
        let cercato = ricerca.trimmingCharacters(in: .whitespaces).lowercased()
        guard !cercato.isEmpty, !documento.testoRiconosciuto.isEmpty else {
            return documento.testoRiconosciuto.isEmpty
                ? nil
                : String(documento.testoRiconosciuto.prefix(110)) + "…"
        }

        let testo = documento.testoRiconosciuto
        guard let posizione = testo.lowercased().range(of: cercato) else { return nil }

        let inizio = testo.index(posizione.lowerBound,
                                 offsetBy: -60,
                                 limitedBy: testo.startIndex) ?? testo.startIndex
        let fine = testo.index(posizione.upperBound,
                               offsetBy: 60,
                               limitedBy: testo.endIndex) ?? testo.endIndex
        return "…" + testo[inizio..<fine].replacingOccurrences(of: "\n", with: " ") + "…"
    }

    // MARK: - Anteprima a destra

    @ViewBuilder
    private var anteprima: some View {
        if let scelto {
            VStack(spacing: 0) {
                VistaPDF(url: scelto.url)
                    .frame(maxHeight: .infinity)
                    .background(Color(esadecimale: "#121317"))

                VStack(alignment: .leading, spacing: 10) {
                    if let scadenza = scelto.scadenza {
                        HStack(spacing: 6) {
                            Image(systemName: "bell.fill")
                            Text("\(scelto.scadenzaDescrizione ?? "Scadenza") · \(scadenza, format: .dateTime.day().month(.abbreviated).year())")
                        }
                        .font(Caratteri.interfaccia(12.5))
                        .foregroundStyle(Colori.accento)
                    }

                    if let collegato = collegatoA(scelto) {
                        HStack(spacing: 6) {
                            Image(systemName: "link")
                            Text("Collegato a \(collegato.nomeFile)")
                        }
                        .font(Caratteri.interfaccia(12.5))
                        .foregroundStyle(Colori.testoSecondario)
                    }

                    HStack(spacing: 8) {
                        Button("Apri") { NSWorkspace.shared.open(scelto.url) }
                            .buttonStyle(PulsanteContorno())
                        Button("Mostra nel Finder") {
                            NSWorkspace.shared.activateFileViewerSelecting([scelto.url])
                        }
                        .buttonStyle(PulsanteContorno())
                    }
                }
                .padding(16)
                .frame(maxWidth: .infinity, alignment: .leading)
                .overlay(alignment: .top) { Rectangle().fill(Colori.linea).frame(height: 1) }
            }
            .background(Colori.barra)
        } else {
            VStack {
                Text("Scegli un documento per vederlo")
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testoSecondario)
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity)
            .background(Colori.barra)
        }
    }

    /// Lo scontrino della stessa giornata, quando c'è: è il collegamento che
    /// serve più spesso davvero.
    private func collegatoA(_ documento: Elemento) -> Elemento? {
        let stessoGiorno = Calendar.current.startOfDay(for: documento.dataScatto)
        return documenti.first { altro in
            altro.persistentModelID != documento.persistentModelID
                && Calendar.current.startOfDay(for: altro.dataScatto) == stessoGiorno
                && TrovaScontrini.sembraUnoScontrino(altro)
        }
    }

    private func leggiIlTestoMancante() async {
        guard documenti.contains(where: { $0.testoRiconosciuto.isEmpty }) else { return }
        stoLeggendo = true
        await LavoriInSottofondo.leggiTestoMancante(contesto: contesto)
        stoLeggendo = false
    }
}

/// Mostra un PDF con le sue pagine. Per tutto il resto mostra la miniatura.
struct VistaPDF: NSViewRepresentable {
    let url: URL

    func makeNSView(context: Context) -> PDFView {
        let vista = PDFView()
        vista.autoScales = true
        vista.displayMode = .singlePageContinuous
        vista.backgroundColor = NSColor(Color(esadecimale: "#121317"))
        return vista
    }

    func updateNSView(_ vista: PDFView, context: Context) {
        guard url.pathExtension.lowercased() == "pdf" else {
            vista.document = nil
            return
        }
        if vista.document?.documentURL != url {
            vista.document = PDFDocument(url: url)
        }
    }
}
