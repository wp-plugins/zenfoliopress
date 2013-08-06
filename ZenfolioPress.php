<?php
/*
 Plugin Name: ZenfolioPress
 Plugin URI: http://zenfoliopress.com
 Description: Integrate Zenfolio images and galleries with Word Press.
 Version: 0.1.3
 Author: David Nusbaum
 Author URI: http://www.davidnusbaum.com
 License: GPL2
 */
/*
 Copyright 2011  David Nusbaum  (email : dave@davidnusbaum.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/* admin actions */
if ( is_admin() ){
	include_once 'ZenfolioPressAdmin.php';
	add_action('admin_menu', array('ZenfolioPressAdmin','adminMenu'));
	add_action('admin_init', array('ZenfolioPressAdmin','registerSettings'));
} else {
	add_action('wp_print_styles',array('ZenfolioPress','loadStyleSheets'));
	add_action('wp_print_scripts',array('ZenfolioPress','loadScripts'));
	/* add filters for photo and photoset shortcuts */
	add_filter('widget_text', 'do_shortcode', SHORTCODE_PRIORITY,2);
	add_shortcode('ZFP_Photo', array('ZenfolioPress','showPhoto'));
	add_shortcode('ZFP_PhotoSet',array('ZenfolioPress','showPhotoSet'));
}

class ZenfolioPress {
	const STYLE_VERSION = 'v003';
	const LIGHTBOX_STYLE_VERSION = 'v2.04';
	const LIGHTBOX_VERSION = 'v2.05z';
	private static $options = null;
	private static $sizes = null;

	public static function getOptions() {
		if(self::$options === null) {
			/* set default options */
			$default['userName'] = '';
			$default['photoSize']='3';
			$default['photoAction']='1';
			$default['photoTarget']='_self';
			$default['thumbSize']='10';
			$default['thumbPadding']='5';
			$default['thumbAction']='2';
			$default['thumbTarget']='_self';
			$default['lightBoxSize']='3';
			$default['lightBoxTitle']='Title';

			/* update defauls with current options */
			$options = get_option('ZFP_Settings');
			if(!empty($options)) {
				foreach ($options as $option=>$value) {
					if(array_key_exists($option, $default)) {
						$default[$option] = $value;
					}
				}
			}
			self::$options = $default;
		}
		return self::$options;
	}

	public static function loadScripts() {

		$options = self::getOptions();
		if($options['photoAction'] == '3' | $options['thumbAction'] == '3' ) {
			wp_enqueue_script('jquery');
			$url = plugins_url('slimbox2.js', __FILE__);
			$fileName = WP_PLUGIN_DIR . '/zenfoliopress/slimbox2.js';
			if ( file_exists($fileName) ) {
				wp_register_script('zfp_slimbox2', $url,false,self::LIGHTBOX_VERSION);
				wp_enqueue_script( 'zfp_slimbox2');
			}
		}
	}

	public static function loadStyleSheets() {
		$options = self::getOptions();
				
		/* load ZenfolioPress style sheet */
		$url = plugins_url('style.php', __FILE__);
		$fileName = WP_PLUGIN_DIR . '/zenfoliopress/style.php';
		if ( file_exists($fileName) ) {
			wp_register_style('zfp_style', $url,false,self::STYLE_VERSION.'|'.$options['thumbPadding']);
			wp_enqueue_style( 'zfp_style');
		}

		/* load lightbox style sheet if needed */
		if($options['photoAction'] == '3' | $options['thumbAction'] == '3' ) {
			$url = plugins_url('slimbox2.css', __FILE__);
			$fileName = WP_PLUGIN_DIR . '/zenfoliopress/slimbox2.css';
			if ( file_exists($fileName) ) {
				wp_register_style('zfp_slimbox2', $url,false,self::LIGHTBOX_STYLE_VERSION);
				wp_enqueue_style( 'zfp_slimbox2');
			}
		}
	}

