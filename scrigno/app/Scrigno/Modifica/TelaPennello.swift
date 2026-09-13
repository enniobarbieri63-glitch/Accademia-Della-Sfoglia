import AppKit
import SwiftUI

/// Il pennello con cui si dipinge a mano la zona da sfocare.
///
/// Quello che si dipinge è una maschera in bianco e nero: bianco vuol dire
/// "sfoca qui", nero "lascia nitido". La maschera vive in un file suo, l'originale
/// non viene toccato.
struct TelaPennello: View {
    @Binding var modifiche: Modifiche
    /// Le misure della foto vera, per dipingere nelle proporzioni giuste.
    let misura: CGSize
    let prima: () -> Void
    let cambiato: () -> Void

    @State private var tela: NSImage?
    @State private var grandezza: Double = 90
    @State private var morbidezza: Double = 55
    @State private var cancella = false
    @State private var giaSegnato = false
    @State private var ultimoPunto: CGPoint?

    var body: some View {
        GeometryReader { spazio in
            ZStack {
                if let tela {
                    Image(nsImage: tela)
                        .resizable()
                        .colorMultiply(Color(esadecimale: "#e9ae4f"))
                        .blendMode(.screen)
                        .opacity(0.5)
                        .allowsHitTesting(false)
                }

                Color.clear
                    .contentShape(Rectangle())
                    .gesture(
                        DragGesture(minimumDistance: 0)
                            .onChanged { movimento in
                                segnaUnaVoltaSola()
                                dipingi(da: ultimoPunto ?? movimento.location,
                                        a: movimento.location,
                                        in: spazio.size)
                                ultimoPunto = movimento.location
                            }
                            .onEnded { _ in
                                ultimoPunto = nil
                                giaSegnato = false
                                salva()
                            }
                    )
            }
            .overlay(alignment: .bottom) { comandi }
        }
        .task { preparaTela() }
    }

    private var comandi: some View {
        HStack(spacing: 12) {
            comandoCursore("Pennello", $grandezza, da: 20, a: 300)
            comandoCursore("Morbidezza", $morbidezza, da: 0, a: 100)

            Divider().frame(height: 22).overlay(Colori.lineaChiara)

            Button {
                cancella.toggle()
            } label: {
                Label(cancella ? "Cancella" : "Dipingi",
                      systemImage: cancella ? "eraser" : "paintbrush.fill")
                    .font(Caratteri.interfaccia(11.5))
                    .foregroundStyle(cancella ? Colori.accento : Colori.testo)
            }
            .buttonStyle(.plain)

            Button("Inverti") { inverti() }
                .font(Caratteri.interfaccia(11.5))
                .buttonStyle(.plain)
                .foregroundStyle(Colori.testo)

            Button("Pulisci") { pulisci() }
                .font(Caratteri.interfaccia(11.5))
                .buttonStyle(.plain)
                .foregroundStyle(Colori.testo)
        }
        .padding(.horizontal, 14)
        .padding(.vertical, 9)
        .background(.black.opacity(0.62), in: RoundedRectangle(cornerRadius: 12))
        .padding(.bottom, 12)
    }

    private func comandoCursore(_ nome: String, _ valore: Binding<Double>,
                                da minimo: Double, a massimo: Double) -> some View {
        VStack(alignment: .leading, spacing: 1) {
            Text(nome)
                .font(Caratteri.interfaccia(10))
                .foregroundStyle(Colori.testoSecondario)
            Slider(value: valore, in: minimo...massimo)
                .controlSize(.mini)
                .tint(Colori.accento)
                .frame(width: 92)
        }
    }

    // MARK: - Dipingere

    private func preparaTela() {
        let (larghezza, altezza) = MaschereDipinte.misuraDiLavoro(
            perLarghezza: Int(misura.width), altezza: Int(misura.height))

        if let nome = modifiche.maschera, let esistente = MaschereDipinte.caricaNSImage(nome) {
            tela = esistente
        } else {
            tela = MaschereDipinte.vuota(larghezza: larghezza, altezza: altezza)
        }
    }

