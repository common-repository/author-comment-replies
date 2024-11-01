<?php
/*
Plugin Name: Author Comment Replies
Plugin URI: http://cnpstudio.com/blog/comment-replies/
Description: The Comment-Reply plugin was originally developed by cnp_studio solely for use on Sony Computer Entertainment America&apos;s (SCEA) <a href="http://blog.us.playstation.com">PlayStation.Blog</a> to visually associate reader comments and author responses.
Version: 1.04
Author: Michael Pretty (cnp_studio)
Author URI: http://cnpstudio.com
*/

/*  Copyright 2008  Michael Pretty  (email : mike@cnpstudio.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Set the URL to the admin section
IF(!defined('ADMIN_DIR'))
{
	define('ADMIN_DIR', "/wp-admin/");
}

function cr_init()
{

	IF(!defined('ADMIN_URL'))
	{
		define('ADMIN_URL', get_settings("siteurl").ADMIN_DIR);
	}
	define("DEFAULT_AUTH_MODE", "default");
	define("AUTHOR_AUTH_MODE", "authors");

	//Set the Authentication Mode here
	//Default allows only users who can modify the post to edit/add replies
	//Author mode allows all authors to edit/add replies
	define("CURRENT_AUTH_MODE", DEFAULT_AUTH_MODE);
	define('CR_BASE_NAME', plugin_basename('comment-replies/comment-replies.php'));
	define('CR_OPTION_PAGE', 'options-general.php?page='.$cr_base_name);
	define('CR_BASE_PAGE', 'edit-comments.php?page='.$cr_base_name);

	add_action('admin_menu', 'cr_menu');
	add_action('wp_print_scripts', 'cr_includescript');
	IF(CURRENT_AUTH_MODE == DEFAULT_AUTH_MODE)
	{
		add_filter('edit_comment_link', 'cr_add_reply_comment_link', 10, 2);
	}
}
add_action('plugins_loaded', 'cr_init');



/**
 * Includes javascript file in post header.
 *
 */
function cr_includescript()
{
	global $post;
	if($post != null && cr_user_can_edit_reply($post->ID))
	{
		echo '<script language="javascript" type="text/javascript" src="' . get_settings("siteurl") . '/wp-content/plugins/comment-replies/comment-replies-js.php"></script>';
	}
}

/**
 * Function used to determine whether current user can add/edit replies.
 * @param int $post_ID
 * @return boolean
 */
function cr_user_can_edit_reply($post_ID)
{
	if(CURRENT_AUTH_MODE == AUTHOR_AUTH_MODE)
	{
		static $canEditReply = null;
		if($canEditReply === null)
		{
			$userInfo = wp_get_current_user();
			if($userInfo->wp_capabilities['author']|| $userInfo->wp_capabilities['administrator'])
			{
				$canEditReply = true;
			}
			else
			{
				$canEditReply = false;
			}
		}
		return  $canEditReply;
	}
	else //DEFAULT_AUTH_MODE
	{
		return current_user_can('edit_post', $post_ID);
	}
}

/**
 * Enter description here...
 *
 * @param unknown_type $link
 * @param unknown_type $comment_ID
 * @return unknown
 */
function cr_add_reply_comment_link($link, $comment_ID)
{
	return $link . " &nbsp; ". cr_get_add_reply_link($comment_ID, true);
}

/**
 * Returns link for adding replies
 *
 * @param int $comment_ID
 * @param bool $add_javascript add javascript to bypass admin page.
 * @return string
 */
function cr_get_add_reply_link($comment_ID, $add_javascript = false)
{
	$link = "<a href=\"".ADMIN_URL."edit-comments.php?page=comment-replies/comment-replies.php&action=edit&comment_ID=$comment_ID\"";
	if($add_javascript)
	{
		$link.= ' onclick="javascript: return OnReplyToCommentClick('.$comment_ID.');"';
	}
	$link.= ">Add Reply</a>";

	return $link;
}

