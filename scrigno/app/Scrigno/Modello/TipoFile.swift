import Foundation
import UniformTypeIdentifiers

/// I cassetti in cui Scrigno smista qualsiasi cosa entri in libreria.
enum TipoFile: String, Codable, CaseIterable, Identifiable, Sendable {
    case foto
    case video
    case documento
    case audio
    case altro

    var id: String { rawValue }

    var nomePlurale: String {
        switch self {
        case .foto: "Foto"
        case .video: "Video"
        case .documento: "Documenti"
        case .audio: "Audio"
        case .altro: "Altri file"
        }
    }

    var nomeSingolare: String {
        switch self {
        case .foto: "foto"
        case .video: "video"
        case .documento: "documento"
        case .audio: "audio"
        case .altro: "file"
        }
    }

    var simbolo: String {
        switch self {
        case .foto: "photo"
        case .video: "video"
        case .documento: "doc.text"
        case .audio: "waveform"
        case .altro: "shippingbox"
        }
    }

    /// Riconosce il tipo dal contenuto del file, non dal nome: un `.jpg` rinominato
    /// `.txt` resta una foto.
    static func riconosci(_ url: URL) -> TipoFile {
        let dalDisco = try? url.resourceValues(forKeys: [.contentTypeKey]).contentType
        let dallEstensione = UTType(filenameExtension: url.pathExtension.lowercased())
        guard let tipo = dalDisco ?? dallEstensione else { return .altro }
        return riconosci(tipo)
    }

    static func riconosci(_ tipo: UTType) -> TipoFile {
        if tipo.conforms(to: .image) { return .foto }
        if tipo.conforms(to: .movie) || tipo.conforms(to: .video) { return .video }
        if tipo.conforms(to: .audio) { return .audio }
        for documento in tipiDocumento where tipo.conforms(to: documento) { return .documento }
        return .altro
    }

    private static let tipiDocumento: [UTType] = [
        .pdf, .rtf, .plainText, .text, .spreadsheet, .presentation, .archive,
        UTType("com.microsoft.word.doc") ?? .data,
        UTType("org.openxmlformats.wordprocessingml.document") ?? .data,
        UTType("com.apple.iwork.pages.pages") ?? .data,
        UTType("com.apple.iwork.keynote.key") ?? .data,
        UTType("com.apple.iwork.numbers.numbers") ?? .data
    ]

    /// Sigla mostrata sulla miniatura di un documento (PDF, DOCX, KEY…).
    static func sigla(per url: URL) -> String {
        let estensione = url.pathExtension.uppercased()
        if estensione.isEmpty { return "FILE" }
        return String(estensione.prefix(5))
    }
}
