import urllib.request
import re
import pypdf

url = "https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3812632/"
req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"})

with urllib.request.urlopen(req) as resp:
    html = resp.read().decode("utf-8")
    m = re.findall(r'href="([^"]+\.pdf)"', html)
    print("PDF links found:", m)
    if m:
        pdf_path = m[0]
        if not pdf_path.startswith("http"):
            pdf_path = "https://www.ncbi.nlm.nih.gov" + pdf_path
        print("Downloading from:", pdf_path)
        req_pdf = urllib.request.Request(pdf_path, headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"})
        with urllib.request.urlopen(req_pdf) as presp, open("artigos/Wang2013-Tacholess-Envelope-Bearings-Sensors.pdf", "wb") as f:
            f.write(presp.read())
        print("Downloaded file!")
        reader = pypdf.PdfReader("artigos/Wang2013-Tacholess-Envelope-Bearings-Sensors.pdf")
        print("Total pages:", len(reader.pages))
        print("Page 1 snippet:", reader.pages[0].extract_text()[:400])
