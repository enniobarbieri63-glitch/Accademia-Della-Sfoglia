import SwiftUI
import UniformTypeIdentifiers

/// Quello che si vede la prima volta, a libreria vuota. Senza questa schermata
/// la prima impressione dell'app sarebbe una finestra grigia.
struct PrimoAvvio: View {
    let importatore: Importatore

    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto
    @State private var sottoIlTrascinamento = false

    var body: some View {
        VStack(spacing: 0) {
            Spacer()

            IconaScrigno(lato: 84)
                .padding(.bottom, 22)

            Text("Scrigno")
                .font(Caratteri.nomeGrande(46))
                .foregroundStyle(Colori.testo)

            Text("Un unico posto sul Mac per foto, video e ogni altro file che vuoi custodire.\nOgni cosa trova il suo cassetto da sola.")
                .font(Caratteri.interfaccia(14))
                .foregroundStyle(Colori.testoSecondario)
                .multilineTextAlignment(.center)
                .padding(.top, 10)

            zonaTrascinamento
                .padding(.top, 30)

            HStack(spacing: 10) {
                Button("Importa una cartella…") { scegliCartella() }
                    .buttonStyle(PulsanteAccento())
                Button("Manda dal telefono") {
                    navigazione.mostraImporta = true
                }
                .buttonStyle(PulsanteContorno())
            }
            .padding(.top, 20)

            Text("I file vengono copiati in \(Libreria.condivisa.radice.path). Gli originali restano dove sono.")
                .font(Caratteri.interfaccia(12))
                .foregroundStyle(Colori.testoTenue)
                .padding(.top, 22)

            Spacer()
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .background(Colori.finestra)
        .onDrop(of: [.fileURL], isTargeted: $sottoIlTrascinamento) { portatori in
            raccogli(portatori)
            return true
        }
    }

    private var zonaTrascinamento: some View {
        VStack(spacing: 8) {
            Image(systemName: "arrow.down.circle")
                .font(.system(size: 26))
                .foregroundStyle(Colori.accento)
            Text("Trascina qui foto, video, cartelle, qualsiasi file")
                .font(Caratteri.interfaccia(13))
                .foregroundStyle(Colori.testoSecondario)
        }
        .frame(width: 420, height: 140)
        .background(sottoIlTrascinamento ? Colori.accentoVelato : Colori.barra,
                    in: RoundedRectangle(cornerRadius: 14))
        .overlay {
            RoundedRectangle(cornerRadius: 14)
                .strokeBorder(sottoIlTrascinamento ? Colori.accento : Colori.lineaChiara,
                              style: StrokeStyle(lineWidth: 1.5, dash: [6, 5]))
        }
    }

    private func scegliCartella() {
        let pannello = NSOpenPanel()
        pannello.canChooseFiles = true
        pannello.canChooseDirectories = true
        pannello.allowsMultipleSelection = true
        pannello.prompt = "Importa"
        pannello.message = "Scegli le cartelle o i file da mettere in Scrigno"

        guard pannello.runModal() == .OK, !pannello.urls.isEmpty else { return }
        avvia(pannello.urls, nome: pannello.urls.count == 1
              ? pannello.urls[0].lastPathComponent : "\(pannello.urls.count) elementi")
    }

    private func raccogli(_ portatori: [NSItemProvider]) {
        Task {
            var urls: [URL] = []
            for portatore in portatori {
                if let url = try? await portatore.loadItem(forTypeIdentifier: UTType.fileURL.identifier) as? Data,
                   let vero = URL(dataRepresentation: url, relativeTo: nil) {
                    urls.append(vero)
                }
            }
            guard !urls.isEmpty else { return }
            avvia(urls, nome: "Trascinati")
        }
    }

    private func avvia(_ urls: [URL], nome: String) {
        navigazione.mostraImporta = true
        Task {
            await importatore.guarda(urls, nomeSorgente: nome, contesto: contesto)
        }
    }
}

/// L'icona dell'app: un cassetto scuro su fondo ambra sfumato.
struct IconaScrigno: View {
    var lato: CGFloat = 58

    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: lato * 0.24, style: .continuous)
                .fill(LinearGradient(
                    colors: [Color(esadecimale: "#f3c46e"),
                             Color(esadecimale: "#c9812c"),
                             Color(esadecimale: "#8a4f1c")],
                    startPoint: .topLeading, endPoint: .bottomTrailing))

            RoundedRectangle(cornerRadius: lato * 0.09, style: .continuous)
                .fill(Colori.finestra)
                .frame(width: lato * 0.58, height: lato * 0.44)
                .offset(y: lato * 0.04)

            RoundedRectangle(cornerRadius: lato * 0.035)
                .fill(Colori.accento)
                .frame(width: lato * 0.17, height: lato * 0.1)
                .offset(y: lato * 0.04)
        }
        .frame(width: lato, height: lato)
        .shadow(color: Colori.accento.opacity(0.25), radius: lato * 0.2, y: lato * 0.12)
    }
}
