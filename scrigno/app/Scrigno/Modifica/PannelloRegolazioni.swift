import CoreImage
import AppKit
import SwiftUI

/// La colonna di destra della schermata Modifica: istogramma, strumenti e
/// cursori raccolti in gruppi che si aprono e si chiudono.
struct PannelloRegolazioni: View {
    @Binding var modifiche: Modifiche
    @Binding var strumento: SchermataModifica.Strumento
    let immagine: NSImage?
    let haProfondita: Bool
    let prima: () -> Void
    let cambiato: () -> Void

    @State private var apertoLuce = true
    @State private var apertoColore = true
    @State private var apertoGeometria = false
    @State private var apertoSfocatura = false
    @State private var apertoDettaglio = false
    @State private var apertoVignettatura = false

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 0) {
                Istogramma(immagine: immagine)
                    .frame(height: 78)
                    .background(Colori.pannello, in: RoundedRectangle(cornerRadius: 8))

                strumenti.padding(.top, 14)

                gruppo("Luce", aperto: $apertoLuce) {
                    cursore("Esposizione", $modifiche.esposizione, da: -100, a: 100)
                    cursore("Contrasto", $modifiche.contrasto, da: -100, a: 100)
                    cursore("Alte luci", $modifiche.alteLuci, da: -100, a: 100)
                    cursore("Ombre", $modifiche.ombre, da: -100, a: 100)
                }

                gruppo("Colore", aperto: $apertoColore) {
                    cursore("Temperatura", $modifiche.temperatura, da: -100, a: 100,
                            sfumatura: [Color(esadecimale: "#5d8fe6"),
                                        Color(esadecimale: "#ddd3c0"),
                                        Color(esadecimale: "#e9a13f")])
                    cursore("Saturazione", $modifiche.saturazione, da: -100, a: 100,
                            sfumatura: [Color(esadecimale: "#777777"),
                                        Color(esadecimale: "#e0707c"),
                                        Color(esadecimale: "#e9ae4f")])
                }

                gruppo("Geometria", aperto: $apertoGeometria) {
                    cursore("Raddrizza", $modifiche.raddrizza, da: -15, a: 15, unita: "°")
                    cursore("Linee cadenti", $modifiche.prospettivaVerticale, da: -100, a: 100)
                    cursore("Prospettiva laterale", $modifiche.prospettivaOrizzontale, da: -100, a: 100)
                    pulsantiGeometria
                }

                gruppo("Sfocatura", aperto: $apertoSfocatura) {
                    cursore("Intensità", $modifiche.sfocatura, da: 0, a: 100)
                    if modifiche.sfocatura > 0 {
                        modiSfocatura
                        cursore("Profondità di campo", $modifiche.profonditaCampo, da: 0, a: 100)
                        if modifiche.sfocaturaModo == ModoSfocatura.banda.rawValue {
                            cursore("Inclinazione", $modifiche.inclinazioneFuoco, da: -90, a: 90, unita: "°")
                        }
                        Text(spiegazioneSfocatura)
                            .font(Caratteri.interfaccia(11.5))
                            .foregroundStyle(Colori.testoTenue)
                            .fixedSize(horizontal: false, vertical: true)
                            .padding(.top, 2)
                    }
                }

                gruppo("Dettaglio", aperto: $apertoDettaglio) {
                    cursore("Nitidezza", $modifiche.nitidezza, da: 0, a: 100)
                    cursore("Riduzione rumore", $modifiche.riduzioneRumore, da: 0, a: 100)
                }

                gruppo("Vignettatura", aperto: $apertoVignettatura) {
                    cursore("Intensità", $modifiche.vignettatura, da: 0, a: 100)
                }
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 14)
        }
        .background(Colori.barra)
    }

    // MARK: - Strumenti

    private var strumenti: some View {
        HStack(spacing: 6) {
            ForEach(SchermataModifica.Strumento.allCases) { quale in
                Button {
                    if quale == .automatico {
                        applicaAuto()
                    } else {
                        strumento = quale
                    }
                } label: {
                    VStack(spacing: 4) {
                        Image(systemName: quale.simbolo).font(.system(size: 15))
                        Text(quale.rawValue).font(Caratteri.interfaccia(11))
                    }
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 8)
                    .foregroundStyle(strumento == quale ? Colori.accento : Colori.testoSecondario)
                    .background(strumento == quale ? Colori.accentoVelato : Colori.pannello,
                                in: RoundedRectangle(cornerRadius: 8))
                    .overlay {
                        RoundedRectangle(cornerRadius: 8)
                            .strokeBorder(strumento == quale ? Colori.accento : Colori.linea)
                    }
                }
                .buttonStyle(.plain)
            }
        }
    }

    private var pulsantiGeometria: some View {
        HStack(spacing: 6) {
            pulsantino("rotate.left", "Ruota a sinistra") {
                prima(); modifiche.rotazioneQuarti -= 1; cambiato()
            }
            pulsantino("rotate.right", "Ruota a destra") {
                prima(); modifiche.rotazioneQuarti += 1; cambiato()
            }
            pulsantino("arrow.left.and.right.righttriangle.left.righttriangle.right",
                       "Ribalta in orizzontale", attivo: modifiche.ribaltaOrizzontale) {
                prima(); modifiche.ribaltaOrizzontale.toggle(); cambiato()
            }
            pulsantino("arrow.up.and.down.righttriangle.up.righttriangle.down",
                       "Ribalta in verticale", attivo: modifiche.ribaltaVerticale) {
                prima(); modifiche.ribaltaVerticale.toggle(); cambiato()
            }
        }
        .padding(.top, 2)
    }

    private func pulsantino(_ simbolo: String, _ aiuto: String, attivo: Bool = false,
                            fai: @escaping () -> Void) -> some View {
        Button(action: fai) {
            Image(systemName: simbolo)
                .font(.system(size: 13))
                .frame(maxWidth: .infinity)
                .padding(.vertical, 7)
                .foregroundStyle(attivo ? Colori.accento : Colori.testoSecondario)
                .background(attivo ? Colori.accentoVelato : Colori.pannello,
                            in: RoundedRectangle(cornerRadius: 7))
        }
        .buttonStyle(.plain)
        .help(aiuto)
    }

    private var modiSfocatura: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(spacing: 5) {
                ForEach(ModoSfocatura.allCases) { modo in
                    let scelto = modifiche.sfocaturaModo == modo.rawValue
                    Button {
                        prima()
                        modifiche.sfocaturaModo = modo.rawValue
                        cambiato()
                    } label: {
                        VStack(spacing: 3) {
                            Image(systemName: modo.simbolo).font(.system(size: 12))
                            Text(modo.rawValue)
                                .font(Caratteri.interfaccia(9.5))
                                .lineLimit(1)
                                .minimumScaleFactor(0.8)
                        }
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 6)
                        .foregroundStyle(scelto ? Colori.accento : Colori.testoSecondario)
                        .background(scelto ? Colori.accentoVelato : Colori.pannello,
                                    in: RoundedRectangle(cornerRadius: 7))
                    }
                    .buttonStyle(.plain)
                    .help(modo.spiegazione)
                }
            }
        }
        .padding(.vertical, 2)
    }

    private var spiegazioneSfocatura: String {
        let modo = ModoSfocatura(rawValue: modifiche.sfocaturaModo) ?? .cerchio
        switch modo {
        case .cerchio:
            return "Clicca sulla foto per spostare il punto a fuoco."
        case .banda:
            return "Clicca sulla foto per spostare la fascia, poi inclinala."
        case .pennello:
            return "Scegli lo strumento Ritocco qui sopra e dipingi la zona da sfocare."
        case .profondita:
            return haProfondita
                ? "Funziona solo con le foto che portano con sé le distanze (ritratti dal telefono). Sulle altre ricade sul cerchio."
                : "Questa foto non porta con sé le distanze: viene usato il cerchio."
        }
    }

    private func applicaAuto() {
        prima()
        var proposta = Modifiche()
        if let immagine, let cg = immagine.cgImage(forProposedRect: nil, context: nil, hints: nil) {
            proposta = MotoreImmagine.regolazioniAutomatiche(per: CIImage(cgImage: cg))
        }
        // L'automatico tocca solo luce e colore: geometria, ritaglio e sfocatura
        // restano quelle scelte a mano.
        modifiche.esposizione = proposta.esposizione
        modifiche.contrasto = proposta.contrasto
        modifiche.alteLuci = proposta.alteLuci
        modifiche.ombre = proposta.ombre
        modifiche.saturazione = proposta.saturazione
        cambiato()
    }

    // MARK: - Pezzi riusabili

    @ViewBuilder
    private func gruppo<Contenuto: View>(_ titolo: String, aperto: Binding<Bool>,
                                         @ViewBuilder _ dentro: () -> Contenuto) -> some View {
        VStack(alignment: .leading, spacing: 0) {
            Rectangle().fill(Colori.linea).frame(height: 1)

            Button {
                withAnimation(.easeOut(duration: 0.15)) { aperto.wrappedValue.toggle() }
            } label: {
                HStack {
                    Text(titolo)
                        .font(Caratteri.interfaccia(13, peso: .semibold))
                        .foregroundStyle(Colori.testo)
                    Spacer()
                    Image(systemName: "chevron.down")
                        .font(.system(size: 10, weight: .semibold))
                        .foregroundStyle(Colori.testoSecondario)
                        .rotationEffect(.degrees(aperto.wrappedValue ? 0 : -90))
                }
                .padding(.vertical, 10)
                .contentShape(Rectangle())
            }
            .buttonStyle(.plain)

            if aperto.wrappedValue {
                VStack(alignment: .leading, spacing: 10) {
                    dentro()
                }
                .padding(.bottom, 10)
            }
        }
    }

    private func cursore(_ nome: String, _ valore: Binding<Double>,
                         da minimo: Double, a massimo: Double,
                         unita: String = "", sfumatura: [Color]? = nil) -> some View {
        VStack(alignment: .leading, spacing: 2) {
            HStack {
                Text(nome)
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testo)
                Spacer()
                Text(mostra(valore.wrappedValue, unita: unita, partiDaZero: minimo >= 0))
                    .font(Caratteri.dati(11.5))
                    .monospacedDigit()
                    .foregroundStyle(Colori.testoSecondario)
            }

            Slider(value: valore, in: minimo...massimo) { iniziato in
                if iniziato { prima() }
            }
            .controlSize(.small)
            .tint(Colori.accento)
            .background(alignment: .center) {
                if let sfumatura {
                    LinearGradient(colors: sfumatura, startPoint: .leading, endPoint: .trailing)
                        .frame(height: 3)
                        .clipShape(Capsule())
                        .allowsHitTesting(false)
                }
            }
            .onChange(of: valore.wrappedValue) { _, _ in cambiato() }
        }
    }

    private func mostra(_ numero: Double, unita: String, partiDaZero: Bool) -> String {
        let arrotondato = numero.rounded()
        if partiDaZero || arrotondato == 0 {
            return "\(Int(arrotondato))\(unita)"
        }
        let segno = arrotondato > 0 ? "+" : "−"
        return "\(segno)\(Int(abs(arrotondato)))\(unita)"
    }
}

