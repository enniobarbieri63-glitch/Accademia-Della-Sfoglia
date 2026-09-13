import AppKit
import SwiftData
import SwiftUI

@main
struct ScrignoApp: App {

    @State private var contenitore: ModelContainer
    @State private var navigazione = Navigazione()
    /// Se il catalogo non si apre, l'app parte lo stesso e lo dice: chiudersi da
    /// sola lascerebbe l'utente senza sapere cosa è successo.
    @State private var guastoAllAvvio: String?

    init() {
        var problema: String?
        let aperto: ModelContainer
        do {
            aperto = try Catalogo.apri()
        } catch {
            aperto = Catalogo.inMemoria()
            problema = """
                Non riesco ad aprire il catalogo in \(Libreria.condivisa.radice.path).
                Quello che vedi adesso non viene salvato. Motivo: \(error.localizedDescription)
                """
        }
        _contenitore = State(initialValue: aperto)
        _guastoAllAvvio = State(initialValue: problema)
    }

    var body: some Scene {
        WindowGroup {
            FinestraPrincipale(guasto: guastoAllAvvio)
                .environment(navigazione)
                .frame(minWidth: 1040, minHeight: 660)
                .preferredColorScheme(.dark)
        }
        .modelContainer(contenitore)
        .windowStyle(.hiddenTitleBar)
        .defaultSize(width: 1320, height: 840)
        .commands { menu }
    }

    @CommandsBuilder
    private var menu: some Commands {
        CommandGroup(replacing: .newItem) {
            Button("Importa…") { navigazione.mostraImporta = true }
                .keyboardShortcut("i", modifiers: .command)
        }

        CommandMenu("Vai") {
            ForEach(Navigazione.Schermata.allCases) { schermata in
                Button(schermata.titolo) { navigazione.vai(schermata) }
                    .keyboardShortcut(KeyEquivalent(schermata.tastoRapido), modifiers: .command)
            }
            Divider()
            Button("Cerca") { navigazione.chiediFuocoRicerca = true }
                .keyboardShortcut("f", modifiers: .command)
        }

        CommandGroup(replacing: .help) {
            Button("Dove sono i miei file") {
                NSWorkspace.shared.open(Libreria.condivisa.radice)
            }
        }
    }
}
