<?php

//inicia web scraping para lista de videos de canales en Youtube
function init($part, $chan, $test){

	require 'simple_html_dom.php';

	$chan = strtolower($chan);
	$chan = str_replace(" ", "_", $chan);

	if ($part != 0) {
		if (!$test) {
			echo "Insertando datos del canal " . $chan . " parte " . $part . " <br/>";
		}
		$url = "http://localhost/webscraping/" . $chan . "/" . $chan . "_fixed_p" . $part . ".html";
	} else {
		if (!$test) {
			echo "Insertando datos del canal " . $chan . " <br/>";
		}
		$url = "http://localhost/webscraping/" . $chan . "/" . $chan . "_fixed.html";
	}

	
	$html = file_get_html($url);
	$arr = array();
	$json = array();
	$count = 0;

	if (empty($html)) {

		echo "el html es vacio</br> ";

		return false;

	} else {

		$feed_item_container = $html->find('li[class=feed-item-container]');

		foreach ($feed_item_container as $item) {
			$feed_item_dismissable = $item->find('div[class=feed-item-dismissable]', 0);
			$feed_item_main = $feed_item_dismissable->find('div[class=feed-item-main]', 0);
			$feed_item_main_content = $feed_item_main->find('div[class=feed-item-main-content]', 0);
			
			$yt_lockup = $feed_item_main_content->find('div[class=yt-lockup]', 0);
			$yt_lockup_dismissable = $yt_lockup->find('div[class=yt-lockup-dismissable]', 0);
			$yt_lockup_content = $yt_lockup_dismissable->find('div[class=yt-lockup-content]', 0);
			$yt_lockup_thumbnail = $yt_lockup_dismissable->find('div[class=yt-lockup-thumbnail]', 0);
			$video_time = $yt_lockup_thumbnail->find('span[class=video-time]', 0);

			$yt_lockup_meta =  $yt_lockup_content->find('div[class=yt-lockup-meta]', 0);
			$yt_lockup_meta_info = $yt_lockup_meta->find('ul[class=yt-lockup-meta-info]', 0);
			
			$link = $yt_lockup_content->find('h3 a', 0);
			$span_length = $yt_lockup_content->find('h3 span', 0);
			$li_seen = $yt_lockup_meta_info->find('li', 1);
			$li_published = $yt_lockup_meta_info->find('li', 0);
			$span_time = $video_time->find('span', 0);

			$url = $link->attr['href'];
			$title = $link->innertext;
			$length = $span_length->innertext;
			$time = $span_time->innertext;
			$seen = $li_seen->innertext;
			$published = $li_published->innertext;

			$fixed_title = str_replace("'", "", $title);
			$arr['title'] = $fixed_title;
			$arr['url'] = 'http://youtube.com'.$url;
			$arr['length'] = $length;
			$arr['time'] = $time;
			$pretty_seen = str_replace(" visualizaciones", "", $seen);
			$pretty_seen = intval(str_replace(".", "", $pretty_seen));
			$arr['seen'] = $pretty_seen;
			$arr['published'] = $published;
			array_push($json, $arr);

		}

		if ($test) {
			echo json_encode($json);
		} else {
			return $json;
		}
	}
}

init(1, 'Karaoke Channel International', true); //comentar al hacer load