/**
 * Prints link to edit reply section in admin
 *
 * @param int $reply_ID
 */
function cr_edit_reply_link($reply_ID)
{
	echo cr_get_edit_reply_link($reply_ID);
}

/**
 * Prints link to delete a reply
 *
 * @param int $reply_ID
 */
function cr_delete_reply_link($reply_ID)
{
	echo(ADMIN_URL."edit-comments.php?page=comment-replies/comment-replies.php&action=delete&reply_ID=$reply_ID") ;
}

/**
 * Returns link to edit reply in admin
 *
 * @param unknown_type $reply_ID
 * @return unknown
 */
function cr_get_edit_reply_link($reply_ID)
{
	return  (ADMIN_URL. "edit-comments.php?page=comment-replies/comment-replies.php&action=edit&reply_ID=$reply_ID");
}

/**
 * Adds admin menu options
 *
 */
function cr_menu()
{
	add_submenu_page('edit-comments.php', 'Author Comment Replies', 'Author Comment Replies', 1, __FILE__, 'cr_comment_replies_admin');
	add_options_page('Author Comment Replies Options', 'Author Comment Replies', 5, __FILE__, 'cr_options_page');
}

/**
 * Renders Admin Page
 *
 */
function cr_comment_replies_admin()
{
	global $wpdb;

	$showlisting = true;
	$action = $_REQUEST['action'];
	if($action == 'edit' || $action == 'update' || $action == 'delete')
	{
		cr_reply_edit_page($showlisting);
	}

	if($showlisting)
	{
		cr_reply_admin_listing();
	}

}

/**
 * Inserts reply into database
 * @param int $comment_ID
 * @param object $userInfo
 * @param string $reply
 */
function cr_add_reply($comment_ID, $userInfo, $reply)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'comment_replies';
	$sql = $wpdb->prepare("INSERT INTO $table_name (comment_ID, reply_author, reply_author_email, reply_author_url, reply_date, reply_date_gmt, reply_content, user_id)
    VALUES (%d, %s, %s, %s, %s, %s, %s, %d)",
	$comment_ID, $userInfo->nickname, $userInfo->user_email, $userInfo->user_url, current_time('mysql'), current_time('mysql', 1), $reply, $userInfo->ID);
	if($wpdb->query($sql))
	{
		return true;
	}
	else
	{
		return  false;
	}
}

/**
 * Renders edit page under admin section
 *
 * @param ref bool $showlisting
 */
