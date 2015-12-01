<?php
/*
 * Users Service
 */
$settings = array(
	"add_user_status" => "accept",
	"show_deleted_users" => false,
	"default_profile" => array(),
	"messages" => array(
		"details_intro" => "Edit the user's details in the form, then hit the update button to save the changes.", 
		"profile_intro" => "Edit your details in the form below, then hit the update button to save the changes.", 
		"history_intro" => "A record of the user's past activity on the site.",
		"invite_intro" => "Inviting users will generate an email which will allow the users to join this collection directly with the specified role.",
		"password_intro" => "Update the password in the form below.",
		"system_add" => "Enter the user's email address, choose a role and a password and hit the button to add them to the system",
		"collection_add" => "Enter the user's email address, choose a password and hit the button to add them to the system",
		"invite_email" => "Hi there\n\n. We are using the Dacura platform to curate our datasets.  You have been invited to join the team. ?",			
	),
	"tables" => array(
		"users" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50)),
		"roles"	=> array("datatable_options" => array("searching" => false, "info" => true, "jQueryUI" => true, 
			"aoColumns" => array(array("bVisible" => false), null, array("bVisible" => false), null, array("bSortable" => false)))),
		"history" => array("datatable_options" => array("searching" => false, "info" => true, "jQueryUI" =>  true, "order"=> array(1, "desc"),
			"aoColumns" => array(array("bVisible" => false), array("iDataSort" => 1), array("bVisible" => false), array("iDataSort" => 3), array("bVisible" => false), array("iDataSort" => 5, "bVisible" => false), null))),
	),
	"forms" => array(
		"csu" => array("email", "password"),
		"ccu" => array("email", "password", "role"),
		"icu" => array("emails", "role", "message"),
		"uxp" => array("password", "confirmpassword"),
		"uxu" => array("id", "email", "name", "status"),
		"upu" => array("id", "email", "name")	
	),
	"form_fields" => array(
		"email" =>	array("label" => "Email Address", "type" => "email", "help" =>"The email address is the primary identifier of the user"),
		"password" => array("label" => "Password", "type" => "password", "help" => "The user's initial password (they can change this later)."),
		"confirmpassword" => array("label" => "Confirm Password", "type" => "password", "help" => "Confirm the new password."),
		"id" => array("label" => "ID", "disabled" => true, "length" => "tiny"),
		"role" => array("label" => "Role", "type" => "choice", "help" => "You must give users a role in the collection to allow them to join it."),
		"status" =>	array("label" => "Status", "help" => "The current status of the user", "type" => "status"),
		"name" => array("label" => "Name", "help" => "The user's handle: a short string that will identify the user on the site"),
		"message" => array("label" => "Message", "input_type" => "textarea", "help" => "An introductory message that will be sent to the users"),			
		"emails" => array("label" => "Email Addresses", "input_type" => "textarea", "help" =>"Type the email addresses of the people that you wish to invite to join the curation effort for your collection. Please separate addresses with white space"),			
	)
);
