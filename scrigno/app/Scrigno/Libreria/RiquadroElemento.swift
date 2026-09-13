import AppKit
import SwiftUI

/// Una casella della griglia. Le foto e i video mostrano l'immagine; documenti,
/// audio e archivi mostrano la scheda colorata col tipo, come nell'anteprima.
struct RiquadroElemento: View {
    let elemento: Elemento
    let selezionato: Bool

    @State private var miniatura: NSImage?

    var body: some View {
        ZStack {
            switch elemento.tipo {
            case .foto, .video:
                immagine
            case .documento, .audio, .altro:
                scheda
            }
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Colori.pannello)
        .clipShape(RoundedRectangle(cornerRadius: 6))
        .overlay(alignment: .bottomLeading) { targhetta }
        .overlay(alignment: .bottomTrailing) { pallineEtichette }
        .overlay(alignment: .topTrailing) { spuntaSelezione }
        .overlay {
            RoundedRectangle(cornerRadius: 6)
                .strokeBorder(Colori.accento, lineWidth: selezionato ? 2 : 0)
        }
        .help(elemento.nomeFile)
        .task(id: elemento.improntaFile) { await caricaMiniatura() }
    }

    // MARK: - Le due facce

    @ViewBuilder
    private var immagine: some View {
        if let miniatura {
            Image(nsImage: miniatura)
                .resizable()
                .aspectRatio(contentMode: .fill)
        } else {
            Image(systemName: elemento.tipo.simbolo)
                .font(.system(size: 20))
                .foregroundStyle(Colori.testoTenue)
        }
    }

    private var scheda: some View {
        VStack(alignment: .leading, spacing: 0) {
            HStack {
                let sigla = TipoFile.sigla(per: elemento.url)
                let colori = Colori.perSigla(sigla)
                Text(sigla)
                    .font(Caratteri.dati(10, peso: .medium))
                    .foregroundStyle(colori.testo)
                    .padding(.horizontal, 6)
                    .padding(.vertical, 2)
                    .background(colori.sfondo, in: RoundedRectangle(cornerRadius: 4))
                Spacer()
            }

            if elemento.tipo == .audio {
                OndaSonora(barre: 26)
                    .frame(height: 38)
                    .padding(.vertical, 6)
            } else {
                RigheFinte()
                    .padding(.vertical, 8)
            }

            Spacer(minLength: 0)

            Text(elemento.nomeFile)
                .font(Caratteri.interfaccia(12, peso: .medium))
                .foregroundStyle(Colori.testo)
                .lineLimit(2)
            Text(sottotitoloScheda)
                .font(Caratteri.interfaccia(11))
                .foregroundStyle(Colori.testoSecondario)
                .lineLimit(1)
        }
        .padding(10)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(
            LinearGradient(colors: [Color(esadecimale: "#2a2c35"), Color(esadecimale: "#22232a")],
                           startPoint: .topLeading, endPoint: .bottomTrailing)
        )
    }

    private var sottotitoloScheda: String {
        var pezzi: [String] = []
        if elemento.durata > 0 { pezzi.append(Durata.testo(elemento.durata)) }
        pezzi.append(Libreria.pesoLeggibile(elemento.peso))
        if let etichetta = elemento.etichette.first { pezzi.append(etichetta.nome) }
        return pezzi.joined(separator: " · ")
    }

    // MARK: - Sovrapposizioni

    @ViewBuilder
    private var targhetta: some View {
        if elemento.tipo == .video, elemento.durata > 0 {
            HStack(spacing: 4) {
                Image(systemName: "play.fill").font(.system(size: 7))
                Text(Durata.testo(elemento.durata)).font(Caratteri.dati(10.5))
            }
            .foregroundStyle(.white)
            .padding(.horizontal, 6)
            .padding(.vertical, 2)
            .background(.black.opacity(0.62), in: RoundedRectangle(cornerRadius: 4))
            .padding(7)
        }
    }

    @ViewBuilder
    private var pallineEtichette: some View {
        if !elemento.etichette.isEmpty {
            HStack(spacing: 3) {
                ForEach(elemento.etichette.prefix(3)) { etichetta in
                    Circle()
                        .fill(etichetta.colore)
                        .frame(width: 7, height: 7)
                        .overlay { Circle().strokeBorder(.black.opacity(0.4), lineWidth: 1.5) }
                }
            }
            .padding(7)
        }
    }

    @ViewBuilder
    private var spuntaSelezione: some View {
        if selezionato {
            Image(systemName: "checkmark")
                .font(.system(size: 10, weight: .bold))
                .foregroundStyle(Colori.suAccento)
                .frame(width: 18, height: 18)
                .background(Colori.accento, in: Circle())
                .padding(7)
        } else if elemento.preferito {
            Image(systemName: "heart.fill")
                .font(.system(size: 10))
                .foregroundStyle(.white)
                .shadow(radius: 2)
                .padding(8)
        }
    }

    private func caricaMiniatura() async {
        guard elemento.tipo == .foto || elemento.tipo == .video else { return }
        let impronta = elemento.improntaFile
        let file = elemento.url
        let tipo = elemento.tipo
        miniatura = await Miniature.condivisa.miniatura(impronta: impronta, file: file, tipo: tipo)
    }
}

/// Le righe finte che nell'anteprima suggeriscono il testo di un documento.
struct RigheFinte: View {
    var body: some View {
        GeometryReader { spazio in
            VStack(alignment: .leading, spacing: 4) {
                ForEach(0..<max(1, Int(spazio.size.height / 9)), id: \.self) { riga in
                    Capsule()
                        .fill(Color.white.opacity(0.07))
                        .frame(width: spazio.size.width * (riga % 3 == 2 ? 0.55 : 0.92), height: 3)
                }
            }
        }
    }
}

/// L'onda sonora delle note vocali.
struct OndaSonora: View {
    var barre: Int = 26
    var colore: Color = Colori.audio

    var body: some View {
        HStack(alignment: .center, spacing: 2) {
            ForEach(0..<barre, id: \.self) { indice in
                let altezza = 0.18 + abs(sin(Double(indice) * 1.3) * cos(Double(indice) * 0.4)) * 0.82
                Capsule()
                    .fill(colore.opacity(0.85))
                    .frame(maxWidth: .infinity)
                    .frame(height: nil)
                    .scaleEffect(y: altezza, anchor: .center)
            }
        }
    }
}

enum Durata {
    /// `3:18`, oppure `1:02:45` quando passa l'ora.
    static func testo(_ secondi: Double) -> String {
        guard secondi.isFinite, secondi >= 0 else { return "—" }
        let interi = Int(secondi.rounded())
        let ore = interi / 3600
        let minuti = (interi % 3600) / 60
        let resto = interi % 60
        return ore > 0
            ? String(format: "%d:%02d:%02d", ore, minuti, resto)
            : String(format: "%d:%02d", minuti, resto)
    }
}
