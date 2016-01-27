<?php
/**
 * The settings for the users Service
 * @author chekov
 * @package users
 * @license GPL V2
 */
$settings = array(
	"facet-list" => array("list" => "View user lists", "view" => "View user pages", "inspect" => "Inspect user activity", "admin" => "Administer Users", "profile" => "Manage profile"),
	"service-title" => "User Management",
	"service-description" => "Manage users' profiles and permissions",
	"add_user_status" => "accept",
	"default_profile" => array(),
	"messages" => array(
		"update_details_intro" => "Edit the user's details in the form, then hit the update button to save the changes.", 
		"view_details_intro" => "", 
		"profile_intro" => "Edit your details in the form below, then hit the update button to save the changes.", 
		"history_intro" => "A record of the user's past activity on the site.",
		"invite_intro" => "Inviting users will generate an email which will allow the users to join this collection directly with the specified role.",
		"password_intro" => "Update the password in the form below.",
		"system_add" => "Enter the user's email address, choose a role and a password and hit the button to add them to the system",
		"collection_add" => "Enter the user's email address, choose a password and hit the button to add them to the system",
		"invite_email" => "Hi there!\n\nWe are using the Dacura platform to curate our datasets. You have been invited to join the team.",			
	),
	"forms" => array(
		"csu" => array("email", "password"),
		"ccu" => array("email", "password", "role"),
		"icu" => array("emails", "role", "message"),
		"uxp" => array("password", "confirmpassword"),
		"uxu" => array("id", "email", "name", "status"),
		"upu" => array("id", "email", "name")	
	),
	"tables" => array(
			"users" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50,
					"aoColumns" => array(null, null, null, null, null, null, array("bSortable" => false)))),
			"susers" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50,
					"aoColumns" => array(array("bVisible" => false), null, null, null, array("bVisible" => false), null, array("bVisible" => false)))),
			"cusers" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50,
					"aoColumns" => array(null, null, null, null, null, array("bVisible" => false), array("bSortable" => false)))),
			"scusers" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50,
					"aoColumns" => array(array("bVisible" => false), null, null, null, array("bVisible" => false), array("bVisible" => false), array("bVisible" => false)))),
			"roles"	=> array("datatable_options" => array("searching" => false, "info" => true, "jQueryUI" => true,
					"aoColumns" => array(array("bVisible" => false), null, array("bSortable" => false), array("bSortable" => false)))),
			"history" => array("datatable_options" => array("searching" => false, "info" => true, "jQueryUI" =>  true, "order"=> array(1, "desc"),
					"aoColumns" => array(array("bVisible" => false), null, null, null, array("bVisible" => false), array("iDataSort" => 3), array("bVisible" => false), array("iDataSort" => 5), array("bVisible" => false), array("iDataSort" => 7), array("bVisible" => false)))),
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
	),
	"config_form_fields" => array(
			"form_fields" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
			"forms" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
			"tables" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
			"collection_paths_to_create" => array("type" => "complex", "label" => "Paths to create for new collections"),
			"messages" => array("type" => "section", "label" => "User messages"),
			"update_details_intro" => array("type" => "text", "label" => "Update Details", "help" => "Welcome Message on update details page"),
			"view_details_intro" => array("type" => "text", "label" => "View Details", "help" => "Welcome Message on view details page"),
			"profile_intro" => array("type" => "text", "label" => "View Profile", "help" => "Welcome Message on view profile page"),
			"history_intro" => array("type" => "text", "label" => "View History", "help" => "Welcome Message on view user history page"),
			"invite_intro" => array("type" => "text", "label" => "Invitations", "help" => "Welcome Message on invite users page"),
			"password_intro" => array("type" => "text", "label" => "Password", "help" => "Welcome Message on set password page"),
			"default_profile" => array("type" => "complex", "label" => "Default profile", "help" => "JSON object that will be given to new users by default"),
			"system_add" => array("label" => "Add system user", "help" => "Text that appears on system level page when users are added.", "type" => "text"),
			"collection_add" => array("label" => "Add collection user", "help" => "Text that appears on collection pages when users are added.", "type" => "text"),
			"invite_email" => array("label" => "Invitation email", "help" => "Email text that will be sent to users who are invited to join the collection.", "type" => "text"),
			"add_user_status" => array("label" => "New user status", "help" => "Default status of new users added with the add function", "type" => "status"),
				
	),
		
);
