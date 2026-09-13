import SwiftData
import SwiftUI

struct BarraLaterale: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto

    @Query(sort: \Elemento.dataScatto, order: .reverse) private var elementi: [Elemento]
    @Query(sort: \Etichetta.ordine) private var etichette: [Etichetta]
    @Query(sort: \Album.creatoIl, order: .reverse) private var album: [Album]
    @Query private var persone: [Persona]

    @State private var spazio = Libreria.Spazio(usatoInLibreria: 0, liberoSulDisco: 0, totaleDelDisco: 0)
    @State private var nuovoAlbum = false
    @State private var nomeNuovoAlbum = ""

    private var vivi: [Elemento] { elementi.filter { !$0.nelCestino } }

    var body: some View {
        VStack(spacing: 0) {
            // Spazio per i tre pallini della finestra, che qui non hanno una barra
            // del titolo sotto cui stare.
            Color.clear.frame(height: 38)

            ScrollView {
                VStack(alignment: .leading, spacing: 0) {
                    gruppo("Libreria") {
                        voce("Tutto", simbolo: "square.grid.2x2", cassetto: .tutto, quanti: vivi.count)
                        ForEach(TipoFile.allCases) { tipo in
                            voce(tipo.nomePlurale, simbolo: tipo.simbolo,
                                 cassetto: .tipo(tipo),
                                 quanti: vivi.filter { $0.tipo == tipo }.count,
                                 vaA: schermataPer(tipo))
                        }
                    }

                    gruppo("Cassetti") {
                        vocePura("Persone", simbolo: "person.2", quanti: persone.filter { $0.confermata }.count,
                                 vaA: .persone)
                        vocePura("Luoghi", simbolo: "mappin.and.ellipse",
                                 quanti: Set(vivi.compactMap(\.luogo)).count, vaA: .persone)
                        voce("Preferiti", simbolo: "heart", cassetto: .preferiti,
                             quanti: vivi.filter { $0.preferito }.count)
                    }

                    gruppo("Raccolte intelligenti") {
                        voce("Senza etichetta", simbolo: "sparkles", cassetto: .senzaEtichetta,
                             quanti: vivi.filter { $0.etichette.isEmpty }.count)
                        vocePura("Possibili doppioni", simbolo: "sparkles", quanti: nil, vaA: .doppioni)
                        voce("Scontrini e ricevute", simbolo: "sparkles", cassetto: .scontrini,
                             quanti: vivi.filter { TrovaScontrini.sembraUnoScontrino($0) }.count)
                    }

                    if !album.isEmpty {
                        gruppo("Album") {
                            ForEach(album) { raccolta in
                                voce(raccolta.nome, simbolo: "rectangle.stack",
                                     cassetto: .album(raccolta.persistentModelID),
                                     quanti: raccolta.quanti)
                            }
                        }
                    }

                    gruppo("Etichette") {
                        ForEach(etichette) { etichetta in
                            voce(etichetta.nome, pallino: etichetta.colore,
                                 cassetto: .etichetta(etichetta.persistentModelID),
                                 quanti: etichetta.quanti)
                        }
                    }

                    Color.clear.frame(height: 14)
                    voce("Eliminati di recente", simbolo: "trash", cassetto: .cestino,
                         quanti: elementi.filter { $0.nelCestino }.count)

                    Color.clear.frame(height: 16)
                }
                .padding(.horizontal, 10)
            }
            .scrollIndicators(.hidden)

            barraSpazio
        }
        .background(Colori.barra)
        .task { spazio = Libreria.condivisa.spazio() }
        .onChange(of: elementi.count) { _, _ in
            spazio = Libreria.condivisa.spazio()
        }
    }

    private func schermataPer(_ tipo: TipoFile) -> Navigazione.Schermata? {
        // Video e Documenti hanno una schermata tutta loro: cliccarli ci porta lì.
        switch tipo {
        case .video: .video
        case .documento: .documenti
        default: nil
        }
    }

    // MARK: - Pezzi

    @ViewBuilder
    private func gruppo<Contenuto: View>(_ titolo: String,
                                         @ViewBuilder _ contenuto: () -> Contenuto) -> some View {
        Text(titolo)
            .stileSezione()
            .padding(.horizontal, 8)
            .padding(.top, 14)
            .padding(.bottom, 6)
        contenuto()
    }

    private func voce(_ titolo: String, simbolo: String? = nil, pallino: Color? = nil,
                      cassetto: Navigazione.Cassetto, quanti: Int?,
                      vaA: Navigazione.Schermata? = nil) -> some View {
        let attiva = navigazione.schermata == .libreria && navigazione.cassetto == cassetto

        return Button {
            if let vaA {
                navigazione.vai(vaA)
            } else {
                navigazione.apri(cassetto)
            }
        } label: {
            rigaVoce(titolo: titolo, simbolo: simbolo, pallino: pallino,
                     quanti: quanti, attiva: attiva)
        }
        .buttonStyle(.plain)
    }

    /// Una voce che porta a un'altra schermata invece di filtrare la libreria.
    private func vocePura(_ titolo: String, simbolo: String, quanti: Int?,
                          vaA: Navigazione.Schermata) -> some View {
        Button {
            navigazione.vai(vaA)
        } label: {
            rigaVoce(titolo: titolo, simbolo: simbolo, pallino: nil,
                     quanti: quanti, attiva: navigazione.schermata == vaA)
        }
        .buttonStyle(.plain)
    }

    private func rigaVoce(titolo: String, simbolo: String?, pallino: Color?,
                          quanti: Int?, attiva: Bool) -> some View {
        HStack(spacing: 10) {
            if let pallino {
                Circle().fill(pallino).frame(width: 9, height: 9).padding(.horizontal, 3)
            } else if let simbolo {
                Image(systemName: simbolo)
                    .font(.system(size: 13))
                    .frame(width: 16)
                    .foregroundStyle(attiva ? Colori.accento : Colori.testoSecondario)
            }

            Text(titolo)
                .font(Caratteri.interfaccia(13))
                .foregroundStyle(attiva ? Colori.testo : Colori.testo.opacity(0.82))
                .lineLimit(1)

            Spacer(minLength: 6)

            if let quanti {
                Text(quanti, format: .number)
                    .font(Caratteri.dati(11))
                    .monospacedDigit()
                    .foregroundStyle(Colori.testoTenue)
            }
        }
        .padding(.horizontal, 8)
        .padding(.vertical, 6)
        .background(attiva ? Colori.accentoVelato : .clear, in: RoundedRectangle(cornerRadius: 7))
        .contentShape(Rectangle())
    }

    // MARK: - Spazio su disco

    private var barraSpazio: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text("Disco del Mac")
                Spacer()
                Text(pesoDisco)
                    .font(Caratteri.dati(11))
                    .monospacedDigit()
            }
            .font(Caratteri.interfaccia(12))
            .foregroundStyle(Colori.testoSecondario)

            GeometryReader { spazioDisponibile in
                HStack(spacing: 0) {
                    ForEach(TipoFile.allCases) { tipo in
                        Colori.perTipo(tipo)
                            .frame(width: spazioDisponibile.size.width * frazione(tipo))
                    }
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(Colori.rialzato)
                .clipShape(RoundedRectangle(cornerRadius: 4))
            }
            .frame(height: 6)

            Text(riepilogoPesi)
                .font(Caratteri.interfaccia(11.5))
                .foregroundStyle(Colori.testoSecondario)
                .lineLimit(2)
        }
        .padding(.horizontal, 18)
        .padding(.vertical, 14)
        .overlay(alignment: .top) { Rectangle().fill(Colori.linea).frame(height: 1) }
    }

    private func pesoPerTipo(_ tipo: TipoFile) -> Int {
        vivi.filter { $0.tipo == tipo }.reduce(0) { $0 + $1.peso }
    }

    private func frazione(_ tipo: TipoFile) -> Double {
        guard spazio.totaleDelDisco > 0 else { return 0 }
        return Double(pesoPerTipo(tipo)) / Double(spazio.totaleDelDisco)
    }

    private var pesoDisco: String {
        guard spazio.totaleDelDisco > 0 else { return "—" }
        let usato = spazio.totaleDelDisco - spazio.liberoSulDisco
        return "\(Libreria.pesoLeggibile(usato)) / \(Libreria.pesoLeggibile(spazio.totaleDelDisco))"
    }

    private var riepilogoPesi: String {
        let voci = [TipoFile.foto, .video, .documento]
            .map { "\($0.nomePlurale) \(Libreria.pesoLeggibile(pesoPerTipo($0)))" }
        return voci.joined(separator: " · ")
    }
}
