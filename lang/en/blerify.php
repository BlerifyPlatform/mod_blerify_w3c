<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Module metadata.
$string['modulename'] = 'Blerify Certificate';
$string['modulenameplural'] = 'Blerify Certificates';
$string['modulename_help'] = 'The Blerify Certificate activity module allows you to issue W3C Verifiable Credentials to students via the Blerify platform.';
$string['pluginadministration'] = 'Blerify Certificate administration';
$string['pluginname'] = 'Blerify Certificate';

// Admin settings.
$string['setting_service_account_json'] = 'Service Account JSON';
$string['setting_service_account_json_desc'] = 'Upload your Blerify service account JSON file (e.g. service-account-SA2-sa-XXXXXXX.json). This file contains all the credentials needed: client_id, organization_id, private_key, token_uri, and iam_audience.';
$string['setting_service_account_upload_help'] = 'Select a .json service account file to import.';
$string['setting_service_account_current'] = 'Current service account (read-only):';
$string['setting_service_account_configured'] = 'Service account imported successfully';
$string['setting_service_account_invalid_json'] = 'The uploaded file does not contain valid JSON.';

// Activity form.
$string['blerifysettings'] = 'Blerify Settings';
$string['projectid'] = 'Project ID';
$string['projectid_help'] = 'UUID of the Blerify project for this certificate';
$string['templateid'] = 'Template ID';
$string['templateid_help'] = 'UUID of the Blerify credential template';
$string['completionissue'] = 'Issue on course completion';
$string['completionissue_help'] = 'Automatically issue a certificate when the student completes the course';

// Validation errors.
$string['error_projectid_required'] = 'Project ID is required';

// View page.
$string['viewheader'] = 'Certificates for: {$a}';
$string['nocertificates'] = 'There are no Blerify certificate activities in this course.';
$string['indexheader'] = 'Blerify Certificates in: {$a}';

// Student view.
$string['certificate_processing'] = 'We are generating your certificate. This page will refresh on its own in a few seconds.';
$string['certificate_error'] = 'There was an error issuing your certificate. Please contact your instructor.';

// Teacher view.
$string['col_student'] = 'Student';
$string['col_email'] = 'Email';
$string['col_status'] = 'Status';
$string['col_credentialid'] = 'Credential ID';
$string['col_date'] = 'Date Issued';
$string['no_credentials_issued'] = 'No credentials have been issued yet.';

// Statuses.
$string['status_pending'] = 'Pending';
$string['status_error'] = 'Error';

// Events.
$string['eventcertificatecreated'] = 'Blerify certificate created';
$string['eventcredentialissuedmanual'] = 'Credential issued manually';

// Privacy.
$string['privacy:metadata:blerify_credentials'] = 'Information about Blerify digital credentials issued to users';
$string['privacy:metadata:blerify_credentials:userid'] = 'The user ID of the credential recipient';
$string['privacy:metadata:blerify_credentials:credentialid'] = 'The external credential identifier in Blerify';
$string['privacy:metadata:blerify_credentials:status'] = 'The current status of the credential';

// Errors.
$string['error_not_configured'] = 'Blerify plugin is not properly configured. Please contact the administrator.';
$string['error_api_call'] = 'Error communicating with Blerify API: {$a}';

// Manual issuance.
$string['issue_credentials'] = 'Enable Credentials';
$string['issue_credentials_header'] = 'Enrolled Participants';
$string['issue_credentials_help'] = 'Select the participants you want to enable a digital credential for so they can claim it. Only participants without an existing credential are shown.';
$string['issue_selected'] = 'Enable for Selected';
$string['no_participants_available'] = 'All enrolled participants already have a credential, or there are no participants enrolled in this course.';
$string['select_all'] = 'Select all';
$string['deselect_all'] = 'Deselect all';
$string['issue_success'] = '{$a} credential(s) enabled. The student can now claim it.';
$string['issue_error_partial'] = '{$a->success} credential(s) enabled. {$a->fail} failed.';
$string['issue_no_selection'] = 'No participants were selected.';
$string['col_participant'] = 'Participant';
$string['col_actions'] = 'Actions';
$string['retry_issue'] = 'Retry';
$string['retry_success'] = 'Credential re-enabled successfully.';
$string['retry_failed'] = 'Retry failed. Check the logs for details.';

// Course completion notification.

