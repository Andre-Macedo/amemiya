"""Offline HTML, LaTeX math, and Mermaid SVG renderer for Pacote_TCC_Amemiya."""

from __future__ import annotations

import hashlib
import html as html_lib
import json
import re
import shutil
import subprocess
import urllib.request
from pathlib import Path

MERMAID_CDN_URL = "https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"

MATH_SYMBOLS = {
    "Delta": "&Delta;",
    "Omega": "&Omega;",
    "Sigma": "&Sigma;",
    "approx": "&approx;",
    "cdot": "&middot;",
    "circ": "&deg;",
    "ge": "&ge;",
    "geq": "&ge;",
    "lambda": "&lambda;",
    "le": "&le;",
    "leq": "&le;",
    "mu": "&mu;",
    "pi": "&pi;",
    "pm": "&plusmn;",
    "quad": "&emsp;",
    "qquad": "&emsp;&emsp;",
    "rightarrow": "&rarr;",
    "to": "&rarr;",
    "sim": "&sim;",
    "theta": "&theta;",
    "times": "&times;",
    "varnothing": "&empty;",
    "infty": "&infin;",
    "neq": "&ne;",
    "equiv": "&equiv;",
}

MATH_FUNCTIONS = {"cos", "sin", "tan", "log", "ln", "exp", "max", "min"}


