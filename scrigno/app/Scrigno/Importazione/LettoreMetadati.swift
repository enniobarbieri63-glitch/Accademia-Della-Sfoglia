import AVFoundation
import CoreLocation
import Foundation
import ImageIO

/// Legge dal file quello che serve a metterlo al posto giusto: quando è stato
/// fatto, dove, con che macchina, quanto è grande.
enum LettoreMetadati {

    struct Scheda {
        var data: Date?
        var larghezza: Int = 0
        var altezza: Int = 0
        var durata: Double = 0
        var latitudine: Double?
        var longitudine: Double?
        var fotocamera: String?
        var diaframma: Double?
        var tempoEsposizione: Double?
        var iso: Int?
        var focale: Double?
    }

    static func leggi(_ url: URL, tipo: TipoFile) async -> Scheda {
        var scheda: Scheda
        switch tipo {
        case .foto:
            scheda = leggiFoto(url)
        case .video, .audio:
            scheda = await leggiFilmato(url)
        case .documento, .altro:
            scheda = Scheda()
        }

        // Senza data di scatto si ripiega su quella del file: meglio una data
        // approssimata che buttare tutto nel giorno dell'importazione.
        if scheda.data == nil {
            scheda.data = dataDalDisco(url)
        }
        return scheda
    }

    // MARK: - Foto

    private static func leggiFoto(_ url: URL) -> Scheda {
        var scheda = Scheda()
        guard let sorgente = CGImageSourceCreateWithURL(url as CFURL, nil),
              let proprieta = CGImageSourceCopyPropertiesAtIndex(sorgente, 0, nil) as? [CFString: Any]
        else { return scheda }

        scheda.larghezza = proprieta[kCGImagePropertyPixelWidth] as? Int ?? 0
        scheda.altezza = proprieta[kCGImagePropertyPixelHeight] as? Int ?? 0

        // Una foto ruotata di 90° ha larghezza e altezza scambiate rispetto ai pixel.
        if let orientamento = proprieta[kCGImagePropertyOrientation] as? Int, orientamento >= 5 {
            swap(&scheda.larghezza, &scheda.altezza)
        }

        if let exif = proprieta[kCGImagePropertyExifDictionary] as? [CFString: Any] {
            scheda.data = dataExif(exif[kCGImagePropertyExifDateTimeOriginal] as? String)
                ?? dataExif(exif[kCGImagePropertyExifDateTimeDigitized] as? String)
            scheda.diaframma = exif[kCGImagePropertyExifFNumber] as? Double
            scheda.tempoEsposizione = exif[kCGImagePropertyExifExposureTime] as? Double
            scheda.focale = exif[kCGImagePropertyExifFocalLength] as? Double
            if let valori = exif[kCGImagePropertyExifISOSpeedRatings] as? [Int] {
                scheda.iso = valori.first
            }
        }

        if let tiff = proprieta[kCGImagePropertyTIFFDictionary] as? [CFString: Any] {
            let marca = tiff[kCGImagePropertyTIFFMake] as? String
            let modello = tiff[kCGImagePropertyTIFFModel] as? String
            scheda.fotocamera = [marca, modello]
                .compactMap { $0 }
                .joined(separator: " ")
                .trimmingCharacters(in: .whitespaces)
            if scheda.fotocamera?.isEmpty == true { scheda.fotocamera = nil }
        }

        if let gps = proprieta[kCGImagePropertyGPSDictionary] as? [CFString: Any] {
            if let latitudine = gps[kCGImagePropertyGPSLatitude] as? Double,
               let riferimento = gps[kCGImagePropertyGPSLatitudeRef] as? String {
                scheda.latitudine = riferimento == "S" ? -latitudine : latitudine
            }
            if let longitudine = gps[kCGImagePropertyGPSLongitude] as? Double,
               let riferimento = gps[kCGImagePropertyGPSLongitudeRef] as? String {
                scheda.longitudine = riferimento == "W" ? -longitudine : longitudine
            }
        }

        return scheda
    }

    private static func dataExif(_ testo: String?) -> Date? {
        guard let testo else { return nil }
        let formato = DateFormatter()
        formato.dateFormat = "yyyy:MM:dd HH:mm:ss"
        formato.timeZone = .current
        return formato.date(from: testo)
    }

    // MARK: - Video e audio

    private static func leggiFilmato(_ url: URL) async -> Scheda {
        var scheda = Scheda()
        let filmato = AVURLAsset(url: url)

        if let durata = try? await filmato.load(.duration) {
            let secondi = CMTimeGetSeconds(durata)
            if secondi.isFinite { scheda.durata = secondi }
        }

        if let creazione = try? await filmato.load(.creationDate),
           let data = try? await creazione.load(.dateValue) {
            scheda.data = data
        }

        if let tracce = try? await filmato.loadTracks(withMediaType: .video),
           let traccia = tracce.first,
           let misura = try? await traccia.load(.naturalSize),
           let trasformazione = try? await traccia.load(.preferredTransform) {
            let ruotata = misura.applying(trasformazione)
            scheda.larghezza = Int(abs(ruotata.width))
            scheda.altezza = Int(abs(ruotata.height))
        }

        if let comuni = try? await filmato.load(.commonMetadata) {
            for voce in comuni where voce.commonKey == .commonKeyLocation {
                if let testo = try? await voce.load(.stringValue) {
                    let posizione = posizioneISO6709(testo)
                    scheda.latitudine = posizione?.0
                    scheda.longitudine = posizione?.1
                }
            }
            for voce in comuni where voce.commonKey == .commonKeyModel {
                scheda.fotocamera = try? await voce.load(.stringValue)
            }
        }

        return scheda
    }

    /// I video registrano la posizione come `+44.6900+012.1800/`.
    private static func posizioneISO6709(_ testo: String) -> (Double, Double)? {
        let numeri = testo.matches(of: /[+-][0-9.]+/).map { String($0.output) }
        guard numeri.count >= 2,
              let latitudine = Double(numeri[0]),
              let longitudine = Double(numeri[1]) else { return nil }
        return (latitudine, longitudine)
    }

    // MARK: - Ripieghi

    private static func dataDalDisco(_ url: URL) -> Date? {
        let chiavi: Set<URLResourceKey> = [.contentCreationDateKey, .contentModificationDateKey]
        let valori = try? url.resourceValues(forKeys: chiavi)
        return valori?.contentCreationDate ?? valori?.contentModificationDate
    }

    /// Il nome del luogo dalle coordinate. Chiede a macOS, che risponde solo se
    /// c'è rete: senza, l'elemento resta con le coordinate e basta.
    static func nomeLuogo(latitudine: Double, longitudine: Double) async -> String? {
        let posizione = CLLocation(latitude: latitudine, longitude: longitudine)
        guard let luoghi = try? await CLGeocoder().reverseGeocodeLocation(posizione),
              let primo = luoghi.first else { return nil }

        let citta = primo.locality ?? primo.subAdministrativeArea
        let zona = primo.administrativeArea
        return [citta, zona].compactMap { $0 }.joined(separator: ", ")
    }
}
