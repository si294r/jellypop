<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

?><!DOCTYPE>
<html><head>
<title><?php echo $status_code; ?></title>
</head><body>
<h1><?php echo $status_code; ?></h1>
<p><?php echo $message; ?></p>
</body></html>