
=== RDP YouTube Video Block ===

Contributors:      WordPress Telex
Tags:              block, youtube, video, playlist, channel
Tested up to:      6.8
Stable tag:        2026.01
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive YouTube video block with support for single videos, playlists, channels, and advanced caching capabilities.



== Description ==

The YouTube Video Block is a powerful WordPress block that allows you to embed and display YouTube content in multiple ways:

* Single Video Display - Embed individual YouTube videos by ID
* Multiple Videos - Display a collection of videos using comma-separated IDs
* Playlist Support - Show all videos from a YouTube playlist
* Channel Support - Display videos from a specific YouTube channel
* Smart Caching - Automatically cache YouTube API responses and thumbnails locally to improve performance and reduce API calls
* Cache Management - Manual cache clearing and automatic refresh intervals
* Customisable Display - Toggle visibility of titles, descriptions, and thumbnails
* Template System - Use custom templates to control video layout and presentation



== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/telex-youtube-block` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Obtain a YouTube Data API v3 key from the Google Cloud Console
4. Add the YouTube Video Block to your post or page
5. Configure your API key and content source in the block settings



== Frequently Asked Questions ==


Q - Do I need a Google API key??

A - Yes! You must provide a Google API key that has the YouTube Data API v3 AND YouTube Embedded Player API enabled. Plus, this key must be permitted for use on your domain name.

Go to API console at: https://console.cloud.google.com/apis/library

For more help, go to: https://support.google.com/googleapi/answer/6158862?hl=en

You may store the API key in Settings > YouTube Playlist admins settings, or you can provide it as a shortcake parameter.



Q - Do I need to provide BOTH the channel ID and playlist ID?

A - No, you provide either the playlist ID or the channel ID. If you do provide both, the playlist ID will be used.



Q - Where do I find the channel ID?

A - The channel ID is normally displayed in the URL, but if your YouTube page has a name, use the following instructions to obtain the channel ID:

https://mixedanalytics.com/blog/find-a-youtube-channel-id/



Q - Where do I find the playlist ID?

A: The playlist ID is normally displayed in the URL. For example:

https://www.youtube.com/playlist?list=PLi66rcYcdNzmQmYGm2sN3RESydBvdJM5q

The playlist ID is PLi66rcYcdNzmQmYGm2sN3RESydBvdJM5q



Q - Can I specify a list of YT videos, rather than a playlist?

A: Yes you can. You can choose to display a single video, or multiple videos.



Q - Can I use a custom template?

A: Yes you can. There are three templates included in the 'templates' folder - a standard grid layout, a MainStage layout and a Lightbox layout.

The easiest way to create a new template is to copy one of the standard templates from the 'templates' folder. First, create a folder called 'rpd-telex-youtube-block' in the current WP theme folder. Then copy one of the standard templates into the new folder, and rename it and change the PHP namespace to match the filename.

For example, if the custom template is titled 'my_custom_grid.php' change the namespace to 'my_custom_grid'.

Finally, in the settings for the block, set the name of the template in the 'Custom Template' section. For example, 'my_custom_grid.php'.



== Changelog ==

= v2026.01 - Initial release
