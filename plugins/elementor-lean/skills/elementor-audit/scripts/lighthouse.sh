#!/usr/bin/env bash
# Mobile Lighthouse performance run for one or more URLs. Prints one JSON line per URL.
# Usage: lighthouse.sh <out_dir> <url> [url...]
# Needs: node, `npm i lighthouse` in the cwd (or global), Chromium at $CHROME_PATH.
set -euo pipefail
out=$1; shift
mkdir -p "$out"
export CHROME_PATH=${CHROME_PATH:-/opt/pw-browsers/chromium}
for url in "$@"; do
	name=$(echo "$url" | sed -E 's#https?://##; s#[^A-Za-z0-9]+#_#g; s#_+$##')
	npx lighthouse "$url" --only-categories=performance --form-factor=mobile \
		--chrome-flags="--headless=new --no-sandbox" --output=json \
		--output-path="$out/$name.json" --quiet >/dev/null 2>&1 || { echo "{\"url\":\"$url\",\"error\":\"lighthouse failed\"}"; continue; }
	# Lighthouse's lab TBT is unreliable on slow shared CPUs (benchmarkIndex < 1000); keep it for reference only.
	jq -c --arg url "$url" '{url:$url, score:(.categories.performance.score*100|round),
		lcp_s:(.audits["largest-contentful-paint"].numericValue/1000|.*10|round/10),
		fcp_s:(.audits["first-contentful-paint"].numericValue/1000|.*10|round/10),
		cls:(.audits["cumulative-layout-shift"].numericValue|.*1000|round/1000),
		tbt_ms:(.audits["total-blocking-time"].numericValue|round),
		kb:(.audits["total-byte-weight"].numericValue/1024|round),
		requests:(.audits["network-requests"].details.items|length),
		dom:.audits["dom-size"].numericValue,
		unused_js_kb:((.audits["unused-javascript"].details.overallSavingsBytes//0)/1024|round),
		unused_css_kb:((.audits["unused-css-rules"].details.overallSavingsBytes//0)/1024|round),
		img_kb:((.audits["uses-optimized-images"].details.overallSavingsBytes//0)/1024|round),
		third_party_kb:(([.audits["third-party-summary"].details.items[]?.transferSize]|add//0)/1024|round),
		fonts:([.audits["network-requests"].details.items[]|select(.resourceType=="Font")]|length),
		lcp_lazy:((.audits["largest-contentful-paint-element"].details.items[0].items[0].node.snippet//"")|test("loading=\"lazy\"")),
		benchmark:.environment.benchmarkIndex}' "$out/$name.json"
done