// Capabilities.
$string['blerify:addinstance'] = 'Add a new Blerify Certificate activity';
$string['blerify:view'] = 'View Blerify Certificate';
$string['blerify:manage'] = 'Manage Blerify Certificates';

// Security.
$string['usernotenrolled'] = 'The specified user is not enrolled in this course.';

// Certificate configuration in the activity form.
$string['certificatename'] = 'Certificate name';
$string['certificatename_help'] = 'The name learners see for this certificate in the course.';
$string['templatetoissue'] = 'Template to issue';
$string['templatetoissue_help'] = 'The Blerify credential template used to issue this certificate. The list comes from the project configured for this course.';
$string['passgrade'] = 'Minimum grade to issue (%)';
$string['passgrade_help'] = 'The minimum course grade required when Moodle marks the course as completed.';

// Learner view.
$string['certificate_not_yet'] = 'Your certificate is not available yet.';
$string['certificate_threshold'] = 'You need a course grade of at least';
$string['certificate_current_grade'] = 'Your current grade:';
$string['pdf_download'] = 'Download PDF';
$string['pdf_open'] = 'Open the PDF';

// Credential statuses.
$string['status_issuing'] = 'Issuing';
$string['status_issued'] = 'Issued';
$string['status_claimed'] = 'Claimed';
$string['error_detail'] = 'Error details';

$string['error_no_templates'] = 'The project configured for this course has no credential templates available.';
$string['error_templates_unavailable'] = 'The credential templates could not be loaded. Check the Blerify service account configuration.';
$string['error_passgrade_range'] = 'The minimum grade must be between 0 and 100.';
$string['error_credential_not_ready'] = 'This credential is not available yet.';

$string['privacy:metadata:blerify_credentials:code'] = 'The claim code used to build the wallet deeplink';
$string['privacy:metadata:blerify_credentials:timecreated'] = 'The time the credential record was created';
$string['privacy:metadata:blerify_api'] = 'Data sent to the Blerify platform to issue a credential';
$string['privacy:metadata:blerify_api:email'] = 'The email address the credential is issued to';
$string['privacy:metadata:blerify_api:fullname'] = 'The name rendered into the credential';

$string['error_no_project_id'] = 'This activity has no Blerify project selected. Edit its settings and pick one.';

$string['projecttoissue'] = 'Project';
$string['projecttoissue_help'] = 'The Blerify project this certificate is issued under. Only projects the service account can issue credentials for are listed. The templates below belong to the project selected here.';
$string['templatepreview'] = 'Preview';
$string['templatepreview_none'] = 'This template has no preview image in Blerify.';
$string['error_no_projects'] = 'The service account has no project with credential issuing permission. Check the configuration in Blerify.';

$string['error_invalid_project'] = 'The selected project is not available for this service account.';
$string['error_invalid_template'] = 'The selected template does not belong to the selected project.';

$string['privacy:metadata:blerify_credentials:projectid'] = 'The Blerify project the credential was issued under';
$string['privacy:metadata:blerify_credentials:templateid'] = 'The template the credential was issued with';
$string['privacy:metadata:blerify_credentials:remotestatus'] = 'The status last reported by the Blerify API';
$string['privacy:metadata:blerify_credentials:errordetail'] = 'The reason the last issuance attempt failed';
$string['privacy:metadata:blerify_credentials:timeissued'] = 'The time the credential was issued';
$string['privacy:metadata:blerify_credentials:timeclaimed'] = 'The time the credential was claimed into a wallet';
$string['eventotpfailed'] = 'Wallet OTP attempt failed';
$string['eventotplockout'] = 'Wallet OTP lockout';
$string['eventclaimratelimited'] = 'Wallet claim rate limited';
$string['eventclaimsucceeded'] = 'Wallet claim succeeded';
$string['eventdidlinked'] = 'Wallet DID linked';
$string['eventcredentialreissued'] = 'Credential reissued';
$string['eventcredentialissuancefailed'] = 'Credential issuance failed';
$string['completion_notification_subject'] = 'Blerify - Your credential is ready to claim';
$string['completion_notification_body'] = 'Congratulations on completing the course. Your digital credential is ready. Visit the certificate activity in the course to claim it by scanning the QR code with your Blerify Wallet.';
$string['error_no_template_id'] = 'This activity has no Blerify template selected. Edit its settings and pick one.';
