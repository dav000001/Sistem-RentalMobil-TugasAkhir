import subprocess
import html
import re
import os

md_path = r'c:\xampp\htdocs\multi-vendor\rental-mobil\docs\EBOOK_PANDUAN_SISTEM_RENTAL_MOBIL.md'
html_path = r'c:\xampp\htdocs\multi-vendor\rental-mobil\docs\EBOOK_PANDUAN_SISTEM_RENTAL_MOBIL.html'
pdf_path = r'c:\xampp\htdocs\multi-vendor\rental-mobil\docs\EBOOK_PANDUAN_SISTEM_RENTAL_MOBIL.pdf'

with open(md_path, 'r', encoding='utf-8') as f:
    text = f.read()

css = """
@page {
    size: A4;
    margin: 20mm 15mm 20mm 15mm;
}
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #1e293b;
    background: #ffffff;
    margin: 0;
    padding: 10px;
}
.cover-page {
    text-align: center;
    padding: 120px 20px 80px 20px;
    page-break-after: always;
}
.cover-badge {
    display: inline-block;
    background: #1d4ed8;
    color: white;
    padding: 6px 20px;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 24px;
}
.cover-title {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    margin-bottom: 16px;
}
.cover-subtitle {
    font-size: 16px;
    color: #475569;
    font-weight: 500;
    max-width: 600px;
    margin: 0 auto 30px auto;
}
.cover-divider {
    width: 70px;
    height: 4px;
    background: #2563eb;
    margin: 25px auto;
    border-radius: 2px;
}
.cover-meta {
    font-size: 13.5px;
    color: #64748b;
    margin-top: 60px;
    line-height: 1.8;
}
h1 {
    font-size: 20px;
    color: #0f172a;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 6px;
    margin-top: 32px;
}
h2 {
    font-size: 17px;
    color: #1e3a8a;
    margin-top: 22px;
}
h3 {
    font-size: 15px;
    color: #1e293b;
    margin-top: 16px;
}
h4 {
    font-size: 14px;
    color: #334155;
    margin-top: 12px;
}
p, li {
    font-size: 13px;
    color: #334155;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 14px 0;
    font-size: 12px;
}
th {
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 700;
    border: 1px solid #cbd5e1;
    padding: 8px 10px;
    text-align: left;
}
td {
    border: 1px solid #cbd5e1;
    padding: 6px 10px;
}
tr:nth-child(even) td {
    background: #f8fafc;
}
pre {
    background: #0f172a;
    color: #f1f5f9;
    padding: 12px;
    border-radius: 6px;
    font-family: 'Consolas', monospace;
    font-size: 11px;
    line-height: 1.35;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}
code {
    background: #f1f5f9;
    color: #b91c1c;
    padding: 2px 4px;
    border-radius: 4px;
    font-family: 'Consolas', monospace;
    font-size: 11.5px;
}
pre code {
    background: transparent;
    color: #f1f5f9;
    padding: 0;
}
blockquote {
    border-left: 4px solid #2563eb;
    background: #eff6ff;
    margin: 14px 0;
    padding: 8px 14px;
    color: #1e40af;
    border-radius: 0 4px 4px 0;
}
hr {
    border: 0;
    height: 1px;
    background: #e2e8f0;
    margin: 24px 0;
}
.page-break {
    page-break-after: always;
}
"""

lines = text.split('\n')
out = []
in_pre = False
in_table = False

for line in lines:
    if line.strip().startswith('```'):
        if in_pre:
            out.append('</pre>')
            in_pre = False
        else:
            out.append('<pre><code>')
            in_pre = True
        continue
    if in_pre:
        out.append(html.escape(line))
        continue
    
    if '|' in line and not line.strip().startswith('```'):
        cells = [c.strip() for c in line.split('|')[1:-1]]
        if not cells:
            continue
        if all(re.match(r'^:?-+:?$', c) for c in cells):
            continue
        if not in_table:
            out.append('<table>')
            in_table = True
            out.append('<tr>' + ''.join(f'<th>{html.escape(c)}</th>' for c in cells) + '</tr>')
        else:
            out.append('<tr>' + ''.join(f'<td>{html.escape(c)}</td>' for c in cells) + '</tr>')
        continue
    else:
        if in_table:
            out.append('</table>')
            in_table = False

    s = line.strip()
    if not s:
        out.append('<br>')
        continue
    if s == '---':
        out.append('<hr>')
        continue
    if s.startswith('# '):
        out.append(f'<h1>{html.escape(s[2:])}</h1>')
    elif s.startswith('## '):
        out.append(f'<h2>{html.escape(s[3:])}</h2>')
    elif s.startswith('### '):
        out.append(f'<h3>{html.escape(s[4:])}</h3>')
    elif s.startswith('#### '):
        out.append(f'<h4>{html.escape(s[5:])}</h4>')
    elif s.startswith('* ') or s.startswith('- '):
        out.append(f'<li>{html.escape(s[2:])}</li>')
    elif re.match(r'^\d+\.\s', s):
        content = re.sub(r'^\d+\.\s', '', s)
        out.append(f'<p><strong>{s.split()[0]}</strong> {html.escape(content)}</p>')
    else:
        out.append(f'<p>{html.escape(s)}</p>')

if in_table:
    out.append('</table>')

body_html = '\n'.join(out)
body_html = re.sub(r'\*\*(.*?)\*\*', r'<strong>\1</strong>', body_html)
body_html = re.sub(r'\*(.*?)\*', r'<em>\1</em>', body_html)
body_html = re.sub(r'`(.*?)`', r'<code>\1</code>', body_html)

full_html = f"""<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>E-Book Panduan Sistem Rental Mobil Multi-Vendor</title>
<style>{css}</style>
</head>
<body>
<div class="cover-page">
    <div><span class="cover-badge">E-Book & Manual Book Resmi</span></div>
    <div class="cover-title">SISTEM INFORMASI RENTAL MOBIL MULTI-VENDOR BERBASIS WEB</div>
    <div class="cover-divider"></div>
    <div class="cover-subtitle">Buku Panduan Penggunaan dan Deskripsi Arsitektur Sistem Menyeluruh (Bab 1 - Bab 5)</div>
    <div class="cover-meta">
        <p><strong>Platform:</strong> Laravel 11 & Filament PHP v3 (TALL Stack)</p>
        <p><strong>Multi-Role:</strong> Pelanggan, Mitra Usaha (Vendor), Super Administrator, & Pengemudi (Driver)</p>
        <p>Tahun Penerbitan: 2026</p>
    </div>
</div>
<div class="page-break"></div>
{body_html}
</body>
</html>"""

with open(html_path, 'w', encoding='utf-8') as f:
    f.write(full_html)
print('Generated HTML successfully at:', html_path)

edge_path = r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
cmd = [
    edge_path,
    '--headless',
    '--disable-gpu',
    '--no-pdf-header-footer',
    f'--print-to-pdf={pdf_path}',
    html_path
]
res = subprocess.run(cmd, capture_output=True, text=True)
print('Edge return code:', res.returncode)
if os.path.exists(pdf_path):
    print('PDF generated successfully! Size:', os.path.getsize(pdf_path), 'bytes')
else:
    print('PDF generation failed.')