/// L'istogramma: quanto la foto è scura, media e chiara, canale per canale.
struct Istogramma: View {
    let immagine: NSImage?
    @State private var conteggi: [[Double]] = []

    var body: some View {
        GeometryReader { spazio in
            ZStack {
                ForEach(Array(conteggi.enumerated()), id: \.offset) { indice, canale in
                    Path { tracciato in
                        tracciato.move(to: CGPoint(x: 0, y: spazio.size.height))
                        for (posizione, valore) in canale.enumerated() {
                            let x = spazio.size.width * Double(posizione) / Double(max(1, canale.count - 1))
                            let y = spazio.size.height * (1 - valore * 0.92)
                            tracciato.addLine(to: CGPoint(x: x, y: y))
                        }
                        tracciato.addLine(to: CGPoint(x: spazio.size.width, y: spazio.size.height))
                        tracciato.closeSubpath()
                    }
                    .fill(colore(indice))
                }
            }
        }
        .clipShape(RoundedRectangle(cornerRadius: 8))
        .task(id: immagine) { conteggi = Self.calcola(immagine) }
    }

    private func colore(_ indice: Int) -> Color {
        switch indice {
        case 0: Color(esadecimale: "#e06d5c").opacity(0.55)
        case 1: Color(esadecimale: "#58b39c").opacity(0.5)
        default: Color(esadecimale: "#6f8cf0").opacity(0.5)
        }
    }

