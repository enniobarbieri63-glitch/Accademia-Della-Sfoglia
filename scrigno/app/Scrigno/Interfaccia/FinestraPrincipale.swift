import AppKit
import SwiftData
import SwiftUI

struct FinestraPrincipale: View {
    let guasto: String?

    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto

    @Query private var elementi: [Elemento]
    @State private var importatore = Importatore()
    @State private var guastoLetto = false

    var body: some View {
        @Bindable var nav = navigazione

        Group {
            if elementi.isEmpty && !navigazione.mostraImporta {
                PrimoAvvio(importatore: importatore)
            } else {
                HStack(spacing: 0) {
                    BarraLaterale()
                        .frame(width: 232)
                    Rectangle()
                        .fill(Colori.linea)
                        .frame(width: 1)
                    contenuto
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                }
            }
        }
        .background(Colori.finestra)
        .sheet(isPresented: $nav.mostraImporta) {
            FinestraImporta(importatore: importatore)
        }
        .alert("Il catalogo non si è aperto", isPresented: .constant(guasto != nil && !guastoLetto)) {
            Button("Ho capito") { guastoLetto = true }
            Button("Apri la cartella") {
                NSWorkspace.shared.open(Libreria.condivisa.radice.deletingLastPathComponent())
                guastoLetto = true
            }
        } message: {
            Text(guasto ?? "")
        }
        .task {
            Catalogo.preparaSeVuoto(contesto)
            Catalogo.svuotaCestinoScaduto(contesto)
        }
    }

    @ViewBuilder
    private var contenuto: some View {
        switch navigazione.schermata {
        case .libreria: SchermataLibreria()
        case .video: SchermataVideo()
        case .modifica: SchermataModifica()
        case .documenti: SchermataDocumenti()
        case .persone: SchermataPersoneLuoghi()
        case .doppioni: SchermataDoppioni()
        }
    }
}

/// La riga in alto, uguale in tutte le schermate: titolo, conteggio, ricerca,
/// pulsanti. Cambia solo quello che le si mette dentro.
struct BarraStrumenti<Centro: View, Destra: View>: View {
    let titolo: String
    let sottotitolo: String
    @ViewBuilder var centro: Centro
    @ViewBuilder var destra: Destra

    var body: some View {
        HStack(spacing: 14) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(titolo)
                    .font(Caratteri.interfaccia(15, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Text(sottotitolo)
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testoSecondario)
            }
            .lineLimit(1)
            .fixedSize(horizontal: true, vertical: false)

            centro
            Spacer(minLength: 8)
            destra
        }
        .padding(.horizontal, 16)
        .frame(height: 56)
        .overlay(alignment: .bottom) {
            Rectangle().fill(Colori.linea).frame(height: 1)
        }
    }
}

/// Il gruppo di pulsanti a segmenti: Anni / Mesi / Giorni / Tutti.
struct Segmenti<Valore: Hashable>: View {
    let voci: [(Valore, String)]
    @Binding var scelta: Valore

    var body: some View {
        HStack(spacing: 2) {
            ForEach(voci, id: \.0) { voce in
                Button {
                    scelta = voce.0
                } label: {
                    Text(voce.1)
                        .font(Caratteri.interfaccia(12.5))
                        .padding(.horizontal, 11)
                        .padding(.vertical, 4)
                        .foregroundStyle(scelta == voce.0 ? Colori.testo : Colori.testoSecondario)
                        .background(scelta == voce.0 ? Colori.rialzato : .clear,
                                    in: RoundedRectangle(cornerRadius: 6))
                }
                .buttonStyle(.plain)
            }
        }
        .padding(2)
        .background(Colori.barra, in: RoundedRectangle(cornerRadius: 8))
        .overlay { RoundedRectangle(cornerRadius: 8).strokeBorder(Colori.linea) }
    }
}

/// Il campo di ricerca dell'anteprima: lente, testo, scritta ⌘F a destra.
struct CampoRicerca: View {
    let invito: String
    @Binding var testo: String
    var larghezza: CGFloat = 290

    @FocusState private var aFuoco: Bool
    @Environment(Navigazione.self) private var navigazione

    var body: some View {
        HStack(spacing: 8) {
            Image(systemName: "magnifyingglass")
                .font(.system(size: 12))
                .foregroundStyle(Colori.testoSecondario)

            TextField(invito, text: $testo)
                .textFieldStyle(.plain)
                .font(Caratteri.interfaccia(13))
                .foregroundStyle(Colori.testo)
                .focused($aFuoco)

            if testo.isEmpty {
                Text("⌘F")
                    .font(Caratteri.dati(10.5))
                    .foregroundStyle(Colori.testoTenue)
                    .padding(.horizontal, 5)
                    .overlay { RoundedRectangle(cornerRadius: 4).strokeBorder(Colori.lineaChiara) }
            } else {
                Button {
                    testo = ""
                } label: {
                    Image(systemName: "xmark.circle.fill")
                        .foregroundStyle(Colori.testoTenue)
                }
                .buttonStyle(.plain)
                .help("Svuota la ricerca")
            }
        }
        .padding(.horizontal, 10)
        .padding(.vertical, 6)
        .frame(width: larghezza)
        .background(Colori.barra, in: RoundedRectangle(cornerRadius: 8))
        .overlay { RoundedRectangle(cornerRadius: 8).strokeBorder(Colori.linea) }
        .onChange(of: navigazione.chiediFuocoRicerca) { _, chiesto in
            if chiesto {
                aFuoco = true
                navigazione.chiediFuocoRicerca = false
            }
        }
    }
}

/// Il messaggio che compare quando un cassetto è vuoto. Meglio dire cosa manca
/// che lasciare uno spazio bianco senza spiegazioni.
struct NienteQui: View {
    let titolo: String
    let spiegazione: String
    var azione: (testo: String, fai: () -> Void)?

    var body: some View {
        VStack(spacing: 10) {
            Text(titolo)
                .font(Caratteri.interfaccia(15, peso: .semibold))
                .foregroundStyle(Colori.testo)
            Text(spiegazione)
                .font(Caratteri.interfaccia(13))
                .foregroundStyle(Colori.testoSecondario)
                .multilineTextAlignment(.center)
                .frame(maxWidth: 380)
            if let azione {
                Button(azione.testo, action: azione.fai)
                    .buttonStyle(PulsanteContorno())
                    .padding(.top, 4)
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .padding(40)
    }
}
