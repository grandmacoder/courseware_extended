<?php
/**
 * Admin only AJAX functions.
 */ 
if (isset($_GET['term'])){
global $wpdb;
$userinfo = $wpdb->get_results($wpdb->prepare("select id, display_name, user_email from wp_users where display_name like %s or user_email like %s",'%' . $wpdb->esc_like($_REQUEST['term']) . '%',$wpdb->esc_like($_REQUEST['term']) . '%', OBJECT));
foreach ($userinfo as $info){
$thelabel = $info->display_name ."-". $info->user_email ;
$results[]=array('id'=>$info->user_email,'label' =>$thelabel);
}
echo json_encode($results);
die();
}

function CE_AJAX_getCourseUnitSelectOptions(){
global $wpdb;
$course_id = $_POST['course_id'];
$rows = $wpdb->get_results($wpdb->prepare("select post_title, ID from wp_posts p, wp_wpcw_units_meta m where p.ID = m.unit_id and parent_course_id = %d", $course_id, OBJECT));
$optionstring="<option value=0>----None---</option>";
if ($rows){
foreach ($rows as $row){
$optionstring.="<option value=". $row->ID .">".$row->post_title."</option>";
}	
}
//get prepopulate items
$rows =$wpdb->get_results($wpdb->prepare("Select * from wp_wpcw_course_extras where course_id = %d", $course_id, OBJECT));
foreach ($rows as $row){
$course_logo_path=$row->course_logo_path;
$row->course_start_page_path == "" ? $course_start_page_path = 0 : $course_start_page_path=preg_replace('/\D/', '', $row->course_start_page_path);
$course_type=$row->course_type;
$row->post_test_id == "" ? $post_test_id=0 : $post_test_id=$row->post_test_id;
$coach_id=$row->coach_id;
$enrollment_key=$row->enrollment_key;
$row->course_intro_page_path =="" ? $course_intro_page_path=0 : $course_intro_page_path=preg_replace('/\D/', '', $row->course_intro_page_path);
$start_date=$row->start_date;
$study_guide_path=$row->study_guide_path;
$max_enrolled=$row->max_enrolled;
$entry_id= $row->entry_id;
$wid=$row->wid;
}
//get the email addresses for the coaches
if ($coach_id <> ""){
$rows = $wpdb->get_results("Select user_email from wp_users where ID IN (".$coach_id.")", OBJECT);
foreach ($rows as $row){
$coachEmails .= $row->user_email .",";
}
}
$ajaxResults=array(
'options'=> $optionstring ,
'course_logo_path'=>$course_logo_path,
'course_start_page_path'=>$course_start_page_path,
'course_type'=>$course_type,
'post_test_id'=>$post_test_id,
'course_intro_page_path'=>$course_intro_page_path,
'enrollment_key'=>$enrollment_key,
'coach_emails'=>$coachEmails,
'start_date'=>$start_date,
'study_guide_path'=>$study_guide_path,
'max_enrolled'=>$max_enrolled,
'entry_id'=>$entry_id,
'wid'=>$wid,
);
echo json_encode($ajaxResults);
die();
}
function CE_AJAX_saveUpdateExtras(){
global $wpdb;
//find out if there is a record in course extras yet
$hasExtras = $wpdb->get_var("Select count(*) from wp_wpcw_course_extras where course_id =" . $_POST['course_id']);
//initialize variables
if ($_POST['post_test_id'] == 0){$post_test_id ="";}
if ($_POST['needkey'] == 1){
	if ($_POST['enrollment_key'] == ""){
	$enrollment_key = strtoupper(md5(uniqid(rand(),true))); 
	}
	else{
	$enrollment_key =$_POST['enrollment_key'];	
	}
}
if (strlen($_POST['coach_list'])  > 0){
$aCoaches=explode(',',$_POST['coach_list']);
	for ($i=0; $i < count($aCoaches)-1; $i++){
		$coachEmail = substr($aCoaches[$i], strpos($aCoaches[$i],'-') + 1, -1);
		$userid=$wpdb->get_var("Select ID from wp_users where user_email like '%". $coachEmail."%'");
		$coachIDs.=$userid .",";
	}
$coachIDs =substr($coachIDs, 0, -1);
}
else{
$coachIDs ="";
}
$_POST['course_intro_page_id'] > 0 ? $courseIntroPage='/?p='.$_POST['course_intro_page_id']."/" : $courseIntroPage="";
$_POST['course_start_page_id'] > 0 ? $courseStartPage='/?p='.$_POST['course_start_page_id']."/" : $courseStartPage="";
if ($_POST['upload_image'] <> ""){
$siteurl=get_site_url();
$logopath=str_replace($siteurl,"",$_POST['upload_image']);
}
else{
$logopath="";	
}
if ($hasExtras > 0){
//update
$wpdb->update( 
	'wp_wpcw_course_extras', 
	array( 
        'course_logo_path' => $logopath,
		'course_start_page_path' => $courseStartPage,
		'course_type' => $_POST['course_type'] ,
		'post_test_id' => $_POST['post_test_id'],
		'national_module' => 1,
		'course_intro_page_path' => $courseIntroPage,
		'enrollment_key' => $enrollment_key,
		'coach_id' => $coachIDs,
		'start_date' => $_POST['start_date'],
		'study_guide_path' => $_POST['upload_study_guide'],
		'max_enrolled' => $_POST['max_enrolled'],
		'wid' => $_POST['wid'],
	    'entry_id'=>$_POST['entry_id'],
	), 
	array( 'course_id' => $_POST['course_id']), 
	array(  '%s', '%s','%s','%d','%d','%s','%s','%s','%s','%s','%d'), 
	array( '%d' ) 
);
}
else{
//insert
$wpdb->query( $wpdb->prepare( 
	   "INSERT INTO wp_wpcw_course_extras
		( course_id,course_logo_path,course_start_page_path,course_type,post_test_id,national_module,course_intro_page_path,enrollment_key,coach_id,start_date,study_guide_path,max_enrolled,wid,entry_id)
		VALUES ('%d', '%s', '%s','%s','%d','%d','%s','%s','%s','%s','%s','%d','%s','%s')",
		$_POST['course_id'],
		$logopath,
		$courseStartPage,
		$_POST['course_type'] ,
		$_POST['post_test_id'],
		1,
		$courseIntroPage,
		$enrollment_key,
		$coachIDs,
		$_POST['start_date'],
		$_POST['upload_study_guide'],
		$_POST['max_enrolled'],
		$_POST['wid'],
		$_POST['entry_id']
		));
}
$ajaxResults=array(
'course_id' =>$_POST['course_id'],
'course_logo_path'=>$_POST['upload_image'],
'course_start_page_path'=>$_POST['course_start_page_id'],
'course_type'=>$_POST['course_type'],
'post_test_id'=>$_POST['post_test_id'],
'course_intro_page_path'=>$_POST['course_intro_page_id'],
'enrollment_key'=>$_POST['needkey'],
'enrollment_key'=>$enrollment_key,
'coach_emails'=>$_POST['coach_list'],
'start_date'=>$_POST['start_date'],
'study_guide_path'=>$_POST['ad_study_guide'],
'max_enrolled'=>$_POST['max_enrolled'],
'sql'=> $sql,
);
echo json_encode($ajaxResults);
die();
}
//register function calls with wordpress by adding wp_ajax to callback for function to run
//call the function with wp_ajax key, excluding the wp_ajax part in ajax call
add_action('wp_ajax_ce_course_unit_options', 'CE_AJAX_getCourseUnitSelectOptions');
add_action( 'wp_ajax_nopriv_ce_course_unit_options', 'CE_AJAX_getCourseUnitSelectOptions' );
add_action('wp_ajax_ce_auto_complete_users', 'CE_AJAX_autoCompleteUsers');
add_action( 'wp_ajax_nopriv_ce_auto_complete_users', 'CE_AJAX_autoCompleteUsers' );
add_action('wp_ajax_ce_save_update_extras', 'CE_AJAX_saveUpdateExtras');
add_action( 'wp_ajax_nopriv_ce_save_update_extras', 'CE_AJAX_saveUpdateExtras' );
?>