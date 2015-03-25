<?php header("HTTP/1.0 404 Not Found");
if($_SERVER['REQUEST_METHOD'] == 'GET'):?>
	<h1>Error 404 Not Found</h1>
<?php endif;
exit();