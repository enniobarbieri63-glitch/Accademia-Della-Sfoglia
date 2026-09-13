import AppKit
import MapKit
import SwiftData
import SwiftUI

/// La colonna di destra. Segue la selezione e mostra solo quello che ha senso:
/// per un PDF non si inventano diaframma e ISO.
struct PannelloInformazioni: View {
    let elemento: Elemento?
    let etichette: [Etichetta]

    @Environment(\.modelContext) private var contesto
    @State private var miniatura: NSImage?

    var body: some View {
        ScrollView {
            if let elemento {
                VStack(alignment: .leading, spacing: 0) {
                    anteprima(elemento)

                    Text(elemento.nomeFile)
                        .font(Caratteri.interfaccia(15, peso: .semibold))
                        .foregroundStyle(Colori.testo)
                        .textSelection(.enabled)

                    Text(dataEstesa(elemento.dataScatto))
                        .font(Caratteri.interfaccia(12))
                        .foregroundStyle(Colori.testoSecondario)
                        .padding(.top, 2)

                    if elemento.tipo == .foto, elemento.diaframma != nil || elemento.iso != nil {
                        datiDiScatto(elemento).padding(.top, 14)
                    }

                    schedaValori(elemento).padding(.top, 14)

                    sezione("Etichette")
                    etichetteDi(elemento)

                    if !elemento.paroleAutomatiche.isEmpty {
                        sezione("Riconosciuto automaticamente")
                        pastiglie(elemento.paroleAutomatiche.map { ($0, nil) })
                    }

                    if !elemento.album.isEmpty {
                        sezione("Album")
                        pastiglie(elemento.album.map { ($0.nome, nil) })
                    }

                    if !elemento.testoRiconosciuto.isEmpty {
                        sezione("Testo trovato dentro")
                        Text(elemento.testoRiconosciuto.prefix(400))
                            .font(Caratteri.interfaccia(12))
                            .foregroundStyle(Colori.testoSecondario)
                            .textSelection(.enabled)
                            .padding(10)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(Colori.pannello, in: RoundedRectangle(cornerRadius: 8))
                    }

                    if let scadenza = elemento.scadenza {
                        sezione("Promemoria")
                        HStack(spacing: 6) {
                            Image(systemName: "bell")
                            Text("\(elemento.scadenzaDescrizione ?? "Scadenza") · \(scadenza, format: .dateTime.day().month(.abbreviated).year())")
                        }
                        .font(Caratteri.interfaccia(12.5))
                        .foregroundStyle(Colori.accento)
                    }

                    if elemento.haPosizione {
                        sezione("Luogo")
                        mappa(elemento)
                    }

                    if elemento.nelCestino, let giorni = elemento.giorniPrimaDellaCancellazione {
                        avvisoCestino(giorni).padding(.top, 18)
                    }
                }
                .padding(16)
            } else {
                VStack(spacing: 8) {
                    Image(systemName: "sidebar.right")
                        .font(.system(size: 22))
                        .foregroundStyle(Colori.testoTenue)
                    Text("Scegli un elemento per vederne i dati")
                        .font(Caratteri.interfaccia(12.5))
                        .foregroundStyle(Colori.testoSecondario)
                        .multilineTextAlignment(.center)
                }
                .padding(30)
            }
        }
        .background(Colori.barra)
        .task(id: elemento?.improntaFile) { await caricaAnteprima() }
    }

    // MARK: - Pezzi

    private func anteprima(_ elemento: Elemento) -> some View {
        ZStack {
            Colori.pannello
            if let miniatura {
                Image(nsImage: miniatura).resizable().aspectRatio(contentMode: .fill)
            } else {
                Image(systemName: elemento.tipo.simbolo)
                    .font(.system(size: 26))
                    .foregroundStyle(Colori.testoTenue)
            }
        }
        .frame(height: 180)
        .clipShape(RoundedRectangle(cornerRadius: 8))
        .padding(.bottom, 14)
    }

    private func datiDiScatto(_ elemento: Elemento) -> some View {
        HStack(spacing: 1) {
            casella(testo: elemento.diaframma.map { "f/\(formatta($0))" } ?? "—", nome: "Diafr.")
            casella(testo: tempoDiPosa(elemento.tempoEsposizione), nome: "Tempo")
            casella(testo: elemento.iso.map(String.init) ?? "—", nome: "ISO")
            casella(testo: elemento.focale.map { "\(Int($0))mm" } ?? "—", nome: "Focale")
        }
        .background(Colori.linea)
        .clipShape(RoundedRectangle(cornerRadius: 8))
    }

