<?php
require_once('../../../wp-config.php');
require_once('comment-replies.php');
?>

function OnReplyToCommentClick(commentID)
{
  comment_li = document.getElementById('comment-'+commentID);
  if(comment_li == null)
  {
    return true;
  }
  var form = document.getElementById('form-' + commentID);
  if(form === null )
  {
    try
    {
      var form_code = '\
      <form id="form-' + commentID + '" action="<?php echo get_settings("siteurl"); ?>/wp-content/plugins/comment-replies/comment-replies-form.php?ret=' + location.href + '" method="post">\
        <h2 class="leavereply">Add a Reply</h2> \
        <div class="comment_reply"> \
          <input type="hidden" name="comment_ID" value="' + commentID + '" /> \
          <p class="info">Logged in as <?php echo $user_identity; ?>. <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?action=logout" title="<?php _e('Log out of this account') ?>">Logout</a>.</p> \
          <p> \
            <label class="replies" for="reply-' + commentID + '">Reply</label> \
            <textarea id="reply-' + commentID + '" rows="10" cols="45" name="reply" ></textarea> \
          </p>\
          <p>\
            <input class="button" type="submit" value="Submit" name="submit" /> \
            <input class="button" type="submit" onclick="OnCancelReplyClick(' + commentID + '); return false;" value="Cancel" name="Cancel" /> \
          </p>\
        </div>\
      </form>';

      comment_li.innerHTML += form_code;
    }
    catch(error)
    {

    }
  }
  return false;
}

function OnCancelReplyClick(commentID)
{
  var replyForm = document.getElementById('form-'+commentID);
  if(replyForm != null)
  {
    replyForm.parentNode.removeChild(replyForm);
  }
}