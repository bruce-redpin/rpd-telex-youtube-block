<?php
/*

Lightbox template for rpd-telex-youtube-block

Templates need to implement a rpdyt_template() class that support the following public methods:

rpdyt_get_block_wrapper_open()

    rpdyt_render_one_post()

rpdyt_get_block_wrapper_close()

*/

namespace rpdyt_template_lightbox;

class rpdyt_template {
	public $html;
	public $attributes;
    public $wrapperClass, $show_description, $show_title, $show_thumbnail, $show_button, $button_label, $mode, $debugMode, $framework;

	function __construct( $attributes ) {

		$this->html = '';

        // Grab attributes that relate to the template
        $this->attributes = $attributes;

        $this->wrapperClass = "rpd-telex-youtube-video-container";
		/*
        if ( $attributes['wrapperClass'] != '' ) {
            $this->wrapperClass  = $attributes['wrapperClass'];
        }
		*/

		$this->framework = $attributes['framework'];

		$this->show_description = (int) $attributes['showDescription'];
		$this->show_title = (int) $attributes['showTitle'];
		$this->show_thumbnail = (int) $attributes['showThumbnail'];
		$this->show_button = (int) $attributes['showButton'];
		$this->button_label = $attributes['buttonLabel'];
		$this->mode = (int) $attributes['displayMode'];
		$this->debugMode = (int) $attributes['debugMode'];
    }

    function rpdyt_render_one_post( $video_id, $video_count, $title, $description, $thumbnail_url ) {

        $this->html = '';

		// Framework column divs
		if ( $this->framework == 'bootstrap' ) {
			$this->html = '<div class="col-12 col-md-6 col-lg-4 bottom-30">';
		} else {
			$this->html = '<div class="rpdyt-thumbnail">';
		}

		// Poster image
		if ( $this->show_thumbnail && $thumbnail_url ) {
			$hero_img = '<img src="'.$thumbnail_url.'" loading="lazy" title="'.$title.'" alt="'.$title.'" data-id="'.$video_id.'" />';
			$this->html .= $hero_img;
		}
		// Video title
		if ($this->show_title) {
			$this->html .= '<h3 class="item_title">' . $title . '</h3>';
		}
		// Video description
		if ($this->show_description) {
			if ( function_exists("short_excerpt") ) {
				$excerpt = short_excerpt( $description, 80 );
			} else {
				$excerpt = $description;
			}
			$this->html .= '<p class="item_excerpt">' . wp_strip_all_tags( $excerpt ) . '</p>';
		}
		// Button
		if ($this->show_button) {
			$this->html .= '<button class="rpdyt-button" type="button" data-id="'.$video_id.'">'.$this->button_label.'</button>';
		}

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
        return '</div>';
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
