import json
import re

with open(r'C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c\.system_generated\steps\28648\content.md', 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

# Find all tool cards in spectral analysis
tools = re.findall(r'<p class="w-full font-semibold">([^<]+)</p>\s*</article>\s*<p class="text-slate-500 text-body-sm">([^<]+)</p>', text)
print("--- SPECTRAL TOOLS ---")
for name, desc in tools:
    print(f"* {name}: {desc}")

# Find all sections and features
print("\n--- FAQS & DESCRIPTIONS ---")
faqs = re.findall(r'\"@type\":\"Question\",\"name\":\"([^\"]+)\",\"acceptedAnswer\":\{\"@type\":\"Answer\",\"text\":\"([^\"]+)\"', text)
for q, a in faqs:
    print(f"Q: {q}\nA: {a}\n")

# Find features lists
features = re.findall(r'\"feature\":\s*\"([^\"]+)\"', text)
print("Features:", set(features))
