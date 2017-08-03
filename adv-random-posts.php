<?php
/*
Plugin Name: Advanced Random Posts
Plugin URI: http://www.yakupgovler.com/?p=416
Description: Display random posts from selected categories or current category or all posts.
Version: 1.1
Author: Yakup GÖVLER
Author URI: http://www.yakupgovler.com
*/

function yg_randomposts_init() {
	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	function yg_randomposts_widget($args) {

		extract($args);
		$options = get_option('yg_randomposts');
		$title = htmlspecialchars($options['title']);
        $limit = intval($options['entries-number']);
        $cats = htmlspecialchars($options['categories']);
		$currentcat = intval($options['currentcat']);
        echo $before_widget.$before_title.$title.$after_title;
        echo "\n<ul>\n";
         yg_randomposts("limit=$limit&cats=$cats&currentcat=$currentcat");
        echo "</ul>\n";
        echo $after_widget;
	}

	function yg_randomposts_options() {
		$options = get_option('yg_randomposts');
		if (!is_array($options)) {
			$options = array('title' => 'Random Posts', 'entries-number' => '10', 'currentcat' => 0, 'categories' => '');
			update_option('yg_randomposts', $options);
		}
		if ($_POST['yg-randomposts-submit']) {
        $options['entries-number'] = intval($_POST['yg-randomposts-entries-number']);
        if (($options['entries-number'] < 1) || ($options['entries-number'] > 20)) $options['entries-number'] = 10;
		  $options['title'] = strip_tags(stripslashes($_POST['yg-randomposts-title']));
		  $options['currentcat'] = ($_POST['yg-randomposts-currentcat']) ? 1 : 0;
		  $cats = str_replace(" ", "", strip_tags(stripslashes($_POST['yg-randomposts-categories'])));
		  if (!intval($cats)) $cats='';
          $options['categories'] = $cats;
		  
		  update_option('yg_randomposts', $options);
		}
?>
		<p>
		 <label for="yg-randomposts-title">
		  <?php _e( 'Title:' ); ?>
		  <input class="widefat" id="yg-randomposts-title" name="yg-randomposts-title" type="text" value="<?php echo htmlspecialchars($options['title']); ?>" />
		 </label>
		</p>
		<p>
		 <label for="yg-randomposts-entries-number"><?php _e('Number of posts to show:'); ?>
		 <input style="width: 15%; text-align:center; padding: 3px;" id="yg-randomposts-entries-number" name="yg-randomposts-entries-number" type="text" value="<?php echo intval($options['entries-number']); ?>" />
		<br /><small><?php _e('(at most 20)'); ?></small>
		 </label>
		</p>
        <p>
		 <label for="yg-randomposts-currentcat"><input class="checkbox" type="checkbox"  id="yg-randomposts-currentcat" name="yg-randomposts-currentcat" <?php echo ($options['currentcat'] == 1) ? 'checked="checked"' : '';?> /> <?php _e('Get posts from current category'); ?></label>
		</p>
		<p>
		 <label for="yg-randomposts-categories"><?php _e('Categories to get posts:'); ?>
		 <input class="widefat" id="yg-randomposts-categories" name="yg-randomposts-categories" type="text" value="<?php echo htmlspecialchars($options['categories']); ?>" />
		<br /><small><?php _e('(Category ids separated with a comma)'); ?></small>
		 </label>
		</p>
		<input type="hidden" id="yg-randomposts-submit" name="yg-randomposts-submit" value="1" />

<?php
	}
	// Register Widget
	register_sidebar_widget('Advanced Random Posts', 'yg_randomposts_widget');
	register_widget_control('Advanced Random Posts', 'yg_randomposts_options');
}

function yg_randomposts($args = '') {
    global $wpdb;
	$defaults = array(
		'limit' => 10, 'cats' => '', 'currentcat' => 0
	);
	$args = wp_parse_args( $args, $defaults );
	extract($args);
	
	$limit = intval($limit);
	$cats = str_replace(" ", "", $cats);
	if (($limit < 1 ) || ($limit > 20)) $limit = 10;
	if (($currentcat) && (is_category())) {
	 $cats = get_query_var('cat');
	}
	if (($currentcat) && (is_single())) {
	 $cats = '';
	 foreach (get_the_category() as $categories) {
	   $cats .= $categories->cat_ID.' ';
	   
	 }
	 $cats = str_replace(" ", ",", trim($cats));
	}
	if (!intval($cats)) $cats='';
	if ($cats == '') $sql = "SELECT id, post_title, post_name FROM $wpdb->posts WHERE ((post_status='publish') AND (post_type = 'post') AND ($wpdb->posts.post_password = '')) ORDER BY RAND() LIMIT $limit";
	else $sql="SELECT $wpdb->posts.id, $wpdb->posts.post_title FROM $wpdb->posts, $wpdb->term_relationships WHERE $wpdb->posts.post_type = 'post' 
AND $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_password = ''
and $wpdb->posts.id = $wpdb->term_relationships.object_id and $wpdb->term_relationships.term_taxonomy_id in ($cats) 
GROUP BY $wpdb->posts.id ORDER BY rand(), $wpdb->posts.post_date desc LIMIT $limit";
    $randomposts = $wpdb->get_results($sql);
	$postlist = '';
    foreach ($randomposts as $post) {
	  $post_title = htmlspecialchars(stripslashes($post->post_title));
      $postlist .= "<li><a href=\"" . get_permalink($post->id) . "\" title=\"". $post_title ."\">" . $post_title ."</a></li>\n";

    }
    echo $postlist;

 }

add_action('plugins_loaded', 'yg_randomposts_init');
?>