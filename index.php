<?php
session_start();
#error_reporting(0);
// We start a Session because the token is saved in a session in gis-wrapper/AuthProviderUser.php
// Timezone to be able to create dates
date_default_timezone_set('Europe/Berlin');
// require the Authentication Provider vor GIS Auth login credentials, GIS wrapper main class and the swiftmailer module
require_once("gis-wrapper/AuthProviderUser.php");
require_once("gis-wrapper/GIS.php");
require_once "vendor/swiftmailer/swiftmailer/lib/swift_required.php";
// login to GIS and instantiate GIS wrapper, besure to change this!
$user = new \GIS\AuthProviderUser("nicolas.oye@aiesec.net", "OGXishottogo");
$gis = new \GIS\GIS($user);
// login to Mail Provider via Swiftmailer. You need to authorize external apps with your account. It's easy with GMail
$transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, "ssl")
  ->setUsername('bianca.seemann@aiesec.de')
  ->setPassword('FUCKyou1/');
$mailer = Swift_Mailer::newInstance($transport);
// creating an empty mail body to fill it
	$mail_body_eps = "<table>";
// this logs us into the system to generate the token
foreach($gis->current_person as $p) {}
// script name, to which we will be referring
$scriptname = 'open_eps';
// this points where the folder is on your file system
$root = "/home/canca/AIESEC/nst/Scripts_Bianca/EPs/$scriptname";
$output = $root."/outputs/eps.csv";
// get the token to be used in subsequent queries.
//$token = $_SESSION['token'];
$token = "bc20f0457b38fee7f28a5ff62ab962e7d98d729ef6c313e84071def9cb7bd49a";
// create the date
$today = time();
// one day is todays date minus 86400 seconds. Date is given in epoch
$one_day = $today - 86400;
// convert date from epoch to a real date
$one_day =  date('Y-m-d', $one_day);
// $token_counter measures the number of API calls so we can re-login from time to time
$token_counter = 0;
###### WHAT ARE WE ACTUALLY DOING ######
// with this script we want to get all EPs who applied on the system since one day to follow them up.
// the first step is to send a query with just that information to the EXPA API. Since the EXPA API gives us the results in Pages,
// we first need to get the number of pages
###### GET THE NUMBER OF PAGES ######
$query = 'https://gis-api.aiesec.org:443/v1/people.json?access_token='.$token.'&filters&filters%5Bregistered%5D%5Bfrom%5D='.$one_day;
// define filename
$file_pages = "$root/ressources/".$scriptname."_pages.txt";