    /// Disegna una scia fra due punti, altrimenti muovendo in fretta il mouse
    /// resterebbero dei buchi.
    private func dipingi(da inizio: CGPoint, a fine: CGPoint, in misuraVista: CGSize) {
        guard let tela else { return }

        let scalaX = tela.size.width / max(1, misuraVista.width)
        let scalaY = tela.size.height / max(1, misuraVista.height)
        let raggio = grandezza / 2 * min(scalaX, scalaY)

        let partenza = CGPoint(x: inizio.x * scalaX, y: tela.size.height - inizio.y * scalaY)
        let arrivo = CGPoint(x: fine.x * scalaX, y: tela.size.height - fine.y * scalaY)

        let distanza = hypot(arrivo.x - partenza.x, arrivo.y - partenza.y)
        let passi = max(1, Int(distanza / max(1, raggio / 3)))

        tela.lockFocus()
        NSGraphicsContext.current?.compositingOperation = .sourceOver

        let dentro = cancella ? NSColor.black : NSColor.white
        let fuori = cancella ? NSColor.black.withAlphaComponent(0) : NSColor.white.withAlphaComponent(0)
        let durezza = 1 - morbidezza / 100

        for passo in 0...passi {
            let frazione = Double(passo) / Double(passi)
            let x = partenza.x + (arrivo.x - partenza.x) * frazione
            let y = partenza.y + (arrivo.y - partenza.y) * frazione
            let cerchio = NSRect(x: x - raggio, y: y - raggio, width: raggio * 2, height: raggio * 2)

            if let sfumatura = NSGradient(colors: [dentro, fuori],
                                          atLocations: [durezza * 0.9, 1],
                                          colorSpace: .deviceRGB) {
                sfumatura.draw(in: NSBezierPath(ovalIn: cerchio), relativeCenterPosition: .zero)
            }
        }

        // Cancellare vuol dire riportare al nero, e il nero copre.
        if cancella {
            NSColor.black.setFill()
            for passo in 0...passi {
                let frazione = Double(passo) / Double(passi)
                let x = partenza.x + (arrivo.x - partenza.x) * frazione
                let y = partenza.y + (arrivo.y - partenza.y) * frazione
                NSBezierPath(ovalIn: NSRect(x: x - raggio, y: y - raggio,
                                            width: raggio * 2, height: raggio * 2)).fill()
            }
        }

        tela.unlockFocus()
        self.tela = tela
    }

    private func inverti() {
        guard let tela else { return }
        prima()
        let rovesciata = NSImage(size: tela.size)
        rovesciata.lockFocus()
        NSColor.white.setFill()
        NSRect(origin: .zero, size: tela.size).fill()
        tela.draw(in: NSRect(origin: .zero, size: tela.size),
                  from: .zero, operation: .destinationOut, fraction: 1)
        rovesciata.unlockFocus()
        self.tela = rovesciata
        salva()
    }

    private func pulisci() {
        prima()
        tela = MaschereDipinte.vuota(larghezza: Int(tela?.size.width ?? 1024),
                                     altezza: Int(tela?.size.height ?? 1024))
        salva()
    }

    private func salva() {
        guard let tela else { return }
        let nome = modifiche.maschera ?? MaschereDipinte.nuovoNome()
        guard MaschereDipinte.salva(tela, comeNome: nome) else { return }
        modifiche.maschera = nome
        // Dipingere a mano ha senso solo col modo pennello: si passa da soli.
        modifiche.sfocaturaModo = ModoSfocatura.pennello.rawValue
        if modifiche.sfocatura == 0 { modifiche.sfocatura = 45 }
        cambiato()
    }

    private func segnaUnaVoltaSola() {
        guard !giaSegnato else { return }
        giaSegnato = true
        prima()
    }
}
