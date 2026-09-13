import SwiftData
import SwiftUI

struct SchermataDoppioni: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto
    @Query(sort: \Elemento.dataScatto, order: .reverse) private var tutti: [Elemento]

    @State private var gruppi: [TrovaDoppioni.Gruppo] = []
    @State private var scelte: [UUID: PersistentIdentifier] = [:]
    @State private var lasciatiStare: Set<UUID> = []
    @State private var genereScelto = "Tutti"
    @State private var staCercando = false

    private var generi: [String] { ["Tutti"] + TrovaDoppioni.Genere.allCases.map(\.rawValue) }

    private var mostrati: [TrovaDoppioni.Gruppo] {
        guard genereScelto != "Tutti" else { return gruppi }
        return gruppi.filter { $0.genere.rawValue == genereScelto }
    }

    private var spazioRecuperabile: Int {
        gruppi
            .filter { !lasciatiStare.contains($0.id) }
            .reduce(0) { totale, gruppo in totale + spazioDi(gruppo) }
    }

    var body: some View {
        VStack(spacing: 0) {
            BarraStrumenti(titolo: "Possibili doppioni",
                           sottotitolo: "\(gruppi.count) \(gruppi.count == 1 ? "gruppo" : "gruppi")") {
                Segmenti(voci: generi.map { ($0, $0) }, scelta: $genereScelto)
            } destra: {
                if staCercando {
                    ProgressView().controlSize(.small)
                } else {
                    Button("Applica i suggerimenti") { applicaTutto() }
                        .buttonStyle(PulsanteAccento())
                        .disabled(gruppi.isEmpty)
                }
            }

            if gruppi.isEmpty && !staCercando {
                NienteQui(titolo: "Nessun doppione",
                          spiegazione: "Non ho trovato file ripetuti, raffiche o la stessa foto salvata in due modi. È una buona notizia.",
                          azione: ("Controlla di nuovo", { cerca() }))
            } else {
                HStack(spacing: 0) {
                    elencoGruppi
                        .frame(maxWidth: .infinity)
                    Rectangle().fill(Colori.linea).frame(width: 1)
                    riepilogo
                        .frame(width: 280)
                }
            }
        }
        .background(Colori.finestra)
        .task { if gruppi.isEmpty { cerca() } }
    }

    // MARK: - Elenco

    private var elencoGruppi: some View {
        ScrollView {
            LazyVStack(spacing: 14) {
                ForEach(mostrati) { gruppo in
                    riquadroGruppo(gruppo)
                }
            }
            .padding(20)
        }
    }

    private func riquadroGruppo(_ gruppo: TrovaDoppioni.Gruppo) -> some View {
        let lasciato = lasciatiStare.contains(gruppo.id)

        return VStack(alignment: .leading, spacing: 12) {
            HStack(spacing: 10) {
                Text(titolo(gruppo))
                    .font(Caratteri.interfaccia(13, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Text(gruppo.perche)
                    .font(Caratteri.interfaccia(12))
                    .foregroundStyle(Colori.testoSecondario)
                Spacer()
                Button(lasciato ? "Scegli di nuovo" : "Tienile tutte") {
                    if lasciato { lasciatiStare.remove(gruppo.id) }
                    else { lasciatiStare.insert(gruppo.id) }
                }
                .buttonStyle(PulsanteContorno())
            }

            LazyVGrid(columns: [GridItem(.adaptive(minimum: 150), spacing: 10)], spacing: 10) {
                ForEach(gruppo.elementi) { elemento in
                    riquadroScelta(elemento, in: gruppo, lasciato: lasciato)
                }
            }
        }
        .padding(14)
        .background(Colori.pannello, in: RoundedRectangle(cornerRadius: 12))
        .overlay { RoundedRectangle(cornerRadius: 12).strokeBorder(Colori.linea) }
    }

    private func riquadroScelta(_ elemento: Elemento, in gruppo: TrovaDoppioni.Gruppo,
                                lasciato: Bool) -> some View {
        let tenuto = lasciato || tenutoIn(gruppo) == elemento.persistentModelID

        return Button {
            guard !lasciato else { return }
            scelte[gruppo.id] = elemento.persistentModelID
        } label: {
            VStack(spacing: 0) {
                ZStack(alignment: .topLeading) {
                    RiquadroElemento(elemento: elemento, selezionato: false)
                        .frame(height: 128)
                        .opacity(tenuto ? 1 : 0.42)

                    Text(tenuto ? "Da tenere" : "Nel cestino")
                        .font(Caratteri.interfaccia(11, peso: .semibold))
                        .foregroundStyle(tenuto ? Colori.suAccento : Color(esadecimale: "#e0e0e0"))
                        .padding(.horizontal, 8)
                        .padding(.vertical, 2)
                        .background(tenuto ? Colori.accento : .black.opacity(0.7),
                                    in: RoundedRectangle(cornerRadius: 5))
                        .padding(8)
                }

                HStack {
                    Text("\(elemento.larghezza)×\(elemento.altezza)")
                    Spacer()
                    Text(Libreria.pesoLeggibile(elemento.peso))
                    Spacer()
                    Text(TrovaDoppioni.motivo(per: elemento, in: gruppoConScelta(gruppo)))
                }
                .font(Caratteri.dati(10.5))
                .foregroundStyle(Colori.testoSecondario)
                .padding(.horizontal, 10)
                .padding(.vertical, 8)
            }
            .background(Colori.barra)
            .clipShape(RoundedRectangle(cornerRadius: 8))
            .overlay {
                RoundedRectangle(cornerRadius: 8)
                    .strokeBorder(tenuto && !lasciato ? Colori.accento : .clear, lineWidth: 2)
            }
        }
        .buttonStyle(.plain)
    }

    private func titolo(_ gruppo: TrovaDoppioni.Gruppo) -> String {
        switch gruppo.genere {
        case .identici: "Stesso file, due volte"
        case .raffiche: "Scatti a raffica"
        case .formati: "Stessa foto, formati diversi"
        }
    }

    // MARK: - Riepilogo

    private var riepilogo: some View {
        VStack(alignment: .leading, spacing: 0) {
            Text("Spazio che puoi liberare").stileSezione()

            Text(Libreria.pesoLeggibile(spazioRecuperabile))
                .font(Caratteri.nomeGrande(42))
                .foregroundStyle(Colori.accento)
                .padding(.top, 6)

            Text("applicando i suggerimenti su tutti i gruppi. Clicca una miniatura per scegliere tu quale tenere.")
                .font(Caratteri.interfaccia(12.5))
                .foregroundStyle(Colori.testoSecondario)
                .fixedSize(horizontal: false, vertical: true)
                .padding(.top, 8)

            VStack(alignment: .leading, spacing: 8) {
                ForEach(TrovaDoppioni.Genere.allCases) { genere in
                    let quanto = gruppi
                        .filter { $0.genere == genere && !lasciatiStare.contains($0.id) }
                        .reduce(0) { $0 + spazioDi($1) }
                    HStack(spacing: 8) {
                        Circle().fill(colore(genere)).frame(width: 9, height: 9)
                        Text(genere.rawValue)
                            .font(Caratteri.interfaccia(12))
                            .foregroundStyle(Colori.testo)
                        Spacer()
                        Text(Libreria.pesoLeggibile(quanto))
                            .font(Caratteri.dati(11))
                            .foregroundStyle(Colori.testoSecondario)
                    }
                }
            }
            .padding(.top, 18)

            Text("Niente viene cancellato subito: quello che scarti resta \(Int(Libreria.giorniNelCestino)) giorni in Eliminati di recente.")
                .font(Caratteri.interfaccia(12.5))
                .foregroundStyle(Color(esadecimale: "#cfe6de"))
                .fixedSize(horizontal: false, vertical: true)
                .padding(12)
                .background(Color(esadecimale: "#58b39c").opacity(0.1),
                            in: RoundedRectangle(cornerRadius: 10))
                .padding(.top, 18)

            Spacer()
        }
        .padding(18)
        .frame(maxHeight: .infinity, alignment: .top)
        .background(Colori.barra)
    }

    private func colore(_ genere: TrovaDoppioni.Genere) -> Color {
        switch genere {
        case .identici: Color(esadecimale: "#6f8cf0")
        case .raffiche: Colori.accento
        case .formati: Color(esadecimale: "#58b39c")
        }
    }

    // MARK: - Conti e azioni

    private func tenutoIn(_ gruppo: TrovaDoppioni.Gruppo) -> PersistentIdentifier? {
        scelte[gruppo.id] ?? gruppo.daTenere?.persistentModelID
    }

    private func gruppoConScelta(_ gruppo: TrovaDoppioni.Gruppo) -> TrovaDoppioni.Gruppo {
        var copia = gruppo
        if let scelto = scelte[gruppo.id] {
            copia.daTenere = gruppo.elementi.first { $0.persistentModelID == scelto }
        }
        return copia
    }

    private func spazioDi(_ gruppo: TrovaDoppioni.Gruppo) -> Int {
        let tenuto = tenutoIn(gruppo)
        return gruppo.elementi
            .filter { $0.persistentModelID != tenuto }
            .reduce(0) { $0 + $1.peso }
    }

    private func cerca() {
        staCercando = true
        gruppi = TrovaDoppioni.cerca(fra: tutti)
        scelte = [:]
        lasciatiStare = []
        staCercando = false
    }

    /// Non cancella niente: sposta gli scarti in "Eliminati di recente", dove
    /// restano recuperabili per 30 giorni.
    private func applicaTutto() {
        let quando = Date()
        for gruppo in gruppi where !lasciatiStare.contains(gruppo.id) {
            let tenuto = tenutoIn(gruppo)
            for elemento in gruppo.elementi where elemento.persistentModelID != tenuto {
                elemento.eliminatoIl = quando
            }
        }
        try? contesto.save()
        cerca()
    }
}
