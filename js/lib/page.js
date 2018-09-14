class PageController {


    constructor(vue) {
        this.vue = vue;
        this.vueMap = Array();
        this.PLAY_CONTROL = null;
        this.QUICKPLAY_TRACK = null;
        this.QUICKPLAY_TRACK_NR = null;
    }

    init() {

        let vueMap = this.vueMap;
        let request = '/dev/lastfmtube/php/json/JsonHandler.php?api=page&data=page';
        let page = this;

        $.getJSON(request, function (json) {
            //console.log(JSON.stringify(json.data.value));
            for (let key in json.data.value) {
                switch (key) {
                    case 'PLAYLIST_NAV':
                        vueMap[key] = page.initPlayListNav(json.data.value[key]);
                        break;
                    case 'PLAYLIST_TRACKS':
                        vueMap[key] = page.initPlayListTracks(json.data.value[key]);
                        break;
                    default:
                        vueMap[key] = page.initDefaultVue(json.data.value[key]);
                        break;
                }
            }
            console.log('init page success');
        }).fail(function (xhr, status, error) {
            //var err = eval("(" + xhr.responseText + ")");
            console.log(xhr.responseText);
        });
    }


    initDefaultVue(json) {
        return new this.vue({
            el: json.el,
            data: json.data
        });
    }

    initPlayListNav(json) {
        return new this.vue({
            el: json.el,
            data: json.data,

            methods: {
                loadPage: function (user, pageNum) {
                    page.loadPage(user, pageNum);
                },

                loadNextPage: function (user, pageNum, maxPages) {
                    pageNum++;
                    if (pageNum > maxPages) pageNum = 1;
                    page.loadPage(user, pageNum);
                },

                loadPrevPage: function (user, pageNum, maxPages) {
                    pageNum--;
                    if (pageNum <= 0) pageNum = maxPages;
                    page.loadPage(user, pageNum);
                }
            }
        });
    }

    initPlayListTracks(json) {
        return new this.vue({
            el: json.el,
            data: json.data,

            methods: {
                togglePlayControl: function (track) {
                    if (page.PLAY_CONTROL != null && page.PLAY_CONTROL != track) {
                        page.PLAY_CONTROL.PLAY_CONTROL = false;
                    }

                    track.PLAY_CONTROL = !track.PLAY_CONTROL;
                    page.PLAY_CONTROL = track;
                },

                playTrack: function (track) {
                    player.loadSong(track);
                },

                togglePlay: function (track) {

                    if (player.CURRENT_TRACK == track) {

                        let track_icon = $(track.NR).prop('outerHTML');

                        if (track_icon === player.icon_playing) {
                            player.ytPlayer.pauseVideo();
                            track.NR = player.icon_pause;
                        } else if (track_icon === player.icon_pause) {
                            track.NR = player.icon_playing;
                            player.ytPlayer.playVideo();
                        } else {
                            console.log('unbekannter zustand für play/pause');
                            console.log(track_icon);
                        }
                    } else if (page.QUICKPLAY_TRACK == track) {
                        page.QUICKPLAY_TRACK.NR = page.QUICKPLAY_TRACK_NR;
                        player.loadSong(track);
                    } else {
                        console.log('unbekannter track');
                        console.log(track);
                    }


                },

                showPlay: function (track, show) {
                    if (track == player.CURRENT_TRACK) {
                        return;
                    }

                    if (show) {
                        page.QUICKPLAY_TRACK = track;
                        page.QUICKPLAY_TRACK_NR = track.NR;
                        track.NR = player.icon_play;
                    } else {
                        track.NR = page.QUICKPLAY_TRACK_NR;
                        page.QUICKPLAY_TRACK = null;
                        page.QUICKPLAY_TRACK_NR = null;
                    }
                }
            }
        });
    }

    loadPage(user, pageNum, list = 'default', callBack = null) {
        let vueMap = page.vueMap;


        let request = 'php/json/JsonHandler.php?api=page&data=playlist' +
            '&type=' + list +
            '&user=' + user +
            '&page=' + pageNum
        ;

        $.getJSON(request, function (json) {

            vueMap['PLAYLIST_HEADER'].$data.LASTFM_USER_NAME = json.data.value['PLAYLIST_HEADER'].data.LASTFM_USER_NAME;
            vueMap['PLAYLIST_HEADER'].$data.LASTFM_USER_URL = json.data.value['PLAYLIST_HEADER'].data.LASTFM_USER_URL;
            vueMap['PLAYLIST_NAV'].$data.CUR_PAGE = json.data.value['PLAYLIST_NAV'].data.CUR_PAGE;
            vueMap['PLAYLIST_NAV'].$data.LASTFM_USER_NAME = json.data.value['PLAYLIST_NAV'].data.LASTFM_USER_NAME;

            if (player.CURRENT_TRACK != null) {
                let newCurTrack = null;
                for(let cnt=0; cnt< json.data.value['PLAYLIST_TRACKS'].data.TRACKS.length; cnt++) {
                    let track = json.data.value['PLAYLIST_TRACKS'].data.TRACKS[cnt];

                    if(track.NR==player.CURRENT_TRACK_NR) {
                        newCurTrack = track;
                        break;
                    }
                }

                if (newCurTrack != null) {
                    player.setCurrentTrack(newCurTrack);
                    newCurTrack.NR = player.icon_playing;
                }
            }

            vueMap['PLAYLIST_TRACKS'].$data.TRACKS = json.data.value['PLAYLIST_TRACKS'].data.TRACKS;

            if(callBack!=null) {
                callBack();
            }
        }).fail(function () {
            console.error('error loading page');
        });
    }

}