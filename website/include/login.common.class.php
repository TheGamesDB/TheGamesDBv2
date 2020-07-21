<?php

// enable for testing
// this will allow you to have a pseudo login tp avoid the need to setup phpbb forum
if(false)
{
	require __DIR__ . "/login.pseudo.class.php";
}
else
{
	require __DIR__ . "/login.phpbb.class.php";
}
