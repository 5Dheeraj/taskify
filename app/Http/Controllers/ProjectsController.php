<?php

namespace App\Http\Controllers;
use Exception;
use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Models\Status;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Priority;
use App\Models\Milestone;
use App\Models\Workspace;
use App\Models\ProjectUser;
use Illuminate\Http\Request;
use App\Models\ProjectClient;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CommentAttachment;
use App\Services\DeletionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserClientPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Request as FacadesRequest;
class ProjectsController extends Controller
{
    protected $workspace;
    protected $user;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // fetch session and use it in entire class with constructor
            $this->workspace = Workspace::find(session()->get('workspace_id'));
            $this->user = getAuthenticatedUser();
            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $type = null)
    {
        $status = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? $_REQUEST['status'] : "";
        $selectedTags = (request('tags')) ? request('tags') : [];
        $where = [];
        if ($status != '') {
            $where['status_id'] = $status;
        }
        $is_favorite = 0;
        if ($type === 'favorite') {
            $where['is_favorite'] = 1;
            $is_favorite = 1;
        }
        $sort = (request('sort')) ? request('sort') : "id";
        $order = 'desc';
        if ($sort == 'newest') {
            $sort = 'created_at';
            $order = 'desc';
        } elseif ($sort == 'oldest') {
            $sort = 'created_at';
            $order = 'asc';
        } elseif ($sort == 'recently-updated') {
            $sort = 'updated_at';
            $order = 'desc';
        } elseif ($sort == 'earliest-updated') {
            $sort = 'updated_at';
            $order = 'asc';
        }
        $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects() : $this->user->projects();
        $projects->where($where);
        if (!empty($selectedTags)) {
            $projects->whereHas('tags', function ($q) use ($selectedTags) {
                $q->whereIn('tags.id', $selectedTags);
            });
        }
        $projects = $projects->orderBy($sort, $order)->paginate(6);
        $statuses = Status::where("admin_id", getAdminIdByUserRole())->orWhereNull('admin_id')->get();
        $tags = Tag::where('admin_id', getAdminIdByUserRole())->orWhereNull('admin_id')->get();
        return view('projects.grid_view', ['projects' => $projects, 'auth_user' => $this->user, 'selectedTags' => $selectedTags, 'is_favorite' => $is_favorite, 'statuses' => $statuses, 'tags' => $tags]);
    }
    public function kanban_view(Request $request, $type = null)
    {
        $status = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? $_REQUEST['status'] : "";
        $selectedTags = (request('tags')) ? request('tags') : [];
        $where = [];
        if ($status != '') {
            $where['status_id'] = $status;
        }
        $is_favorite = 0;
        if ($type === 'favorite') {
            $where['is_favorite'] = 1;
            $is_favorite = 1;
        }
        $sort = (request('sort')) ? request('sort') : "id";
        $order = 'desc';
        if ($sort == 'newest') {
            $sort = 'created_at';
            $order = 'desc';
        } elseif ($sort == 'oldest') {
            $sort = 'created_at';
            $order = 'asc';
        } elseif ($sort == 'recently-updated') {
            $sort = 'updated_at';
            $order = 'desc';
        } elseif ($sort == 'earliest-updated') {
            $sort = 'updated_at';
            $order = 'asc';
        }
        $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects() : $this->user->projects();
        $projects->where($where);
        if (!empty($selectedTags)) {
            $projects->whereHas('tags', function ($q) use ($selectedTags) {
                $q->whereIn('tags.id', $selectedTags);
            });
        }
        $projects = $projects->orderBy($sort, $order)->get();
        $statuses = Status::where("admin_id", getAdminIdByUserRole())->orWhereNull('admin_id')->get();
        $tags = Tag::where('admin_id', getAdminIdByUserRole())->orWhereNull('admin_id')->get();
        return view('projects.kanban', ['projects' => $projects, 'auth_user' => $this->user, 'selectedTags' => $selectedTags, 'is_favorite' => $is_favorite, 'statuses' => $statuses, 'tags' => $tags]);
    }
    public function list_view(Request $request, $type = null)
    {
        $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects : $this->user->projects;
        $users = $this->workspace->users;
        $clients = $this->workspace->clients;
        $is_favorites = 0;
        if ($type === 'favorite') {
            $is_favorites = 1;
        }
        return view('projects.projects', ['projects' => $projects, 'users' => $users, 'clients' => $clients, 'is_favorites' => $is_favorites]);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = $this->workspace->users;
        $clients = $this->workspace->clients;
        $adminId = getAdminIdByUserRole();
        $statuses = Status::where('admin_id', $adminId)
            ->orWhere(function ($query) {
                $query->whereNull('admin_id')
                ->where('is_default', 1);
            })->get();
        $tags = Tag::where('admin_id', $adminId)
            ->get();
        return view('projects.create_project', ['users' => $users, 'clients' => $clients, 'auth_user' => $this->user, 'statuses' => $statuses, 'tags' => $tags]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $adminId = getAdminIdByUserRole();
        $formFields = $request->validate([
            'title' => ['required'],
            'status_id' => ['required'],
            'priority_id' => ['nullable'],
            'start_date' => ['required', 'before_or_equal:end_date'],
            'end_date' => ['required'],
            'budget' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'task_accessibility' => ['required'],
            'description' => ['nullable'],
            'note' => ['nullable'],
            'enable_tasks_time_entries' =>  'boolean',
        ], [
            'status_id.required' => 'The status field is required.'
        ]);
        $status = Status::findOrFail($request->input('status_id'));
        if (canSetStatus($status)) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $formFields['start_date'] = format_date($start_date, false, app('php_date_format'), 'Y-m-d');
            $formFields['end_date'] = format_date($end_date, false, app('php_date_format'), 'Y-m-d');
            $formFields['admin_id'] = $adminId;
            $formFields['workspace_id'] = $this->workspace->id;
            $formFields['created_by'] = $this->user->id;
            $new_project = Project::create($formFields);
            $userIds = $request->input('user_id') ?? [];
            $clientIds = $request->input('client_id') ?? [];
            $tagIds = $request->input('tag_ids') ?? [];
            // Set creator as a participant automatically
            if (Auth::guard('client')->check() && !in_array($this->user->id, $clientIds)) {
                array_splice($clientIds, 0, 0, $this->user->id);
            } else if (Auth::guard('web')->check() && !in_array($this->user->id, $userIds)) {
                array_splice($userIds, 0, 0, $this->user->id);
            }
            $project_id = $new_project->id;
            $project = Project::find($project_id);
            $project->users()->attach($userIds);
            $project->clients()->attach($clientIds);
            $project->tags()->attach($tagIds);
            //Status Timeline
            $project->statusTimelines()->create([
                'status' => $status->title,
                'new_color' => $status->color,
                'previous_status' => '-',
                'changed_at' => now(),
            ]);

            // Send notification to assigned users and clients
            $notification_data = [
                'type' => 'project',
                'type_id' => $project_id,
                'type_title' => $project->title,
                'access_url' => 'projects/information/' . $project_id,
                'action' => 'assigned'
            ];
            $recipients = array_merge(
                array_map(function ($userId) {
                    return 'u_' . $userId;
                }, $userIds),
                array_map(function ($clientId) {
                    return 'c_' . $clientId;
                }, $clientIds)
            );
            processNotifications($notification_data, $recipients);
            return response()->json(['error' => false, 'id' => $new_project->id, 'message' => 'Project created successfully.']);
        } else {
            return response()->json(['error' => true, 'message' => 'You are not authorized to set this status.']);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Project::findOrFail($id);
        $projectTags = $project->tags;
        $users = $this->workspace->users;
        $clients = $this->workspace->clients;
        $types = getControllerNames();
        $statuses = Status::where("admin_id", getAdminIdByUserRole())->get();
        $toSelectTaskUsers = $project->users;
        $comments = $project->comments;
        return view('projects.project_information', ['project' => $project, 'projectTags' => $projectTags, 'users' => $users, 'clients' => $clients, 'types' => $types, 'auth_user' => $this->user, 'statuses' => $statuses, 'toSelectTaskUsers' => $toSelectTaskUsers, 'comments' => $comments]);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $users = $this->workspace->users;
        $clients = $this->workspace->clients;
        $adminId = getAdminIdByUserRole();
        $statuses = Status::where("admin_id", getAdminIdByUserRole())->get();
        $tags = Tag::where('admin_id', $adminId)->get();
        return view('projects.update_project', ["project" => $project, "users" => $users, "clients" => $clients, 'statuses' => $statuses, 'tags' => $tags]);
    }
    public function get($projectId)
    {
        $project = Project::findOrFail($projectId);
        $users = $project->users()->get();
        $clients = $project->clients()->get();
        $tags = $project->tags()->get();
        $workspace_users = $this->workspace->users;
        $workspace_clients = $this->workspace->clients;
        $task_lists = $project->taskLists;

        return response()->json(['error' => false, 'project' => $project, 'users' => $users, 'clients' => $clients, 'workspace_users' => $workspace_users, 'workspace_clients' => $workspace_clients, 'tags' => $tags, 'task_lists' => $task_lists]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $formFields = $request->validate([
            'id' => 'required|exists:projects,id',
            'title' => ['required'],
            'status_id' => ['required'],
            'priority_id' => ['nullable'],
            'budget' => ['nullable', 'integer'],
            'start_date' => ['required', 'before_or_equal:end_date'],
            'end_date' => ['required'],
            'task_accessibility' => ['required'],
            'description' => ['nullable'],
            'note' => ['nullable'],
            'user_id' => ['array'], // Ensuring these are arrays
            'client_id' => ['array'],
            'tag_ids' => ['array'],
            'enable_tasks_time_entries' =>  'boolean',
        ]);
        $id = $formFields['id'];
        $project = Project::findOrFail($id);
        $currentStatusId = $project->status_id;
        $workspace = $project->workspace;  // Assuming project is related to a workspace
        // Check if the status has changed and if the user can set this status
        if ($currentStatusId != $formFields['status_id']) {
            $status = Status::findOrFail($formFields['status_id']);
            if (!canSetStatus($status)) {
                return response()->json(['error' => true, 'message' => 'You are not authorized to set this status.']);
            }
            // Status Time Storing
            $oldStatus = Status::findOrFail($currentStatusId);
            $newStatus = Status::findOrFail($formFields['status_id']);
            $project->statusTimelines()->create([
                'status' => $newStatus->title,
                'new_color' => $newStatus->color,
                'previous_status' => $oldStatus->title,
                'old_color' => $oldStatus->color,
                'changed_at' => now(),
            ]);
        }
        // Format dates
        $formFields['start_date'] = format_date($formFields['start_date'], false, app('php_date_format'), 'Y-m-d');
        $formFields['end_date'] = format_date($formFields['end_date'], false, app('php_date_format'), 'Y-m-d');
        // Remove user_id and client_id from $formFields to prevent updating directly on the projects table
        unset($formFields['user_id'], $formFields['client_id']);
        // Retrieve user and client IDs, defaulting to empty arrays
        $userIds = $request->input('user_id', []);
        $clientIds = $request->input('client_id', []);
        $tagIds = $request->input('tag_ids', []);
        // Automatically set creator as a participant if not already included
        $creatorId = $project->created_by;
        if (!in_array($creatorId, $userIds) && User::find($creatorId)) {
            array_unshift($userIds, $creatorId);
        } elseif (!in_array($creatorId, $clientIds) && Client::find($creatorId)) {
            array_unshift($clientIds, $creatorId);
        }
        // Update the project details
        $project->update($formFields);
        // Use workspace relations to ensure only workspace users and clients are synced
        $workspaceUserIds = $workspace->users->pluck('id')->toArray();
        $workspaceClientIds = $workspace->clients->pluck('id')->toArray();
        // Sync only valid workspace users and clients
        $validUserIds = array_intersect($userIds, $workspaceUserIds);
        $validClientIds = array_intersect($clientIds, $workspaceClientIds);
        // Sync relationships with users, clients, and tags
        $project->users()->sync($validUserIds);
        $project->clients()->sync($validClientIds);
        $project->tags()->sync($tagIds);
        // Prepare notification data
        $notificationData = [
            'type' => 'project',
            'type_id' => $project->id,
            'type_title' => $project->title,
            'access_url' => 'projects/information/' . $project->id,
            'action' => 'assigned',
            'title' => 'Project Updated',
            'message' => $this->user->first_name . ' ' . $this->user->last_name . ' assigned you new project: ' . $project->title . ', ID #' . $project->id . '.'
        ];
        // Determine recipients
        $recipients = array_merge(
            array_map(fn($userId) => 'u_' . $userId, $validUserIds),
            array_map(fn($clientId) => 'c_' . $clientId, $validClientIds)
        );
        // Process notifications
        processNotifications($notificationData, $recipients);
        return response()->json(['error' => false, 'id' => $id, 'message' => 'Project updated successfully.']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        // Get all attachments before deletion
        $comments = $project->comments()->with('attachments')->get();
        // Delete all files using public disk
        $comments->each(function ($comment) {
            $comment->attachments->each(function ($attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            });
        });
        $project->comments()->forceDelete();
        $response = DeletionService::delete(Project::class, $id, 'Project');
        return $response;
    }
    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:projects,id' // Ensure each ID in 'ids' is an integer and exists in the 'projects' table
        ]);
        $ids = $validatedData['ids'];
        $deletedProjectTitles = [];
        // Retrieve all projects by the given IDs
        $projects = Project::whereIn('id', $ids)->get();
        if ($projects->isEmpty()) {
            return response()->json(['error' => true, 'message' => 'No projects found to delete.']);
        }
        // Collect project titles and delete all associated comments in bulk
        foreach ($projects as $project) {
            $deletedProjectTitles[] = $project->title;
            $comments = $project->comments()->with('attachments')->get();
            // Delete all files using public disk
            $comments->each(function ($comment) {
                $comment->attachments->each(function ($attachment) {
                    Storage::disk('public')->delete($attachment->file_path);
                    $attachment->delete();
                });
            });
            $project->comments()->forceDelete();
        }
        // Bulk delete associated comments for all projects
        // Bulk delete projects using the DeletionService
        foreach ($ids as $id) {
            DeletionService::delete(Project::class, $id, 'Project');
        }
        return response()->json(['error' => false, 'message' => 'Project(s) deleted successfully.', 'ids' => $ids, 'titles' => $deletedProjectTitles]);
    }
    public function list(Request $request, $id = '', $type = '')
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $status = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? $_REQUEST['status'] : "";
        $user_id = (request('user_id')) ? request('user_id') : "";
        $client_id = (request('client_id')) ? request('client_id') : "";
        $start_date_from = (request('project_start_date_from')) ? request('project_start_date_from') : "";
        $start_date_to = (request('project_start_date_to')) ? request('project_start_date_to') : "";
        $end_date_from = (request('project_end_date_from')) ? request('project_end_date_from') : "";
        $end_date_to = (request('project_end_date_to')) ? request('project_end_date_to') : "";
        $is_favorites = (request('is_favorites')) ? request('is_favorites') : "";
        $where = [];
        if ($status != '') {
            $where['status_id'] = $status;
        }
        if ($is_favorites) {
            $where['is_favorite'] = 1;
        }
        if ($id) {
            $id = explode('_', $id);
            $belongs_to = $id[0];
            $belongs_to_id = $id[1];
            $userOrClient = $belongs_to == 'user' ? User::find($belongs_to_id) : Client::find($belongs_to_id);
            $projects = isAdminOrHasAllDataAccess($belongs_to, $belongs_to_id) ? $this->workspace->projects() : $userOrClient->projects();
        } else {
            $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects() : $this->user->projects();
        }
        if ($user_id) {
            $user = User::find($user_id);
            $projects = $user->projects();
        }
        if ($client_id) {
            $client = Client::find($client_id);
            $projects = $client->projects();
        }
        if ($start_date_from && $start_date_to) {
            $projects->whereBetween('start_date', [$start_date_from, $start_date_to]);
        }
        if ($end_date_from && $end_date_to) {
            $projects->whereBetween('end_date', [$end_date_from, $end_date_to]);
        }
        $projects->when($search, function ($query) use ($search) {
            $query->where('title', 'like', '%' . $search . '%')
                ->orWhere('id', 'like', '%' . $search . '%');
        });
        $projects->where($where);
        $totalprojects = $projects->count();
        $canCreate = checkPermission('create_projects');
        $canEdit = checkPermission('edit_projects');
        $canDelete = checkPermission('delete_projects');
        $statuses = Status::where('admin_id', getAdminIDByUserRole())
            ->orWhere(function ($query) {
                $query->whereNull('admin_id')
                ->where('is_default', 1);
            })->get();
        $priorities = Priority::where('admin_id', getAdminIDByUserRole())->get();
        $labelNote = get_label('note', 'Note');
        $projects = $projects->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(
            function ($project) use ($statuses, $priorities, $canEdit, $canDelete, $canCreate, $labelNote) {
                $statusOptions = '';
                foreach ($statuses as $status) {
                    // Determine if the option should be disabled
                    $disabled = canSetStatus($status)  ? '' : 'disabled';
                    // Render the option with appropriate attributes
                    $selected = $project->status_id == $status->id ? 'selected' : '';
                    $statusOptions .= "<option value='{$status->id}' class='badge bg-label-$status->color' $selected $disabled>$status->title</option>";
                }
                $priorityOptions = "";
                foreach ($priorities as $priority) {
                    $selected = $project->priority_id == $priority->id ? 'selected' : '';
                    $priorityOptions .= "<option value='{$priority->id}' class='badge bg-label-$priority->color' $selected>$priority->title</option>";
                }
                $actions = '';
                if ($canEdit) {
                    $actions .= '<a href="javascript:void(0);" class="edit-project" data-id="' . $project->id . '" title="' . get_label('update', 'Update') . '">' .
                        '<i class="bx bx-edit mx-1"></i>' .
                        '</a>';
                }
                if ($canDelete) {
                    $actions .= '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $project->id . '" data-type="projects" data-table="projects_table">' .
                        '<i class="bx bx-trash text-danger mx-1"></i>' .
                        '</button>';
                }
                if ($canCreate) {
                    $actions .= '<a href="javascript:void(0);" class="duplicate" data-id="' . $project->id . '" data-title="' . $project->title . '" data-type="projects" data-table="projects_table" title="' . get_label('duplicate', 'Duplicate') . '">' .
                        '<i class="bx bx-copy text-warning mx-2"></i>' .
                        '</a>';
                }
                $actions .= '<a href="javascript:void(0);" class="quick-view" data-id="' . $project->id . '" data-type="project" title="' . get_label('quick_view', 'Quick View') . '">' .
                    '<i class="bx bx-info-circle mx-3"></i>' .
                    '</a>';
                $actions = $actions ?: '-';
                $userHtml = '';
                if (!empty($project->users) && count($project->users) > 0) {
                    $userHtml .= '<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">';
                    foreach ($project->users as $user) {
                        $userHtml .= "<li class='avatar avatar-sm pull-up'><a href='" . route('users.show', ['id' => $user->id]) . "' target='_blank' title='{$user->first_name} {$user->last_name}'><img src='" . ($user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></a></li>";
                    }
                    if ($canEdit) {
                        $userHtml .= '<li title=' . get_label('update', 'Update') . '><a href="javascript:void(0)" class="btn btn-icon btn-sm btn-outline-primary btn-sm rounded-circle edit-project update-users-clients" data-id="' . $project->id . '"><span class="bx bx-edit"></span></a></li>';
                    }
                    $userHtml .= '</ul>';
                } else {
                    $userHtml = '<span class="badge bg-primary">' . get_label('not_assigned', 'Not Assigned') . '</span>';
                    if ($canEdit) {
                        $userHtml .= '<a href="javascript:void(0)" class="btn btn-icon btn-sm btn-outline-primary btn-sm rounded-circle edit-project update-users-clients" data-id="' . $project->id . '">' .
                            '<span class="bx bx-edit"></span>' .
                            '</a>';
                    }
                }
                $clientHtml = '';
                if (!empty($project->clients) && count($project->clients) > 0) {
                    $clientHtml .= '<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">';
                    foreach ($project->clients as $client) {
                        $clientHtml .= "<li class='avatar avatar-sm pull-up'><a href='" . route('clients.profile', ['id' => $client->id]) . "' target='_blank' title='{$client->first_name} {$client->last_name}'><img src='" . ($client->photo ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></a></li>";
                    }
                    if ($canEdit) {
                        $clientHtml .= '<li title=' . get_label('update', 'Update') . '><a href="javascript:void(0)" class="btn btn-icon btn-sm btn-outline-primary btn-sm rounded-circle edit-project update-users-clients" data-id="' . $project->id . '"><span class="bx bx-edit"></span></a></li>';
                    }
                    $clientHtml .= '</ul>';
                } else {
                    $clientHtml = '<span class="badge bg-primary">' . get_label('not_assigned', 'Not Assigned') . '</span>';
                    if ($canEdit) {
                        $clientHtml .= '<a href="javascript:void(0)" class="btn btn-icon btn-sm btn-outline-primary btn-sm rounded-circle edit-project update-users-clients" data-id="' . $project->id . '">' .
                            '<span class="bx bx-edit"></span>' .
                            '</a>';
                    }
                }
                $tagHtml = '';
                foreach ($project->tags as $tag) {
                    $tagHtml .= "<span class='badge bg-label-{$tag->color}'>{$tag->title}</span> ";
                }
                $description = \Illuminate\Support\Str::limit(strip_tags($project->description), 25);
                return [
                    'id' => $project->id,
                    'title' => "<a href='" . route('projects.info', ['id' => $project->id]) . "' target='_blank' title=' {$description}'><strong>{$project->title}</strong></a> <a href='javascript:void(0);' class='mx-2'><i class='bx " . ($project->is_favorite ? 'bxs' : 'bx') . "-star favorite-icon text-warning' data-favorite='{$project->is_favorite}' data-id='{$project->id}' title='" . ($project->is_favorite ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite')) . "'></i></a>
                    <a href='" . route('projects.info', ['id' => $project->id]) . "#navs-top-discussions'  target='_blank'  class='mx-2'>
                    <i class='bx bx-message-rounded-dots text-danger' data-bs-toggle='tooltip' data-bs-placement='right' title='" . get_label('discussions', 'Discussions') . "'></i>
                </a>",
                    'users' => $userHtml,
                    'clients' => $clientHtml,
                    'start_date' => format_date($project->start_date),
                    'end_date' => format_date($project->end_date),
                    'budget' => !empty($project->budget) && $project->budget !== null ? format_currency($project->budget) : '-',
                    'status_id' => "
                       <div class='d-flex align-items-center'>
                         <select class='form-select form-select-sm select-bg-label-{$project->status->color}'
                            id='statusSelect' data-id='{$project->id}' data-original-status-id='{$project->status->id}' data-original-color-class='select-bg-label-{$project->status->color}'> {$statusOptions} </select>
                              " . (!empty($project->note) ? "
                            <span class='ms-2' data-bs-toggle='tooltip' title='{$labelNote}:{$project->note}'> <i class='bx bxs-notepad text-primary'></i></span>" : "") .
                    " </div>
                            ",
                    'priority_id' => "<select class='form-select form-select-sm select-bg-label-" . ($project->priority ? $project->priority->color : 'secondary') . "' id='prioritySelect' data-id='{$project->id}' data-original-priority-id='" . ($project->priority ? $project->priority->id : '') . "' data-original-color-class='select-bg-label-" . ($project->priority ? $project->priority->color : 'secondary') . "'>{$priorityOptions}</select>",
                    'task_accessibility' => get_label($project->task_accessibility, ucwords(str_replace("_", " ", $project->task_accessibility))),
                    'tags' => $tagHtml ?: ' - ',
                    'created_at' => format_date($project->created_at, true),
                    'updated_at' => format_date($project->updated_at, true),
                    'tasks_count' => $project->tasks()->count(),
                    'actions' => $actions
                ];
            }
            );
        return response()->json([
            "rows" => $projects->items(),
            "total" => $totalprojects,
        ]);
    }
    public function update_favorite(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['error' => true, 'message' => 'Project not found']);
        }
        $isFavorite = $request->input('is_favorite');
        // Update the project's favorite status
        $project->is_favorite = $isFavorite;
        $project->save();
        return response()->json(['error' => false]);
    }
    public function duplicate($id)
    {
        // Define the related tables for this meeting
        $relatedTables = ['users', 'clients', 'tasks', 'tags']; // Include related tables as needed
        // Use the general duplicateRecord function
        $title = (request()->has('title') && !empty(trim(request()->title))) ? request()->title : '';
        $duplicate = duplicateRecord(Project::class, $id, $relatedTables, $title);
        if (!$duplicate) {
            return response()->json(['error' => true, 'message' => 'Project duplication failed.']);
        }
        if (request()->has('reload') && request()->input('reload') === 'true') {
            Session::flash('message', 'Project duplicated successfully.');
        }
        return response()->json(['error' => false, 'message' => 'Project duplicated successfully.', 'id' => $id]);
    }
    public function upload_media(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'integer|exists:projects,id'
            ]);
            $mediaIds = [];
            if ($request->hasFile('media_files')) {
                $project = Project::find($validatedData['id']);
                $mediaFiles = $request->file('media_files');
                foreach ($mediaFiles as $mediaFile) {
                    $mediaItem = $project->addMedia($mediaFile)
                        ->sanitizingFileName(function ($fileName) use ($project) {
                            // Replace special characters and spaces with hyphens
                            return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                        })
                        ->toMediaCollection('project-media');
                    $mediaIds[] = $mediaItem->id;
                }
                Session::flash('message', 'File(s) uploaded successfully.');
                return response()->json(['error' => false, 'message' => 'File(s) uploaded successfully.', 'id' => $mediaIds, 'type' => 'media', 'parent_type' => 'project', 'parent_id' => $project->id]);
            } else {
                Session::flash('error', 'No file(s) chosen.');
                return response()->json(['error' => true, 'message' => 'No file(s) chosen.']);
            }
        } catch (Exception $e) {
            // Handle the exception as needed
            Session::flash('error', 'An error occurred during file upload: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'An error occurred during file upload: ' . $e->getMessage()]);
        }
    }
    public function get_media($id)
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $project = Project::findOrFail($id);
        $media = $project->getMedia('project-media');
        if ($search) {
            $media = $media->filter(function ($mediaItem) use ($search) {
                return (
                    // Check if ID contains the search query
                    stripos($mediaItem->id, $search) !== false ||
                    // Check if file name contains the search query
                    stripos($mediaItem->file_name, $search) !== false ||
                    // Check if date created contains the search query
                    stripos($mediaItem->created_at->format('Y-m-d'), $search) !== false
                );
            });
        }
        $formattedMedia = $media->map(function ($mediaItem) {
            // Check if the disk is public
            $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;
            // Generate file URL based on disk visibility
            $fileUrl = $isPublicDisk
                ? asset('storage/project-media/' . $mediaItem->file_name)
            : $mediaItem->getFullUrl();
            return [
                'id' => $mediaItem->id,
                'file' => '<a href="' . $fileUrl . '" data-lightbox="project-media"> <img src="' . $fileUrl . '" alt="' . $mediaItem->file_name . '" width="50"></a>',
                'file_name' => $mediaItem->file_name,
                'file_size' => formatSize($mediaItem->size),
                'created_at' => format_date($mediaItem->created_at),
                'updated_at' => format_date($mediaItem->updated_at),
                'actions' => [
                    '<a href="' . $fileUrl . '" title=' . get_label('download', 'Download') . ' download>' .
                        '<i class="bx bx-download bx-sm"></i>' .
                        '</a>' .
                        '<button title=' . get_label('delete', 'Delete') . ' type="button" class="btn delete" data-id="' . $mediaItem->id . '" data-type="project-media" data-table="project_media_table">' .
                        '<i class="bx bx-trash text-danger"></i>' .
                        '</button>'
                ],
            ];
        });
        if ($order == 'asc') {
            $formattedMedia = $formattedMedia->sortBy($sort);
        } else {
            $formattedMedia = $formattedMedia->sortByDesc($sort);
        }
        return response()->json([
            'rows' => $formattedMedia->values()->toArray(),
            'total' => $formattedMedia->count(),
        ]);
    }
    public function delete_media($mediaId)
    {
        $mediaItem = Media::find($mediaId);
        if (!$mediaItem) {
            // Handle case where media item is not found
            return response()->json(['error' => true, 'message' => 'File not found.']);
        }
        // Delete media item from the database and disk
        $mediaItem->delete();
        return response()->json(['error' => false, 'message' => 'File deleted successfully.', 'id' => $mediaId, 'title' => $mediaItem->file_name, 'parent_id' => $mediaItem->model_id,  'type' => 'media', 'parent_type' => 'project']);
    }
    public function delete_multiple_media(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:media,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $deletedIds = [];
        $deletedTitles = [];
        $parentIds = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $media = Media::find($id);
            if ($media) {
                $deletedIds[] = $id;
                $deletedTitles[] = $media->file_name;
                $parentIds[] = $media->model_id;
                $media->delete();
            }
        }
        return response()->json(['error' => false, 'message' => 'Files(s) deleted successfully.', 'id' => $deletedIds, 'titles' => $deletedTitles, 'parent_id' => $parentIds, 'type' => 'media', 'parent_type' => 'project']);
    }
    public function store_milestone(Request $request)
    {
        $formFields = $request->validate([
            'project_id' => ['required'],
            'title' => ['required'],
            'status' => ['required'],
            'start_date' => ['required', 'before_or_equal:end_date'],
            'end_date' => ['required'],
            'cost' => ['required', 'regex:/^\d+(\.\d+)?$/'],
            'description' => ['nullable'],
        ]);
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $formFields['start_date'] = format_date($start_date, false, app('php_date_format'), 'Y-m-d');
        $formFields['end_date'] = format_date($end_date, false, app('php_date_format'), 'Y-m-d');
        $formFields['workspace_id'] = $this->workspace->id;
        $formFields['created_by'] = isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id;
        $milestone = Milestone::create($formFields);
        return response()->json(['error' => false, 'message' => 'Milestone created successfully.', 'id' => $milestone->id, 'type' => 'milestone', 'parent_type' => 'project', 'parent_id' => $milestone->project_id]);
    }
    public function get_milestones($id)
    {
        $project = Project::findOrFail($id);
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $status = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? $_REQUEST['status'] : "";
        $start_date_from = (request('start_date_from')) ? request('start_date_from') : "";
        $start_date_to = (request('start_date_to')) ? request('start_date_to') : "";
        $end_date_from = (request('end_date_from')) ? request('end_date_from') : "";
        $end_date_to = (request('end_date_to')) ? request('end_date_to') : "";
        $milestones =  $project->milestones();
        if ($search) {
            $milestones = $milestones->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhere('cost', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        if ($start_date_from && $start_date_to) {
            $milestones = $milestones->whereBetween('start_date', [$start_date_from, $start_date_to]);
        }
        if ($end_date_from && $end_date_to) {
            $milestones  = $milestones->whereBetween('end_date', [$end_date_from, $end_date_to]);
        }
        if ($status) {
            $milestones  = $milestones->where('status', $status);
        }
        $total = $milestones->count();
        $milestones = $milestones->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(function ($milestone) {
                if (strpos($milestone->created_by, 'u_') === 0) {
                    // The ID corresponds to a user
                    $creator = User::find(substr($milestone->created_by, 2)); // Remove the 'u_' prefix
                } elseif (strpos($milestone->created_by, 'c_') === 0) {
                // The ID corresponds to a client
                $creator = Client::find(substr($milestone->created_by, 2)); // Remove the 'c_' prefix
                }
                if ($creator !== null) {
                    $creator = $creator->first_name . ' ' . $creator->last_name;
                } else {
                    $creator = '-';
            }
            $statusBadge = '';
                if ($milestone->status == 'incomplete') {
                    $statusBadge = '<span class="badge bg-danger">' . get_label('incomplete', 'Incomplete') . '</span>';
                } elseif ($milestone->status == 'complete') {
                    $statusBadge = '<span class="badge bg-success">' . get_label('complete', 'Complete') . '</span>';
                }
                $progress = '<div class="demo-vertical-spacing">
                <div class="progress">
                  <div class="progress-bar" role="progressbar" style="width: ' . $milestone->progress . '%" aria-valuenow="' . $milestone->progress .
                '" aria-valuemin="0" aria-valuemax="100">
                  </div>
                </div>
              </div> <h6 class="mt-2">' . $milestone->progress . '%</h6>';
                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'status' => $statusBadge,
                    'progress' => $progress,
                    'cost' => format_currency($milestone->cost),
                    'start_date' => format_date($milestone->start_date),
                    'end_date' => format_date($milestone->end_date),
                    'created_by' => $creator,
                    'description' => $milestone->description,
                'created_at' => format_date($milestone->created_at),
                'updated_at' => format_date($milestone->updated_at),
                ];
            });
        return response()->json([
            "rows" => $milestones->items(),
            "total" => $total,
        ]);
    }
    public function get_milestone($id)
    {
        $ms = Milestone::findOrFail($id);
        return response()->json(['ms' => $ms]);
    }
    public function update_milestone(Request $request)
    {
        $formFields = $request->validate([
            'title' => ['required'],
            'status' => ['required'],
            'start_date' => ['required', 'before_or_equal:end_date'],
            'end_date' => ['required'],
            'cost' => ['required', 'regex:/^\d+(\.\d+)?$/'],
            'progress' => ['required'],
            'description' => ['nullable'],
        ]);
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $formFields['start_date'] = format_date($start_date, null, "Y-m-d");
        $formFields['end_date'] = format_date($end_date, null, "Y-m-d");
        $ms = Milestone::findOrFail($request->id);
        if ($ms->update($formFields)) {
            return response()->json(['error' => false, 'message' => 'Milestone updated successfully.', 'id' => $ms->id, 'type' => 'milestone', 'parent_type' => 'project', 'parent_id' => $ms->project_id]);
        } else {
            return response()->json(['error' => true, 'message' => 'Milestone couldn\'t updated.']);
        }
    }
    public function delete_milestone($id)
    {
        $ms = Milestone::findOrFail($id);
        DeletionService::delete(Milestone::class, $id, 'Milestone');
        return response()->json(['error' => false, 'message' => 'Milestone deleted successfully.', 'id' => $id, 'title' => $ms->title, 'type' => 'milestone', 'parent_type' => 'project', 'parent_id' => $ms->project_id]);
    }
    public function delete_multiple_milestones(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:milestones,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $deletedIds = [];
        $deletedTitles = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $ms = Milestone::findOrFail($id);
            $deletedIds[] = $id;
            $deletedTitles[] = $ms->title;
            $parentIds[] = $ms->project_id;
            DeletionService::delete(Milestone::class, $id, 'Milestone');
        }
        return response()->json(['error' => false, 'message' => 'Milestone(s) deleted successfully.', 'id' => $deletedIds, 'titles' => $deletedTitles, 'type' => 'milestone', 'parent_type' => 'project', 'parent_id' => $parentIds]);
    }
    public function saveViewPreference(Request $request)
    {
        $view = $request->input('view');
        $prefix = isClient() ? 'c_' : 'u_';
        if (UserClientPreference::updateOrCreate(
            ['user_id' => $prefix . $this->user->id, 'table_name' => 'projects'],
            ['default_view' => $view]
        )) {
            return response()->json(['error' => false, 'message' => 'Default View Set Successfully.']);
        } else {
            return response()->json(['error' => true, 'message' => 'Something Went Wrong.']);
        }
    }
    public function update_status(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'statusId' => ['required']
        ]);
        $id = $request->id;
        $statusId = $request->statusId;
        $status = Status::findOrFail($statusId);
        if (canSetStatus($status)) {
            $project = Project::findOrFail($id);
            $oldStatus = $project->status_id;
            $currentStatus = $project->status->title;
            $project->status_id = $statusId;
            $project->note = $request->note;
            $oldStatus = Status::findOrFail($oldStatus);
            $newStatus = Status::findOrFail($statusId);
            $project->statusTimelines()->create([
                'status' => $newStatus->title,
                'new_color' => $newStatus->color,
                'previous_status' => $oldStatus->title,
                'old_color' => $oldStatus->color,
                'changed_at' => now(),
            ]);
            if ($project->save()) {
                // Reload the project to get updated status information
                $project = $project->fresh();
                $newStatus = $project->status->title;
                $notification_data = [
                    'type' => 'project_status_updation',
                    'type_id' => $id,
                    'type_title' => $project->title,
                    'updater_first_name' => $this->user->first_name,
                    'updater_last_name' => $this->user->last_name,
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus,
                    'access_url' => 'projects/information/' . $id,
                    'action' => 'status_updated'
                ];
                $userIds = $project->users->pluck('id')->toArray();
                $clientIds = $project->clients->pluck('id')->toArray();
                $recipients = array_merge(
                    array_map(function ($userId) {
                        return 'u_' . $userId;
                    }, $userIds),
                    array_map(function ($clientId) {
                        return 'c_' . $clientId;
                    }, $clientIds)
                );
                processNotifications($notification_data, $recipients);
                return response()->json(['error' => false, 'message' => 'Status updated successfully.', 'id' => $id, 'type' => 'project', 'activity_message' => $this->user->first_name . ' ' . $this->user->last_name . ' updated project status from ' . $currentStatus . ' to ' . $newStatus]);
            } else {
                return response()->json(['error' => true, 'message' => 'Status couldn\'t updated.']);
            }
        } else {
            return response()->json(['error' => true, 'message' => 'You are not authorized to set this status.']);
        }
    }
    public function update_priority(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'priorityId' => ['nullable']
        ]);
        $id = $request->id;
        $priorityId = $request->priorityId;
        $project = Project::findOrFail($id);
        $currentPriority = $project->priority ? $project->priority->title : 'Default';
        $project->priority_id = $priorityId;
        $project->note = $request->note;
        if ($project->save()) {
            // Reload the project to get updated priority information
            $project = $project->fresh();
            $newPriority = $project->priority ? $project->priority->title : 'Default';
            $message = $this->user->first_name . ' ' . $this->user->last_name . ' updated project priority from ' . $currentPriority . ' to ' . $newPriority;
            return response()->json(['error' => false, 'message' => 'Priority updated successfully.', 'id' => $id, 'type' => 'project', 'activity_message' => $message]);
        } else {
            return response()->json(['error' => true, 'message' => 'Priority couldn\'t updated.']);
        }
    }
    public function comments(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'content' => 'required|string',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,xlsx,txt,docx|max:2048', // Add more file types and size limits if needed
        ]);
        list($processedContent, $mentionedUserIds) = replaceUserMentionsWithLinks($request->content);
        $comment = Comment::with('user')->create([
            'commentable_type' => $request->model_type,
            'commentable_id' => $request->model_id,
            'content' => $processedContent,
            'user_id' => auth()->id(), // Associate with authenticated user
            'parent_id' => $request->parent_id, // Set the parent_id for replies
        ]);
        $directoryPath = storage_path('app/public/comment_attachments');
        // Create the directory with permissions if it does not exist
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true); // 0755 for directories
        }
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('public/comment_attachments');
                $path = str_replace('public/', '', $path);
                CommentAttachment::create([
                    'comment_id' => $comment->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                ]);
            }
        }
        sendMentionNotification($comment, $mentionedUserIds, session()->get('workspace_id'), auth()->id());
        return response()->json([
            'success' => true,
            'comment' => $comment->load('attachments'),
            'message' => get_label('comment_added_successfully', 'Comment Added Successfully'),
            'user' => $comment->user,
            'created_at' => $comment->created_at->diffForHumans() // Send human-readable date
        ]);
    }
    public function get_comment(Request $request, $id)
    {
        $comment = Comment::with('attachments')->findOrFail($id);
        return response()->json([
            'comment' => $comment,
        ]);
    }
    public function update_comment(Request $request)
    {
        $request->validate([
            'comment_id' => ['required'],
            'content' => 'required|string',
        ]);
        list($processedContent, $mentionedUserIds) = replaceUserMentionsWithLinks($request->content);
        $id = $request->comment_id;
        $comment = Comment::findOrFail($id);
        $comment->content = $processedContent;
        if ($comment->save()) {
            sendMentionNotification($comment, $mentionedUserIds, session()->get('workspace_id'), auth()->id());
            return response()->json(['error' => false, 'message' => 'Comment updated successfully.', 'id' => $id, 'type' => 'project']);
        } else {
            return response()->json(['error' => true, 'message' => 'Comment couldn\'t updated.']);
        }
    }
    public function destroy_comment(Request $request)
    {
        $request->validate([
            'comment_id' => ['required'],
        ]);
        $id = $request->comment_id;
        $comment = Comment::findOrFail($id);
        $attachments = $comment->attachments;
        foreach ($attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }
        if ($comment->forceDelete()) {
            return response()->json(['error' => false, 'message' => 'Comment deleted successfully.', 'id' => $id, 'type' => 'project']);
        } else {
            return response()->json(['error' => true, 'message' => 'Comment couldn\'t deleted.']);
        }
    }
    public function destroy_comment_attachment($id)
    {
        $attachment = CommentAttachment::findOrFail($id);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return response()->json(['error' => false, 'message' => 'Attachment deleted successfully.']);
    }
    public function gantt_chart()
    {
        $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects : $this->user->projects;
        // This method now only returns the view, without preloading data
        return view('projects.gantt-chart-view', compact('projects'));
    }
    public function fetch_gantt_data(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects : $this->user->projects;
        // Filter projects based on the date range
        $filteredProjects = $projects->filter(function ($project) use ($startDate, $endDate) {
            $projectStart = Carbon::parse($project->start_date);
            $projectEnd = Carbon::parse($project->end_date);
            return ($projectStart->between($startDate, $endDate) ||
                $projectEnd->between($startDate, $endDate) ||
                ($projectStart->lte($startDate) && $projectEnd->gte($endDate)));
        });
        // Load the tasks for each project
        $filteredProjects->load('tasks');
        return response()->json($filteredProjects->values());
    }
    // public function fetch_gantt_data(Request $request)
    // {
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');
    //     $projects = isAdminOrHasAllDataAccess()
    //         ? $this->workspace->projects()->with(['tasks' => function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('start_date', [$startDate, $endDate])
    //                 ->orWhereBetween('due_date', [$startDate, $endDate]);
    //         }])->whereBetween('start_date', [$startDate, $endDate])
    //         ->orWhereBetween('end_date', [$startDate, $endDate])
    //         ->get()
    //         : $this->user->projects()->with(['tasks' => function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('start_date', [$startDate, $endDate])
    //                 ->orWhereBetween('due_date', [$startDate, $endDate]);
    //         }])->whereBetween('start_date', [$startDate, $endDate])
    //         ->orWhereBetween('end_date', [$startDate, $endDate])
    //         ->get();
    //     return response()->json($projects);
    // }
    public function update_module_dates(Request $request)
    {
        // First, validate the input format for module and date fields
        $request->validate([
            'module' => 'required|array',
            'module.type' => 'required|string|in:project,task',
            'module.id' => 'required|integer',
            'start_date' => 'required|string', // Temporarily validate as a string
            'end_date' => 'required|string',   // Temporarily validate as a string
        ], [
            'module.required' => 'The module is required. Please specify if it is a project or task.',
            'module.type.required' => 'The type must be either "project" or "task".',
            'module.type.in' => 'The module type must be either "project" or "task".',
            'module.id.required' => 'The module ID is required.',
            'module.id.integer' => 'The module ID must be a valid integer.',
            'start_date.required' => 'The start date is required.',
            'end_date.required' => 'The end date is required.',
        ]);
        // Extract module and date strings from the request
        $module = $request->input('module');
        $startDateString = $request->input('start_date');
        $endDateString = $request->input('end_date');
        // Attempt to parse dates using the parseDate helper method
        $startDate = $this->parseDate($startDateString);
        $endDate = $this->parseDate($endDateString);
        // Validate the parsed dates to ensure they are valid
        $request->validate([
            'start_date' => ['required', function ($attribute, $value, $fail) use ($startDate) {
                if (!$startDate) {
                    $fail('The start date format is invalid. Please provide a valid date.');
                }
            }],
            'end_date' => ['required', function ($attribute, $value, $fail) use ($endDate, $startDate) {
                if (!$endDate) {
                    $fail('The end date format is invalid. Please provide a valid date.');
                } elseif ($endDate < $startDate) {
                    $fail('The end date must be after or equal to the start date.');
                }
            }],
        ]);
        // Handle project or task based on the provided module type
        if ($module['type'] === 'project') {
            $project = Project::find($module['id']);
            if ($project) {
                $project->start_date = $startDate;
                $project->end_date = $endDate;
                $project->save(); // Save the project dates
                return response()->json(['error' => false, 'message' => 'Project dates updated successfully.']);
            } else {
                return response()->json(['error' => true, 'message' => 'Project not found.']);
            }
        } elseif ($module['type'] === 'task') {
            $task = Task::find($module['id']);
            if ($task) {
                $task->start_date = $startDate;
                $task->due_date = $endDate;
                $task->save(); // Save the task dates
                return response()->json(['error' => false, 'message' => 'Task dates updated successfully.']);
            } else {
                return response()->json(['error' => true, 'message' => 'Task not found.']);
            }
        } else {
            return response()->json(['error' => true, 'message' => 'Unknown module type.']);
        }
    }
    /**
     * Helper function to parse a date string
     *
     * @param string $dateString
     * @return Carbon|null
     */
    protected function parseDate($dateString)
    {
        // Remove timezone abbreviation and parse the date
        $dateString = preg_replace('/\s\([^)]+\)$/', '', $dateString);
        try {
            $date = Carbon::parse($dateString);
            return $date->format('Y-m-d'); // Format to 'YYYY-MM-DD'
        } catch (\Exception $e) {
            return null;
        }
    }
    // Updated mind_map function
    public function mind_map(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        $mindMapData = $this->getMindMapData($project);
        return view('projects.mind_map', compact('mindMapData', 'project'));
    }
    private function getMindMapData($project)
    {
        $mindMapData = [
            'meta' => [
                'name' => $project->title,
                'author' => $project->created_by,
                'version' => '1.0'
            ],
            'format' => 'node_tree', // Specify format if required by your jsMind version
            'data' => [
                'id' => 'project_' . $project->id,
                'topic' => $project->title,
                'isroot' => true,
                'level' => 1,
                'children' => [
                    [
                        'id' => 'tasks',
                        'topic' => 'Tasks',
                        'level' => 2,
                        'children' => $project->tasks->map(function ($task) {
                            return [
                                'id' => 'task_' . $task->id,
                                'topic' => $task->title,
                                'data' => [
                                    'media' => $task->media->map(function ($mediaItem) {
                                        $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;
                                        $fileUrl = $isPublicDisk
                                            ? asset('storage/project-media/' . $mediaItem->file_name)
                                            : $mediaItem->getFullUrl();
                                        return $fileUrl;
                                    })->toArray()
                                ]
                            ];
                        })->toArray()
                    ],
                    // [
                    //     'id' => 'comments',
                    //     'topic' => 'Comments',
                    //     'children' => $project->comments->map(function ($comment) {
                    //         return [
                    //             'id' => 'comment_' . $comment->id,
                    //             'topic' => $comment->content,
                    //             'children' => $comment->children->map(function ($reply) {
                    //                 return [
                    //                     'id' => 'reply_' . $reply->id,
                    //                     'topic' => $reply->content
                    //                 ];
                    //             })->toArray()
                    //         ];
                    //     })->toArray()
                    // ],
                    // [
                    //     'id' => 'milestones',
                    //     'topic' => 'Milestones',
                    //     'children' => $project->milestones->map(function ($milestone) {
                    //         return [
                    //             'id' => 'milestone_' . $milestone->id,
                    //             'topic' => $milestone->title
                    //         ];
                    //     })->toArray()
                    // ],
                    [
                        'id' => 'media',
                        'topic' => 'Media',
                        'children' => $project->media->map(function ($mediaItem) {
                            $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;
                            $fileUrl = $isPublicDisk
                                ? asset('storage/project-media/' . $mediaItem->file_name)
                                : $mediaItem->getFullUrl();
                            return [
                                'id' => 'media_' . $mediaItem->id,
                                'topic' => $mediaItem->file_name,
                                'data' => [
                                    'url' => $fileUrl
                                ]
                            ];
                        })->toArray()
                    ],
                    [
                        'id' => 'users',
                        'topic' => 'Users',
                        'children' => $project->users->map(function ($user) {
                            return [
                                'id' => 'user_' . $user->id,
                                'topic' => $user->first_name . ' ' . $user->last_name
                            ];
                        })->toArray()
                    ],
                    [
                        'id' => 'clients',
                        'topic' => 'Clients',
                        'children' => $project->clients->map(function ($client) {
                            return [
                                'id' => 'client_' . $client->id,
                                'topic' => $client->first_name . ' ' . $client->last_name
                            ];
                        })->toArray()
                    ]
                ]
            ]
        ];
        return $mindMapData;
    }
    public function export_mindmap(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        $imageData = $request->input('imageData');
        // Generate PDF
        $pdf = PDF::loadView('projects.pdf_mind_map', compact('imageData', 'project'));
        return $pdf->download('mind_map_' . $project->id . '.pdf');
    }
    public function get_users(Request $request)
    {
        // Get mention_id and mention_type from the request
        $mentionId = $request->get('mention_id');
        $mentionType = $request->get('mention_type');
        $query = $request->get('search', '');
        // dd($mentionId, $mentionType, $query);
        // Initialize users query
        $users = User::query();
        // Apply relationship based on mention_type
        switch ($mentionType) {
            case 'project':
                $users->whereHas('projects', function ($q) use ($mentionId) {
                    $q->where('projects.id', $mentionId);
                });
                break;
            case 'task':
                $users->whereHas('tasks', function ($q) use ($mentionId) {
                    $q->where('tasks.id', $mentionId);
                });
                break;
            case 'workspace':
                $users->whereHas('workspaces', function ($q) use ($mentionId) {
                    $q->where('workspaces.id', $mentionId);
                });
                break;
            default:
                return response()->json(['error' => 'Invalid mention_type'], 400);
        }
        // Apply search filter for first_name
        $users->where('first_name', 'like', '%' . $query . '%');
        // Fetch and map users
        $users = $users->get(['id', 'first_name', 'last_name'])->map(function ($user) {
            return [
                'key' => $user->id,
                'value' => $user->first_name . ' ' . $user->last_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];
        });
        // Return the users as JSON
        return response()->json($users);
    }
}