class _LatexParser:
    """Minimal recursive-descent parser converting TCC LaTeX math subsets to clean HTML/CSS."""

    def __init__(self, src: str) -> None:
        self.src = src
        self.pos = 0
        self.n = len(src)

    def _skip_ws(self) -> None:
        while self.pos < self.n and self.src[self.pos].isspace():
            self.pos += 1

    def _read_braced_raw(self) -> str:
        self._skip_ws()
        if self.pos >= self.n:
            return ""
        if self.src[self.pos] != "{":
            if self.src[self.pos] == "\\":
                start = self.pos
                self.pos += 1
                while self.pos < self.n and self.src[self.pos].isalpha():
                    self.pos += 1
                return self.src[start:self.pos]
            ch = self.src[self.pos]
            self.pos += 1
            return ch
        self.pos += 1
        depth = 1
        start = self.pos
        while self.pos < self.n and depth > 0:
            ch = self.src[self.pos]
            if ch == "\\" and self.pos + 1 < self.n:
                self.pos += 2
                continue
            if ch == "{":
                depth += 1
            elif ch == "}":
                depth -= 1
                if depth == 0:
                    inner = self.src[start:self.pos]
                    self.pos += 1
                    return inner
            self.pos += 1
        return self.src[start:]

    def _read_arg_html(self) -> str:
        raw = self._read_braced_raw()
        return _LatexParser(raw).parse()

    def _read_script_suffix(self) -> tuple[str | None, str | None]:
        sub_html: str | None = None
        sup_html: str | None = None
        for _ in range(2):
            self._skip_ws()
            if self.pos >= self.n:
                break
            marker = self.src[self.pos]
            if marker == "_" and sub_html is None:
                self.pos += 1
                sub_html = self._read_arg_html()
            elif marker == "^" and sup_html is None:
                self.pos += 1
                sup_html = self._read_arg_html()
            else:
                break
        return sub_html, sup_html

    def _parse_text_mode(self, raw: str) -> str:
        cleaned = raw.replace("--", "&ndash;")
        # Restore literal en-dash safely while HTML-escaping everything else
        parts = cleaned.split("&ndash;")
        return "&ndash;".join(html_lib.escape(p, quote=False) for p in parts)

    def _parse_atom(self) -> str:
        ch = self.src[self.pos]
        if ch == "{":
            return self._read_arg_html()

        if ch == "\\":
            self.pos += 1
            if self.pos >= self.n:
                return ""
            nxt = self.src[self.pos]
            if not nxt.isalpha():
                self.pos += 1
                esc_map = {
                    "%": "%",
                    " ": "&nbsp;",
                    ",": "&thinsp;",
                    ";": "&ensp;",
                    "{": "{",
                    "}": "}",
                    "\\": "<br>",
                }
                return esc_map.get(nxt, html_lib.escape(nxt, quote=False))

            start = self.pos
            while self.pos < self.n and self.src[self.pos].isalpha():
                self.pos += 1
            cmd = self.src[start:self.pos]

            if cmd in ("left", "right"):
                self._skip_ws()
                if self.pos < self.n:
                    delim = self.src[self.pos]
                    self.pos += 1
                    if delim == ".":
                        return ""
                    if delim == "\\" and self.pos < self.n:
                        d2 = self.src[self.pos]
                        self.pos += 1
                        return html_lib.escape(d2, quote=False)
                    return html_lib.escape(delim, quote=False)
                return ""

            if cmd == "frac":
                num = self._read_arg_html()
                den = self._read_arg_html()
                return f'<span class="mfrac"><span class="mnum">{num}</span><span class="mden">{den}</span></span>'

            if cmd == "sqrt":
                body = self._read_arg_html()
                return f'<span class="msqrt"><span class="mrad">&radic;</span><span class="msqrt-body">{body}</span></span>'

            if cmd in ("text", "mathrm", "textrm"):
                raw_txt = self._read_braced_raw()
                return f'<span class="mtext">{self._parse_text_mode(raw_txt)}</span>'

            if cmd in ("mathbf", "textbf"):
                inner = self._read_arg_html()
                return f'<strong class="mbold">{inner}</strong>'

            if cmd == "sum":
                sub_h, sup_h = self._read_script_suffix()
                if sub_h is not None or sup_h is not None:
                    sup_span = f'<span class="mop-sup">{sup_h}</span>' if sup_h else '<span class="mop-sup"></span>'
                    sub_span = f'<span class="mop-sub">{sub_h}</span>' if sub_h else '<span class="mop-sub"></span>'
                    return f'<span class="mop">{sup_span}<span class="mop-sym">&Sigma;</span>{sub_span}</span>'
                return '<span class="mop-sym">&Sigma;</span>'

            if cmd in MATH_FUNCTIONS:
                return f'<span class="mfunc">{cmd}</span>'

            if cmd in MATH_SYMBOLS:
                return MATH_SYMBOLS[cmd]

            return f'<span class="mtext">{html_lib.escape(cmd, quote=False)}</span>'

        self.pos += 1
        if ch.isalpha():
            return f"<var>{html_lib.escape(ch, quote=False)}</var>"
        if ch in ("=", "+", "-", "<", ">"):
            # Unary minus at start of group/after comparator stays tight to the number
            prev_non_ws = self.src[: self.pos - 1].rstrip()
            if ch == "-" and (not prev_non_ws or prev_non_ws[-1] in ("=", "(", "[", "{", "<", ">", ",")):
                return "-"
            return f'<span class="mop-bin">{html_lib.escape(ch, quote=False)}</span>'
        return html_lib.escape(ch, quote=False)

    def parse(self) -> str:
        out: list[str] = []
        while self.pos < self.n:
            ch = self.src[self.pos]
            if ch.isspace():
                self.pos += 1
                if out and not out[-1].endswith((" ", "&nbsp;", "&emsp;")):
                    out.append(" ")
                continue
            if ch in ("_", "^"):
                atom = ""
            else:
                atom = self._parse_atom()

            sub_h, sup_h = self._read_script_suffix()
            if sub_h is not None and sup_h is not None:
                out.append(f"{atom}<sub>{sub_h}</sub><sup>{sup_h}</sup>")
            elif sub_h is not None:
                out.append(f"{atom}<sub>{sub_h}</sub>")
            elif sup_h is not None:
                out.append(f"{atom}<sup>{sup_h}</sup>")
            else:
                out.append(atom)
        return "".join(out).strip()


def latex_to_html(expr: str, display: bool = False) -> str:
    """Convert a LaTeX math expression into semantic HTML + CSS classes."""
    body = _LatexParser(expr.strip()).parse()
    if display:
        return f'<div class="math-display"><span class="math">{body}</span></div>'
    return f'<span class="math math-inline">{body}</span>'


def _ensure_mermaid_js(cache_dir: Path) -> Path | None:
    cache_dir.mkdir(parents=True, exist_ok=True)
    js_path = cache_dir / "mermaid.min.js"
    if js_path.exists() and js_path.stat().st_size > 100_000:
        return js_path
    try:
        with urllib.request.urlopen(MERMAID_CDN_URL, timeout=15) as resp:
            if resp.status == 200:
                js_path.write_bytes(resp.read())
                return js_path
    except Exception:
        return None
    return None