function cr_reply_edit_page(&$showlisting)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'comment_replies';

	$comment_ID = (int)$_REQUEST['comment_ID'];
	$reply_ID = (int)$_REQUEST['reply_ID'];
	$error = false;
	if($reply_ID > 0)
	{
		$sql = "SELECT C.*, R.* FROM $wpdb->comments C
      JOIN $table_name R ON R.comment_ID = C.comment_ID
      WHERE R.reply_ID = $reply_ID";
		$toprow_title = 'Editing Reply #'.$reply_ID;
	}
	elseif($comment_ID > 0)
	{
		$sql = "SELECT C.* FROM $wpdb->comments C WHERE comment_ID = $comment_ID";
		$toprow_title = 'Replying to Comment #'.$comment_ID;
	}
	else
	{
		$error = true;
	}
	if(!$error)
	{
		global  $comment;
		$comment = $wpdb->get_row($sql);

		if($_REQUEST['action'] == 'update')
		{
			$userInfo = wp_get_current_user();
			if($reply_ID > 0)
			{
				$sql = $wpdb->prepare("UPDATE $table_name SET reply_content = %s WHERE reply_ID = %d", $_REQUEST['content'], $reply_ID);
				if($wpdb->query($sql))
				{
					echo '<!-- Last Action --><div id="message" class="updated fade"><p>Comment reply was updated</p></div>';
				}
			}
			else
			{
				//$sql = $wpdb->prepare("INSERT INTO $table_name (comment_ID, reply_author, reply_author_email, reply_author_url, reply_date, reply_date_gmt, reply_content, user_id)
				//  VALUES (%d, %s, %s, %s, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, %s, %d)",
				//$comment_ID, $userInfo->nickname, $userInfo->user_email, $userInfo->user_url, $_REQUEST['content'], $userInfo->ID);
				if(cr_add_reply($comment_ID, $userInfo, $_REQUEST['content']))
				{
					echo '<!-- Last Action --><div id="message" class="updated fade"><p>Comment reply was added</p></div>';
				}
			}

			return ;
		}
		elseif($_REQUEST['action'] == 'delete')
		{
			$reply_ID = (int)$_REQUEST['reply_ID'];
			$sql = $wpdb->prepare("DELETE FROM $table_name WHERE reply_ID = %d", $reply_ID);
			if($wpdb->query($sql))
			{
				echo '<!-- Last Action --><div id="message" class="updated fade"><p>Comment reply has been deleted</p></div>';
			}
			return ;
		}
		else
		{
			$showlisting = false;
    ?>
    <form name="post" action="edit-comments.php?page=comment-replies/comment-replies.php" method="post" id="post">
      <h2><?php echo $toprow_title; ?></h2>
      <div class="wrap">
        <input type="hidden" name="user_ID" value="<?php echo (int) $user_ID ?>" />
        <input type="hidden" name="action" value='update' />
        <input type="hidden" name="comment_ID" value="<?php echo($comment_ID)?>" />
        <input type="hidden" name="reply_ID" value="<?php echo( $reply_ID) ?>" />
        <div>

        <?php
        $post = get_post($comment->comment_post_ID, OBJECT, 'display');
        $post_title = wp_specialchars( $post->post_title, 'double' );
        $post_title = ('' == $post_title) ? "# $comment->comment_post_ID" : $post_title;
        ?>
          <a href="<?php echo get_permalink($comment->comment_post_ID); ?>"><?php echo $post_title; ?></a></h4>
    			<p><strong><?php comment_author() ?></strong> <?php if ($comment->comment_author_email) { ?>| <?php comment_author_email_link() ?> <?php } if ($comment->comment_author_url && 'http://' != $comment->comment_author_url) { ?> | <?php comment_author_url_link() ?> <?php } ?>| <?php _e('IP:') ?> <a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput=<?php comment_author_IP() ?>"><?php comment_author_IP() ?></a> | Comment Date: <?php comment_date('M j, g:i A');  ?> |
    			</p>
          <?php comment_text() ?>
        </div>
        <fieldset style="clear: both;">
          <legend><?php _e('Reply') ?></legend>
        	<?php the_editor($comment->reply_content, 'content', 'newcomment_author_url'); ?>
        </fieldset>
        <p class="submit"><input type="submit" name="editreply" id="editreply" value="Submit Reply" style="font-weight: bold;" tabindex="6" />
          <input name="referredby" type="hidden" id="referredby" value="<?php echo wp_get_referer(); ?>" />
        </p>
      </div>
    </form>
    <?php
		}
	}
	else
	{
		echo ("AN ERROR OCCCURRED SOMEWHERE");
	}
}

/**
 * Renders replies for admin page
 *
 */