    /// Si contano i pixel di una copia piccolissima: per un istogramma basta e
    /// avanza, e non fa aspettare nessuno.
    static func calcola(_ immagine: NSImage?) -> [[Double]] {
        guard let immagine,
              let cg = immagine.cgImage(forProposedRect: nil, context: nil, hints: nil)
        else { return [] }

        let lato = 96
        var pixel = [UInt8](repeating: 0, count: lato * lato * 4)
        guard let spazio = CGColorSpace(name: CGColorSpace.sRGB),
              let contesto = CGContext(data: &pixel, width: lato, height: lato,
                                       bitsPerComponent: 8, bytesPerRow: lato * 4,
                                       space: spazio,
                                       bitmapInfo: CGImageAlphaInfo.premultipliedLast.rawValue)
        else { return [] }

        contesto.draw(cg, in: CGRect(x: 0, y: 0, width: lato, height: lato))

        let caselle = 48
        var conti = [[Double]](repeating: [Double](repeating: 0, count: caselle), count: 3)
        for posizione in stride(from: 0, to: pixel.count, by: 4) {
            for canale in 0..<3 {
                let valore = Int(pixel[posizione + canale]) * caselle / 256
                conti[canale][min(caselle - 1, valore)] += 1
            }
        }

        return conti.map { canale in
            let massimo = canale.max() ?? 1
            return canale.map { massimo > 0 ? $0 / massimo : 0 }
        }
    }
}
