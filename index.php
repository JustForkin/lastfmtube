<?php
# encoding: UTF-8

require_once 'includes/bootstrap.php';

DB::getInstance()->updateLastFMUserVisit($_SESSION['music']['lastfm_user']);


$page=1;
$pagecnt=25;



$playlist       = $lastfm->getRecentlyPlayed($page,$pagecnt);
$tracklist      = $playlist->getTracks();

$default_video='31H7--Cjo7w';

$final_tracklist = array();

	
for($i=0;$i<sizeof($tracklist);$i++) {
    $track = $tracklist[$i];
    $needle = $track->artist.' '.$track->title;
    $needle = addslashes(html_entity_decode($needle));
    $video_id=DB::getInstance()->getEnvVar($needle);

    if($video_id!=''&&$video_id!='undefined') {
        if(!isset($startvideo)) {
            $startvideo['videoId']      = $video_id;
            $startvideo['videoIndex']   = $i;
            $startvideo['artist']       = addslashes(html_entity_decode($track->artist));
            $startvideo['title']        = addslashes(html_entity_decode($track->title));
        }
        $final_tracklist[] = array('artist'=>$track->artist,'title'=>$track->title,'dateofplay'=>$track->dateofplay,'isplaying'=>(($track->isPlaying())?true:false),'video_id'=>$video_id);
    } else if(!isset($startvideo)) {
        $searcher->setNeedle($needle);
        $searcher->search();
        $search_result = $searcher->getVideoList();

        if(sizeof($search_result)>0)
        {
            $video                          = $search_result[0];
            $startvideo['videoId']          = $video->getVideoID();
            $startvideo['videoIndex']       = $i;
            $startvideo['artist']           = addslashes(html_entity_decode($track->artist));
            $startvideo['title']            = addslashes(html_entity_decode($track->title));
        }

        $final_tracklist[] = array('artist'=>$track->artist,'title'=>$track->title,'dateofplay'=>$track->dateofplay,'isplaying'=>(($track->isPlaying())?true:false),'video_id'=>'-1');
    } else
        $final_tracklist[] = array('artist'=>$track->artist,'title'=>$track->title,'dateofplay'=>$track->dateofplay,'isplaying'=>(($track->isPlaying())?true:false),'video_id'=>'-1');
}

$trackno = (($page-1)*$pagecnt)+1;

if(isset($_GET['disable_charts']))
    $smarty->assign('charts_counter',false);
else
    $smarty->assign('charts_counter',true);

$smarty->assign('music',1);
$smarty->assign('lastfm_user',$_SESSION['music']['lastfm_user']);
$smarty->assign('current_page',$page);
$smarty->assign('total_pages',$playlist->totalpages);
$smarty->assign('track_no',$trackno);
$smarty->assign('startvideo',$startvideo);
$smarty->assign('autostart',true);
$smarty->assign('tracklist',$final_tracklist);
$smarty->display('index.tpl');


?>