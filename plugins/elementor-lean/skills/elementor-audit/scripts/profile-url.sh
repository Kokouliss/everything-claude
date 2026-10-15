#!/usr/bin/env bash
# Profile any public page without site access: Elementor markers, theme, cache/optimizer plugin,
# fonts, tag managers, third-party hosts, hero lazy-load, add-on packs — then optional Lighthouse.
# Prints one JSON object. Usage: profile-url.sh <url> [lighthouse_out_dir]
set -uo pipefail
url=$1; out=${2:-}
ua="Mozilla/5.0 (Linux; Android 10; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36"
html=$(curl -sSL --max-time 40 -A "$ua" "$url") || { echo "{\"url\":\"$url\",\"error\":\"fetch failed\"}"; exit 0; }
host=$(sed -E 's#https?://([^/]*).*#\1#' <<<"$url" | sed 's/^www\.//')
count() { grep -o "$1" <<<"$html" | wc -l | tr -d ' '; }
ver=$(grep -o 'elementor/assets/js/frontend[^"]*ver=[0-9.]*' <<<"$html" | head -1 | sed 's/.*ver=//')
pro=$(grep -o 'elementor-pro/assets/js/frontend[^"]*ver=[0-9.]*' <<<"$html" | head -1 | sed 's/.*ver=//')
theme=$(grep -o 'wp-content/themes/[^/"]*' <<<"$html" | sort | uniq -c | sort -rn | head -1 | awk '{print $2}' | sed 's#wp-content/themes/##')
cache=""; for m in litespeed flying-press wp-rocket perfmatters w3-total-cache wp-fastest-cache nitropack autoptimize sg-optimizer breeze wp-optimize; do grep -qi "$m" <<<"$html" && cache="$cache$m,"; done
addons=""; for m in essential-addons premium-addons ultimate-elementor happy-elementor elementskit jet-elements jet-smart-filters jet-menu unlimited-elements powerpack element-pack royal-elementor the-plus-addons dynamic-content-for-elementor; do grep -qi "plugins/$m" <<<"$html" && addons="$addons$m,"; done
fontfam=$(grep -o 'family=[^&"]*' <<<"$html" | sed 's/family=//; s/:.*//' | sort -u | tr '\n' '|')
gtm=$(grep -o 'GTM-[A-Z0-9]*' <<<"$html" | sort -u | tr '\n' ',')
gtags=$(grep -o 'gtag/js?id=[A-Z0-9-]*' <<<"$html" | sort -u | wc -l | tr -d ' ')
third=$(grep -o 'https\?://[^/"'"'"' )]*' <<<"$html" | sed 's#https\?://##; s/^www\.//' | grep -v "$host" | sort | uniq -c | sort -rn | head -12 | awk '{print $2":"$1}' | tr '\n' ' ')
hero_lazy=$(grep -o '<img[^>]*>' <<<"$html" | head -3 | grep -c 'loading="lazy"' || true)
json=$(jq -n --arg url "$url" --arg ver "$ver" --arg pro "$pro" --arg theme "$theme" --arg cache "$cache" --arg addons "$addons" \
  --arg fontfam "$fontfam" --arg gtm "$gtm" --arg third "$third" \
  --argjson html_kb "$(( ${#html} / 1024 ))" --argjson containers "$(count 'e-con ')" --argjson sections "$(count 'elementor-section ')" \
  --argjson animated "$(count 'elementor-invisible')" --argjson scripts "$(count '<script[^>]*src=')" --argjson css "$(count '<link[^>]*rel=.stylesheet')" \
  --argjson gfonts "$(count 'fonts.googleapis.com/css')" --argjson gtags "$gtags" --argjson fb "$(count 'connect.facebook.net')" \
  --argjson hero_lazy "$hero_lazy" --argjson video "$(count '<video')" --argjson iframes "$(count '<iframe')" --argjson swiper "$(count 'swiper')" \
  '{url:$url, elementor:$ver, elementor_pro:$pro, theme:$theme, html_kb:$html_kb, containers:$containers, legacy_sections:$sections,
    animated_elements:$animated, scripts:$scripts, stylesheets:$css, cache_or_optimizer:$cache, elementor_addons:$addons,
    google_fonts_requests:$gfonts, font_families:$fontfam, gtm_containers:$gtm, gtag_ids:$gtags, facebook_pixel:($fb>0),
    third_party_hosts:$third, first_images_lazy:$hero_lazy, video_tags:$video, iframes:$iframes, swiper_mentions:$swiper}')
if [ -n "$out" ]; then
  lh=$(bash "$(dirname "$0")/lighthouse.sh" "$out" "$url" | tail -1)
  json=$(jq -c --argjson lh "$lh" '. + {lighthouse:$lh}' <<<"$json")
fi
echo "$json"
