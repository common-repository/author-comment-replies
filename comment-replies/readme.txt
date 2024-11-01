=== Author Comment Replies ===

Tags: Comment Replies, Author Only
Contributors: cnp_studio, Michael Pretty

We originally developed the Author Comment Reply plugin solely for use on Sony Computer Entertainment 
America’s (SCEA) <a href="http://blog.us.playstation.com">PlayStation.Blog</a> to visually associate 
reader comments and author responses. Thanks to SCEA’s desire to contribute back to the WordPress 
community, we are able to offer this plugin for public consumption.

The Author Comment Reply plugin gives authors the ability to reply directly to a posted comment.

== Installation ==

Note: The Image-Rotator Plugin requires WordPress 2.3 or greater

Note: The theme's comment loop must include the call to edit_comment_link().  If this function is 
not called, it will need to be added before you can respond to comments.

1.  Upload the Author Comment Reply plugin to your plug-ins directory, usually wp-content/plugins 
    to create a wp-content/plugins/comment-replies/ directory.

2.  Activate the plug-in through the Plug-ins admin screen.

3.  Open the theme's comment file, usually wp-content/themes/<THEME NAME>/comments.php

4.  Add the following code within the comment loop directly under the call to comment_text():
    '<?php if(function_exists('cr_display_comment_replies')) cr_display_comment_replies($comment); ?>'

== Frequently Asked Questions == 

= Is there a way to make the comment replies appear in the comment feed? =

The comment replies plugin uses its own table to store the comment replies.  Because of this,
the replies are not included within the comment feed.

= I can’t seem to find the CSS class(es) in comment-replies.php to make sure the style difference fits with the design aesthetic of the the rest of the blog. = 

To keep any classes from interfering with styles already within the blog theme, no classes were added
to the comment replies by default.  This can all be edited by styling the comment replies through
the options page.  You can see an example in this screen shot: 
<a href="http://www.flickr.com/photos/cnp_studio/2245871285/in/photostream/">http://www.flickr.com/photos/cnp_studio/2245871285/in/photostream/</a>
 

== Styling Replies ==

The reply template can easily be updated via the admin under Options -> Comment-Replies. Version 1.0
of the Author Comment Reply plug-in has the following variables available:

* %REPLY_USERID% - The User ID of the Reply Author
* %REPLY_AUTHOR% - The nickname of the Reply Author
* %REPLY_DATE% - The date of the reply. Uses the date format set in the WordPress Options.
* %REPLY_TIME% - The time of the reply. Users the time format set in the WordPress Options.
* %REPLY_TEXT% - The content of the reply

== Adding Replies ==

To add a reply, simply find the comment on the front end of your site and click the "Add Reply" 
link for that comment. Do you not see this link? Make sure you read thoroughly the directions 
under the installation section.

== Managing Replies ==

You can manage all replies from the Comments area of the WordPress administration by clicking
on the Comment Replies section.

== Altering Permissions ==

The Author Comment Reply plug-in comes with two sets of permissions handling. The Default only 
allows users who have permissions to edit the current post to add replies, this is usually just 
administrators or the author of the current post. The Author permission mode, allows any users 
marked as an author to add replies.

To change to Author permission handling:

1. Open the comment-replies.php file.

2. Find the line: 'define("CURRENT_AUTH_MODE", DEFAULT_AUTH_MODE);'

3. Replace it with: 'define("CURRENT_AUTH_MODE", AUTHOR_AUTH_MODE);'

== Support ==

Please leave a comment on our <a href="http://cnpstudio.com/blog/comment-replies/">Author 
Comment Replies</a> page for support.