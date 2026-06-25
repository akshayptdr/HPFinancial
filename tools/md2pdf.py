#!/usr/bin/env python3
"""
md2pdf.py — Convert a Markdown file to a clean A4 PDF.

Pipeline: Markdown -> styled HTML (python-markdown) -> Chrome headless --print-to-pdf

Usage:
    python tools/md2pdf.py <input.md> [output.pdf]

If output.pdf is omitted, it is derived from the input filename.
"""
import sys
import os
import subprocess
import tempfile

try:
    import markdown
except ImportError:
    sys.exit("Missing dependency: run  pip install markdown")

CHROME_CANDIDATES = [
    r"C:\Program Files\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    r"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
]

CSS = """
@page { size: A4; margin: 18mm 16mm; }
* { box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11px; line-height: 1.5; color: #1a1a1a; margin: 0;
}
h1 { font-size: 22px; color: #14306b; border-bottom: 3px solid #2563eb;
     padding-bottom: 6px; margin: 0 0 12px; }
h2 { font-size: 16px; color: #14306b; margin: 20px 0 8px;
     border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
h3 { font-size: 13px; color: #1d4ed8; margin: 14px 0 6px; }
h4 { font-size: 12px; color: #334155; margin: 12px 0 4px; }
p, li { margin: 4px 0; }
code { background: #f1f5f9; padding: 1px 4px; border-radius: 3px;
       font-family: Consolas, monospace; font-size: 10px; }
pre { background: #0f172a; color: #e2e8f0; padding: 10px; border-radius: 6px;
      overflow-x: auto; font-size: 9.5px; line-height: 1.4; }
pre code { background: transparent; color: inherit; padding: 0; }
table { border-collapse: collapse; width: 100%; margin: 8px 0; font-size: 10px; }
th, td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: left;
         vertical-align: top; }
th { background: #e0e7ff; }
blockquote { border-left: 4px solid #2563eb; background: #eff6ff; margin: 8px 0;
             padding: 6px 12px; color: #1e3a8a; }
hr { border: none; border-top: 1px solid #cbd5e1; margin: 16px 0; }
a { color: #2563eb; }
strong { color: #0f172a; }
"""


def find_chrome():
    for path in CHROME_CANDIDATES:
        if os.path.exists(path):
            return path
    sys.exit("Chrome/Edge not found. Edit CHROME_CANDIDATES in md2pdf.py.")


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    md_path = os.path.abspath(sys.argv[1])
    if not os.path.exists(md_path):
        sys.exit(f"Input not found: {md_path}")
    pdf_path = os.path.abspath(sys.argv[2]) if len(sys.argv) > 2 \
        else os.path.splitext(md_path)[0] + ".pdf"

    with open(md_path, "r", encoding="utf-8") as f:
        md_text = f.read()

    html_body = markdown.markdown(
        md_text,
        extensions=["tables", "fenced_code", "toc", "sane_lists", "attr_list"],
    )
    title = os.path.splitext(os.path.basename(md_path))[0]
    html = f"""<!DOCTYPE html><html><head><meta charset="utf-8">
<title>{title}</title><style>{CSS}</style></head>
<body>{html_body}</body></html>"""

    html_path = os.path.join(tempfile.gettempdir(), title + "_render.html")
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html)

    chrome = find_chrome()
    cmd = [
        chrome, "--headless", "--disable-gpu", "--no-sandbox",
        "--no-pdf-header-footer",
        f"--print-to-pdf={pdf_path}",
        "file:///" + html_path.replace("\\", "/"),
    ]
    print("Rendering:", md_path, "->", pdf_path)
    res = subprocess.run(cmd, capture_output=True, text=True)
    if not os.path.exists(pdf_path):
        sys.stderr.write(res.stdout + "\n" + res.stderr + "\n")
        sys.exit("PDF was not produced.")
    size = os.path.getsize(pdf_path)
    print(f"OK: {pdf_path} ({size:,} bytes)")


if __name__ == "__main__":
    main()
