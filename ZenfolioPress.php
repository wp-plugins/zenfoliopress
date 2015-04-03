<?php
/*
 Plugin Name: ZenfolioPress
 Plugin URI: http://zenfoliopress.com
 Description: Integrate Zenfolio images and galleries with Word Press.
 Version: 0.1.6
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
	add_filter('widget_text', 'do_shortcode', 11,2);
	add_shortcode('ZFP_Photo', array('ZenfolioPress','showPhoto'));
	add_shortcode('ZFP_PhotoSet',array('ZenfolioPress','showPhotoSet'));
}

class ZenfolioPress {
	const STYLE_VERSION = 'v015'; //3
	const LIGHTBOX_STYLE_VERSION = 'v2.04';
	const LIGHTBOX_VERSION = 'v2.05z';
	private static $options = null;
	private static $sequence = null;
	//private static $sizes = null; not sure why it's here
	private static $sizes = array(
		'0'=>array('w'=>80,'h'=>80),
		'1'=>array('w'=>60,'h'=>60),
		'2'=>array('w'=>400,'h'=>400),
		'3'=>array('w'=>580,'h'=>450),
		'4'=>array('w'=>800,'h'=>630),
		'5'=>array('w'=>1100,'h'=>850),
		'6'=>array('w'=>1550,'h'=>960),
		'10'=>array('w'=>120,'h'=>120),
		'11'=>array('w'=>200,'h'=>200));
	private static $squares = array(
		'0'=>array('h'=>53,'w'=>53),
		'1'=>array('h'=>40,'w'=>40),
		'2'=>array('h'=>267,'w'=>267),
		'3'=>array('h'=>387,'w'=>300),
		'4'=>array('h'=>533,'w'=>420),
		'5'=>array('h'=>733,'w'=>567),
		'6'=>array('h'=>960,'w'=>640),
		'10'=>array('h'=>80,'w'=>80),
		'11'=>array('h'=>133,'w'=>133));

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

			/* update defaults with current options */
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
	
	/**
	 * Get the next sequence number for a photo or photoset
	 * 
	 * Sequence numbers are used so the same photo or photoset can
	 * be used multiple times within the same page.
	 * 
	 * @param String $id The id for the Zenfolio resource being referenced.
	 */
	public static function getSequence($id) {
		if(self::$sequence === null || !isset(self::$sequence[$id])) {
			self::$sequence[$id]=0;
		} else {
			self::$sequence[$id] = self::$sequence[$id]+1;
		}
		return self::$sequence[$id];
	}

	public static function loadScripts() {
		wp_enqueue_script('jquery');
		$url = plugins_url('slimbox2.js', __FILE__);
		$fileName = WP_PLUGIN_DIR . '/zenfoliopress/slimbox2.js';
		if ( file_exists($fileName) ) {
			wp_register_script('zfp_slimbox2', $url,false,self::LIGHTBOX_VERSION);
			wp_enqueue_script( 'zfp_slimbox2');
		}
	}

	public static function loadStyleSheets() {
		/* load ZenfolioPress style sheet */
		$url = plugins_url('zfp.css', __FILE__);
		$fileName = WP_PLUGIN_DIR . '/zenfoliopress/zfp.css';
		if ( file_exists($fileName) ) {
			wp_register_style('zfp_style', $url,false,self::STYLE_VERSION);
			wp_enqueue_style( 'zfp_style');
		}

		/* load lightbox style sheet */
		$url = plugins_url('slimbox2.css', __FILE__);
		$fileName = WP_PLUGIN_DIR . '/zenfoliopress/slimbox2.css';
		if ( file_exists($fileName) ) {
			wp_register_style('zfp_slimbox2', $url,false,self::LIGHTBOX_STYLE_VERSION);
			wp_enqueue_style( 'zfp_slimbox2');
		}
	}

	public static function showPhoto($params) {
		$options = self::getOptions();
		$defaults = array(
			'id' => '',
			'size' => $options['photoSize'],
			'action' => $options['photoAction'],
			'box_size' => $options['lightBoxSize'],
			'link_target' => $options['photoTarget']);
		extract(shortcode_atts($defaults, $params));
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
		if(empty($photo)) {
			return "<!-- photo $id could not be loaded by ZenfolioPress -->";
		}

		if($action == '3') {
			$link = 'http://'.$photo->UrlHost.$photo->UrlCore.'-'.$box_size.'.jpg?sn='.$photo->Sequence;
		} else {
			$link = $photo->PageUrl;
		}
		$lightbox = $action == '3' ? 'rel="zfpLightbox-p'.$id.'"' : '';
		$title = $photo->Title ? 'title="'.htmlspecialchars($photo->Title).'"' : '';
		$target = $action < '3' ? 'target="'.$link_target.'"' : '';
		$src = 'http://'.$photo->UrlHost.'/'.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;

		$html = '';
		if($action > '0') {
			$html.= "<a href=\"$link\" $lightbox $title $target>";
		}
		$html.= "<img id=\"$id\" src=\"$src\"/>";
		if($action > '0') {
			$html.= "</a>";
		}
		return $html;
	}

	public static function showPhotoSet($params) {
		$options = self::getOptions();
		$defaults = array(
			'id' => '',
			'size' => $options['thumbSize'],
			'padding' => $options['thumbPadding'],
			'action' => $options['thumbAction'],
			'box_size' => $options['lightBoxSize'],
			'box_title' => $options['lightBoxTitle'],
			'link_target' => $options['thumbTarget']);
		extract(shortcode_atts($defaults, $params));
		/* trim off any additional characters the user might have included */
		$id = preg_replace("/[^0-9,.]/", "", $id);

		/* retrieve the PhotoSet data from zenfolio */
		require_once('Zenfolio.php');
		$zenfolio = new Zenfolio();
		$includeDetails = $box_title=='Caption' ? 2 : 1;
		$photoSet = $zenfolio->loadPhotoSet($id,'LEVEL2',$includeDetails);
		if(empty($photoSet)) {
			return "<!-- photoset $id could not be loaded by ZenfolioPress -->";
		}
		$photos = $photoSet->Photos;
		if(empty($photos)) {
			return "<!-- photoset $id does not contain any public photos -->";
		}
		if(is_numeric($size)) {
			$square = false;
			$fw = self::$sizes[$size]['w']+(2*$padding);  // frame width
			$fh = self::$sizes[$size]['h']+(2*$padding);  // frame height
		} else {
			$size = substr($size,1);
			$square=true;
			$fw = self::$squares[$size]['w'];  // frame width
			$fh = self::$squares[$size]['h'];  // frame height
		}

		$style = "style=\"width:{$fw}px; height:{$fh}px; margin:{$padding}px;\"";

		$target = $action < '3' ? 'target="'.$link_target.'"' : '';
		$id_seq = $id.'_'.self::getSequence($id);
		$lightbox = $action == '3' ? 'rel="zfpLightbox-ps'.$id_seq.'"' : '';
		$lightBoxSize = $box_size;

		$html = '';
		if(is_array($photos) && count($photos)) {
			$html.= "<div id=\"zfp_photoset_$id_seq\" class=\"zfp_photoset\">\n";
			foreach ($photos as $photo) {
				$src = 'http://'.$photo->UrlHost.$photo->UrlCore.'-'.$size.'.jpg?sn='.$photo->Sequence;
				switch ($action) {
					case '2':
						$link = $photoSet->PageUrl.substr($photo->PageUrl,strrpos($photo->PageUrl,'/'));
						break;
					case '3':
						$link = 'http://'.$photo->UrlHost.$photo->UrlCore.'-'.$lightBoxSize.'.jpg?sn='.$photo->Sequence;
						break;
					default:
						$link = $photo->PageUrl;
				}
				switch($box_title) {
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
				if($square) {
					if($photo->Height/$photo->Width > 1) {
						$height = self::$sizes[$size]['h'];
						$top=0-round(($height-$fh)/3);
						$right = $width = self::$sizes[$size]['w'];
						$bottom=$top+$fh;
						$left=0;
						//$img_style =  "style=\"clip: rect({$top}px, {$right}px, {$Bottom}px, {$left}px);\"";
						$img_style =  "style=\"left:{$left}px; top:{$top}px;\"";
					} else {
						$width = self::$sizes[$size]['w'];
						$top=0;
						$bottom=$fh;
						$left = 0-round(($width-$fw)/2);
						$right = $left + $fw;
						$img_style =  "style=\"left:{$left}px; top:{$top}px; max-width:{$width}px;\"";
						//$img_style = "style=\"max-width:{$width}px; clip: rect({$top}px, {$right}px, {$Bottom}px, {$left}px);\"";
					}
					$html.= "<div class=\"zfp_box\" $style>\n";
					//echo "<div id=\"".'P'.$photo->Id."\" class=\"zfp_dimmed\" $style></div>\n";
					if($action > 0) {
						$html.= "<a class=\"zfp_box\" $style href=\"$link\"  $lightbox $title $target>\n";
					}
					$html.= "<img src=\"$src\" class=\"zfp_box\" $img_style $alt>\n";
					if($action > 0) {
						$html.= '</a>';
					}
					$html.= "</div>\n";
				} else {
					$html.= "<table class=\"zfp_frame\" $style>\n";
					$html.= "<tr class=\"zfp_frame\">\n";
					$html.= "<td class=\"zfp_frame\">\n";
					if($action > 0) {
						$html.= "<a class=\"zfp_frame\" href=\"$link\" $lightbox $title $target>\n";
					}
					$html.= "<img src=\"$src\" $alt >\n";
					if($action > 0) {
						$html.= "</a>\n";
					}
					$html.= "</td>\n";
					$html.= "</tr>\n";
					$html.= "</table>\n";
				}
			}
			$html.= "</div> <!-- /zfp_photoset -->\n";
		}

		return $html;
	}
}
?>
