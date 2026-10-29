# ONIKI Living — Visual and map notes

The architectural hero is a generated marketing illustration, not an image of an actual property being offered. Website captions explicitly identify it as an illustration.

Final asset: public/oniki-living-hero.jpg (1536 × 1024, JPEG export from original generated PNG).
Generation mode: built-in ImageGen.
Final prompt:
> Create a high-end photorealistic architectural editorial photograph for ONIKI Japanese real estate website hero. Wide landscape composition 3:2. Contemporary Japanese residential neighborhood in Osaka with elegant modern white-and-warm-wood low-rise apartments, lush small gardens, clean quiet street, balcony details, warm interior lights at blue hour, rich deep cobalt evening sky. Aspirational but realistic Japanese architecture, cinematic natural light, premium property magazine photography, crisp details, no people, no logos, no text, no watermarks, no advertising signs. This is a clearly illustrative marketing image, not a specific listed property. Compose buildings prominently at center and right, left foreground subdued for adjacent website typography. Output high resolution landscape image.

Both company logos are original files supplied by the user and are unchanged.

## Map implementation
Leaflet 1.9.4 is bundled in public/vendor/leaflet with its original license.
Sources: https://leafletjs.com/examples/quick-start/ and https://operations.osmfoundation.org/policies/tiles/

Map tiles load directly from OpenStreetMap over HTTPS, with visible attribution and ordinary browser caching. There is no bulk download, prefetch or offline map feature. The tile provider URL can be changed through ONIKI_MAP_TILES; preserve the appropriate provider attribution if replacing the provider.
Only published property records with a coordinate pair become markers. Exact overlapping points share a popup. No sample property remains in the local database after QA; automated tests use in-memory SQLite.

The search-method chooser and full-screen map/condition-panel organization were informed by the user-provided reference https://ongchuxomtro.com/ . No competitor logos, photos, listing inventory or proprietary source code were copied.
Nearby markers use leaflet.markercluster 1.5.3, bundled with its MIT license in public/vendor/leaflet/markercluster-LICENSE.

## Calm basemap and viewed state
The default vector background is ONIKI Live, derived from https://tiles.openfreemap.org/styles/bright . It uses brighter blue water, green parks and clear roads. Individual properties use original code-drawn pink house pins; nearby properties cluster into blue circles. Viewed pins/cards only fade, with no viewed labels or legend. Attribution remains visible: OpenFreeMap, OpenMapTiles and OpenStreetMap.
Integration follows https://openfreemap.org/quick_start/ and https://github.com/maplibre/maplibre-gl-leaflet . Browser renderer: MapLibre GL JS 5.7.1 from unpkg; the Leaflet bridge is pinned to 0.0.22 and bundled with its ISC license. A muted raster layer is the compatibility fallback.
Viewed property IDs persist locally in the user's browser, capped at 800. Single-property popup opening and detail-page navigation mark a property as viewed; grouped markers only fade when all contained properties have been viewed. The state is restored on reload/back navigation and propagated to other tabs through the browser storage event. No browsing history is uploaded to the server.

The bundled bridge includes an ONIKI compatibility patch: camera synchronization uses MapLibre's public jumpTo API instead of mutating transform internals. This keeps basemap tiles synchronized with Leaflet during dragging on MapLibre 5. Parks use mint green, water aqua, and roads white/gray following the user's palette reference.

Property entry accepts a Japanese street address and stores it separately from the area label. An authenticated, CSRF-protected manual search proxies GSI AddressSearch, caches results for one day and allows 10 searches per user per minute. Provider URL is configurable with ONIKI_ADDRESS_SEARCH_URL. Source: https://www.gsi.go.jp/SERVICE/keiyaku/oc_20260428_02.pdf . Staff choose a candidate and can correct the pin manually; changing address clears stale coordinates. Coordinates stay hidden in the form. GSI results may resolve to a neighborhood rather than a building.
