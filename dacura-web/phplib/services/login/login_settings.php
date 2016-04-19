<?php
/** Settings for Login Service
 * @package login
 * @author chekov
 * @license GPL V2
 */
$settings = array(
	'register_email_subject' => "Dacura Registration",
	'lost_email_subject' => "Dacura Password Reset",
	'reset_instruction' => "Enter a new password in the boxes below. Make sure that the passwords match",
	'set_instruction' => "In order to accept your invitation, you must set up a password for logging into the Dacura System. Please choose a password in the form below",
	"facet-list" => array("login" => "Login to the system"),
	"config_form_fields" => array(
		"facets" => array("hidden" => true),
		"register_email_subject" => array("label" => "Email registration subject", "type" => "text", "help" => "The text that will be positioned in the subject line of emails in response to new account registrations"),
		"lost_email_subject" => array("label" => "Lost password subject", "type" => "text", "help" => "The text that will be placed in the subject line of emails in response to lost password requests"),
		"reset_instruction" => array("label" => "Reset page message", "type" => "text", "input_type" => "textarea", "help" => "The text that appears on the top of pages where users reset their password"),
		"set_instruction" => array("label" => "Set password message", "type" => "text", "input_type" => "textarea", "help" => "The text that appears on the top of pages where users must set a password"),
	)	
);