	public static function showPhoto($params) {
		$options = self::getOptions();
		$default_size = $options['photoSize'];
		$lightBoxSize = $options['lightBoxSize'];
		extract(shortcode_atts(array('id' => '','size'=>$default_size), $params));
		/* trim off any additional characters the user might have included */
		if(($h=strrpos($id,'h'))!==false) {
			$id = substr($id,$h+1);
		}
		/* convert from hex to decimal */
		$id = hexdec($id);
		/* retrieve the photo data from zenfolio */
		require_once('Zenfolio.php');
		$zenfolio = new Zenfolio();
		$photo = $zenfolio->loadPhoto($id,'LEVEL2');

		if($options['photoAction'] == '3') {
			$link = 'http://'.$photo->UrlHost.$photo->UrlCore.'-'.$lightBoxSize.'.jpg?sn='.$photo->Sequence;
		} else {
			$link = $photo->PageUrl;
		}
		$lightbox = $options['photoAction'] == '3' ? 'rel="zfpLightbox-p'.$id.'"' : '';
		$title = $photo->Title ? 'title="'.htmlspecialchars($photo->Title).'"' : '';
		$target = $options['photoAction'] < '3' ? 'target="'.$options['photoTarget'].'"' : '';
		$src = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
		
		$html = '';
		if($options['photoAction'] > '0') {
			$html.= "<a href=\"$link\" $lightbox $title $target>";
		}
		$html.= "<img id=\"$id\" src=\"$src\"/>";
		if($options['photoAction'] > '0') {
			$html.= "</a>";
		}
		return $html;
	}

	public static function showPhotoSet($params) {
		$options = self::getOptions();
		$default_size = $options['thumbSize'];
		extract(shortcode_atts(array('id' => '','size'=>$default_size), $params));
		/* trim off any additional characters the user might have included */
		if(($p=strrpos($id,'p'))!==false) {
			$id = substr($id,$p+1);
		}

		/* retrieve the PhotoSet data from zenfolio */
		require_once('Zenfolio.php');
		$zenfolio = new Zenfolio();
		$includeDetails = $options['lightBoxTitle']=='Caption' ? 2 : 1;
		$photoSet = $zenfolio->loadPhotoSet($id,'LEVEL2',$includeDetails);
		$photos = $photoSet->Photos;
		
		$target = $options['thumbAction'] < '3' ? 'target="'.$options['thumbTarget'].'"' : '';
		$lightbox = $options['thumbAction'] == '3' ? 'rel="zfpLightbox-ps'.$id.'"' : '';
		$lightBoxSize = $options['lightBoxSize'];

		$html = '';
		if(is_array($photos) && count($photos)) {
			$html.= "<div id=\"zfp_photoset_$id\" class=\"zfp_photoset\">\n";
			foreach ($photos as $photo) {
				$src = 'http://'.$photo->UrlHost.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
				switch ($options['thumbAction']) {
					case '2':
						$link = $photoSet->PageUrl.substr($photo->PageUrl,strrpos($photo->PageUrl,'/'));
						break;
					case '3':
						$link = 'http://'.$photo->UrlHost.$photo->UrlCore.'-'.$lightBoxSize.'.jpg?sn='.$photo->Sequence;
						break;
					default:
						$link = $photo->PageUrl;
				}
				switch($options['lightBoxTitle']) {
					case 'Title':
						$title = $photo->Title ? 'title="'.htmlspecialchars($photo->Title).'"' : '';
						$alt = $photo->Title ? 'alt="'.htmlspecialchars($photo->Title).'"' : '';
						break;
					case 'Caption':
						$title = $photo->Caption ? 'title="'.htmlspecialchars($photo->Caption).'"' : '';
						$alt = $photo->Caption ? 'alt="'.htmlspecialchars($photo->Caption).'"' : '';
						break;
					default:
						$title = '';
						$alt = '';
				}
				if($photo->Id == $photoSet->TitlePhoto->Id) {
					$titleSrc = 'http://'.$photo->UrlHost.$photo->UrlCore.'-11.jpg?sn='.$photo->Sequence;
				}
				//$html.= "<table class=\"zfp_frame\" style=\"$tableStyle\">\n";
				$html.= "<table class=\"zfp_frame zfp_$size\">\n";
				$html.= "<tr class=\"zfp_frame\">\n";
				//$html.= "<td class=\"zfp_frame \" style=\"$cellStyle\">\n";
				$html.= "<td class=\"zfp_frame zfp_$size\">\n";
				if($options['thumbAction'] > 0) {
					$html.= "<a class=\"zfp_frame\" href=\"$link\" $lightbox $title $target>\n";
				}
				$html.= "<img src=\"$src\" $alt >\n";
				if($options['thumbAction'] > 0) {
					$html.= "</a>\n";
				}
				$html.= "</td>\n";
				$html.= "</tr>\n";
				$html.= "</table>\n";
			}
			$html.= "</div> <!-- /zfp_photoset -->\n";
		}

		return $html;
	}

}
?>
