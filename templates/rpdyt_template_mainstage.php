<?php
/*

Mainstage template for rpd-telex-youtube-block

Templates need to implement a rpdyt_template() class that support the following public methods:

rpdyt_get_block_wrapper_open()

    rpdyt_render_one_post()

rpdyt_get_block_wrapper_close()

*/

namespace rpdyt_template_mainstage;

class rpdyt_template {
	public $html;
	public $attributes;
    public $wrapperClass, $show_description, $show_title, $show_thumbnail, $show_button, $button_label, $mode, $debugMode, $framework;


	function __construct( $attributes ) {

		$this->html = '';

        // Grab attributes that relate to the template
        $this->attributes = $attributes;

        $this->wrapperClass = "rpd-telex-youtube-video-container";
       // if ( $attributes['wrapperClass'] != '' ) {
            //$this->wrapperClass  = $attributes['wrapperClass'];
        //}

		$this->framework = $attributes['framework'];

		$this->show_description = (int) $attributes['showDescription'];
		$this->show_title = (int) $attributes['showTitle'];
		$this->show_thumbnail = (int) $attributes['showThumbnail'];
		$this->show_button = (int) $attributes['showButton'];
		$this->button_label = $attributes['buttonLabel'];
		$this->mode = (int) $attributes['displayMode'];
		$this->debugMode = (int) $attributes['debugMode'];

    }

    function rpdyt_render_one_post( $video_id, $video_idx, $title, $description, $thumbnail_url ) {

        $this->html = '';

		// Framework column divs
		if ( $this->framework == 'bootstrap' ) {
			$this->html .= '<div class="col-12 col-md-6 col-lg-4 bottom-30">';
		} else {
			// Standard CSS grid
			$this->html .= '<div class="video_wrap">';
		}

		$this->html .= '<div class="rpd-telex-youtube-video-item" id="video_item_'. esc_attr( $video_id ) .'" data-video-id="'. esc_attr( $video_id ) .'">';

			if ( $this->show_thumbnail ) {
				$this->html .= '<img class="rpd-telex-youtube-thumbnail" src="'. esc_url( $thumbnail_url ) .'" alt="'. esc_attr( $title ) .'" title="'. esc_attr( $title ) .'" loading="lazy" data-id="'. esc_attr( $video_id ) .'"/>';
			}

			$this->html .= '<div class="rpd-telex-youtube-content">';
				if ( $this->show_title ) {
					$this->html .= '<h3 class="rpd-telex-youtube-title">'. esc_html( $title ) .'</h3>';
				}
				
				if ( $this->show_description ) {
					$excerpt = $this->short_excerpt( $description, 250 );
					$this->html .= '<p class="rpd-telex-youtube-description">'. $excerpt .'</p>';
				}

				// Button
				if ($this->show_button) {
					$this->html .= '<button class="rpdyt-button" type="button" data-id="'.$video_id.'">'.$this->button_label.'</button>';
				}
			$this->html .= '</div>';
		$this->html .= '</div>';

							
		// Close framework column divs
		$this->html .= '</div>';

		return $this->html;
	}



    function rpdyt_get_block_wrapper_open($extra_class = '') {
		// Framework row divs
		$retHtml = '<div class="'.$this->wrapperClass.' '.$extra_class.'" id="ytEmbeds">';
		return ( $retHtml );
    }

	
    function rpdyt_get_block_wrapper_close() {
		// Close framework row divs
		$retHtml = '</div>';
        return ( $retHtml );
    }

    //
    // Add additional supporting function here....
    //

	private function get_first_sentence($content, $min_character_count = 0, $max_character_count = 150, $num_sentances = 1) {
		$retVal = $content;

		// Remove H4s
		$clean = preg_replace('#<h4>(.*?)</h4>#', '', $content);
		$clean = wp_strip_all_tags($clean);
		// Replace all curly quotes.
		$clean = str_replace(array('“','”'), '"', $clean);

		$locs = $this->get_sentance_endings($clean, $min_character_count);
		$loc = $locs[0];

	
		$retVal = substr($clean,0, ($loc+1) );

		if ($num_sentances == 2) {
			$clean = substr( $clean, ($loc+1), (strlen($clean)-($loc+1)) );

			$locs = $this->get_sentance_endings($clean, $min_character_count);
			$loc = $locs[0];
			$retVal .= substr($clean,0, ($loc+1) );
		}

		if (strlen($retVal) > $max_character_count) {
			$retVal = substr($retVal,0,$max_character_count+10);
			$last_word = strripos($retVal,' ');
			if ($last_word !== false) {
				$retVal = substr($retVal,0,$last_word) . '...';
			}
		}

		return $retVal;
	}

	private function get_sentance_endings( $clean, $min_character_count ) {
		$exclaim = strpos($clean, "!",$min_character_count);
		if ($exclaim === false) {
			$exclaim = strlen($clean)-1;
		}
		$question = strpos($clean, "?",$min_character_count);
		if ($question === false) {
			$question = strlen($clean)-1;
		}
		$endquote = strpos($clean, '".',$min_character_count);
		if ($endquote === false) {
			$endquote = strlen($clean)-1;
		}
		$period = strpos($clean, '.',$min_character_count);
		if ($period === false) {
			$period = strlen($clean)-1;
		}

		$locs = array($exclaim,$question,$endquote,$period);
		sort( $locs );

		return $locs;
	}

	private function short_excerpt( $content, $limit_chars = 150 ) {
		$excerpt = $this->get_first_sentence($content, 0, $limit_chars, 1);
		return $excerpt;
	}
}
