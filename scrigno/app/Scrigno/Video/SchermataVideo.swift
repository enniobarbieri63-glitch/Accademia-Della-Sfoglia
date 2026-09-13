import AVKit
import AppKit
import SwiftData
import SwiftUI

struct SchermataVideo: View {
    @Environment(Navigazione.self) private var navigazione
    @Environment(\.modelContext) private var contesto
    @Query(sort: \Elemento.dataScatto, order: .reverse) private var tutti: [Elemento]

    @State private var lettore = AVPlayer()
    @State private var modifiche = Modifiche()
    @State private var durata: Double = 0
    @State private var posizione: Double = 0
    @State private var osservatore: Any?
    @State private var esportatore = Esportatore()
    @State private var fotogrammi: [NSImage] = []
    @State private var ricerca = ""

    private var video: [Elemento] {
        let elenco = tutti.filter { $0.tipo == .video && !$0.nelCestino }
        let cercato = ricerca.trimmingCharacters(in: .whitespaces).lowercased()
        guard !cercato.isEmpty else { return elenco }
        return elenco.filter { $0.testoCercabile.contains(cercato) }
    }

    private var aperto: Elemento? {
        if let scelto = navigazione.elementoAperto,
           let trovato = video.first(where: { $0.persistentModelID == scelto }) {
            return trovato
        }
        return video.first
    }

    var body: some View {
        VStack(spacing: 0) {
            BarraStrumenti(titolo: "Video",
                           sottotitolo: "\(video.count) filmati · \(Libreria.pesoLeggibile(pesoTotale))") {
                CampoRicerca(invito: "Cerca nei video…", testo: $ricerca, larghezza: 250)
            } destra: {
                EmptyView()
            }

            if let aperto {
                HStack(spacing: 0) {
                    lettoreEStrumenti(aperto)
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                    Rectangle().fill(Colori.linea).frame(width: 1)
                    elencoLaterale
                        .frame(width: 300)
                }
            } else {
                NienteQui(titolo: "Nessun video",
                          spiegazione: "Importa dei filmati e li trovi qui, con il lettore e gli strumenti di taglio.",
                          azione: ("Importa", { navigazione.mostraImporta = true }))
            }
        }
        .background(Colori.finestra)
        .task(id: aperto?.persistentModelID) { await preparaLettore() }
        .onDisappear { fermaOsservatore() }
    }

    private var pesoTotale: Int {
        video.reduce(0) { $0 + $1.peso }
    }

    // MARK: - Lettore

    private func lettoreEStrumenti(_ elemento: Elemento) -> some View {
        VStack(spacing: 14) {
            ZStack(alignment: .topLeading) {
                VideoPlayer(player: lettore)
                    .clipShape(RoundedRectangle(cornerRadius: 10))

                HStack(spacing: 8) {
                    if elemento.larghezza > 0 {
                        Text("\(elemento.larghezza)×\(elemento.altezza)")
                            .font(Caratteri.dati(10.5))
                            .padding(.horizontal, 6)
                            .padding(.vertical, 2)
                            .background(.black.opacity(0.45), in: RoundedRectangle(cornerRadius: 4))
                    }
                    Text(elemento.nomeFile)
                        .font(Caratteri.interfaccia(13))
                }
                .foregroundStyle(.white.opacity(0.85))
                .padding(14)
            }
            .frame(maxHeight: .infinity)

            strumenti(elemento)
            lineaDelTempo
        }
        .padding(.horizontal, 20)
        .padding(.vertical, 18)
    }

    private func strumenti(_ elemento: Elemento) -> some View {
        HStack(spacing: 8) {
            attrezzo("Taglia", "scissors", attivo: modifiche.inizio != nil || modifiche.fine != nil) {
                if modifiche.inizio == nil {
                    modifiche.inizio = 0
                    modifiche.fine = durata
                } else {
                    modifiche.inizio = nil
                    modifiche.fine = nil
                }
            }

            Menu {
                ForEach([0.25, 0.5, 1.0, 1.5, 2.0], id: \.self) { velocita in
                    Button(velocita == 1 ? "Normale" : "\(velocita.formatted())×") {
                        modifiche.velocita = velocita
                        lettore.rate = Float(velocita)
                    }
                }
            } label: {
                Label(modifiche.velocita == 1 ? "Velocità" : "\(modifiche.velocita.formatted())×",
                      systemImage: "speedometer")
                    .font(Caratteri.interfaccia(12.5))
            }
            .menuStyle(.borderlessButton)
            .fixedSize()
            .padding(.horizontal, 12)
            .padding(.vertical, 7)
            .background(Colori.pannello, in: RoundedRectangle(cornerRadius: 8))
            .overlay { RoundedRectangle(cornerRadius: 8).strokeBorder(Colori.linea) }

            attrezzo("Salva fotogramma", "camera.viewfinder") { salvaFotogramma(elemento) }

            Spacer()

            if esportatore.inCorso {
                HStack(spacing: 8) {
                    ProgressView(value: esportatore.avanzamento)
                        .progressViewStyle(.linear)
                        .tint(Colori.accento)
                        .frame(width: 120)
                    Button("Ferma") { esportatore.ferma() }
                        .buttonStyle(PulsanteContorno())
                }
            } else {
                Button("Salva le modifiche") { salvaModifiche(elemento) }
                    .buttonStyle(PulsanteContorno())
                Button("Esporta 1080p") { esporta(elemento) }
                    .buttonStyle(PulsanteAccento())
            }
        }
    }

