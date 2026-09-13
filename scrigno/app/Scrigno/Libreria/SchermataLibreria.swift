import AppKit
import SwiftData
import SwiftUI
import UniformTypeIdentifiers

struct SchermataLibreria: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto

    @Query(sort: \Elemento.dataScatto, order: .reverse) private var tutti: [Elemento]
    @Query(sort: \Etichetta.ordine) private var etichette: [Etichetta]
    @Query(sort: \Album.creatoIl, order: .reverse) private var album: [Album]

    @State private var sottoIlTrascinamento = false
    @State private var anteprimaGrande: Elemento?

    private var visibili: [Elemento] {
        Filtraggio.filtra(tutti, cassetto: navigazione.cassetto, ricerca: navigazione.ricerca)
    }

    private var gruppi: [Filtraggio.Gruppo] {
        Filtraggio.raggruppa(visibili, come: navigazione.raggruppamento)
    }

    private var selezionati: [Elemento] {
        tutti.filter { navigazione.selezione.contains($0.persistentModelID) }
    }

    private var inEvidenza: Elemento? {
        selezionati.last ?? visibili.first
    }

    var body: some View {
        @Bindable var nav = navigazione

        VStack(spacing: 0) {
            BarraStrumenti(titolo: titolo, sottotitolo: sottotitolo) {
                Segmenti(voci: Navigazione.Raggruppamento.allCases.map { ($0, $0.rawValue) },
                         scelta: $nav.raggruppamento)
                CampoRicerca(invito: "Cerca per nome, luogo, testo…", testo: $nav.ricerca)
            } destra: {
                pulsantiAzione
            }

            HStack(spacing: 0) {
                contenuto
                    .frame(maxWidth: .infinity, maxHeight: .infinity)

                Rectangle().fill(Colori.linea).frame(width: 1)

                PannelloInformazioni(elemento: inEvidenza, etichette: etichette)
                    .frame(width: 290)
            }
        }
        .background(Colori.finestra)
        .sheet(item: $anteprimaGrande) { elemento in
            AnteprimaGrande(elemento: elemento)
        }
    }

    // MARK: - Testate

    private var titolo: String {
        navigazione.nomeCassetto(etichette: etichette, album: album)
    }

    private var sottotitolo: String {
        let quanti = visibili.count
        let parola = quanti == 1 ? "elemento" : "elementi"
        if !navigazione.ricerca.isEmpty {
            return "\(quanti) \(parola) per «\(navigazione.ricerca)»"
        }
        return "\(quanti) \(parola)"
    }

    @ViewBuilder
    private var pulsantiAzione: some View {
        HStack(spacing: 8) {
            if !selezionati.isEmpty {
                Button {
                    cambiaPreferito()
                } label: {
                    Image(systemName: selezionati.allSatisfy(\.preferito) ? "heart.fill" : "heart")
                }
                .buttonStyle(.plain)
                .foregroundStyle(Colori.testoSecondario)
                .help("Metti o togli dai preferiti")

                Button {
                    condividi()
                } label: {
                    Image(systemName: "square.and.arrow.up")
                }
                .buttonStyle(.plain)
                .foregroundStyle(Colori.testoSecondario)
                .help("Condividi")

                Button {
                    if case .cestino = navigazione.cassetto { recupera() } else { elimina() }
                } label: {
                    Image(systemName: cestinoAperto ? "arrow.uturn.backward" : "trash")
                }
                .buttonStyle(.plain)
                .foregroundStyle(Colori.testoSecondario)
                .keyboardShortcut(.delete, modifiers: .command)
                .help(cestinoAperto ? "Rimetti in libreria" : "Sposta in Eliminati di recente")
            }

            Button("Importa") { navigazione.mostraImporta = true }
                .buttonStyle(PulsanteAccento())
        }
    }

    private var cestinoAperto: Bool {
        if case .cestino = navigazione.cassetto { return true }
        return false
    }

    // MARK: - Il corpo

    @ViewBuilder
    private var contenuto: some View {
        if visibili.isEmpty {
            NienteQui(titolo: titoloVuoto, spiegazione: spiegazioneVuoto,
                      azione: ("Mostra tutto", { navigazione.apri(.tutto) }))
                .background(sfondoTrascinamento)
                .onDrop(of: [.fileURL], isTargeted: $sottoIlTrascinamento, perform: raccogli)
        } else {
            ScrollView {
                LazyVStack(alignment: .leading, spacing: 0, pinnedViews: [.sectionHeaders]) {
                    Section {
                        filtriRapidi
                    }

                    ForEach(gruppi) { gruppo in
                        intestazioneGruppo(gruppo)
                        griglia(gruppo.elementi)
                            .padding(.bottom, 22)
                    }
                }
                .padding(.horizontal, 20)
                .padding(.bottom, 30)
            }
            .background(sfondoTrascinamento)
            .onDrop(of: [.fileURL], isTargeted: $sottoIlTrascinamento, perform: raccogli)
        }
    }

    private var sfondoTrascinamento: some View {
        Colori.finestra
            .overlay {
                if sottoIlTrascinamento {
                    RoundedRectangle(cornerRadius: 12)
                        .strokeBorder(Colori.accento, style: StrokeStyle(lineWidth: 2, dash: [8, 6]))
                        .padding(10)
                }
            }
    }

    private var filtriRapidi: some View {
        ScrollView(.horizontal) {
            HStack(spacing: 8) {
                pastiglia("Tutti i tipi", attiva: navigazione.cassetto == .tutto, quanti: nil) {
                    navigazione.apri(.tutto)
                }
                ForEach(TipoFile.allCases) { tipo in
                    let quanti = tutti.filter { !$0.nelCestino && $0.tipo == tipo }.count
                    if quanti > 0 {
                        pastiglia(tipo.nomePlurale,
                                  attiva: navigazione.cassetto == .tipo(tipo),
                                  quanti: quanti,
                                  simbolo: tipo.simbolo) {
                            navigazione.apri(.tipo(tipo))
                        }
                    }
                }
                ForEach(etichette) { etichetta in
                    if etichetta.quanti > 0 {
                        pastiglia(etichetta.nome,
                                  attiva: navigazione.cassetto == .etichetta(etichetta.persistentModelID),
                                  quanti: nil, pallino: etichetta.colore) {
                            navigazione.apri(.etichetta(etichetta.persistentModelID))
                        }
                    }
                }
            }
            .padding(.vertical, 13)
        }
        .scrollIndicators(.never)
        .background(Colori.finestra)
    }

    private func pastiglia(_ testo: String, attiva: Bool, quanti: Int?,
                           simbolo: String? = nil, pallino: Color? = nil,
                           fai: @escaping () -> Void) -> some View {
        Button(action: fai) {
            HStack(spacing: 7) {
                if let pallino {
                    Circle().fill(pallino).frame(width: 9, height: 9)
                } else if let simbolo {
                    Image(systemName: simbolo).font(.system(size: 11))
                }
                Text(testo).font(Caratteri.interfaccia(12.5))
                if let quanti {
                    Text(quanti, format: .number)
                        .font(Caratteri.dati(11))
                        .opacity(0.6)
                }
            }
            .foregroundStyle(attiva ? Colori.finestra : Colori.testo.opacity(0.82))
            .padding(.horizontal, 11)
            .padding(.vertical, 5)
            .background(attiva ? Colori.testo : .clear, in: Capsule())
            .overlay { Capsule().strokeBorder(attiva ? .clear : Colori.lineaChiara) }
        }
        .buttonStyle(.plain)
    }

    private func intestazioneGruppo(_ gruppo: Filtraggio.Gruppo) -> some View {
        HStack(alignment: .firstTextBaseline, spacing: 12) {
            Text(gruppo.titolo)
                .font(Caratteri.interfaccia(19, peso: .semibold))
                .foregroundStyle(Colori.testo)

            if let luogo = gruppo.luogo {
                HStack(spacing: 4) {
                    Image(systemName: "mappin.and.ellipse").font(.system(size: 10))
                    Text(luogo)
                }
                .font(Caratteri.interfaccia(13))
                .foregroundStyle(Colori.testoSecondario)
            }

            Text("· \(gruppo.elementi.count) \(gruppo.elementi.count == 1 ? "elemento" : "elementi")")
                .font(Caratteri.interfaccia(13))
                .foregroundStyle(Colori.testoSecondario)

            Spacer()
        }
        .padding(.vertical, 10)
    }

    private func griglia(_ elementi: [Elemento]) -> some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 128, maximum: 220), spacing: 6)],
                  spacing: 6) {
            ForEach(elementi) { elemento in
                RiquadroElemento(elemento: elemento,
                                 selezionato: navigazione.selezione.contains(elemento.persistentModelID))
                    .aspectRatio(1, contentMode: .fill)
                    .onTapGesture(count: 2) { apri(elemento) }
                    .onTapGesture { scegli(elemento) }
                    .contextMenu { menuContestuale(elemento) }
            }
        }
    }

    @ViewBuilder
    private func menuContestuale(_ elemento: Elemento) -> some View {
        Button("Apri") { apri(elemento) }
        Button("Anteprima grande") { anteprimaGrande = elemento }
        Button(elemento.preferito ? "Togli dai preferiti" : "Metti nei preferiti") {
            elemento.preferito.toggle()
            salva()
        }
        Divider()
        Menu("Etichetta") {
            ForEach(etichette) { etichetta in
                Button(etichetta.nome) { assegna(etichetta, a: elemento) }
            }
        }
        Button("Mostra nel Finder") {
            NSWorkspace.shared.activateFileViewerSelecting([elemento.url])
        }
        Divider()
        if elemento.nelCestino {
            Button("Rimetti in libreria") {
                elemento.eliminatoIl = nil
                salva()
            }
        } else {
            Button("Sposta in Eliminati di recente", role: .destructive) {
                elemento.eliminatoIl = Date()
                salva()
            }
        }
    }

    // MARK: - Testi del vuoto

    private var titoloVuoto: String {
        cestinoAperto ? "Il cestino è vuoto" : "Qui non c'è niente"
    }

    private var spiegazioneVuoto: String {
        if cestinoAperto {
            return "Quello che elimini resta qui \(Int(Libreria.giorniNelCestino)) giorni prima di sparire davvero."
        }
        if !navigazione.ricerca.isEmpty {
            return "Nessun elemento per «\(navigazione.ricerca)». La ricerca guarda nomi, luoghi, etichette e il testo dentro i documenti."
        }
        return "In «\(titolo)» non è ancora finito niente."
    }

    // MARK: - Azioni

    private func scegli(_ elemento: Elemento) {
        let identificativo = elemento.persistentModelID
        if NSEvent.modifierFlags.contains(.command) {
            if navigazione.selezione.contains(identificativo) {
                navigazione.selezione.remove(identificativo)
            } else {
                navigazione.selezione.insert(identificativo)
            }
        } else {
            navigazione.selezione = [identificativo]
        }
    }

    private func apri(_ elemento: Elemento) {
        switch elemento.tipo {
        case .foto:
            navigazione.apriInModifica(elemento)
        case .video:
            navigazione.apriNelLettore(elemento)
        case .documento:
            navigazione.vai(.documenti)
        case .audio, .altro:
            NSWorkspace.shared.open(elemento.url)
        }
    }

    private func cambiaPreferito() {
        let tuttiPreferiti = selezionati.allSatisfy(\.preferito)
        for elemento in selezionati { elemento.preferito = !tuttiPreferiti }
        salva()
    }

    private func elimina() {
        let quando = Date()
        for elemento in selezionati { elemento.eliminatoIl = quando }
        navigazione.selezione = []
        salva()
    }

    private func recupera() {
        for elemento in selezionati { elemento.eliminatoIl = nil }
        navigazione.selezione = []
        salva()
    }

    private func assegna(_ etichetta: Etichetta, a elemento: Elemento) {
        if let indice = elemento.etichette.firstIndex(where: { $0.persistentModelID == etichetta.persistentModelID }) {
            elemento.etichette.remove(at: indice)
        } else {
            elemento.etichette.append(etichetta)
        }
        salva()
    }

    private func condividi() {
        guard !selezionati.isEmpty else { return }
        let pannello = NSSharingServicePicker(items: selezionati.map(\.url))
        guard let finestra = NSApp.keyWindow?.contentView else { return }
        pannello.show(relativeTo: .zero, of: finestra, preferredEdge: .minY)
    }

    private func raccogli(_ portatori: [NSItemProvider]) -> Bool {
        Task {
            var urls: [URL] = []
            for portatore in portatori {
                if let dati = try? await portatore.loadItem(forTypeIdentifier: UTType.fileURL.identifier) as? Data,
                   let url = URL(dataRepresentation: dati, relativeTo: nil) {
                    urls.append(url)
                }
            }
            guard !urls.isEmpty else { return }
            navigazione.mostraImporta = true
            NotificationCenter.default.post(name: .fileTrascinati, object: urls)
        }
        return true
    }

    private func salva() {
        try? contesto.save()
    }
}

