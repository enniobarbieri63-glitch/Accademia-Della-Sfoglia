import AppKit
import MapKit
import SwiftData
import SwiftUI

struct SchermataPersoneLuoghi: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto
    @Query(sort: \Elemento.dataScatto, order: .reverse) private var tutti: [Elemento]
    @Query private var persone: [Persona]

    @State private var ricerca = ""
    @State private var staAnalizzando = false
    @State private var daRinominare: Persona?
    @State private var nomeScritto = ""

    private var vivi: [Elemento] { tutti.filter { !$0.nelCestino } }

    private var conNome: [Persona] {
        let cercato = ricerca.trimmingCharacters(in: .whitespaces).lowercased()
        let elenco = persone.filter { $0.confermata && !$0.nome.isEmpty }
            .sorted { $0.quanteFoto > $1.quanteFoto }
        guard !cercato.isEmpty else { return elenco }
        return elenco.filter { $0.nome.lowercased().contains(cercato) }
    }

    private var daRiconoscere: [Persona] {
        persone.filter { !$0.confermata }.sorted { $0.quanteFoto > $1.quanteFoto }
    }

    private var luoghi: [(nome: String, quanti: Int, posizione: CLLocationCoordinate2D)] {
        var mucchi: [String: [Elemento]] = [:]
        for elemento in vivi {
            guard let luogo = elemento.luogo, !luogo.isEmpty, elemento.haPosizione else { continue }
            mucchi[luogo, default: []].append(elemento)
        }
        return mucchi.map { nome, dentro in
            let latitudine = dentro.compactMap(\.latitudine).reduce(0, +) / Double(dentro.count)
            let longitudine = dentro.compactMap(\.longitudine).reduce(0, +) / Double(dentro.count)
            return (nome, dentro.count,
                    CLLocationCoordinate2D(latitude: latitudine, longitude: longitudine))
        }
        .sorted { $0.quanti > $1.quanti }
    }

    private var senzaPosizione: Int {
        vivi.filter { !$0.haPosizione }.count
    }

    var body: some View {
        VStack(spacing: 0) {
            BarraStrumenti(titolo: "Persone e luoghi",
                           sottotitolo: "\(conNome.count) volti · \(luoghi.count) luoghi") {
                CampoRicerca(invito: "Cerca una persona…", testo: $ricerca, larghezza: 240)
            } destra: {
                if staAnalizzando {
                    HStack(spacing: 6) {
                        ProgressView().controlSize(.small)
                        Text("sto guardando le foto…")
                            .font(Caratteri.interfaccia(12))
                            .foregroundStyle(Colori.testoSecondario)
                    }
                } else {
                    Button("Cerca i volti") { analizza() }
                        .buttonStyle(PulsanteContorno())
                }
            }

            ScrollView {
                VStack(alignment: .leading, spacing: 22) {
                    sezionePersone
                    sezioneLuoghi
                }
                .padding(22)
            }
        }
        .background(Colori.finestra)
        .sheet(item: $daRinominare) { persona in
            finestraNome(persona)
        }
    }

    // MARK: - Persone

    private var sezionePersone: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(alignment: .firstTextBaseline, spacing: 12) {
                Text("Persone")
                    .font(Caratteri.interfaccia(19, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Text("i volti vengono riconosciuti sul Mac, nulla va su internet")
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testoSecondario)
                Spacer()
            }

            if conNome.isEmpty && daRiconoscere.isEmpty {
                Text("Nessun volto ancora. Premi «Cerca i volti» e Scrigno guarda le foto una per una, qui sul Mac.")
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testoSecondario)
            } else {
                LazyVGrid(columns: [GridItem(.adaptive(minimum: 92), spacing: 14)], spacing: 14) {
                    ForEach(conNome) { persona in
                        faccia(persona)
                    }
                    if !daRiconoscere.isEmpty {
                        gruppoDaRiconoscere
                    }
                }
            }
        }
    }

    private func faccia(_ persona: Persona) -> some View {
        Button {
            daRinominare = persona
            nomeScritto = persona.nome
        } label: {
            VStack(spacing: 6) {
                RitaglioVolto(volto: persona.voltoDiCopertina)
                    .frame(width: 78, height: 78)
                Text(persona.nome.isEmpty ? "Senza nome" : persona.nome)
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testo)
                    .lineLimit(1)
                Text(persona.quanteFoto, format: .number)
                    .font(Caratteri.dati(10.5))
                    .foregroundStyle(Colori.testoSecondario)
            }
        }
        .buttonStyle(.plain)
    }

    private var gruppoDaRiconoscere: some View {
        Button {
            if let prima = daRiconoscere.first {
                daRinominare = prima
                nomeScritto = ""
            }
        } label: {
            VStack(spacing: 6) {
                ZStack {
                    Circle().fill(Colori.pannello)
                    Image(systemName: "person.fill.questionmark")
                        .font(.system(size: 24))
                        .foregroundStyle(Colori.testoTenue)
                }
                .frame(width: 78, height: 78)
                .overlay {
                    Circle().strokeBorder(Colori.lineaChiara,
                                          style: StrokeStyle(lineWidth: 2, dash: [4, 3]))
                }
                Text("Da riconoscere")
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testo)
                Text("\(daRiconoscere.count) gruppi")
                    .font(Caratteri.dati(10.5))
                    .foregroundStyle(Colori.testoSecondario)
            }
        }
        .buttonStyle(.plain)
    }

    private func finestraNome(_ persona: Persona) -> some View {
        VStack(alignment: .leading, spacing: 14) {
            Text("Chi è?")
                .font(Caratteri.interfaccia(16, peso: .semibold))
                .foregroundStyle(Colori.testo)

            HStack(spacing: 10) {
                RitaglioVolto(volto: persona.voltoDiCopertina)
                    .frame(width: 64, height: 64)
                VStack(alignment: .leading, spacing: 4) {
                    TextField("Nome", text: $nomeScritto)
                        .textFieldStyle(.roundedBorder)
                        .frame(width: 220)
                    Text("\(persona.quanteFoto) foto in questo gruppo")
                        .font(Caratteri.interfaccia(12))
                        .foregroundStyle(Colori.testoSecondario)
                }
            }

            Text("Scrigno mette insieme i volti che si somigliano, e ogni tanto sbaglia. Se in questo gruppo ci sono persone diverse, dagli il nome di quella giusta: le altre torneranno fra quelle da riconoscere.")
                .font(Caratteri.interfaccia(12))
                .foregroundStyle(Colori.testoTenue)
                .fixedSize(horizontal: false, vertical: true)
                .frame(width: 320)

            HStack {
                Button("Annulla") { daRinominare = nil }
                    .buttonStyle(PulsanteContorno())
                Spacer()
                Button("Salva") {
                    persona.nome = nomeScritto.trimmingCharacters(in: .whitespaces)
                    persona.confermata = !persona.nome.isEmpty
                    try? contesto.save()
                    daRinominare = nil
                }
                .buttonStyle(PulsanteAccento())
            }
        }
        .padding(20)
        .frame(width: 380)
        .background(Colori.pannello)
    }

    // MARK: - Luoghi

    private var sezioneLuoghi: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(alignment: .firstTextBaseline, spacing: 12) {
                Text("Luoghi")
                    .font(Caratteri.interfaccia(19, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Text("raggruppati sulla mappa dai dati GPS degli scatti")
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testoSecondario)
                Spacer()
            }

            if luoghi.isEmpty {
                Text("Nessuna foto porta con sé una posizione. Le foto scattate col telefono di solito ce l'hanno, le scansioni e gli screenshot no.")
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testoSecondario)
            } else {
                HStack(alignment: .top, spacing: 18) {
                    Map {
                        ForEach(luoghi, id: \.nome) { luogo in
                            Marker("\(luogo.nome) · \(luogo.quanti)", coordinate: luogo.posizione)
                                .tint(Colori.accento)
                        }
                    }
                    .frame(minHeight: 380)
                    .clipShape(RoundedRectangle(cornerRadius: 12))
                    .overlay { RoundedRectangle(cornerRadius: 12).strokeBorder(Colori.linea) }

                    classifica.frame(width: 290)
                }
            }
        }
    }

    private var classifica: some View {
        VStack(alignment: .leading, spacing: 11) {
            Text("I più fotografati").stileSezione()

            ForEach(luoghi.prefix(8), id: \.nome) { luogo in
                Button {
                    navigazione.apri(.luogo(luogo.nome))
                } label: {
                    VStack(alignment: .leading, spacing: 4) {
                        HStack {
                            Text(luogo.nome)
                                .font(Caratteri.interfaccia(12.5))
                                .foregroundStyle(Colori.testo)
                            Spacer()
                            Text(luogo.quanti, format: .number)
                                .font(Caratteri.dati(11.5))
                                .foregroundStyle(Colori.testoSecondario)
                        }
                        GeometryReader { spazio in
                            RoundedRectangle(cornerRadius: 3)
                                .fill(Color(esadecimale: "#58b39c"))
                                .frame(width: spazio.size.width * frazione(luogo.quanti))
                        }
                        .frame(height: 4)
                        .background(Colori.rialzato, in: RoundedRectangle(cornerRadius: 3))
                    }
                }
                .buttonStyle(.plain)
            }

            if senzaPosizione > 0 {
                Text("Senza posizione").stileSezione().padding(.top, 6)
                HStack {
                    Text("Scansioni e schermate")
                        .font(Caratteri.interfaccia(12.5))
                        .foregroundStyle(Colori.testo)
                    Spacer()
                    Text(senzaPosizione, format: .number)
                        .font(Caratteri.dati(11.5))
                        .foregroundStyle(Colori.testoSecondario)
                }
            }
        }
        .padding(16)
        .background(Colori.barra, in: RoundedRectangle(cornerRadius: 12))
        .overlay { RoundedRectangle(cornerRadius: 12).strokeBorder(Colori.linea) }
    }

    private func frazione(_ quanti: Int) -> Double {
        let massimo = luoghi.first?.quanti ?? 1
        return massimo > 0 ? Double(quanti) / Double(massimo) : 0
    }

    private func analizza() {
        staAnalizzando = true
        Task {
            await RiconoscimentoVolti.analizza(contesto: contesto)
            staAnalizzando = false
        }
    }
}

/// Il ritaglio tondo di un volto dentro la sua foto.
struct RitaglioVolto: View {
    let volto: Volto?
    @State private var immagine: NSImage?

    var body: some View {
        ZStack {
            Circle().fill(Colori.pannello)
            if let immagine, let volto {
                Image(nsImage: immagine)
                    .resizable()
                    .aspectRatio(contentMode: .fill)
                    .scaleEffect(1 / max(0.12, volto.larghezza * 1.6))
                    .offset(x: (0.5 - volto.x - volto.larghezza / 2) * 200,
                            y: (0.5 - volto.y - volto.altezza / 2) * 200)
            } else {
                Image(systemName: "person.fill")
                    .font(.system(size: 24))
                    .foregroundStyle(Colori.testoTenue)
            }
        }
        .clipShape(Circle())
        .overlay { Circle().strokeBorder(Colori.lineaChiara, lineWidth: 1) }
        .task(id: volto?.persistentModelID) {
            guard let elemento = volto?.elemento else { return }
            let impronta = elemento.improntaFile
            let file = elemento.url
            let tipo = elemento.tipo
            immagine = await Miniature.condivisa.miniatura(impronta: impronta, file: file, tipo: tipo)
        }
    }
}
