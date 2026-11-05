import fitz
import os

pdf = r"C:\Users\Administrador\Downloads\colecao_bali_partes_cursor\colecao_bali_parte_01_a_10.pdf"
doc = fitz.open(pdf)
print("pages", doc.page_count)
page = doc[0]
print("size", page.rect)
imgs = page.get_images(full=True)
print("embedded images on page0", len(imgs))
if imgs:
    print("first", imgs[0])
print("text sample:", repr(page.get_text()[:500]))
out_dir = r"c:\Users\Administrador\OneDrive\Desktop\mandalla\public\uploads\products"
os.makedirs(out_dir, exist_ok=True)
pix = page.get_pixmap(matrix=fitz.Matrix(0.35, 0.35))
out = os.path.join(out_dir, "_preview_p0.png")
pix.save(out)
print("saved", out, pix.width, pix.height)

# try page 1 text too
for i in range(min(3, doc.page_count)):
    t = doc[i].get_text().strip()
    print(f"--- page {i+1} text len={len(t)} ---")
    print(t[:400] if t else "(no text)")
