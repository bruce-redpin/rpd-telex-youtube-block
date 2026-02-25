<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api_key = isset( $attributes['apiKey'] ) ? $attributes['apiKey'] : '';
$source_type = isset( $attributes['sourceType'] ) ? $attributes['sourceType'] : 'single';
$show_title = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
$show_description = isset( $attributes['showDescription'] ) ? $attributes['showDescription'] : true;
$show_thumbnail = isset( $attributes['showThumbnail'] ) ? $attributes['showThumbnail'] : true;
$show_button = isset( $attributes['showButton'] ) ? $attributes['showButton'] : true;
$button_label = isset( $attributes['buttonLabel'] ) ? $attributes['buttonLabel'] : 'Watch Now';
$custom_template = isset( $attributes['customTemplate'] ) ? $attributes['customTemplate'] : '';
$max_results = isset( $attributes['maxResults'] ) ? $attributes['maxResults'] : 10;
$cache_interval = isset( $attributes['cacheRefreshInterval'] ) ? $attributes['cacheRefreshInterval'] : 60;

$display_mode = isset( $attributes['displayMode'] ) ? $attributes['displayMode'] : 'grid';
$template_file = isset( $attributes['templateFile'] ) ? $attributes['templateFile'] : 'rpdyt_template_std.php';

$custom_thumb_filename = isset( $attributes['customThumbnail'] ) ? $attributes['customThumbnail'] : '';

$debug_mode = isset( $attributes['debugMode'] ) ? $attributes['debugMode'] : false;


// Get the plugin version number
$plugin_data = get_plugin_data( __FILE__ );
$plugin_version = $plugin_data['Version'];

if ($display_mode == 'mainstage') {
	wp_register_script( 'rpdyt_mainstage_controller', plugins_url( '/rpdyt-mainstage-controller.js',__FILE__ ), null, '0', true );
	wp_enqueue_script( 'rpdyt_mainstage_controller' );
	// Default template
	if ( $template_file == '' ) {
		$template_file = 'rpdyt_template_mainstage.php';
	}
}

if ( empty( $api_key ) ) {
	echo '<div ' . get_block_wrapper_attributes() . '>';
	echo '<div class="rpd-telex-youtube-error">' . esc_html__( 'Please configure your YouTube API key in the block settings.', 'rpd-telex-youtube-block' ) . '</div>';
	echo '</div>';
	return;
}

$videos = array();
$cache_expiration = $cache_interval > 0 ? $cache_interval * 60 : 3600;

$wp_error_str = '';

switch ( $source_type ) {
	case 'single':
		$video_id = isset( $attributes['videoId'] ) ? $attributes['videoId'] : '';
		if ( ! empty( $video_id ) ) {
			$video_data = rpd_telex_youtube_fetch_video( $api_key, $video_id );

			if ( ! is_wp_error( $video_data ) ) {
				$videos[] = array(
					'id' => $video_id,
					'title' => $video_data['snippet']['title'],
					'description' => $video_data['snippet']['description'],
					'thumbnail' => $video_data['snippet']['thumbnails']['high']['url'],

				);
			} else {
				$wp_error_str = $video_data->get_error_message();
			}
		}
		break;

	case 'multiple':
		$video_ids = isset( $attributes['videoIds'] ) ? $attributes['videoIds'] : '';
		if ( ! empty( $video_ids ) ) {
			$ids = array_map( 'trim', explode( ',', $video_ids ) );
			foreach ( $ids as $vid ) {
				if ( empty( $vid ) ) {
					continue;
				}
				$video_data = rpd_telex_youtube_fetch_video( $api_key, $vid );
				if ( ! is_wp_error( $video_data ) ) {
					$videos[] = array(
						'id' => $vid,
						'title' => $video_data['snippet']['title'],
						'description' => $video_data['snippet']['description'],
						'thumbnail' => $video_data['snippet']['thumbnails']['high']['url'],
					);
				} else {
					$wp_error_str = $video_data->get_error_message();
				}
			}
		}
		break;

	case 'playlist':
		$playlist_id = isset( $attributes['playlistId'] ) ? $attributes['playlistId'] : '';
		if ( ! empty( $playlist_id ) ) {
			$playlist_items = rpd_telex_youtube_fetch_playlist( $api_key, $playlist_id, $max_results );
			if ( ! is_wp_error( $playlist_items ) ) {
				foreach ( $playlist_items as $item ) {
					$videos[] = array(
						'id' => $item['snippet']['resourceId']['videoId'],
						'title' => $item['snippet']['title'],
						'description' => $item['snippet']['description'],
						'thumbnail' => $item['snippet']['thumbnails']['high']['url'],
					);
				}
			} else {
				$wp_error_str = $playlist_items->get_error_message();
			}
		}
		break;

	case 'channel':
		$channel_id = isset( $attributes['channelId'] ) ? $attributes['channelId'] : '';
		if ( ! empty( $channel_id ) ) {
			$channel_items = rpd_telex_youtube_fetch_channel( $api_key, $channel_id, $max_results );
			if ( ! is_wp_error( $channel_items ) ) {
				foreach ( $channel_items as $item ) {
					$videos[] = array(
						'id' => $item['id']['videoId'],
						'title' => $item['snippet']['title'],
						'description' => $item['snippet']['description'],
						'thumbnail' => $item['snippet']['thumbnails']['high']['url'],
					);
				}
			} else {
				$wp_error_str = $channel_items->get_error_message();
			}
		}
		break;
}


