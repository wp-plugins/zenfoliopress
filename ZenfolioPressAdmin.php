<?php
class ZenfolioPressAdmin {

	public static function adminMenu() {
		add_options_page('ZenfolioPress Options', 'ZenfolioPress', 'manage_options', 'zfp_options', array('ZenfolioPressAdmin','showOptions'));
	}

	public static function linkActionHTML($name,$value,$isPhotoSet = false) {
		echo "<select id='$name' name='ZFP_Settings[$name]'>\n";
		echo "<option value=\"0\"".($value == '0'?'selected':'').">None</option>\n";
		if($isPhotoSet) {
			echo "<option value=\"2\"".($value == '2'?'selected':'').">Open photo in Zenfolio collection</option>\n";
		}
		echo "<option value=\"1\"".($value == '1'?'selected':'').">Open photo in Zenfolio gallery</option>\n";
		echo "</select>\n";
	}

	public static function linkTargetHTML($name,$value) {
		echo "<select id='$name' name='ZFP_Settings[$name]'>\n";
		echo "<option value=\"_self\"".($value == '_self'?'selected':'').">Same window</option>\n";
		echo "<option value=\"_blank\"".($value == '_blank'?'selected':'').">New window</option>\n";
		echo "</select>\n";
	}

	public static function photoActionHTML() {
		$options = ZenfolioPress::getOptions();
		self::linkActionHTML('photoAction',$options['photoAction']);
	}

	public static function photoSetSettingsHTML() {
		//echo '<p>Default settings for galleries and collections</p>';
	}

	public static function photoSettingsHTML() {
		//echo '<p>Default settings for photos</p>';
	}

	public function photoSizeHTML() {
		$options = ZenfolioPress::getOptions();
		self::selectSizeHTML('photoSize', $options['photoSize']);
	}

	public static function photoTargetHTML() {
		$options = ZenfolioPress::getOptions();
		self::linkTargetHTML('photoTarget',$options['photoTarget']);
	}

	public static function registerSettings() {
		register_setting( 'ZFP_Settings', "ZFP_Settings",array('ZenfolioPressAdmin','validateOptions') );
		add_settings_section('ZFP_ZenfolioSettings', 'Zenfolio Settings',array('ZenfolioPressAdmin','zenfolioSettingsHTML'), 'ZenfolioPress');
		add_settings_field('userName', 'Zenfolio User Name', array('ZenfolioPressAdmin','userNameHTML'), 'ZenfolioPress', 'ZFP_ZenfolioSettings');


		add_settings_section('ZFP_PhotoSettings', 'Default Photo Settings',array('ZenfolioPressAdmin','photoSettingsHTML'), 'ZenfolioPress');
		add_settings_field('photoSize', 'Photo Size', array('ZenfolioPressAdmin','photoSizeHTML'), 'ZenfolioPress', 'ZFP_PhotoSettings');
		add_settings_field('photoAction', 'Photo Link Action', array('ZenfolioPressAdmin','photoActionHTML'), 'ZenfolioPress', 'ZFP_PhotoSettings');
		add_settings_field('photoTarget', 'Photo Link Target', array('ZenfolioPressAdmin','photoTargetHTML'), 'ZenfolioPress', 'ZFP_PhotoSettings');

		add_settings_section('ZFP_PhotoSetSettings', 'Default Gallery and Collection Settings',array('ZenfolioPressAdmin','photoSetSettingsHTML'), 'ZenfolioPress');
		add_settings_field('thumbSize', 'Thumbnail Size', array('ZenfolioPressAdmin','thumbSizeHTML'), 'ZenfolioPress', 'ZFP_PhotoSetSettings');
		add_settings_field('thumbAction', 'Thumbnail Link Action', array('ZenfolioPressAdmin','thumbActionHTML'), 'ZenfolioPress', 'ZFP_PhotoSetSettings');
		add_settings_field('thumbTarget', 'Thumbnail Link Target', array('ZenfolioPressAdmin','thumbTargetHTML'), 'ZenfolioPress', 'ZFP_PhotoSetSettings');
	}

	public static function selectSizeHTML($name,$value) {
		echo "<select id='$name' name='ZFP_Settings[$name]'>\n";
		echo "<option value=\"0\"".($value == '0'?'selected':'').">Small thumbnail (up to 80 x 80)</option>\n";
		echo "<option value=\"1\"".($value == '1'?'selected':'').">Square thumbnail (60 x 60, cropped square)</option>\n";
		echo "<option value=\"10\"".($value == '10'?'selected':'').">Medium thumbnail (up to 120 x 120)</option>\n";
		echo "<option value=\"11\"".($value == '11'?'selected':'').">Large thumbnail (up to 200 x 200)</option>\n";
		echo "<option value=\"2\"".($value == '2'?'selected':'').">Small (up to 400 x 400)</option>\n";
		echo "<option value=\"3\"".($value == '3'?'selected':'').">Medium (up to 580 x 450)</option>\n";
		echo "<option value=\"4\"".($value == '4'?'selected':'').">Large (up to 800 x 630)</option>\n";
		echo "<option value=\"5\"".($value == '5'?'selected':'').">X-Large (up to 1100 x 850)</option>\n";
		echo "<option value=\"6\"".($value == '6'?'selected':'').">XX-Large (up to 1550 x 960)</option>\n";
		echo "</select>\n";
	}

	public static function showOptions() {
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		include 'options.html';
	}

	public static function thumbActionHTML() {
		$options = ZenfolioPress::getOptions();
		self::linkActionHTML('thumbAction',$options['thumbAction'],true);
	}

	public function thumbSizeHTML() {
		$options = ZenfolioPress::getOptions();
		self::selectSizeHTML('thumbSize', $options['thumbSize']);
	}

	public static function thumbTargetHTML() {
		$options = ZenfolioPress::getOptions();
		self::linkTargetHTML('thumbTarget',$options['thumbTarget']);
	}

	public static function userNameHTML() {
		$options = ZenfolioPress::getOptions();
		echo "<input id='userName' name='ZFP_Settings[userName]' size='40' type='text' value='{$options['userName']}' />";
	}

	public static function validateOptions($input) {
		$options = ZenfolioPress::getOptions();

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
				case 'photoAction':
					if(in_array($value, array('0','1'))) {
						$valid[$option] = $value;
					}
					break;
				case 'photoTarget':
					if(in_array($value, array('_self','_blank'))) {
						$valid[$option] = $value;
					}
					break;
				case 'thumbSize':
					if(in_array($value, array('0','1','2','3','4','5','6','10','11'))) {
						$valid[$option] = $value;
					}
					break;
				case 'thumbAction':
					if(in_array($value, array('0','1','2'))) {
						$valid[$option] = $value;
					}
					break;
				case 'thumbTarget':
					if(in_array($value, array('_self','_blank'))) {
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
		$valid['userName'] = $publicProfile->LoginName;

		//self::$options = $valid;

		return $valid;
	}

	public static function zenfolioSettingsHTML() {
		//echo '<p>Settings for accessing your Zenfolio account.</p>';
	}
}