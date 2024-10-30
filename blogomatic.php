<?php 
/*
Plugin Name: Blogomatic
Plugin URI: http://www.websoftdownload.com/
Description: Blogomatic is a handy plugin for creating an automatic blogroll based on url alexa traffic and pagerank
Author: Mohammad Hossein Aghanabi
Version: 1.0
Author URI: http://www.websoftdownload.com/
Author EMAIL : m.websoft@gmail.com
*/
require_once($_SERVER['DOCUMENT_ROOT'].'/wp-config.php' );
mysql_query("SET CHARACTER SET utf8");
mysql_query("SET NAMES utf8"); 
$plugin_path = dirname(plugin_basename(__FILE__)); 
load_plugin_textdomain( 'WSDBR', false, $plugin_path );
	if($_POST['send_button']) { 
		class GooglePR { 
			function StrToNum($Str, $Check, $Magic) { 
				$Int32Unit = 4294967296; 
				$length = strlen($Str); 
				for ($i = 0; $i < $length; $i++) 
					{ $Check *= $Magic; if ($Check >= $Int32Unit) 
						{ $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit)); 
							$Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check; }
							$Check += ord($Str{$i}); } return $Check; } 
			function HashURL($String) { 
				$Check1 = $this->StrToNum($String, 0x1505, 0x21);
				$Check2 = $this->StrToNum($String, 0, 0x1003F);
				$Check1 >>= 2; $Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
				$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
				$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);
				$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
				$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
				return ($T1 | $T2); } 

			function CheckHash($Hashnum) { 
				$CheckByte = 0; $Flag = 0;
				$HashStr = sprintf('%u', $Hashnum) ; 
				$length = strlen($HashStr);
				for ($i = $length - 1; $i >= 0; $i --) { 
					$Re = $HashStr{$i};
						if (1 === ($Flag % 2)) { 
							$Re += $Re;
							$Re = (int)($Re / 10) + ($Re % 10);
							} 
				$CheckByte += $Re; $Flag ++; }
				$CheckByte %= 10;
					if (0 !== $CheckByte) { 
						$CheckByte = 10 - $CheckByte;
						if (1 === ($Flag % 2) ) {
							if (1 === ($CheckByte % 2)) { 
								$CheckByte += 9; } 
							$CheckByte >>= 1; } 
						}
				 return '7' . $CheckByte . $HashStr;
			 } 

			function getPagerank($url) {
				$query = "http://toolbarqueries.google.com/search?client=navclient-auto&ch=" . $this->CheckHash($this->HashURL($url)) . "&features=Rank&q=info:" . $url . "&num=100&filter=0"; 
				$data = $this->file_get_contents_curl($query);
				$pos = strpos($data, "Rank_");
					if($pos !== false){
						$pagerank = substr($data, $pos + 9);
						return trim($pagerank);
					 }
			}
			
			function file_get_contents_curl($url) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $url);
				$data = curl_exec($ch); curl_close($ch); return $data; 
			} 
		}

		Class Alexa { 

			function getAlexaRank($domain) {
				$remote_url = 'http://data.alexa.com/data?cli=10&dat=snbamz&url=' . trim($domain);
				$xml = simplexml_load_file($remote_url);
					if(isset($xml->SD[1]->POPULARITY['TEXT'])){ 
						return $xml->SD[1]->POPULARITY['TEXT'];
					 } else { return 0; } 
				} 
			} 

			function Result($url, $name) { 
				if(!empty($url)) {
					 if(stristr($_POST['id'], 'http://www.')) {
						 if(preg_match('/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/', $url)) {
		 if(!empty($name)) {
			$gpr = new GooglePR();
			$pagerank = $gpr->getPagerank($url);
			$alexa = new Alexa;
			$alexarank = $alexa->getAlexaRank($url);
			$ch = curl_init();
			$timeout = 5;
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file = curl_exec($ch); curl_close($ch);
			if(strpos($file, $_POST['url'])) { 
				if($pagerank >= $_POST['pr'] || $alexarank <= $_POST['ax'] && $pagerank != '') {
					_e("Pagerank : ",WSDBR); echo $pagerank."<br />";
					_e("Alexa Traffic : ",WSDBR); echo $alexarank."<br />";
					mysql_query("INSERT INTO wp_links (link_id,link_url,link_name,link_image,link_target,link_description,link_visible,link_owner,link_rating,link_updated,link_rel,link_notes,link_rss) 
					VALUES ('','$url','$name','','','','Y','1','0','0000-00-00 00:00:00','','','')") or die('Error:'.mysql_error());
					_e("Added.",WSDBR);
				} else { _e("Sorry, can't be added.",WSDBR); }
			        } else { _e("Please add our url first<br />",WSDBR); echo $_POST['url']; }
		               } else { _e("Name field is empty",WSDBR);}
	                     } else {_e("URL is not valid.",WSDBR);} 
                           } else { _e("http://www is required.<br />",WSDBR); } 
                } else { _e("URL is empty",WSDBR); } 
   }
	$url = $_POST['id'];
	$name = $_POST['desc'];
	$sql = mysql_query("SELECT * FROM wp_links") or die('Error:'.mysql_error());
	$num_rows = mysql_num_rows($sql); if($num_rows >0) { 
		if($num_rows <= $_POST['li']) { 
			if(!empty($_POST['id'])) { 
				if(stristr($_POST['id'], 'http://www.')) { 
			while($r=mysql_fetch_array($sql)) { 
				preg_match('@^(?:http://)?([^/]+)@i', $url, $match);
				$get_link = $match[1];
					if(stristr($r['link_url'],$get_link)) { 
						_e('URL existed', 'WSDBR'); break;
					 }
				 }
			if(!stristr($r['link_url'],$get_link)) {
				 if(preg_match('/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/', $url)) {
					 if(!empty($name)) { 
						$gpr = new GooglePR();
						$pagerank = $gpr->getPagerank($url);
						$alexa = new Alexa;
						$alexarank = $alexa->getAlexaRank($url);
						$ch = curl_init();
						$timeout = 5;
						curl_setopt ($ch, CURLOPT_URL, $url);
						curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
						$file = curl_exec($ch);
						curl_close($ch);
							if(strpos($file, $_POST['url'])) {
								 if($pagerank >= $_POST['pr'] || $alexarank <= $_POST['ax'] && $pagerank != '') { 
					_e("Pagerank : ",WSDBR); echo $pagerank."<br />";
					_e("Alexa Traffic : ",WSDBR); echo $alexarank."<br />";
						mysql_query("INSERT INTO wp_links (link_id,link_url,link_name,link_image,link_target,link_description,link_visible,link_owner,link_rating,link_updated,link_rel,link_notes,link_rss) 
						VALUES ('','$url','$name','','','','Y','1','0','0000-00-00 00:00:00','','','')") or die('Error:'.mysql_error());
						_e("Added.",WSDBR); 
					       } else { _e("Sorry, can't be added.",WSDBR); }
				             } else { _e("Please add our url first<br />",WSDBR); echo $_POST['url']; } 
			                  } else { _e("Name field is empty",WSDBR);}
		                       } else { _e("URL is not valid.",WSDBR); } 
	                               }
	                      } else { _e("http://www is required.<br />",WSDBR); } 
	               } else { _e("URL is empty",WSDBR); }
	       } else { _e("List is Full",WSDBR); } 
            } else { Result($url, $name); } 
 } else { 
		function WSDBlogRoll() { 
			$checker = plugin_dir_url(__FILE__)."blogomatic.php";
			$sql = mysql_query("SELECT * FROM wp_links ORDER BY link_id DESC") or die('Error:'.mysql_error());
			 while($r=mysql_fetch_array($sql)) { 
					echo "<a href=\"".$r['link_url']."\">".$r['link_name']."</a><br />"; } 
			_e("Base Alexa Traffic : ",WSDBR); echo get_option("ax")."<br />";
			_e("Base PageRank : ",WSDBR); echo get_option("pr")."<br />";
			_e("One of Two above is needed",WSDBR)."<br />"; 
			echo "<form id=\"form\" name=\"yid\" style=\"text-align:center\" method=\"post\" onsubmit=\"xmlhttpPost('".$checker."', 'yid', 'form', '".get_option("la")."'); return false;\">
			<input type=\"text\" class=\"id\" name=\"id\" id=\"id\" style=\"text-align: left; font-family: Tahoma; font-size:8pt; direction: ltr; margin-bottom: 5px; margin-top:5px;\" value=\"".__("Enter your link",WSDBR)."\" size=\"20\" onkeypress=\"return event.keyCode!=13\"/>	
			<br /><input type=\"text\" class=\"desc\" name=\"desc\" id=\"desc\" style=\"text-align: left; font-family: Tahoma; font-size:8pt; direction: ltr; margin-bottom: 5px;\" value=\"".__("Enter url title",WSDBR)."\" size=\"20\" onkeypress=\"return event.keyCode!=13\"/>
			<input type=\"hidden\" name=\"pr\" value=\"".get_option("pr")."\" />
			<input type=\"hidden\" name=\"ax\" value=\"".get_option("ax")."\" />
			<input type=\"hidden\" name=\"url\" value=\"".get_option("url_ch")."\" />
			<input type=\"hidden\" name=\"li\" value=\"".get_option("li")."\" /> <br />
			<input name=\"send_button\" type=\"submit\" value=\"".__("Send Link",WSDBR)."\" /> </form>";
			 } 

		function widget_WSDBR($args) { 
			extract($args);
			$options = get_option("widget_WSDBR");
			if (!is_array( $options )) {
				$options = array( 'name' => 'BlogRoll',
						 'url_ch' => 'Site URL',
						 'pr' => 'base pagerank',
						 'ax' => 'alexa traffic',
						 'la' => 'Please Wait', 'li' => '20'
						 );
			} 
			echo $before_widget;
			echo $before_title;
			echo get_option("name");
			echo $after_title;
			WSDBlogRoll();
			echo $after_widget; 
		} 
			
		function WSDBR_control() { 
			$options = get_option("widget_WSDBR");
			if (!is_array( $options )) {
				$options = array( 'name' => 'BlogRoll',
				 'url_ch' => 'Site URL',
				 'pr' => 'base pagerank',
				 'ax' => 'alexa traffic',
				 'la' => 'Please Wait', 'li' => '20' 
				);
		} 

		if ($_POST['WSDBR-Submit']) { 
			$options['name'] = htmlspecialchars($_POST['WSDBR-NAME']);
			$options['pr'] = htmlspecialchars($_POST['WSDBR-PR']);
			$options['ax'] = htmlspecialchars($_POST['WSDBR-AX']);
			$options['url_ch'] = htmlspecialchars($_POST['WSDBR-URL']);
			$options['la'] = htmlspecialchars($_POST['WSDBR-LA']);
			$options['li'] = htmlspecialchars($_POST['WSDBR-LI']);
			if(!add_option("name", $options['name']))
			add_option("name", $options['name']);
			if(!add_option("pr", $options['pr']))
			add_option("pr", $options['pr']);
			if(!add_option("ax", $options['ax']))
			add_option("ax", $options['ax']); 
			if(!add_option("url_ch", $options['url_ch'])) 
			add_option("url_ch", $options['url_ch']); 
			if(!add_option("la", $options['la'])) 
			add_option("la", $options['la']); 
			if(!add_option("li", $options['li'])) 
			add_option("li", $options['li']); 
			update_option("name", $options['name']); 
			update_option("pr", $options['pr']); 
			update_option("ax", $options['ax']); 
			update_option("url_ch", $options['url_ch']); 
			update_option("la", $options['la']); 
			update_option("li", $options['li']); 
			update_option("widget_WSDBR", $options); } 
?> 
<p> <label for="WSDBR-NAME"><?php _e("Widget Name : ",WSDBR); ?></label>
<input type="text" id="WSDBR-NAME" name="WSDBR-NAME" size="30" value="<?php echo $options['name'];?>" /><br />
<label for="WSDBR-URL"><?php _e("Site URL : ",WSDBR); ?></label>
<input type="text" id="WSDBR-URL" name="WSDBR-URL" size="30" value="<?php echo $options['url_ch'];?>" /><br />
<label for="WSDBR-PR"><?php _e("Base Pagerank : ",WSDBR); ?></label>
<input type="text" id="WSDBR-PR" name="WSDBR-PR" size="10" value="<?php echo $options['pr'];?>" /><br />
<label for="WSDBR-AX"><?php _e("Alexa Traffic : ",WSDBR); ?></label>
<input type="text" id="WSDBR-AX" name="WSDBR-AX" size="10" value="<?php echo $options['ax'];?>" /><br />
<label for="WSDBR-LA"><?php _e("Loading Message : ",WSDBR); ?></label>
<input type="text" id="WSDBR-LA" name="WSDBR-LA" size="20" value="<?php echo $options['la'];?>" /><br />
<label for="WSDBR-LI"><?php _e("List Limit. : ",WSDBR); ?></label>
<input type="text" id="WSDBR-LI" name="WSDBR-LI" size="20" value="<?php echo $options['li'];?>" />
<input type="hidden" id="WSDBR-Submit" name="WSDBR-Submit" value="1" /> </p> 
<?php }
	 function WSDBR_init() { 
		register_sidebar_widget(_('Blogomatic'), 'widget_WSDBR'); 
		register_widget_control( 'Blogomatic', 'WSDBR_control', 300, 200 ); 
	}
	add_action("plugins_loaded", "WSDBR_init"); }
?> 