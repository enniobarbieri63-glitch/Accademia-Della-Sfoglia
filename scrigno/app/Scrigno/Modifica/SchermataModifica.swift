import AppKit
import CoreImage
import SwiftData
import SwiftUI

struct SchermataModifica: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto
    @Query(sort: \Elemento.dataScatto, order: .reverse) private var tutti: [Elemento]

    @State private var modifiche = Modifiche()
    @State private var partenza: CIImage?
    @State private var resa: NSImage?
    @State private var anteprimaOriginale: NSImage?

    @State private var confronto = true
    @State private var divisione: Double = 0.5
    @State private var strumento: Strumento = .regola
    @State private var passato: [Modifiche] = []
    @State private var futuro: [Modifiche] = []
    @State private var salvataggioInCorso = false

    enum Strumento: String, CaseIterable, Identifiable {
        case regola = "Regola"
        case ritaglia = "Ritaglia"
        case pennello = "Ritocco"
        case automatico = "Auto"

        var id: String { rawValue }

        var simbolo: String {
            switch self {
            case .regola: "slider.horizontal.3"
            case .ritaglia: "crop"
            case .pennello: "paintbrush"
            case .automatico: "wand.and.stars"
            }
        }
    }

    private var elemento: Elemento? {
        if let aperto = navigazione.elementoAperto,
           let trovato = tutti.first(where: { $0.persistentModelID == aperto }) {
            return trovato
        }
        return tutti.first { $0.tipo == .foto && !$0.nelCestino }
    }

    var body: some View {
        VStack(spacing: 0) {
            barra
            if let elemento {
                HStack(spacing: 0) {
                    centro(elemento)
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                    Rectangle().fill(Colori.linea).frame(width: 1)
                    PannelloRegolazioni(
                        modifiche: $modifiche,
                        strumento: $strumento,
                        immagine: resa,
                        haProfondita: elemento.tipo == .foto,
                        prima: { segnaPassato() },
                        cambiato: { rigenera() }
                    )
                    .frame(width: 300)
                }
            } else {
                NienteQui(titolo: "Nessuna foto da modificare",
                          spiegazione: "Scegli una foto in Libreria e premi Invio, oppure fai doppio clic.",
                          azione: ("Vai in Libreria", { navigazione.vai(.libreria) }))
            }
        }
        .background(Colori.sfondoScuro)
        .task(id: elemento?.persistentModelID) { await carica() }
    }

    // MARK: - Barra in alto

    private var barra: some View {
        BarraStrumenti(titolo: elemento?.nomeFile ?? "Modifica",
                       sottotitolo: "l'originale resta intatto") {
            Segmenti(voci: [(false, "Solo modifica"), (true, "Prima / dopo")],
                     scelta: $confronto)
        } destra: {
            HStack(spacing: 8) {
                Button {
                    annulla()
                } label: { Image(systemName: "arrow.uturn.backward") }
                    .buttonStyle(.plain)
                    .foregroundStyle(passato.isEmpty ? Colori.testoTenue : Colori.testoSecondario)
                    .disabled(passato.isEmpty)
                    .keyboardShortcut("z", modifiers: .command)
                    .help("Annulla")

                Button {
                    ripeti()
                } label: { Image(systemName: "arrow.uturn.forward") }
                    .buttonStyle(.plain)
                    .foregroundStyle(futuro.isEmpty ? Colori.testoTenue : Colori.testoSecondario)
                    .disabled(futuro.isEmpty)
                    .keyboardShortcut("z", modifiers: [.command, .shift])
                    .help("Ripeti")

                Button("Ripristina originale") { ripristina() }
                    .buttonStyle(PulsanteContorno())
                    .disabled(modifiche.nessuna)

                Button(salvataggioInCorso ? "Salvo…" : "Fatto") { salva() }
                    .buttonStyle(PulsanteAccento())
                    .disabled(salvataggioInCorso)
            }
        }
    }

    // MARK: - La foto al centro

    private func centro(_ elemento: Elemento) -> some View {
        VStack(spacing: 12) {
            GeometryReader { spazio in
                let riquadro = riquadroFoto(in: spazio.size)
                ZStack {
                    if let resa {
                        // Sotto c'è l'originale, sopra la versione modificata,
                        // tagliata dove sta la maniglia bianca.
                        if confronto, let anteprimaOriginale {
                            Image(nsImage: anteprimaOriginale)
                                .resizable()
                                .aspectRatio(contentMode: .fit)
                        }
                        Image(nsImage: resa)
                            .resizable()
                            .aspectRatio(contentMode: .fit)
                            .mask(alignment: .trailing) {
                                GeometryReader { dentro in
                                    Rectangle()
                                        .frame(width: confronto
                                               ? dentro.size.width * (1 - divisione)
                                               : dentro.size.width)
                                        .frame(maxWidth: .infinity, alignment: .trailing)
                                }
                            }
                    } else {
                        ProgressView().controlSize(.small)
                    }

                    if strumento == .ritaglia {
                        TelaRitaglio(ritaglio: Binding(
                            get: { modifiche.ritaglio ?? Ritaglio() },
                            set: { modifiche.ritaglio = $0; rigenera() }
                        ), prima: { segnaPassato() })
                    } else {
                        GrigliaDeiTerzi()
                    }

                    if strumento == .pennello, elemento.tipo == .foto {
                        TelaPennello(modifiche: $modifiche,
                                     misura: CGSize(width: max(1, elemento.larghezza),
                                                    height: max(1, elemento.altezza)),
                                     prima: { segnaPassato() },
                                     cambiato: { rigenera() })
                    }

                    if confronto { maniglia(in: riquadro) }
                }
                .frame(width: spazio.size.width, height: spazio.size.height)
                .contentShape(Rectangle())
                .onTapGesture { punto in
                    guard strumento == .regola, modifiche.sfocatura > 0 else { return }
                    puntaIlFuoco(punto, in: spazio.size)
                }
            }
            .padding(.horizontal, 20)
            .padding(.top, 18)

            StiliPronti(scelto: modifiche.stile, anteprima: anteprimaOriginale) { stile in
                segnaPassato()
                modifiche.stile = stile.rawValue
                rigenera()
            }
            .padding(.bottom, 14)
        }
    }

    private func riquadroFoto(in misura: CGSize) -> CGRect {
        guard let resa, resa.size.width > 0, resa.size.height > 0 else {
            return CGRect(origin: .zero, size: misura)
        }
        let rapporto = resa.size.width / resa.size.height
        var larghezza = misura.width
        var altezza = larghezza / rapporto
        if altezza > misura.height {
            altezza = misura.height
            larghezza = altezza * rapporto
        }
        return CGRect(x: (misura.width - larghezza) / 2,
                      y: (misura.height - altezza) / 2,
                      width: larghezza, height: altezza)
    }

    private func maniglia(in riquadro: CGRect) -> some View {
        GeometryReader { spazio in
            let x = spazio.size.width * divisione
            ZStack(alignment: .top) {
                Rectangle()
                    .fill(.white)
                    .frame(width: 2)
                Image(systemName: "chevron.left.chevron.right")
                    .font(.system(size: 11, weight: .bold))
                    .foregroundStyle(.black)
                    .frame(width: 30, height: 30)
                    .background(.white, in: Circle())
                    .offset(y: spazio.size.height / 2 - 15)
            }
            .position(x: x, y: spazio.size.height / 2)
            .gesture(
                DragGesture()
                    .onChanged { movimento in
                        divisione = min(1, max(0, movimento.location.x / spazio.size.width))
                    }
            )
            .overlay(alignment: .topLeading) {
                etichetta("PRIMA").padding(10)
            }
            .overlay(alignment: .topTrailing) {
                etichetta("DOPO").padding(10)
            }
        }
    }

    private func etichetta(_ testo: String) -> some View {
        Text(testo)
            .font(.system(size: 11, weight: .semibold))
            .tracking(0.8)
            .foregroundStyle(.white)
            .padding(.horizontal, 8)
            .padding(.vertical, 3)
            .background(.black.opacity(0.5), in: RoundedRectangle(cornerRadius: 4))
    }

    private func puntaIlFuoco(_ punto: CGPoint, in misura: CGSize) {
        let riquadro = riquadroFoto(in: misura)
        guard riquadro.contains(punto) else { return }
        segnaPassato()
        modifiche.fuocoX = (punto.x - riquadro.minX) / riquadro.width
        modifiche.fuocoY = (punto.y - riquadro.minY) / riquadro.height
        rigenera()
    }

    // MARK: - Caricare, rigenerare, salvare

    private func carica() async {
        guard let elemento else { return }
        modifiche = elemento.modifiche
        passato = []
        futuro = []

        let url = elemento.url
        // Si lavora su una copia ridotta: sulla foto piena ogni cursore
        // farebbe aspettare mezzo secondo.
        guard let ridotta = Impronte.immagineRidotta(di: url, lato: 1800) else {
            partenza = nil
            resa = nil
            return
        }

        partenza = CIImage(cgImage: ridotta)
        anteprimaOriginale = NSImage(cgImage: ridotta,
                                     size: NSSize(width: ridotta.width, height: ridotta.height))
        rigenera()
    }

    private func rigenera() {
        guard let partenza else { return }
        let risultato = MotoreImmagine.applica(modifiche, a: partenza,
                                               fileOriginale: elemento?.url)
        guard let cg = MotoreImmagine.contesto.createCGImage(risultato, from: risultato.extent)
        else { return }
        resa = NSImage(cgImage: cg, size: NSSize(width: cg.width, height: cg.height))
    }

    private func segnaPassato() {
        passato.append(modifiche)
        if passato.count > 40 { passato.removeFirst() }
        futuro = []
    }

    private func annulla() {
        guard let ultima = passato.popLast() else { return }
        futuro.append(modifiche)
        modifiche = ultima
        rigenera()
    }

    private func ripeti() {
        guard let prossima = futuro.popLast() else { return }
        passato.append(modifiche)
        modifiche = prossima
        rigenera()
    }

    private func ripristina() {
        segnaPassato()
        MaschereDipinte.elimina(modifiche.maschera)
        modifiche = Modifiche()
        rigenera()
    }

    /// Salva solo le istruzioni, mai il file. L'originale resta quello che è.
    private func salva() {
        guard let elemento else { return }
        salvataggioInCorso = true
        elemento.modifiche = modifiche
        try? contesto.save()

        // La miniatura deve mostrare la foto come è adesso.
        let impronta = elemento.improntaFile
        Task {
            await Miniature.condivisa.dimentica(impronta)
            salvataggioInCorso = false
            navigazione.vai(.libreria)
        }
    }
}

