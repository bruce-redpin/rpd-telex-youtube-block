/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 */

// Global iframe setup
var ytPlayerReady = false;

// Store the player that is currently playing.
var currentPlayingPlayer = null;

var is_mainstage = false;
var is_lightbox = false;


document.addEventListener( 'DOMContentLoaded', function() {
    initYouTubePlayers();

    // Make video thumbnails clickable
    const thumbnails = document.querySelectorAll('.rpd-telex-youtube-thumbnail');
    // Loop through the selected elements and add the event listener
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', handleThumbnailClick);
    });

    // Make 'Watch Now' buttons clickable
    const buttons = document.querySelectorAll('.rpdyt-button');
    // Loop through the selected elements and add the event listener
    buttons.forEach(button => {
        button.addEventListener('click', handleThumbnailClick);
    });

    // Make video players clickable
    const players = document.querySelectorAll('.video-embed');
    // Loop through the selected elements and add the event listener
    players.forEach(player => {
        player.addEventListener('click', handlePlayerClick);
    });

} );


function loadYouTubeAPI() {
  return new Promise((resolve) => {
    if (window.YT && window.YT.Player) {
      resolve(window.YT);
      return;
    }

    const existingScript = document.querySelector('script[src*="youtube.com/iframe_api"]');
    if (existingScript) {
      window.onYouTubeIframeAPIReady = () => resolve(window.YT);
      return;
    }

    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    document.body.appendChild(tag);

    window.onYouTubeIframeAPIReady = () => resolve(window.YT);
  });
}

/*
const observer = new MutationObserver(() => {
  initYouTubePlayers();
});

observer.observe(document.body, {
  childList: true,
  subtree: true
});
*/


window.onYouTubeIframeAPIReady = function () {
    // If the iframe player is not yet initialised, force the issue...
    if (!ytPlayerReady) {
        onYouTubeIframeAPIReady();
    }
}


const YTPlayers = new Map();

