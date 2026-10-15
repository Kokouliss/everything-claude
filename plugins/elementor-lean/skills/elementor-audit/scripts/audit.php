// Elementor Lean — read-only site audit. Paste into Novamira `novamira/execute-php` as `code`.
// Changes nothing. Returns one JSON-able array.
global $wpdb;
$out = [];

$out['site'] = [
	'url'             => home_url( '/' ),
	'wp'              => get_bloginfo( 'version' ),
	'php'             => PHP_VERSION,
	'theme'           => get_stylesheet(),
	'parent_theme'    => get_template(),
	'revisions_limit' => defined( 'WP_POST_REVISIONS' ) ? WP_POST_REVISIONS : 'unlimited',
];

// --- Elementor core info + features (experiments) ---
$out['elementor'] = [
	'version' => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null,
	'pro'     => defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : null,
];
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->experiments ) ) {
	$exp   = \Elementor\Plugin::$instance->experiments;
	$feats = [];
	foreach ( $exp->get_features() as $name => $f ) {
		$feats[ $name ] = ( $exp->is_feature_active( $name ) ? 'on' : 'off' ) . ( ( $f['state'] ?? 'default' ) === 'default' ? '' : ' (set)' );
	}
	$out['elementor']['features'] = $feats;
}
foreach ( [ 'elementor_css_print_method', 'elementor_font_display', 'elementor_google_font', 'elementor_load_fa4_shim', 'elementor_element_cache_ttl', 'elementor_disabled_elements', 'elementor_active_kit', 'elementor_unfiltered_files_upload' ] as $opt ) {
	$v = get_option( $opt, '__unset__' );
	$out['elementor']['settings'][ $opt ] = is_array( $v ) ? count( $v ) . ' items' : $v;
}

// --- Widget / structure usage across every Elementor document (one row at a time to stay within memory) ---
$ids = $wpdb->get_results(
	"SELECT p.ID, p.post_type, LENGTH(m.meta_value) AS len FROM {$wpdb->postmeta} m
	 JOIN {$wpdb->posts} p ON p.ID = m.post_id
	 WHERE m.meta_key = '_elementor_data' AND p.post_type <> 'revision' AND p.post_status IN ('publish','private')
	 LIMIT 3000"
);
$widgets = $eltypes = $fonts = [];
$flags   = [ 'entrance_animations' => 0, 'bg_video' => 0, 'bg_slideshow' => 0, 'sticky' => 0, 'motion_fx' => 0, 'custom_css' => 0, 'video_not_lazy' => 0, 'fa4_icons' => 0, 'html_with_script' => 0 ];
$max_depth = 0;
$walk = function ( $nodes, $d ) use ( &$walk, &$widgets, &$eltypes, &$fonts, &$flags, &$max_depth ) {
	foreach ( (array) $nodes as $n ) {
		if ( ! is_array( $n ) ) {
			continue;
		}
		$t             = $n['elType'] ?? '?';
		$eltypes[ $t ] = ( $eltypes[ $t ] ?? 0 ) + 1;
		if ( 'widget' === $t && isset( $n['widgetType'] ) ) {
			$widgets[ $n['widgetType'] ] = ( $widgets[ $n['widgetType'] ] ?? 0 ) + 1;
		}
		$max_depth = max( $max_depth, $d );
		$s         = isset( $n['settings'] ) && is_array( $n['settings'] ) ? $n['settings'] : [];
		foreach ( $s as $k => $v ) {
			if ( is_string( $v ) && '' !== $v && substr( $k, -11 ) === 'font_family' ) {
				$fonts[ $v ] = ( $fonts[ $v ] ?? 0 ) + 1;
			}
		}
		if ( ( ! empty( $s['_animation'] ) && 'none' !== $s['_animation'] ) || ( ! empty( $s['animation'] ) && 'none' !== $s['animation'] ) ) {
			$flags['entrance_animations']++;
		}
		if ( ! empty( $s['background_video_link'] ) ) {
			$flags['bg_video']++;
		}
		if ( ( $s['background_background'] ?? '' ) === 'slideshow' ) {
			$flags['bg_slideshow']++;
		}
		if ( ! empty( $s['sticky'] ) ) {
			$flags['sticky']++;
		}
		if ( ! empty( $s['motion_fx_motion_fx_scrolling'] ) || ! empty( $s['motion_fx_motion_fx_mouse'] ) ) {
			$flags['motion_fx']++;
		}
		if ( ! empty( $s['custom_css'] ) ) {
			$flags['custom_css']++;
		}
		if ( ( $n['widgetType'] ?? '' ) === 'video' && ( $s['lazy_load'] ?? '' ) !== 'yes' ) {
			$flags['video_not_lazy']++;
		}
		if ( ( $n['widgetType'] ?? '' ) === 'html' && false !== stripos( $s['html'] ?? '', '<script' ) ) {
			$flags['html_with_script']++;
		}
		if ( 0 === strpos( $s['selected_icon']['value'] ?? '', 'fa fa-' ) ) {
			$flags['fa4_icons']++;
		}
		if ( ! empty( $n['elements'] ) ) {
			$walk( $n['elements'], $d + 1 );
		}
	}
};
$by_type = [];
$total   = 0;
foreach ( $ids as $r ) {
	$by_type[ $r->post_type ] = ( $by_type[ $r->post_type ] ?? 0 ) + 1;
	$total                   += (int) $r->len;
	$data                     = json_decode( get_post_meta( $r->ID, '_elementor_data', true ), true );
	if ( is_array( $data ) ) {
		$walk( $data, 1 );
	}
}
arsort( $widgets );
arsort( $fonts );
$out['usage'] = [
	'documents'         => count( $ids ),
	'documents_by_type' => $by_type,
	'data_kb'           => round( $total / 1024 ),
	'element_types'     => $eltypes,
	'max_nesting'       => $max_depth,
	'widgets'           => $widgets,
	'fonts_in_widgets'  => $fonts,
	'flags'             => $flags,
];