    private func casella(testo: String, nome: String) -> some View {
        VStack(spacing: 2) {
            Text(testo).font(Caratteri.dati(12))
            Text(nome).stileSezione().font(.system(size: 9.5, weight: .semibold))
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 8)
        .background(Colori.pannello)
    }

    private func schedaValori(_ elemento: Elemento) -> some View {
        VStack(spacing: 6) {
            if let fotocamera = elemento.fotocamera {
                riga("Fotocamera", fotocamera)
            }
            if elemento.larghezza > 0 {
                riga("Dimensioni", "\(elemento.larghezza) × \(elemento.altezza)")
            }
            if elemento.durata > 0 {
                riga("Durata", Durata.testo(elemento.durata))
            }
            riga("Peso", Libreria.pesoLeggibile(elemento.peso))
            riga("Tipo", elemento.tipo.nomePlurale)
            riga("Modificata", elemento.modificato ? "Sì · originale conservato" : "No")
        }
    }

    private func riga(_ nome: String, _ valore: String) -> some View {
        HStack(alignment: .top) {
            Text(nome)
                .font(Caratteri.interfaccia(12.5))
                .foregroundStyle(Colori.testoSecondario)
            Spacer(minLength: 10)
            Text(valore)
                .font(Caratteri.interfaccia(12.5))
                .foregroundStyle(Colori.testo)
                .multilineTextAlignment(.trailing)
        }
    }

    private func sezione(_ titolo: String) -> some View {
        Text(titolo)
            .stileSezione()
            .padding(.top, 18)
            .padding(.bottom, 8)
    }

    private func etichetteDi(_ elemento: Elemento) -> some View {
        FlussoPastiglie {
            ForEach(elemento.etichette) { etichetta in
                Button {
                    stacca(etichetta, da: elemento)
                } label: {
                    pastiglia(etichetta.nome, colore: etichetta.colore)
                }
                .buttonStyle(.plain)
                .help("Togli questa etichetta")
            }

            Menu {
                ForEach(etichette.filter { e in
                    !elemento.etichette.contains { $0.persistentModelID == e.persistentModelID }
                }) { etichetta in
                    Button(etichetta.nome) { attacca(etichetta, a: elemento) }
                }
            } label: {
                Text("+ Aggiungi")
                    .font(Caratteri.interfaccia(12))
                    .foregroundStyle(Colori.testoSecondario)
                    .padding(.horizontal, 9)
                    .padding(.vertical, 3)
                    .overlay {
                        Capsule().strokeBorder(Colori.lineaChiara, style: StrokeStyle(lineWidth: 1, dash: [3, 3]))
                    }
            }
            .menuStyle(.borderlessButton)
            .fixedSize()
        }
    }

    private func pastiglie(_ voci: [(String, Color?)]) -> some View {
        FlussoPastiglie {
            ForEach(voci, id: \.0) { voce in
                pastiglia(voce.0, colore: voce.1)
            }
        }
    }

    private func pastiglia(_ testo: String, colore: Color?) -> some View {
        HStack(spacing: 6) {
            if let colore {
                Circle().fill(colore).frame(width: 8, height: 8)
            }
            Text(testo).font(Caratteri.interfaccia(12))
        }
        .foregroundStyle(Colori.testo)
        .padding(.leading, colore == nil ? 9 : 7)
        .padding(.trailing, 9)
        .padding(.vertical, 3)
        .background(Colori.rialzato, in: Capsule())
    }

    private func mappa(_ elemento: Elemento) -> some View {
        let posizione = CLLocationCoordinate2D(latitude: elemento.latitudine ?? 0,
                                               longitude: elemento.longitudine ?? 0)
        return VStack(alignment: .leading, spacing: 6) {
            Map(initialPosition: .region(MKCoordinateRegion(
                center: posizione,
                span: MKCoordinateSpan(latitudeDelta: 0.05, longitudeDelta: 0.05)
            ))) {
                Marker(elemento.luogo ?? "Qui", coordinate: posizione)
                    .tint(Colori.accento)
            }
            .frame(height: 110)
            .clipShape(RoundedRectangle(cornerRadius: 8))
            .allowsHitTesting(false)

            Text(descrizioneLuogo(elemento))
                .font(Caratteri.interfaccia(11.5))
                .foregroundStyle(Colori.testoSecondario)
        }
    }

