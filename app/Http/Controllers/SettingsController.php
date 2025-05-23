<?php
namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Admin;
use App\Models\Setting;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class SettingsController extends Controller
{
    public function index()
    {
        $timezones = get_timezone_array();
        return view('settings.general_settings', compact('timezones'));
    }
    public function security()
    {
        return view('settings.security_settings');
    }
    public function pusher()
    {
        return view('settings.pusher_settings');
    }
    public function email()
    {
        return view('settings.email_settings');
    }
    public function media_storage()
    {
        return view('settings.media_storage_settings');
    }
    public function templates()
    {
        return view('settings.template_settings');
    }
    public function store_general_settings(Request $request)
    {
        $request->validate([
            'company_title' => ['required'],
            'timezone' => ['required'],
            'currency_full_form' => ['required'],
            'currency_symbol' => ['required'],
            'currency_code' => ['required'],
            'date_format' => ['required']
        ]);
        $settings = [];
        $fetched_data = Setting::where('variable', 'general_settings')->first();
        if ($fetched_data != null) {
            $settings = json_decode($fetched_data->value, true);
        }
        $form_val = $request->except('_token', '_method', 'redirect_url');
        $old_logo = isset($settings['full_logo']) && !empty($settings['full_logo']) ? $settings['full_logo'] : '';
        if ($request->hasFile('full_logo')) {
            Storage::disk('public')->delete($old_logo);
            $form_val['full_logo'] = $request->file('full_logo')->store('logos', 'public');
        } else {
            $form_val['full_logo'] = $old_logo;
        }
        $old_half_logo = isset($settings['half_logo']) && !empty($settings['half_logo']) ? $settings['half_logo'] : '';
        if ($request->hasFile('half_logo')) {
            Storage::disk('public')->delete($old_half_logo);
            $form_val['half_logo'] = $request->file('half_logo')->store('logos', 'public');
        } else {
            $form_val['half_logo'] = $old_half_logo;
        }
        $old_favicon = isset($settings['favicon']) && !empty($settings['favicon']) ? $settings['favicon'] : '';
        if ($request->hasFile('favicon')) {
            Storage::disk('public')->delete($old_favicon);
            $form_val['favicon'] = $request->file('favicon')->store('logos', 'public');
        } else {
            $form_val['favicon'] = $old_favicon;
        }
        $old_footer_logo = isset($settings['footer_logo']) && !empty($settings['footer_logo']) ? $settings['footer_logo'] : '';
        if ($request->hasFile('footer_logo')) {
            Storage::disk('public')->delete($old_favicon);
            $form_val['footer_logo'] = $request->file('footer_logo')->store('logos', 'public');
        } else {
            $form_val['footer_logo'] = $old_footer_logo;
        }
        $data = [
            'variable' => 'general_settings',
            'value' => json_encode($form_val),
        ];
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            Setting::where('variable', 'general_settings')->update($data);
        }
        session()->put('date_format', $request->input('date_format'));
        Session::flash('message', 'Settings saved successfully.');
        return response()->json(['error' => false]);
    }
    public function store_security_settings(Request $request)
    {
        $request->validate(['max_login_attempts' => 'nullable|integer',
            'time_decay' => 'nullable|integer',
        ]);
        $fetched_data = Setting::where('variable', 'security_settings')->first();
        $form_val = $request->except('_token', '_method', 'redirect_url');
        $data = [
            'variable' => 'security_settings',
            'value' => json_encode($form_val),
        ];
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            Setting::where('variable', 'security_settings')->update($data);
        }
        return response()->json(['error' => false, 'message' => 'Settings saved successfully.']);
    }
    public function store_pusher_settings(Request $request)
    {
        $request->validate([
            'pusher_app_id' => ['required'],
            'pusher_app_key' => ['required'],
            'pusher_app_secret' => ['required'],
            'pusher_app_cluster' => ['required']
        ]);
        $fetched_data = Setting::where('variable', 'pusher_settings')->first();
        $form_val = $request->except('_token', '_method', 'redirect_url');
        $data = [
            'variable' => 'pusher_settings',
            'value' => json_encode($form_val),
        ];
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            Setting::where('variable', 'pusher_settings')->update($data);
        }
        Session::flash('message', 'Settings saved successfully.');
        return response()->json(['error' => false]);
    }
    public function store_email_settings(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'smtp_host' => ['required'],
            'smtp_port' => ['required'],
            'email_content_type' => ['required'],
            'smtp_encryption' => ['required']
        ]);
        $fetched_data = Setting::where('variable', 'email_settings')->first();
        $form_val = $request->except('_token', '_method', 'redirect_url');
        $data = [
            'variable' => 'email_settings',
            'value' => json_encode($form_val),
        ];
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            Setting::where('variable', 'email_settings')->update($data);
        }
        Session::flash('message', 'Settings saved successfully.');
        return response()->json(['error' => false]);
    }
    public function store_media_storage_settings(Request $request)
    {
        $request->validate([
            'media_storage_type' => 'required|in:local,s3',
            's3_key' => $request->input('media_storage_type') === 's3' ? 'required' : 'nullable',
            's3_secret' => $request->input('media_storage_type') === 's3' ? 'required' : 'nullable',
            's3_region' => $request->input('media_storage_type') === 's3' ? 'required' : 'nullable',
            's3_bucket' => $request->input('media_storage_type') === 's3' ? 'required' : 'nullable',
        ]);
        $fetched_data = Setting::where('variable', 'media_storage_settings')->first();
        $form_val = $request->except('_token', '_method', 'redirect_url');
        $data = [
            'variable' => 'media_storage_settings',
            'value' => json_encode($form_val),
        ];
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            Setting::where('variable', 'media_storage_settings')->update($data);
        }
        Session::flash('message', 'Settings saved successfully.');
        return response()->json(['error' => false]);
    }
    public function privacy_policy()
    {
        $privacy_policy = get_settings('privacy_policy');
        return view('settings.privacy_policy', ['privacy_policy' => $privacy_policy]);
    }
    public function terms_and_conditions()
    {
        $terms_and_conditions = get_settings('terms_and_conditions');
        return view('settings.terms_and_conditions', ['terms_and_conditions' => $terms_and_conditions]);
    }
    public function refund_policy()
    {
        $refund_policy = get_settings('refund_policy');
        return view('settings.refund_policy', ['refund_policy' => $refund_policy]);
    }
    public function store_privacy_policy(Request $request)
    {
        $request->validate([
            'privacy_policy' => ['required'],
        ]);
        // Fetch existing PayPal settings if they exist
        $fetched_data = Setting::where('variable', 'privacy_policy')->first();
        // Extract form values except for certain fields
        $form_val = $request->except('_token', '_method', 'redirect_url');
        // Prepare data to be stored in the database
        $data = [
            'variable' => 'privacy_policy',
            'value' => json_encode($form_val),
        ];
        // If no existing PayPal settings found, create new; otherwise, update existing
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            $fetched_data->update($data);
        }
        // return redirect()->back()->with('success', 'PayPal Settings Updated Successfully');
        // Flash success message and return JSON response
        // Session::flash('message', 'Privacy Policy saved successfully.');
        //
        return response()->json([
            'success' => true,
            'message' => 'Privacy Policy Saved successfully!',
            'redirect_url' => $request->redirect_url
        ]);
    }
    public function store_terms_and_conditions(Request $request)
    {
        $request->validate([
            'terms_and_conditions' => ['required'],
        ]);
        // Fetch existing PayPal settings if they exist
        $fetched_data = Setting::where('variable', 'terms_and_conditions')->first();
        // Extract form values except for certain fields
        $form_val = $request->except('_token', '_method', 'redirect_url');
        // Prepare data to be stored in the database
        $data = [
            'variable' => 'terms_and_conditions',
            'value' => json_encode($form_val),
        ];
        // If no existing PayPal settings found, create new; otherwise, update existing
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            $fetched_data->update($data);
        }
        // return redirect()->back()->with('success', 'PayPal Settings Updated Successfully');
        // Flash success message and return JSON response
        // Session::flash('message', 'Privacy Policy saved successfully.');
        //
        return response()->json([
            'success' => true,
            'message' => 'Terms And Conditions Saved successfully!',
            'redirect_url' => $request->redirect_url,
        ]);
    }
    public function store_refund_policy(Request $request)
    {
        $request->validate([
            'refund_policy' => ['required'],
        ]);
        // Fetch existing PayPal settings if they exist
        $fetched_data = Setting::where('variable', 'refund_policy')->first();
        // Extract form values except for certain fields
        $form_val = $request->except('_token', '_method', 'redirect_url');
        // Prepare data to be stored in the database
        $data = [
            'variable' => 'refund_policy',
            'value' => json_encode($form_val),
        ];
        // If no existing PayPal settings found, create new; otherwise, update existing
        if ($fetched_data == null) {
            Setting::create($data);
        } else {
            $fetched_data->update($data);
        }
        // return redirect()->back()->with('success', 'PayPal Settings Updated Successfully');
        // Flash success message and return JSON response
        // Session::flash('message', 'Privacy Policy saved successfully.');
        //
        return response()->json([
            'success' => true,
            'message' => 'Refund Policy Saved successfully!',
            'redirect_url' => $request->redirect_url
        ]);
    }
    public function store_template(Request $request)
    {
        $formFields = $request->validate([
            'type' => 'required',
            'name' => 'required',
            'subject' => [
                'nullable',
                'required_if:type,email',
            ],
            'content' => 'required',
            'status' => 'nullable'
        ], [
            'content.required' => 'The message field is required.'
        ]);
        $type = $request->input('type');
        $name = $request->input('name');
        $fetched_data = Template::where('type', $type)
            ->where('name', $name)
            ->first();
        if ($fetched_data == null) {
            Template::create($formFields);
        } else {
            // Use an array of conditions for the update query
            Template::where([
                ['type', '=', $type],
                ['name', '=', $name]
            ])->update($formFields);
        }
        // Session::flash('message', 'Template saved successfully.');
        return response()->json(['error' => false, 'message' => 'Saved successfully.']);
    }
    public function get_default_template(Request $request)
    {
        // Get the type and name from the request
        $type = $request->input('type');
        $name = $request->input('name');
        // Define the directory structure based on type and name
        switch ($type) {
            case 'email':
                $directory = 'views/mail/default_templates/';
                switch ($name) {
                    case 'account_creation':
                        $directory .= 'account_creation.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'verify_email':
                        $directory .= 'verify_email.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'forgot_password':
                        $directory .= 'forgot_password.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'project_assignment':
                        $directory .= 'project_assignment.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'task_assignment':
                        $directory .= 'task_assignment.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'workspace_assignment':
                        $directory .= 'workspace_assignment.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'meeting_assignment':
                        $directory .= 'meeting_assignment.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'leave_request_creation':
                        $directory .= 'leave_request_creation.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'leave_request_status_updation':
                        $directory .= 'leave_request_status_updation.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'team_member_on_leave_alert':
                        $directory .= 'team_member_on_leave_alert.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'project_status_updation':
                        $directory .= 'project_status_updation.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'project_issue_assignment':
                        $directory .= 'project_issue_assignment.blade.php';
                        break;
                    case 'task_status_updation':
                        $directory .= 'task_status_updation.blade.php';
                        // Include or return the file content based on $directory
                        break;
                    case 'announcement':
                        $directory .= 'announcement.blade.php';
                        break;
                    case 'task_reminder':
                        $directory .= 'task_reminder.blade.php';
                        break;
                    case 'recurring_task':
                        $directory .= 'recurring_task.blade.php';
                        break;
                    default:
                        return response()->json(['error' => true, 'message' => 'Unknown email template name.']);
                        break;
                }
                // Return or include the file based on the constructed $directory
                break;
            case 'sms':
            case 'whatsapp':
                switch ($name) {
                    case 'project_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'Hello, {FIRST_NAME} {LAST_NAME} You have been assigned a new project {PROJECT_TITLE}, ID:#{PROJECT_ID}.']);
                        break;
                    case 'project_status_updation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of project {PROJECT_TITLE}, ID:#{PROJECT_ID}, from {OLD_STATUS} to {NEW_STATUS}.']);
                        break;
                    case 'project_issue_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has Assigned you new issue: {ISSUE_TITLE}, ID:#{ISSUE_ID} ,Status : {STATUS}']);
                        break;
                    case 'task_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'Hello, {FIRST_NAME} {LAST_NAME} You have been assigned a new task {TASK_TITLE}, ID:#{TASK_ID}.']);
                        break;
                    case 'task_status_updation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of task {TASK_TITLE}, ID:#{TASK_ID}, from {OLD_STATUS} to {NEW_STATUS}.']);
                        break;
                    case 'workspace_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'Hello, {FIRST_NAME} {LAST_NAME} You have been added in a new workspace {WORKSPACE_TITLE}, ID:#{WORKSPACE_ID}.']);
                        break;
                    case 'meeting_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'Hello, {FIRST_NAME} {LAST_NAME} You have been added in a new meeting {MEETING_TITLE}, ID:#{MEETING_ID}.']);
                        break;
                    case 'leave_request_creation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'New Leave Request ID:#{ID} Has Been Created By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME}.']);
                        break;
                    case 'leave_request_status_updation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'Leave Request ID:#{ID} Status Updated From {OLD_STATUS} To {NEW_STATUS}.']);
                        break;
                    case 'team_member_on_leave_alert':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.']);
                        break;
                    case 'announcement':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has made a new announcement titled "{ANNOUNCEMENT_TITLE}". Shared by {COMPANY_TITLE} ({CURRENT_YEAR}).']);
                        break;
                    case 'task_reminder':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'You have a task reminder for Task #{TASK_ID} - "{TASK_TITLE}". You can view the task here: {TASK_URL}']);
                    case 'recurring_task':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed. You can view the new instance here: {TASK_URL}']);
                    default:
                        return response()->json(['error' => true, 'message' => 'Unknown SMS template name.']);
                        break;
                }
                break;
            case 'system':
                switch ($name) {
                    case 'project_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} assigned you new project: {PROJECT_TITLE}, ID:#{PROJECT_ID}.']);
                        break;
                    case 'project_status_updation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of project {PROJECT_TITLE}, ID:#{PROJECT_ID}, from {OLD_STATUS} to {NEW_STATUS}.']);
                        break;
                    case 'project_issue_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has Assigned you new issue: {ISSUE_TITLE}, ID:#{ISSUE_ID} ,Status : {STATUS}']);
                        break;
                    case 'task_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} assigned you new task: {TASK_TITLE}, ID:#{TASK_ID}.']);
                        break;
                    case 'task_status_updation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of task {TASK_TITLE}, ID:#{TASK_ID}, from {OLD_STATUS} to {NEW_STATUS}.']);
                        break;
                    case 'workspace_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} added you in a new workspace {WORKSPACE_TITLE}, ID:#{WORKSPACE_ID}.']);
                        break;
                    case 'meeting_assignment':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} added you in a new meeting {MEETING_TITLE}, ID:#{MEETING_ID}.']);
                        break;
                    case 'leave_request_creation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'New Leave Request ID:#{ID} Has Been Created By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME}.']);
                        break;
                    case 'leave_request_status_updation':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'Leave Request ID:#{ID} Status Updated From {OLD_STATUS} To {NEW_STATUS}.']);
                        break;
                    case 'team_member_on_leave_alert':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.']);
                        break;
                    case 'announcement':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has made a new announcement titled "{ANNOUNCEMENT_TITLE}". Shared by {COMPANY_TITLE} ({CURRENT_YEAR}).']);
                        break;
                    case 'task_reminder':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'You have a task reminder for Task #{TASK_ID} - "{TASK_TITLE}".']);
                    case 'recurring_task':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed.']);
                    default:
                        return response()->json(['error' => true, 'message' => 'Unknown SMS template name.']);
                        break;
                }
                break;
            case 'slack':
                switch ($name) {
                    case 'project_assignment':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*New Project Assigned:* {PROJECT_TITLE}, ID: #{PROJECT_ID}. By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME}
You can find the project here :{PROJECT_URL}'
                        ]);
                        break;
                    case 'project_status_updation':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*Project Status Updated:* By {UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} , {PROJECT_TITLE}, ID: #{PROJECT_ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`.
