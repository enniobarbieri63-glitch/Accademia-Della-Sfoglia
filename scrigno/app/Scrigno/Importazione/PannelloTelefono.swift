import AppKit
import SwiftUI

/// Il riquadro che spiega come mandare foto e file da un telefono Android
/// (o da qualsiasi altro) senza cavi e senza installare niente.
struct PannelloTelefono: View {
    let telefono: ArrivoDaTelefono

    var body: some View {
        HStack(alignment: .top, spacing: 16) {
            codice
            spiegazione
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(Colori.barra, in: RoundedRectangle(cornerRadius: 12))
        .overlay { RoundedRectangle(cornerRadius: 12).strokeBorder(Colori.linea) }
    }

    @ViewBuilder
    private var codice: some View {
        switch telefono.stato {
        case .pronto:
            if let immagine = telefono.codiceQR {
                Image(nsImage: immagine)
                    .resizable()
                    .interpolation(.none)
                    .frame(width: 132, height: 132)
                    .padding(8)
                    .background(.white, in: RoundedRectangle(cornerRadius: 10))
            }
        case .accendo:
            ProgressView()
                .controlSize(.small)
                .frame(width: 148, height: 148)
        case .spento, .guasto:
            Image(systemName: "wifi.slash")
                .font(.system(size: 30))
                .foregroundStyle(Colori.testoTenue)
                .frame(width: 148, height: 148)
                .background(Colori.pannello, in: RoundedRectangle(cornerRadius: 10))
        }
    }

    @ViewBuilder
    private var spiegazione: some View {
        VStack(alignment: .leading, spacing: 8) {
            switch telefono.stato {
            case .pronto:
                Text("Inquadra il codice col telefono")
                    .font(Caratteri.interfaccia(14, peso: .semibold))
                    .foregroundStyle(Colori.testo)

                Text("Si apre una pagina: scegli foto, video o file e premi Manda. Arrivano qui sotto, pronti da importare.")
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testoSecondario)
                    .fixedSize(horizontal: false, vertical: true)

                if let indirizzo = telefono.indirizzo {
                    HStack(spacing: 8) {
                        Text(indirizzo)
                            .font(Caratteri.dati(11.5))
                            .foregroundStyle(Colori.testo)
                            .textSelection(.enabled)
                            .lineLimit(1)
                        Button {
                            NSPasteboard.general.clearContents()
                            NSPasteboard.general.setString(indirizzo, forType: .string)
                        } label: {
                            Image(systemName: "doc.on.doc").font(.system(size: 11))
                        }
                        .buttonStyle(.plain)
                        .foregroundStyle(Colori.testoSecondario)
                        .help("Copia l'indirizzo")
                    }
                    .padding(.horizontal, 10)
                    .padding(.vertical, 6)
                    .background(Colori.pannello, in: RoundedRectangle(cornerRadius: 7))
                }

                Text("Il telefono deve essere sulla stessa rete Wi-Fi del Mac. La pagina resta aperta solo finché questa finestra è aperta.")
                    .font(Caratteri.interfaccia(11.5))
                    .foregroundStyle(Colori.testoTenue)
                    .fixedSize(horizontal: false, vertical: true)

                if !telefono.arrivati.isEmpty {
                    HStack(spacing: 6) {
                        Image(systemName: "checkmark.circle.fill")
                            .foregroundStyle(Color(esadecimale: "#58b39c"))
                        Text("Arrivati \(telefono.arrivati.count) file\(telefono.ultimoNome.map { " · ultimo: \($0)" } ?? "")")
                            .lineLimit(1)
                    }
                    .font(Caratteri.interfaccia(12))
                    .foregroundStyle(Colori.testo)
                }

            case .accendo:
                Text("Sto aprendo la pagina sulla rete di casa…")
                    .font(Caratteri.interfaccia(13))
                    .foregroundStyle(Colori.testoSecondario)

            case .guasto(let motivo):
                Text("Non riesco ad aprire la pagina")
                    .font(Caratteri.interfaccia(14, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Text(motivo)
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testoSecondario)
                    .fixedSize(horizontal: false, vertical: true)
                Button("Riprova") { telefono.accendi() }
                    .buttonStyle(PulsanteContorno())

            case .spento:
                Text("Manda dal telefono")
                    .font(Caratteri.interfaccia(14, peso: .semibold))
                    .foregroundStyle(Colori.testo)
                Text("Funziona con Android, iPhone e tablet: serve solo il browser del telefono, niente da installare.")
                    .font(Caratteri.interfaccia(12.5))
                    .foregroundStyle(Colori.testoSecondario)
                    .fixedSize(horizontal: false, vertical: true)
                Button("Accendi") { telefono.accendi() }
                    .buttonStyle(PulsanteAccento())
            }

            Spacer(minLength: 0)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }
}
