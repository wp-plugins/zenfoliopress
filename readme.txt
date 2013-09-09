=== ZenfolioPress ===
Contributors: nusbaum
Donate link: http://zenfoliopress.com/
Tags: zenfolio, photo, gallery, photo gallery, photo collection, thumbnails
Requires at least: 2.7
Tested up to: 3.6
Stable tag: 0.1.5

The ZenfolioPress plugin allows users to easily incorporate 
their Zenfolio hosted photos, galleries and collections into 
their WordPress site.

== Description ==

The ZenfolioPress plugin for WordPress allows users to easily incorporate their 
[Zenfolio](http://www.zenfolio.com) hosted photos, galleries and collection into their 
WordPress site.

== Installation ==

1. Extract the contents of the zip archive to the `/wp-content/plugins/` directory or 
install the plugin from your WordPress dashboard -> plugins -> add new menu
1. Activate the plugin through the 'Plugins menu' in WordPress
1. Configure the plugin using the ZenfoliioPress settings page

= Displaying Photos =

Insert the code **[ZFP_Photo id='nnnn']** to display a photo from your Zenfolio account. 
The photo id can be found at the end of the URL displayed in your web browser.

> http:&#47;&#47;www.davidnusbaum.com/individuals/h6913aba#__h6913aba__

= Displaying Galleries and Collections =

Insert the code **[ZFP_PhotoSet id='nnnn']** to display a Zenfolio gallery or collection. 
The id can be found at the end of the URL displayed in your web browser.

> http:&#47;&#47;www.davidnusbaum.com/p**444438099**

If you have a friendly URL for a gallery or collection you can find the id by going to the edit view.

> http:&#47;&#47;www.zenfolio.com/nusbaum/p**444438099**/edit

= Short Code Options =
**id**  
This is the photo or photo set id and is described above because it is always required.

**size**  
This is the size of the photo or thumbnail that is displayed on the page or post. The
options are based on those provided by Zenfolio.  

* '0'	Small thumbnail (up to 80 x 80)
* '1'	Square thumbnail (60 x 60, cropped square)
* '10'	Medium thumbnail (up to 120 x 120)
* '11'	Large thumbnail (up to 200 x 200)
* '2'	Small (up to 400 x 400)
* '3'	Medium (up to 580 x 450)
* '4'	Large (up to 800 x 630)
* '5'	X-Large (up to 1100 x 850)
* '6'	XX-Large (up to 1550 x 960)

**padding**  
This is the amount of padding, in pixels, that is placed around an image in a photo set.
The value should be a number with no trailing 'px'.

**action**  
This is the action that will occur when a view clicks on an image.

* '0' Nothing will happen.
* '3' Open the photo in a lightbox.
* '2' Open the photo in the Zenfolio collection it came from. (only applies to photosets)
* '1' Open the photo in the Zenfolio gallery it came from.

**link_target**  
This defines how a photo will link to a Zenfolio collection or gallery.

* '_self' The collection or gallery is opened in the same browser window.
* '_blank' The collection or gallery is opened in a new browser window.

**box_size**   
This defines the size of the photo that will appear in the lightbox. The options are
the same as described for the size option.

**box_title**
The defines the photo title that is included with the lightbox.

* 'None' No title is displayed.
* 'Title' The photo title from Zenfolio is displayed.
* 'Caption' The photo caption from Zenfolio is displayed.

== Frequently Asked Questions ==

= Can I have password protected galleries with ZenfolioPress =

http://zenfoliopress.com/2013/08/25/protected-galleries-using-zenfoliopress/


== Screenshots ==
Will add soon...

== Changelog ==
= 0.1.5 =
Added the ability to set options for each photo or photoset uniquely with short code parameters.

= 0.1.3 = 
Photo, as well as photoset, lightbox now uses the default image size. Changed the "rel" attribute so the ZenfolioPress use of Slimbox does not conflict with the Slimbox plugin.
 
= 0.1.2 =
Fixed a bug that affected how titles and captions are set for photosets.

= 0.1.1 =
Quick CSS fix for themes that tie tr and td attributes to #content.

= 0.1.0 =
Fixed the space allocated for square thumbnails.

= 0.0.9 =
Added new configuration options for spacing around thumbnails and using captions rather than titles 
for the lightbox.

= 0.0.8 =
Adjusted CSS to keep the background behind each thumbnail transparent.

= 0.0.7 = 
Minor changes to selectors in the CSS file.

= 0.0.6 =
Integrated an optional lightbox presentation for photos, gallaries and collections. This presentation utilizes the 
[slimbox2](http://www.digitalia.be/software/slimbox2) jquery plugin by Christophe Beyls.

= 0.0.5 =
Cleaned up the style sheet so inline styles aren't required. This should make it easier for users who want to use
ZenfolioPress with their own styles. Continued testing with more themes to make sure there are no conflicts.

= 0.0.4 =
Added the ability to configure the link actions for photos and gallery thumbnails.

= 0.0.3 =
As expected, deployment from the WordPress plugin directory had some minor impacts. Upated the path to the CSS file,
but will look for a more dynamic way to get the path in the future.

= 0.0.2 =
Update to fix creating settings for new installs. You'll need this to configure ZenfolioPress.

= 0.0.1 =
An alpha level release published for testing and getting input from potential users.

== Upgrade Notice ==
= 0.0.8 =
Minor CSS update to force a transparent background behind each thumbnail.

= 0.0.7 = 
Should prevent having a single column of thumbnails is certain themes.

= 0.0.6 =
Integrated an optional lighbox presentation, similar to lights out, when an image is selected. Go to the options
page and give it a try.

= 0.0.5 =
No functional changes, but removed the inline CSS so users have more flexibility when modifying the look of galleries 
and collections.

= 0.0.4 =
This upgrade will allow you to configure the link actions for photos as well as thumbnails for galleries and collections.

= 0.0.3 =
Required to correctly load the style sheet for the plugin.

= 0.0.2 ==
Update to fix creating settings for new installs. You'll need this to configure ZenfolioPress.

== To Do ==

1. Add clear overlay gif to at least slow down efforts to right click and save your images.
1. Insert a slideshow into a blog entry
1. Strong call to action in a form of a “Buy Prints” or “Buy Products” button which will transition into the 
buying experience.
