<?php
/**
 * In theory with a relational database the list of following and followers would be contained in a single table 
 * with fields like following_id and follower_id. You can extract the followers or following of every user using an
 * SQL query. With a key-value DB things are a bit different since we need to set both the 1000 is following 5000
 * and 5000 is followed by 1000 relations. This is the price to pay, but on the other hand accessing the data is 
 * simpler and extremely fast. Having these things as separate sets allows us to do interesting stuff. For example,
 * using ZINTERSTORE we can have the intersection of 'following' of two different users, so we may add a feature to
 * our Twitter clone so that it is able to tell you very quickly when you visit somebody else's profile, "you and 
 * Alice have 34 followers in common", and things like that.
 */

include("retwis.php");

$r = redisLink();
if (!isLoggedIn() || !gt("uid") || gt("f") === false ||
    !($username = $r->hget("user:".gt("uid"),"username"))) {
    header("Location:index.php");
    exit;
}

$f = intval(gt("f"));
$uid = intval(gt("uid"));
if ($uid != $User['id']) {
    if ($f) {
        $r->zadd("followers:".$uid,time(),$User['id']);
        $r->zadd("following:".$User['id'],time(),$uid);
    } else {
        $r->zrem("followers:".$uid,$User['id']);
        $r->zrem("following:".$User['id'],$uid);
    }
}
header("Location: profile.php?u=".urlencode($username));
?>
