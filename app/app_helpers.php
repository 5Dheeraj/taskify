<?php

use App\Models\Tax;
use App\Models\Task;
use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Status;
use App\Models\Update;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Template;
use App\Models\TeamMember;
use App\Models\LeaveEditor;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Subscription;
use Chatify\ChatifyMessenger;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Models\UserClientPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Request;
use Twilio\Rest\Client as TwilioClient;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use App\Notifications\AssignmentNotification;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

if (!function_exists('get_timezone_array')) {
    // 1.Get Time Zone
    function get_timezone_array()
    {
        $list = DateTimeZone::listAbbreviations();
        $idents = DateTimeZone::listIdentifiers();
        $data = $offset = $added = array();
        foreach ($list as $abbr => $info) {
            foreach ($info as $zone) {
                if (
                    !empty($zone['timezone_id'])
                    and
                    !in_array($zone['timezone_id'], $added)
                    and
                    in_array($zone['timezone_id'], $idents)
                ) {
                    $z = new DateTimeZone($zone['timezone_id']);
                    $c = new DateTime("", $z);
                    $zone['time'] = $c->format('h:i A');
                    $offset[] = $zone['offset'] = $z->getOffset($c);
                    $data[] = $zone;
                    $added[] = $zone['timezone_id'];
                }
            }
        }
        array_multisort($offset, SORT_ASC, $data);
        $i = 0;
        $temp = array();
        foreach ($data as $key => $row) {
            $temp[0] = $row['time'];
            $temp[1] = formatOffset($row['offset']);
            $temp[2] = $row['timezone_id'];
            $options[$i++] = $temp;
        }
        return $options;
    }
}
if (!function_exists('formatOffset')) {
    function formatOffset($offset)
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);
        if ($hour == 0 and $minutes == 0) {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');
    }
}
if (!function_exists('relativeTime')) {
    function relativeTime($time)
    {
        if (!ctype_digit($time))
            $time = strtotime($time);
        $d[0] = array(1, "second");
        $d[1] = array(60, "minute");
        $d[2] = array(3600, "hour");
        $d[3] = array(86400, "day");
        $d[4] = array(604800, "week");
        $d[5] = array(2592000, "month");
        $d[6] = array(31104000, "year");
        $w = array();
        $return = "";
        $now = time();
        $diff = ($now - $time);
        $secondsLeft = $diff;
        for ($i = 6; $i > -1; $i--) {
            $w[$i] = intval($secondsLeft / $d[$i][0]);
            $secondsLeft -= ($w[$i] * $d[$i][0]);
            if ($w[$i] != 0) {
                $return .= abs($w[$i]) . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
            }
        }
        $return .= ($diff > 0) ? "ago" : "left";
        return $return;
    }
}
if (!function_exists('get_settings')) {
    function get_settings($variable)
    {
        $fetched_data = Setting::all()->where('variable', $variable)->values();
        if (isset($fetched_data[0]['value']) && !empty($fetched_data[0]['value'])) {
            if (isJson($fetched_data[0]['value'])) {
                $fetched_data = json_decode($fetched_data[0]['value'], true);
            }
            return $fetched_data;
        }
    }
}
if (!function_exists('isJson')) {
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
if (!function_exists('create_label')) {
    function create_label($variable, $title = '', $locale = '')
    {
        if ($title == '') {
            $title = $variable;
        }
        return "
            <div class='mb-3 col-md-6'>
                        <label class='form-label' for='end_date'>$title</label>
                        <div class='input-group input-group-merge'>
                            <input type='text' name='$variable' class='form-control' value='" . get_label($variable, $title, $locale) . "'>
                        </div>
                    </div>
            ";
    }
}
if (!function_exists('get_label')) {
    function get_label($label, $default, $locale = '')
    {
        if (Lang::has('labels.' . $label, $locale)) {
            return trans('labels.' . $label, [], $locale);
        } else {
            return $default;
        }
    }
}
if (!function_exists('empty_state')) {
    function empty_state($url)
    {
        return "
    <div class='card text-center'>
    <div class='card-body'>
        <div class='misc-wrapper'>
            <h2 class='mb-2 mx-2'>Data Not Found </h2>
            <p class='mb-4 mx-2'>Oops! 😖 Data doesn't exists.</p>
            <a href='/$url' class='btn btn-primary'>Create now</a>
            <div class='mt-3'>
                <img src='../assets/img/illustrations/page-misc-error-light.png' alt='page-misc-error-light' width='500' class='img-fluid' data-app-dark-img='illustrations/page-misc-error-dark.png' data-app-light-img='illustrations/page-misc-error-light.png' />
            </div>
        </div>
    </div>
</div>";
    }
}
if (!function_exists('format_date')) {
    function format_date($date, $time = false, $from_format = null, $to_format = null, $apply_timezone = true)
    {
        if ($date) {
            $from_format = $from_format ?? 'Y-m-d';
            $to_format = $to_format ?? get_php_date_time_format();
            $time_format = get_php_date_time_format(true);
            if ($time) {
                if ($apply_timezone) {
                    if (!$date instanceof \Carbon\Carbon) {
                        $dateObj = \Carbon\Carbon::createFromFormat($from_format . ' H:i:s', $date)
                            ->setTimezone(config('app.timezone'));
                    } else {
                        $dateObj = $date->setTimezone(config('app.timezone'));
                    }
                } else {
                    if (!$date instanceof \Carbon\Carbon) {
                        $dateObj = \Carbon\Carbon::createFromFormat($from_format . ' H:i:s', $date);
                    } else {
                        $dateObj = $date;
                    }
                }
            } else {
                if (!$date instanceof \Carbon\Carbon) {
                    $dateObj = \Carbon\Carbon::createFromFormat($from_format, $date);
                } else {
                    $dateObj = $date;
                }
            }
            $timeFormat = $time ? ' ' . $time_format : '';
            $date = $dateObj->format($to_format . $timeFormat);
            return $date;
        } else {
            return '-';
        }
    }
}
if (!function_exists('getAuthenticatedUser')) {
    function getAuthenticatedUser($idOnly = false, $withPrefix = false)
    {
        $prefix = '';
        // Check the 'web' guard (users)
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $prefix = 'u_';
        }
        // Check the 'clients' guard (clients)
        elseif (Auth::guard('client')->check()) {
            $user = Auth::guard('client')->user();
            $prefix = 'c_';
        }
        // No user is authenticated
        else {
            return null;
        }
        if ($idOnly) {
            if ($withPrefix) {
                return $prefix . $user->id;
            } else {
                return $user->id;
            }
        }
        return $user;
    }
}
if (!function_exists('isUser')) {
    function isUser()
    {
        return Auth::guard('web')->check(); // Assuming 'role' is a field in the user model.
    }
}
if (!function_exists('isClient')) {
    function isClient()
    {
        return Auth::guard('client')->check(); // Assuming 'role' is a field in the user model.
    }
}
if (!function_exists('generateUniqueSlug')) {
    function generateUniqueSlug($title, $model, $id = null)
    {
        $slug = Str::slug($title);
        $count = 2;
        // If an ID is provided, add a where clause to exclude it
        if ($id !== null) {
            while ($model::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = Str::slug($title) . '-' . $count;
                $count++;
            }
        } else {
            while ($model::where('slug', $slug)->exists()) {
                $slug = Str::slug($title) . '-' . $count;
                $count++;
            }
        }
        return $slug;
    }
}
if (!function_exists('duplicateRecord')) {
    function duplicateRecord($model, $id, $relatedTables = [], $title = '')
    {
        // Find the original record with related data
        $originalRecord = $model::with($relatedTables)->find($id);
        if (!$originalRecord) {
            return false; // Record not found
        }
        // Start a new database transaction to ensure data consistency
        DB::beginTransaction();
        try {
            // Duplicate the original record
            $duplicateRecord = $originalRecord->replicate();
            // Set the title if provided
            if (!empty($title)) {
                $duplicateRecord->title = $title;
            }
            $duplicateRecord->save();
            foreach ($relatedTables as $relatedTable) {
                if ($relatedTable === 'tasks') {
                    // Handle 'tasks' relationship separately
                    foreach ($originalRecord->$relatedTable as $task) {
                        // Duplicate the related task
                        $duplicateTask = $task->replicate();
                        $duplicateTask->project_id = $duplicateRecord->id;
                        $duplicateTask->save();
                        foreach ($task->users as $user) {
                            // Attach the duplicated user to the duplicated task
                            $duplicateTask->users()->attach($user->id);
                        }
                    }
                }
            }
            // Handle many-to-many relationships separately
            if (in_array('users', $relatedTables)) {
                $originalRecord->users()->each(function ($user) use ($duplicateRecord) {
                    $duplicateRecord->users()->attach($user->id);
                });
            }
            if (in_array('clients', $relatedTables)) {
                $originalRecord->clients()->each(function ($client) use ($duplicateRecord) {
                    $duplicateRecord->clients()->attach($client->id);
                });
            }
            if (in_array('tags', $relatedTables)) {
                $originalRecord->tags()->each(function ($tag) use ($duplicateRecord) {
                    $duplicateRecord->tags()->attach($tag->id);
                });
            }
            // Commit the transaction
            DB::commit();
            return $duplicateRecord;
        } catch (\Exception $e) {
            // Handle any exceptions and rollback the transaction on failure
            DB::rollback();
            return false;
        }
    }
}
if (!function_exists('is_admin_or_leave_editor')) {
    function is_admin_or_leave_editor($user = null)
    {
        if (!$user) {
            $user = getAuthenticatedUser();
        }
        // Check if the user is an admin or a leave editor based on their presence in the leave_editors table
        if ($user->hasRole('admin') || LeaveEditor::where('user_id', $user->id)->exists()) {
            // dd($user->hasRole('admin'), LeaveEditor::where('user_id', $user->id)->exists());
            return true;
        }
        return false;
    }
}
if (!function_exists('get_php_date_format')) {
    function get_php_date_format()
    {
        $general_settings = get_settings('general_settings');
        $date_format = $general_settings['date_format'] ?? 'DD-MM-YYYY|d-m-Y';
        $date_format = explode('|', $date_format);
        return $date_format[1];
    }
}
if (!function_exists('get_system_update_info')) {
    function get_system_update_info()
    {
        $updatePath = Config::get('constants.UPDATE_PATH');
        $updaterPath = $updatePath . 'updater.json';
        $subDirectory = (File::exists($updaterPath) && File::exists($updatePath . 'update/updater.json')) ? 'update/' : '';
        if (File::exists($updaterPath) || File::exists($updatePath . $subDirectory . 'updater.json')) {
            $updaterFilePath = File::exists($updaterPath) ? $updaterPath : $updatePath . $subDirectory . 'updater.json';
            $updaterContents = File::get($updaterFilePath);
            // Check if the file contains valid JSON data
            if (!json_decode($updaterContents)) {
                throw new \RuntimeException('Invalid JSON content in updater.json');
            }
            $linesArray = json_decode($updaterContents, true);
            if (!isset($linesArray['version'], $linesArray['previous'], $linesArray['manual_queries'], $linesArray['query_path'])) {
                throw new \RuntimeException('Invalid JSON structure in updater.json');
            }
        } else {
            throw new \RuntimeException('updater.json does not exist');
        }
        $dbCurrentVersion = Update::latest()->first();
        $data['db_current_version'] = $dbCurrentVersion ? $dbCurrentVersion->version : '1.0.0';
        if ($data['db_current_version'] == $linesArray['version']) {
            $data['updated_error'] = true;
            $data['message'] = 'Oops!. This version is already updated into your system. Try another one.';
            return $data;
        }
        if ($data['db_current_version'] == $linesArray['previous']) {
            $data['file_current_version'] = $linesArray['version'];
        } else {
            $data['sequence_error'] = true;
            $data['message'] = 'Oops!. Update must performed in sequence.';
            return $data;
        }
        $data['query'] = $linesArray['manual_queries'];
        $data['query_path'] = $linesArray['query_path'];
        return $data;
    }
}
if (!function_exists('escape_array')) {
    function escape_array($array)
    {
        if (empty($array)) {
            return $array;
        }
        $db = DB::connection()->getPdo();
        if (is_array($array)) {
            return array_map(function ($value) use ($db) {
                return $db->quote($value);
            }, $array);
        } else {
            // Handle single non-array value
            return $db->quote($array);
        }
    }
}
if (!function_exists('isEmailConfigured')) {
    function isEmailConfigured()
    {
        $email_settings = get_settings('email_settings');
        // dd($email_settings);
        if (
            isset($email_settings['email']) && !empty($email_settings['email']) &&
            isset($email_settings['password']) && !empty($email_settings['password']) &&
            isset($email_settings['smtp_host']) && !empty($email_settings['smtp_host']) &&
            isset($email_settings['smtp_port']) && !empty($email_settings['smtp_port'])
        ) {
            return true;
        } else {
            return false;
        }
    }
}
if (!function_exists('get_current_version')) {
    function get_current_version()
    {
        $dbCurrentVersion = Update::latest()->first();
        return $dbCurrentVersion ? $dbCurrentVersion->version : '1.0.0';
    }
}
if (!function_exists('isAdminOrHasAllDataAccess')) {
    function isAdminOrHasAllDataAccess($type = null, $id = null)
    {
        if ($type == 'user' && $id !== null) {
            $user = User::find($id);
            if ($user) {
                return $user->hasRole('admin') || $user->can('access_all_data') ? true : false;
            }
        } elseif ($type == 'client' && $id !== null) {
            $client = Client::find($id);
            if ($client) {
                return $client->hasRole('admin') || $client->can('access_all_data') ? true : false;
            }
        } elseif ($type == null && $id == null) {
            return getAuthenticatedUser()->hasRole('admin') || getAuthenticatedUser()->can('access_all_data') ? true : false;
        }
        return false;
    }
}
if (!function_exists('getControllerNames')) {
    function getControllerNames()
    {
        $controllersPath = app_path('Http/Controllers');
        $files = File::files($controllersPath);
        $excludedControllers = [
            'ActivityLogController',
            'Controller',
            'HomeController',
            'InstallerController',
            'LanguageController',
            'ProfileController',
            'RolesController',
            'SearchController',
            'SettingsController',
            'UpdaterController',
        ];
        $controllerNames = [];
        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            // Skip controllers in the excluded list
            if (in_array($fileName, $excludedControllers)) {
                continue;
            }
            if (str_ends_with($fileName, 'Controller')) {
                // Convert to singular form, snake_case, and remove 'Controller' suffix
                $controllerName = Str::snake(Str::singular(str_replace('Controller', '', $fileName)));
                $controllerNames[] = $controllerName;
            }
        }
        // Add manually defined types
        $manuallyDefinedTypes = [
            'contract_type',
            'media'
            // Add more types as needed
        ];
        $controllerNames = array_merge($controllerNames, $manuallyDefinedTypes);
        return $controllerNames;
    }
    if (!function_exists('formatSize')) {
        function formatSize($bytes)
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = 0;
            while ($bytes >= 1024 && $i < count($units) - 1) {
                $bytes /= 1024;
                $i++;
            }
            return round($bytes, 2) . ' ' . $units[$i];
        }
    }
}
if (!function_exists('getAdminIdByUserRole')) {
    function getAdminIdByUserRole()
    {
        $user = getAuthenticatedUser();
        if ($user) {
            $roles = $user->roles;
            foreach ($roles as $role) {
                switch ($role->name) {
                    case 'admin':
                        // If the user is an admin, fetch the admin ID directly
                        $admin = Admin::where('user_id', $user->id)->first();
                        return $admin ? $admin->id : null;
                    case 'member':
                        // If the user is a member, fetch the admin ID from the team member table
                        $teamMember = TeamMember::where('user_id', $user->id)->first();
                        return $teamMember ? $teamMember->admin_id : null;
                    case 'client':
                        // If the user is a client, fetch the admin ID from the client table
                        $client = Client::where('id', $user->id)->first();
                        return $client ? $client->admin_id : null;
                    default:
                        // For any other roles, fetch the admin ID from the team member table
                        $teamMember = TeamMember::where('user_id', $user->id)->first();
                        return $teamMember ? $teamMember->admin_id : null;
                }
            }
        }
        return null; // Return null if user is not logged in or has no role
    }
}
if (!function_exists('getSuperAdmin')) {
    function getSuperAdmin()
    {
        $role = Role::where('name', 'superadmin')->first();
        $superadmin = $role->users->first();
        return $superadmin;
    }
}
if (!function_exists('get_subscriptionFeatures')) {
    function get_subscriptionModules()
    {
        $user = getAuthenticatedUser();

        if (!$user) {
            return []; // User null ho to blank modules return kare
        }

        if ($user->hasRole('admin')) {
            $subscription = \App\Models\Subscription::where([
                'user_id' => $user->id,
                'status' => 'active',
            ])->first();
        } else {
            $adminID = getAdminIdByUserRole();
            $user = \App\Models\Admin::find($adminID);

            if (!$user) {
                return [];
            }

            $subscription = \App\Models\Subscription::where([
                'user_id' => $user->user_id,
                'status' => 'active',
            ])->first();
        }

        if ($subscription) {
            $features = json_decode($subscription->features);
            return $features->modules ?? [];
        }

        return [];
    }
}
if (!function_exists('getStatusColor')) {
    function getStatusColor($status)
    {
        switch ($status) {
            case 'sent':
                return 'primary';
            case 'accepted':
            case 'fully_paid':
                return 'success';
            case 'draft':
                return 'secondary';
            case 'declined':
            case 'due':
                return 'danger';
            case 'expired':
            case 'partially_paid':
                return 'warning';
            case 'not_specified':
                return 'secondary';
            default:
                return 'info';
        }
    }
}
if (!function_exists('getStatusCount')) {
    function getStatusCount($status, $type)
    {
        $query = DB::table('estimates_invoices')->where('type', $type);
        if (!empty($status)) {
            $query->where('status', $status);
        }
        return $query->count();
    }
}
if (!function_exists('format_currency')) {
    function format_currency($amount, $is_currency_symbol = 1)
    {
        $general_settings = get_settings('general_settings');
        $currency_symbol = $general_settings['currency_symbol'] ?? '₹';
        $currency_format = $general_settings['currency_formate'] ?? 'comma_separated';
        $decimal_points = intval($general_settings['decimal_points_in_currency'] ?? '2');
        $currency_symbol_position = $general_settings['currency_symbol_position'] ?? 'before';
        // Determine the appropriate separators based on the currency format
        $thousands_separator = ($currency_format == 'comma_separated') ? ',' : '.';
        // Format the amount with the determined separators
        // dd(number_format($amount, $decimal_points, '.', $thousands_separator));
        $formatted_amount = number_format($amount, $decimal_points, '.', $thousands_separator);
        if ($is_currency_symbol) {
            // Format currency symbol position
            if ($currency_symbol_position === 'before') {
                $currency_amount = $currency_symbol . ' ' . $formatted_amount;
            } else {
                $currency_amount = $formatted_amount . ' ' . $currency_symbol;
            }
            return $currency_amount;
        }
        return $formatted_amount;
    }
}
function get_tax_data($tax_id, $total_amount, $currency_symbol = 0)
{
    // Check if tax_id is not empty
    if ($tax_id != '') {
        // Retrieve tax data from the database using the tax_id
        $tax = Tax::find($tax_id);
        // Check if tax data is found
        if ($tax) {
            // Get tax rate and type
            $taxRate = $tax->amount;
            $taxType = $tax->type;
            // Calculate tax amount based on tax rate and type
            $taxAmount = 0;
            $disp_tax = '';
            if ($taxType == 'percentage') {
                $taxAmount = ($total_amount * $tax->percentage) / 100;
                $disp_tax = format_currency($taxAmount, $currency_symbol) . '(' . $tax->percentage . '%)';
            } elseif ($taxType == 'amount') {
                $taxAmount = $taxRate;
                $disp_tax = format_currency($taxAmount, $currency_symbol);
            }
            // Return the calculated tax data
            return [
                'taxAmount' => $taxAmount,
                'taxType' => $taxType,
                'dispTax' => $disp_tax,
            ];
        }
    }
    // Return empty data if tax_id is empty or tax data is not found
    return [
        'taxAmount' => 0,
        'taxType' => '',
        'dispTax' => '',
    ];
}
if (!function_exists('format_budget')) {
    function format_budget($amount)
    {
        // Check if the input is numeric or can be converted to a numeric value.
        if (!is_numeric($amount)) {
            // If the input is not numeric, return null or handle the error as needed.
            return null;
        }
        // Remove non-numeric characters from the input string.
        $amount = preg_replace('/[^0-9.]/', '', $amount);
        // Convert the input to a float.
        $amount = (float) $amount;
        // Define suffixes for thousands, millions, etc.
        $suffixes = ['', 'K', 'M', 'B', 'T'];
        // Determine the appropriate suffix and divide the amount accordingly.
        $suffixIndex = 0;
        while ($amount >= 1000 && $suffixIndex < count($suffixes) - 1) {
            $amount /= 1000;
            $suffixIndex++;
        }
        // Format the amount with the determined suffix.
        return number_format($amount, 2) . $suffixes[$suffixIndex];
    }
}
if (!function_exists('canSetStatus')) {
    function canSetStatus($status)
    {
        static $user = null;
        static $isAdminOrHasAllDataAccess = null;
        if ($user === null) {
            $user = getAuthenticatedUser();
        }
        if ($isAdminOrHasAllDataAccess === null) {
            $isAdminOrHasAllDataAccess = isAdminOrHasAllDataAccess();
        }
        // Check if the user has permission for this status
        $hasPermission = $status->roles->contains($user->roles->first()->id) || $isAdminOrHasAllDataAccess;
        return $hasPermission;
    }
}
if (!function_exists('checkPermission')) {
    function checkPermission($permission)
    {
        static $user = null;
        if ($user === null) {
            $user = getAuthenticatedUser();
        }
        return $user->can($permission);
    }
}
if (!function_exists('getUserPreferences')) {
    function getUserPreferences($table, $column = 'visible_columns', $userId = null)
    {
        if ($userId === null) {
            $userId = getAuthenticatedUser(true, true);
        }
        $result = UserClientPreference::where('user_id', $userId)
            ->where('table_name', $table)
            ->first();
        switch ($column) {
            case 'default_view':
                // if ($table == 'projects') {
                //     // dd($result->default_view);
                //     switch ($result->default_view) {
                //         case 'list':
                //             return 'list';
                //         case 'kanban_view':
                //             return 'kanban_view';
                //         case 'grid':
                //             return 'grid';
                //         default:
                //             return 'projects'; // or handle unexpected cases
                //     }
                // }
                if ($table == 'projects') {
                    return $result && $result->default_view
                        ? ($result->default_view == 'list' ? 'list'
                            : ($result->default_view == 'kanban_view' ? 'kanban_view'
                                : ($result->default_view == 'grid' ? 'grid'
                                    : 'projects')))
                        : 'projects';
                } elseif ($table == 'tasks') {
                    return $result && $result->default_view
                        ? ($result->default_view == 'draggable' ? 'tasks/draggable'
                            : ($result->default_view == 'calendar-view' ? 'tasks/calendar-view'
                                : ($result->default_view == 'group-by-task-list' ? 'tasks/group-by-task-list'
                                    : 'tasks')))
                        : 'tasks';
                }

                break;
            case 'visible_columns':
                return $result && $result->visible_columns ? $result->visible_columns : [];
                break;
            case 'enabled_notifications':
            case 'enabled_notifications':
                if ($result) {
                    if ($result->enabled_notifications === null) {
                        return null;
                    }
                    return json_decode($result->enabled_notifications, true);
                }
                return [];
                break;
                break;
            default:
                return null;
                break;
        }
    }
}
if (!function_exists('getOrdinalSuffix')) {
    function getOrdinalSuffix($number)
    {
        if (!in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return 'st';
                case 2:
                    return 'nd';
                case 3:
                    return 'rd';
            }
        }
        return 'th';
    }
}
if (!function_exists('get_php_date_time_format')) {
    function get_php_date_time_format($timeFormat = false)
    {
        $general_settings = get_settings('general_settings');
        if ($timeFormat) {
            return $general_settings['time_format'] ?? 'H:i:s';
        } else {
            $date_format = $general_settings['date_format'] ?? 'DD-MM-YYYY|d-m-Y';
            $date_format = explode('|', $date_format);
            return $date_format[1];
        }
    }
}
// Process all type of the notfications
if (!function_exists('processNotifications')) {
    /**
     * Process all type of the notfications
     *
     * @param array $data Notification data
     * @param array $recipients Recipients of the notification
     * @return void
     */
    function processNotifications($data, $recipients)
    {
        // Define an array of types for which email notifications should be sent
        $smsNotificationTypes = ['project_assignment', 'project_status_updation', 'task_assignment', 'task_status_updation', 'workspace_assignment', 'meeting_assignment', 'leave_request_creation', 'leave_request_status_updation', 'team_member_on_leave_alert', 'project_issue', 'announcement'];
        $emailNotificationTypes = ['project_assignment', 'project_status_updation', 'task_assignment', 'task_status_updation', 'workspace_assignment', 'meeting_assignment', 'leave_request_creation', 'leave_request_status_updation', 'team_member_on_leave_alert', 'project_issue', 'announcement'];
        if (!empty($recipients)) {
            $mapping = [
                'task_status_updation' => 'task',
                'project_status_updation' => 'project',
                'leave_request_creation' => 'leave_request',
                'leave_request_status_updation' => 'leave_request',
                'team_member_on_leave_alert' => 'leave_request',

            ];
            $type = $mapping[$data['type']] ?? $data['type'];
            $template = getNotificationTemplate($data['type'], 'system');

            if (!$template || ($template->status !== 0)) {
                $notification = Notification::create([
                    'workspace_id' => session()->get('workspace_id'),
                    'from_id' => isClient() ? 'c_' . session()->get('user_id') : 'u_' . session()->get('user_id'),
                    'type' => $type,
                    'type_id' => $data['type_id'],
                    'action' => $data['action'],
                    'title' => getTitle($data),
                    'message' => get_message($data, NULL, 'system'),
                ]);
            }
            // Exclude creator from receiving notification
            $loggedInUserId = isClient() ? 'c_' . getAuthenticatedUser()->id : 'u_' . getAuthenticatedUser()->id;
            $recipients = array_diff($recipients, [$loggedInUserId]);
            $recipients = array_unique($recipients);
            foreach ($recipients as $recipient) {
                $enabledNotifications = getUserPreferences('notification_preference', 'enabled_notifications', $recipient);
                $recipientId = substr($recipient, 2);
                if (substr($recipient, 0, 2) === 'u_') {
                    $recipientModel = User::find($recipientId);
                } elseif (substr($recipient, 0, 2) === 'c_') {
                    $recipientModel = Client::find($recipientId);
                }

                // Check if recipient was found
                if ($recipientModel) {
                    if (!$template || ($template->status !== 0)) {
                        if (
                            (is_array($enabledNotifications) && empty($enabledNotifications)) || (
                                is_array($enabledNotifications) && (
                                    in_array('system_' . $data['type'] . '_assignment', $enabledNotifications) ||
                                    in_array('system_' . $data['type'], $enabledNotifications)
                                )
                            )
                        ) {

                            $recipientModel->notifications()->attach($notification->id);
                        }
                    }
                    if (in_array($data['type'] . '_assignment', $emailNotificationTypes) || in_array($data['type'], $emailNotificationTypes)) {
                        if (
                            (is_array($enabledNotifications) && empty($enabledNotifications)) || (
                                is_array($enabledNotifications) && (
                                    in_array('email_' . $data['type'] . '_assignment', $enabledNotifications) ||
                                    in_array('email_' . $data['type'], $enabledNotifications)
                                )
                            )
                        ) {
                            try {
                                sendEmailNotification($recipientModel, $data);
                            } catch (\Exception $e) {
                                Log::error('Email Notification Error: ' . $e->getMessage());
                            } catch (TransportExceptionInterface $e) {
                                Log::error('Email Notification Transport Error: ' . $e->getMessage());
                            } catch (Throwable $e) {
                                Log::error('Email Notification Throwable Error: ' . $e->getMessage());
                                // Catch any other throwable, including non-Exception errors
                            }
                        }
                    }
                    if (in_array($data['type'] . '_assignment', $smsNotificationTypes) || in_array($data['type'], $smsNotificationTypes)) {
                        if (
                            (is_array($enabledNotifications) && empty($enabledNotifications)) || (
                                is_array($enabledNotifications) && (
                                    in_array('sms_' . $data['type'] . '_assignment', $enabledNotifications) ||
                                    in_array('sms_' . $data['type'], $enabledNotifications)
                                )
                            )
                        ) {
                            try {
                                sendSMSNotification($data, $recipientModel);
                            } catch (\Exception $e) {
                                Log::error('SMS Notification Error' . $e->getMessage());
                            }
                        }
                    }
                    if (
                        (is_array($enabledNotifications) && empty($enabledNotifications)) || (
                            is_array($enabledNotifications) && (
                                in_array('whatsapp_' . $data['type'] . '_assignment', $enabledNotifications) ||
                                in_array('whatsapp_' . $data['type'], $enabledNotifications)
                            )
                        )
                    ) {
                        try {
                            sendWhatsAppNotification($data, $recipientModel);
                        } catch (\Exception $e) {
                            Log::error('WhatsApp Notification Error: ' . $e->getMessage());
                        }
                    }
                    if (
                        (is_array($enabledNotifications) && empty($enabledNotifications)) || (
                            is_array($enabledNotifications) && (
                                in_array('slack_' . $data['type'] . '_assignment', $enabledNotifications) ||
                                in_array('slack_' . $data['type'], $enabledNotifications)
                            )
                        )
                    ) {
                        try {
                            // dd($data, $recipientModel);
                            sendSlackNotification($data, $recipientModel);
                        } catch (\Exception $e) {

                            Log::error('Slack Notification Error: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
if (!function_exists('sendEmailNotification')) {
    function sendEmailNotification($recipientModel, $data)
    {
        $template = getNotificationTemplate($data['type']);
        if (!$template || ($template->status !== 0)) {
            $recipientModel->notify(new AssignmentNotification($recipientModel, $data));
        }
    }
}
if (!function_exists('sendSlackNotification')) {
    function sendSlackNotification($data, $recipient)
    {
        $template = getNotificationTemplate($data['type'], 'slack');
        if (!$template || ($template->status !== 0)) {
            send_slack_notification($data, $recipient);
        }
    }
}
if (!function_exists('sendSMSNotification')) {
    function sendSMSNotification($data, $recipient)
    {
        $template = getNotificationTemplate($data['type'], 'sms');
        if (!$template || ($template->status !== 0)) {
            send_sms($data, $recipient);
        }
    }
}
if (!function_exists('sendWhatsAppNotification')) {
    function sendWhatsAppNotification($data, $recipient)
    {
        $template = getNotificationTemplate($data['type'], 'whatsapp');
        if (!$template || ($template->status !== 0)) {
            send_whatsapp_notification($data, $recipient);
        }
    }
}
if (!function_exists('getNotificationTemplate')) {
    /**
     * Retrieves the notification template based on the given type and medium.
     *
     * This function queries the Template model to find a template record that matches
     * the provided type and medium (either 'email' or 'sms'). It first attempts to find
     * a template with the name pattern '{type}_assignment'. If not found, it searches
     * for a template with the name corresponding to the type. Returns the first matching
     * template or null if no match is found.
     *
     * @param string $type The type of the notification (e.g., 'project', 'task').
     * @param string $emailOrSMS The medium of the notification, either 'email' or 'sms'.
     *                           Defaults to 'email'.
     *
     * @return \App\Models\Template|null The notification template or null if not found.
     */

    function getNotificationTemplate($type, $emailOrSMS = 'email')
    {
        $template = Template::where('type', $emailOrSMS)
            ->where('name', $type . '_assignment')
            ->first();
        if (!$template) {
            // If template with $type . '_assignment' name not found, check for template with $type name
            $template = Template::where('type', $emailOrSMS)
                ->where('name', $type)
                ->first();
        }
        return $template;
    }
}
if (!function_exists('send_sms')) {
    /**
     * Sends an SMS message using predefined settings.
     *
     * This function constructs and sends an SMS message via a specified SMS gateway.
     * It retrieves the message content using the get_message function and formats
     * it according to the gateway's requirements, including body, header, and params.
     * The settings for the SMS gateway are retrieved from the application's configuration.
     *
     * @param array $itemData The data required to construct the message content.
     * @param object $recipient The recipient object containing phone number and country code.
     *
     * @return void
     */

    function send_sms($itemData, $recipient)
    {
        // print_r($recipient);
        $msg = get_message($itemData, $recipient);
        try {
            $sms_gateway_settings = get_settings('sms_gateway_settings', true);
            $data = [
                "base_url" => $sms_gateway_settings['base_url'],
                "sms_gateway_method" => $sms_gateway_settings['sms_gateway_method']
            ];
            $data["body"] = [];
            if (isset($sms_gateway_settings["body_formdata"])) {
                foreach ($sms_gateway_settings["body_formdata"] as $key => $value) {
                    $value = parse_sms($value, $recipient->phone, $msg, $recipient->country_code);
                    $data["body"][$key] = $value;
                }
            }
            $data["header"] = [];
            if (isset($sms_gateway_settings["header_data"])) {
                foreach ($sms_gateway_settings["header_data"] as $key => $value) {
                    $value = parse_sms($value, $recipient->phone, $msg, $recipient->country_code);
                    $data["header"][] = $key . ": " . $value;
                }
            }
            $data["params"] = [];
            if (isset($sms_gateway_settings["params_data"])) {
                foreach ($sms_gateway_settings["params_data"] as $key => $value) {
                    $value = parse_sms($value, $recipient->phone, $msg, $recipient->country_code);
                    $data["params"][$key] = $value;
                }
            }
            $response = curl_sms($data["base_url"], $data["sms_gateway_method"], $data["body"], $data["header"]);
            // print_r($response);
        } catch (Exception $e) {
            // Handle the exception
            // echo 'Error: ' . $e->getMessage();
        }
    }
}
if (!function_exists('send_whatsapp_notification')) {
    /**
     * Sends a WhatsApp notification using a predefined template.
     *
     * This function constructs and sends a WhatsApp message via the Facebook Graph API,
     * using the 'taskify_saas_notification' template. It replaces placeholders in the
     * template with the provided message and company title. The function logs the success
     * or failure of the message sending process.
     *
     * @param array $itemData The data required to construct the message content.
     * @param object $recipient The recipient object containing phone number and country code.
     *
     * @return void
     */

    function send_whatsapp_notification($itemData, $recipient)
    {
        $msg = get_message($itemData, $recipient, 'whatsapp');


        $admin = Admin::find(getAdminIdByUserRole());

        $admin_settings = json_decode($admin->admin_settings, true) ?? [];
        $whatsapp_settings = $admin_settings['whatsapp_settings'] ?? [];


        $client = new GuzzleHttpClient();

        // Old Meta API Whatsapp Notifications

        // $whatsapp_settings = get_settings('whatsapp_settings', true);
        // $general_settings = get_settings('general_settings');
        // $company_title = $general_settings['company_title'] ?? 'Taskify';
        // try {
        //     $response = $client->post('https://graph.facebook.com/v20.0/' . $whatsapp_settings['whatsapp_phone_number_id'] . '/messages', [
        //         'headers' => [
        //             'Authorization' => 'Bearer ' . $whatsapp_settings['whatsapp_access_token'],
        //             'Content-Type' => 'application/json',
        //         ],
        //         'json' => [
        //             'messaging_product' => 'whatsapp',
        //             'recipient_type' => 'individual',
        //             'to' => $recipient->country_code . $recipient->phone,
        //             'type' => 'template',
        //             'template' => [
        //                 'name' => 'taskify_saas_notification',
        //                 'language' => [
        //                     'code' => 'en'
        //                 ],
        //                 'components' => [
        //                     [
        //                         'type' => 'body',
        //                         'parameters' => [
        //                             [
        //                                 'type' => 'text',
        //                                 'text' => $msg  // This will replace {{1}}
        //                             ],
        //                             [
        //                                 'type' => 'text',
        //                                 'text' => $company_title  // This will replace {{2}}
        //                             ]
        //                         ]
        //                     ]
        //                 ]
        //             ]
        //         ],
        //     ]);
        //     $data = json_decode($response->getBody(), true);
        //     Log::info("Message sent successfully. Response: " . print_r($data, true));
        // } catch (RequestException $e) {
        //     Log::error("Error sending message: " . $e->getMessage());
        //     if ($e->hasResponse()) {
        //         Log::error("Response: " . $e->getResponse()->getBody()->getContents());
        //     }
        // }


        $api_key = $whatsapp_settings['api_key'] ?? '';
        $sender_number = $whatsapp_settings['sender_mobile_number'] ?? '';
        $recipient_number = ltrim($recipient->country_code, '+') . $recipient->phone;

        $client = new GuzzleHttpClient();


        try {
            $response = $client->post('https://web.taskway.in/send-message', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'api_key' => $api_key,
                    'sender' => $sender_number,
                    'number' => $recipient_number,
                    'message' => $msg,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            Log::info("WhatsApp message sent successfully via Taskway. Response: " . print_r($data, true));
        } catch (RequestException $e) {
            Log::error("Error sending WhatsApp message via Taskway: " . $e->getMessage());

            if ($e->hasResponse()) {
                Log::error("Taskway Response: " . $e->getResponse()->getBody()->getContents());
            }
        }
    }
}
if (!function_exists('send_slack_notification')) {
    /**
     * Send a Slack direct message to a user, given an item data array and a recipient object.
     *
     * @param array $itemData An array containing the item data.
     * @param object $recipient A user object containing the email address of the recipient.
     *
     * @return void
     */
    function send_slack_notification($itemData, $recipient)
    {
        $msg = get_message($itemData, $recipient);
        $slack_settings = get_settings('slack_settings');
        // dd($itemData, $recipient, $msg, $slack_settings);
        $botToken = $slack_settings['slack_bot_token'];
        // Create a Guzzle client for Slack API
        $client = new GuzzleHttpClient([
            'base_uri' => 'https://slack.com/api/',
            'headers' => [
                'Authorization' => 'Bearer ' . $botToken,
                'Content-Type' => 'application/json',
            ],
        ]);
        // Step 4: Look up the Slack user ID by email
        // dd($recipient);
        $email = $recipient->email;
        // dd($itemData, $recipient);
        // $email = 'infinitietechnologies10@gmail.com';
        $userId = get_slack_user_id_by_email($client, $email);
        if ($userId) {
            // Step 5: Prepare the message payload
            // Assuming template has a 'content' field
            $slackMessage = [
                'channel' => $userId,
                'text' => $msg,
                'username' => 'Taskify Notification',
                'icon_emoji' => ':office:',
            ];
            try {
                // Step 6: Send the Slack message
                $response = $client->post('chat.postMessage', [
                    'json' => $slackMessage
                ]);
                $responseBody = json_decode(
                    $response->getBody(),
                    true
                );
                if ($responseBody['ok']) {
                    Log::info('Slack DM sent successfully to user: ' . $userId);
                } else {
                    Log::warning('Failed to send Slack DM to user ' . $userId . ': ' . $responseBody['error']);
                }
            } catch (\Exception $e) {
                Log::error('Error sending Slack DM to user: ' . $userId . ', Error: ' . $e->getMessage());
            }
        } else {
            Log::warning('Slack user ID not found for email: ' . $email);
        }
    }
}
/**
 * Helper function to get Slack user ID by email
 */
function get_slack_user_id_by_email($client, $email)
{
    // dd($email);
    try {
        $response = $client->get('users.lookupByEmail', [
            'query' => ['email' => $email]
        ]);
        $body = json_decode($response->getBody(), true);
        if ($body['ok'] === true) {
            return $body['user']['id']; // Return Slack User ID
        } else {
            Log::error("Failed to get Slack user ID: " . $body['error']);
        }
    } catch (\Exception $e) {
        Log::error('Error getting Slack user ID for email ' . $email . ': ' . $e->getMessage());
    }
}
if (!function_exists('curl_sms')) {
    /**
     * Perform a curl request to the specified URL
     *
     * @param string $url The URL to make the request to
     * @param string $method The HTTP method to use (default: GET)
     * @param array $data The data to send with the request (default: empty array)
     * @param array $headers The headers to send with the request (default: empty array)
     *
     * @return array An associative array with the following keys:
     *     - body: The response body as a JSON-decoded array
     *     - http_code: The HTTP status code of the response
     */
    function curl_sms($url, $method = 'GET', $data = [], $headers = [])
    {
        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            )
        );
        if (count($headers) != 0) {
            $curl_options[CURLOPT_HTTPHEADER] = $headers;
        }
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
        } else {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);
        $result = array(
            'body' => json_decode(curl_exec($ch), true),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        );
        return $result;
    }
}
if (!function_exists('parse_sms')) {
    /**
     * Parses a given SMS template and replaces placeholders with actual values.
     *
     * This function is a placeholder and should be replaced with actual SMS parsing logic.
     *
     * @param string $template The SMS template containing placeholders.
     * @param string $phone The recipient's phone number.
     * @param string $msg The message content.
     * @param string $country_code The recipient's country code.
     *
     * @return string The parsed SMS template with placeholders replaced by actual values.
     */
    function parse_sms($template, $phone, $msg, $country_code)
    {
        // Implement your parsing logic here
        // This is just a placeholder
        return str_replace(['{only_mobile_number}', '{message}', '{country_code}'], [$phone, $msg, $country_code], $template);
    }
}
if (!function_exists('get_message')) {
    /**
     * Generates a notification message based on the provided data, recipient, and type.
     *
     * This function retrieves a template based on the notification type and data,
     * and fills in placeholders with the appropriate content for the recipient.
     *
     * @param array $data An associative array containing notification details, such as type, type_id, type_title, etc.
     * @param object $recipient The recipient object that contains recipient details, such as first_name, last_name, and email.
     * @param string $type The type of notification (e.g., 'sms', 'system', 'slack'), default is 'sms'.
     *
     * @return string The generated notification message with placeholders replaced by actual values.
     */

    function get_message($data, $recipient, $type = 'sms')
    {
        static $authUser = null;
        static $company_title = null;
        if ($authUser === null) {
            $authUser = getAuthenticatedUser();
        }
        if ($company_title === null) {
            $general_settings = get_settings('general_settings');
            $company_title = $general_settings['company_title'] ?? 'Taskify-SaaS';
        }
        $siteUrl = request()->getSchemeAndHttpHost() . '/master-panel';
        $fetched_data = Template::where('type', $type)
            ->where('name', $data['type'] . '_assignment')
            ->first();
        if (!$fetched_data) {
            // If template with $this->data['type'] . '_assignment' name not found, check for template with $this->data['type'] name
            $fetched_data = Template::where('type', $type)
                ->where('name', $data['type'])
                ->first();
        }
        $templateContent = 'Default Content';
        $contentPlaceholders = []; // Initialize outside the switch
        // Customize content based on type
        if ($type === 'system') {
            switch ($data['type']) {
                case 'project':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} assigned you new project: {PROJECT_TITLE}, ID:#{PROJECT_ID}.';
                    break;
                case 'project_status_updation':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of project {PROJECT_TITLE}, ID:#{PROJECT_ID}, from {OLD_STATUS} to {NEW_STATUS}.';
                    break;
                case 'project_issue':
                    $contentPlaceholders = [
                        '{ISSUE_ID}' => $data['type_id'],
                        '{ISSUE_TITLE}' => $data['type_title'],
                        '{STATUS}' => $data['status'],
                        '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                        '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                        '{ACCESS_URL}' => $siteUrl . '/' . $data['access_url'],

                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has Assigned you new issue: {ISSUE_TITLE}, ID:#{ISSUE_ID} ,Status : {STATUS}';
                    break;
                case 'task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} assigned you new task: {TASK_TITLE}, ID:#{TASK_ID}.';
                    break;
                case 'task_status_updation':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of task {TASK_TITLE}, ID:#{TASK_ID}, from {OLD_STATUS} to {NEW_STATUS}.';
                    break;
                case 'workspace':
                    $contentPlaceholders = [
                        '{WORKSPACE_ID}' => $data['type_id'],
                        '{WORKSPACE_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{WORKSPACE_URL}' => $siteUrl . '/workspaces'
                    ];
                    $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} added you in a new workspace {WORKSPACE_TITLE}, ID:#{WORKSPACE_ID}.';
                    break;
                case 'meeting':
                    $contentPlaceholders = [
                        '{MEETING_ID}' => $data['type_id'],
                        '{MEETING_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{MEETING_URL}' => $siteUrl . '/meetings'
                    ];
                    $templateContent = '{ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME} added you in a new meeting {MEETING_TITLE}, ID:#{MEETING_ID}.';
                    break;
                case 'leave_request_creation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{REASON}' => $data['reason'],
                        '{STATUS}' => $data['status'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = 'New Leave Request ID:#{ID} Has Been Created By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME}.';
                    break;
                case 'leave_request_status_updation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{REASON}' => $data['reason'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = 'Leave Request ID:#{ID} Status Updated From {OLD_STATUS} To {NEW_STATUS}.';
                    break;
                case 'team_member_on_leave_alert':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.';
                    break;
                case 'announcement':
                    $contentPlaceholders = [
                        '{ANNOUNCEMENT_ID}' => $data['type_id'],
                        '{ANNOUNCEMENT_TITLE}' => $data['type_title'],
                        '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                        '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{ACCESS_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{CURRENT_YEAR}' => date('Y'),
                    ];
                    $templateContent = '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has made a new announcement titled "{ANNOUNCEMENT_TITLE}". Shared by {COMPANY_TITLE} ({CURRENT_YEAR}).';
                    break;
                case 'task_reminder':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    $templateContent = 'You have a task reminder for Task #{TASK_ID} - "{TASK_TITLE}". You can view the task here: {TASK_URL}.';
                    break;
                case 'recurring_task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    $templateContent = 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed. You can view the new instance here: {TASK_URL}';
                    break;
            }
        } else if ($type === 'slack') {
            switch ($data['type']) {
                case 'project':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    $templateContent = '*New Project Assigned:* {PROJECT_TITLE}, ID: #{PROJECT_ID}. By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME}
You can find the project here :{PROJECT_URL}';
                    break;
                case 'project_status_updation':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*Project Status Updated:* By {UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} , {PROJECT_TITLE}, ID: #{PROJECT_ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`.
You can find the project here :{PROJECT_URL}';
                    break;
                case 'project_issue':
                    $contentPlaceholders = [
                        '{ISSUE_ID}' => $data['type_id'],
                        '{ISSUE_TITLE}' => $data['type_title'],
                        '{STATUS}' => $data['status'],
                        '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                        '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                        '{ACCESS_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has Assigned you new issue: {ISSUE_TITLE}, ID:#{ISSUE_ID} ,Status : {STATUS}';
                    break;
                case 'task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url']
                    ];
                    $templateContent = '*New Task Assigned:* {TASK_TITLE}, ID: #{TASK_ID}. By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME}
You can find the task here : {TASK_URL}';
                    break;
                case 'task_status_updation':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*Task Status Updated:* By {UPDATER_FIRST_NAME} {UPDATER_LAST_NAME},  {TASK_TITLE}, ID: #{TASK_ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`.
You can find the Task here : {TASK_URL}';
                    break;
                case 'workspace':
                    $contentPlaceholders = [
                        '{WORKSPACE_ID}' => $data['type_id'],
                        '{WORKSPACE_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{WORKSPACE_URL}' => $siteUrl . '/workspaces'
                    ];
                    $templateContent = '*New Workspace Added:* By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME},   {WORKSPACE_TITLE}, ID: #{WORKSPACE_ID}.
You can find the Workspace here : {WORKSPACE_URL}';
                    break;
                case 'meeting':
                    $contentPlaceholders = [
                        '{MEETING_ID}' => $data['type_id'],
                        '{MEETING_TITLE}' => $data['type_title'],
                        '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                        '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{MEETING_URL}' => $siteUrl . '/meetings'
                    ];
                    $templateContent = 'New Meeting Scheduled:* By {ASSIGNEE_FIRST_NAME} {ASSIGNEE_LAST_NAME},  {MEETING_TITLE}, ID: #{MEETING_ID}.
You can find the Meeting here : {MEETING_URL}';
                    break;
                case 'leave_request_creation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{REASON}' => $data['reason'],
                        '{STATUS}' => $data['status'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*New {TYPE} Leave Request Created:* ID: #{ID} By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} for {REASON}.  From ( {FROM} ) -  To ( {TO} ).';
                    break;
                case 'leave_request_status_updation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{REASON}' => $data['reason'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*Leave Request Status Updated:* For {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME},  ID: #{ID}. Status changed from `{OLD_STATUS}` to `{NEW_STATUS}`.';
                    break;
                case 'team_member_on_leave_alert':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '*Team Member Leave Alert:* {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.';
                    break;
                case 'announcement':
                    $contentPlaceholders = [
                        '{ANNOUNCEMENT_ID}' => $data['type_id'],
                        '{ANNOUNCEMENT_TITLE}' => $data['type_title'],
                        '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                        '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{ACCESS_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{CURRENT_YEAR}' => date('Y'),
                    ];
                    $templateContent = '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has made a new announcement titled "{ANNOUNCEMENT_TITLE}". Shared by {COMPANY_TITLE} ({CURRENT_YEAR}).';
                    break;
                case 'task_reminder':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    $templateContent = 'You have a task reminder for Task #{TASK_ID} - "{TASK_TITLE}". You can view the task here: {TASK_URL}.';
                    break;
                case 'recurring_task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    $templateContent = 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed. You can view the new instance here: {TASK_URL}';
                    break;
            }
        } else {
            switch ($data['type']) {
                case 'project':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{SITE_URL}' => $siteUrl
                    ];
                    $templateContent = 'Hello, {FIRST_NAME} {LAST_NAME} You have been assigned a new project {PROJECT_TITLE}, ID:#{PROJECT_ID}.';
                    break;
                case 'project_status_updation':
                    $contentPlaceholders = [
                        '{PROJECT_ID}' => $data['type_id'],
                        '{PROJECT_TITLE}' => $data['type_title'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{PROJECT_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{SITE_URL}' => $siteUrl,
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of project {PROJECT_TITLE}, ID:#{PROJECT_ID}, from {OLD_STATUS} to {NEW_STATUS}.';
                    break;
                case 'project_issue':
                    $contentPlaceholders = [
                        '{ISSUE_ID}' => $data['type_id'],
                        '{ISSUE_TITLE}' => $data['type_title'],
                        '{STATUS}' => $data['status'],
                        '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                        '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                        '{ACCESS_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has Assigned you new issue: {ISSUE_TITLE}, ID:#{ISSUE_ID} ,Status : {STATUS}';
                    break;
                case 'task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{SITE_URL}' => $siteUrl
                    ];
                    $templateContent = 'Hello, {FIRST_NAME} {LAST_NAME} You have been assigned a new task {TASK_TITLE}, ID:#{TASK_ID}.';
                    break;
                case 'task_status_updation':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                        '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{SITE_URL}' => $siteUrl,
                        '{COMPANY_TITLE}' => $company_title
                    ];
                    $templateContent = '{UPDATER_FIRST_NAME} {UPDATER_LAST_NAME} has updated the status of task {TASK_TITLE}, ID:#{TASK_ID}, from {OLD_STATUS} to {NEW_STATUS}.';
                    break;
                case 'workspace':
                    $contentPlaceholders = [
                        '{WORKSPACE_ID}' => $data['type_id'],
                        '{WORKSPACE_TITLE}' => $data['type_title'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{WORKSPACE_URL}' => $siteUrl . '/workspaces',
                        '{SITE_URL}' => $siteUrl
                    ];
                    $templateContent = 'Hello, {FIRST_NAME} {LAST_NAME} You have been added in a new workspace {WORKSPACE_TITLE}, ID:#{WORKSPACE_ID}.';
                    break;
                case 'meeting':
                    $contentPlaceholders = [
                        '{MEETING_ID}' => $data['type_id'],
                        '{MEETING_TITLE}' => $data['type_title'],
                        '{FIRST_NAME}' => $recipient->first_name,
                        '{LAST_NAME}' => $recipient->last_name,
                        '{COMPANY_TITLE}' => $company_title,
                        '{MEETING_URL}' => $siteUrl . '/meetings',
                        '{SITE_URL}' => $siteUrl
                    ];
                    $templateContent = 'Hello, {FIRST_NAME} {LAST_NAME} You have been added in a new meeting {MEETING_TITLE}, ID:#{MEETING_ID}.';
                    break;
                case 'leave_request_creation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{REASON}' => $data['reason'],
                        '{STATUS}' => $data['status'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{SITE_URL}' => $siteUrl,
                        '{CURRENT_YEAR}' => date('Y')
                    ];
                    $templateContent = 'New Leave Request ID:#{ID} Has Been Created By {REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME}.';
                    break;
                case 'leave_request_status_updation':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{REASON}' => $data['reason'],
                        '{OLD_STATUS}' => $data['old_status'],
                        '{NEW_STATUS}' => $data['new_status'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{SITE_URL}' => $siteUrl,
                        '{CURRENT_YEAR}' => date('Y')
                    ];
                    $templateContent = 'Leave Request ID:#{ID} Status Updated From {OLD_STATUS} To {NEW_STATUS}.';
                    break;
                case 'team_member_on_leave_alert':
                    $contentPlaceholders = [
                        '{ID}' => $data['type_id'],
                        '{USER_FIRST_NAME}' => $recipient->first_name,
                        '{USER_LAST_NAME}' => $recipient->last_name,
                        '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                        '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                        '{TYPE}' => $data['leave_type'],
                        '{FROM}' => $data['from'],
                        '{TO}' => $data['to'],
                        '{DURATION}' => $data['duration'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{SITE_URL}' => $siteUrl,
                        '{CURRENT_YEAR}' => date('Y')
                    ];
                    $templateContent = '{REQUESTEE_FIRST_NAME} {REQUESTEE_LAST_NAME} will be on {TYPE} leave from {FROM} to {TO}.';
                    break;
                case 'announcement':
                    $contentPlaceholders = [
                        '{ANNOUNCEMENT_ID}' => $data['type_id'],
                        '{ANNOUNCEMENT_TITLE}' => $data['type_title'],
                        '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                        '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{ACCESS_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{CURRENT_YEAR}' => date('Y'),
                    ];
                    $templateContent = '{CREATOR_FIRST_NAME} {CREATOR_LAST_NAME} has made a new announcement titled "{ANNOUNCEMENT_TITLE}". Shared by {COMPANY_TITLE} ({CURRENT_YEAR}).';
                    break;
                case 'task_reminder':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title,
                        '{SITE_URL}' => $siteUrl
                    ];
                    $templateContent = 'You have a task reminder for Task #{TASK_ID} - {TASK_TITLE}. You can view the task here: {TASK_URL}';
                    break;
                case 'recurring_task':
                    $contentPlaceholders = [
                        '{TASK_ID}' => $data['type_id'],
                        '{TASK_TITLE}' => $data['type_title'],
                        '{TASK_URL}' => $siteUrl . '/' . $data['access_url'],
                        '{COMPANY_TITLE}' => $company_title,
                    ];
                    $templateContent = 'The recurring task #{TASK_ID} - "{TASK_TITLE}" has been executed. You can view the new instance here: {TASK_URL}';
                    break;
            }
        }
        if (filled(Arr::get($fetched_data, 'content'))) {
            $templateContent = $fetched_data->content;
        }
        // Replace placeholders with actual values
        $content = str_replace(array_keys($contentPlaceholders), array_values($contentPlaceholders), $templateContent);
        return $content;
    }
}
if (!function_exists('getTitle')) {
    /**
     * Generates a title for notifications based on the data type and context.
     *
     * This function retrieves the authenticated user and company title and uses
     * them along with the provided data to generate a notification title. It
     * supports various types, including project, task, workspace, meeting,
     * leave requests, status updates, and announcements, customizing the subject
     * with relevant placeholders for each type.
     *
     * @param array $data An associative array containing data for generating the title.
     *                    Expected keys include 'type', 'type_id', 'type_title', and
     *                    other context-specific keys such as 'status', 'old_status',
     *                    'new_status', 'team_member_first_name', 'team_member_last_name',
     *                    'updater_first_name', 'updater_last_name', 'creator_first_name',
     *                    'creator_last_name', and 'access_url'.
     *
     * @return string The generated title with placeholders replaced by actual values.
     */

    function getTitle($data)
    {
        static $authUser = null;
        static $companyTitle = null;
        if ($authUser === null) {
            $authUser = getAuthenticatedUser();
        }
        if ($companyTitle === null) {
            $general_settings = get_settings('general_settings');
            $companyTitle = $general_settings['company_title'] ?? 'Taskify';
        }
        $fetched_data = Template::where('type', 'system')
            ->where('name', $data['type'] . '_assignment')
            ->first();
        if (!$fetched_data) {
            $fetched_data = Template::where('type', 'system')
                ->where('name', $data['type'])
                ->first();
        }
        $subject = 'Default Subject'; // Set a default subject
        $subjectPlaceholders = [];
        // Customize subject based on type
        switch ($data['type']) {
            case 'project':
                $subjectPlaceholders = [
                    '{PROJECT_ID}' => $data['type_id'],
                    '{PROJECT_TITLE}' => $data['type_title'],
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'task':
                $subjectPlaceholders = [
                    '{TASK_ID}' => $data['type_id'],
                    '{TASK_TITLE}' => $data['type_title'],
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'workspace':
                $subjectPlaceholders = [
                    '{WORKSPACE_ID}' => $data['type_id'],
                    '{WORKSPACE_TITLE}' => $data['type_title'],
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'meeting':
                $subjectPlaceholders = [
                    '{MEETING_ID}' => $data['type_id'],
                    '{MEETING_TITLE}' => $data['type_title'],
                    '{ASSIGNEE_FIRST_NAME}' => $authUser->first_name,
                    '{ASSIGNEE_LAST_NAME}' => $authUser->last_name,
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'leave_request_creation':
                $subjectPlaceholders = [
                    '{ID}' => $data['type_id'],
                    '{STATUS}' => $data['status'],
                    '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                    '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'leave_request_status_updation':
                $subjectPlaceholders = [
                    '{ID}' => $data['type_id'],
                    '{OLD_STATUS}' => $data['old_status'],
                    '{NEW_STATUS}' => $data['new_status'],
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'team_member_on_leave_alert':
                $subjectPlaceholders = [
                    '{ID}' => $data['type_id'],
                    '{REQUESTEE_FIRST_NAME}' => $data['team_member_first_name'],
                    '{REQUESTEE_LAST_NAME}' => $data['team_member_last_name'],
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'project_status_updation':
                $subjectPlaceholders = [
                    '{PROJECT_ID}' => $data['type_id'],
                    '{PROJECT_TITLE}' => $data['type_title'],
                    '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                    '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                    '{OLD_STATUS}' => $data['old_status'],
                    '{NEW_STATUS}' => $data['new_status'],
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'project_issue':
                $subjectPlaceholders = [
                    '{ISSUE_ID}' => $data['type_id'],
                    '{ISSUE_TITLE}' => $data['type_title'],
                    '{STATUS}' => $data['status'],
                    '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                    '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                    '{ACCESS_URL}' => $data['access_url'],

                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'task_status_updation':
                $subjectPlaceholders = [
                    '{TASK_ID}' => $data['type_id'],
                    '{TASK_TITLE}' => $data['type_title'],
                    '{UPDATER_FIRST_NAME}' => $data['updater_first_name'],
                    '{UPDATER_LAST_NAME}' => $data['updater_last_name'],
                    '{OLD_STATUS}' => $data['old_status'],
                    '{NEW_STATUS}' => $data['new_status'],
                    '{COMPANY_TITLE}' => $companyTitle
                ];
                break;
            case 'announcement':
                $subjectPlaceholders = [
                    '{ANNOUNCEMENT_ID}' => $data['type_id'],
                    '{ANNOUNCEMENT_TITLE}' => $data['type_title'],
                    '{CREATOR_FIRST_NAME}' => $data['creator_first_name'],
                    '{CREATOR_LAST_NAME}' => $data['creator_last_name'],
                    '{CURRENT_YEAR}' => date('Y'),
                ];
                break;
            case 'task_reminder':
            case 'recurring_task':
                $subjectPlaceholders = [
                    '{TASK_TITLE}' => $data['type_title'],
                    '{TASK_ID}' => $data['type_id'],
                    '{COMPANY_TITLE}' => $companyTitle,
                    '{CURRENT_YEAR}' => date('Y'),
                ];
                break;
        }
        if (filled(Arr::get($fetched_data, 'subject'))) {
            $subject = $fetched_data->subject;
        } else {
            if ($data['type'] == 'leave_request_creation') {
                $subject = 'Leave Requested';
            } elseif ($data['type'] == 'leave_request_status_updation') {
                $subject = 'Leave Request Status Updated';
            } elseif ($data['type'] == 'team_member_on_leave_alert') {
                $subject = 'Team Member on Leave Alert';
            } elseif ($data['type'] == 'project_status_updation') {
                $subject = 'Project Status Updated';
            } elseif ($data['type'] == 'task_status_updation') {
                $subject = 'Task Status Updated';
            } else {
                $subject = 'New ' . ucfirst($data['type']) . ' Assigned';
            }
        }
        $subject = str_replace(array_keys($subjectPlaceholders), array_values($subjectPlaceholders), $subject);
        return $subject;
    }
}
if (!function_exists('hasPrimaryWorkspace')) {
    /**
     * Checks if there is a primary workspace and returns its ID.
     *
     * @return int The ID of the primary workspace if it exists, otherwise 0.
     */

    function hasPrimaryWorkspace()
    {
        $primaryWorkspace = \App\Models\Workspace::where('is_primary', 1)->first();
        return $primaryWorkspace ? $primaryWorkspace->id : 0;
    }
}

if (!function_exists('replaceUserMentionsWithLinks')) {
    /**
     * Replace plain @mentions in the given content with HTML links to the user's profile.
     *
     * @param string $content
     * @return array [$modifiedContent, $mentionedUserIds]
     */
    function replaceUserMentionsWithLinks($content)
    {
        // Find all @mentions in the content
        preg_match_all('/@([A-Za-z]+\s[A-Za-z]+)/', $content, $matches);
        // Initialize modified content
        $modifiedContent = $content;
        $mentionedUserIds = [];
        // Check if any matches were found
        if (!empty($matches[1])) {
            foreach ($matches[1] as $fullName) {
                // Try to find the user by their full name (first_name + last_name)
                $user = User::where(DB::raw("CONCAT(first_name, ' ', last_name)"), '=', $fullName)->first();
                if ($user) {
                    // Add user ID to the list of mentioned user IDs
                    $mentionedUserIds[] = $user->id;
                    // Create a profile link for the mentioned user
                    $mentionLink = '<a target="blank" href="' . route('users.show', ['id' => $user->id]) . '">@' . $fullName . '</a>';
                    // Replace the plain @mention with the linked version
                    $modifiedContent = str_replace(
                        '@' . $fullName,
                        $mentionLink,
                        $modifiedContent
                    );
                }
            }
        }
        return [$modifiedContent, $mentionedUserIds];
    }
}
if (!function_exists('sendMentionNotification')) {
    /**
     * Send a notification to all users who were mentioned in a comment.
     *
     * @param \App\Models\Comment $comment The comment that was posted.
     * @param int[] $mentionedUserIds The IDs of the users who were mentioned.
     * @param int $workspaceId The ID of the workspace where the comment was posted.
     * @param int $currentUserId The ID of the user who posted the comment.
     *
     * @return void
     */
    function
    sendMentionNotification(
        $comment,
        $mentionedUserIds,
        $workspaceId,
        $currentUserId
    ) {
        // dd($mentionedUserIds);
        $mentionedUserIds = array_unique($mentionedUserIds);
        $moduleType = '';
        $url = '';
        switch ($comment->commentable_type) {
            case 'App\Models\Task':
                $moduleType = 'task';
                $url = route('tasks.info', ['id' => $comment->commentable_id]);
                break;
            case 'App\Models\Project':
                $moduleType = 'project';
                $url = route('projects.info', ['id' => $comment->commentable_id]);
                break;
            default:
                $moduleType = '';
                break;
        }
        $module = [];
        if ($moduleType) {
            switch ($moduleType) {
                case 'task':
                    $module = Task::find($comment->commentable_id);
                    break;
                case 'project':
                    $module = Project::find($comment->commentable_id);
                    break;
                default:
                    break;
            }
        }
        foreach ($mentionedUserIds as $userId) {
            $notification = Notification::create([
                'workspace_id' => $workspaceId,
                'from_id' => 'u_' . $currentUserId,
                'type' => $moduleType . '_comment_mention',
                'type_id' => $module->id,
                'action' => 'mentioned',
                'title' => 'You were mentioned in a comment',
                'message' => 'You were mentioned in a comment by ' . getAuthenticatedUser()->first_name . ' ' . getAuthenticatedUser()->last_name . ' in ' . ucfirst($moduleType) . ' #' . $module->title . '. Click <a href="' . $url . '">here</a> to view the comment.',
            ]);
            $notification->users()->attach($userId);
        }
    }
}
if (!function_exists('getDefaultViewRoute')) {
    /**
     * Get the default view route for a given entity (projects or tasks).
     *
     * @param string $entity
     * @return string
     */
    function getDefaultViewRoute($entity)
    {
        $defaultView = getUserPreferences($entity, 'default_view');
        $routes = [
            'projects' => [
                'list' => 'projects.list_view',
                'grid' => 'projects.index',
                'kanban_view' => 'projects.kanban_view',
            ],
            'tasks' => [
                'tasks/draggable' => 'tasks.draggable',
                'tasks/calendar-view' => 'tasks.calendar_view',
                'tasks/group-by-task-list' => 'tasks.groupByTaskList',
                'default' => 'tasks.index',
            ],
        ];
        return route($routes[$entity][$defaultView] ?? $routes[$entity]['default'] ?? 'projects.index');
    }
}

// Function for sending reminders for tasks or birthday or work anniversary
if (!function_exists('sendReminderNotification')) {


    /**
     * Sends reminder notifications to the given recipients based on the given data.
     *
     * @param array $data The reminder data, must contain the type of reminder.
     * @param array $recipients The recipients of the notification, must contain the user or client IDs.
     * @return void
     */
    function sendReminderNotification($data, $recipients)
    {
        Log::info('Sending reminder notification to: ' . json_encode($recipients, JSON_PRETTY_PRINT) . 'With data: ' . json_encode($data, JSON_PRETTY_PRINT));
        if (empty($recipients)) {
            return;
        }

        // Define notification types
        $notificationTypes = ['task_reminder', 'project_reminder', 'leave_request_reminder', 'recurring_task'];
        Log::debug('Checking notification type', ['type' => $data['type'], 'valid_types' => $notificationTypes]);
        // Get notification template based on the type
        $template = getNotificationTemplate($data['type'], 'system');
        if (!$template || $template->status !== 0) {
            $notification = createNotification($data);
        }

        // Process each recipient
        foreach (array_unique($recipients) as $recipient) {
            Log::info('Processing recipient', ['recipient_id' => $recipient]);
            $recipientModel = getRecipientModel($recipient);
            if ($recipientModel) {
                Log::debug('Found recipient model', [
                    'recipient_type' => get_class($recipientModel),
                    'recipient_id' => $recipientModel->id
                ]);
                handleRecipientNotification($recipientModel, $notification, $template, $data, $notificationTypes);
            }
        }
    }

    /**
     * Creates a new notification from the given data.
     *
     * @param array $data An associative array containing the notification details,
     *                    including the 'type', 'type_id', and 'action'.
     * @return \App\Models\Notification The newly created notification instance.
     */
    function createNotification($data)
    {
        return Notification::create(
            [
                'workspace_id' => $data['workspace_id'],
                'from_id' => $data['from_id'],
                'type' => $data['type'],
                'type_id' => $data['type_id'],
                'action' => $data['action'],
                'title' => getTitle($data),
                'message' => get_message($data, null, 'system'),
            ]
        );
    }

    /**
     * Given a recipient identifier, returns the corresponding model instance.
     *
     * A recipient identifier is a string that starts with either 'u_' for a user or
     * 'c_' for a client, followed by the numeric identifier of the user or client.
     * For example, 'u_1' refers to a user with identifier 1, and 'c_2' refers to a
     * client with identifier 2.
     *
     * @param string $recipient The recipient identifier.
     * @return \App\Models\User|\App\Models\Client|null The recipient model instance, or null if not found.
     */
    function getRecipientModel($recipient)
    {
        $recipientId = substr($recipient, 2);
        if (substr($recipient, 0, 2) === 'u_') {
            return User::find($recipientId);
        } elseif (substr($recipient, 0, 2) === 'c_') {
            return Client::find($recipientId);
        }
        return null;
    }

    /**
     * Handles a notification for a recipient based on their notification preferences.
     *
     * This function takes a recipient model, a notification, a template, data about the
     * notification, and an array of notification types. It checks the recipient's
     * preferences for the notification types and sends notifications accordingly.
     * If the notification is already attached to the recipient, it will not be attached again.
     *
     * @param mixed $recipientModel The recipient model to send the notification to.
     * @param mixed $notification The notification to be sent.
     * @param mixed $template The template to use for the notification.
     * @param array $data An associative array containing details about the notification.
     * @param array $notificationTypes An array of notification types to check for.
     */
    function handleRecipientNotification($recipientModel, $notification, $template, $data, $notificationTypes)
    {
        Log::info('Handling recipient notification', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type']
        ]);
        $enabledNotifications = getUserPreferences('notification_preference', 'enabled_notifications', 'u_' . $recipientModel->id);

        // Attach the notification to the recipient
        attachNotificationIfNeeded($recipientModel, $notification, $template, $enabledNotifications, $data);
        Log::info('Starting notification delivery process', [
            'recipient_id' => $recipientModel->id,
            'notification_types' => $notificationTypes,
            'enabled_notifications' => $enabledNotifications
        ]);
        // Send notifications based on preferences
        sendEmailIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
        sendSMSIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
        sendWhatsAppIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
        sendSlackIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes);
    }

    /**
     * Attach a notification to the recipient if the recipient has enabled system notifications for the given type
     * of notification and the notification template is not found or is not enabled.
     *
     * @param mixed $recipientModel The recipient model (User or Client) to which the notification should be attached.
     * @param Notification $notification The notification to be attached to the recipient.
     * @param Template $template The notification template to be checked for enabled status.
     * @param array $enabledNotifications An array of enabled notification types for the recipient.
     * @param array $data The data for the notification, including the type of notification.
     */
    function attachNotificationIfNeeded($recipientModel, $notification, $template, $enabledNotifications, $data)
    {
        Log::debug('Checking if notification needs to be attached', [
            'recipient_id' => $recipientModel->id,
            'notification_id' => $notification ? $notification->id : null,
            'template_exists' => (bool) $template,
            'template_status' => $template ? $template->status : null
        ]);
        if (!$template || $template->status !== 0) {
            if (is_array($enabledNotifications) && (empty($enabledNotifications) || in_array('system_' . $data['type'], $enabledNotifications))) {
                $recipientModel->notifications()->attach($notification->id);
            }
        }
    }

    /**
     * Send an email notification if the recipient has enabled email notifications for the given type of notification.
     *
     * @param mixed $recipientModel The recipient model (User or Client) to which the notification should be sent.
     * @param array $enabledNotifications An array of enabled notification types for the recipient.
     * @param array $data The notification data.
     * @param array $notificationTypes An array of notification types for which email notifications should be sent.
     * @return void
     */
    function sendEmailIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {

        Log::debug('Checking email notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'email_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'email_' . $data['type'])) {
            try {
                sendEmailNotification($recipientModel, $data);
            } catch (\Exception $e) {
                Log::error('Email Notification Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send SMS notification if enabled.
     *
     * This function sends an SMS notification to the given recipient if the
     * notification type is enabled in the recipient's preferences.
     *
     * @param  \App\Models\User|\App\Models\Client  $recipientModel
     * @param  array  $enabledNotifications
     * @param  array  $data
     * @param  array  $notificationTypes
     * @return void
     */
    function sendSMSIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking SMS notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'sms_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'sms_' . $data['type'])) {
            try {
                sendSMSNotification($data, $recipientModel);
            } catch (\Exception $e) {
                Log::error('SMS Notification Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send WhatsApp notification if enabled.
     *
     * This function sends a WhatsApp notification to the given recipient if the
     * notification type is enabled in the recipient's preferences.
     *
     * @param  \App\Models\User|\App\Models\Client  $recipientModel
     * @param  array  $enabledNotifications
     * @param  array  $data
     * @param  array  $notificationTypes
     * @return void
     */
    function sendWhatsAppIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking WhatsApp notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'whatsapp_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'whatsapp_' . $data['type'])) {
            try {
                sendWhatsAppNotification($data, $recipientModel);
            } catch (\Exception $e) {
                Log::error('WhatsApp Notification Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send a Slack notification if the recipient has enabled Slack notifications for the given type.
     *
     * @param User|Client $recipientModel The recipient model to send the notification to.
     * @param array $enabledNotifications An array of enabled notification types.
     * @param array $data An associative array containing the notification details,
     *                    including the 'type', 'type_id', and 'action'.
     * @param array $notificationTypes An array of notification types.
     */
    function sendSlackIfEnabled($recipientModel, $enabledNotifications, $data, $notificationTypes)
    {
        Log::debug('Checking Slack notification preferences', [
            'recipient_id' => $recipientModel->id,
            'notification_type' => $data['type'],
            'is_type_valid' => in_array($data['type'], $notificationTypes),
            'is_enabled' => isNotificationEnabled($enabledNotifications, 'slack_' . $data['type'])
        ]);
        if (in_array($data['type'], $notificationTypes) && isNotificationEnabled($enabledNotifications, 'slack_' . $data['type'])) {
            try {
                sendSlackNotification($data, $recipientModel);
            } catch (\Exception $e) {
                Log::error('Slack Notification Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if a notification type is enabled for a user/client.
     *
     * @param array $enabledNotifications An array of enabled notification types.
     * @param string $type The notification type to check.
     * @return bool True if the notification type is enabled.
     */
    function isNotificationEnabled($enabledNotifications, $type)
    {

        return is_array($enabledNotifications) && (empty($enabledNotifications) || in_array($type, $enabledNotifications));
    }
}
if (!function_exists('getDefaultStatus')) {
    /**
     * Get the default status ID based on the given status name.
     *
     * @param string $statusName
     * @return object|null
     */
    function getDefaultStatus(string $statusName): ?object
    {
        // Fetch the default status using the Statuses model
        $status = Status::where('title', $statusName)
            ->where('is_default', 1) // Assuming there's an 'is_default' column
            ->first();

        // Return the ID if found, or null
        return $status ? $status : null;
    }
}
if (!function_exists('getWorkspaceId')) {
    function getWorkspaceId()
    {
        $workspaceId = 0;
        $authenticatedUser = getAuthenticatedUser();

        if ($authenticatedUser) {
            if (session()->has('workspace_id')) {
                $workspaceId = session('workspace_id'); // Retrieve workspace_id from session
            } else {
                $workspaceId = request()->header('workspace_id');
            }
        }
        return $workspaceId;
    }
}
if (!function_exists('getGuardName')) {
    function getGuardName()
    {
        static $guardName = null;

        // If the guard name is already determined, return it
        if ($guardName !== null) {
            return $guardName;
        }

        // Check the 'web' guard (users)
        if (Auth::guard('web')->check()) {
            $guardName = 'web';
        }
        // Check the 'client' guard (clients)
        elseif (Auth::guard('client')->check()) {
            $guardName = 'client';
        }
        // Check the 'sanctum' guard (API tokens)
        elseif (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            // Determine if the sanctum user is a user or a client
            if ($user instanceof \App\Models\User) {
                $guardName = 'web';
            } elseif ($user instanceof \App\Models\Client) {
                $guardName = 'client';
            }
        }

        return $guardName;
    }
}
if (!function_exists('getMenus')) {
    function getMenus()
    {
        $user = getAuthenticatedUser();
        $current_workspace_id = getWorkspaceId();
        $messenger = new ChatifyMessenger();
        $unread = $messenger->totalUnseenMessages();
        $pending_todos_count = $user->todos(0)->count();
        $ongoing_meetings_count = $user->meetings('ongoing')->count();
        $query = LeaveRequest::where('status', 'pending')
            ->where('workspace_id', $current_workspace_id);
        if (!is_admin_or_leave_editor()) {
            $query->where('user_id', $user->id);
        }
        $pendingLeaveRequestsCount = $query->count();
        return [
            [
                'id' => 'dashboard',
                'label' => get_label('dashboard', 'Dashboard'),
                'url' => route('home.index'),
                'icon' => 'bx bx-home-circle',
                'class' => 'menu-item' . (Request::is('master-panel/home') ? ' active' : '')
            ],
            [
                'id' => 'projects',
                'label' => get_label('projects', 'Projects'),
                'url' => route('projects.index'),
                'icon' => 'bx bx-briefcase-alt-2',
                'class' => 'menu-item' . (Request::is('master-panel/projects') || Request::is('master-panel/tags/*') || Request::is('master-panel/projects/*') || Request::is('master-panel/task-lists') ? ' active open' : ''),
                'show' => ($user->can('manage_projects') || $user->can('manage_tags')) ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'manage_projects',
                        'label' => get_label('manage_projects', 'Manage projects'),
                        'url' => getDefaultViewRoute('projects'),
                        'class' => 'menu-item' . (Request::is('master-panel/projects') || (Request::is('master-panel/projects/*') && !Request::is('master-panel/projects/*/favorite') && !Request::is('master-panel/projects/favorite')) ? ' active' : ''),
                        'show' => ($user->can('manage_projects')) ? 1 : 0
                    ],
                    [
                        'id' => 'favorite_projects',
                        'label' => get_label('favorite_projects', 'Favorite projects'),
                        'url' => route('projects.index', ['type' => 'favorite']),
                        'class' => 'menu-item' . (Request::is('master-panel/projects/favorite') || Request::is('master-panel/projects/list/favorite') || Request::is('master-panel/projects/kanban/favorite') ? ' active' : ''),
                        'show' => ($user->can('manage_projects')) ? 1 : 0
                    ],

                    [
                        'id' => 'tags',
                        'label' => get_label('tags', 'Tags'),
                        'url' => route('tags.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/tags/*') ? ' active' : ''),
                        'show' => ($user->can('manage_tags')) ? 1 : 0
                    ],
                    [
                        'id' => 'task_lists',
                        'label' => get_label('task_lists', 'Task Lists'),
                        'url' => route('task_lists.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/task-lists') || Request::is('master-panel/task-lists/*') ? ' active' : ''),
                        'show' => ($user->can('manage_tasks')) ? 1 : 0
                    ]
                ],
            ],
            [
                'id' => 'tasks',
                'label' => get_label('tasks', 'Tasks'),
                'url' => getDefaultViewRoute('tasks'),
                'icon' => 'bx bx-task',
                'class' => 'menu-item' . (Request::is('master-panel/tasks') || Request::is('master-panel/tasks/*') ? ' active' : ''),
                'show' => $user->can('manage_tasks') ? 1 : 0,

            ],
            [
                'id' => 'statuses',
                'label' => get_label('statuses', 'Statuses'),
                'url' => route('status.index'),
                'icon' => 'bx bx-grid-small',
                'class' => 'menu-item' . (Request::is('master-panel/status/manage') ? ' active' : ''),
                'show' => $user->can('manage_statuses') ? 1 : 0
            ],
            [
                'id' => 'priorities',
                'label' => get_label('priorities', 'Priorities'),
                'url' => route('priority.manage'),
                'icon' => 'bx bx-up-arrow-alt ',
                'class' => 'menu-item' . (Request::is('master-panel/priority/manage') ? ' active' : ''),
                'show' => $user->can('manage_priorities') ? 1 : 0
            ],
            [
                'id' => 'workspaces',
                'label' => get_label('workspaces', 'Workspaces'),
                'url' => route('workspaces.index'),
                'icon' => 'bx bx-check-square',
                'class' => 'menu-item' . (Request::is('master-panel/workspaces') || Request::is('master-panel/workspaces/*') ? ' active' : ''),
                'show' => $user->can('manage_workspaces') ? 1 : 0
            ],
            [
                'id' => 'chat',
                'label' => get_label('chat', 'Chat'),
                'url' => url('chat'),
                'icon' => 'bx bx-chat',
                'class' => 'menu-item' . (Request::is('chat') || Request::is('chat/*') ? ' active' : ''),
                'badge' => ($unread > 0) ? '<span class="flex-shrink-0 badge badge-center bg-danger w-px-20 h-px-20">' . $unread . '</span>' : '',
                'show' => Auth::guard('web')->check() ? 1 : 0
            ],
            [
    'id' => 'training',
    'label' => 'Training',
    'icon' => 'bx bx-slideshow',
    'url' => '', // No main URL; just opens submenu
    'class' => 'menu-item',
    'show' => 1,
    'submenus' => [
        [
            'id' => 'create-training',
            'label' => 'Create Training',
            'url' => route('training.create'),
            'class' => 'menu-item',
            'show' => 1,
        ],
        [
            'id' => 'list-training',
            'label' => 'Training List',
            'url' => route('training.index'),
            'class' => 'menu-item',
            'show' => 1,
        ],
        // Optional: Training Report
        // [
        //     'id' => 'report-training',
        //     'label' => 'Training Report',
        //     'url' => route('training.report'),
        //     'class' => 'menu-item',
        //     'show' => 1,
        // ],
    ],
    
],
            [
                'id' => 'todos',
                'label' => get_label('todos', 'Todos'),
                'url' => route('todos.index'),
                'icon' => 'bx bx-list-check',
                'class' => 'menu-item' . (Request::is('master-panel/todos') || Request::is('master-panel/todos/*') ? ' active' : ''),
                'badge' => ($pending_todos_count > 0) ? '<span class="flex-shrink-0 badge badge-center bg-danger w-px-20 h-px-20">' . $pending_todos_count . '</span>' : ''
            ],
            [
                'id' => 'meetings',
                'label' => get_label('meetings', 'Meetings'),
                'url' => route('meetings.index'),
                'icon' => 'bx bx-shape-polygon',
                'class' => 'menu-item' . (Request::is('master-panel/meetings') || Request::is('master-panel/meetings/*') ? ' active' : ''),
                'badge' => ($ongoing_meetings_count > 0) ? '<span class="flex-shrink-0 badge badge-center bg-success w-px-20 h-px-20">' . $ongoing_meetings_count . '</span>' : '',
                'show' => $user->can('manage_meetings') ? 1 : 0
            ],
            [
                'id' => 'users',
                'label' => get_label('users', 'Users'),
                'url' => route('users.index'),
                'icon' => 'bx bx-group',
                'class' => 'menu-item' . (Request::is('master-panel/users') || Request::is('master-panel/users/*') ? ' active' : ''),
                'show' => $user->can('manage_users') ? 1 : 0
            ],
            [
                'id' => 'clients',
                'label' => get_label('clients', 'Clients'),
                'url' => route('clients.index'),
                'icon' => 'bx bx-group',
                'class' => 'menu-item' . (Request::is('master-panel/clients') || Request::is('master-panel/clients/*') ? ' active' : ''),
                'show' => $user->can('manage_clients') ? 1 : 0
            ],
            [
                'id' => 'contracts',
                'label' => get_label('contracts', 'Contracts'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-news',
                'class' => 'menu-item' . (Request::is('master-panel/contracts') || Request::is('master-panel/contracts/*') ? ' active open' : ''),
                'show' => ($user->can('manage_contracts') || $user->can('manage_contract_types')) ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'manage_contracts',
                        'label' => get_label('manage_contracts', 'Manage contracts'),
                        'url' => route('contracts.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/contracts') ? ' active' : ''),
                        'show' => $user->can('manage_contracts') ? 1 : 0
                    ],
                    [
                        'id' => 'contract_types',
                        'label' => get_label('contract_types', 'Contract types'),
                        'url' => route('contracts.contract_types'),
                        'class' => 'menu-item' . (Request::is('master-panel/contracts/contract-types') ? ' active' : ''),
                        'show' => $user->can('manage_contract_types') ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'payslips',
                'label' => get_label('payslips', 'Payslips'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-box',
                'class' => 'menu-item' . (Request::is('master-panel/payslips') || Request::is('master-panel/payslips/*') || Request::is('master-panel/allowances') || Request::is('master-panel/deductions') ? ' active open' : ''),
                'show' => ($user->can('manage_payslips') || $user->can('manage_allowances') || $user->can('manage_deductions')) ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'manage_payslips',
                        'label' => get_label('manage_payslips', 'Manage payslips'),
                        'url' => route('payslips.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/payslips') || Request::is('master-panel/payslips/*') ? ' active' : ''),
                        'show' => $user->can('manage_payslips') ? 1 : 0
                    ],
                    [
                        'id' => 'allowances',
                        'label' => get_label('allowances', 'Allowances'),
                        'url' => route('allowances.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/allowances') ? ' active' : ''),
                        'show' => $user->can('manage_allowances') ? 1 : 0
                    ],
                    [
                        'id' => 'deductions',
                        'label' => get_label('deductions', 'Deductions'),
                        'url' => route('deductions.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/deductions') ? ' active' : ''),
                        'show' => $user->can('manage_deductions') ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'finance',
                'label' => get_label('finance', 'Finance'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-box',
                'class' => 'menu-item' . (Request::is('master-panel/estimates-invoices') || Request::is('master-panel/estimates-invoices/*') || Request::is('master-panel/taxes') || Request::is('master-panel/payment-methods') || Request::is('master-panel/payments') || Request::is('master-panel/units') || Request::is('master-panel/items') || Request::is('master-panel/expenses') || Request::is('master-panel/expenses/*') ? ' active open' : ''),
                'show' => ($user->can('manage_estimates_invoices') || $user->can('manage_expenses') || $user->can('manage_payment_methods') ||
                    $user->can('manage_expense_types') || $user->can('manage_payments') || $user->can('manage_taxes') ||
                    $user->can('manage_units') || $user->can('manage_items')) ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'expenses',
                        'label' => get_label('expenses', 'Expenses'),
                        'url' => route('expenses.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/expenses') ? ' active' : ''),
                        'show' => $user->can('manage_expenses') ? 1 : 0
                    ],
                    [
                        'id' => 'expense_types',
                        'label' => get_label('expense_types', 'Expense types'),
                        'url' => route('expenses-type.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/expenses/expense-types') ? ' active' : ''),
                        'show' => $user->can('manage_expense_types') ? 1 : 0
                    ],
                    [
                        'id' => 'estimates_invoices',
                        'label' => get_label('estimates_invoices', 'Estimates/Invoices'),
                        'url' => route('estimates-invoices.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/estimates-invoices') || Request::is('master-panel/estimates-invoices/*') ? ' active' : ''),
                        'show' => $user->can('manage_estimates_invoices') ? 1 : 0
                    ],
                    [
                        'id' => 'payments',
                        'label' => get_label('payments', 'Payments'),
                        'url' => route('payments.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/payments') ? ' active' : ''),
                        'show' => $user->can('manage_payments') ? 1 : 0
                    ],
                    [
                        'id' => 'payment_methods',
                        'label' => get_label('payment_methods', 'Payment methods'),
                        'url' => route('paymentMethods.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/payment-methods') ? ' active' : ''),
                        'show' => $user->can('manage_payment_methods') ? 1 : 0
                    ],
                    [
                        'id' => 'taxes',
                        'label' => get_label('taxes', 'Taxes'),
                        'url' => route('taxes.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/taxes') ? ' active' : ''),
                        'show' => $user->can('manage_taxes') ? 1 : 0
                    ],
                    [
                        'id' => 'units',
                        'label' => get_label('units', 'Units'),
                        'url' => route('units.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/units') ? ' active' : ''),
                        'show' => $user->can('manage_units') ? 1 : 0
                    ],
                    [
                        'id' => 'items',
                        'label' => get_label('items', 'Items'),
                        'url' => route('items.index'),
                        'class' => 'menu-item' . (Request::is('master-panel/items') ? ' active' : ''),
                        'show' => $user->can('manage_items') ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'reports',
                'label' => get_label('reports', 'Reports'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-file',
                'class' => 'menu-item' . (Request::is('master-panel/reports') || Request::is('master-panel/reports/*') ? ' active open' : ''),
                'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'projects_report',
                        'label' => get_label('projects', 'Projects'),
                        'url' => route('reports.projects-report'),
                        'class' => 'menu-item' . (Request::is('master-panel/reports/projects-report') ? ' active' : ''),
                        'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                    ],
                    [
                        'id' => 'tasks_report',
                        'label' => get_label('tasks', 'Tasks'),
                        'url' => route('reports.tasks-report'),
                        'class' => 'menu-item' . (Request::is('master-panel/reports/tasks-report') ? ' active' : ''),
                        'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                    ],
                    [
                        'id' => 'invoices_report',
                        'label' => get_label('invoices', 'Invoices'),
                        'url' => route('reports.invoices-report'),
                        'class' => 'menu-item' . (Request::is('master-panel/reports/invoices-report') ? ' active' : ''),
                        'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                    ],
                    [
                        'id' => 'income_vs_expense',
                        'label' => get_label('income_vs_expense', 'Income vs Expense'),
                        'url' => route('reports.income-vs-expense-report'),
                        'class' => 'menu-item' . (Request::is('master-panel/reports/income-vs-expense-report') ? ' active' : ''),
                        'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                    ],
                    [
                        'id' => 'leaves',
                        'label' => get_label('leaves', 'Leaves'),
                        'url' => route('reports.leaves-report'),
                        'class' => 'menu-item' . (Request::is('master-panel/reports/leaves-report') ? ' active' : ''),
                        'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                    ],
                    [
                        'id' => 'work_hours',
                        'label' => get_label('work_hours_report', 'Work Hours Report'),
                        'url' => route('reports.work-hours-report'),
                        'class' => 'menu-item' . (Request::is('master-panel/reports/work-hours-report') ? ' active' : ''),
                        'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                    ]
                ],
            ],
            [
                'id' => 'notes',
                'label' => get_label('notes', 'Notes'),
                'url' => route('notes.index'),
                'icon' => 'bx bx-notepad',
                'class' => 'menu-item' . (Request::is('master-panel/notes') || Request::is('master-panel/notes/*') ? ' active' : '')
            ],
            [
                'id' => 'leave_requests',
                'label' => get_label('leave_requests', 'Leave requests'),
                'url' => route('leave_requests.index'),
                'icon' => 'bx bx-right-arrow-alt',
                'class' => 'menu-item' . (Request::is('master-panel/leave-requests') || Request::is('master-panel/leave-requests/*') ? ' active' : ''),
                'badge' => ($pendingLeaveRequestsCount > 0) ? '<span class="flex-shrink-0 badge badge-center bg-danger w-px-20 h-px-20">' . $pendingLeaveRequestsCount . '</span>' : '',
                'show' => Auth::guard('web')->check() ? 1 : 0
            ],
            [
                'id' => 'activity_log',
                'label' => get_label('activity_log', 'Activity log'),
                'url' => route('activity_log.index'),
                'icon' => 'bx bx-line-chart',
                'class' => 'menu-item' . (Request::is('master-panel/activity-log') || Request::is('master-panel/activity-log/*') ? ' active' : ''),
                'show' => $user->can('manage_activity_log') ? 1 : 0
            ],
            [
                'id' => 'subscription_plan',
                'label' => get_label('subscription_plan', 'Subscription Plan'),
                'url' => route('subscription-plan.index'),
                'icon' => 'bx bx-task',
                'class' => 'menu-item' . (Request::is('master-panel/subscription-plan') || Request::is('master-panel/subscription-plan/*') ? ' active' : ''),
                'show' => $user->hasRole('admin') ? 1 : 0
            ],
            [
                'id' => 'settings',
                'label' => get_label('settings', 'Settings'),
                'icon' => 'bx bx-cog',
                'class' => 'menu-item' . (Request::is('master-panel/settings') || Request::is('master-panel/settings/*') ? ' active open' : ''),
                'show' => $user->hasRole('admin') ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'admin_settings',
                        'label' => get_label('admin_settings', 'Admin Settings'),
                        'class' => 'menu-item' . (Request::is('master-panel/settings') ? ' active' : ''),
                        'show' => $user->hasRole('admin') ? 1 : 0,
                        'url' => route('admin_settings.index'),
                    ],
                    [
                        'id' => 'whatsapp_notification_settings',
                        'label' => get_label('whatsapp_notifications_settings', 'Whatsapp Notifications Settings'),
                        'class' => 'menu-item' . (Request::is('master-panel/settings/whatsapp-notifications-settings') ? ' active' : ''),
                        'show' => $user->hasRole('admin') ? 1 : 0,
                        'url' => route('admin_whatsapp_notifications_settings.index'), // Or use url('...') if route is not defined
                    ]
                ]
            ]

        ];
    }
}