def _render_mermaid_svgs(diagrams: list[tuple[str, str]], root_dir: Path, pacote_dir: Path) -> dict[str, str]:
    """Pre-render Mermaid blocks to inline SVGs using headless Playwright with SHA-256 cache."""
    if not diagrams:
        return {}

    cache_dir = root_dir / "docs" / ".vitepress" / "cache" / "tcc"
    svg_cache_file = cache_dir / "mermaid_svg_cache.json"
    svg_cache: dict[str, str] = {}
    if svg_cache_file.exists():
        try:
            svg_cache = json.loads(svg_cache_file.read_text(encoding="utf-8"))
        except Exception:
            svg_cache = {}

    results: dict[str, str] = {}
    missing: list[dict[str, str]] = []
    hashes: dict[str, str] = {}

    for key, code in diagrams:
        digest = hashlib.sha256(code.strip().encode("utf-8")).hexdigest()[:16]
        hashes[key] = digest
        if digest in svg_cache and "<svg" in svg_cache[digest]:
            results[key] = f'<figure class="mermaid-diagram">{svg_cache[digest]}</figure>'
        else:
            missing.append({"id": f"mmd_{digest}", "key": key, "code": code.strip()})

    if missing:
        mermaid_js = _ensure_mermaid_js(cache_dir)
        node_bin = shutil.which("node")
        render_script = root_dir / "scripts" / "render_mermaid_svg.mjs"

        rendered_ok = False
        if mermaid_js and node_bin and render_script.exists():
            try:
                proc = subprocess.run(
                    [node_bin, str(render_script), str(mermaid_js)],
                    input=json.dumps([{"id": m["id"], "code": m["code"]} for m in missing]),
                    text=True,
                    capture_output=True,
                    cwd=str(root_dir),
                    timeout=60,
                )
                if proc.returncode == 0 and proc.stdout.strip().startswith("["):
                    payload = json.loads(proc.stdout)
                    for item, meta in zip(payload, missing):
                        if item.get("svg"):
                            svg_cache[hashes[meta["key"]]] = item["svg"]
                            results[meta["key"]] = f'<figure class="mermaid-diagram">{item["svg"]}</figure>'
                    svg_cache_file.write_text(json.dumps(svg_cache, ensure_ascii=False), encoding="utf-8")
                    rendered_ok = all(m["key"] in results for m in missing)
            except Exception:
                rendered_ok = False

        if not rendered_ok:
            # Offline fallback: bundle classic script if available
            if mermaid_js:
                assets_dir = pacote_dir / "assets"
                assets_dir.mkdir(parents=True, exist_ok=True)
                shutil.copy2(mermaid_js, assets_dir / "mermaid.min.js")
            for meta in missing:
                if meta["key"] not in results:
                    results[meta["key"]] = f'<div class="mermaid">{html_lib.escape(meta["code"])}</div>'

    return results


