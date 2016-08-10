<?php
include("retwis.php");

# Form sanity checks
if (!gt("username") || !gt("password"))
    goback("You need to enter both username and password to login.");

# The form is ok, check if the username is available
$username = gt("username");
$password = gt("password");
$r = redisLink();
# Check if the username field actually exists in the users Hash
# If it exists we have the user id
$userid = $r->hget("users",$username);
if (!$userid)
    goback("Wrong username or password");
# Check if user:1000 password matches, if not, return an error message
$realpassword = $r->hget("user:$userid","password");
if ($realpassword != $password)
    goback("Wrong useranme or password");

# Ok authenticated! Set "fea5e81ac8ca77622bed1c2132a021f9" (the value of user:1000 auth field) as the "auth" cookie
$authsecret = $r->hget("user:$userid","auth");
setcookie("auth",$authsecret,time()+3600*24*365);
header("Location: index.php");
?>
