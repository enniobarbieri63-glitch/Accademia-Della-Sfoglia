import SwiftUI

/// Il riquadro di ritaglio: si sposta trascinandolo dentro, si ridimensiona
/// dagli angoli. Le misure sono frazioni del lato, così restano giuste a
/// qualsiasi risoluzione.
struct TelaRitaglio: View {
    @Binding var ritaglio: Ritaglio
    let prima: () -> Void

    @State private var proporzioni: Proporzioni = .libero
    @State private var giaSegnato = false

    var body: some View {
        GeometryReader { spazio in
            let riquadro = CGRect(
                x: ritaglio.x * spazio.size.width,
                y: ritaglio.y * spazio.size.height,
                width: ritaglio.larghezza * spazio.size.width,
                height: ritaglio.altezza * spazio.size.height
            )

            ZStack {
                // Fuori dal ritaglio si scurisce, così si vede cosa resta.
                Path { tracciato in
                    tracciato.addRect(CGRect(origin: .zero, size: spazio.size))
                    tracciato.addRect(riquadro)
                }
                .fill(.black.opacity(0.55), style: FillStyle(eoFill: true))
                .allowsHitTesting(false)

                Rectangle()
                    .strokeBorder(.white, lineWidth: 1)
                    .frame(width: riquadro.width, height: riquadro.height)
                    .position(x: riquadro.midX, y: riquadro.midY)
                    .contentShape(Rectangle())
                    .gesture(trascinaTutto(in: spazio.size))

                ForEach(Angolo.allCases) { angolo in
                    maniglia(angolo, riquadro: riquadro, misura: spazio.size)
                }
            }
            .overlay(alignment: .bottom) { barraProporzioni }
        }
    }

    private enum Angolo: String, CaseIterable, Identifiable {
        case altoSinistra, altoDestra, bassoSinistra, bassoDestra
        var id: String { rawValue }
    }

    private func maniglia(_ angolo: Angolo, riquadro: CGRect, misura: CGSize) -> some View {
        let punto: CGPoint = switch angolo {
        case .altoSinistra: CGPoint(x: riquadro.minX, y: riquadro.minY)
        case .altoDestra: CGPoint(x: riquadro.maxX, y: riquadro.minY)
        case .bassoSinistra: CGPoint(x: riquadro.minX, y: riquadro.maxY)
        case .bassoDestra: CGPoint(x: riquadro.maxX, y: riquadro.maxY)
        }

        return Rectangle()
            .fill(.white)
            .frame(width: 16, height: 16)
            .overlay { Rectangle().strokeBorder(.black.opacity(0.3)) }
            .position(punto)
            .gesture(
                DragGesture()
                    .onChanged { movimento in
                        segnaUnaVoltaSola()
                        ridimensiona(angolo, verso: movimento.location, misura: misura)
                    }
                    .onEnded { _ in giaSegnato = false }
            )
    }

    private func trascinaTutto(in misura: CGSize) -> some Gesture {
        DragGesture()
            .onChanged { movimento in
                segnaUnaVoltaSola()
                let passoX = movimento.translation.width / misura.width
                let passoY = movimento.translation.height / misura.height
                var nuovo = ritaglio
                nuovo.x = min(max(0, ritaglio.x + passoX), 1 - ritaglio.larghezza)
                nuovo.y = min(max(0, ritaglio.y + passoY), 1 - ritaglio.altezza)
                ritaglio = nuovo
            }
            .onEnded { _ in giaSegnato = false }
    }

    private func ridimensiona(_ angolo: Angolo, verso punto: CGPoint, misura: CGSize) {
        let x = min(max(0, punto.x / misura.width), 1)
        let y = min(max(0, punto.y / misura.height), 1)

        var sinistra = ritaglio.x
        var alto = ritaglio.y
        var destra = ritaglio.x + ritaglio.larghezza
        var basso = ritaglio.y + ritaglio.altezza

        switch angolo {
        case .altoSinistra: sinistra = x; alto = y
        case .altoDestra: destra = x; alto = y
        case .bassoSinistra: sinistra = x; basso = y
        case .bassoDestra: destra = x; basso = y
        }

        // Non si scende sotto un ventesimo del lato: sotto quella misura il
        // riquadro diventa impossibile da prendere.
        guard destra - sinistra > 0.05, basso - alto > 0.05 else { return }

        var nuovo = Ritaglio(x: sinistra, y: alto,
                             larghezza: destra - sinistra, altezza: basso - alto)

        if let rapporto = proporzioni.rapporto {
            // Il rapporto è sulla foto vera, non sui numeri normalizzati.
            let rapportoSchermo = rapporto * (misura.height / misura.width)
            nuovo.altezza = nuovo.larghezza / rapportoSchermo
            if nuovo.y + nuovo.altezza > 1 { nuovo.altezza = 1 - nuovo.y }
        }
        ritaglio = nuovo
    }

    private var barraProporzioni: some View {
        HStack(spacing: 4) {
            ForEach(Proporzioni.allCases) { quale in
                Button {
                    proporzioni = quale
                    if quale == .originale {
                        prima()
                        ritaglio = Ritaglio()
                    }
                } label: {
                    Text(quale.rawValue)
                        .font(Caratteri.interfaccia(11.5))
                        .padding(.horizontal, 9)
                        .padding(.vertical, 4)
                        .foregroundStyle(proporzioni == quale ? Colori.suAccento : Colori.testo)
                        .background(proporzioni == quale ? Colori.accento : Colori.pannello,
                                    in: RoundedRectangle(cornerRadius: 6))
                }
                .buttonStyle(.plain)
            }
        }
        .padding(6)
        .background(.black.opacity(0.55), in: RoundedRectangle(cornerRadius: 10))
        .padding(.bottom, 12)
    }

    /// Un trascinamento lungo deve valere un solo passo indietro, non cento.
    private func segnaUnaVoltaSola() {
        guard !giaSegnato else { return }
        giaSegnato = true
        prima()
    }
}