// save the contents of the file into the variable $pages
$pages = file_get_contents($query);
// the files are JSON Decoded which makes extracting of information super super easy
$pages = json_decode($pages);
// check the file and see how it is structured, or display it with print_r($pages);
// with the arrows you go through the different levels in the JSON structure. Check it out to understand it.
$pages = $pages->paging->total_pages;
// now we know how many total pages there are and we are going to go through them one by one
$output_file = fopen($output,'w');
for($current_page = 1;$current_page <= $pages;$current_page++){
	// it's the same query to the EXPA API expect that we now specifiy which page we want to look at
	$query = "https://gis-api.aiesec.org:443/v1/people.json?access_token=".$token."&page=".$current_page.'&filters&filters%5Bregistered%5D%5Bfrom%5D='.$one_day;
	$file_current_page = "$root/ressources/".$scriptname."_page_".$current_page.".txt";
	if(file_exists($file_current_page)){}
	else {
		file_put_contents($file_current_page, file_get_contents($query));
	}
	$file_current_page = file_get_contents($file_current_page);
	$file_current_page = json_decode($file_current_page);
	// now we create an array with all EPs, it's saved in data.
	$array = $file_current_page->data;
	#print_r($array);
	#$ep = reset($array);
	foreach($array as $ep){	//	in the array, for each EP there is another structural level, so we can easily loop through them
		// getting all the needed data.
		// First, we get the ID and then query all atributes of the EP, selecting the ones we want to know to create the .csv file
		$ep_id = $ep->id;
		$full_info_query = "https://gis-api.aiesec.org:443/v1/people/".$ep->id.".json?access_token=".$token;
		$json_full_info = file_get_contents($full_info_query);
		$full_info = json_decode($json_full_info, true);
		// get the data needed
		$full_info['home_lc'] = $full_info['home_lc']['name'];
		$full_info['cv_info'] = $full_info['cv_info']['url'];
		unset($full_info['home_mc']);
		unset($full_info['programmes']);
		unset($full_info['introduction']);
		unset($full_info['views']);
		unset($full_info['favourites_count']);
		unset($full_info['contacted_at']);
		unset($full_info['contacted_by']);
		unset($full_info['current_office']);
		unset($full_info['profile_photo_urls']);
		unset($full_info['cover_photo_urls']);
		unset($full_info['teams']);
		unset($full_info['positions']);
		unset($full_info['visible_profile']);
		unset($full_info['address_info']);
		if(array_key_exists('contact_info', $full_info)) {
			foreach($full_info['contact_info'] as $key => $value) {
				$full_info[$key] = $value;
			}
			unset($full_info['contact_info']);
		} else {
			$full_info['website']='';
			$full_info['phone']='';
			$full_info['facebook']='';
			$full_info['twitter']='';
			$full_info['instagram']='';
			$full_info['linkedin']='';
		}
		$tmp = array();
		foreach ($full_info['profile']['skills'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['skills'] = $tmp;
		$tmp = array();
		foreach ($full_info['profile']['languages'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['languages'] = $tmp;
		$tmp = array();
		foreach ($full_info['profile']['backgrounds'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['backgrounds'] = $tmp;
		$tmp = array();
		foreach ($full_info['profile']['issues'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['issues'] = $tmp;
		$tmp = array();
		foreach ($full_info['profile']['work_fields'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['work_fields'] = $tmp;
		$tmp = array();
		foreach ($full_info['profile']['preferred_locations_info'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['preferred_locations_info'] = $tmp;
		$tmp = array();
		foreach ($full_info['profile']['selected_programmes_info'] as $key => $value) {
			array_push($tmp, $value['name']);
		}
		$full_info['profile']['selected_programmes_info'] = $tmp;
		foreach ($full_info['profile'] as $key => $value) {
			$full_info[$key] = implode(",", $value);
		}
		unset($full_info['profile']);

		unset($full_info['academic_experiences']);
		unset($full_info['professional_experiences']);
		unset($full_info['nps_score']);
		unset($full_info['current_experience']);
		unset($full_info['permissions']);
		$full_info['missing_profile_fields'] = implode(",", $full_info['missing_profile_fields']);
		unset($full_info['managers']);
		#print_r($full_info);
		// output the EP data to the file
		fputcsv($output_file, $full_info);
		######## CREATE THE MAIL CONTENT ######
		// EP Name with link to his profile
		//$mail_body_eps .= "<tr>";
		//$mail_body_eps .= "<td><a href=http://www.experience.aiesec.de/#/people/$ep_id>$ep_name</a></td>";
		//$mail_body_eps .= "<td>$ep_mail</td>";
		//$mail_body_eps .= "<td>$ep_lc</td>";
		//$mail_body_eps .= "</tr>";
	}
	//$mail_body_eps .= "</table>";
}
fclose($output_file);
###### SETTING UP THE MAIL ######
//$header = "<html>Hey there. These are the applicants since $one_day";
//$mail = "$header\n\n$mail_body_eps\n\n</html>";

########### SEND THE MAIL #########
// make sure to change the mail addresses here aswell
//$message = Swift_Message::newInstance()->setSubject("Open EPs since $one_day")
//                        ->setFrom('yourmail@yourprovider.com')
//                        ->setTo('receiver@receiverprovider.com')
//                        ->setContentType("text/html")
//                        ->setBody("$mail");
// if you want to send an attachment
#$message->attach(Swift_Attachment::fromPath("$root/$scriptname.csv")->setFilename('$scriptname.csv'));
$errors = array();
//$mailer->send($message, $errors);
system("rm $root/ressources/$scriptname_page*");
system("find $root/ressources/ -name '*.txt' -size -1k -delete");
