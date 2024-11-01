<?php
/*
Plugin Name: WP-Planet
Plugin URI: http://czepol.info/#
Description: WP-PLANET
Author: czepol
Version: 0.1
Author URI: http://czepol.info/
*/
define('WP_PLANET_DB', 'wp_planet');
define('WP_POSTS_DB', 'wp_planet_posts');
define('WP_FEEDS_DB', 'wp_feeds');
register_activation_hook( __FILE__, 'install_wp_planet' );
add_action('admin_menu', 'wp_planet_menu');
require('planet.functions.php');
function install_wp_planet() {
	global $wpdb;
	if($wpdb->get_var("SHOW TABLES LIKE '".WP_PLANET_DB."'") != WP_PLANET_DB) {
	$sql = 	"CREATE TABLE ".WP_PLANET_DB." (
	`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`feed_title` VARCHAR( 50 ) NOT NULL ,
	`feed_url` VARCHAR( 150 ) NOT NULL ,
	`feed_description` TEXT NOT NULL ,
	`feed_category` INT NOT NULL ,
	`feed_manager` INT NOT NULL ,
	PRIMARY KEY ( `ID` )
	) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	}
	if($wpdb->get_var("SHOW TABLES LIKE '".WP_POSTS_DB."'") != WP_POSTS_DB) {
	$sql = "CREATE TABLE ".WP_POSTS_DB." (
	`ID` INT( 6 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`post_id` INT( 6 ) NOT NULL ,
	`source_service` INT( 3 ) NOT NULL ,
	`source_url` VARCHAR( 1000 ) NOT NULL ,
	`source_author` VARCHAR( 200 ) NOT NULL
	) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);		
	}
	if($wpdb->get_var("SHOW TABLES LIKE '".WP_FEEDS_DB."'") != WP_FEEDS_DB) {
	$sql = "CREATE TABLE ".WP_FEEDS_DB." (
	`ID` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`feed_id` INT( 10 ) NOT NULL ,
	`item_id` INT( 10 ) NOT NULL,
	`item_title` VARCHAR( 200 ) NOT NULL ,
	`item_url` VARCHAR( 500 ) NOT NULL ,
	`item_content` TEXT NOT NULL,
	`item_pub_date` DATETIME NOT NULL,
	`item_tags` text NOT NULL
	) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);		
	}
}

class Planet {
	
	function __constructor() {
	}
	
	function __destructor() {
	}
	
	public function feeds() {
	//Ta funkcja pobiera listę serwisów z odpowiedniej tabeli	
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM ".WP_PLANET_DB);
	}
	
	public function search_feed( $where = null ) {
	global $wpdb;
	if( $where == null ) {
		return $wpdb->get_results("SELECT * FROM ".WP_PLANET_DB);
	} 
	if( !is_array( $where ) ) {
		return $wpdb->get_results("SELECT * FROM ".WP_PLANET_DB." WHERE $where");	
	} else {
		$query = "SELECT * FROM ".WP_PLANET_DB." WHERE ";
		$x = count( $where );
		foreach($where as $key => $parameter) {
		$query.= " ".$key."=".addslashes($parameter)." ";
		if($x>1) { $query.=" AND "; }
		$x--;	
		}
		print_r($query);
		return $wpdb->get_results($query);	
	}
	}
	
	public function feed_by_id( $id ) {
	global $wpdb;
	if( !is_numeric( $id ) ) {
		return false;
	}
	return $wpdb->get_results("SELECT * FROM ".WP_PLANET_DB." WHERE ID = $id"); 
	}
	
	public function add_feed( $title, $url, $description, $category, $manager) {
	global $wpdb;
	$title = addslashes($title);
	$url = addslashes($url);
	$description = addslashes($description);
	$category = addslashes($category);
	$manager = addslashes($manager);
	$data = array(	'feed_title' => $title,
					'feed_url' => $url,
					'feed_description' => $description,
					'feed_category' => $category,
					'feed_manager' => $manager
				 );		
	return $wpdb->insert(WP_PLANET_DB, $data);	
	}
	
	public function edit_feed( $id, $title, $url, $description, $category, $manager ) {
	global $wpdb;
	if( !is_numeric( $id ) ) {
		return false;
		}
		
	$data = $wpdb->get_results("SELECT * FROM ".WP_PLANET_DB." WHERE ID=$id");
	
	$title = addslashes($title);
	$url = addslashes($url);
	$description = addslashes($description);
	$category = addslashes($category);
	$manager = addslashes($manager);
	$data = array(	'feed_title' => $title,
					'feed_url' => $url,
					'feed_description' => $description,
					'feed_category' => $category,
					'feed_manager' => $manager
				 );
	return $wpdb->update(WP_PLANET_DB, $data, array('ID'=>$id));		
	}
	
	public function delete_feed( $id ) {
	global $wpdb;
	if( !is_numeric( $id ) ) {
		return false;
	} else {
		return $wpdb->query("DELETE FROM ".WP_PLANET_DB." WHERE ID = ".$id);
	}
	}
} 
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
require_once('rss.class/rss_fetch.inc');

class RSS {
	
	function validate( $url ) {
		$rss = @simplexml_load_file($url);
		if(is_object($rss)) {
			return true;
		} else {
			return false;
		}
	}
	
	function get_feed( $url ) {
		return RSS::get_last_feed($url);
		if(RSS::validate($url) == true) {
		
		}
		
		else {
			return false;
		}
	}
	
	function feed_from_db( $data ) {
	
	}
	
	function feed_to_db( $data, $url ) {
	if(!(RSS::is_feed_exist_in_db($data)) || !(RSS::is_feed_edited($data))) {
		$feed = RSS::get_last_feed($url);
		global $wpdb;
		
	} else if(RSS::is_feed_edited($data)) {
	//Trzeba jeszcze raz pobrać RSSa (zaktualizować)
	//TODO	
	echo "Feed istnieje, ale był edytowany";
	} else {
		echo "Feed już zindeksowany";
		return false;
	}
	}
	
	function is_feed_exist_in_db( $data ) {
	//ID -> http://jakilinux.org/?p=2324324
	//ID = 2324324
	//Data 
	global $wpdb;
	$id = $data['id'];
	$date = $data['date'];
	$query = $wpdb->get_results("SELECT * FROM ".WP_FEEDS_DB." WHERE item_id ='".$id."' AND item_pub_date = '".$date."'");
	if(isset($query[0])) {
		return true;
	} else {
		return false;
	}
	
	}
	
	function is_feed_edited( $data ) {
	global $wpdb;
	$id = $data['id'];
	$date = $data['date'];
	$query = $wpdb->get_results("SELECT * FROM ".WP_FEEDS_DB." WHERE item_id ='".$id."' AND item_pub_date != '".$date."'");
	if(isset($query[0])) {
		return true;
	} else {
		return false;
	}	
	}
	
	function formate_date( $input ) {
		return date('Y-m-d H:i:s', strtotime($input));
	}
	
	function get_last_feed( $url ) {
		if(RSS::validate($url) == true) {
			$feed = simplexml_load_file($url);
			$item = $feed->channel->item;
			$title = $item->title;						//$title = Czy powinno się tworzyć aplikację na wiele systemów operacyjnych? – analiza przypadku komunikatora Kadu
			$link = $item->link;						//$link = http://jakilinux.org/aplikacje/komunikatory/czy-powinno-sie-tworzyc-aplikacje-na-wiele-systemow-operacyjnych-analiza-przypadku-komunikatora-kadu/
			$comments_link = $item->comments;			//$comments_link = http://jakilinux.org/aplikacje/komunikatory/czy-powinno-sie-tworzyc-aplikacje-na-wiele-systemow-operacyjnych-analiza-przypadku-komunikatora-kadu/#comments
			$date = $item->pubDate;						//$date = Tue, 09 Feb 2010 20:50:30 +0000
			$categories = $item->category;				//$categories = array(Komunikatory, Gnome, KDE, mac, usability);
			$permalink = $item->guid;					//$permalink = http://jakilinux.org/?p=104722
			$ns = array (
				‘content’ => "http://purl.org/rss/1.0/modules/content/",
				‘wfw’ => "http://wellformedweb.org/CommentAPI/",
				‘dc’ => "http://purl.org/dc/elements/1.1/"
			); 
			$content = $item->children($ns[‘content’]); //$content - sformatowany string
			$dc      = $item->children($ns[‘dc’]);		//$dc = patpi
			$wfw     = $item->children($ns[‘wfw’]);		//$wfw = http://jakilinux.org/aplikacje/komunikatory/czy-powinno-sie-tworzyc-aplikacje-na-wiele-systemow-operacyjnych-analiza-przypadku-komunikatora-kadu/feed/
			$friendly_url = explode('/feed/', $wfw); 	//$wfw = http://example.com/wpis/feed/
			$friendly_url = $friendly_url[0].'/'; 		//$friendly_url = http://example.com/wpis/
			$c = count($categories);
			$tags ="";
			for($i=0;$i<$c;$i++) {
				$tags.= $categories[$i];
				if($i<($c-1)) {
					$tags .= ", ";	
				}
			}
			$explode = explode("?p=", $permalink);
			$service = $explode[0];
			$orginal_post_id = $explode[1];
			$string = '<span id="more-'.$orginal_post_id.'"></span>';
			$content = strip_tags($content, '<p><a><em><span><b><i><strong><li><ul><ol><img><br><table><tr><td><th><abbr>');
			$content = explode($string, $content);
			$content = $content[0]."<!--more-->".$content[1];
			$postdata = array(
			'title' => $title,
			'permalink' => $friendly_url,
			'content' => $content,
			'service' => $service,
			'date' => $date,
			'tags' => $tags
			);			 
			return $postdata;
		} else {
			return false;
		} 
	}  
	
	function feed2post ( $data, $service ) {
	
		if( !isset( $data ) ) {
			return false;
		}
	
		if( !is_array( $data ) ) {
			return false; 
		}
		$author = $service['manager'];
		$category = $service['category'];
		$post = array(
		  'menu_order' => '0',
		  'comment_status' => 'open',
		  'ping_status' => 'open',
		  'pinged' => '',
		  'post_author' => $author,
		  'post_category' => $category,
		  'post_content' => $data['content'],
		  'post_date' => date('Y-m-d H:i:s'),
		  'post_date_gmt' => '',
		  'post_excerpt' => '',
		  'post_name' => '',
		  'post_parent' => '0',
		  'post_password' => '',
		  'post_status' => 'publish',
		  'post_title' => $data['title'],
		  'post_type' => 'post',
		  'tags_input' => $data['tags'],
		  'to_ping' => ''
		);		
		
		return wp_insert_post($post);
	
	}
	function post( $data = array() ) {
		$post = array(
		  'comment_status' => 'closed',
		  'ping_status' => 'open',
		  'post_author' => 1,
		  'post_category' => array(1),
		  'post_content' => "TREŚĆ POSTA",
		  'post_status' => 'publish', 
		  'post_title' => "TEST DODAWANIA WPISU",
		  'post_type' => 'post',
		  'tags_input' => 'tag1, tag2, tag3',
		);
		return wp_insert_post( $post );		
	}
}

function wp_planet_menu() {
	add_menu_page('WP-Planet', 'WP-Planet', 8, basename(__FILE__), 'main_page');
	add_submenu_page(basename(__FILE__), 'Ustawienia', 'Ustawienia', 8, 'wp-planet-options.php', 'options_page');
	add_submenu_page(basename(__FILE__), 'Feed', 'Feed', 8, 'wp-planet-feed.php', 'single_feed');
}



function main_page() {
$wp_planet = new Planet();
if($_POST['form'] == 'send') {
	if( $_POST['type_form'] == 'add' ) {
	$title = addslashes($_POST['feed_title']);
	$url = addslashes($_POST['feed_url']);
	$description = addslashes($_POST['feed_description']);
	$category = addslashes($_POST['feed_category']);
	$manager = addslashes($_POST['feed_manager']);
	$wp_planet->add_feed( $title, $url, $description, $category, $manager );
	}
	if( $_POST['type_form'] == 'edit' ) {
	$id = addslashes($_POST['feed_id']); 
	$title = addslashes($_POST['feed_title']);
	$url = addslashes($_POST['feed_url']);
	$description = addslashes($_POST['feed_description']);
	$category = addslashes($_POST['feed_category']);
	$manager = addslashes($_POST['feed_manager']);
	$wp_planet->edit_feed( $id, $title, $url, $description, $category, $manager );	
	}
	if( $_POST['type_form'] == 'delete' ) {
		if($_POST['del_conf'] == 1) {
		$delete_id = $_POST['delete_id'];
		$wp_planet->delete_feed($delete_id);
		}
	}
}	
if( $_GET['action'] ) {
	if( $_GET['action'] === 'add' ) {
		add_feed_view();
	}
	if( $_GET['action'] === 'edit' ) {
		if($_GET['id']) {
			edit_feed_view($_GET['id']);
		}
	}
	if( $_GET['action'] === 'view' ) {
		if($_GET['id']) {
			$id = $_GET['id'];
			feed_view( $id );
		}
	}	
} else {

$feeds = $wp_planet->feeds();
?>
	<div class="wrap">
	<h2>Lista serwisów</h2>
	<table cellspacing="0" class="widefat fixed">
	<thead>
	<tr class="thead">
		<th width="20px" class="manage-column" scope="col">ID</th>
		<th style="" class="manage-column" id="title" scope="col">Nazwa kanału</th>
		<th style="" class="manage-column" id="url" scope="col">Feed URL</th>
		<th style="" class="manage-column" id="desciption" scope="col">Opis</th>
		<th style="" class="manage-column" id="desciption" scope="col">Ostatnie Feedy</th>
		<th width="100px" class="manage-column" id="category" scope="col">Kategoria</th>
		<th width="100px" class="manage-column" id="contributor" scope="col">Ojciec Dyrektor</th>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
		<th width="150px" class="manage-column" scope="col">ID</th>
		<th style="" class="manage-column" id="title" scope="col">Nazwa kanału</th>
		<th style="" class="manage-column" id="url" scope="col">Feed URL</th>
		<th style="" class="manage-column" id="desciption" scope="col">Opis</th>
		<th style="" class="manage-column" id="desciption" scope="col">Ostatnie Feedy</th>
		<th width="100px" class="manage-column" id="category" scope="col">Kategoria</th>
		<th width="150px" class="manage-column" id="contributor" scope="col">Ojciec Dyrektor</th>
	</tr>
	</tfoot>
	<tbody class="list:user user-list" id="users">
<?php foreach( $feeds as $feed ): ?>
		<tr class="alternate" id="entry-<?php echo $feed->ID; ?>">
		<td><?php echo $feed->ID; ?></td>
		<td><?php echo $feed->feed_title; ?><div class="row-actions"><a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=wp-planet.php&action=edit&id=<?php echo $feed->ID;?>">Edytuj</a></div></td>
		<td><a href="<?php echo $feed->feed_url; ?>"><?php echo $feed->feed_url; ?></a></td>
		<td><?php echo $feed->feed_description; ?></td>
		<td><?php echo "<a href='admin.php?page=wp-planet.php&action=view&id=".$feed->ID."'>5 ostatnich wpisów</a>"; ?></td>
		<td><?php $cat_id = $feed->feed_category; echo get_category( $cat_id )->name; ?></td>
		<td class="username column-username"><?php $user_id = $feed->feed_manager; echo get_avatar( $user_id, 32 );?><strong><?php echo get_user_by_id( $user_id )->display_name; ?></strong><br /><div class="row-actions"><a href="<?php bloginfo('url'); ?>/wp-admin/user-edit.php?user_id=<?php echo $feed->feed_manager; ?>">Edytuj</a></div></td>
	</tr>
<?php endforeach; ?>
	</tbody>
	</table>
	
	<p>
		<a href="admin.php?page=wp-planet.php&amp;action=add">Dodaj nowy serwis</a>
	</p>
	</div>		
<?php
	}
}

function options_page() {
$rss = new RSS();
$rss->validate('http://czepol.info/feed/');
$feed = fetch_rss('http://czepol.info/feed/?format=xml');
print_r($rss->feed2post("ID=>'1'"));
echo "Site: ", $feed->channel['title'], "<br>\n";
foreach ($feed->items as $item ) {
	$title = $item[title];
	$url   = $item->feedburner->origLink;
	echo "<a href=$url>$title</a></li><br>\n";
	
}
$xml = simplexml_load_file('http://czepol.info/feed/?format=xml');
echo $xml->channel->title;
echo $xml->channel->item->guid;
	
?>
<div class="wrap">
	<h2>Ustawienia</h2>
	<p>TODO</p>
</div>
<?php
}

function feed_view( $id ) {
	echo "<div class='wrap'>";
	$wp_planet = new Planet();
	$wp_rss = new RSS();
	$feed = $wp_planet->feed_by_id($id);
	$feed = $feed[0];
	$rss = $wp_rss->get_feed($feed->feed_url, 5);
	print_r($rss);
	echo "</div>";
}

function add_feed_view() { 
?>
	<div class="wrap">
		<h2>Dodawanie serwisu</h2>
		<form method="post" action="admin.php?page=wp-planet.php">
		<input type="hidden" name="form" value="send" />
		<input type="hidden" name="type_form" value="add" />
		<table class="form-table">
			<tr>
				<th><label name="feed_title">Nazwa serwisu</label></th>
				<td><input type="text" name="feed_title" size="50"/></td>
			</tr>
			<tr>
				<th>Adres feeda</th>
				<td><input type="text" name="feed_url" size="50"/></td>
			</tr>
			<tr>
				<th>Opis serwisu</th>
				<td><textarea name="feed_description" cols="42" rows="5"></textarea></td>
			</tr>
			<tr>
				<th>Kategoria</th>
				<td>
					<select name="feed_category">
					<?php
					$categories = get_categories(array('hide_empty'=>false));
					foreach($categories as $category):
					?>
					<option value="<?php echo get_cat_ID($category->name); ?>" ><?php echo $category->name;?></option>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th>Menadżer</th>
				<td>
					<select name="feed_manager">
						<?php
						global $wpdb;
						$users = $wpdb->get_results("SELECT * FROM $wpdb->users");
						foreach($users as $user):				
						?>
						<option value="<?php echo $user->ID;?>" ><?php echo $user->display_name;?></option>
						<?php endforeach; ?>
					</select>				
				</td>
			</tr>
			<tr>
				<td>
					<p class="submit"><input type="submit" name="Submit" value="Dodaj serwis" /></p>
				</td>
			</tr>
		</table>
		</form>
	</div>

<?php	
}

function edit_feed_view( $id ) {
$wp_planet = new Planet();
$feed = $wp_planet->feed_by_id($id);
$feed = $feed[0];
?>
	<div class="wrap">
		<h2>Edycja serwisu</h2>
		
		<form method="post" action="admin.php?page=wp-planet.php">
		<input type="hidden" name="form" value="send" />
		<input type="hidden" name="type_form" value="edit" />
		<input type="hidden" name="feed_id" value="<?php echo $feed->ID; ?>" />
		<table class="form-table">
			<tr>
				<th><label name="feed_title">Nazwa serwisu</label></th>
				<td><input type="text" name="feed_title" size="50" value="<?php echo $feed->feed_title; ?>" /></td>
			</tr>
			<tr>
				<th>Adres feeda</th>
				<td><input type="text" name="feed_url" size="50" value="<?php echo $feed->feed_url; ?>" /></td>
			</tr>
			<tr>
				<th>Opis serwisu</th>
				<td><textarea name="feed_description" cols="42" rows="5"><?php echo $feed->feed_description; ?></textarea></td>
			</tr>
			<tr>
				<th>Kategoria</th>
				<td>
					<select name="feed_category">
					<?php
					$categories = get_categories(array('hide_empty'=>false));
					foreach($categories as $category):
					?>
					<option <?php if(get_cat_ID($category->name)==$feed->feed_category) { echo 'selected="selected"';} ?> value="<?php echo get_cat_ID($category->name); ?>" ><?php echo $category->name;?></option>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th>Menadżer</th>
				<td>
					<select name="feed_manager">
						<?php
						global $wpdb;
						$users = $wpdb->get_results("SELECT * FROM $wpdb->users");
						foreach($users as $user):				
						?>
						<option <?php if($user->ID==$feed->feed_manager) { echo 'selected="selected"'; } ?> value="<?php echo $user->ID;?>" ><?php echo $user->display_name;?></option>
						<?php endforeach; ?>
					</select>				
				</td>
			</tr>
			<tr>
				<td>
					<p class="submit"><input type="submit" name="Submit" value="Edytuj serwis" /></p>
					</form>
				</td>
				<td>
					<form method="post" action="admin.php?page=wp-planet.php">
						<input type="hidden" name="form" value="send" />
						<input type="hidden" name="type_form" value="delete" />
						<input type="hidden" name="delete_id" value="<?php echo $feed->ID; ?>" />
						<input type="submit" name="delete" value="Usuń" /> <label for="del_conf">Jesteś pewien?</label> <input type="checkbox" value="1" name="del_conf" />
					</form>
				</td>
			</tr>
		</table>		
	</div>
<?php
}

function single_feed() {
$rss = new RSS();
$url = 'http://www.devblogi.pl/feeds/posts/default?alt=rss';
$single = $rss->get_feed($url);
$service = array('author'=>1, 'category'=>1);
$data = array('id'=>'1', 'date'=>'2010-02-18 22:22:22');
print_r($rss->get_last_feed($url));
//print_r($rss->is_feed_exist_in_db($data));
//$rss->feed2post($single, $service);
$rss->feed_to_db( $data, $url ); 

/*
echo "<br />";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'http://jakilinux.org/feed/?format=xml');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$strona = curl_exec($curl);
curl_close($curl);
echo $strona;
//$feed = simplexml_load_string($strona);
//$feed = simplexml_load_file($url);
$feed = $feed->channel;
$item = $feed->item[0];
echo $item->title;
echo "<br />";
echo $item->link;
echo "<br />";
echo $item->comments;
echo "<br />";
echo $item->pubDate;
echo "<br />";
echo "Kategorie: ";
foreach ($item->category as $category) {
	echo $category;
	echo ", ";
}
echo "<br />";
echo $item->guid;
echo "<br />";
$ns = array (
        ‘content’ => "http://purl.org/rss/1.0/modules/content/",
        ‘wfw’ => "http://wellformedweb.org/CommentAPI/",
        ‘dc’ => "http://purl.org/dc/elements/1.1/"
); 
$content = $item->children($ns[‘content’]);
$dc      = $item->children($ns[‘dc’]);
$wfw     = $item->children($ns[‘wfw’]);
echo $dc;
echo "<br />";
echo $content;
echo "<br />";
echo $wfw;
//print_r($item);*/
}
?>
