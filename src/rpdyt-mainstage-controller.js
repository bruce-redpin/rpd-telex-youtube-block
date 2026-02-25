
var player = null; // The YT iframe

document.addEventListener( 'DOMContentLoaded', function() {
    //console.log('rpdyt doc ready');
    //rpdytSetup();
    
    // If the iframe player is not yet initialised, force the issue...
    if (!player) {
        //onYouTubeIframeAPIReady();
    }
});




function rpdytSetup() {

    // Play a video if one of the thumbnails is clicked
    document.getElementByClass('.rpdyt-thumbnail img').on('click', function () {
        var videoId = jQuery(this).data('id');
        var videoTitle = jQuery(this).attr('title');
        playVideo(videoId, videoTitle);
    });

    // Play a video if one of the thumbnails is clicked
    document.getElementByClass('.rpdyt-thumbnail a.button').on('click', function () {
        var videoId = jQuery(this).data('id');
        var videoTitle = jQuery(this).data('title');
        playVideo(videoId, videoTitle);
    });

    // Play a video if the poster is clicked
    document.getElementById('rpdytMainstagePoster').on('click', function () {
        var videoId = jQuery(this).data('id');
        var videoTitle = jQuery(this).data('title');
        playVideo(videoId, videoTitle);
    });
}

function onYouTubeIframeAPIReady() {
    //console.log('rpdyt onYouTubeIframeAPIReady');
    
    // Make sure the player is setup prior to initing the YT player
    let mainstage_poster = document.getElementById("rpdytMainstagePoster");
    if ( !mainstage_poster.dataset.id) {
        rpdytSetup();
    }
    
    player = new YT.Player('rpdytMainstage', {
        height: '390',
        width: '640',
        videoId: '',
        playerVars: { 'autoplay': 1, 'playsinline': 1 },
        events: {
            'onStateChange': onPlayerStateChange
        }
    });

    // Pre-load the first video into the mainstage poster
    var firstVideoId = document.getElementByClass(".rpdyt-thumbnails > :first-child > img").dataset.id;
    var firstVideoTitle = document.getElementByClass(".rpdyt-thumbnails > :first-child > img").getAttribute('title');
    var posterImg = document.getElementByClass(".rpdyt-thumbnails > :first-child > img").getAttribute('src');

    getElementById("rpdytMainstagePoster").css("background-image",'url("'+posterImg+'")');
    getElementById("rpdytMainstagePoster").setAttribute('data-title', firstVideoTitle)
    getElementById("rpdytMainstageTitle").text(firstVideoTitle);
    getElementById("rpdytMainstagePoster").setAttribute('data-id', firstVideoId);
}



function playVideo( videoId, videoTitle = "" ) {
    //console.log('rpdyt playVideo start...'+videoId);

    if (player) {
        getElementById("rpdytMainstagePoster").style.display = "block";
        player.loadVideoById(videoId);
console.log('title:'+videoTitle);
        // Set the mainstage title
        if ( getElementById("rpdytMainstageTitle").text !== '' ) {
console.log('setting title:'+videoTitle);
            getElementById("rpdytMainstageTitle").text(videoTitle);
        }

        // Scroll the player into view
        document.querySelector('rpdytMainstageWrapper').scrollIntoView();
    }
}




/*
function onPlayerStateChange(event) {
    var done = false;
    //console.log('rpdyt state change: '+event.data );

    
    // -1 (unstarted)
    // 0 (ended)
    // 1 (playing)
    // 2 (paused)
    // 3 (buffering)
    // 5 (video cued).
    

    if (event.data == YT.PlayerState.PLAYING) {
        //jQuery('#rpdytMainstage-close').show();
        //jQuery('#yrpdytMainstageWrapper').show();
    }
}


function pauseVideo() {
    if (player) {
        player.pauseVideo();

        //jQuery('#iytpMainstage-close').hide();
        //jQuery('#rpdytMainstageWrapper').hide();
    } 
    //console.log('rpdyt pause video');
}
*/
