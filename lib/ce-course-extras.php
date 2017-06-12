<?php
function add_courseware_extras(){
if(!current_user_can('manage_options')){
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
$courseExtrasList = new Course_Extras_List_Table();
$courseExtrasList->prepare_items();
?>
 <style>
   .message {font-size: 18px; color: #000; width: 50%; line-height: 150%; background-color:#ffebe8; border:1px solid #cc0000; }
   </style>
   <div class='wrap'>
                <h2>Course Extras Listing</h2>
				<div>
              <?php  $courseExtrasList->display(); ?>
              </div>
			<br><br>
			
		  <div id="course_extra_edit_area">
		  <h3>Choose a course to edit from the list above. The information will show below.</h3>
		  <div id="message_area"></div>
		  <form id="course_extras_form" name="course_extras_form">
			   <input type="hidden" name="course_id" id="course_id" value="">
			  <hr />
				 <div id="current_logo"></div>
				    <div class="form-group">
					<div id="logo_upload">
					<label for="upload_image">Upload/Add a course logo</label>
					<input id="upload_image" type="text"  name="upload_image" value="" class="form-control" style="width:auto;" /> 
					<input id="upload_image_button" class="button" type="button" value="Select or Upload Image" />
					</div>
				</div>
				<div class="form-group">
				<label for="course_intro_page_id"> Choose the introductory page for the course.</label>
				<select id ="course_intro_page_id" name="course_intro_page_id" class="form-control" style="width:auto;">
				<option value='0'>----None---</option>
				<?php
				$args= array( 'posts_per_page' => 100, 'offset'=> 0, 'category' =>get_option('intro_page_categories'), 'post_status'=>'publish','post_type'=>'post','post_status'=>'publish', 'orderby'=>'post_title','order'=>'ASC');
				$posts = get_posts($args);
				foreach ($posts as $post){
					echo "<option value=". $post->ID.">". $post->post_title ."</option>";
				}
				?>
				</select>
				</div>
				<div class="form-group">
				<label for="course_start_page_id"> Choose the page that the course starts on.</label>
				<select id ="course_start_page_id" name="course_start_page_id" class="form-control" style="width:auto;">
				<option value=0>----None---</option>
				<?php
				$args= array( 'posts_per_page' => 100, 'offset'=> 0, 'category' =>get_option('start_page_categories'), 'post_status'=>'publish','post_type'=>'post','post_status'=>'publish', 'orderby'=>'post_title','order'=>'ASC');
				$posts = get_posts($args);
				foreach ($posts as $post){
					echo "<option value=". $post->ID.">". $post->post_title ."</option>";
				}
				?>
				</select>
				</div>
				<div class="form-group">
                <label for="post_test_id"> Choose the page with the post test.</label>
				<select id ="post_test_id" name="post_test_id" class="form-control" style="width:auto;"></select>	
                </div>
				<div class="form-group"><div class="form-group">
				<label for="course_type"> Choose type of course</label>
				<select id ="course_type" name="course_type" class="form-control" style="width:auto;">
				<?php
				$aCourseTypes =get_option('course_types');
				for ($i=0;$i<count($aCourseTypes); $i++){
				echo "<option value='". $aCourseTypes[$i] . "'";
				if ($aCourseTypes[$i] == 'inactive'){echo " selected ";}
				echo ">". $aCourseTypes[$i] ."</option>";	
				}
				?>
				</select>
				</div>
				<hr />
				<h3>These items pertain to LERN topics.</h3>
			    <div class="form-group">
		        <label for="enrollment_key">Does this course need an enrollment key?</label><br />
				<input type="checkbox" id="enrollment_y" name="enrollment_y"/> Yes, create an enrollment key.</input><br>
				<label for="enrollment_key">Enrollment key:</label>		
				<input  type="text" readonly id='enrollment_key' name='enrollment_key'></input><br>
			    </div>
				<div class="form-group">
				<label for="start_date">Enter the start date if there is one.</label>				
				<input type="text" class="custom_date" name="start_date" id="start_date" class="form-control" /><br>
				</div>
				<div class="form-group">
				<label for="max_enrolled">Enter the maximum enrollment if you are limiting enrollment.</label>				
				<select id="max_enrolled" name= "max_enrolled" class="form-control" style="width:auto;">	<?php for ($i=0; $i <= 200; $i++){echo "<option value=". $i.">". $i."</option>";}?></select>
				</div>
                <div class="form-group">				
				<div id="studyguide_upload">
					<label for="upload_image">Add the study guide path or a url if you are including a study guide.</label>
					<input id="upload_study_guide" type="text" size="36" name="upload_study_guide" value="" class="form-control" style="width:auto;"/> 
					<input id="upload_studyguide_button" class="btn btn-default" type="button" value="Select or Upload Study Guide" />
				</div>
				</div>
				<div class="form-group">
				<p>Does this course have a coach/coaches? Find them by entering part of their name or email address.</p>
			     <label class="screen-reader-text" for="user-search-input">Search Users:</label>
				 <input size="60"  id="user-search-input" name="s" value="" class="ui-autocomplete-input" autocomplete="off">
                 </br>
				</div>
				<div class="form-group">
				 <label for="coach_list">Coach email(s) will appear as you select them.</label>
				 <input id="coach_list" name="coach_list" value="" size="80">
				 </div>
				 <div class="form-group">
				 <label for="wid">If relevant, enter the wid for the main video in the course (LERN).</label>
				 <input id="wid" name="wid" value="" size="20">
				 </div>
				 <div class="form-group">
				 <label for="entry_id">If relevant, enter the entry_id for the main video in the course (LERN).</label>
				 <input id="entry_id" name="entry_id" value="" size="20">
				 </div>
				<input type=button name="btnSaveExtras" value="Save Course Extra Info" id ="btnSaveExtras" class="btn btn-default>
			   </form>
		  </div>

<?php
}
class Course_Extras_List_Table extends WP_List_Table{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */

    public function prepare_items()
    {
		isset($_GET['orderby'])?$orderby=$_GET['orderby']:$orderby='course_id';
		isset($_GET['order'])?$order=$_GET['order']:$order='asc';
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
		$data = $this->table_data($orderby, $order );
        $perPage =10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array    array('Course Name'=>$course_name 'Course ID'=>$course_id, 'Course Type'=>$course_type);
     */
    public function get_columns()
    {
        $columns = array(
		    'Course ID'		=> 'COURSE ID',
            'Course Name'   => 'COURSE NAME',  
            'Course Type' => 'COURSE TYPE',
        );
        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('Course Name' => array('course_title', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data($orderby, $order)
    {   global $wpdb;
		$courses = $wpdb->get_results($wpdb->prepare("select course_id, course_title from wp_wpcw_courses  order by " . $orderby . " " . $order), OBJECT);
		$courses_array = array();
			foreach($courses as $course){
			//select the course type
			$course_type=$wpdb->get_var("Select course_type from wp_wpcw_course_extras where course_id=". $course->course_id);
			$qsURL =add_query_arg( array('course_id'=>$course->course_id,'action'=>'edit-course-extras'));
			$courses_array[] = array('Course ID'=>$course->course_id,'Course Name'=>"<a href='".$qsURL."' id='".$course->course_id."' class='add_course_extras'>".$course->course_title ."</a>", 'Course Type'=>$course_type);
			}
		return $courses_array;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'Course ID':
            case 'Course Name':
            case 'Course Type':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    public function sort_data( $a, $b )
    {
	
        // Set defaults
        $orderby = 'course_title';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
			
        }
   
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }

        $result = strcmp( $a[$orderby], $b[$orderby] );
		
		if($order === 'asc')
        {
            return $result;
        }

        return $result;
    }
}
?>