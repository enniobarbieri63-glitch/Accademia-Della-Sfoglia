import AppKit
import CoreImage
import CoreImage.CIFilterBuiltins
import Foundation

/// Le due cose che servono per far arrivare i file da un telefono:
/// l'indirizzo del Mac sulla rete di casa e il codice QR da inquadrare.
enum ReteLocale {

    /// L'indirizzo IPv4 del Mac sulla rete locale. `nil` quando il Mac non è
    /// collegato a nessuna rete: in quel caso non c'è niente da promettere all'utente.
    static func indirizzo() -> String? {
        var elenco: UnsafeMutablePointer<ifaddrs>?
        guard getifaddrs(&elenco) == 0, let primo = elenco else { return nil }
        defer { freeifaddrs(elenco) }

        var migliore: String?

        for interfaccia in sequence(first: primo, next: { $0.pointee.ifa_next }) {
            let bandiere = Int32(interfaccia.pointee.ifa_flags)
            guard bandiere & IFF_UP == IFF_UP,
                  bandiere & IFF_LOOPBACK == 0,
                  let indirizzoGrezzo = interfaccia.pointee.ifa_addr,
                  indirizzoGrezzo.pointee.sa_family == UInt8(AF_INET)
            else { continue }

            var nome = [CChar](repeating: 0, count: Int(NI_MAXHOST))
            guard getnameinfo(indirizzoGrezzo,
                              socklen_t(indirizzoGrezzo.pointee.sa_len),
                              &nome, socklen_t(nome.count),
                              nil, 0, NI_NUMERICHOST) == 0 else { continue }

            let testo = String(cString: nome)
            // Gli indirizzi 169.254.x.x vogliono dire "rete non trovata".
            guard !testo.hasPrefix("169.254") else { continue }

            let interfacciaNome = String(cString: interfaccia.pointee.ifa_name)
            // Wi-Fi ed Ethernet prima di tutto il resto (VPN, macchine virtuali).
            if interfacciaNome.hasPrefix("en") { return testo }
            if migliore == nil { migliore = testo }
        }
        return migliore
    }

    /// Il codice QR che il telefono inquadra per aprire la pagina.
    static func codiceQR(per testo: String, lato: CGFloat = 220) -> NSImage? {
        let filtro = CIFilter.qrCodeGenerator()
        filtro.message = Data(testo.utf8)
        filtro.correctionLevel = "M"

        guard let piccolo = filtro.outputImage else { return nil }

        let ingrandimento = lato / piccolo.extent.width
        let grande = piccolo.transformed(by: CGAffineTransform(scaleX: ingrandimento, y: ingrandimento))

        guard let cg = MotoreImmagine.contesto.createCGImage(grande, from: grande.extent) else { return nil }
        return NSImage(cgImage: cg, size: NSSize(width: lato, height: lato))
    }

    /// Quattro cifre da far comparire nell'indirizzo, così chi è sulla stessa rete
    /// non può mandare roba senza sapere il codice.
    static func codiceNuovo() -> String {
        String(format: "%04d", Int.random(in: 0...9999))
    }
}
