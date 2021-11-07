<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$return_users_array = [];
$user_data["records"] =[];
//Get users by Filters
$city = $proff = $skills = $id = 1;
$limit = 5;$offset=0;
$data = json_decode(file_get_contents('php://input'), true);

print_r($data);

if($data && count($data)>0){
	//MysQL newew version
        $city = !empty($data['city']) ? explode(";",substr($data['city'],0,-1)) : "";
        $city = $city ? "LOWER(city)  REGEXP '\\\b".strtolower(implode("|",$city))."\\\b'":1;
        //$proff= !empty($data['proff']) ? explode(";",substr($data['proff'],0,-1)) : "";
        $proff= !empty($data['proff']) ? explode(";",substr(str_replace("-"," ",$data['proff']),0,-1)) : "";
	$proff = $proff ? "LOWER(custom)  REGEXP '".strtolower(implode("\\\b|\\\b",$proff))."\\\b'":1;
        $skills = !empty($data['skills']) ? explode(";",substr($data['skills'],0,-1)) : "";
        $skills = $skills ? "LOWER(custom)  REGEXP '\\\b".strtolower(implode("\\\b|\\\b",$skills))."\\\b'":1;
	//Mysql 5.7
	/*
	$city = !empty($data['city']) ? explode(";",strtolower($data['city'])) : "";
	$city = $city ? 'LOWER(city)  REGEXP \'[[:<:]]'.strtolower(implode('[[:<:]]|[[:<:]]',$city)).'[[:<:]]\'':1;
	$proff= !empty($data['proff']) ? explode(";",$data['proff']) : "";
	$proff = $proff ? "LOWER(custom)  REGEXP '[[:<:]]".strtolower(implode("[[:<:]]|[[:<:]]",$proff))."[[:<:]]'":1;
	$skills = !empty($data['skills']) ? explode(";",$data['skills']) : "";
	$skills = $skills ? "LOWER(custom)  REGEXP '[[:<:]]".strtolower(implode("[[:<:]]|[[:<:]]",$skills))."[[:<:]]'":1;
	*/
	        //GET Pagination
        if(!empty($data['page'])) {
                $page = $data['page']-1;
                $offset = $limit * $page ;
        }else {
                $page = 0;
                $offset = 0;
        }

}
// Get A user
$id = !empty($_GET['id']) ? "id=".$_GET['id'] : 1;

//GET Pagination
/*
if(!empty($_GET['page'])) {
      $page = $_GET['page']-1;
      $offset = $limit * $page ;
}else {
      $page = 0;
      $offset = 0;
}
*/
// Get ALL Users
$new_query = "
SELECT SQL_CALC_FOUND_ROWS *  FROM (
	Select
	u.id,
	u.firstname,
	u.lastname,
	u.email,
	u.city,
	f.contextid,
	f.itemid,
	f.filearea,
	#if(f.component='profilefield_file','profilefield_file',NULL) AS component,
	f.component,
	f.filename,
	f.source,
	GROUP_CONCAT(DISTINCT CONCAT('"."\""."',fls.shortname,'"."\":\""."',REPLACE(datas.data,"."'\"'".","."'\''"."),'"."\""."') SEPARATOR ',') as custom,
	GROUP_CONCAT(DISTINCT enrol.courseid SEPARATOR ',') as courses
	from mdl_files AS f
	INNER JOIN mdl_context as c on c.id = f.contextid
	INNER JOIN mdl_user as u on u.id = c.instanceid
	INNER JOIN mdl_user_info_data as datas on datas.userid = u.id
	INNER JOIN mdl_user_info_field as fls on fls.id = datas.fieldid
	LEFT JOIN mdl_user_enrolments as u_enrol on u_enrol.userid = u.id
	LEFT JOIN mdl_enrol as enrol on enrol.id = u_enrol.enrolid
	#WHERE f.component like 'profilefield_file' and f.source  IS NOT NULL
	GROUP BY u.id
	) tbl
	#WHERE
	#custom  LIKE '%skills%' AND  custom LIKE '%about%' AND  custom LIKE '%talent_platform%'
	WHERE custom REGEXP 'skills|talent_platform'
	AND ".$id."
	AND ".$city."
	AND ".$proff."
	AND ".$skills."
	ORDER BY id DESC
	LIMIT ".$offset.",".$limit;