function cr_reply_admin_listing()
{
	global $wpdb;
	$itemsPerPage = 10;
	$table_name = $wpdb->prefix . 'comment_replies';
	?>
	<div class="wrap">
	<?php
	if ( isset( $_GET['apage'] ) )
	{
		$page = (int) $_GET['apage'];
	}
	else
	{
		$page = 1;
	}
	$start = $offset = ( $page - 1 ) * $itemsPerPage;

	$sql = "SELECT C.*, R.* FROM $wpdb->comments C
	 JOIN $table_name R ON R.comment_ID = C.comment_ID
	 WHERE C.comment_approved = '1'
	 ORDER BY reply_date_gmt DESC
	 LIMIT $start, $itemsPerPage";
	$comments = $wpdb->get_results($sql);

	$total = $wpdb->get_var("SELECT Count(*) FROM $wpdb->comments C JOIN $table_name R ON R.comment_ID = C.comment_ID WHERE C.comment_approved = '1'");
	if($total > $itemsPerPage)
	{
		$total_pages = ceil( $total / $itemsPerPage );
		$r = '';
		if ( 1 < $page ) {
			$args['apage'] = ( 1 == $page - 1 ) ? FALSE : $page - 1;
			$r .=  '<a class="prev" href="' . add_query_arg( $args ) . '">&laquo; '. __('Previous Page') .'</a>' . "\n";
		}
		if ( ( $total_pages = ceil( $total / $itemsPerPage ) ) > 1 ) {
			for ( $page_num = 1; $page_num <= $total_pages; $page_num++ )
			{
				if ( $page == $page_num )
				{
					$r .=  "<span>$page_num</span>\n";
				}
				else
				{
					$p = false;
					if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) {
						$args['apage'] = ( 1 == $page_num ) ? FALSE : $page_num;
						$r .= '<a class="page-numbers" href="' . add_query_arg($args) . '">' . ( $page_num ) . "</a>\n";
						$in = true;
					}
					elseif ( $in == true ) {
						$r .= "...\n";
						$in = false;
					}
				}
			}
		}
		if ( ( $page ) * $itemsPerPage < $total || -1 == $total ) {
			$args['apage'] = $page + 1;
			$r .=  '<a class="next" href="' . add_query_arg($args) . '">'. __('Next Page') .' &raquo;</a>' . "\n";
		}
		echo "<p class='pagenav'>$r</p>";
	}
	if ($comments) {
		$offset = $offset + 1;
		$start = " start='$offset'";

		echo "<ol id='the-comment-list' class='commentlist' $start>";
		$i = 0;
		global $comment;
		foreach ($comments as $comment) {
			++$i; $class = '';

			if ($i % 2)
			$class = ' alternate';

			echo "<li id='comment-$comment->comment_ID' class='$class'>";
				?>
				<h4>
				<?php
				$post = get_post($comment->comment_post_ID, OBJECT, 'display');
				$post_title = wp_specialchars( $post->post_title, 'double' );
				$post_title = ('' == $post_title) ? "# $comment->comment_post_ID" : $post_title;
        ?>
          <a href="<?php echo get_permalink($comment->comment_post_ID); ?>"><?php echo $post_title; ?></a></h4>
					<p><strong><?php comment_author() ?></strong> <?php if ($comment->comment_author_email) { ?>| <?php comment_author_email_link() ?> <?php } if ($comment->comment_author_url && 'http://' != $comment->comment_author_url) { ?> | <?php comment_author_url_link() ?> <?php } ?>| <?php _e('IP:') ?> <a href="http://ws.arin.net/cgi-bin/whois.pl?queryinput=<?php comment_author_IP() ?>"><?php comment_author_IP() ?></a> | Comment Date: <?php comment_date('M j, g:i A');  ?> |
					</p>
					<?php comment_text() ?>
					<div style="margin: 10px 0 10px 20px; background-color: #fff; padding: 10px;">
					<p>
					 <b><?php echo( $comment->reply_author . '</b> | Reply Date: '. mysql2date( get_option('time_format'), $comment->reply_date_gmt) )?><br /><br/>
					 <?php echo($comment->reply_content)?>
					 </p>
					 <?php
					 if(cr_user_can_edit_reply($comment->comment_post_ID))
					 {
					   ?>
  					 [<a href="<?php cr_edit_reply_link($comment->reply_ID);?>">Edit Reply</a>]
  					 [<a href="<?php cr_delete_reply_link($comment->reply_ID);?>">Delete Reply</a>]
  					 <?php
					 }
					 ?>
					</div>
				</li>

<?php } // end foreach($comment) ?>
	</ol>

<div id="ajax-response"></div>

<?php
	} else { //no comments to show

		?>
		<p>
			<strong><?php _e('No comment replies found.') ?></strong></p>

		<?php
	} // end if ($comments)

	?>
