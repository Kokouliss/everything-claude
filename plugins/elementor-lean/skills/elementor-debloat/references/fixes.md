# Finding → fix → risk

Ordered by typical PageSpeed gain on mobile. "Auto" = `scripts/debloat.php` can do it.

| Finding | Fix | Risk | Auto |
|---|---|---|---|
| Hero image has `loading="lazy"` | Widget → Image → Lazy Load: No. If the theme adds it, filter `wp_lazy_loading_enabled` for the first image | none | no |
| Hero is a slideshow, background video, or 3D/WebGL scene | Poster image above the fold; load the heavy thing below the fold or on click | design decision | no |
| GTM loads several gtag/AW tags, plus a separate pixel plugin | One GTM container; move the pixel into GTM; delay non-essential tags with Perfmatters / FlyingPress / LiteSpeed "delay JS" | tracking gaps if a tag is dropped — confirm with the client's marketer | no |
| More than 2 font families, or Google Fonts with 10+ weights | Keep 2 families, 2–3 weights; set exact weights in the kit; prefer self-hosted `.woff2` (Elementor Pro Custom Fonts) | visual; look at headings after | no |
| Custom font served as `.woff` / `.ttf` | Convert to `.woff2` (`woff2_compress`), re-upload in Custom Fonts | none | no |
| `elementor_load_fa4_shim` = yes | Turn off if no `fa fa-` icons are used | icons vanish if FA4 is still used — the script checks first | yes |
| `e_optimized_markup` off | Turn on | custom CSS targeting old wrappers can break — screenshot pages with custom CSS | yes |
| `elementor_element_cache_ttl` = disable | Leave it. Element Caching is experimental and shortens uncached renders only; it must stay Inactive on widgets with dynamic tags, shortcodes, cart, booking or user data | stale prices/availability, expired nonces in forms and filters | no — owner OK on pure brochure sites only |
| Render-blocking CSS from a plugin not used on the page (jQuery UI, booking calendar, WooCommerce on brochure pages) | Unload per page with Perfmatters / FlyingPress / LiteSpeed asset manager | the page may need it in a modal — click through the page | no |
| Entrance animations / motion effects above the fold | Remove from the first screen only | design decision | no |
| Many video widgets without lazy-load | Set lazy-load on all video widgets | shows a thumbnail instead of the player until clicked | yes |
| HTML widgets with inline `<script>` | Move the script to the theme or a snippets plugin with `defer` and page conditions | may break if load order matters | no |
| Two image optimisers / two popup tools / Font Awesome plugin next to Elementor | Keep one of each; deactivate the other | user's call | no |
| Elementor add-on packs (Premium Addons, Essential Addons…) where only 1–2 widgets are used | Disable unused widgets in the add-on's settings; replace the rest with core widgets, then deactivate | page-by-page check | no |
| Revisions > 200, Elementor meta > 20 MB | Prune revisions older than 90 days; set `WP_POST_REVISIONS` to 10 in wp-config | irreversible | yes (with confirm) |
| Transients in the thousands | Delete expired | none | yes |
| Autoload > 800 KB | Find the biggest autoloaded options (`SELECT option_name, LENGTH(option_value) … ORDER BY 2 DESC`) and set `autoload = no` on logs/caches of deactivated plugins | low | no |
| No page cache | Install one: LiteSpeed Cache on LiteSpeed hosts, otherwise FlyingPress or WP Rocket | none | no |
