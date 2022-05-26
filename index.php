<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">
<title>Crawler</title>
<div style='padding: 20px;'>
	<form  action='index.php' spellcheck="false">
		<p><b><a href='<?php echo $_SERVER['PHP_SELF']; ?>' style=' text-decoration: none; color: black;'>Enter Url:</a></b></p>
		<p><textarea rows="5" cols="45" name="url"><?php echo @$_GET['url']; ?></textarea></p>
		<p><input type="submit" value="Start"></p>
	</form>
 <?php
 
if ( !isset($_GET['url']) ) die;

set_time_limit(0);
include_once('simple_html_dom.php');
ini_set("memory_limit","1024M");


$csv_content = '"Name","Url"'.PHP_EOL ;
$csv_file_path = 'projects.txt';
$excel_file_path = 'projects.xlsx';

$urls_file_path = 'index.php';

if ( ! is_writable(dirname($urls_file_path))) {
	echo '<h3 style="color: red">Directory '.realpath( dirname($urls_file_path) ). ' must be writable!<br>
	"chmod o+w '.realpath( dirname($urls_file_path) ). '" in cmd should help!
	<h3>';
}

$url = trim($_GET['url']);
$counter = 0;

//$pure_html = curl($url);
//$html = str_get_html( $pure_html );
//$limit = $html->find('b[class="count"]', 0)->plaintext;
//$limit = intval($limit/12) + 1;
//echo $limit;
//die;

for ($i = 1; $i <= 220; $i++) {// not trying to find exact paging number

	$current_page = $url.'&page='.$i;
	$pure_html = curl($current_page);
	$html = str_get_html( $pure_html );

	if ( $html->find('div[class="js-react-proj-card"]', 0) == null ) {
		echo '<h4>no projects for '.$current_page.'</h4>';
		continue;
	}

    echo '<h5 style="color: red;">'.$current_page.'</h5>';

	ob_implicit_flush(true);
	@ob_end_flush();

	foreach ( $html->find('div[class="js-react-proj-card"]') as $card) {

		$counter = $counter + 1;
		
		$project_url = $card->getAttribute('data-project');
		$project_url = html_entity_decode( $project_url, ENT_QUOTES, 'UTF-8' );
		$project_url = json_decode( $project_url );
		$project_url  = $project_url->urls->web->project;
		echo '<h6 style="color: green;">'.$counter.'. '.$project_url.'</h6>';
		check_link($project_url);

	}
}


file_put_contents($csv_file_path, $csv_content);
echo '<br><br>Data stored in <a href="'.$csv_file_path.'">'.$csv_file_path.'</a>';

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
$objPHPExcel = $reader->load($csv_file_path);
$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
$objWriter->save($excel_file_path);

echo '<br><br>Data stored in <a href="'.$excel_file_path.'">'.$excel_file_path.'</a>';

function check_link($url) {

	global $csv_content;

	$pure_html = curl($url);
	$html = str_get_html( $pure_html );
	
//	$html = str_get_html( file_get_contents( 'test.html') );

	$name = $html->find( 'meta[property="og:title"]', 0 )->content;
	$name = html_entity_decode( trim($name), ENT_QUOTES, 'UTF-8' );

	echo $name;

	$website = '';

	$website = $html->find( 'div[id="content-wrap"]', 0 )->find( 'div[class="bg-grey-100"]', 0 )->getAttribute('data-initial');
	$website = html_entity_decode( $website, ENT_QUOTES, 'UTF-8' );
	$website = json_decode( $website );
	$website = $website->project->creator->websites[0]->url;

	echo ' - '.$website.'<br>';
	
	$csv_content = $csv_content .$name.','.$website.PHP_EOL;

	ob_implicit_flush(true);
	@ob_end_flush();

}

function curl( $url, $retry = 3 ){
		
	sleep(10);
	
	$user_agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36';

	if( $retry > 5 ) {
		print "Maximum 5 retries are done, skipping!\n";
		return "in loop!";
	}
	
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt ($ch, CURLOPT_HEADER, TRUE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
//	curl_setopt ($ch, CURLOPT_REFERER, 'http://www.google.com.ua/');
//	curl_setopt($ch, CURLOPT_PROXY, 'socks5://144.76.64.245:9100');

	curl_setopt($ch,CURLOPT_ENCODING , "");
	curl_setopt ($ch, CURLOPT_COOKIEFILE,"./cookie.txt");//read cookies
//	curl_setopt ($ch, CURLOPT_COOKIEJAR,"./cookie.txt");//write cookies
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);

	// handling the follow redirect
	if(preg_match("|Location: (https?://\S+)|", $result, $m)){
		echo "Manually doing follow redirect! -> $m[1] <br>";
		return curl($m[1], $user_agent, $retry + 1);
	}

	// add another condition here if the location is like Location: /home/products/index.php

	return $result;
}

?>