<?php if ( $total > $itemsPerPage ) {
	$total_pages = ceil( $total / $itemsPerPage );
	$r = '';
	if ( 1 < $page ) {
		$args['apage'] = ( 1 == $page - 1 ) ? FALSE : $page - 1;
		$r .=  '<a class="prev" href="' . add_query_arg( $args ) . '">&laquo; '. __('Previous Page') .'</a>' . "\n";
	}
	if ( ( $total_pages = ceil( $total / $itemsPerPage ) ) > 1 ) {
		for ( $page_num = 1; $page_num <= $total_pages; $page_num++ ) :
		if ( $page == $page_num ) :
		$r .=  "<span>$page_num</span>\n";
		else :
		$p = false;
		if ( $page_num < 3 || ( $page_num >= $page - 3 && $page_num <= $page + 3 ) || $page_num > $total_pages - 3 ) :
		$args['apage'] = ( 1 == $page_num ) ? FALSE : $page_num;
		$r .= '<a class="page-numbers" href="' . add_query_arg($args) . '">' . ( $page_num ) . "</a>\n";
		$in = true;
		elseif ( $in == true ) :
		$r .= "...\n";
		$in = false;
		endif;
		endif;
		endfor;
	}
	if ( ( $page ) * $itemsPerPage < $total || -1 == $total ) {
		$args['apage'] = $page + 1;
		$r .=  '<a class="next" href="' . add_query_arg($args) . '">'. __('Next Page') .' &raquo;</a>' . "\n";
	}
	echo "<p class='pagenav'>$r</p>";
?>

<?php } ?>

</div>


	<?php

}

/**
 * Renders options administration page for comment_reply
 *
 */
function cr_options_page()
{
	global $cr_option_page;
	if($_POST['cr_do']) {
		$replyTemplate = trim($_POST["cr_reply_template"]);

		$text = "";

		if(update_option('cr_reply_template', $replyTemplate))
		{
			$text .= '<font color="green">Comment Template Updated</font><br/>';
		}
		if(empty($text)) {
			$text = '<font color="red">'.__('No Comment Reply Options Updated', 'comment_replies').'</font>';
		}
	}
	?>
	<div class="wrap">
		<h2>Author Comment Replies</h2>
		<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
		<form method="post" action="<?php echo $cr_option_page; ?>" >
		<table cellspacing="0" cellpadding="5">
			<tr>
				<td valign="top"><strong>Reply Template:</strong><br/><br/>
					Allowed Variables:<br/>
					- %REPLY_USERID%<br/>
					- %REPLY_AUTHOR%<br/>
					- %REPLY_DATE%<br/>
					- %REPLY_TIME%<br/>
          - %REPLY_TEXT%<br/>
				</td>
				<td><textarea cols="80" rows="10" id="cr_reply_template" name="cr_reply_template"><?php echo htmlspecialchars(stripslashes(get_option('cr_reply_template')))?></textarea></td>
			</tr>
			<tr>
				<td colspan="2" style="text-align: center;">
					<input type="submit" value="Update" id="cr_do" name="cr_do" class="button">
				</td>
			</tr>
		</table>
		</form>
	</div>
	<?php
}

/**
 * Displays the comment replies for the given comment
 *
 * @param object $comment
 */
