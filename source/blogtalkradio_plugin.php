<?php
	/*
	Plugin Name: Blog Talk Radio Plugin
	Description: Loads a blog talk radio channel onto a page via a shortcode
	Version: 1.0
	Author: Jacob Davidson
	Author URI: http://jacobmdavidson.com
	License: MIT
	License URI: http://opensource.org/licenses/MIT
	*/

	add_shortcode('blogtalkradio', 'blogtalkradio');

	function blogtalkradio ($atts){
		extract(shortcode_atts(array(
			"username" => 'Default', 
			"itemcount" => '10',
			"itunes" => 'false',
			"logo" => 'true'), $atts));
		
		// Retrieve the image url, and the URL, title, and description of each episode 
		// from http://www.blogtalkradio/username.rss
		$description_array = getFeed ('http://www.blogtalkradio.com/' . $username . '.rss');
	
		// Retrieve the itunes link, and the URL, show_id, and embed code for each episode from 
		// http://www.blogtalkradio/username/playlist.xml
		$ident_array = getIdent('http://www.blogtalkradio.com/' . $username . '/play_list.xml?itemcount=' . $itemcount);
	
		// Retrieve the host_id from http://www.blogtalkradio/username/playlist.xml
		$host_id = getHostID('http://www.blogtalkradio.com/' . $username . '/play_list.xml');
	
		// Extract the image url
		$image_url = $description_array['image_url'];
	
		// Extract the itunes link
		$itunes_link = $ident_array[0]['itunes'];
		unset ($description_array['image_url'], $ident_array[0]);
	
		// Combine the ident_array and description_array
		$show_array = array();
			foreach ($ident_array as $ident) {
				foreach ($description_array as $description) {
					if ($ident['url'] === $description['url']) {
						$show_array[] = array(
									'id' => $ident['show_id'],
									'url' => $ident['url'],
									'title' => $description['title'],
									'description' => $description['description'],
									'embed_code' => $ident['embed_code']
									);
					}
				}
			}
		
		// Display the logo
		if($logo == 'true')
		{
		echo '<div class="blogtalk-logo" style="display: inline-block; width: 100%; text-align: center;">
				<a href="http://www.blogtalkradio.com/' . $username .'">
					<img src="' . $image_url . '" width="400" height="400" style="display: inline-block;"/>
				</a>
			</div>';
		};
	
		// Display the itunes link
		if($itunes == 'true')
		{
			echo '<div class="blogtalk-itunes" style="display: inline-block; width: 100%; text-align: center;">
					<br>
					<a href="' . $itunes_link . '" target="itunes_store">
						Check us out in iTunes!
					</a>
					<br>
					<br>
				</div>';
		};
	
		// Display the each episode
		echo '<div class="blogtalk-wrapper" style="width: 100%; text-align: center;">';
		foreach ($show_array as $show){
				echo '<h2 style="text-align: left;">' . $show['title'] . '</h2>';
				echo '<div class="blogtalk-embed" style="min-width: 280px; width: 45%; margin: 10px 0px; display: inline-block; vertical-align: top;">
						<iframe width="280px" height="200px" 
								src="http://player.cinchcast.com/?platformId=1&assetType=single&assetId=' . 
								$show['id'] . '" frameborder="0" allowfullscreen>
						</iframe>';
				echo '<div class="blogtalk-link" style="font-size: 10px;">' . $show['embed_code'] . '</div>
					  </div>
					  <div class="blogtalk-description" style="min-width: 300px; width: 51%; 
					  padding: 0px 10px; margin: 10px 0px;  display: inline-block; text-align: left; 
					  vertical-align: top;">
						<a href="' . $show['url'] . '">' . $show['title'] . '</a> - ' . $show['description'] . '
					  </div>
					  <div style="clear: both;"></div><br>';
		}
		echo '</div>';
	}

	/**
	 * Retrieves the itunes link, and the url, show_id, and embed code for
	 * each episode for a given feed url.
	 */
	function getIdent($feed_url) {
		$content = file_get_contents($feed_url);
		$x = new SimpleXmlElement($content);
		$ident_array = array();
		$ident_array[] = array(
						'itunes' => $x->trackList['ituneslink']
						);
		foreach ($x->trackList->track as $entry){
			//$show_id = strstr ($entry->identifier, ';', True);
			$show_id = $entry->identifier;
			$url = strstr ($entry->location, '.mp3', True);
			$embed_code = strstr ($entry->embedcode, '<div>');
			$embed_code = str_replace('&quot;', '"', $embed_code);
			$embed_code = str_replace('<div>', '', $embed_code);
			$embed_code = str_replace('</div>', '', $embed_code);
			$ident_array[] = array(
								'url' => $url, 
								'show_id'=>$show_id,
								'embed_code'=>$embed_code
							);
		}
		return $ident_array;
	}

	/**
	 * Retrieves the image link, and the url, title, and description for
	 * each episode for a given feed url.
	 */
	function getFeed($feed_url) {
		$description_array = array();
		$content = file_get_contents($feed_url);
		$content = str_replace("<![CDATA[", "", $content);
		$content = str_replace("]]", "", $content);
		$content = str_replace("&", "&amp;", $content);
		$x = new SimpleXmlElement($content);
		$image = (string)($x->channel->image->url);
		$description_array['image_url'] = $image;
		foreach($x->channel->item as $entry){
			$entry->description = str_replace('|', '', $entry->description);
			$entry->description = str_replace('>', '', $entry->description);
			$entry = get_object_vars($entry);
			$description_array[] = array (
									'url' => $entry['link'],
									'title' => $entry['title'],
									'description' => $entry['description']
									);
		}
		return $description_array;
	}

	/*
	 * Extracts the host id from a given feed url
	 *
	 */
	function getHostID($feed_url) {
		$description_array = array();
		$content = file_get_contents($feed_url);
		$x = new SimpleXmlElement($content);
		$host_id = str_replace('HostID: ', '', $x->trackList['host_id']);
		return $host_id;
	}
?>