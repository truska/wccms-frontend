<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Hello Truska Text TVS5L</title>
	</head>

	<body>
		<H1>Hello</H1>
		<h3>From TVS5 [LOCAL]</h3>
		<?php

			//Get Page URL (prefer ?url=..., fallback to current request)
				$pageURL = isset($_GET['url']) ? $_GET['url'] : '';
				if ($pageURL === '') {
					$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
					$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
					$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
					$pageURL = $scheme . '://' . $host . $uri;
				}
				$pageURL = rtrim($pageURL,'/') ;
                
            echo "<h3>Server: " . $_SERVER['SERVER_NAME'] . "</h3>" ;
			echo "<h3>Server IP: " . $_SERVER['SERVER_ADDR'] . "</h3>" ;

			echo "<h3>Agent: " . $_SERVER['HTTP_USER_AGENT'] . "</h3>" ;
			echo "<h3>Agent IP: " . $_SERVER['REMOTE_ADDR'] . "</h3>" ;
			echo "<h3>Page URL: " . $pageURL . "</h3>" ;
			echo "<h3>Time: " . $_SERVER['REQUEST_TIME'] . "</h3>" ;

			echo "<hr>";
	
			$debug = 'Yes';


			/* 2nd level urls and variables */

			$pagenumber = basename( $pageURL );
			//echo "Page Number = " . $pagenumber . "<br>" ;
			$path = parse_url($pageURL, PHP_URL_PATH);
			$script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
			if ($script !== '' && $path !== null) {
				$path = preg_replace('#^/?' . preg_quote($script, '#') . '/?#', '', $path);
			}
			$path = trim((string)$path, '/');
			$segs = ($path === '') ? array('') : explode('/', $path);

			if ($debug == 'Yes' ) {
				echo "<p>Debug = Yes</p>" ;
				echo "segs[0] = " .$segs[0] . "<br>"; 
				echo "segs[1] = " .$segs[1] . "<br>"; 
				echo "segs[2] = " .$segs[2] . "<br>"; 
				echo "segs[3] = " .$segs[3] . "<br>"; 
				echo "segs[4] = " .$segs[4] . "<br>"; 
				echo "segs[5] = " .$segs[5] . "<br>";  
				echo "new url = " .$segs[0] . "/" . $segs[1] . "<br>" ;
				echo "Page URL = " . $pageURL . "<br>" ;
			}
		?>
		<h5>END...</h5>
	</body>
</html>