You can find the project here :{PROJECT_URL}'
                        ]);
                        break;
                    case 'project_issue_assignment':
                        return response()->json(['error' => false, 'message' => 'Slack template set successfully.', 'content' => '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has Assigned you new issue: {ISSUE_TITLE}, ID:#{ISSUE_ID} ,Status : {STATUS} ']);
                        break;
                    case 'task_assignment':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*New Task Assigned:* {TASK_TITLE}, ID: #{TASK_ID}. By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME}
You can find the task here : {TASK_URL}'
                        ]);
                        break;
                    case 'task_status_updation':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*Task Status Updated:* By {UPDATER_FIRST_NAME} {UPDATER_LAST_NAME},  {TASK_TITLE}, ID: #{TASK_ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`.
You can find the Task here : {TASK_URL}'
                        ]);
                        break;
                    case 'workspace_assignment':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*New Workspace Added:* By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME},   {WORKSPACE_TITLE}, ID: #{WORKSPACE_ID}.
You can find the Workspace here : {WORKSPACE_URL}'
                        ]);
                        break;
                    case 'meeting_assignment':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*New Meeting Scheduled:* By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME},  {MEETING_TITLE}, ID: #{MEETING_ID}.
You can find the Meeting here : {MEETING_URL}'
                        ]);
                        break;
                    case 'leave_request_creation':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*New {TYPE} Leave Request Created:* ID: #{ID} By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} for {REASON}.  From ( {FROM} ) -  To ( {TO} ).'
                        ]);
                        break;
                    case 'leave_request_status_updation':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*Leave Request Status Updated:* For {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME},  ID: #{ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`.'
                        ]);
                        break;
                    case 'team_member_on_leave_alert':
                        return response()->json([
                            'error' => false,
                            'message' => 'Slack template set successfully.',
                            'content' => '*Team Member Leave Alert:* {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.'
                        ]);
                        break;
                    case 'announcement':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has made a new announcement titled "{ANNOUNCEMENT_TITLE}". Shared by {COMPANY_TITLE} ({CURRENT_YEAR}).']);
                        break;
                    case 'task_reminder':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'You have a task reminder for Task #{TASK_ID} - "{TASK_TITLE}". You can view the task here: {TASK_URL}.']);
                        break;
                    case 'recurring_task':
                        return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed. You can view the new instance here: {TASK_URL}.']);
                        break;
                }
            default:
                return response()->json(['error' => true, 'message' => 'Unknown template type.']);
                break;
        }
        // Construct the default template path
        $defaultTemplatePath = resource_path($directory);
        // Check if the default template file exists
        if (File::exists($defaultTemplatePath)) {
            // Read the content of the default template file
            $defaultTemplateContent = File::get($defaultTemplatePath);
            // Return the default template content as a response
            return response()->json(['error' => false, 'message' => 'Reset to default successfully.', 'content' => $defaultTemplateContent]);
        } else {
            // If the default template file does not exist, return an error response
            return response()->json(['error' => true, 'message' => 'Default template not found.']);
        }
    }
    public function sms_gateway()
    {
        return view('settings.sms_gateway_settings');
    }
    public function store_sms_gateway_settings(Request $request)
    {
        $request->validate([
            'base_url' => 'required|string',
            'sms_gateway_method' => 'required|string|in:POST,GET',
            'header_key' => 'nullable|array',
            'header_value' => 'nullable|array',
            'body_key' => 'nullable|array',
            'body_value' => 'nullable|array',
            'params_key' => 'nullable|array',
            'params_value' => 'nullable|array',
            'text_format_data' => 'nullable|string',
        ]);
        // Prepare the data to store
        $data = [
            'base_url' => $request->base_url,
            'sms_gateway_method' => $request->sms_gateway_method,
            'header_data' => $request->header_key && $request->header_value ? array_combine($request->header_key, $request->header_value) : [],
            'body_formdata' => $request->body_key && $request->body_value ? array_combine($request->body_key, $request->body_value) : [],
            'params_data' => $request->params_key && $request->params_value ? array_combine($request->params_key, $request->params_value) : [],
            'text_format_data' => $request->text_format_data,
        ];
        // Convert data to JSON
        $jsonData = json_encode($data);
        // Check if the setting exists
        $existingSetting = Setting::where('variable', 'sms_gateway_settings')->first();
        if ($existingSetting) {
            // Update existing setting
            $existingSetting->update(['value' => $jsonData]);
        } else {
            // Create new setting
            Setting::create([
                'variable' => 'sms_gateway_settings',
                'value' => $jsonData,
            ]);
        }
        return response()->json(['error' => false, 'message' => 'Settings saved successfully.']);
    }
    public function store_whatsapp_settings(Request $request)
    {
        $request->validate([
            'whatsapp_access_token' => 'required|string',
            'whatsapp_phone_number_id' => 'required|string',
        ]);
        // Prepare the data to store
        $data = [
            'whatsapp_access_token' => $request->whatsapp_access_token,
            'whatsapp_phone_number_id' => $request->whatsapp_phone_number_id,
        ];
        // Convert data to JSON
        $jsonData = json_encode($data);
        // Check if the setting exists
        $existingSetting = Setting::where('variable', 'whatsapp_settings')->first();
        if ($existingSetting) {
            // Update existing setting
            $existingSetting->update(['value' => $jsonData]);
        } else {
            // Create new setting
            Setting::create([
                'variable' => 'whatsapp_settings',
                'value' => $jsonData,
            ]);
        }
        return response()->json(['error' => false, 'message' => 'Settings saved successfully.']);
    }
    public function store_slack_settings(Request $request)
    {
        $request->validate([
            'slack_bot_token' => 'required|string',
        ]);
        // Prepare the data to store
        $data = [
            'slack_bot_token' => $request->slack_bot_token,
        ];
        // Convert data to JSON
        $jsonData = json_encode($data);
        // Check if the setting exists
        $existingSetting = Setting::where('variable', 'slack_settings')->first();
        if ($existingSetting) {
            // Update existing setting
            $existingSetting->update(['value' => $jsonData]);
        } else {
            // Create new setting
            Setting::create([
                'variable' => 'slack_settings',
                'value' => $jsonData,
            ]);
        }
        return response()->json(['error' => false, 'message' => 'Settings saved successfully.']);
    }
    public function admin_settings(Request $request)
    {
        return view('settings.admin_settings');
    }
    public function update_admin_settings(Request $request)
    {
        $request->validate([
            'full_logo' => 'nullable',
            'half_logo' => 'nullable',
        ]);
        // Get the current admin
        $admin = Admin::findOrFail(getAdminIdByUserRole()); // Assuming the logged-in user is associated with an admin
        // Initialize settings array
        $settings = [];
        // Check if the admin already has settings
        $fetched_data = $admin->admin_settings; // Assuming 'settings' is a JSON column in the 'admins' table
        if ($fetched_data != null) {
            $settings = json_decode($fetched_data, true);
        }
        $form_val = $request->except('_token', '_method', 'redirect_url');
        // Handle full logo upload and update
        $old_logo = $settings['full_logo'] ?? '';
        if ($request->hasFile('full_logo')) {
            Storage::disk('public')->delete($old_logo);
            $form_val['full_logo'] = $request->file('full_logo')->store('logos', 'public');
        } else {
            $form_val['full_logo'] = $old_logo;
        }
        // Handle half logo upload and update
        $old_half_logo = $settings['half_logo'] ?? '';
        if ($request->hasFile('half_logo')) {
            Storage::disk('public')->delete($old_half_logo);
            $form_val['half_logo'] = $request->file('half_logo')->store('logos', 'public');
        } else {
            $form_val['half_logo'] = $old_half_logo;
        }
        // Convert settings to JSON and store them in the 'admins' table
        $admin->admin_settings = json_encode($form_val);
        $admin->save();
        // Flash message and return response
        Session::flash('message', 'Admin settings updated successfully.');
        return response()->json(['error' => false]);
    }
    public function test_email_settings(Request $request)
    {
        try {
            // Retrieve the email from the SMTP configuration
            $smtpFromEmail = config('mail.from.address');
            if (!$smtpFromEmail) {
                return response()->json(['error' => true, 'message' => 'SMTP email is not configured.'], 200);
            }
            $smtpServer = config('mail.mailers.smtp.host');
            $smtpPort = config('mail.mailers.smtp.port');
            $smtpEncryption = config('mail.mailers.smtp.encryption');
            $smtpUsername = config('mail.mailers.smtp.username');
            $emailContent = "
                <p>We're pleased to inform you that your SMTP configuration has passed our rigorous testing using our SMTP Tester tool. Your email settings are correctly configured and ready for use.</p>
                <p><strong>Here are the specifics of the test:</strong></p>
                <ul>
                    <li><strong>SMTP Server:</strong> {$smtpServer}</li>
                    <li><strong>Port:</strong> {$smtpPort}</li>
                    <li><strong>Encryption:</strong> {$smtpEncryption}</li>
                    <li><strong>Username:</strong> {$smtpUsername}</li>
                    <li><strong>Test Email:</strong> {$smtpFromEmail}</li>
                </ul>
            ";
            Mail::html($emailContent, function ($message) use ($smtpFromEmail) {
                $message->to($smtpFromEmail)
                    ->subject('SMTP Test Email');
            });
            return response()->json(['error' => false, 'message' => "Test email sent successfully to {$smtpFromEmail}. Check your inbox."]);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'SMTP test failed: ' . $e->getMessage()], 500);
        }
    }
    // Admin Whatsapp Notifications Settings
    public function admin_whatsapp_notifications_settings()
    {
        $admin = Admin::find(getAdminIdByUserRole());

        if (!$admin) {
            abort(404, 'Admin not found');
        }

        $admin_settings = json_decode($admin->admin_settings, true) ?? [];
        $admin_whatsapp_settings = $admin_settings['whatsapp_settings'] ?? [];

        return view('settings.admin_whatsapp_settings', [
            'admin_whatsapp_settings' => $admin_whatsapp_settings
        ]);
    }

    public function update_admin_whatsapp_notifications_settings(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|max:255',
            'sender_mobile_number' => 'required|string|regex:/^[0-9]+$/',
        ], [
            'api_key.required' => 'API Key is required.',
            'sender_mobile_number.required' => 'Sender mobile number is required.',
            'sender_mobile_number.regex' => 'Sender number must contain only digits.',
        ]);
        try {
            $admin_id = getAdminIdByUserRole(); // <- helper to get admin_id based on user
            $admin = Admin::find($admin_id);
            if (!$admin) {
                abort(404, 'Admin not found');
            }
            $admin_settings = json_decode($admin->admin_settings, true) ?? [];
            $admin_settings['whatsapp_settings'] = [
                'api_key' => $request->api_key,
                'sender_mobile_number' => $request->sender_mobile_number,
            ];
            $admin->admin_settings = json_encode($admin_settings);
            $admin->save();
            Session::flash('message', 'WhatsApp notification settings updated successfully.');
            return response()->json(['error' => false]);
        } catch (\Exception $e) {
            Log::error("Failed to update WhatsApp settings: " . $e->getMessage());
            Session::flash('error', 'WhatsApp notification settings could not be updated. Please try again later.');
            return response()->json(['error' => true], 500);
        }
    }
}
