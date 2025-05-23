<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use Illuminate\Support\Facades\Session;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('status.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('status.create');
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
            'color' => ['required']
        ]);
        $slug = generateUniqueSlug($request->title, Status::class);
        $formFields['slug'] = $slug;
        $formFields['admin_id'] = $adminId;

        $roleIds = $request->input('role_ids');
        if ($status = Status::create($formFields)) {
            $status->roles()->attach($roleIds);
            return response()->json(['error' => false, 'message' => 'Status created successfully.', 'id' => $status->id, 'status' => $status]);
        } else {
            return response()->json(['error' => true, 'message' => 'Status couldn\'t created.']);
        }
    }

    public function list()
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $status = Status::orderBy($sort, $order);
        $adminId = getAdminIdByUserRole();
        $status->where('admin_id', $adminId);
        if ($search) {
            $status = $status->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }
        $total = $status->count();
        $status = $status
            ->paginate(request("limit"))
            ->through(function ($status) {
                $roles = $status->roles->pluck('name')->map(function ($roleName) {
                    return ucfirst($roleName);
                })->implode(', ');
                return [
                    'id' => $status->id,
                    'title' => $status->title,
                'roles_has_access' => $roles ?: ' - ',
                    'color' => '<span class="badge bg-' . $status->color . '">' . $status->title . '</span>',
                    'created_at' => format_date($status->created_at, true),
                    'updated_at' => format_date($status->updated_at, true),
                ];
            });


        return response()->json([
            "rows" => $status->items(),
            "total" => $total,
        ]);
    }

    public function get($id)
    {
        $status = Status::findOrFail($id);
        $roles = $status->roles()->pluck('id')->toArray();
        return response()->json(['status' => $status, 'roles' => $roles]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
            'id' => ['required'],
            'title' => ['required'],
            'color' => ['required']
        ]);
        $slug = generateUniqueSlug($request->title, Status::class, $request->id);
        $formFields['slug'] = $slug;
        $status = Status::findOrFail($request->id);

        if ($status->update($formFields)) {
            $roleIds = $request->input('role_ids');
            $status->roles()->sync($roleIds);
            return response()->json(['error' => false, 'message' => 'Status updated successfully.', 'id' => $status->id]);
        } else {
            return response()->json(['error' => true, 'message' => 'Status couldn\'t updated.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = Status::findOrFail($id);
        if ($status->projects()->count() > 0 ||  $status->tasks()->count() > 0) {

            return response()->json(['error' => true, 'message' => 'Status can\'t be deleted.It is associated with a project or task.']);
        } else {

            $response = DeletionService::delete(Status::class, $id, 'Status');
            return $response;
        }
    }

    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:statuses,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);

        $ids = $validatedData['ids'];
        $deletedIds = [];
        $deletedTitles = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $status = Status::findOrFail($id);
            if ($status->projects()->count() > 0 ||  $status->tasks()->count() > 0) {
                return response()->json(['error' => true, 'message' => 'Status can\'t be deleted.It is associated with a project']);
            } else {
                $deletedIds[] = $id;
                $deletedTitles[] = $status->title;
                DeletionService::delete(Status::class, $id, 'Status');
            }
        }
        return response()->json(['error' => false, 'message' => 'Status(es) deleted successfully.', 'id' => $deletedIds, 'titles' => $deletedTitles]);
    }
    public function search(Request $request)
    {
        $query = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = 10;

        // If there is no query, return the first set of statuses
        $statuses = Status::where('admin_id', getAdminIDByUserRole())
            ->orWhere(function ($query) {
                $query->whereNull('admin_id')
                    ->where('is_default', 1);
            })
            ->when($query, function ($queryBuilder) use ($query) {
                $queryBuilder->where('title', 'like', '%' . $query . '%');
            })
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'title']);

        $statuses = $statuses->unique('id');
        // Prepare response for Select2
        $results = $statuses->map(function ($status) {
            return ['id' => $status->id, 'text' => $status->title];
        });

        // Flag for more results
        $pagination = ['more' => $statuses->count() === $perPage];

        return response()->json([
            'items' => $results,
            'pagination' => $pagination
        ]);
    }
}