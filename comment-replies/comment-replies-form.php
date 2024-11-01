<?php
require_once('../../../wp-config.php');
require_once('comment-replies.php');
$comment_ID = (int) $_POST['comment_ID'];
$reply = $_POST['reply'];
if($comment_ID < 1)
{
  wp_die('Invalid comment_id');
}
if(!$reply)
{
  wp_die('You cannot submit an empty reply.');
}

$comment = get_comment($comment_ID);

if(!cr_user_can_edit_reply($comment_ID->comment_post_ID))
{
  wp_die("You do not have permissions to reply to this comment.");
}
$userInfo = wp_get_current_user();
if(cr_add_reply($comment_ID, $userInfo, $reply))
{
  header("Location: ". urldecode($_REQUEST['ret']));
  die();
}
else
{
  wp_die("We were unable to add your reply at this time.");
}
?>

