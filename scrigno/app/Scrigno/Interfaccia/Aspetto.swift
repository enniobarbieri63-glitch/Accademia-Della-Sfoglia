import AppKit
import SwiftUI

extension Color {
    /// Legge i colori come sono scritti nell'anteprima: `#e9ae4f`.
    init(esadecimale: String) {
        let pulito = esadecimale.trimmingCharacters(in: CharacterSet(charactersIn: "#"))
        var numero: UInt64 = 0
        Scanner(string: pulito).scanHexInt64(&numero)

        let rosso, verde, blu, opacita: Double
        switch pulito.count {
        case 8:
            rosso = Double((numero >> 24) & 0xff) / 255
            verde = Double((numero >> 16) & 0xff) / 255
            blu = Double((numero >> 8) & 0xff) / 255
            opacita = Double(numero & 0xff) / 255
        default:
            rosso = Double((numero >> 16) & 0xff) / 255
            verde = Double((numero >> 8) & 0xff) / 255
            blu = Double(numero & 0xff) / 255
            opacita = 1
        }
        self.init(.sRGB, red: rosso, green: verde, blue: blu, opacity: opacita)
    }
}

/// I colori dell'anteprima approvata. Un tema solo, scuro: le foto risaltano meglio.
enum Colori {
    static let finestra = Color(esadecimale: "#1b1c22")
    static let barra = Color(esadecimale: "#16171c")
    static let pannello = Color(esadecimale: "#202128")
    static let rialzato = Color(esadecimale: "#282a32")
    static let linea = Color(esadecimale: "#2c2e37")
    static let lineaChiara = Color(esadecimale: "#393b46")

    static let testo = Color(esadecimale: "#ece9e3")
    static let testoSecondario = Color(esadecimale: "#8e8c96")
    static let testoTenue = Color(esadecimale: "#5d5c66")

    static let accento = Color(esadecimale: "#e9ae4f")
    static let accentoVelato = Color(esadecimale: "#e9ae4f").opacity(0.16)
    static let suAccento = Color(esadecimale: "#1d1506")

    static let audio = Color(esadecimale: "#b77fd6")
    static let sfondoScuro = Color(esadecimale: "#141519")

    static func perTipo(_ tipo: TipoFile) -> Color {
        switch tipo {
        case .foto: accento
        case .video: Color(esadecimale: "#58b39c")
        case .documento: Color(esadecimale: "#6f8cf0")
        case .audio: audio
        case .altro: testoSecondario
        }
    }

    /// I colori delle siglette sulle miniature dei documenti (PDF, DOCX, KEY…).
    static func perSigla(_ sigla: String) -> (sfondo: Color, testo: Color) {
        switch sigla.uppercased() {
        case "PDF": (Color(esadecimale: "#5a2a2a"), Color(esadecimale: "#ffb3a6"))
        case "DOC", "DOCX", "PAGES", "RTF", "TXT": (Color(esadecimale: "#23364f"), Color(esadecimale: "#9ec3ff"))
        case "M4A", "MP3", "WAV", "AIFF": (Color(esadecimale: "#3b2a4a"), Color(esadecimale: "#dcb6f2"))
        case "ZIP", "RAR", "7Z", "TAR", "GZ": (Color(esadecimale: "#2c3a33"), Color(esadecimale: "#9fd6bf"))
        case "KEY", "PPT", "PPTX": (Color(esadecimale: "#4a3d1f"), Color(esadecimale: "#f4d38c"))
        case "XLS", "XLSX", "NUMBERS", "CSV": (Color(esadecimale: "#2d4a2a"), Color(esadecimale: "#b6e3a5"))
        default: (Color(esadecimale: "#2e303a"), Color(esadecimale: "#c7c5cf"))
        }
    }
}

/// I caratteri dell'anteprima.
enum Caratteri {

    /// Il nome "Scrigno" e i numeri grandi. Nell'anteprima è Fraunces corsivo.
    /// Se il file del carattere non è dentro l'app si usa il serif di sistema:
    /// somiglia, e almeno non cambia di nascosto.
    static func nomeGrande(_ dimensione: CGFloat) -> Font {
        if NSFont(name: "Fraunces", size: dimensione) != nil {
            return .custom("Fraunces", size: dimensione).italic()
        }
        return .system(size: dimensione, weight: .semibold, design: .serif).italic()
    }

    static var fraunceseDisponibile: Bool { NSFont(name: "Fraunces", size: 12) != nil }

    /// I dati tecnici: f/1.8, 1/250, ISO, durate, pesi. Cifre incolonnate.
    static func dati(_ dimensione: CGFloat, peso: Font.Weight = .regular) -> Font {
        .system(size: dimensione, weight: peso, design: .monospaced)
    }

    static func interfaccia(_ dimensione: CGFloat, peso: Font.Weight = .regular) -> Font {
        .system(size: dimensione, weight: peso)
    }

    /// Le intestazioni piccole in maiuscoletto delle colonne laterali.
    static let sezione = Font.system(size: 11, weight: .semibold)
}

extension View {
    /// L'intestazione di gruppo grigia e spaziata, come nell'anteprima.
    func stileSezione() -> some View {
        self.font(Caratteri.sezione)
            .tracking(0.8)
            .textCase(.uppercase)
            .foregroundStyle(Colori.testoTenue)
    }

    /// Il bordo ambra dell'elemento selezionato.
    func bordoSelezione(_ attivo: Bool, raggio: CGFloat = 6) -> some View {
        self.overlay {
            RoundedRectangle(cornerRadius: raggio)
                .strokeBorder(Colori.accento, lineWidth: attivo ? 2 : 0)
        }
    }
}

/// Il pulsante ambra pieno: "Importa", "Fatto", "Esporta".
struct PulsanteAccento: ButtonStyle {
    func makeBody(configuration: Configuration) -> some View {
        configuration.label
            .font(Caratteri.interfaccia(13, peso: .semibold))
            .foregroundStyle(Colori.suAccento)
            .padding(.horizontal, 14)
            .padding(.vertical, 7)
            .background(Colori.accento.opacity(configuration.isPressed ? 0.8 : 1),
                        in: RoundedRectangle(cornerRadius: 8))
    }
}

/// Il pulsante col solo contorno: "Annulla", "Tienile tutte".
struct PulsanteContorno: ButtonStyle {
    func makeBody(configuration: Configuration) -> some View {
        configuration.label
            .font(Caratteri.interfaccia(12.5))
            .foregroundStyle(Colori.testo.opacity(0.85))
            .padding(.horizontal, 12)
            .padding(.vertical, 7)
            .background(configuration.isPressed ? Colori.rialzato : .clear,
                        in: RoundedRectangle(cornerRadius: 8))
            .overlay {
                RoundedRectangle(cornerRadius: 8).strokeBorder(Colori.lineaChiara)
            }
    }
}
