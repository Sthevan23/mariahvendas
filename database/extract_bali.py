"""Extrai a maior imagem embutida de cada página dos PDFs da Coleção Bali."""
import fitz
import os
from pathlib import Path

SRC = Path(r"C:\Users\Administrador\Downloads\colecao_bali_partes_cursor")
OUT = Path(r"c:\Users\Administrador\OneDrive\Desktop\mandalla\public\uploads\products\bali")
OUT.mkdir(parents=True, exist_ok=True)

pdfs = sorted(SRC.glob("colecao_bali_parte_*.pdf"))
index = 1
manifest = []

for pdf_path in pdfs:
    doc = fitz.open(pdf_path)
    print(f"=== {pdf_path.name} ({doc.page_count} pages) ===")
    for page_i, page in enumerate(doc):
        images = page.get_images(full=True)
        best = None
        best_size = 0
        for img in images:
            xref = img[0]
            try:
                pix = fitz.Pixmap(doc, xref)
                if pix.n - pix.alpha >= 4:  # CMYK
                    pix = fitz.Pixmap(fitz.csRGB, pix)
                size = pix.width * pix.height
                if size > best_size:
                    best_size = size
                    best = pix
            except Exception as e:
                print(f"  skip xref {xref}: {e}")
                continue

        if best is None:
            # fallback: render page
            best = page.get_pixmap(matrix=fitz.Matrix(1.5, 1.5), alpha=False)
            print(f"  page {page_i+1}: rendered fallback {best.width}x{best.height}")
        else:
            print(f"  page {page_i+1}: extracted {best.width}x{best.height}")

        filename = f"bali-{index:03d}.jpg"
        out_path = OUT / filename
        # save as jpeg
        if hasattr(best, "n") and best.n == 4 and best.alpha:
            best = fitz.Pixmap(fitz.csRGB, best)
        best.save(str(out_path), jpg_quality=88)
        manifest.append({
            "index": index,
            "file": f"/uploads/products/bali/{filename}",
            "source": pdf_path.name,
            "page": page_i + 1,
            "w": best.width,
            "h": best.height,
        })
        index += 1

# write manifest
import json
manifest_path = OUT / "manifest.json"
manifest_path.write_text(json.dumps(manifest, indent=2), encoding="utf-8")
print(f"\nTotal: {len(manifest)} images -> {OUT}")
