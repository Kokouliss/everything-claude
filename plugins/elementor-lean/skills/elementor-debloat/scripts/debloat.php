// Elementor Lean — reversible debloat actions. Run through Novamira `novamira/execute-php`.
// Edit $actions, then run. Before-values go to option `elementor_lean_undo`; `undo` restores them.
$actions          = [ 'expired_transients', 'fa4_shim_off' ]; // see SKILL.md for the full list
$revision_days    = 90;    // only for revisions_prune
$revision_confirm = false; // must be true to delete revisions (irreversible)

global $wpdb;
$undo       = get_option( 'elementor_lean_undo', [] );
$report     = [];
$set_option = function ( $name, $value ) use ( &$undo ) {
	if ( ! array_key_exists( $name, $undo ) ) {
		$undo[ $name ] = get_option( $name, '__unset__' );
	}
	update_option( $name, $value );
};
$elementor_docs = function () use ( $wpdb ) {
	return $wpdb->get_col(
		"SELECT p.ID FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id
		 WHERE m.meta_key = '_elementor_data' AND p.post_type <> 'revision' AND p.post_status IN ('publish','private')"
	);
};

foreach ( $actions as $action ) {
	switch ( $action ) {
		case 'expired_transients':
			$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" );
			delete_expired_transients( true );
			$after             = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" );
			$report[ $action ] = "transients $before -> $after";
			break;

		case 'fa4_shim_off':
			$fa4 = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE '%\"fa fa-%'" );
			if ( $fa4 ) {
				$report[ $action ] = "skipped: $fa4 documents still use Font Awesome 4 icons";
				break;
			}
			$set_option( 'elementor_load_fa4_shim', '' );
			$report[ $action ] = 'fa4 shim off';
			break;

		case 'optimized_markup_on':
			$set_option( 'elementor_experiment-e_optimized_markup', 'active' );
			$report[ $action ] = 'optimized markup active — check pages with custom CSS';
			break;

		case 'element_cache_on':
			$set_option( 'elementor_element_cache_ttl', '24' );
			$report[ $action ] = 'element cache 24h — check dynamic widgets';
			break;

		case 'video_lazy':
			$changed = 0;
			foreach ( $elementor_docs() as $id ) {
				$raw  = get_post_meta( $id, '_elementor_data', true );
				$data = json_decode( $raw, true );
				if ( ! is_array( $data ) ) {
					continue;
				}
				$hit  = 0;
				$walk = function ( &$nodes ) use ( &$walk, &$hit ) {
					foreach ( $nodes as &$n ) {
						if ( ( $n['widgetType'] ?? '' ) === 'video' && ( $n['settings']['lazy_load'] ?? '' ) !== 'yes' ) {
							$n['settings']['lazy_load'] = 'yes';
							$hit++;
						}
						if ( ! empty( $n['elements'] ) ) {
							$walk( $n['elements'] );
						}
					}
				};
				$walk( $data );
				if ( $hit ) {
					if ( ! get_post_meta( $id, '_elementor_data_lean_bak', true ) ) {
						update_post_meta( $id, '_elementor_data_lean_bak', wp_slash( $raw ) );
					}
					update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
					$changed += $hit;
				}
			}
			$report[ $action ] = "lazy-load set on $changed video widgets";
			break;

		case 'revisions_prune':
			if ( ! $revision_confirm ) {
				$report[ $action ] = 'skipped: set $revision_confirm = true (irreversible)';
				break;
			}
			$ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_modified < %s",
				gmdate( 'Y-m-d H:i:s', time() - $revision_days * DAY_IN_SECONDS )
			) );
			foreach ( $ids as $id ) {
				wp_delete_post_revision( (int) $id );
			}
			$report[ $action ] = count( $ids ) . " revisions older than $revision_days days deleted";
			break;

		case 'undo':
			foreach ( $undo as $name => $value ) {
				'__unset__' === $value ? delete_option( $name ) : update_option( $name, $value );
			}
			$restored = 0;
			foreach ( $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data_lean_bak'" ) as $id ) {
				update_post_meta( $id, '_elementor_data', wp_slash( get_post_meta( $id, '_elementor_data_lean_bak', true ) ) );
				delete_post_meta( $id, '_elementor_data_lean_bak' );
				$restored++;
			}
			$undo              = [];
			$report[ $action ] = "options restored, $restored page backups restored";
			break;

		default:
			$report[ $action ] = 'unknown action';
	}
}

update_option( 'elementor_lean_undo', $undo, false );
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
	$report['cache'] = 'Elementor CSS cache cleared';
}
return $report;