async function initYouTubePlayers() {
    const YT = await loadYouTubeAPI();

    var videoElements = document.getElementsByClassName('rpd-telex-youtube-video-item');
    if ( videoElements[0] ) {
        var videoId = videoElements[0].dataset.videoId;
console.log('first video id:'+videoId);
    }

    var mainstage_wrapper = document.getElementById("rpdytMainstageWrapper");
    var lightbox_wrapper = document.querySelectorAll(".rpd-telex-youtube-video-container.lightbox");

    if ( mainstage_wrapper ) {
        is_mainstage = true;
        is_lightbox = false;

        const player = new YT.Player(mainstage_wrapper, {
            videoId: videoId,
            playerVars: {
                autoplay: 0,
                playsinline: 1,
                rel: 0,
                modestbranding: 1
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
        YTPlayers.set(videoId, player);
        currentVideoId = videoId;
console.log('set mainstage player for: '+videoId);

    } else if ( lightbox_wrapper[0] ) {
        is_mainstage = false;
        is_lightbox = true;

        // Handle thumbnail clicks
        document.querySelectorAll('.rpdyt-thumbnail img').forEach((el) => {
            el.addEventListener('click', () => {
                videoId = el.dataset.id;
                rpdytDisplayLightbox(videoId);
            });

        });
        // Handle button clicks
        document.querySelectorAll('.rpdyt-thumbnail button').forEach((el) => {
            el.addEventListener('click', () => {
                videoId = el.dataset.id;
                rpdytDisplayLightbox(videoId);
            });

        });


    } else {
        document.querySelectorAll('.video-embed').forEach((el) => {
            videoId = el.dataset.videoId;

            if ( !YTPlayers.has(videoId)) {

console.log('setup multiple single players ');
                    // Single players, not a mainstage
                    const player = new YT.Player(el, {
                        videoId: videoId,
                        playerVars: {
                            autoplay: 0,
                            playsinline: 1,
                            rel: 0,
                            modestbranding: 1
                        },
                        events: {
                            'onReady': onPlayerReady,
                            'onStateChange': onPlayerStateChange
                        }
                    });

                    YTPlayers.set(videoId, player);

                    // 
                    el.style.display = "none";
                }
            }
        );
    }
}


// Display the lightbox as required
function rpdytDisplayLightbox( videoId ) {
    if (videoId) {
        var lightboxHtml = '<div id="rpdyt_lightboxWrapper"><div class="rpdyt_lightbox_overlay">';
        lightboxHtml += '<div id="rpdytLightboxClose">&times;</div><iframe src="https://www.youtube.com/embed/' + videoId + '?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        lightboxHtml += '</div></div>';
        document.body.insertAdjacentHTML('beforeend', lightboxHtml);

        // Remove the div when the X is clicked
        var closeButton = document.getElementById('rpdytLightboxClose');
        closeButton.addEventListener('click', () => {
            const lightboxDiv = document.getElementById('rpdyt_lightboxWrapper');
            lightboxDiv.remove();
        });
    }
}


function handlePlayerClick(event) {
    if ( event.target.classList.contains('.video-embed') ) {
        const wrapper = event.target;
    } else {
        const wrapper = event.target.closest('.video-embed');
    }

    if (wrapper) {
        const videoId = wrapper.dataset.videoId;
        const player = YTPlayers.get(videoId);

        if (player) {
            // Pause all others
            YTPlayers.forEach((p, id) => {
                if (id !== videoId) {
                p.pauseVideo();
                }
            });

            player.playVideo();
        }
    }
}


// Handle starting the player if a thumbnail is clicked
function handleThumbnailClick(event) {
    const thumb = event.target;

    if ( thumb.matches('img') || (thumb.tagName === 'BUTTON') ) {
        const dataId = thumb.dataset.id;
        console.log('dataId: ' + dataId);

        if ( dataId ) {
            if ( is_lightbox ) {
                // Play the video in a lightbox
                rpdytDisplayLightbox( dataId );
                
            } else {
                // Play the video
                handleYtPlayVideo( dataId );
            }
        }
    }
}


// The player is ready!
function onPlayerReady(event) {
    console.log('onPlayerReady');
}


// Playing state changed. If there is already a video playing, we MUST pause it. You cannot have two
// videos playing at the same time.
function onPlayerStateChange(event) {

    if (event.data == YT.PlayerState.PLAYING) {

        if ( currentPlayingPlayer ) {
            // Check this is not the same video
            var currId = currentPlayingPlayer.getVideoData().video_id;
            var newId = event.target.getVideoData().video_id;
console.log(currId+' vs '+newId);
            if ( currId != newId ) {
                currentPlayingPlayer.pauseVideo();
            }
        }

        currentPlayingPlayer = event.target; // This is the current YT.Player instance
    }

    if ( currentPlayingPlayer ) {
        var currentVideoId = currentPlayingPlayer.getVideoData().video_id;
        console.log('currentVideoId: '+currentVideoId);
    }
}


function handleYtPlayVideo( videoId, videoTitle = "", singlePlayer = true ) {
    console.log('handleYtPlayVideo wants to start...'+`${videoId}`);

    var currentVideoId = 0;
    if ( currentPlayingPlayer ) {
        currentVideoId = currentPlayingPlayer.getVideoData().video_id;
    }
    console.log('handleYtPlayVideo current video...'+`${currentVideoId}`);

    if (currentVideoId == videoId) {
        currentPlayingPlayer.pauseVideo();
         currentPlayingPlayer = null;

    } else {
        if (YTPlayers) {
console.log('mainstage:'+is_mainstage+'  lightbox:'+is_lightbox);
            if ( is_mainstage ) {
                // Mainstage player - get the key value of the first index
                // Get the iterator
                const keysIterator = YTPlayers.keys(); // Returns a MapIterator
                // Get the first item from the iterator - this is the videoId
                currentVideoId = keysIterator.next().value;

                const player = YTPlayers.get(currentVideoId);
                if (player && typeof player.loadVideoById === 'function') {
                    player.loadVideoById(videoId);
                    
                    console.log('handleYtPlayVideo switching from '+`${currentVideoId}`+' to '+`${videoId}`);
                    
                    // Update the YTPlayers mapping
                    YTPlayers.delete(currentVideoId, player);
                    YTPlayers.set(videoId, player);
                    currentVideoId = videoId;

                } else {
console.log('handleYtPlayVideo failed to get player!');
                }
                
            } else if ( is_lightbox ) {


            } else {
                // Single individual players
                const player = YTPlayers.get(videoId);

                if (player) {
                    player.playVideo();

                    // Hide the thumbnail so you can see the player
                    var thumb = document.querySelector(`[data-id="${videoId}"]`);
                    if (thumb) {
                        thumb.style.zIndex = "-1";
                    }
                }
            }
        }
    }
}
