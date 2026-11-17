import { chromium } from '@playwright/test';
import fs from 'node:fs';

async function launchBrowser() {
  for (const opts of [
    { headless: true },
    { channel: 'msedge', headless: true },
    { channel: 'chrome', headless: true },
  ]) {
    try {
      return await chromium.launch(opts);
    } catch {
      // Try next installed browser channel
    }
  }
  throw new Error('No Chromium/Edge/Chrome browser available for Playwright Mermaid rendering.');
}

async function main() {
  const mermaidJsPath = process.argv[2];
  if (!mermaidJsPath || !fs.existsSync(mermaidJsPath)) {
    throw new Error(`mermaid.min.js not found: ${mermaidJsPath}`);
  }

  const rawInput = fs.readFileSync(0, 'utf-8');
  const items = JSON.parse(rawInput);
  if (!Array.isArray(items) || items.length === 0) {
    process.stdout.write('[]');
    return;
  }

  const browser = await launchBrowser();
  try {
    const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
    await page.setContent('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><div id="host"></div></body></html>');
    await page.addScriptTag({ path: mermaidJsPath });

    const rendered = await page.evaluate(async (diagrams) => {
      window.mermaid.initialize({
        startOnLoad: false,
        theme: 'default',
        securityLevel: 'loose',
        flowchart: { useMaxWidth: true, htmlLabels: true, curve: 'basis' },
      });
      const out = [];
      for (const item of diagrams) {
        try {
          const res = await window.mermaid.render(item.id, item.code);
          out.push({ id: item.id, svg: res.svg, error: null });
        } catch (err) {
          out.push({ id: item.id, svg: null, error: String(err) });
        }
      }
      return out;
    }, items);

    process.stdout.write(JSON.stringify(rendered));
  } finally {
    await browser.close();
  }
}

main().catch((err) => {
  process.stderr.write(String(err?.stack || err) + '\n');
  process.exit(1);
});