function cr_display_comment_replies($comment)
{
	global $wpdb;
	static $replies = null;
	if($replies === null)
	{
		//setup replies array for this post.
		$reply_table = $wpdb->prefix . 'comment_replies';
		$sql = "SELECT DISTINCT R.reply_ID, R.comment_ID, R.reply_author, R.reply_author_email,
      R.reply_author_url, R.reply_date, R.reply_content, R.user_id
      FROM $reply_table R
      JOIN $wpdb->comments C ON C.comment_ID = R.comment_ID
      WHERE C.comment_post_ID = $comment->comment_post_ID
      AND C.comment_approved = '1' AND comment_type = ''
      ORDER BY reply_date_gmt";

		$reply_results = $wpdb->get_results($sql);
		$replies = array();
		foreach ($reply_results as $reply)
		{
			if($replies[$reply->comment_ID] === null)
			{
				$replies[$reply->comment_ID] = array();
			}
			$replies[$reply->comment_ID][] = $reply;
		}
	}

	$replyTemplate = stripslashes(get_option('cr_reply_template'));
	$responseText = "";
	//var_dump($replies);
	if(CURRENT_AUTH_MODE == AUTHOR_AUTH_MODE && cr_user_can_edit_reply($comment->comment_post_ID))
	{
		$responseText.= "<span class=\"editlink\">".cr_get_add_reply_link($comment->comment_ID, true)."</span><br />";
	}
	if(count($replies) > 0 && count($replies[$comment->comment_ID]) > 0)
	{
		foreach($replies[$comment->comment_ID] as $reply)
		{
			$replyText = $replyTemplate;
			$replyText = str_replace("%REPLY_USERID%",$reply->user_id, $replyText);
			$replyText = str_replace("%REPLY_DATE%", mysql2date( get_option('date_format'), $reply->reply_date), $replyText);
			$replyText = str_replace("%REPLY_TIME%", mysql2date( get_option('time_format'), $reply->reply_date), $replyText);

			$author = $reply->reply_author;
			if(strlen($author) > 30)
			{
				$author = substr($author, 0, 30);
			}
			$replyText = str_replace("%REPLY_AUTHOR%", $author , $replyText);
			$replyText = str_replace("%REPLY_TEXT%", apply_filters('comment_text', stripslashes($reply->reply_content)), $replyText);
			$responseText.= $replyText;
		}
	}
	echo($responseText);
}

/**
 * Comment Replies Installation Method
 *
 */
function cr_comment_replies_install()
{
	global $wpdb;
	$installed_ver = get_option('comment_replies_version');
	$comment_replies_version = '1.04';
	if($installed_ver != $comment_replies_version)
	{
		update_option("comment_replies_version", $comment_replies_version);
	}
	$table_name = $wpdb->prefix . 'comment_replies';

	add_option("cr_reply_template", cr_getDefaultTemplate('reply'));

	$sql = "CREATE TABLE " . $table_name . " (
		  reply_ID bigint(20) UNSIGNED NOT NULL auto_increment,
			comment_ID bigint(20) UNSIGNED NOT NULL,
      reply_author tinytext collate latin1_general_ci NOT NULL,
      reply_author_email varchar(100) collate latin1_general_ci NOT NULL default '',
      reply_author_url varchar(200) collate latin1_general_ci NOT NULL default '',
      reply_date datetime NOT NULL default '0000-00-00 00:00:00',
      reply_date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
      reply_content text collate latin1_general_ci NOT NULL,
      user_id bigint(20) NOT NULL default '0',
			PRIMARY KEY  reply_ID (reply_ID),
			KEY comment_ID (comment_ID)
		);";
	if(!function_exists("dbDelta"))
	{
		require_once(ABSPATH.ADMIN_DIR.'/upgrade-functions.php');
	}
	dbDelta($sql);

}
add_action('activate_comment-replies/comment-replies.php', 'cr_comment_replies_install');

/**
 * Returns default comment template
 *
 * @return string
 */
function cr_getDefaultTemplate($template)
{
	switch ($template)
	{
		case 'reply':
			$tmp = '<cite>%REPLY_AUTHOR%</cite> says<br /><small class="replymetadata">%REPLY_DATE% at %REPLY_TIME%</a></small>'."\n".
			'%REPLY_TEXT%'."\n";
			break;
	}
	return  $tmp;
}

?>