    private func attrezzo(_ nome: String, _ simbolo: String, attivo: Bool = false,
                          fai: @escaping () -> Void) -> some View {
        Button(action: fai) {
            Label(nome, systemImage: simbolo)
                .font(Caratteri.interfaccia(12.5))
                .foregroundStyle(attivo ? Colori.accento : Colori.testo)
                .padding(.horizontal, 12)
                .padding(.vertical, 7)
                .background(attivo ? Colori.accentoVelato : Colori.pannello,
                            in: RoundedRectangle(cornerRadius: 8))
                .overlay {
                    RoundedRectangle(cornerRadius: 8)
                        .strokeBorder(attivo ? Colori.accento : Colori.linea)
                }
        }
        .buttonStyle(.plain)
    }

    private var lineaDelTempo: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(Durata.testo(posizione)).font(Caratteri.dati(11))
                Spacer()
                Text(Durata.testo(durata)).font(Caratteri.dati(11))
            }
            .foregroundStyle(Colori.testoTenue)

            GeometryReader { spazio in
                ZStack(alignment: .leading) {
                    HStack(spacing: 1) {
                        ForEach(Array(fotogrammi.enumerated()), id: \.offset) { _, immagine in
                            Image(nsImage: immagine)
                                .resizable()
                                .aspectRatio(contentMode: .fill)
                                .frame(maxWidth: .infinity)
                                .clipped()
                        }
                    }
                    .frame(height: 58)
                    .clipShape(RoundedRectangle(cornerRadius: 6))

                    if let inizio = modifiche.inizio, let fine = modifiche.fine, durata > 0 {
                        let sinistra = spazio.size.width * (inizio / durata)
                        let larghezza = spazio.size.width * ((fine - inizio) / durata)

                        Rectangle().fill(.black.opacity(0.68))
                            .frame(width: sinistra)
                        Rectangle().fill(.black.opacity(0.68))
                            .frame(width: max(0, spazio.size.width - sinistra - larghezza))
                            .offset(x: sinistra + larghezza)

                        RoundedRectangle(cornerRadius: 6)
                            .strokeBorder(Colori.accento, lineWidth: 3)
                            .frame(width: larghezza, height: 58)
                            .offset(x: sinistra)

                        maniglia(x: sinistra, larghezzaTotale: spazio.size.width) { nuova in
                            modifiche.inizio = min(nuova, (modifiche.fine ?? durata) - 0.2)
                        }
                        maniglia(x: sinistra + larghezza, larghezzaTotale: spazio.size.width) { nuova in
                            modifiche.fine = max(nuova, (modifiche.inizio ?? 0) + 0.2)
                        }
                    }

                    if durata > 0 {
                        Rectangle()
                            .fill(.white)
                            .frame(width: 2, height: 66)
                            .offset(x: spazio.size.width * (posizione / durata))
                    }
                }
                .contentShape(Rectangle())
                .gesture(
                    DragGesture(minimumDistance: 0)
                        .onChanged { movimento in
                            let frazione = min(1, max(0, movimento.location.x / spazio.size.width))
                            vaiA(frazione * durata)
                        }
                )
            }
            .frame(height: 66)
        }
        .padding(12)
        .background(Colori.barra, in: RoundedRectangle(cornerRadius: 10))
        .overlay { RoundedRectangle(cornerRadius: 10).strokeBorder(Colori.linea) }
    }

    private func maniglia(x: Double, larghezzaTotale: Double,
                          sposta: @escaping (Double) -> Void) -> some View {
        Rectangle()
            .fill(Colori.accento)
            .frame(width: 9, height: 58)
            .offset(x: x - 4.5)
            .gesture(
                DragGesture()
                    .onChanged { movimento in
                        let frazione = min(1, max(0, movimento.location.x / larghezzaTotale))
                        sposta(frazione * durata)
                    }
            )
    }

    // MARK: - Elenco a destra

    private var elencoLaterale: some View {
        ScrollView {
            LazyVStack(alignment: .leading, spacing: 2) {
                ForEach(Filtraggio.raggruppa(video, come: .giorni)) { gruppo in
                    Text(gruppo.titolo)
                        .stileSezione()
                        .padding(.horizontal, 7)
                        .padding(.top, 14)
                        .padding(.bottom, 6)

                    ForEach(gruppo.elementi) { elemento in
                        Button {
                            navigazione.elementoAperto = elemento.persistentModelID
                        } label: {
                            rigaVideo(elemento)
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
            .padding(.horizontal, 14)
            .padding(.bottom, 20)
        }
        .background(Colori.barra)
    }

    private func rigaVideo(_ elemento: Elemento) -> some View {
        let scelto = aperto?.persistentModelID == elemento.persistentModelID

        return HStack(spacing: 10) {
            RiquadroElemento(elemento: elemento, selezionato: false)
                .frame(width: 96, height: 56)

            VStack(alignment: .leading, spacing: 1) {
                Text(elemento.nomeFile)
                    .font(Caratteri.interfaccia(12.5, peso: .medium))
                    .foregroundStyle(Colori.testo)
                    .lineLimit(1)
                Text("\(Libreria.pesoLeggibile(elemento.peso)) · \(Durata.testo(elemento.durata))")
                    .font(Caratteri.dati(11))
                    .foregroundStyle(Colori.testoSecondario)
            }
            Spacer(minLength: 0)
        }
        .padding(7)
        .background(scelto ? Colori.accentoVelato : .clear, in: RoundedRectangle(cornerRadius: 8))
        .contentShape(Rectangle())
    }

    // MARK: - Lettore e azioni

    private func preparaLettore() async {
        fermaOsservatore()
        guard let aperto else { return }

        modifiche = aperto.modifiche
        let filmato = AVURLAsset(url: aperto.url)
        lettore.replaceCurrentItem(with: AVPlayerItem(asset: filmato))

        if let lunghezza = try? await filmato.load(.duration) {
            let secondi = CMTimeGetSeconds(lunghezza)
            durata = secondi.isFinite ? secondi : aperto.durata
        } else {
            durata = aperto.durata
        }

        osservatore = lettore.addPeriodicTimeObserver(
            forInterval: CMTime(seconds: 0.1, preferredTimescale: 600), queue: .main
        ) { istante in
            posizione = CMTimeGetSeconds(istante)
        }

        fotogrammi = await Esportatore.striscia(di: filmato, quanti: 10)
    }

    private func fermaOsservatore() {
        if let osservatore { lettore.removeTimeObserver(osservatore) }
        osservatore = nil
        lettore.pause()
    }

    private func vaiA(_ secondi: Double) {
        lettore.seek(to: CMTime(seconds: secondi, preferredTimescale: 600),
                     toleranceBefore: .zero, toleranceAfter: .zero)
    }

    private func salvaModifiche(_ elemento: Elemento) {
        elemento.modifiche = modifiche
        try? contesto.save()
    }

    private func salvaFotogramma(_ elemento: Elemento) {
        Task {
            guard let immagine = await Esportatore.fotogramma(di: AVURLAsset(url: elemento.url),
                                                              a: posizione) else { return }
            let pannello = NSSavePanel()
            pannello.nameFieldStringValue =
                "\((elemento.nomeFile as NSString).deletingPathExtension) \(Int(posizione))s.png"
            pannello.allowedContentTypes = [.png]
            guard pannello.runModal() == .OK, let destinazione = pannello.url else { return }

            if let tiff = immagine.tiffRepresentation,
               let rappresentazione = NSBitmapImageRep(data: tiff),
               let png = rappresentazione.representation(using: .png, properties: [:]) {
                try? png.write(to: destinazione)
            }
        }
    }

    private func esporta(_ elemento: Elemento) {
        let pannello = NSSavePanel()
        pannello.nameFieldStringValue =
            "\((elemento.nomeFile as NSString).deletingPathExtension) 1080p.mp4"
        pannello.allowedContentTypes = [.mpeg4Movie]
        guard pannello.runModal() == .OK, let destinazione = pannello.url else { return }

        let url = elemento.url
        let scelte = modifiche
        Task { await esportatore.esporta(url, con: scelte, in: destinazione) }
    }
}