extension Notification.Name {
    static let fileTrascinati = Notification.Name("it.scrigno.fileTrascinati")
}

/// L'anteprima a tutto schermo che si apre con la barra spaziatrice.
struct AnteprimaGrande: View {
    let elemento: Elemento
    @Environment(\.dismiss) private var chiudi
    @State private var immagine: NSImage?

    var body: some View {
        VStack(spacing: 0) {
            ZStack {
                Color.black
                if let immagine {
                    Image(nsImage: immagine).resizable().aspectRatio(contentMode: .fit)
                } else {
                    ProgressView().controlSize(.small)
                }
            }

            HStack {
                VStack(alignment: .leading, spacing: 2) {
                    Text(elemento.nomeFile)
                        .font(Caratteri.interfaccia(14, peso: .semibold))
                    Text(elemento.dataScatto, format: .dateTime.weekday(.wide).day().month(.wide).year())
                        .font(Caratteri.interfaccia(12))
                        .foregroundStyle(Colori.testoSecondario)
                }
                Spacer()
                Button("Apri con l'app di sistema") { NSWorkspace.shared.open(elemento.url) }
                    .buttonStyle(PulsanteContorno())
                Button("Chiudi") { chiudi() }
                    .buttonStyle(PulsanteAccento())
                    .keyboardShortcut(.escape, modifiers: [])
            }
            .padding(14)
            .background(Colori.barra)
        }
        .frame(width: 900, height: 640)
        .task {
            let impronta = elemento.improntaFile
            let file = elemento.url
            let tipo = elemento.tipo
            immagine = NSImage(contentsOf: file)
                ?? (await Miniature.condivisa.miniatura(impronta: impronta, file: file, tipo: tipo))
        }
    }
}
