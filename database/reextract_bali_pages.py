"""Reextrai a Coleção Bali renderizando cada página (evita imagens embutidas reutilizadas)."""
import fitz
import json
from pathlib import Path

SRC = Path(r"C:\Users\Administrador\Downloads\colecao_bali_partes_cursor")
OUT = Path(r"c:\Users\Administrador\OneDrive\Desktop\mandalla\public\uploads\products\bali")
OUT.mkdir(parents=True, exist_ok=True)

# limpa jpgs antigos (mantém manifest se quiser)
for old in OUT.glob("bali-*.jpg"):
    old.unlink()

pdfs = sorted(SRC.glob("colecao_bali_parte_*.pdf"))
index = 1
manifest = []
hashes = {}

for pdf_path in pdfs:
    doc = fitz.open(pdf_path)
    print(f"=== {pdf_path.name} ({doc.page_count} pages) ===")
    for page_i, page in enumerate(doc):
        # render página inteira em boa qualidade
        pix = page.get_pixmap(matrix=fitz.Matrix(1.6, 1.6), alpha=False)
        filename = f"bali-{index:03d}.jpg"
        out_path = OUT / filename
        pix.save(str(out_path), jpg_quality=88)

        # hash para pular páginas visualmente idênticas na hora
        data = out_path.read_bytes()
        import hashlib
        h = hashlib.md5(data).hexdigest()
        if h in hashes:
            print(f"  page {page_i+1}: DUPLICATE of #{hashes[h]} -> skip keep file but mark")
            manifest.append({
                "index": index,
                "file": f"/uploads/products/bali/{filename}",
                "source": pdf_path.name,
                "page": page_i + 1,
                "w": pix.width,
                "h": pix.height,
                "duplicate_of": hashes[h],
            })
        else:
            hashes[h] = index
            print(f"  page {page_i+1}: {pix.width}x{pix.height} unique")
            manifest.append({
                "index": index,
                "file": f"/uploads/products/bali/{filename}",
                "source": pdf_path.name,
                "page": page_i + 1,
                "w": pix.width,
                "h": pix.height,
            })
        index += 1

(OUT / "manifest.json").write_text(json.dumps(manifest, indent=2), encoding="utf-8")
unique = sum(1 for m in manifest if "duplicate_of" not in m)
print(f"\nTotal pages={len(manifest)} unique={unique} dups={len(manifest)-unique}")
