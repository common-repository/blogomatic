=== Plugin Name ===

Contributors: Mohammad Hossein Aghanabi
Tags: automatic, blogroll, ajax, link, exchanging, exchange, blogomatic
Requires at least: 2.7
Tested up to: 3.0.4
Stable tag: trunk

blogomatic is a handy wordpress plugin that will make a automatic link exchange tool as a widget on your blog.

== Description ==

*  it's based on alexa traffic or pagerank.
*  and user website at least should match with one of those above, in that way plugin will do the exchanging.
*  after a new exchanging that link is not allowed to be added again till it has been removed by admin

== Installation ==

1. Upload `blogomatic` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `<script type="text/javascript" src="http://www.your-website.com/wp-content/plugins/blogomatic/qstring.js"></script>
` in your template header.php file, in the head section.
4. Go to widget section of your wordpress, drag blogomatic widget to a sidebar.
5. do the required options.
6. that's all ! :)

== Changelog ==

= 1.0 =
* plugin checks for validity of given url
* compatible with unicode
* with an ajax interface
* for security reasons all links should include "http://www." at the first, otherwise plugin gives a warning.