/// La griglia dei terzi, per raddrizzare a occhio.
struct GrigliaDeiTerzi: View {
    var body: some View {
        GeometryReader { spazio in
            Path { tracciato in
                for frazione in [1.0 / 3, 2.0 / 3] {
                    tracciato.move(to: CGPoint(x: spazio.size.width * frazione, y: 0))
                    tracciato.addLine(to: CGPoint(x: spazio.size.width * frazione, y: spazio.size.height))
                    tracciato.move(to: CGPoint(x: 0, y: spazio.size.height * frazione))
                    tracciato.addLine(to: CGPoint(x: spazio.size.width, y: spazio.size.height * frazione))
                }
            }
            .stroke(.white.opacity(0.22), lineWidth: 1)
        }
        .allowsHitTesting(false)
    }
}

/// Gli otto stili pronti sotto la foto.
struct StiliPronti: View {
    let scelto: String
    let anteprima: NSImage?
    let scegli: (Stile) -> Void

    var body: some View {
        ScrollView(.horizontal) {
            HStack(spacing: 8) {
                ForEach(Stile.allCases) { stile in
                    Button {
                        scegli(stile)
                    } label: {
                        VStack(spacing: 5) {
                            ZStack {
                                Colori.pannello
                                if let anteprima {
                                    Image(nsImage: anteprima)
                                        .resizable()
                                        .aspectRatio(contentMode: .fill)
                                        .saturation(saturazione(stile))
                                        .contrast(contrasto(stile))
                                        .grayscale(stile.regolazioni.monocromatico ? 1 : 0)
                                }
                            }
                            .frame(width: 92, height: 62)
                            .clipShape(RoundedRectangle(cornerRadius: 6))
                            .overlay {
                                RoundedRectangle(cornerRadius: 6)
                                    .strokeBorder(scelto == stile.rawValue ? Colori.accento : .clear,
                                                  lineWidth: 2)
                            }

                            Text(stile.rawValue)
                                .font(Caratteri.interfaccia(11.5))
                                .foregroundStyle(scelto == stile.rawValue
                                                 ? Colori.testo : Colori.testoSecondario)
                        }
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(.horizontal, 20)
        }
        .scrollIndicators(.never)
    }

    private func saturazione(_ stile: Stile) -> Double {
        1 + stile.regolazioni.saturazione / 100 * 0.8
    }

    private func contrasto(_ stile: Stile) -> Double {
        1 + stile.regolazioni.contrasto / 100 * 0.5
    }
}