def _render_single_markdown(
    raw: str,
    md_path: Path,
    pacote_dir: Path,
    known_stems: dict[str, str],
    md_lib,
    mermaid_queue: list[tuple[str, str]],
) -> tuple[str, dict[str, str]]:
    """Preprocess Markdown: protect code/mermaid/math, rewrite relative links/images, convert to HTML."""
    doc_rel_dir = md_path.parent.relative_to(pacote_dir).as_posix()
    replacements: dict[str, str] = {}

    # 1. Extract fenced code blocks (mermaid + normal code) before math/markdown processing
    code_placeholders: dict[str, str] = {}

    def repl_fence(m: re.Match) -> str:
        lang = (m.group(1) or "").strip().lower()
        body = m.group(2)
        if lang == "mermaid":
            token = f"AMEMIYAMERMAID{len(mermaid_queue)}TOKEN"
            mermaid_queue.append((token, body))
            return f"\n\n{token}\n\n"
        token = f"AMEMIYACODEFENCE{len(code_placeholders)}TOKEN"
        code_placeholders[token] = m.group(0)
        return token

    text = re.sub(r"```([a-zA-Z0-9_-]*)\s*\r?\n(.*?)```", repl_fence, raw, flags=re.S)

    # 2. Protect inline code spans so $VAR inside backticks is never parsed as LaTeX
    inline_code_placeholders: dict[str, str] = {}

    def repl_inline_code(m: re.Match) -> str:
        token = f"AMEMIYAINLINECODE{len(inline_code_placeholders)}TOKEN"
        inline_code_placeholders[token] = m.group(0)
        return token

    text = re.sub(r"`[^`\n]+`", repl_inline_code, text)

    # 3. VitePress callouts -> readable Markdown blockquotes
    text = re.sub(
        r"^:::\s*(info|tip|warning|danger|caution)\s*(.*)$",
        r"> **[\1] \2**",
        text,
        flags=re.M | re.I,
    )
    text = re.sub(r"^:::\s*$", "", text, flags=re.M)

    # 4. Rewrite relative image URLs so they resolve from Pacote_TCC_Amemiya root
    def repl_image(m: re.Match) -> str:
        alt, src = m.group(1), m.group(2).strip()
        if src.startswith(("http://", "https://", "data:", "/")):
            return m.group(0)
        clean_src = src.lstrip("./")
        return f"![{alt}]({doc_rel_dir}/{clean_src})"

    text = re.sub(r"!\[([^\]]*)\]\(([^)]+)\)", repl_image, text)

    # 5. Rewrite internal Markdown/VitePress links to HTML anchors
    def repl_link(m: re.Match) -> str:
        label, href = m.group(1), m.group(2).strip()
        if href.startswith(("http://", "https://", "mailto:", "#")):
            return m.group(0)
        stem = Path(href.split("#")[0]).stem
        if stem in known_stems:
            return f"[{label}](#{known_stems[stem]})"
        return m.group(0)

    text = re.sub(r"(?<!!)\[([^\]]+)\]\(([^)]+)\)", repl_link, text)

    # 6. Extract display math $$...$$ then inline math $...$
    math_counter = 0

    def repl_display_math(m: re.Match) -> str:
        nonlocal math_counter
        token = f"AMEMIYAMATHDISP{math_counter}TOKEN"
        math_counter += 1
        replacements[token] = latex_to_html(m.group(1), display=True)
        return f"\n\n{token}\n\n"

    text = re.sub(r"\$\$(.*?)\$\$", repl_display_math, text, flags=re.S)

    def repl_inline_math(m: re.Match) -> str:
        nonlocal math_counter
        token = f"AMEMIYAMATHINL{math_counter}TOKEN"
        math_counter += 1
        replacements[token] = latex_to_html(m.group(1), display=False)
        return token

    # Ignore currency like "US$ 500" (space immediately after $)
    text = re.sub(r"(?<!\\)(?<!\$)\$(?!\s|\$)((?:\\.|[^$\n\\])*(?<![\s\\]))\$(?!\$)", repl_inline_math, text)

    # 7. Restore code tokens before Markdown parser runs
    for token, original in inline_code_placeholders.items():
        text = text.replace(token, original)
    for token, original in code_placeholders.items():
        text = text.replace(token, original)

    if md_lib is not None:
        html_out = md_lib.markdown(text, extensions=["tables", "fenced_code", "sane_lists"])
    else:
        html_out = f"<pre>{html_lib.escape(text)}</pre>"

    # 8. Inject rendered math tokens cleanly (unwrapping <p> around block display math)
    for token, math_html in replacements.items():
        html_out = html_out.replace(f"<p>{token}</p>", math_html)
        html_out = html_out.replace(token, math_html)

    return html_out, {}


