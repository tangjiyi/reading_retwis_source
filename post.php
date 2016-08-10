<?php
include("retwis.php");

if (!isLoggedIn() || !gt("status")) {
    header("Location:index.php");
    exit;
}

$r = redisLink();
$postid = $r->incr("next_post_id");
$status = str_replace("\n"," ",gt("status"));
$r->hmset("post:$postid","user_id",$User['id'],"time",time(),"body",$status);
$followers = $r->zrange("followers:".$User['id'],0,-1);
$followers[] = $User['id']; /* Add the post to our own posts too */

# After we create a post and we obtain the post ID, we need to LPUSH the ID in the timeline of every user that is following the author of the post, and of course in the list of posts of the author itself (everybody is virtually following herself/himself)
foreach($followers as $fid) {
    # We'll need to access this data in chronological order later, from the most recent update to the oldest, so the perfect kind of data structure for this is a List
    $r->lpush("posts:$fid",$postid);
}
# Push the post on the timeline, and trim the timeline to the
# newest 1000 elements.The global timeline is actually only used in order to show a few posts in the home page, there is no need to have the full history of all the posts.
# Basically LTRIM + LPUSH is a way to create a capped collection in Redis
$r->lpush("timeline",$postid);
$r->ltrim("timeline",0,1000);

header("Location: index.php");
?>
