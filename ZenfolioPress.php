<?php
/*
 Plugin Name: ZenfolioPress
 Plugin URI: http://www.zenfoliopress.com
 Description: Integrate Zenfolio images and galleries with Word Press.
 Version: 0.0.4
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
	const STYLE_VERSION = 'v001';
	private static $options = null;
	private static $sizes = null;


	public static function getFrameCellStyle($size) {
		$sizes = self::getSizes();
		$style = 'border: 0; ';
		$style.= 'height: '.$sizes[$size][1].'px; ';
		$style.= 'width: '.$sizes[$size][0].'px; ';

		return $style;
	}

	public static function getFrameTableStyle($size) {
		$sizes = self::getSizes();
		$style = 'border: 0; ';
		$style.= 'height: '.$sizes[$size][1].'px; ';
		$style.= 'width: '.$sizes[$size][0].'px; ';
		$style.= 'margin: 5px; ';

		return $style;
	}

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

	public static function getSizes() {
		if(self::$sizes === null) {
			$sizes['0'] = array(80,80);
			$sizes['1'] = array(60,60);
			$sizes['2'] = array(400,400);
			$sizes['3'] = array(580,450);
			$sizes['4'] = array(800,630);
			$sizes['5'] = array(1100,850);
			$sizes['6'] = array(1550,960);
			$sizes['10'] = array(120,120);
			$sizes['11'] = array(200,200);
			self::$sizes = $sizes;
		}
		return self::$sizes;
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
		$tableStyle = self::getFrameTableStyle($size);
		$cellStyle = self::getFrameCellStyle($size);

		$html = '';
		if(is_array($photos) && count($photos)) {
			$html.= "<div class=\"zfp_gallery\">\n";
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
				$html.= "<table class=\"zfpFrame\" style=\"$tableStyle\">\n";
				$html.= "<tr class=\"zfpFrame\">\n";
				$html.= "<td class=\"zfpFrame\" style=\"$cellStyle\">\n";
				if($options['thumbAction'] > 0) {
					$html.= "<a class=\"zfpFrame\" href=\"$link\" target=\"$linkTarget\">\n";
				}
				$html.= "<img src=\"$src\"/ alt=\"$title\">\n";
				if($options['thumbAction'] > 0) {
					$html.= "</a>\n";
				}
				$html.= "</td>\n";
				$html.= "</tr>\n";
				/*
				 if($title) {
				 echo "<td class=\"vt_title\"><a href=\"$link\">$title</a></td>\n";
				 } else {
				 echo '&nbsp;';
				 }
				 */
				$html.= "</table>\n";
			}
			$html.= "</div> <!-- /zfp_gallery -->\n";
		}

		return $html;
	}

}
?>