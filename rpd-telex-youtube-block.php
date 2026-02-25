<?php
/**
 * Plugin Name:       RPD YouTube Video Block
 * Description:       A comprehensive YouTube video block with support for single videos, playlists, channels, and advanced caching capabilities.
 * Version:           2026.01
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       telex-youtube-block
 *
 * @package TelexYouTubeBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!function_exists("rpd_telex_log")) {
	function rpd_telex_log($msg) {
		$upload_dir = wp_upload_dir();
		$logFile = $upload_dir['basedir'] . '/' . 'rpd_telex_log.txt';
		date_default_timezone_set('Australia/Sydney');

		// Now write out to the file
		$log_handle = fopen($logFile, "a");
		if ($log_handle !== false) {
			fwrite($log_handle, date("H:i:s").": ".$msg."\r\n");
			fclose($log_handle);
		}
	}
}




/**
 * Registers the block using the metadata loaded from the `block.json` file.
 */
function rpd_telex_youtube_block_init() {
	register_block_type( __DIR__ . '/build/' );
}
add_action( 'init', 'rpd_telex_youtube_block_init' );



/**
 * Get cached YouTube data.
 *
 * @param string $cache_key The cache key.
 * @return mixed|false The cached data or false if not found.
 */
function rpd_telex_youtube_get_cached_data( $cache_key ) {

	$cached_data = get_transient( 'rpd_telex_youtube_' . md5( $cache_key ) );
	//rpd_telex_log('rpd_telex_youtube_get_cached_data: '.print_r($cached_data,true));

	return $cached_data;
}

/**
 * Set cached YouTube data.
 *
 * @param string $cache_key The cache key.
 * @param mixed  $data The data to cache.
 * @param int    $expiration Cache expiration in seconds.
 * @return bool True on success, false on failure.
 */
function rpd_telex_youtube_set_cached_data( $cache_key, $data, $expiration = 3600 ) {

	//rpd_telex_log('rpd_telex_youtube_set_cached_data: '.$cache_key.': '.print_r($data,true));

	return set_transient( 'rpd_telex_youtube_' . md5( $cache_key ), $data, $expiration );
}

/**
 * Clear YouTube cache.
 *
 * @param string $cache_key Optional. Specific cache key to clear. If empty, clears all.
 * @return bool True on success.
 */
function rpd_telex_youtube_clear_cache( $cache_key = '' ) {
	global $wpdb;

	//rpd_telex_log( "rpd_telex_youtube_clear_cache key: ".$cache_key  );
	try {
	
		if ( ! empty( $cache_key ) ) {
			return delete_transient( 'rpd_telex_youtube_' . md5( $cache_key ) );
		}
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_rpd_telex_youtube_' ) . '%'
			)
		);
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_rpd_telex_youtube_' ) . '%'
			)
		);

	} catch (Exception $ex) {
		rpd_telex_log( "rpd_telex_youtube_clear_cache error: " . $ex->getMessage() ); // Code that runs if an exception is caught
	}
	
	return true;
}

/**
 * Fetch video data from YouTube API.
 *
 * @param string $api_key YouTube API key.
 * @param string $video_id Video ID.
 * @return array|WP_Error Video data or error.
 */
function rpd_telex_youtube_fetch_video( $api_key, $video_id ) {
	try {
		$cache_key = "video_{$video_id}";
		$cached = rpd_telex_youtube_get_cached_data( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$url = add_query_arg(
			array(
				'part' => 'snippet',
				'id' => $video_id,
				'key' => $api_key,
			),
			'https://www.googleapis.com/youtube/v3/videos'
		);

		//rpd_telex_log( "rpd_telex_youtube_fetch_video url: " . $url );
		
		$response = wp_remote_get( $url, array( 'headers' => array( 'referer' => home_url() ) ) );
	
		//rpd_telex_log( "rpd_telex_youtube_fetch_video response: " . print_r($response,true) );


		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			$err = '';
			foreach($data['error']['errors'] as $error) {
				$err .= trim($error['message']).' Domain: '.trim($error['domain']).' Reason: '.trim($error['reason']);
			}
			return new WP_Error( 'api_error', __( $err, 'rpd_telex-youtube-block' ) );
		}

		
		if ( empty( $data['items'] ) ) {
			return new WP_Error( 'no_video', __( 'Video not found', 'rpd_telex-youtube-block' ) );
		}
		
		$video_data = $data['items'][0];

		//rpd_telex_log( "rpd_telex_youtube_fetch_video video_data: " . print_r($video_data,true) );

		rpd_telex_youtube_set_cached_data( $cache_key, $video_data );

	} catch (Exception $ex) {
		rpd_telex_log( "rpd_telex_youtube_fetch_video error: " . $ex->getMessage() ); // Code that runs if an exception is caught
	}
	
	return $video_data;
}