def build_tcc_offline_html(pacote_dir: Path, root_dir: Path) -> Path:
    """Generate self-contained LEIA_NO_NAVEGADOR.html with rendered schematics, Mermaid SVGs, and LaTeX math."""
    try:
        import markdown as md_lib
    except ImportError:
        md_lib = None

    sections: list[tuple[str, Path, str]] = []
    known_stems: dict[str, str] = {}

    dec_dir = pacote_dir / "03_Documentacao_e_Decisoes_Arquiteturais"
    art_dir = pacote_dir / "04_Artigos_e_Referencias_Cientificas"

    if dec_dir.exists():
        spec_order = [
            "arquitetura-distribuida.md",
            "pinagem-hardware.md",
            "dsp-vibracao-fft.md",
            "norma-iso-20816.md",
            "payload-lora-binario.md",
            "api-mqtt-telemetria.md",
            "REGRAS_DE_NEGOCIO_MLOPS_E_AMOSTRAGEM.md",
        ]
        for fname in spec_order:
            p = dec_dir / fname
            if p.exists():
                anchor = "doc-" + re.sub(r"[^a-zA-Z0-9_-]", "-", p.stem)
                known_stems[p.stem] = anchor
                known_stems[p.name] = anchor
                sections.append(("Arquitetura, Hardware & DSP", p, anchor))
        for p in sorted(dec_dir.glob("ADR-*.md")):
            anchor = "doc-" + re.sub(r"[^a-zA-Z0-9_-]", "-", p.stem)
            known_stems[p.stem] = anchor
            known_stems[p.name] = anchor
            sections.append(("Decisões Arquiteturais (ADRs)", p, anchor))

    if art_dir.exists():
        for p in sorted(art_dir.glob("*.md")):
            anchor = "doc-" + re.sub(r"[^a-zA-Z0-9_-]", "-", p.stem)
            known_stems[p.stem] = anchor
            known_stems[p.name] = anchor
            sections.append(("Artigos Científicos, Auditoria ML & Normas", p, anchor))

    # Aliases para docs resumidos removidos do HTML para evitar redundância
    if "DECISOES_TECNICAS_E_REFERENCIAS_CIENTIFICAS" in known_stems:
        known_stems["referencias-cientificas"] = known_stems["DECISOES_TECNICAS_E_REFERENCIAS_CIENTIFICAS"]
    if "ADR-008-ml-hibrido-isolation-forest-xgboost-hitl" in known_stems:
        known_stems["tinyml-vs-cloud"] = known_stems["ADR-008-ml-hibrido-isolation-forest-xgboost-hitl"]

    pdf_dir = pacote_dir / "02_Hardware_Projetos_KiCad" / "Esquematicos_PDF"
    pdf_pills: list[str] = []
    png_figures: list[str] = []
    if pdf_dir.exists():
        for pf in sorted(pdf_dir.iterdir()):
            if pf.suffix.lower() == ".pdf":
                rel = pf.relative_to(pacote_dir).as_posix()
                pdf_pills.append(f'<a class="pdf-pill" href="{html_lib.escape(rel)}" target="_blank">PDF · {html_lib.escape(pf.name)}</a>')
            elif pf.suffix.lower() == ".png":
                rel = pf.relative_to(pacote_dir).as_posix()
                label = pf.stem.replace("_", " ")
                png_figures.append(
                    f'<figure class="schem-card">'
                    f'<a href="{html_lib.escape(rel)}" target="_blank">'
                    f'<img src="{html_lib.escape(rel)}" alt="{html_lib.escape(label)}" loading="lazy">'
                    f'</a>'
                    f'<figcaption><a href="{html_lib.escape(rel)}" target="_blank">{html_lib.escape(label)}</a></figcaption>'
                    f'</figure>'
                )

    nav_groups: dict[str, list[tuple[str, str]]] = {}
    body_blocks: list[str] = []
    mermaid_queue: list[tuple[str, str]] = []

    for group_title, md_path, anchor in sections:
        raw = md_path.read_text(encoding="utf-8")
        first_h1 = next((l.lstrip("# ").strip() for l in raw.splitlines() if l.startswith("# ")), md_path.stem)
        clean_h1 = re.sub(r"[`*]", "", first_h1)
        nav_groups.setdefault(group_title, []).append((anchor, clean_h1))
        rendered, _ = _render_single_markdown(raw, md_path, pacote_dir, known_stems, md_lib, mermaid_queue)
        body_blocks.append(f'<article id="{anchor}" class="doc-card">{rendered}</article>')

    # Batch-render all collected Mermaid diagrams to inline SVGs and splice them into articles
    mermaid_svgs = _render_mermaid_svgs(mermaid_queue, root_dir, pacote_dir)
    if mermaid_svgs:
        patched_blocks: list[str] = []
        for blk in body_blocks:
            for token, svg_html in mermaid_svgs.items():
                blk = blk.replace(f"<p>{token}</p>", svg_html).replace(token, svg_html)
            patched_blocks.append(blk)
        body_blocks = patched_blocks

    needs_mermaid_js = any('class="mermaid"' in blk for blk in body_blocks)
    fallback_js = pacote_dir / "assets" / "mermaid.min.js"
    if not needs_mermaid_js and fallback_js.exists():
        fallback_js.unlink()
        if fallback_js.parent.exists() and not any(fallback_js.parent.iterdir()):
            fallback_js.parent.rmdir()

    mermaid_fallback_script = ""
    if needs_mermaid_js and fallback_js.exists():
        mermaid_fallback_script = (
            '<script src="assets/mermaid.min.js"></script>'
            "<script>if(window.mermaid){window.mermaid.initialize({startOnLoad:true,theme:'default'});}</script>"
        )

    pdf_display_titles = {
        "NR-10.pdf": "Norma Regulamentadora NR-10 — Segurança em Instalações e Serviços em Eletricidade (MTE)",
        "NR-12.pdf": "Norma Regulamentadora NR-12 — Segurança no Trabalho em Máquinas e Equipamentos (MTE)",
        "Jakobsen2024_HardwareX_MEMS_LoRa_Mancal.pdf": "Jakobsen, M. O. (HardwareX, 2024) — Low cost MEMS accelerometer and microphone based condition monitoring sensor, with LoRa",
        "Janssens2019_Thermal_Imaging_Vibration_Fault_Detection.pdf": "Janssens, O.; Loccufier, M.; Van Hoecke, S. (IEEE TII, 2019) — Thermal Imaging and Vibration Based Multi-Sensor Fault Detection for Rotating Machinery",
        "Kibrete2026_Vibration_Condition_Monitoring_Fault_Diagnosis.pdf": "Kibrete, F. et al. (Results in Engineering, 2026) — Vibration-based condition monitoring and fault diagnosis of rotating machinery",
        "Gildish2024_Vibration_Rotation_Speed_Estimation_IIoT.pdf": "Gildish, E.; Grebshtein, M.; Makienko, I. (Internet of Things, 2024) — Vibration-based rotation speed estimation for Industrial IoT",
        "Hua2026_ARC_Tacholess_RPM_Estimation.pdf": "Hua, W.; Hao, C. (arXiv, 2026) — ARC: Alignment-based RPM Estimation with Curvature-adaptive Tracking",
        "Keshavarz2025_Containerized_Edge_AI_Predictive_Maintenance.pdf": "Keshavarz, M. A.; Sharifian, S. (J. Supercomputing, 2025) — A containerized Edge AI predictive maintenance infrastructure using CWT and Transformer UNet",
        "Lazzaro2024_Edge_ML_Predictive_Maintenance.pdf": "Lazzaro, A.; D'Addona, D. M.; Merenda, M. (IEEE OJIES, 2024) — Algorithms for Predictive Maintenance in Smart Manufacturing Using Edge Machine Learning",
        "Liu2024_Multisensory_Time_Frequency_Fusion_Fault_Diagnosis.pdf": "Liu, J. et al. (J. Intell. Manuf., 2024) — A multisensory time-frequency features fusion method for rotating machinery fault diagnosis",
        "Zhang2023_Multisensor_OpenSet_Diagnostics_Rotating_Machinery.pdf": "Zhang, Y. et al. (MSSP, 2023) — Multi-sensor open-set cross-domain intelligent diagnostics for rotating machinery",
        "Christou2020_EndToEnd_IIoT_Predictive_Maintenance.pdf": "Christou, I. T. et al. (IFAC PapersOnLine, 2020) — End-to-End Industrial IoT Platform for Actionable Predictive Maintenance",
        "Ahmed2025_Industry4_Digital_Twins_Predictive_Maintenance_IoT_ML.pdf": "Ahmed, K. R. (IEEE QPAIN, 2025) — Driving Industry 4.0 with Digital Twins: Predictive Maintenance Through IoT and ML",
        "Muruganandam2025_EdgeAI_Vibration_Monitoring_Anomaly.pdf": "Muruganandam, M. et al. (TIJER, 2025) — Edge-AI Based Vibration Monitoring and Anomaly Detection Device",
        "Jardine2006_Review_Machinery_Diagnostics_Prognostics_CBM.pdf": "Jardine, A. K. S.; Lin, D.; Banjevic, D. (MSSP, 2006) — A review on machinery diagnostics and prognostics implementing condition-based maintenance",
        "Lin_Zhao2014_Review_Diagnosis_Speed_Varying_Machinery.pdf": "Lin, J.; Zhao, M. (2014) — A Review and Strategy for the Diagnosis of Speed-Varying Machinery",
        "Brandao_Costa2022_Fault_Diagnosis_Rotary_Machines_ML.pdf": "Brandão, I. M.; Costa, C. (Eletrônica de Potência, 2022) — Fault Diagnosis of Rotary Machines Using Machine Learning",
        "Shahin2023_ML_DL_Early_Failure_Detection_Manufacturing.pdf": "Shahin, M. et al. (IJAMT, 2023) — Using machine learning and deep learning algorithms for downtime minimization in manufacturing",
        "Tractian_Datasheet_SmartTrac_Ultra_Gen2.pdf": "Tractian (Datasheet v1.0) — Smart Trac Ultra Gen 2 Industrial Vibration Sensor (Benchmark)",
    }

    art_pdfs_html = ""
    if art_dir.exists():
        art_pdfs = sorted(art_dir.rglob("*.pdf"), key=lambda p: (0 if p.parent == art_dir else 1, pdf_display_titles.get(p.name, p.name).lower()))
        if art_pdfs:
            items_li = []
            for ap in art_pdfs:
                rel = ap.relative_to(pacote_dir).as_posix()
                sz_mb = ap.stat().st_size / (1024 * 1024)
                sz_lbl = f"{sz_mb:.2f} MB" if sz_mb >= 1.0 else f"{ap.stat().st_size / 1024:.0f} KB"
                kind = "Norma" if ap.parent == art_dir else "Artigo / Datasheet"
                display_title = pdf_display_titles.get(ap.name, ap.stem.replace("_", " ").replace("-", " "))
                items_li.append(
                    f'<li><a href="{html_lib.escape(rel)}" target="_blank">{html_lib.escape(display_title)}</a> '
                    f'<span style="color:#64748b;font-size:12px;">({kind} · {sz_lbl} · <code>{html_lib.escape(ap.name)}</code>)</span></li>'
                )
            art_pdfs_html = (
                '<article id="doc-pdfs-artigos" class="doc-card">'
                '<h1>Biblioteca Offline de Artigos Científicos e Normas (PDF)</h1>'
                '<p>Arquivos PDF incluídos no pacote com identificação por autor, periódico/ano e título para consulta offline:</p>'
                f'<ul>{"".join(items_li)}</ul>'
                '</article>'
            )
            nav_groups.setdefault("Artigos Científicos, Auditoria ML & Normas", []).insert(
                0, ("doc-pdfs-artigos", "PDFs dos Artigos & Normas (Offline)")
            )

    nav_html = ['<div class="nav-group"><h3>Esquemáticos (PDF / PNG)</h3><a href="#doc-esquematicos">Galeria e PDFs dos Esquemáticos</a></div>']
    for gtitle, items in nav_groups.items():
        links = "".join(f'<a href="#{anc}">{html_lib.escape(lbl)}</a>' for anc, lbl in items)
        nav_html.append(f'<div class="nav-group"><h3>{html_lib.escape(gtitle)}</h3>{links}</div>')

    schem_block = (
        '<article id="doc-esquematicos" class="doc-card">'
        '<h1>Esquemáticos Elétricos e Diagramas Atualizados</h1>'
        '<p>Esquemáticos V2 ativos (sem fusíveis no painel, sem <code>INMP441</code>, sem <code>SCT-013</code>, conector <code>GX16-5</code> e <code>SPI_MOSI = GPIO7</code>). Abra os PDFs vetoriais completos ou clique em qualquer folha abaixo:</p>'
        f'<div class="pdf-pills">{"".join(pdf_pills)}</div>'
        f'<div class="schem-grid">{"".join(png_figures)}</div>'
        '</article>'
    )

    full_html = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Amemiya TCC — Portal Offline de Documentação e Hardware</title>
