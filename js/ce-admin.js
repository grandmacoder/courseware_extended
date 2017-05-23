jQuery(document).ready(function($){
var current_page = $(location).attr('href');
//handle look for course id in the qs and if present load the select lists and load screen
if(current_page.indexOf("?") != -1){
var query_vals = {}; 
$.each(document.location.search.substr(1).split('&'),function(c,q){ var i = q.split('='); query_vals[i[0].toString()] = i[1].toString(); });
if (query_vals['course_id'] > 0 ){
	//a course has been selected look up all the data and populate the form.
	var course_id = query_vals['course_id'];
	//set course id on the form
	if (course_id > 0){
	$('#course_id').val(course_id);
	}
    $.ajax({
		type: "POST",
		url: CEAjax.ajaxurl,
		dataType: 'json',
		data:{'action':'ce_course_unit_options', 'course_id': course_id},
		success: function(response) {
		 $('#post_test_id').append(response['options']);
         $('#upload_image').val(response['course_logo_path']);
         $('#course_start_page_id').val(response['course_start_page_path']);
         $('#course_type').val(response['course_type']);
		 $('#post_test_id').val(response['post_test_id']);
         $('#course_intro_page_path').val(response['course_intro_page_path']);
         if (response['enrollment_key']!= null){
		  $('#enrollment_key').append("Your key is " + response['enrollment_key']);
		  $('#enrollment_y').attr('checked', true);
		 }
		 $('#coach_list').val(response['coach_emails']);
		 $('#start_date').val(response['start_date']);
		 $('#upload_study_guide').val(response['study_guide_path']);
		 $('#max_enrolled').val(response['max_enrolled']);
		 $('#wid').val(response['wid']);
		 $('#entry_id').val(response['entry_id']);
		 if (response['course_logo_path'] != null){
		 $('#current_logo').html("<img src='"+response['course_logo_path']+"' style='max-width: 130px;'>" );
		 }
		},
		error: function(xhr, status, error) {
        // handle error
		 alert('An error occurred...' + error);
        }
     });
  }
}//end if we are checking the qs 
//process the form
$('#btnSaveExtras').click(function(e) {
e.preventDefault();
var data = $("#course_extras_form").serialize();
if ($('#enrollment_y').is(':checked')){
data=data+"&needkey=1"; 
}
data=data+"&action=ce_save_update_extras";
//set up the data as json data
query_to_json = function(data) {
  var j, q;
  q = data.replace(/\?/, "").split("&");
  j = {};
  $.each(q, function(i, arr) {
    arr = arr.split('=');
    return j[arr[0]] = arr[1];
  });
  return j;
}
var jsonData = JSON.stringify(query_to_json(data));
console.log(data);
     $.ajax({
		type: "POST",
		url: CEAjax.ajaxurl,
		dataType: 'json',
		data: data,
		success: function(response) {
		console.log(response);
		$('#message_area').html("<p class='message'>The course was updated.</p>" );
		if (response['course_logo_path'] != null){
		 $('#current_logo').html("<img src='"+ response['course_logo_path']+"' style='max-width: 130px;'>" );
		}
		 if (response['enrollment_key']!= null){
		  $('#enrollment_key').append("Your key is " + response['enrollment_key']);
		  $('#enrollment_y').attr('checked', true);
		 }
        },
		error: function(xhr, status, error) {
        // handle error
		 alert('An error occurred...' + error);
        }
     });
});

//handle the study guide and logo
   $('#upload_studyguide_button').click(function(e) {
	   var custom_uploader;
      e.preventDefault();
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
       //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose File',
            button: {
                text: 'Choose File'
            },
            multiple: false
        });
    //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            console.log(custom_uploader.state().get('selection').toJSON());
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#upload_study_guide').val(attachment.url);
        });

        //Open the uploader dialog
        custom_uploader.open();

    });

//handle the upload logo button   
   $('#upload_image_button').click(function(e) {
	   var custom_uploader;
       e.preventDefault();
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Logo',
            button: {
                text: 'Choose Logo'
            },
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            console.log(custom_uploader.state().get('selection').toJSON());
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#upload_image').val(attachment.url);
        });

        //Open the uploader dialog
        custom_uploader.open();

    });
//make the searchbox use autocomplete for users
$( "#user-search-input" ).autocomplete({
      minLength: 2,
	  source: function( request, response ) {
        $.getJSON(CEAjax.ajaxurl, request, function( data, status, xhr ) {
         response( data );
	    });
	  },
	  select: function( event, ui ) {
	  //set the value of the search input 
	  var currentSelections = $("#coach_list").val();
	     $("#user-search-input").text(ui.item.value);
		 $("#coach_list").val(currentSelections + ui.item.value+',');	
         $("#coach_list").css("background-color", "#f1e9a9");
		 $("#user-search-input").val('');
         return false;		 
      },	  
});

});