#echo $new_query;


$conn->query("SET CHARACTER SET utf8");
$result = mysqli_query($conn, $new_query) or die (mysqli_error($conn));


//header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");
if(!empty($result->num_rows) && $result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $return_users_array["id"] = $row["id"];
	$return_users_array["name"] =$row["firstname"];
	$return_users_array["lastname"] = $row["lastname"];
	//$return_users_array["avatar"] = $row["profileimageurl"]
	$return_users_array["city"] = $row["city"];
	$return_users_array["email"] = $row["email"];
	$custom =trim(preg_replace('/\s+/', ' ', $row["custom"]));
	$return_users_array["avatar"] ="http://learn.ilmhona.org/webservice/pluginfile.php/".$row["contextid"]."/profilefield_file/files_20/".$row["id"]."/".$row["filename"]."?token=03363bd8b910b9f35e2c46a8e204a82c";
	$custom = "[{".$custom."}]";
	$arr = json_decode($custom);
	//print_r($arr);
	if (is_array($arr) || is_object($arr))
	{
		foreach ($arr as $key){
		//print_r($key);

			if(property_exists($key,"work_status") && strtolower($key->work_status) == strtolower("ready to work")) $return_users_array["canWork"] = true;
			else $return_users_array["canWork"] = false;
			if(property_exists($key,"skills"))$return_users_array["skills"] = getSkills($key->skills,$row["id"]);
			if(property_exists($key,"about")){
				if(!empty($key->about))
					$return_users_array["about"] = str_replace('&nbsp;',' ',strip_tags($key->about));
				else
					$return_users_array["about"] = str_replace('&nbsp;',' ',strip_tags($key->about_user));
			}
			//if(property_exists($key,"about"))$return_users_array["education"] = strip_tags($key->about);
			if(property_exists($key,"proff"))$return_users_array["proff"] = strip_tags($key->proff);
			if(property_exists($key,"github"))$return_users_array["github"] = strip_tags($key->github);
			if(property_exists($key,"linkedin"))$return_users_array["linkedin"] = strip_tags($key->linkedin);
			if(property_exists($key,"facebook"))$return_users_array["facebook"] = strip_tags($key->facebook);
			if(property_exists($key,"personal_website"))$return_users_array["personal_website"] = strip_tags($key->personal_website);
		}
	}
    	array_push($user_data["records"], $return_users_array);
  }
	//Get total row for pagination
	$total_res = $conn->query("SELECT FOUND_ROWS();");
	$total_res = $total_res->fetch_row();
	$user_data['total_row']= $total_res[0];

  	// set response code - 200 OK
	http_response_code(200);
	// show products data in json format
	echo json_encode($user_data,JSON_UNESCAPED_UNICODE);
} else {
    // set response code - 404 Not found
    http_response_code(404);

    // tell the user no products found
    echo json_encode(
        array("message" => "No data found.")
    );

}


function getSkills($skills, $id) {

$skills = explode(" ",$skills);
//get Badges verified
$api_url = "http://learn.ilmhona.org/webservice/rest/server.php?wstoken=03363bd8b910b9f35e2c46a8e204a82c&moodlewsrestformat=json&wsfunction=core_badges_get_user_badges&userid=".$id;
$getJson = file_get_contents($api_url);
$badges = json_decode($getJson);
if(isset($badges->badges)){
	$badges= array_map(function($item) {
			return strtolower($item->name);
		},$badges->badges);
	$verified = array_map(function($item) use ($badges){
		$data = in_array(strtolower($item),$badges)? ["val"=>$item,"verified"=>true] : ["val"=>$item,"verified"=>false];
		return $data;
		},$skills);
}else{

	$verified = array_map(function($item) use ($badges){

		return  ["val"=>$item,"verified"=>false];

		},$skills);
}

return $verified;
}
?>
