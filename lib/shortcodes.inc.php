<?php
class Coursewareplus {
  // additional properties and methods here
}
/**
 * Creates widget that shows off a training course, its modules, and its units.
 *
 *  e.g. [wpcourse course="2" showunits="true" /]
 */
function TCCW_shortcodes_showTrainingCourse($atts, $content)
{
	extract( shortcode_atts( array(
		'course' 		=> 0,
	), $atts));


	// Just pass arguments straight on
	return TCCW_courses_renderCourseList($course, $atts);
}


/**
 * Function that creates a list of units.
 *
 * @param Integer $courseID The ID of the course to show.
 * @param Array $options The list of options to show.
 */
function TCCW_courses_renderCourseList($courseID, $options)
{
	extract(shortcode_atts(array(
		'module'			=> 0,
		'module_desc'		=> false,
		'show_title'		=> false,
		'show_desc'			=> false,

		// Hide the credit link if requested
		'hide_credit_link'	=> false,

		// Easy way to determine if in widget mode
		'widget_mode'		=> false,

		// Default not to toggle .
		'show_toggle_col' 	=> false,

		// Handle widget showing/hiding capability.
		'show_modules_previous'		=> 'all',
		'show_modules_next'			=> 'all',
		'toggle_modules' 			=> 'expand_all',

		// Handle quiz grade on unit
		'user_quiz_grade' => false,

	), $options));

	// Check settings to to see if they are true
	$module_desc 	= ($module_desc == 'true');
	$show_title		= ($show_title 	== 'true');
	$show_desc		= ($show_desc 	== 'true');

	$courseDetails = false;
	$parentData    = false;

	global $post;
	if (!$post) {
		return;
	}

	// Show course based on current location for user. Use the currently shown post
	// to work out which course to show using the associated parent data.
	if ('current' == $courseID)
	{
		$parentData = WPCW_units_getAssociatedParentData($post->ID);
		if ($parentData)
		{
			$courseDetails = WPCW_courses_getCourseDetails($parentData->parent_course_id);
			$courseID = $parentData->parent_course_id;
		}

		// Nothing to show, so don't.
		else {
			return false;
		}
	}

	// Just check for the course ID as usual
	else
	{
		// Check course ID is valid
		$courseDetails = WPCW_courses_getCourseDetails($courseID);
		if (!$courseDetails) {
			return __('Unrecognised course ID.', 'wp-courseware');
		}

		// Course ID is fine, get associated parent data for
		// hiding aspects of the widget
		$parentData = WPCW_units_getAssociatedParentData($post->ID);
	}

	$moduleList = false;

	// Do we just want a single module?
	if ($module > 0)
	{
		// Get module by module number within course (not the module ID)
		$moduleDetailsSingle = WPCW_modules_getModuleDetails_byModuleNumber($courseDetails->course_id, $module);
		if (!$moduleDetailsSingle) {
			return __('Could not find module.', 'wp-courseware');
		}

		// Create module list of 1 using single module
		$moduleList[$moduleDetailsSingle->module_id] = $moduleDetailsSingle;
	}

	// Nah, we want multiple modules...
	else {
		// Check there are modules
		$moduleList = WPCW_courses_getModuleDetailsList($courseID);
		if (!$moduleList) {
			return __('There are no modules in this training course.', 'wp-courseware');
		}
	}

	$html = false;


	//Show user message if they are not logged on and trying to work on module
	$userId = get_current_user_id();
	if($userId == 0){
	$html .= "<h3 style='color:red; font-size: 18px;'>To work on this module please login with your email and password on the left. </h3>";
	}

	// #### Show course title/description. $courseDetails->course_title .
	if ($show_title) {
		global $wpdb;
		$summary_post_id = $wpdb->get_var($wpdb->prepare("SELECT course_start_page_path FROM wp_wpcw_course_extras WHERE course_id =%d",$courseID));
		$html .= sprintf('<div class="wpcw_fe_course_title_widget"><strong>'. $courseDetails->course_title.'</strong><div><a href="/blog/'.$summary_post_id.'"> Your Progress </a> </div></div><span style="font-size: 12px;">( green items are completed )</span>') ;
	}

	if ($show_desc) {
		$html .= sprintf('<div class="wpcw_fe_course_desc">%s</div>', $courseDetails->course_desc);
	}

	$html .= '<table id="wpcw_fe_course" class="wpcw_fe_table" cellspacing="0" cellborder="0">';



	$showUnitLinks = false; 		// If true, show links to the units
	$colCount = 2; 					// Number of columns in the table


	// UP Object to determine what to show to the user.
	$userProgress = false;

	// Check user is logged in, and if they can access this course
	$user_id = get_current_user_id();
	if ($user_id != 0)
	{
		$userProgress = new UserProgress($courseID, $user_id);

		// Show links for user if they are allowed to access this course.
		if ($userProgress->canUserAccessCourse())
		{
			// User is logged in and can do course, so show the stuff they can do.
			$showUnitLinks = true;

			// Got an extra column to show progress
			$colCount = 3;
		}
	}

	// If we're showing a widget, and we have the parent data based on the
	// currently viewed unit, then change what's in the widget in terms
	// of previous/next units.
	$hideList = array();
	if ($widget_mode && $module == 0 && $parentData)
	{
		// Build a list of the modules before and after the current
		// module, so that we can more easily control what's visible,
		// and what's not.
		$modulesBefore = array();
		$modulesAfter = array();

		$currentList = &$modulesBefore;
		foreach ($moduleList as $moduleID => $moduleObj)
		{
			// Switch lists, we've found the current module
			if ($moduleID == $parentData->parent_module_id) {
				$currentList = &$modulesAfter;
			}
			// Any other module, just add to the list (which is either the before or after).
			else {
				$currentList[] = $moduleID;
			}
		}

		// Handle showing previous modules
		switch ($show_modules_previous)
		{
			// All all items in the before list to be hidden
			case 'none':
					$hideList = array_merge($hideList, $modulesBefore);
				break;

			case 'all':
				break;

			// Keep a specific number of modules to show.
			default:
				 	$show_modules_previous += 0;
				 	$modulesToPickFrom = count($modulesBefore);

				 	// Remove the modules at the start of the list, leaving the right number of
				 	// $show_modules_previous modules in the list.
				 	if ($show_modules_previous > 0 && $modulesToPickFrom > $show_modules_previous) {
				 		$hideList = array_merge($hideList, (array_slice($modulesBefore, 0, ($modulesToPickFrom - $show_modules_previous))));
				 	}
				break;
		} // end switch

		// Handle showing the next modules.
		switch ($show_modules_next)
		{
			// All all items in the after list to be hidden
			case 'none':
					$hideList = array_merge($hideList, $modulesAfter);
				break;

			case 'all':
				break;

			// Keep a specific number of modules to show.
			default:
				 	$show_modules_next += 0;
				 	$modulesToPickFrom = count($modulesAfter);

				 	// Remove the modules at the start of the list, leaving the right number of
				 	// $show_modules_previous modules in the list.
				 	if ($show_modules_next > 0 && $modulesToPickFrom > $show_modules_next) {
				 		$hideList = array_merge($hideList, (array_slice($modulesAfter, $show_modules_next)));
				 	}
				break;
		} // end switch
	}
global $wpcwdb, $wpdb;
//see if this course has a pre AND a post test
$pretestUnitID =$wpdb->get_var($wpdb->prepare("SELECT ID from wp_posts p,  wp_wpcw_units_meta c where p.ID = c.unit_id  and post_title like '%pre-test%' and post_type='%s' and parent_course_id =%d",'course_unit',$courseID)); 
$postTestUnitID =$wpdb->get_var($wpdb->prepare("SELECT ID from wp_posts p,  wp_wpcw_units_meta c where p.ID = c.unit_id  and post_title like '%post-test%' and post_type='%s' and parent_course_id =%d", 'course_unit',$courseID)); 
$courseType = $wpdb->get_var("SELECT course_type from wp_wpcw_course_extras where course_id=". $courseID); 
//if this is a course that is a training course with a pre and post test then do this
if ($pretestUnitID  > 0 && $postTestUnitID > 0 || $courseType =="LERN"){
	//get the total units
	$wpdb->show_errors();
	$totalUnits = 0;
        $numCompleted=0;
	$enableUnits=0;
	$enablePostTest=0;
	$enableSurvey=0;
	$postTestCompletedUnitID=0;
	$surveyCompletedUnitID=0;
	$pretestCompletedUnitID=0;
	$postTestID = 0;
	$preTestID = 0;
	$moduleSurveyID =0;
	$userid = get_current_user_id();	
	$sql = $wpdb->prepare("select count(*) as totalUnits from 
				$wpcwdb->units_meta u
				where parent_course_id = %d", $courseID);
	
	 $totalUnits= $wpdb->get_var($sql);
	
	//get the total completed units
	$sql = $wpdb->prepare("select count(*) as numCompleted from 
				$wpcwdb->user_progress p,
				$wpcwdb->units_meta u
				where p.unit_id = u.unit_id and
				u.parent_course_id = %d
				and unit_completed_status='complete' 
				and
				p.user_id = %d", $courseID, $userid);
	
	$numCompleted = $wpdb->get_var($sql);
	$pretestCompletedUnitID =$wpdb->get_var("SELECT ID from wp_posts p, wp_wpcw_units_meta c, wp_wpcw_user_progress u where p.ID = c.unit_id and u.unit_id = c.unit_id and u.unit_id = p.ID and u.user_id =". $userid." and post_title like '%pre-test%' and post_type='course_unit' and parent_course_id = ". $courseID); 
	$postCompletedTestUnitID =$wpdb->get_var("SELECT ID from wp_posts p, wp_wpcw_units_meta c, wp_wpcw_user_progress u where p.ID = c.unit_id and u.unit_id = c.unit_id and u.unit_id = p.ID and u.user_id =". $userid." and post_title like '%post-test%' and post_type='course_unit' and parent_course_id = ". $courseID); 
	$surveyCompletedUnitID =$wpdb->get_var("SELECT ID from wp_posts p, wp_wpcw_units_meta c, wp_wpcw_user_progress u where p.ID = c.unit_id and u.unit_id = c.unit_id and u.unit_id = p.ID and u.user_id =". $userid." and post_title like '%satisfaction survey%' and post_type='course_unit' and parent_course_id = ". $courseID); 
	$preTestID = $wpdb->get_var("SELECT ID from wp_posts p, wp_wpcw_units_meta c where p.ID = c.unit_id and post_title like '%pre-test%' and post_type='course_unit' and parent_course_id = ". $courseID);  
	$postTestID = $wpdb->get_var("SELECT ID from wp_posts p, wp_wpcw_units_meta c where p.ID = c.unit_id and post_title like '%post-test%' and post_type='course_unit' and parent_course_id = ". $courseID);  
	$moduleSurveyID = $wpdb->get_var("SELECT ID from wp_posts p, wp_wpcw_units_meta c where p.ID = c.unit_id and post_title like '%satisfaction survey%' and post_type='course_unit' and parent_course_id = ". $courseID); 
	
	//see if the user has finished the pretest or if it is a LERN module --  if yes enable all items except the post test and the satisfaction survey -- if no only enable the pretest disable all other links set title on links
	if ($pretestCompletedUnitID > 0 || $courseType="LERN" ){$enableUnits=1;}
	//see if the user has completed all the items except the post test -- enable the post test total-2 = completed
	if ($pretestCompletedUnitID > 0 && ($totalUnits - 2) == $numCompleted){$enablePostTest =1;}
	//see if the user has completed everything ---offer the satisfaction survey total-1 = completed
	 if ($pretestCompletedUnitID > 0 && ($totalUnits-1) == $numCompleted){$enableSurvey = 1;}
	 	//see if the user has completed everything on a LERN course type ---offer the satisfaction survey total-1 = completed
if ($totalUnits-1 == $numCompleted && $courseType ="LERN" ){$enableSurvey = 1;}
}
else{
$enableUnits = 1; //just enable all the units
}	


	// Columns for marking item as being pending or complete.
	$progress_Complete 	= '<td class="wpcw_fe_unit_progress wpcw_fe_unit_progress_complete"><span>&nbsp;</span></td>';
	$progress_Pending 	= '<td class="wpcw_fe_unit_progress wpcw_fe_unit_progress_incomplete"><span>&nbsp;</span></td>';
	$progress_Blank 	= '<td class="wpcw_fe_unit_progress"><span>&nbsp;</span></td>';

	// Show modules
	foreach ($moduleList as $moduleID => $moduleObj)
	{
		// See if we're skipping this module
		if (in_array($moduleID, $hideList)) {
			continue;
		}

		// If $collapseTitleArea is set to true, then the module will be collapsed. So just check what to hide
		// based on the contents of $toggle_modules
		$collapseTitleArea = false;
		if ($widget_mode)
		{
			switch ($toggle_modules)
			{
				case 'contract_all':
					$collapseTitleArea = true;
				break;

				// See if the currently visible unit module is the one being rendered.
				case 'contract_all_but_current':
					$collapseTitleArea = true; // Contract all by default.

					// We're showing the current module.
					if ($parentData && $moduleID == $parentData->parent_module_id) {
						$collapseTitleArea = false;
					}
				break;

				// Default is not to collapse.
			}
		}
       $quizGradeArea = false;
        // We're showing the toggle section, so add it.
		if ($show_toggle_col) {
			$moduleTitleArea = false;
			$moduleTitleArea = sprintf('<td class=sessionHeading>%s</td><td class="wpcw_fe_toggle">%s</td>', strtoupper($moduleObj->module_title), ($collapseTitleArea ? '+' : '-'));	
		}
        // No toggle section, so extend the row to correctly fill the width.
		else {
			$moduleTitleArea = sprintf('<td colspan="%d">%s</td>', $colCount-1, $moduleObj->module_title);
			//Add quiz grade column header only if shortcode param is true, isn't a widget and user is logged in.
			if($user_quiz_grade && !$widget_mode && is_user_logged_in())
			{
				if ($userProgress->canUserAccessCourse())
				{
					$moduleTitleArea = sprintf('<td colspan="%d">%s</td>', $colCount-2, $moduleObj->module_title);
					$quizGradeArea = sprintf('<td colspan="%d">%s</td>', $colCount-1, __('Quiz Grade','wp-courseware'));
				}
			}
		}

		// Render final title bit
		$html .= sprintf('<tr class="wpcw_fe_module %s %s" id="wpcw_fe_module_group_%d">
							<td>%s %d</td>
							' . $moduleTitleArea . '
							' . $quizGradeArea . '
						</tr>',
							($collapseTitleArea ? 'wpcw_fe_module_toggle_hide' : ''),
							($parentData && $moduleID == $parentData->parent_module_id ? 'active' : ''),
							$moduleObj->module_number, __('Module', 'wp-courseware'),
							$moduleObj->module_number,
							$moduleTitleArea
						);

		// Render module description
		if ($module_desc){
			// Check if user_quiz_grade param is being used and if user is logged in
			if ($user_quiz_grade && $user_id != 0){
					if ($userProgress->canUserAccessCourse()){
						$html .= sprintf('<tr class="wpcw_fe_module_des"><td colspan="%d">%s</td></tr>', $colCount+1, $moduleObj->module_desc);
					}else{
						$html .= sprintf('<tr class="wpcw_fe_module_des"><td colspan="%d">%s</td></tr>', $colCount, $moduleObj->module_desc);
					}
			}else{
				// Default module description
				$html .= sprintf('<tr class="wpcw_fe_module_des"><td colspan="%d">%s</td></tr>', $colCount, $moduleObj->module_desc);
			}
		}

		// Add the class for the row that matches the parent module ID.
		$moduleRowClass = ' wpcw_fe_module_group_' . $moduleObj->module_number;

		// ### No Units Line
		$units = WPCW_units_getListOfUnits($moduleID);
		if (!$units)
		{
			$extraColSpan = 0;
			if ($show_toggle_col) {
				$extraColSpan = 1;
			}

			$html .= sprintf('<tr class="wpcw_fe_unit wpcw_fe_unit_none %s">
						<td colspan="%d">%s</td>
					  </tr>',
						$moduleRowClass,
						$colCount+$extraColSpan, __('There are no units in this module.', 'wp-courseware')
					);
		}

		// ### Show Units
		else
		{
			// Render each unit
			foreach ($units as $unit)
			{
				$progressRow = false;
				$progressCol = false;
				$quizGradeCol = false;
				// Show links for units
					// Show links for units
				if ($showUnitLinks)
				{
                                   				
		               // Yes we are showing progress data... see what state we're at.
					if ($userProgress)  {	
						if ($userProgress->isUnitCompleted($unit->ID)) {
							$progressCol = $progress_Complete;
							$progressRow = 'wpcw_fe_unit_complete';
						} else {
							$progressCol = $progress_Pending;
							$progressRow = 'wpcw_fe_unit_pending';
						}
					}	
					
					// See if the user is allowed to access this unit or not.
					if ($userProgress->canUserAccessUnit($unit->ID)){
                                           
					   if ($enableUnits == 0){
					                if ($unit->ID == $preTestID){
						            // Main unit title, link and unit number
						        $html .= sprintf('
							    <tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
								<td>%s</td>
								<td class="wpcw_fe_unit"><a href="%s">%s</a></td>
								'.$progressCol.'
							</tr>',   
						__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
					                }
							else{
							// Main unit title, link and unit number
						        $html .= sprintf('
							<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
								<td>%s </td>
								<td class="wpcw_fe_unit"><a href="%s" class=disabledUnit>%s</a><p class=disabledUnitHint> These items are disabled until you complete the pre test.</p></td>
								'.$progressCol.'
							</tr>',   
						__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
							}
							
					}
					elseif ($enableUnits > 0 || $enableUnits > 1){
					//if it is not the post test and it is not the survey or it is a learning module without a pre or post test
						if ($unit->ID <> $postTestID && $unit->ID <> $moduleSurveyID){
						
							if ( $enablePostTest > 0  && $postCompletedTestUnitID == 0  ){ //post test is enabled so disable everything else
							 $html .= sprintf('
								<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
									<td>%s</td>
									<td class="wpcw_fe_unit"><a href="%s"class=disabledUnit>%s</a><p class=disabledUnitHint>Sessions are disabled until you complete the post test.</p></td>
									'.$progressCol.'
								</tr>',   
							__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
							}
				
							else{ 
							  if ($unit->ID == $post->ID){
							 $html .= sprintf('<tr class="wpcw_fe_unit_current '.$progressRow . $moduleRowClass . '">
									<td>%s</td>
									<td class="wpcw_fe_unit"><a href="%s">%s</a></td>
									'.$progressCol.'
									</tr>',   
								__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
								}
							  else{
							  $html .= sprintf('<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
									<td>%s</td>
									<td class="wpcw_fe_unit"><a href="%s">%s</a></td>
									'.$progressCol.'
									</tr>',   
								__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
								}
							}
						}
						else{
							//if it is a post test
							if ($unit->ID == $postTestID){
							
								if ($enablePostTest > 0 || $postCompletedTestUnitID > 0 ){
								 $html .= sprintf('
									<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
										<td>%s</td>
										<td class="wpcw_fe_unit"><a href="%s">%s</a></td>
										'.$progressCol.'
									</tr>',   
								__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
								}
								else{
								 $html .= sprintf('
								<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
									<td>%s</td>
									<td class="wpcw_fe_unit"><a href="%s"class=disabledUnit>%s</a><p class=disabledUnitHint>Post test is disabled until you complete all the Sessions.</p></td>
									'.$progressCol.'
								         </tr>',   
							               __('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
								}
							
							}
							//if it is a survey
							if ($unit->ID == $moduleSurveyID){
								if ($enableSurvey > 0 || $surveyCompletedUnitID > 0){
								 $html .= sprintf('
									<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
										<td>%s</td>
										<td class="wpcw_fe_unit"><a href="%s">%s</a></td>
										'.$progressCol.'
									</tr>',   
								__('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
								}
								else{
								 $html .= sprintf('
								<tr class="wpcw_fe_unit '.$progressRow . $moduleRowClass . '">
									<td>%s</td>
									<td class="wpcw_fe_unit"><a href="%s"class=disabledUnit>%s</a><p class=disabledUnitHint>The survey is disabled until you complete all the items.</p></td>
									'.$progressCol.'
								         </tr>',   
							               __('', 'wp-courseware'), get_permalink($unit->ID), $unit->post_title);
								}
								
							}
							
						}
					}
					}
					else 
					{
						// If we're not allowed to access the unit, then it's always marked as pending.
						$html .= sprintf('
							<tr class="wpcw_fe_unit '. $progressRow . $moduleRowClass . '">
								<td>%s</td>
								<td class="wpcw_fe_unit">%s</td>
								'.$progress_Pending.'
							</tr>',  
						__('', 'wp-courseware'), $unit->post_title);
					}
			  }
                                // Don't show links for units (not logged in)
				else
				{
					$colspan = 1;
					if ($show_toggle_col) {
						$colspan = 2;	
					}
					
					$html .= sprintf('
					<tr class="wpcw_fe_unit '. $progressRow . $moduleRowClass. '">
						<td>%s %d</td>
						<td colspan="%d" class="wpcw_fe_unit">%s</td>
					</tr>',  
				__('', 'wp-courseware'), $unit->unit_meta->unit_number, 
				$colspan, $unit->post_title);
				}
			}
		} // end show units
}
$html .= '</table>';
return $html;
}




/**
 * Creates widget that shows off the user's progress on their respective courses.
 *
 *  e.g. [wpcourse_progress courses="2" user_progress="true" user_grade="true" /]
 */
function TCCW_shortcodes_showTrainingCourseProgress($atts, $content)
{
	extract( shortcode_atts( array(
		'courses' 			=> 'all',
		'user_progress' 	=> true,
		'user_grade' 		=> true,
		'user_quiz_grade'	=> false,
		'certificate'		=> true,
		'hide_credit_link'	=> false
	), $atts));

	// Check flags to see what we're showing
	$showUserProgress = ('true' == strtolower($user_progress));
	$showUserGrade    = ('true' == strtolower($user_grade));
	$showCertificate    = ('true' == strtolower($certificate));

	// Show a message to the user if they are not logged in.
	$user_id = get_current_user_id();
	if (!$user_id)
	{
		return sprintf('<div class="wpcw_fe_progress_box_wrap"><div class="wpcw_fe_progress_box wpcw_fe_progress_box_error">%s</div></div>',
			apply_filters('wpcw_front_shortcode_wpcourse_progress_notloggedin', __('You need to be logged in to see your course progress.', 'wp-courseware'))
		);
	}

	// Get a list of all of the courses that the user is subscribed to.
	$courseList = WPCW_users_getUserCourseList($user_id);
	$selectedCourseList = array();


	// Filter the list of courses to remove the ones that the trainer doesn't
	// want the user to see. 'all' means show all courses with no filtering.
	// Only do this check if we have any courses to check, to save time.
	if (!empty($courseList) && 'all' != strtolower($courses))
	{
		$selectedCourseList = explode(',', $courses);

		// This is the list of courses we'll actually use.
		$chosenListOfCourses = array();

		// We've got courses that have been specified, so we need to go through them now.
		if (!empty($selectedCourseList))
		{
			foreach ($selectedCourseList as $potentialItem)
			{
				$potentialItem = trim($potentialItem);

				// Got a potential ID here.
				if (preg_match('/^([0-9]+)$/', $potentialItem))
				{
					// Check each course we still have to see if the ID matches.
					// I know it's O(N), but it's simple at least.
					foreach ($courseList as $idx => $aSingleCourse)
					{
						// Got a match...
						if ($potentialItem == $aSingleCourse->course_id)
						{
							// Move the chosen course to the selected list. Doing
							// so makes subsequent searches faster.
							$chosenListOfCourses[] = $aSingleCourse;
							unset($courseList[$idx]);

							// Stop searching, we found it.
							break;
						}
					} // end foreach
				} // end ID check
			} // end foreach of potential IDs in list.
		}

		// Overwrite the list of courses to use.
		$courseList = $chosenListOfCourses;
	}


	// Handle when the list is empty
	if (empty($courseList))
	{
		// Change message slightly based on how many courses are selected.
		$messageToShow = __('You are not currently enrolled on any courses.', 'wp-courseware');
		if (!empty($selectedCourseList)) {
			$messageToShow = __('You are not currently enrolled on any of these courses.', 'wp-courseware');
		}

		return sprintf('<div class="wpcw_fe_progress_box_wrap"><div class="wpcw_fe_progress_box wpcw_fe_progress_box_error">%s</div></div>',
			apply_filters('wpcw_front_shortcode_wpcourse_progress_no_courses', $messageToShow, count($courseList))
		);
	}

	// Used to determine how many columns we have in the table for showing the course details.
	$columnCount = 1;

	// Show the list of courses
	$html = '<table id="wpcw_fe_course_progress" class="wpcw_fe_table wpcw_fe_summary_course_progress">';

		// The title bar for the course.
		$html .= '<thead><tr>';
			// Course name
			$html .= sprintf('<th class="wpcw_fe_course_progress_course">%s</th>', __('Course', 'wp-courseware'));

			// Course progress
			if ($showUserProgress) {
				$columnCount++;
				$html .= sprintf('<th class="wpcw_fe_course_progress_pc">%s</th>', __('Your Progress', 'wp-courseware'));
			}

			// Overall grade so far
			if ($showUserGrade) {
				$columnCount++;
				$html .= sprintf('<th class="wpcw_fe_course_progress_grade">%s</th>', __('Your Overall Grade', 'wp-courseware'));
			}

						// Overall grade so far
			if ($showCertificate) {
				$columnCount++;
				$html .= sprintf('<th class="wpcw_fe_course_progress_certificate">%s</th>', __('Certificate', 'wp-courseware'));
			}

		$html .= '</tr></thead><tbody>';


		// The main body of the course information.
		foreach ($courseList as $aSingleCourse)
		{
			$html .= '<tr class="wpcw_fe_course_progress_row">';

				// Course name
				$html .= sprintf('<td class="wpcw_fe_course_progress_course"><a href="#" data-toggle="wpcw_fe_course_progress_detail_%d">%s</a></td>', $aSingleCourse->course_id, $aSingleCourse->course_title);

				// Course progress
				if ($showUserProgress) {
					$html .= sprintf('<td class="wpcw_fe_course_progress_pc">%s</td>', WPCW_content_progressBar($aSingleCourse->course_progress));
				}

				// Show the Overall grade so far
				if ($showUserGrade) {
					$html .= sprintf('<td class="wpcw_fe_course_progress_grade">%s</td>', WPCW_courses_getCourseCumulativeGrade($aSingleCourse->course_id, $user_id));
				}

				// Show certificate for course if available
				if ($showCertificate) {
					$courseDetails 	= WPCW_courses_getCourseDetails($aSingleCourse->course_id);
					$usingCertificates = ('use_certs' == $courseDetails->course_opt_use_certificate);

					// Generate certificate button if enabled and a certificate exists for this user.
					if ($usingCertificates && $certificateDetails = WPCW_certificate_getCertificateDetails($user_id, $aSingleCourse->course_id, false))
					{
						$html .= sprintf('<td class="wpcw_fe_course_progress_certificate"><a href="%s" class="fe_btn fe_btn_download" target="_blank">%s</a></td>',
						WPCW_certificate_generateLink($certificateDetails->cert_access_key), __('Download Certificate', 'wp-courseware'));
					}else{
						$html .= sprintf('<td class="wpcw_fe_course_progress_certificate">%s</td>', __('Not available', 'wp-courseware'));
					}
				}

			$html .= '</tr>';

			// Show full course details. This might be a setting at some point.
			$html .= sprintf('<tr><td class="wpcw_fe_course_progress_detail" id="wpcw_fe_course_progress_detail_%d" colspan="%d">',
					$aSingleCourse->course_id, $columnCount
				);

				$html .= WPCW_courses_renderCourseList($aSingleCourse->course_id, array('hide_credit_link' => true,'user_quiz_grade' => $user_quiz_grade));
			$html .= '</td></tr>';

		}

		$html .= '</tbody></table>'; // end .wpcw_fe_summary_course_progress
	return $html;
}

/**
 * Creates an enrollment button for both "logged-on" or "logged-off" users.
 *
 *  e.g. [wpcourse_enroll courses="2,3" enroll_text="Enroll Here" ]
 */
function TCCW_create_enrollment_button($atts){

	extract( shortcode_atts( array(
		'courses' 		=> false,
		'enroll_text' 		=> 'Enroll Now'
	), $atts));

	//Do we have any course IDs at all?
	if (!$courses && !is_null($courses))
	{
		return;
	}

	//Get course ID's
	$courseList = explode(',', $courses);
	$availableCourseList = array();

	// Make sure the course ID's actually exist
	foreach ($courseList as $key => $courseID)
	{
		$courseDetails = WPCW_courses_getCourseDetails($courseID);
		if (!empty($courseDetails))
		{
			if(is_user_logged_in ())
			{
				$user_id = get_current_user_id();
				if (!WPCW_courses_canUserAccessCourse($courseID, $user_id))
				{
					$availableCourseList[] = $courseID;
				}
			}else{
					$availableCourseList['course_id['. $key .']'] = $courseID;
			}
		}
	}

	// If the user is logged in we will build an enrollment button
	if(is_user_logged_in ())
	{
		if($availableCourseList)
		{
			$courseIDs = implode("_",$availableCourseList);
			$html = sprintf('
				<div class="wpcw_fe_enroll_button" id="wpcw_fe_enroll_%s">
					<img src="%s/ajax_loader.gif" class="wpcw_loader" style="display: none;" />
					<a href="#" class="fe_btn fe_btn_completion btn_completion" id="enroll_%s">%s</a>
				</div>',$courseIDs, WPCW_plugin_getPluginPath() . 'img', $courseIDs, $enroll_text);
		}else{
			$html = sprintf('<div class="wpcw_fe_enroll_button">%s</div>',__('You have already been enrolled!', 'wp-courseware'));
		}
		return $html;
	}else{

		// Create registration button
		$courseEnrollmentButtonURL = wp_nonce_url(add_query_arg( $availableCourseList, wp_registration_url()),'wpcw_enroll','_wp_enroll') ;
		$html = sprintf('<a href="%s" class="fe_btn fe_btn_completion btn_completion" id="enroll_registration">%s</a>', $courseEnrollmentButtonURL,$enroll_text);
		return $html;
	}

}

?>