<?php
/*
 Plugin Name: ZenfolioPress
 Plugin URI: http://www.zenfoliopress.com
 Description: Integrate Zenfolio images and galleries with Word Press.
 Version: 0.0.2
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
	add_action('admin_menu', array('ZenfolioPress','adminMenu'));
	add_action('admin_init', array('ZenfolioPress','registerSettings'));
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
			$default['thumbSize']='10';

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
		$fileName = WP_PLUGIN_DIR . '/ZenfolioPress/style.css';
		if ( file_exists($fileName) ) {
			wp_register_style('ZFP_Style', $url,false,self::STYLE_VERSION);
			wp_enqueue_style( 'ZFP_Style');
		}
	}

	public static function adminMenu() {
		add_options_page('ZenfolioPress Options', 'ZenfolioPress', 'manage_options', 'zfp_options', array('ZenfolioPress','showOptions'));
	}

	public static function showOptions() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		include 'options.html';
	}

	public static function registerSettings() {
		register_setting( 'ZFP_Settings', "ZFP_Settings",array('ZenfolioPress','validateOptions') );
		add_settings_section('ZFP_ZenfolioSettings', 'Zenfolio Settings',array('ZenfolioPress','zenfolioSettingsHTML'), 'ZenfolioPress');
		add_settings_field('userName', 'Zenfolio User Name', array('ZenfolioPress','userNameHTML'), 'ZenfolioPress', 'ZFP_ZenfolioSettings');


		add_settings_section('ZFP_PhotoSettings', 'Photo Settings',array('ZenfolioPress','photoSettingsHTML'), 'ZenfolioPress');
		add_settings_field('photoSize', 'Default Photo Size', array('ZenfolioPress','photoSizeHTML'), 'ZenfolioPress', 'ZFP_PhotoSettings');

		add_settings_section('ZFP_PhotoSetSettings', 'Gallery and Collection Settings',array('ZenfolioPress','photoSetSettingsHTML'), 'ZenfolioPress');
		add_settings_field('thumbSize', 'Default Thumbnail Size', array('ZenfolioPress','thumbSizeHTML'), 'ZenfolioPress', 'ZFP_PhotoSetSettings');
	}

	public static function zenfolioSettingsHTML() {
		//echo '<p>Settings for accessing your Zenfolio account.</p>';
	}

	public static function userNameHTML() {
		$options = self::getOptions();
		echo "<input id='userName' name='ZFP_Settings[userName]' size='40' type='text' value='{$options['userName']}' />";
	}

	public static function photoSettingsHTML() {
		//echo '<p>Default settings for photos</p>';
	}

	public static function photoSetSettingsHTML() {
		//echo '<p>Default settings for galleries and collections</p>';
	}

	public function photoSizeHTML() {
		$options = self::getOptions();
		self::selectSizeHTML('photoSize', $options['photoSize']);
	}

	public function thumbSizeHTML() {
		$options = self::getOptions();
		self::selectSizeHTML('thumbSize', $options['thumbSize']);
	}

	public static function selectSizeHTML($name,$value) {
		echo "<select id='$name' name='ZFP_Settings[$name]'>\n";
		echo "<option value=\"0\"".($value == '0'?'selected':'').">Small thumbnail (up to 80 x 80)</option>\n";
		echo "<option value=\"1\"".($value == '1'?'selected':'').">Square thumbnail (60 x 60, cropped square)</option>\n";
		echo "<option value=\"10\"".($value == '10'?'selected':'').">Medium thumbnail (up to 120 x 120)</option>\n";
		echo "<option value=\"11\"".($value == '11'?'selected':'').">Large thumbnail (up to 120 x 120)</option>\n";
		echo "<option value=\"2\"".($value == '2'?'selected':'').">Small (up to 400 x 400)</option>\n";
		echo "<option value=\"3\"".($value == '3'?'selected':'').">Medium (up to 580 x 450)</option>\n";
		echo "<option value=\"4\"".($value == '4'?'selected':'').">Large (up to 800 x 630)</option>\n";
		echo "<option value=\"5\"".($value == '5'?'selected':'').">X-Large (up to 1100 x 850)</option>\n";
		echo "<option value=\"6\"".($value == '6'?'selected':'').">XX-Large (up to 1550 x 960)</option>\n";
		echo "</select>\n";
	}

	public static function validateOptions($input) {
		$options = self::getOptions();

		/* whitelist input */
		foreach ($input as $option=>$value) {
			switch ($option) {
				case 'userName':
					$valid[$option] = trim($value);
					break;
				case 'photoSize':
					if(in_array($value, array('0','1','2','3','4','5','6','10','11'))) {
						$valid[$option] = $value;
					}
					break;
				case 'thumbSize':
					if(in_array($value, array('0','1','2','3','4','5','6','10','11'))) {
						$valid[$option] = $value;
					}
					break;
			}
		}

		/* Validate the user */
		require_once('Zenfolio.php');
		$zenfolio = new Zenfolio();
		$publicProfile = $zenfolio->loadPublicProfile($valid['userName']);
		if($publicProfile === false) {
			//send_message('Cannot connect to Zenfolio',E_USER_ERROR);
			$valid['userName'] = '';
		}

		self::$options = $valid;

		return $valid;
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
		$src = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
		return "<img id=\"$id\" src=\"$src\"/>";
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
		$tableStyle = self::getFrameTableStyle($size);
		$cellStyle = self::getFrameCellStyle($size);

		$html = '';
		if(is_array($photos) && count($photos)) {
			$html.= "<div class=\"zfp_gallery\">\n";
			foreach ($photos as $photo) {
				$src = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
				//$link = $photo->PageUrl;
				$link =  $photoSet->PageUrl.substr($photo->PageUrl,strrpos($photo->PageUrl,'/'));
				$title = $photo->Title;
				if($photo->Id == $photoSet->TitlePhoto->Id) {
					$titleSrc = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-11.jpg?sn='.$photo->Sequence;
				}
				$html.= "<table class=\"zfpFrame\" style=\"$tableStyle\">\n";
				$html.= "<tr class=\"zfpFrame\">\n";
				$html.= "<td class=\"zfpFrame\" style=\"$cellStyle\">\n";
				$html.= "<a class=\"zfpFrame\" href=\"$link\" >\n";
				$html.= "<img src=\"$src\"/>\n";
				$html.= "</a>\n";
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