<?php
namespace App\Http\Controllers;
use Throwable;
use App\Models\Task;
use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Project;
use App\Models\TaskUser;
use App\Models\Template;
use App\Models\Workspace;
use App\Models\TeamMember;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use GuzzleHttp\Promise\TaskQueue;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Notifications\AccountCreation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role as ContractsRole;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workspace = Workspace::find(session()->get('workspace_id'));
        $users = $workspace->users;
        $roles = Role::where('guard_name', 'web')
            ->whereNotIn('name', ['superadmin', 'admin', 'manager'])
        ->get();

        return view('users.users', ['users' => $users, 'roles' => $roles]);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'web')
        ->whereNotIn('name', ['superadmin', 'admin', 'manager'])
        ->get();
        return view('users.create_user', ['roles' => $roles]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        // dd($request);
        $adminId = null;
        if (Auth::guard('web')->check() && Auth::user()->hasRole('admin')) {
            $admin = Admin::where('user_id', Auth::user()->id)->first();
            if ($admin) {
                $adminId = $admin->id;
            }
        }
        ini_set('max_execution_time', 300);
        $formFields = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
            'address' => 'nullable',
            'phone' => 'nullable',
            'country_code' => 'nullable',
            'city' => 'nullable',
            'state' => 'nullable',
            'country' => 'nullable',
            'zip' => 'nullable',
            'dob' => 'nullable',
            'doj' => 'nullable',
            'role' => 'required',
            'country_iso_code' => 'nullable','profile' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'], // max:2048 = 2MB
        ]);
        $validator = Validator($formFields)->after(function ($validator) use ($request) {
            $email = $request->input('email');

            $existsInUsers = DB::table('users')->where('email', $email)->exists();
            $existsInClients = DB::table('clients')->where('email', $email)->exists();

            if ($existsInUsers || $existsInClients) {
                $validator->errors()->add('email', 'The email has already been taken in users or clients.');
            }
        });
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $workspace = Workspace::find(session()->get('workspace_id'));
        if ($request->input('dob')) {
            $dob = $request->input('dob');
            $formFields['dob'] = format_date($dob, false, app('php_date_format'), 'Y-m-d');
        }
        if ($request->input('doj')) {
            $doj = $request->input('doj');
            $formFields['doj'] = format_date($doj, false, app('php_date_format'), 'Y-m-d');
        }
        $password = $request->input('password');
        $formFields['password'] = bcrypt($password);
        if ($request->hasFile('profile')) {
            $formFields['photo'] = $request->file('profile')->store('photos', 'public');
        } else {
            $formFields['photo'] = 'photos/no-image.jpg';
        }
        // $require_ev = isAdminOrHasAllDataAccess() && $request->has('require_ev') && $request->input('require_ev') == 0 ? 0 : 1;
        // $status = getAuthenticatedUser()->hasRole('admin') && $request->has('status') && $request->input('status') == 1 ? 1 : 0;
        // if ($status == 1) {
        //     $formFields['status'] = 1;
        //     $formFields['email_verified_at'] = now()->tz(config('app.timezone'));
        // }
        $require_ev = isAdminOrHasAllDataAccess() && $request->has('require_ev') && $request->input('require_ev') == 0 ? 0 : 1;
            $status = isAdminOrHasAllDataAccess() && $request->has('status') && $request->input('status') == 1 ? 1 : 0;
            $formFields['email_verified_at'] = $require_ev == 0 ? now()->tz(config('app.timezone')) : null;
            $formFields['status'] = $status;
        $user = User::create($formFields);
        TeamMember::create([
            'admin_id' => $adminId,
            'user_id' => $user->id,
        ]);
        try {
            if ($require_ev == 1) {
                $user->notify(new VerifyEmail($user));
            }
            $workspace->users()->attach($user->id);
            $user->assignRole($request->input('role'));
            if (isEmailConfigured()) {
                $account_creation_template = Template::where('type', 'email')
                    ->where('name', 'account_creation')
                    ->first();
                if (!$account_creation_template || ($account_creation_template->status !== 0)) {
                    $user->notify(new AccountCreation($user, $password));
                }
            }
            Session::flash('message', 'User created successfully.');
            return response()->json(['error' => false, 'id' => $user->id]);
        } catch (TransportExceptionInterface $e) {
            $user = User::findOrFail($user->id);
            $user->delete();
            return response()->json(['error' => true, 'message' => 'User TransportException couldn\'t be created, please check email settings.']);
        } catch (Throwable $e) {
            // Catch any other throwable, including non-Exception errors
            $user = User::findOrFail($user->id);
            $user->delete();
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }
    public function email_verification()
    {
        $user = getAuthenticatedUser();
        if (!$user->hasVerifiedEmail()) {
            return view('auth.verification-notice');
        } else {
            return redirect(route('home.index'));
        }
    }
    public function resend_verification_link(Request $request)
    {
        if (isEmailConfigured()) {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('message', 'Verification link sent.');
        } else {
            return back()->with('error', 'Verification link couldn\'t sent.');
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit_user($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::where('guard_name', 'web')
            ->whereNotIn('name', ['superadmin', 'manager'])
        ->get();
        return view('users.edit_user', ['user' => $user, 'roles' => $roles]);
    }
    public function update_user(Request $request, $id)
    {

        $formFields = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'phone' => 'nullable',
            'country_code' => 'nullable',
            'address' => 'nullable',
            'city' => 'nullable',
            'state' => 'nullable',
            'country' => 'nullable',
            'zip' => 'nullable',
            'dob' => 'nullable',
            'doj' => 'nullable',
            'password' => 'nullable|min:6','password_confirmation' => 'required_with:password|same:password',
            'country_iso_code' => 'nullable',
            'upload' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'], // max:2048 = 2MB
        ]);

        $user = User::findOrFail($id);
        if ($request->input('dob')) {
            $dob = $request->input('dob');
            $formFields['dob'] = format_date($dob, false, app('php_date_format'), 'Y-m-d');
        }
        if ($request->input('doj')) {
            $doj = $request->input('doj');
            $formFields['doj'] = format_date($doj, false, app('php_date_format'), 'Y-m-d');
        }
        if ($request->hasFile('upload')) {
            if ($user->photo != 'photos/no-image.jpg' && $user->photo !== null)
                Storage::disk('public')->delete($user->photo);

            $formFields['photo'] = $request->file('upload')->store('photos', 'public');
        }

        $status = isAdminOrHasAllDataAccess() && $request->has('status') ? $request->input('status') : $user->status;
        $formFields['status'] = $status;

        if (isAdminOrHasAllDataAccess() && isset($formFields['password']) && !empty($formFields['password'])) {
            $formFields['password'] = bcrypt($formFields['password']);
        } else {
            unset($formFields['password']);
        }

        if (!($user->hasRole('admin'))) {
            $user->syncRoles($request->input('role'));
        }


        $user->update($formFields);

        Session::flash('message', 'Profile details updated successfully.');
        return response()->json(['error' => false, 'id' => $user->id]);
    }
    public function update_photo(Request $request, $id)
    {
        if ($request->hasFile('upload')) {
            $old = User::findOrFail($id);
            if ($old->photo != 'photos/no-image.jpg' && $old->photo !== null)
                Storage::disk('public')->delete($old->photo);
            $formFields['photo'] = $request->file('upload')->store('photos', 'public');
            User::findOrFail($id)->update($formFields);
            return back()->with('message', 'Profile picture updated successfully.');
        } else {
            return back()->with('error', 'No profile picture selected.');
        }
    }
    public function delete_user($id)
    {
        $user = User::findOrFail($id);
        $response = DeletionService::delete(User::class, $id, 'User');
        $user->todos()->delete();
        return $response;
    }
    public function delete_multiple_user(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:users,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $deletedUsers = [];
        $deletedUserNames = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $user = User::findOrFail($id);
            if ($user) {
                $deletedUsers[] = $id;
                $deletedUserNames[] = $user->first_name . ' ' . $user->last_name;
                DeletionService::delete(User::class, $id, 'User');
                $user->todos()->delete();
            }
        }
        return response()->json(['error' => false, 'message' => 'User(s) deleted successfully.', 'id' => $deletedUsers, 'titles' => $deletedUserNames]);
    }
    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            auth('web')->logout();
        } else {
            auth('client')->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('message', 'Logged out successfully.');
    }
    public function login()
    {
        return view('front-end.login');
        // return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => 'required'
        ]);

        $maxLoginAttempts = (int) $this->getSetting('max_login_attempts', 5);
        $decayTime = (int) $this->getSetting('time_decay', 1) * 60;
        $throttleKey = Str::lower($credentials['email']) . '|' . $request->ip();

        if ($maxLoginAttempts > 0 && $this->hasTooManyLoginAttempts($throttleKey, $maxLoginAttempts)) {
            return $this->sendLockoutResponse($throttleKey);
        }

        $account = $this->findAccount($credentials['email']);

        if (!$account) {
            return $this->handleFailedLogin($throttleKey, $maxLoginAttempts, $decayTime);
        }

        $loginAttempt = $this->attemptLogin($account, $credentials['password']);

        if ($loginAttempt === true) {
            return $this->sendSuccessResponse($request, $account);
        } elseif (is_array($loginAttempt) && isset($loginAttempt['error'])) {
            return response()->json($loginAttempt);
        }

        return $this->handleFailedLogin($throttleKey, $maxLoginAttempts, $decayTime);
    }


    protected function findAccount(string $email)
    {
        return User::where('email', $email)->first() ?? Client::where('email', $email)->first();
    }

    protected function attemptLogin($account, string $password)
    {
        if (!Hash::check($password, $account->password)) {
            return false;
        }

        // If the account is a User
        if ($account instanceof User) {
            // Check if the user is an admin or if the account is active (status = 1)
            if ($account->hasRole('admin') || $account->status == 1) {
                return true;
            } else {
                // Return a custom error message if the status is not 1
                return ['error' => true, 'message' => get_label('status_not_active', 'Your account is currently inactive. Please contact admin for assistance.')];
            }
        }

        // If the account is a Client
        if ($account instanceof Client) {
            if ($account->status == 1) {
                return true;
            } else {
                // Return a custom error message if the status is not 1
                return ['error' => true, 'message' => get_label('status_not_active', 'Your client account is currently inactive. Please contact admin for assistance.')];
            }
        }

        return false;
    }


    protected function sendSuccessResponse(Request $request, $account)
    {
        RateLimiter::clear($account->email . '|' . $request->ip());

        $guard = $account instanceof User ? 'web' : 'client';
        auth($guard)->login($account);

        $workspace_id = $account->workspaces->first()->id ?? 0;
        $locale = $account->lang ?? 'en';
        $account_type = $account instanceof User ? 'user' : 'client';

        session()->put([
            'user_id' => $account->id,
            'workspace_id' => $workspace_id,
            'my_locale' => $locale,
            'locale' => $locale,
            'account_type' => $account_type
        ]);

        $request->session()->regenerate();
        Session::flash('message', 'Logged in successfully.');

        return response()->json([
            'error' => false,
            'message' => 'Logged in successfully',
            'redirect_url' => $request->redirect_url,
            'account_type' => $account_type
        ]);
    }


    /**
     * Handle a failed login attempt.
     *
     * @param string $throttleKey The RateLimiter key to throttle the login attempts.
     * @param int $maxLoginAttempts The maximum number of login attempts to allow.
     * @param int $decayTime The number of seconds to delay the next login attempt.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the error message.
     */

    protected function handleFailedLogin($throttleKey, $maxLoginAttempts, $decayTime)
    {
        if ($maxLoginAttempts > 0) {
            RateLimiter::hit($throttleKey, $decayTime);
        }

        return response()->json([
            'error' => true,
            'message' => 'Invalid credentials!'
        ]);
    }


    /**
     * Check if the given key has exceeded the maximum allowed login attempts.
     *
     * @param string $key The key to check for rate limiting.
     * @param int $maxAttempts The maximum allowed login attempts.
     *
     * @return bool Whether the given key has exceeded the maximum allowed login attempts.
     */

    protected function hasTooManyLoginAttempts($key, $maxAttempts)
    {
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }


    /**
     * Sends a JSON response indicating that the user is locked out due to too many login attempts.
     *
     * @param string $key The key to check for rate limiting.
     *
     * @return \Illuminate\Http\JsonResponse
     */

     protected function sendLockoutResponse($key)
     {
         $seconds = RateLimiter::availableIn($key);
         $minutes = floor($seconds / 60);
         $remainingSeconds = $seconds % 60;

         $timeString = '';
         if ($minutes > 0) {
             $timeString .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
             if ($remainingSeconds > 0) {
                 $timeString .= ' and ';
             }
         }
         if ($remainingSeconds > 0 || $minutes == 0) {
             $timeString .= $remainingSeconds . ' second' . ($remainingSeconds != 1 ? 's' : '');
         }

         return response()->json([
             'error' => true,
             'message' => "Too many login attempts. Please try again in $timeString."
         ]);
     }

    protected function getSetting($name, $default = null)
    {
        $security_settings = get_settings('security_settings', true);
        return $security_settings[$name] ?? $default;
    }


    public function register(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|regex:/^[^\d]+$/',
            'last_name' => 'required|string|regex:/^[^\d]+$/',
            'email' => [
                'required',
                'email',
                'unique:users,email',
                'unique:clients,email', // Check in both users and clients
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'unique:users,phone',
            ],
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ], [
            'first_name.required' => 'First name is required.',
            'first_name.string' => 'First name must be a string.',
            'first_name.regex' => 'First name cannot contain numbers.',
            'last_name.required' => 'Last name is required.',
            'last_name.string' => 'Last name must be a string.',
            'last_name.regex' => 'Last name cannot contain numbers.',
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format.',
            'email.unique' => 'The email has already been taken in users or clients.',
            'phone.required' => 'Phone number is required.',
            'phone.string' => 'Phone number must be a string.',
            'phone.unique' => 'The phone number has already been taken.',
            'phone.regex' => 'Phone number can only contain digits.',
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least 6 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        // Check if validation failed
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }

        // Create a new user
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = '1';
        $user->email_verified_at = now()->tz(config('app.timezone'));
        $user->save();

        // Assign role to user
        $user->assignRole('admin');

        // Notify user if email configuration is set
        if (isEmailConfigured()) {
            $account_creation_template = Template::where('type', 'email')
            ->where('name', 'account_creation')
            ->first();

            if (!$account_creation_template || $account_creation_template->status !== 0) {
                $user->notify(new AccountCreation($user, $request->password));
            }
        }

        // Create a new admin with the user ID
        $admin = new Admin();
        $admin->user_id = $user->id;
        $admin->save();

        return response()->json(['error' => false, 'message' => 'User registered successfully', 'redirect_url' => route('login')]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        $workspace = Workspace::find(session()->get('workspace_id'));
        $projects = isAdminOrHasAllDataAccess() ? $workspace->projects : $user->projects;
        $tasks = isAdminOrHasAllDataAccess() ? $workspace->tasks->count() : $user->tasks->count();
        $users = $workspace->users;
        $clients = $workspace->clients;
        return view('users.user_profile', ['user' => $user, 'projects' => $projects, 'tasks' => $tasks, 'users' => $users, 'clients' => $clients, 'auth_user' => getAuthenticatedUser()]);
    }
    public function list()
    {
        $workspace = Workspace::find(session()->get('workspace_id'));
        $search = request('search');
        $sort = request('sort') ?: 'id';
        $order = request('order') ?: 'DESC';
        $type = request('type');
        $typeId = request('typeId');
        $status = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? $_REQUEST['status'] : "";
        $role_ids = request('role_ids', []);

        if ($type && $typeId) {
            if ($type == 'project') {
                $project = Project::find($typeId);
                $users = $project->users();
            } elseif ($type == 'task') {
                $task = Task::find($typeId);
                $users = $task->users();
            } else {
                $users = $workspace->users();
            }
        } else {
            $users = $workspace->users();
        }

        // Ensure the search query does not introduce duplicates
        $users = $users->when($search, function ($query) use ($search) {
            return $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        });

        if ($status != '') {
            $users = $users->where('status', $status);
        }

        if (!empty($role_ids)) {
            $users = $users->whereHas('roles', function ($query) use ($role_ids) {
                $query->whereIn('roles.id', $role_ids);
            });
        }

        $totalusers = $users->count();

        $canEdit = checkPermission('edit_users');
        $canDelete = checkPermission('delete_users');
        $canManageProjects = checkPermission('manage_projects');
        $canManageTasks = checkPermission('manage_tasks');

        // Use distinct to avoid duplicates if any join condition or query causes duplicates
        $users = $users->select('users.*')
        ->distinct()
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->orderByRaw("CASE WHEN roles.name = 'admin' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN roles.name = 'admin' THEN users.id END ASC")
            ->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(
                function ($user) use ($workspace, $canEdit, $canDelete, $canManageProjects, $canManageTasks) {
                    $actions = '';
                    if ($canEdit) {
                    $actions .= '<a href="' . route('users.edit', ['id' => $user->id]) . '" title="' . get_label('update', 'Update') . '">' .
                        '<i class="bx bx-edit mx-1"></i>' .
                        '</a>';
                    }

                    if ($canDelete) {
                    $actions .= '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $user->id . '" data-type="users">' .
                        '<i class="bx bx-trash text-danger mx-1"></i>' .
                        '</button>';
                    }
                if (isAdminOrHasAllDataAccess()) {
                    $actions .=
                    '<a href="' . route('users.permissions', ['user' => $user->id]) . '" title="' . get_label('permissions', 'Permissions') . '">' .
                    '<i class="bx bxs-key mx-1"></i>' .
                    '</a>';
                }
                    $actions = $actions ?: '-';

                    $projectsBadge = '<span class="badge rounded-pill bg-primary">' . (isAdminOrHasAllDataAccess('user', $user->id) ? count($workspace->projects) : count($user->projects)) . '</span>';
                    if ($canManageProjects) {
                        $projectsBadge = '<a href="javascript:void(0);" class="viewAssigned" data-type="projects" data-id="' . 'user_' . $user->id . '" data-user="' . $user->first_name . ' ' . $user->last_name . '">' .
                    $projectsBadge . '</a>';
                    }

                    $tasksBadge = '<span class="badge rounded-pill bg-primary">' . (isAdminOrHasAllDataAccess('user', $user->id) ? count($workspace->tasks) : count($user->tasks)) . '</span>';
                    if ($canManageTasks) {
                        $tasksBadge = '<a href="javascript:void(0);" class="viewAssigned" data-type="tasks" data-id="' . 'user_' . $user->id . '" data-user="' . $user->first_name . ' ' . $user->last_name . '">' .
                    $tasksBadge . '</a>';
                }
                $photoHtml = "<div class='avatar avatar-md pull-up' title='" . $user->first_name . " " . $user->last_name . "'>
                    <a href=' " . route('users.show', ['id' => $user->id]) . "'>
                        <img src='" . ($user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle'>
                    </a>
                  </div>";

                $statusBadge = $user->status === 1
                ? '<span class="badge bg-success">' . get_label('active', 'Active') . '</span>'
                : '<span class="badge bg-danger">' . get_label('deactive', 'Deactive') . '</span>';

                $formattedHtml = '<div class="d-flex mt-2">' .
                $photoHtml .
                '<div class="mx-2">' .
                '<h6 class="mb-1">' .
                $user->first_name . ' ' . $user->last_name .
                ' ' . $statusBadge .
                '</h6>' .
                '<p class="text-muted">' . $user->email . '</p>' .
                '</div>' .
                '</div>';

                $phone = !empty($user->country_code) ? $user->country_code . ' ' . $user->phone : $user->phone;

                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                    'role' => "<span class='badge bg-label-" . (isset(config('taskify.role_labels')[$user->getRoleNames()->first()]) ? config('taskify.role_labels')[$user->getRoleNames()->first()] : config('taskify.role_labels')['default']) . " me-1'>" . $user->getRoleNames()->first() . "</span>",
                    'email' => $user->email,
                    'phone' => $phone,
                    'profile' => $formattedHtml,
                    'status' => $user->status,
                        'created_at' => format_date($user->created_at, true),
                        'updated_at' => format_date($user->updated_at, true),
                        'assigned' => '<div class="d-flex justify-content-start align-items-center">' .
                        '<div class="text-center mx-4">' .
                        $projectsBadge .
                            '<div>' . get_label('projects', 'Projects') . '</div>' .
                            '</div>' .
                            '<div class="text-center">' .
                            $tasksBadge .
                            '<div>' . get_label('tasks', 'Tasks') . '</div>' .
                            '</div>' .
                            '</div>',
                        'actions' => $actions
                    ];
            }
            );

        return response()->json([
            "rows" => $users->items(),
            "total" => $totalusers,
        ]);
    }
    public function permissions(Request $request, User $user)
    {
        $userId = $user->id;
        $role  = $user->roles[0]['name'];
        $role = Role::where('name', $role)->first();
        // Fetch permissions associated with the role
        // $rolePermissions = $role->permissions;
        $mergedPermissions = collect();
        // Loop through each role to merge its permissions
        $mergedPermissions = $mergedPermissions->merge($role->permissions);
        // If you also want to include permissions directly assigned to the user
        $mergedPermissions = $mergedPermissions->merge($user->permissions);
        return view('users.permissions', ['mergedPermissions' => $mergedPermissions, 'role' => $role, 'user' => $user]);
    }
    public function update_permissions(Request $request, User $user)
    {
        $user->syncPermissions($request->permissions);
        return redirect()->back()->with(['message' => 'Permissions updated successfully']);
    }
    public function searchUsers(Request $request)
    {
        $query = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = 10;
        $users = Workspace::find(session()->get('workspace_id'))->users();
        // If there is no query, return the first set of statuses

        $users = $users->when($query, function ($queryBuilder) use ($query) {
            $queryBuilder->where('first_name', 'like', '%' . $query . '%')
                ->orWhere('last_name', 'like', '%' . $query . '%');
        })
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['users.id', 'first_name', 'last_name']);

        // Prepare response for Select2
        $results = $users->map(function ($user) {
            return ['id' => $user->id, 'text' => ucwords($user->first_name . ' ' . $user->last_name)];
        });

        // Flag for more results
        $pagination = ['more' => $users->count() === $perPage];

        return response()->json([
            'items' => $results,
            'pagination' => $pagination
        ]);
    }
}