// --- Kit (global) fonts ---
$kit = (int) get_option( 'elementor_active_kit' );
if ( $kit ) {
	$ks  = get_post_meta( $kit, '_elementor_page_settings', true );
	$kf  = [];
	$fam = function ( $a ) use ( &$fam, &$kf ) {
		foreach ( (array) $a as $k => $v ) {
			if ( is_array( $v ) ) {
				$fam( $v );
			} elseif ( is_string( $v ) && '' !== $v && is_string( $k ) && substr( $k, -11 ) === 'font_family' ) {
				$kf[ $v ] = true;
			}
		}
	};
	$fam( $ks );
	$out['kit_fonts'] = array_keys( $kf );
}

// --- Plugins, flagged by role ---
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all    = get_plugins();
$active = [];
$roles  = [];
$known  = [
	'cache'           => '/litespeed|wp-rocket|w3-total|super-cache|wp-fastest|flying-press|nitropack|swift-performance|breeze|sg-cachepress|hummingbird/i',
	'optimizer'       => '/autoptimize|perfmatters|asset-clean|wp-optimize|clearfy|async-javascript|debloat/i',
	'image'           => '/smush|tinypng|tiny-compress|imagify|shortpixel|ewww|webp|converter-for-media|optimole|robin-image/i',
	'elementor_addon' => '/essential-addons|premium-addons|ultimate-elementor|ultimate-addons|happy-elementor|elementskit|jet[a-z-]*|unlimited-elements|powerpack|element-pack|exclusive-addons|royal-elementor|sky-elementor|qi-addons|the-plus|livemesh/i',
	'popup'           => '/popup|optin|convertpro|hustle/i',
	'icons'           => '/font-awesome|fontawesome|icon/i',
	'slider'          => '/slider|revslider|smart-slider|layerslider|swiper/i',
	'security'        => '/wordfence|sucuri|ithemes|solid-security|all-in-one-wp-security/i',
];
foreach ( (array) get_option( 'active_plugins', [] ) as $file ) {
	$name     = $all[ $file ]['Name'] ?? $file;
	$active[] = $name . ' ' . ( $all[ $file ]['Version'] ?? '' );
	foreach ( $known as $role => $re ) {
		if ( 'elementor_addon' === $role && preg_match( '#^elementor(-pro)?/#', $file ) ) {
			continue;
		}
		if ( preg_match( $re, $file ) ) {
			$roles[ $role ][] = $name;
		}
	}
}
$out['plugins'] = [ 'active_count' => count( $active ), 'by_role' => $roles, 'active' => $active ];

// --- Database weight ---
$autoload_vals = function_exists( 'wp_autoload_values_to_autoload' ) ? wp_autoload_values_to_autoload() : [ 'yes' ];
$in            = "'" . implode( "','", array_map( 'esc_sql', $autoload_vals ) ) . "'";
$out['db']     = [
	'revisions'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" ),
	'autoload_kb'       => round( (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ($in)" ) / 1024 ),
	'transients'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" ),
	'elementor_meta_kb' => round( (int) $wpdb->get_var( "SELECT SUM(LENGTH(meta_value)) FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_elementor%'" ) / 1024 ),
];

return $out;