<style>
  :root {{ --bg: #0f172a; --panel: #1e293b; --card: #ffffff; --text: #0f172a; --muted: #475569; --accent: #2563eb; --border: #e2e8f0; }}
  * {{ box-sizing: border-box; }}
  body {{ margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f8fafc; color: var(--text); display: flex; min-height: 100vh; }}
  aside {{ width: 310px; flex-shrink: 0; background: var(--bg); color: #f8fafc; padding: 20px 16px; position: sticky; top: 0; height: 100vh; overflow-y: auto; }}
  aside h2 {{ font-size: 16px; margin: 0 0 6px; color: #38bdf8; }}
  aside p {{ font-size: 12px; color: #94a3b8; margin: 0 0 18px; }}
  .nav-group {{ margin-bottom: 18px; }}
  .nav-group h3 {{ font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin: 0 0 6px; }}
  .nav-group a {{ display: block; color: #e2e8f0; text-decoration: none; font-size: 13px; padding: 5px 8px; border-radius: 6px; margin-bottom: 2px; line-height: 1.3; }}
  .nav-group a:hover {{ background: var(--panel); color: #ffffff; }}
  main {{ flex: 1; max-width: 1020px; padding: 28px 36px; margin: 0 auto; min-width: 0; }}
  .doc-card {{ background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 26px 30px; margin-bottom: 26px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }}
  h1 {{ font-size: 24px; margin-top: 0; border-bottom: 2px solid var(--border); padding-bottom: 8px; }}
  h2 {{ font-size: 19px; margin-top: 24px; color: #1e293b; }}
  h3 {{ font-size: 15px; margin-top: 18px; color: #334155; }}
  p, li {{ line-height: 1.6; color: #1e293b; }}
  a {{ color: var(--accent); }}
  code {{ background: #f1f5f9; padding: 2px 5px; border-radius: 4px; font-size: 0.9em; }}
  pre {{ background: #0f172a; color: #e2e8f0; padding: 14px 16px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.45; }}
  pre code {{ background: transparent; padding: 0; color: inherit; }}
  table {{ width: 100%; border-collapse: collapse; margin: 14px 0; font-size: 14px; }}
  th, td {{ border: 1px solid var(--border); padding: 8px 10px; text-align: left; vertical-align: top; }}
  th {{ background: #f1f5f9; font-weight: 600; }}
  blockquote {{ margin: 14px 0; padding: 10px 14px; background: #eff6ff; border-left: 4px solid var(--accent); border-radius: 0 6px 6px 0; }}
  .doc-card img {{ max-width: 100%; height: auto; display: block; margin: 14px auto; border-radius: 8px; border: 1px solid var(--border); }}
  .schem-grid {{ display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin: 18px 0; }}
  .schem-card {{ margin: 0; border: 1px solid var(--border); border-radius: 8px; padding: 10px; background: #f8fafc; }}
  .schem-card img {{ margin: 0 0 8px; border-radius: 4px; max-height: 190px; width: 100%; object-fit: contain; background: #fff; }}
  .schem-card figcaption {{ font-size: 12px; text-align: center; line-height: 1.35; }}
  .pdf-pills {{ display: flex; flex-wrap: wrap; gap: 10px; margin: 14px 0 4px; }}
  .pdf-pill {{ display: inline-block; padding: 7px 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; color: #1d4ed8; }}
  .pdf-pill:hover {{ background: #dbeafe; }}
  .mermaid-diagram {{ margin: 18px 0; padding: 14px; background: #ffffff; border: 1px solid var(--border); border-radius: 8px; overflow-x: auto; text-align: center; }}
  .mermaid-diagram svg {{ max-width: 100%; height: auto; }}
  /* Math typography (offline CSS without external webfont dependencies) */
  .math {{ font-family: "Cambria Math", "STIX Two Math", "Latin Modern Math", Georgia, serif; font-size: 1.05em; color: #0f172a; }}
  .math var {{ font-style: italic; }}
  .math .mtext, .math .mfunc {{ font-family: system-ui, -apple-system, Segoe UI, sans-serif; font-style: normal; font-size: 0.92em; }}
  .math .mbold {{ font-weight: 700; }}
  .math .mop-bin {{ padding: 0 0.22em; }}
  .math .mfrac {{ display: inline-flex; flex-direction: column; vertical-align: middle; text-align: center; margin: 0 0.25em; font-size: 0.92em; }}
  .math .mfrac .mnum {{ padding: 0 0.3em 0.12em; border-bottom: 1.5px solid #0f172a; }}
  .math .mfrac .mden {{ padding: 0.15em 0.3em 0; }}
  .math .msqrt {{ display: inline-flex; align-items: stretch; vertical-align: middle; }}
  .math .msqrt .mrad {{ font-size: 1.25em; line-height: 1; padding-right: 1px; }}
  .math .msqrt .msqrt-body {{ border-top: 1.5px solid #0f172a; padding: 0.1em 0.25em 0; }}
  .math .mop {{ display: inline-flex; flex-direction: column; align-items: center; vertical-align: middle; margin: 0 0.2em; line-height: 1.05; }}
  .math .mop-sym {{ font-size: 1.45em; }}
  .math .mop-sup, .math .mop-sub {{ font-size: 0.72em; }}
  .math sub, .math sup {{ font-size: 0.75em; line-height: 0; }}
  .math-display {{ display: block; text-align: center; margin: 16px 0; padding: 12px 16px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; overflow-x: auto; }}
  @media (max-width: 900px) {{ body {{ flex-direction: column; }} aside {{ width: 100%; height: auto; position: static; }} main {{ padding: 16px; }} }}
</style>
</head>
<body>
  <aside>
    <h2>Amemiya — Pacote TCC</h2>
    <p>Documentação técnica, ADRs e referências científicas em modo offline.</p>
    {"".join(nav_html)}
  </aside>
  <main>
    {schem_block}
    {art_pdfs_html}
    {"".join(body_blocks)}
  </main>
  {mermaid_fallback_script}
</body>
</html>
"""
    out_path = pacote_dir / "LEIA_NO_NAVEGADOR.html"
    out_path.write_text(full_html, encoding="utf-8")
    return out_path
