<?php
include("retwis.php");

# Form sanity checks
if (!gt("username") || !gt("password") || !gt("password2"))
    goback("Every field of the registration form is needed!");
if (gt("password") != gt("password2"))
    goback("The two password fileds don't match!");

# The form is ok, check if the username is available
$username = gt("username");
$password = gt("password");
$r = redisLink();
if ($r->hget("users",$username))
    goback("Sorry the selected username is already in use.");

# Everything is ok, Register the user!
# We use the next_user_id key in order to always get an unique ID for every new user. Then we use this unique ID to name the key holding a Hash with user's data. This is a common design pattern with key-values stores
$userid = $r->incr("next_user_id");
$authsecret = getrand();
# sometimes it can be useful to be able to get the user ID from the username, so every time we add an user, we also populate the users key, which is a Hash, with the username as field, and its ID as value
$r->hset("users",$username,$userid);
# We'll handle authentication in a simple but robust way: we don't want to use PHP sessions, our system must be ready to be distributed among different web servers easily, so we'll keep the whole state in our Redis database. All we need is a random unguessable string to set as the cookie of an authenticated user, and a key that will contain the user ID of the client holding the string.We need two things in order to make this thing work in a robust way. First: the current authentication secret (the random unguessable string) should be part of the User object, so when the user is created we also set an auth field in its Hash.Moreover, we need a way to map authentication secrets to user IDs, so we also take an auths key, which has as value a Hash type mapping authentication secrets to user IDs.
$r->hmset("user:$userid",
    "username",$username,
    "password",$password,
    "auth",$authsecret);
$r->hset("auths",$authsecret,$userid);

$r->zadd("users_by_time",time(),$username);

# User registered! Login her / him.
setcookie("auth",$authsecret,time()+3600*24*365);

include("header.php");
?>
<h2>Welcome aboard!</h2>
Hey <?=utf8entities($username)?>, now you have an account, <a href="index.php">a good start is to write your first message!</a>.
<?php
include("footer.php")
?>