    private func avvisoCestino(_ giorni: Int) -> some View {
        Text(giorni == 0
             ? "Sparisce col prossimo avvio dell'app."
             : "Sparisce davvero fra \(giorni) \(giorni == 1 ? "giorno" : "giorni").")
            .font(Caratteri.interfaccia(12.5))
            .foregroundStyle(Color(esadecimale: "#f0cfc9"))
            .padding(12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(Color(esadecimale: "#e06d5c").opacity(0.12),
                        in: RoundedRectangle(cornerRadius: 10))
    }

    // MARK: - Testi e azioni

    private func dataEstesa(_ data: Date) -> String {
        let formato = DateFormatter()
        formato.locale = Locale(identifier: "it_IT")
        formato.dateFormat = "EEEE d MMMM yyyy · HH:mm"
        return formato.string(from: data).capitalized
    }

    private func descrizioneLuogo(_ elemento: Elemento) -> String {
        let coordinate = String(format: "%.4f° N, %.4f° E",
                                elemento.latitudine ?? 0, elemento.longitudine ?? 0)
        guard let luogo = elemento.luogo, !luogo.isEmpty else { return coordinate }
        return "\(luogo) · \(coordinate)"
    }

    private func formatta(_ numero: Double) -> String {
        numero == numero.rounded() ? String(Int(numero)) : String(format: "%.1f", numero)
    }

    private func tempoDiPosa(_ secondi: Double?) -> String {
        guard let secondi, secondi > 0 else { return "—" }
        if secondi >= 1 { return String(format: "%.1fs", secondi) }
        return "1/\(Int((1 / secondi).rounded()))"
    }

    private func attacca(_ etichetta: Etichetta, a elemento: Elemento) {
        elemento.etichette.append(etichetta)
        try? contesto.save()
    }

    private func stacca(_ etichetta: Etichetta, da elemento: Elemento) {
        elemento.etichette.removeAll { $0.persistentModelID == etichetta.persistentModelID }
        try? contesto.save()
    }

    private func caricaAnteprima() async {
        miniatura = nil
        guard let elemento else { return }
        let impronta = elemento.improntaFile
        let file = elemento.url
        let tipo = elemento.tipo
        miniatura = await Miniature.condivisa.miniatura(impronta: impronta, file: file, tipo: tipo)
    }
}

/// Mette le pastiglie in fila e va a capo quando finisce lo spazio.
struct FlussoPastiglie: Layout {
    var spaziaturaX: CGFloat = 6
    var spaziaturaY: CGFloat = 6

    func sizeThatFits(proposal: ProposedViewSize, subviews: Subviews, cache: inout ()) -> CGSize {
        let larghezzaMassima = proposal.width ?? 260
        var x: CGFloat = 0
        var y: CGFloat = 0
        var altezzaRiga: CGFloat = 0

        for vista in subviews {
            let misura = vista.sizeThatFits(.unspecified)
            if x + misura.width > larghezzaMassima, x > 0 {
                x = 0
                y += altezzaRiga + spaziaturaY
                altezzaRiga = 0
            }
            x += misura.width + spaziaturaX
            altezzaRiga = max(altezzaRiga, misura.height)
        }
        return CGSize(width: larghezzaMassima, height: y + altezzaRiga)
    }

    func placeSubviews(in bounds: CGRect, proposal: ProposedViewSize,
                       subviews: Subviews, cache: inout ()) {
        var x = bounds.minX
        var y = bounds.minY
        var altezzaRiga: CGFloat = 0

        for vista in subviews {
            let misura = vista.sizeThatFits(.unspecified)
            if x + misura.width > bounds.maxX, x > bounds.minX {
                x = bounds.minX
                y += altezzaRiga + spaziaturaY
                altezzaRiga = 0
            }
            vista.place(at: CGPoint(x: x, y: y), proposal: ProposedViewSize(misura))
            x += misura.width + spaziaturaX
            altezzaRiga = max(altezzaRiga, misura.height)
        }
    }
}