if ( empty( $videos ) ) {
	if ( !$wp_error_str ) {
		$wp_error_str = 'No videos found. Please check your settings.';
	}
	echo '<div ' . get_block_wrapper_attributes() . '>';
	echo '<div class="rpd-telex-youtube-error">' . esc_html__( $wp_error_str, 'rpd-telex-youtube-block' ) . '</div>';
	echo '</div>';
	return;
}



//
// Use a custom template?
//
$retHtml = '';
$custom_template = '';
if ( $template_file != '' ) {

	if ($debug_mode)
		rpd_telex_log( "Render: theme:" . get_template_directory() .'/rpd-telex-youtube-block/'.$template_file);

	if ( file_exists( get_template_directory() .'/rpd-telex-youtube-block/'.$template_file ) ) {
		$custom_template = get_template_directory() .'/rpd-telex-youtube-block/'.$template_file;

	} else {
		if ($debug_mode)
			rpd_telex_log( "Render: plugin:" . plugin_dir_path( __DIR__ ) . 'templates/'.$template_file);

		if ( file_exists( plugin_dir_path( __DIR__ ) . 'templates/'.$template_file ) ) {
			$custom_template = plugin_dir_path( __DIR__ ) . 'templates/'.$template_file;
		}
	}
}

if ($debug_mode)
	rpd_telex_log('Render: custom template_file:'.$custom_template);

$has_template = false;
$template_file = $custom_template;
if ( file_exists( $template_file ) ) {
	// Custom template exists
	$has_template = true;
} else {
	// Use the standard template - include the ../ so you mve out of the build folder and into the plugin root
	$template_file = plugin_dir_path( __FILE__ ) . '../templates/rpdyt_template_std.php';
	if ( file_exists( $template_file ) ) {
		$has_template = true;
	} else {
		$template_file = '';
	}
}


if ($debug_mode)
	rpd_telex_log('Render: using template_file:'.$template_file);
$templateRenderer = null;

if ( !$has_template ) {
	$retHtml = '<p>Sorry, there is no template available to display the videos!</p>';
} else {
	// Include the template file
	include_once($template_file);

	// Instantiate the renderer class
	if ( class_exists("rpdyt_template") ) {
		$templateRenderer = new rpdyt_template( $attributes );

	} else {
		$name_space = basename($template_file, '.php');
		$class_name = $name_space."\\rpdyt_template";

		if ( class_exists($class_name) ) {
			$templateRenderer = new $class_name( $attributes );
		} else {
			$templateRenderer = null;
			$retHtml = '<p>Sorry, the template '.$template_file. ' does not support the mandatory functions.</p>';
			echo $retHtml;
		}
	}
}

$video_idx = 0;

if ( $templateRenderer ) { ?>

	<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php
	// Open the wrapper divs
	$retHtml .= $templateRenderer->rpdyt_get_block_wrapper_open($display_mode);

	// Lightbox / Mainstage mode - not just a grid of multiple players
	if ( $display_mode != 'grid' ) {
		if ( $display_mode == 'mainstage' ) {
			$video_id = 0;
			$retHtml .= '<div id="rpdytMainstageWrapper"><div id="rpdytMainstagePoster"></div>';
				$retHtml .= '<div class="player_wrapper">';
					$retHtml .= '<div class="ratio ratio-16x9 video-embed" id="player_wrap_'. esc_attr( $video_id ) .'" data-video-id="'. esc_attr( $video_id ) .'"></div>';

					if ( $show_thumbnail ) {
						// Get the thumbnail
						$title = '';
						$thumbnail_url = rpd_telex_youtube_thumbnail_url( $video_id, '' );
						$retHtml .= '<img class="rpd-telex-youtube-thumbnail" src="'. esc_url( $thumbnail_url ) .'" alt="'. esc_attr( $title ) .'" title="'. esc_attr( $title ) .'" loading="lazy" data-id="'. esc_attr( $video_id ) .'"/>';
					}

					$retHtml .= '</div>';
			$retHtml .= '</div>';
		}
		$retHtml .= '<div class="rpdyt-thumbnails">';
	}

	foreach ( $videos as $video ) {
		//Embed video
		$title = $description = $thumbnail_url = $fallback_thumb = '';
		$video_id = $video['id'];
		if ( ($source_type == 'playlist') || ($source_type == 'channel')) {
			if ($video_id) {
				$video_id = $video['id'];
				$title = $video['title'];
				$description = $video['description'];
				$custom_thumb_filename = $video['id'].'.jpg';
			}
		} elseif ( $source_type == 'multiple' ) {
			if ($video_id) {
				$video_id = $video['id'];
				$title = $video['title'];
				$description = $video['description'];
				//$fallback_thumb = $video->snippet->thumbnails->standard->url;
				$custom_thumb_filename = $video['id'].'.jpg';
			}
		} else {
			if ($video_id) {
				$video_id = $video['id'];
				$title = $video['title'];
				$description = $video['description'];
			}
		}

		// Check if there is a custom thumbnail provided.
		$thumbnail_url = rpd_telex_youtube_thumbnail_url( $video['id'], $video['thumbnail'] );

		// Limit description to 2 sentences. Break into sentences
		$pattern = "/[.!?]/";
		$sentences = preg_split( $pattern, $description, 3);
		if ( count($sentences) >= 2 ) {
			$description = $sentences[0] . '. ' . $sentences[1] . '. ';
		}

		if ( $video_id ) {
			$video_idx += 1;

			$retHtml .= $templateRenderer->rpdyt_render_one_post( $video_id, $video_idx, $title, $description, $thumbnail_url );
		}
	}

	// Close the wrapper divs
	$retHtml .= $templateRenderer->rpdyt_get_block_wrapper_close();

	echo $retHtml;

	} ?>

</div> <!-- Close the block wrapper -->

