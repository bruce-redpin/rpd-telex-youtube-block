
/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/// bruce
import ServerSideRender from '@wordpress/server-side-render';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

import { 
	PanelBody, 
	TextControl, 
	SelectControl, 
	ToggleControl, 
	Button,
	TextareaControl,
	RangeControl,
	Notice,
	CheckboxControl
} from '@wordpress/components';

import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object} props Block properties.
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		apiKey,
		sourceType,
		videoId,
		videoIds,
		playlistId,
		channelId,
		showTitle,
		showDescription,
		showThumbnail,
		showButton,
		buttonLabel,
		cacheRefreshInterval,
		maxResults,
		displayMode,
		templateFile,
		framework,
		debugMode,
		customThumbnail
	} = attributes;

	const [ isClearing, setIsClearing ] = useState( false );
	const [ clearMessage, setClearMessage ] = useState( '' );

	const blockProps = useBlockProps();

	const toggleDebugCheckbox = ( newValue ) => {
        setAttributes( { debugMode: newValue } );
    };
	

	const handleClearCache = () => {
		setIsClearing( true );
		setClearMessage( '' );

		fetch('/wp-json/block-rpd-telex-youtube-block/ajax_clear_cache', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': wpApiSettings.nonce,
			},
			body: JSON.stringify({ job: 'cache clear' }),
		} )

		.then(response => response.json())
		.then(json => {
			if (json.success) {
				setClearMessage( __( json.data, 'rpd-telex-youtube-block' ) );
				setIsClearing( false );
			}
		})
		.catch( ( error ) => {
			setClearMessage( __( 'Error clearing cache. Please try again.', 'rpd-telex-youtube-block' ) );
			setIsClearing( false );
		} );
	};


	const handleTest = () => {
		setClearMessage( '' );

		fetch('/wp-json/block-rpd-telex-youtube-block/ajax_rpd_test', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': wpApiSettings.nonce,
			},
			body: JSON.stringify({ foo: 'bar' }),
		} )

		.then(response => response.json())
		.then(json => {
			if (json.success) {
				setClearMessage( __( 'Test OK:'+json.data, 'rpd-telex-youtube-block' ) );
			}
		})
		.catch( ( error ) => {
			setClearMessage( __( 'Test fail!!!!', 'rpd-telex-youtube-block' ) );
		} );
	};


	const getPreviewContent = () => {
		if ( ! apiKey ) {
			return (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Please enter your YouTube API key in the block settings.', 'rpd-telex-youtube-block' ) }
				</Notice>
			);
		}

		let contentId = '';
		switch ( sourceType ) {
			case 'single':
				contentId = videoId;
				break;
			case 'multiple':
				contentId = videoIds;
				break;
			case 'playlist':
				contentId = playlistId;
				break;
			case 'channel':
				contentId = channelId;
				break;
		}

		if ( ! contentId ) {
			return (
				<Notice status="info" isDismissible={ false }>
					{ __( 'Please configure your content source in the block settings.', 'rpd-telex-youtube-block' ) }
				</Notice>
			);
		}

		return (
			<div className="telex-youtube-block-preview">
				<div className="preview-badge">
					<span>{ __( 'YouTube Video Block', 'rpd-telex-youtube-block' ) }</span>
				</div>
				<div className="preview-info">
					<p><strong>{ __( 'Source Type:', 'rpd-telex-youtube-block' ) }</strong> { sourceType }</p>
					<p><strong>{ __( 'Content ID:', 'rpd-telex-youtube-block' ) }</strong> { contentId }</p>
					<p><strong>{ __( 'Display Options:', 'rpd-telex-youtube-block' ) }</strong></p>
					<ul>
						<li>{ showTitle ? '✓' : '✗' } { __( 'Title', 'rpd-telex-youtube-block' ) }</li>
						<li>{ showDescription ? '✓' : '✗' } { __( 'Description', 'rpd-telex-youtube-block' ) }</li>
						<li>{ showThumbnail ? '✓' : '✗' } { __( 'Thumbnail', 'rpd-telex-youtube-block' ) }</li>
					</ul>
					
					{ ( sourceType === 'playlist' || sourceType === 'channel' ) && (
						<p><strong>{ __( 'Max Results:', 'rpd-telex-youtube-block' ) }</strong> { maxResults }</p>
					) }
					{ ( sourceType === 'single') && (
						<p><strong>{ __( 'Custom Thumbnail:', 'rpd-telex-youtube-block' ) }</strong> { customThumbnail }</p>
					) }

					<p><strong>{ __( 'Display Mode:', 'rpd-telex-youtube-block' ) }</strong> { displayMode }</p>
					<p><strong>{ __( 'Template:', 'rpd-telex-youtube-block' ) }</strong> { templateFile }</p>
				</div>
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'API Settings', 'rpd-telex-youtube-block' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'YouTube API Key', 'rpd-telex-youtube-block' ) }
						value={ apiKey }
						onChange={ ( value ) => setAttributes( { apiKey: value } ) }
						help={ __( 'Enter your YouTube Data API v3 key', 'rpd-telex-youtube-block' ) }
						type="password"
					/>
				</PanelBody>

				<PanelBody title={ __( 'Content Source', 'rpd-telex-youtube-block' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Source Type', 'rpd-telex-youtube-block' ) }
						value={ sourceType }
						options={ [
							{ label: __( 'Single Video', 'rpd-telex-youtube-block' ), value: 'single' },
							{ label: __( 'Multiple Videos', 'rpd-telex-youtube-block' ), value: 'multiple' },
							{ label: __( 'Playlist', 'rpd-telex-youtube-block' ), value: 'playlist' },
							{ label: __( 'Channel', 'rpd-telex-youtube-block' ), value: 'channel' }
						] }
						onChange={ ( value ) => setAttributes( { sourceType: value } ) }
					/>

					{ sourceType === 'single' && (
						<TextControl
							label={ __( 'Video ID', 'rpd-telex-youtube-block' ) }
							value={ videoId }
							onChange={ ( value ) => setAttributes( { videoId: value } ) }
							help={ __( 'Enter the YouTube video ID', 'rpd-telex-youtube-block' ) }
						/>
					) }

					{ sourceType === 'multiple' && (
						<TextareaControl
							label={ __( 'Video IDs', 'rpd-telex-youtube-block' ) }
							value={ videoIds }
							onChange={ ( value ) => setAttributes( { videoIds: value } ) }
							help={ __( 'Enter comma-separated video IDs', 'rpd-telex-youtube-block' ) }
						/>
					) }

					{ sourceType === 'playlist' && (
						<TextControl
							label={ __( 'Playlist ID', 'rpd-telex-youtube-block' ) }
							value={ playlistId }
							onChange={ ( value ) => setAttributes( { playlistId: value } ) }
							help={ __( 'Enter the YouTube playlist ID', 'rpd-telex-youtube-block' ) }
						/>
					) }

					{ sourceType === 'channel' && (
						<TextControl
							label={ __( 'Channel ID', 'rpd-telex-youtube-block' ) }
							value={ channelId }
							onChange={ ( value ) => setAttributes( { channelId: value } ) }
							help={ __( 'Enter the YouTube channel ID', 'rpd-telex-youtube-block' ) }
						/>
					) }

					{ ( sourceType === 'playlist' || sourceType === 'channel' ) && (
						<RangeControl
							label={ __( 'Maximum Results', 'rpd-telex-youtube-block' ) }
							value={ maxResults }
							onChange={ ( value ) => setAttributes( { maxResults: value } ) }
							min={ 1 }
							max={ 50 }
							help={ __( 'Number of videos to display', 'rpd-telex-youtube-block' ) }
						/>
					) }

					<SelectControl
						label={ __( 'Display Mode', 'rpd-telex-youtube-block' ) }
						value={ displayMode }
						options={ [
							{ label: __( 'Grid', 'rpd-telex-youtube-block' ), value: 'grid' },
							{ label: __( 'Mainstage', 'rpd-telex-youtube-block' ), value: 'mainstage' },
							{ label: __( 'Lightbox', 'rpd-telex-youtube-block' ), value: 'lightbox' }
						] }
						onChange={ ( value ) => setAttributes( { displayMode: value } ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Display Options', 'rpd-telex-youtube-block' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Show Title', 'rpd-telex-youtube-block' ) }
						checked={ showTitle }
						onChange={ ( value ) => setAttributes( { showTitle: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Description', 'rpd-telex-youtube-block' ) }
						checked={ showDescription }
						onChange={ ( value ) => setAttributes( { showDescription: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Thumbnail', 'rpd-telex-youtube-block' ) }
						checked={ showThumbnail }
						onChange={ ( value ) => setAttributes( { showThumbnail: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Button', 'rpd-telex-youtube-block' ) }
						checked={ showButton }
						onChange={ ( value ) => setAttributes( { showButton: value } ) }
					/>
					<TextControl
						label={ __( 'Button Label', 'rpd-telex-youtube-block' ) }
						value={ buttonLabel }
						onChange={ ( value ) => setAttributes( { buttonLabel: value } ) }
					/>
					<TextControl
						label={ __( 'Custom Thumbnail', 'rpd-telex-youtube-block' ) }
						value={ customThumbnail }
						onChange={ ( value ) => setAttributes( { customThumbnail: value } ) }
						help={ __( 'Filename of the custom thumbnail. JPG image. Save to "custom" folder.', 'rpd-telex-youtube-block' ) }
					/>
					<SelectControl
						label={ __( 'Framework', 'rpd-telex-youtube-block' ) }
						value={ framework }
						options={ [
							{ label: __( 'CSS Grid', 'rpd-telex-youtube-block' ), value: 'cssgrid' },
							{ label: __( 'Bootstrap', 'rpd-telex-youtube-block' ), value: 'bootstrap' },
						] }
						onChange={ ( value ) => setAttributes( { displayMode: value } ) }
					/>
					<CheckboxControl
						label="Debug Mode"
						help="Switch debug logging on or off"
						checked={ debugMode }
						onChange={ toggleDebugCheckbox }
					/>

				</PanelBody>

				<PanelBody title={ __( 'Cache Settings', 'rpd-telex-youtube-block' ) } initialOpen={ false }>
					<RangeControl
						label={ __( 'Auto Refresh Interval (minutes)', 'rpd-telex-youtube-block' ) }
						value={ cacheRefreshInterval }
						onChange={ ( value ) => setAttributes( { cacheRefreshInterval: value } ) }
						min={ 0 }
						max={ 1440 }
						help={ __( 'Set to 0 to disable automatic refresh', 'rpd-telex-youtube-block' ) }
					/>
					<Button
						variant="secondary"
						onClick={ handleClearCache }
						isBusy={ isClearing }
						disabled={ isClearing }
					>
						{ __( 'Clear Cache', 'rpd-telex-youtube-block' ) }
					</Button>
					{ clearMessage && (
						<Notice 
							status={ clearMessage.includes( 'success' ) ? 'success' : 'error' }
							isDismissible={ true }
							onRemove={ () => setClearMessage( '' ) }
						>
							{ clearMessage }
						</Notice>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Custom Template', 'rpd-telex-youtube-block' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Template File', 'rpd-telex-youtube-block' ) }
						value={ templateFile }
						onChange={ ( value ) => setAttributes( { templateFile: value } ) }
						help={ __( 'Enter the template filename', 'rpd-telex-youtube-block' ) }
					/>
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
			<ServerSideRender 
				block="telex/block-rpd-telex-youtube-block"
				attributes={ { 
					apiKey,
					sourceType,
					videoId,
					videoIds,
					playlistId,
					channelId,
					showTitle,
					showDescription,
					showThumbnail,
					showButton,
					buttonLabel,
					cacheRefreshInterval,
					maxResults,
					displayMode,
					templateFile,
					framework,
					debugMode,
					customThumbnail 
				} }
			/>
			</div>
	
		</>
	);
}