/**
 * Fetch playlist data from YouTube API.
 *
 * @param string $api_key YouTube API key.
 * @param string $playlist_id Playlist ID.
 * @param int    $max_results Maximum results to fetch.
 * @return array|WP_Error Playlist data or error.
 */
function rpd_telex_youtube_fetch_playlist( $api_key, $playlist_id, $max_results = 50 ) {
	$cache_key = "playlist_{$playlist_id}_{$max_results}";
	$cached = rpd_telex_youtube_get_cached_data( $cache_key );
	
	if ( false !== $cached ) {
		return $cached;
	}
	
	$url = add_query_arg(
		array(
			'part' => 'snippet',
			'playlistId' => $playlist_id,
			'maxResults' => $max_results,
			'key' => $api_key,
		),
		'https://www.googleapis.com/youtube/v3/playlistItems'
	);
	
	$response = wp_remote_get( $url, array( 'headers' => array( 'referer' => home_url() ) ) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( isset( $data['error'] ) ) {
		$err = '';
		foreach($data['error']['errors'] as $error) {
			$err .= trim($error['message']).' Domain: '.trim($error['domain']).' Reason: '.trim($error['reason']);
		}
		return new WP_Error( 'api_error', __( $err, 'rpd_telex-youtube-block' ) );
	}
	
	if ( empty( $data['items'] ) ) {
		return new WP_Error( 'no_playlist', __( 'Playlist not found or empty', 'telex-youtube-block' ) );
	}
	
	rpd_telex_youtube_set_cached_data( $cache_key, $data['items'] );
	
	return $data['items'];
}

/**
 * Fetch channel videos from YouTube API.
 *
 * @param string $api_key YouTube API key.
 * @param string $channel_id Channel ID.
 * @param int    $max_results Maximum results to fetch.
 * @return array|WP_Error Channel videos or error.
 */
function rpd_telex_youtube_fetch_channel( $api_key, $channel_id, $max_results = 50 ) {
	$cache_key = "channel_{$channel_id}_{$max_results}";
	$cached = rpd_telex_youtube_get_cached_data( $cache_key );
	
	if ( false !== $cached ) {
		return $cached;
	}
	
	$url = add_query_arg(
		array(
			'part' => 'snippet',
			'channelId' => $channel_id,
			'maxResults' => $max_results,
			'order' => 'date',
			'type' => 'video',
			'key' => $api_key,
		),
		'https://www.googleapis.com/youtube/v3/search'
	);
	
	$response = wp_remote_get( $url, array( 'headers' => array( 'referer' => home_url() ) ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	//rpd_telex_log( "rpd_telex_youtube_fetch_channel data: " . print_r($data,true) );
	

	if ( isset( $data['error'] ) ) {
		$err = '';
		foreach($data['error']['errors'] as $error) {
			$err .= trim($error['message']).' Domain: '.trim($error['domain']).' Reason: '.trim($error['reason']);
		}
		return new WP_Error( 'api_error', __( $err, 'rpd_telex-youtube-block' ) );
	}
	
	if ( empty( $data['items'] ) ) {
		return new WP_Error( 'no_channel', __( 'Channel not found or has no videos', 'telex-youtube-block' ) );
	}
	
	rpd_telex_youtube_set_cached_data( $cache_key, $data['items'] );
	
	return $data['items'];
}

/**
 * Download and cache thumbnail.
 *
 * @param string $thumbnail_url Thumbnail URL.
 * @param string $video_id Video ID.
 * @return string Local thumbnail URL.
 */
function rpd_telex_youtube_cache_thumbnail( $thumbnail_url, $video_id ) {
	$upload_dir = wp_upload_dir();
	$cache_dir = $upload_dir['basedir'] . '/rpd-telex-youtube-cache/thumbnails';
	
	if ( ! file_exists( $cache_dir ) ) {
		wp_mkdir_p( $cache_dir );
	}
	
	$filename = $video_id . '_' . md5( $thumbnail_url ) . '.jpg';
	$filepath = $cache_dir . '/' . $filename;
	$file_url = $upload_dir['baseurl'] . '/rpd-telex-youtube-cache/thumbnails/' . $filename;
	
	if ( file_exists( $filepath ) ) {
		return $file_url;
	}
	
	$response = wp_remote_get( $thumbnail_url );
	
	if ( is_wp_error( $response ) ) {
		return $thumbnail_url;
	}
	
	$image_data = wp_remote_retrieve_body( $response );
	
	global $wp_filesystem;
	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}
	
	$wp_filesystem->put_contents( $filepath, $image_data, FS_CHMOD_FILE );
	
	return $file_url;
}


function rpd_telex_youtube_thumbnail_url( $videoId, $videoThumbnail, $custom_thumb_filename = '' ) {

	// Check if there is a custom thumbnail provided.
	$upload_dir = wp_upload_dir();
	$custom_thumbnail_dir = $upload_dir['basedir'] . '/rpd-telex-youtube-custom-thumbnails/';
	$thumbnail_url = '';
	if ( $custom_thumb_filename == '' ) {
		$custom_thumb_filename = $videoId.'.jpg';
	}
	//rpd_telex_log('looking for: '.$custom_thumbnail_dir.$custom_thumb_filename);
	if ( file_exists( $custom_thumbnail_dir.$custom_thumb_filename ) ) {
		$thumbnail_url = $upload_dir['baseurl'] . '/rpd-telex-youtube-custom-thumbnails/' . $custom_thumb_filename;
	}

	// Otherwise check the cached thumbnail
	if ( $thumbnail_url == '' ) {
		$thumbnail_url = rpd_telex_youtube_cache_thumbnail( $videoThumbnail, $videoId );
	}

	return $thumbnail_url;
}



function rpd_test_handler($request) {
	//rpd_telex_log( "rpd_test_handler: " . print_r($request,true) );
    return [
        'success' => true,
        'data' => 'Hello from REST',
    ];
}


function rdp_delete_folder_contents(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);

	//rpd_telex_log( "items: " . print_r($items,true) );

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rdp_delete_folder_contents($path);
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}

function rpd_telex_youtube_ajax_clear_cache($request) {
	//check_ajax_referer( 'rpd_telex_youtube_nonce', 'nonce' );

	//rpd_telex_log( "rpd_telex_youtube_ajax_clear_cache: " . print_r($request,true) );

	rpd_telex_youtube_clear_cache();
	
	$upload_dir = wp_upload_dir();
	$cache_dir = $upload_dir['basedir'] . '/rpd-telex-youtube-cache';

	rdp_delete_folder_contents($cache_dir);

	return [
        'success' => true,
        'data' => 'Cache cleared successfully',
    ];
}


add_action('rest_api_init', function () {
    register_rest_route('block-rpd-telex-youtube-block', '/ajax_rpd_test', [
        'methods'  => 'POST',
        'callback' => 'rpd_test_handler',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);

	register_rest_route('block-rpd-telex-youtube-block', '/ajax_clear_cache', [
        'methods'  => 'POST',
        'callback' => 'rpd_telex_youtube_ajax_clear_cache',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ]);
});









/**
 * Schedule automatic cache refresh.
 */
function rpd_telex_youtube_schedule_cache_refresh() {
	if ( ! wp_next_scheduled( 'rpd_telex_youtube_auto_refresh' ) ) {
		wp_schedule_event( time(), 'hourly', 'rpd_telex_youtube_auto_refresh' );
	}
}
add_action( 'init', 'rpd_telex_youtube_schedule_cache_refresh' );

/**
 * Auto refresh cache based on settings.
 */
function rpd_telex_youtube_auto_refresh_cache() {
	// This hook can be used to implement automatic cache refresh
	// based on user settings stored in block attributes
	do_action( 'rpd_telex_youtube_cache_refresh' );
}
add_action( 'rpd_telex_youtube_auto_refresh', 'rpd_telex_youtube_auto_refresh_cache' );

/**
 * Deactivation cleanup.
 */
function rpd_telex_youtube_deactivate() {
	wp_clear_scheduled_hook( 'rpd_telex_youtube_auto_refresh' );
}
register_deactivation_hook( __FILE__, 'rpd_telex_youtube_deactivate' );
