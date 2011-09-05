<?php
/*
 Plugin Name: ZenfolioPress
 Plugin URI: http://www.zenfoliopress.com
 Description: Integrate Zenfolio images and galleries with Word Press.
 Version: 0.0.5
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
	add_action('wp_print_styles',array('ZenfolioPress','loadStyleSheet'));
	/* add filters for photo and photoset shortcuts */
	add_filter('widget_text', 'do_shortcode', SHORTCODE_PRIORITY,2);
	add_shortcode('ZFP_Photo', array('ZenfolioPress','showPhoto'));
	add_shortcode('ZFP_PhotoSet',array('ZenfolioPress','showPhotoSet'));
}

class ZenfolioPress {
	const STYLE_VERSION = 'v002';
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
			$default['thumbAction']='2';
			$default['thumbTarget']='_self';

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

	public static function loadStyleSheet() {
		$url = plugins_url('style.css', __FILE__);
		$fileName = WP_PLUGIN_DIR . '/zenfoliopress/style.css';
		if ( file_exists($fileName) ) {
			wp_register_style('ZFP_Style', $url,false,self::STYLE_VERSION);
			wp_enqueue_style( 'ZFP_Style');
		}
	}

	public static function showPhoto($params) {
		$options = self::getOptions();
		$default_size = $options['photoSize'];
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
		$link = $photo->PageUrl;
		$target = $options['photoTarget'];
		$src = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
		$html = '';
		if($options['photoAction'] > 0) {
			$html.= "<a href=\"$link\" target=\"$target\">";
		}
		$html.= "<img id=\"$id\" src=\"$src\"/>";
		if($options['photoAction'] > 0) {
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
		$photoSet = $zenfolio->loadPhotoSet($id,'LEVEL2',true);
		$photos = $photoSet->Photos;

		$linkTarget = $options['thumbTarget'];

		$html = '';
		if(is_array($photos) && count($photos)) {
			$html.= "<div class=\"zfp_photoset\">\n";
			foreach ($photos as $photo) {
				$src = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
				if($options['thumbAction'] == '2') {
					$link =  $photoSet->PageUrl.substr($photo->PageUrl,strrpos($photo->PageUrl,'/'));
				} else {
					$link = $photo->PageUrl;
				}
				$title = $photo->Title ? $photo->Title : 'Untitled';
				if($photo->Id == $photoSet->TitlePhoto->Id) {
					$titleSrc = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-11.jpg?sn='.$photo->Sequence;
				}
				//$html.= "<table class=\"zfp_frame\" style=\"$tableStyle\">\n";
				$html.= "<table class=\"zfp_frame zfp_$size\">\n";
				$html.= "<tr class=\"zfp_frame\">\n";
				//$html.= "<td class=\"zfp_frame \" style=\"$cellStyle\">\n";
				$html.= "<td class=\"zfp_frame zfp_$size\">\n";
				if($options['thumbAction'] > 0) {
					$html.= "<a class=\"zfp_frame\" href=\"$link\" target=\"$linkTarget\">\n";
				}
				$html.= "<img src=\"$src\"/ alt=\"$title\">\n";
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