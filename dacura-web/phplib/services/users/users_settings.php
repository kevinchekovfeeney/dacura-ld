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
		"profile_password_intro" => "Update your password in the form below.",
		"system_add" => "Enter the user's email address, choose a role and a password and hit the button to add them to the system",
		"collection_add" => "Enter the user's email address, choose a password and hit the button to add them to the system",
		"invite_email" => "Hi there!\n\nWe are using the Dacura platform to curate our datasets. You have been invited to join the team.",
		"password_rule" => "Passwords must be at least six characters long", 				
	),
	"csu" => array(
		"email" =>	array("label" => "Email Address", "type" => "email", "help" =>"The email address is the primary identifier of the user"),
		"password" => array("label" => "Password", "type" => "password", "help" => "The user's initial password (they will be able to change this later).")
	),
	"ccu" => array(
		"email" =>	array("label" => "Email Address", "type" => "email", "help" =>"The email address is the primary identifier of the user"),
		"password" => array("label" => "Password", "type" => "password", "help" => "The user's initial password (they will be able to change this later)."),
		"role" => array("label" => "Role", "type" => "choice", "help" => "You must give users a role in the collection to allow them to join it."),
	),			
	"icu" => array(
		"emails" => array("label" => "Email Addresses", "input_type" => "textarea", "help" =>"Type the email addresses of the people that you wish to invite to join the curation effort for your collection. Please separate addresses with white space"),	
		"role" => array("label" => "Role", "type" => "choice", "help" => "You must give users a role in the collection to allow them to join it."),
		"message" => array("label" => "Message", "input_type" => "textarea", "help" => "An introductory message that will be sent to the users"),
	),
	"uxp" => array(
		"password" => array("label" => "Password", "type" => "password", "help" => "Enter the user's new password."),
		"confirmpassword" => array("label" => "Confirm Password", "type" => "password", "help" => "Confirm the new password - it must match!"),
	),
	"upp" => array(
		"password" => array("label" => "Password", "type" => "password", "help" => "Enter your new password."),
		"confirmpassword" => array("label" => "Confirm Password", "type" => "password", "help" => "Confirm your new password - it must match!"),
	),
	"uxu" => array(
		"id" => array("label" => "ID", "disabled" => true, "length" => "tiny", "help" => "The internal, numeric, identifier of the user within Dacura"),
		"email" =>	array("label" => "Email Address", "type" => "email", "help" =>"The email address is the primary identifier of the user"),
		"name" => array("label" => "Name", "help" => "The user's handle: a short string that identifies the user on the site"),
		"status" =>	array("label" => "Status", "help" => "The user's current status", "type" => "status"),
	),
	"upu" => array(
		"id" => array("label" => "ID", "disabled" => true, "length" => "tiny", "help" => "Your internal identifier within Dacura"),
		"email" =>	array("label" => "Email Address", "type" => "email", "help" =>"Your registered email address - it is the primary way that you are identified by Dacura"),
		"name" => array("label" => "Name", "help" => "A short string that identifies you on the site"),
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
		"system_history" => array("datatable_options" => array("searching" => false, "info" => true, "jQueryUI" =>  true, "order"=> array(5, "desc"),
				"aoColumns" => array(array("bVisible" => false), null, null, null, array("bVisible" => false), array("iDataSort" => 5), array("bVisible" => false), array("iDataSort" => 7), array("bVisible" => false), array("iDataSort" => 9), array("bVisible" => false)))),
		"collection_history" => array("datatable_options" => array("searching" => false, "info" => true, "jQueryUI" =>  true, "order"=> array(5, "desc"),
				"aoColumns" => array(array("bVisible" => false), null, array("bVisible" => false), null, array("bVisible" => false), array("iDataSort" => 5), array("bVisible" => false), array("iDataSort" => 7),  array("bVisible" => false), array("iDataSort" => 9), array("bVisible" => false)))),
		),
	"config_form_fields" => array(
		"uxu" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"uxp" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"csu" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"ccu" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"icu" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"upu" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
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
		"invite_email" => array("label" => "Invitation email", "help" => "Email text that will be sent to users who are invited to join the collection.", "type" => "text", "input_type" => "textarea"),
		"add_user_status" => array("label" => "New user status", "help" => "Default status of new users added with the add function", "type" => "status"),
	),
);
