# Migrasi berkas dummy lama -> struktur baru (sekali jalan saat SPEC-DB).
# - 7 PDF lama disalin ke storage/ebooks/{id}.pdf
# - 4 EPUB/MOBI diganti PDF placeholder 12 halaman (PDF-saja, PDF.js)
# - 10 cover lama disalin ke public/uploads/covers/{id}.jpg
# - metadata (page_count, file_size, checksum_sha256) dicetak untuk seed SQL
import hashlib
import json
import shutil
from pathlib import Path

from pypdf import PdfReader, PdfWriter

ROOT = Path(__file__).resolve().parent.parent
BUKU = [
    # id, berkas lama, judul
    (1, "bk001.pdf", "Dasar-Dasar Basis Data"),
    (2, "bk002.pdf", "Belajar Java OOP"),
    (3, "bk003.epub", "Laskar Pelangi"),
    (4, "bk004.pdf", "Fisika Dasar Modern"),
    (5, "bk005.epub", "Sejarah Nusantara"),
    (6, "bk006.pdf", "Strategi Bisnis Digital"),
    (7, "bk007.pdf", "Harry Potter 1"),
    (8, "bk008.mobi", "Metode Pembelajaran Aktif"),
    (9, "bk009.epub", "Psikologi Remaja"),
    (10, "bk010.pdf", "Prinsip Desain UI/UX"),
]


def pdf_placeholder(path: Path, judul: str, halaman: int = 12) -> None:
    """PDF teks sederhana yang valid (Helvetica), tanpa dependensi eksternal."""
    objs = {}
    kids = " ".join(f"{4 + 2 * i} 0 R" for i in range(halaman))
    objs[1] = b"<< /Type /Catalog /Pages 2 0 R >>"
    objs[2] = f"<< /Type /Pages /Kids [{kids}] /Count {halaman} >>".encode()
    objs[3] = b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"
    for i in range(halaman):
        teks = judul.replace("(", r"\(").replace(")", r"\)")
        stream = (
            f"BT /F1 24 Tf 72 720 Td ({teks}) Tj ET\n"
            f"BT /F1 12 Tf 72 690 Td (Halaman {i + 1} dari {halaman} - berkas dummy pembelajaran.) Tj ET\n"
            "BT /F1 12 Tf 72 660 Td (Konten contoh untuk menguji pembaca PDF.js dan auto-save last_page.) Tj ET"
        ).encode()
        objs[4 + 2 * i] = (
            f"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
            f"/Resources << /Font << /F1 3 0 R >> >> /Contents {5 + 2 * i} 0 R >>"
        ).encode()
        objs[5 + 2 * i] = f"<< /Length {len(stream)} >>\nstream\n".encode() + stream + b"\nendstream"

    out = bytearray(b"%PDF-1.4\n")
    offsets = {}
    for nomor in sorted(objs):
        offsets[nomor] = len(out)
        out += f"{nomor} 0 obj\n".encode() + objs[nomor] + b"\nendobj\n"
    xref = len(out)
    out += f"xref\n0 {len(objs) + 1}\n0000000000 65535 f \n".encode()
    for nomor in sorted(objs):
        out += f"{offsets[nomor]:010d} 00000 n \n".encode()
    out += f"trailer\n<< /Size {len(objs) + 1} /Root 1 0 R >>\nstartxref\n{xref}\n%%EOF".encode()
    path.write_bytes(bytes(out))


def main() -> None:
    storage = ROOT / "storage" / "ebooks"
    covers = ROOT / "public" / "uploads" / "covers"
    storage.mkdir(parents=True, exist_ok=True)
    covers.mkdir(parents=True, exist_ok=True)

    meta = {}
    for id_buku, berkas, judul in BUKU:
        src = ROOT / "ebook" / berkas
        dst = storage / f"{id_buku}.pdf"
        if src.suffix.lower() == ".pdf":
            shutil.copyfile(src, dst)
        else:
            pdf_placeholder(dst, judul)
        shutil.copyfile(ROOT / "cover" / f"bk{id_buku:03d}.jpg", covers / f"{id_buku}.jpg")

        data = dst.read_bytes()
        meta[id_buku] = {
            "page_count": len(PdfReader(dst).pages),
            "file_size": len(data),
            "checksum_sha256": hashlib.sha256(data).hexdigest(),
        }
    print(json.dumps(meta, indent=1))


if __name__ == "__main__":
    main()
