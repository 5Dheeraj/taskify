<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use Illuminate\Support\Facades\Log;

class IssueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        // Start try-catch block for error handling
        try {
            // Validate the incoming request data
            $formFields = $request->validate([
                'title' => 'required|max:256',
                'description' => 'required|max:512',
                'status' => 'required|in:open,in_progress,resolved,closed',
                'assignee_id' => 'nullable|array',  // Make sure assignee_id is an array if provided
                'assignee_id.*' => 'exists:users,id' // Ensure each assignee_id is a valid user ID
            ]);

            // Add the user who created the issue
            $formFields['created_by'] = getAuthenticatedUser()->id;

            // Create the new issue associated with the project
            $issue = $project->issues()->create($formFields);

            // If assignee_ids are provided, attach them to the issue
            $assignee_ids = $request->assignee_id;
            if ($assignee_ids) {
                // Attach the assignees to the issue
                $issue->users()->attach($assignee_ids);
            }
            $notification_data = [
                'type' => 'project_issue',
                'type_id' => $issue->id,
                'type_title' => $issue->title,
                'status' => ucwords(str_replace('_', ' ', $issue->status)),
                'creator_first_name' => ucwords($issue->creator->first_name),
                'creator_last_name' => ucwords($issue->creator->last_name),
                'access_url' => 'projects/information/' . $project->id,
                'action' => 'assigned'
            ];
            $recipients = array_merge(
                array_map(function ($assignee_ids) {
                    return 'u_' . $assignee_ids;
                }, $assignee_ids)
            );

            processNotifications($notification_data, $recipients);

            // Return success response with the issue data
            return response()->json([
                'error' => false,
                'message' => 'Issue created successfully.',
                'issue' => $issue
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors (incorrect input, missing fields, etc.)
            return response()->json([
                'error' => true,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-related errors, like issues creating or attaching assignees
            return response()->json([
                'error' => true,
                'message' => 'Database error occurred while creating the issue.',
                'details' => $e->getMessage(),  // Optionally include details for debugging
            ], 500);
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            return response()->json([
                'error' => true,
                'message' => 'An unexpected error occurred. Please try again later.',
                'details' => $e->getMessage(),  // Optionally include details for debugging
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project, Issue $issue)
    {
        try {
            // Ensure the issue belongs to the project
            if ($issue->project_id !== $project->id) {
                return response()->json(['error' => true, 'message' => 'Issue does not belong to the specified project.'], 403);
            }
            $assigneeIds = $issue->users->pluck('id')->toArray();
            return response()->json(['error' => false, 'issue' => $issue, 'assignee_ids' => $assigneeIds], 200);
        } catch (\Throwable $th) {
            // Log the error for debugging
            Log::error('Error editing issue: ' . $th->getMessage());

            return response()->json(['error' => true, 'message' => 'An unexpected error occurred.'], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Project $project, Request $request)
    {
        // Start a try-catch block for error handling
        // Validate the incoming request data
        $formFields = $request->validate([
            'id' => 'required|exists:issues,id',
            'title' => 'required|max:256',
            'description' => 'required|max:512',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'assignee_id' => 'nullable|array',  // Make sure assignee_id is an array if provided
            'assignee_id.*' => 'exists:users,id' // Ensure each assignee_id is a valid user ID
        ]);
        try {

            // Find the issue or throw an exception if not found
            $issue = Issue::findOrFail($formFields['id']);

            // Update the issue's attributes
            $issue->title = $formFields['title'];
            $issue->description = $formFields['description'];
            $issue->status = $formFields['status'];

            // Save the updated issue
            $issue->save();

            // If assignee_ids are provided, sync the users
            $assignee_ids = $formFields['assignee_id'] ?? null;
            if ($assignee_ids) {
                // Ensure the assignees are valid and sync them with the issue
                $issue->users()->sync($assignee_ids);
            }

            // Return success response
            return response()->json([
                'error' => false,
                'message' => 'Issue updated successfully.',
                'issue' => $issue
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found (issue not found)
            return response()->json([
                'error' => true,
                'message' => 'Issue not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'error' => true,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'error' => true,
                'message' => 'An unexpected error occurred. Please try again later.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, Issue $id)
    {
        $response = DeletionService::delete(Issue::class, $id->id, 'Issue');
        return $response;
    }
    public function destroy_multiple(Request $request, Project $id)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:issues,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $deletedIds = [];
        $deletedTitles = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $issue = Issue::findOrFail($id);
            $deletedIds[] = $id;
            $deletedTitles[] = $issue->title;
            DeletionService::delete(Issue::class, $id, 'Issue');
        }
        return response()->json(['error' => false, 'message' => 'Issue(s) deleted successfully.', 'id' => $deletedIds, 'titles' => $deletedTitles, 'type' => 'issue']);
    }
    public function list(Request $request, $id = '', $type = '')
    {
        $search = $request->input('search', '');
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'DESC');
        $status = $request->input('status', '');
        $assigned_to = $request->input('assigned_to', '');
        $created_by = $request->input('created_by', '');
        $start_date = $request->input('start_date', '');
        $end_date = $request->input('end_date', '');
        $limit = $request->input('limit', 10);

        // Initialize issues query
        $issuesQuery = Issue::query();

        // Filter by project ID if provided
        if ($id) {
            $issuesQuery->where('project_id', $id);
        }

        // Apply additional filters
        if ($status) {
            $issuesQuery->where('status', $status);
        }
        if ($assigned_to) {
            $issuesQuery->where('assigned_to', $assigned_to);
        }
        if ($created_by) {
            $issuesQuery->where('created_by', $created_by);
        }
        if ($start_date && $end_date) {
            $issuesQuery->whereBetween('created_at', [$start_date, $end_date]);
        }

        // Apply search filter
        if ($search) {
            $issuesQuery->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('status', 'LIKE', "%{$search}%")
                ->orWhereHas('users', function ($query) use ($search) {
                    $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
                })
                    ->orWhereHas('creator', function ($query) use ($search) {
                        $query->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Get total issues count before applying pagination
        $totalIssues = $issuesQuery->count();

        // Apply sorting and pagination
        $issues = $issuesQuery
            ->orderBy($sort, $order)
            ->paginate($limit)
            ->through(function ($issue) {
                $userHtml = '';
                $userHtml .= '<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">';
            if ($issue->users->count() > 0) {
                foreach ($issue->users as $user) {
                    $userHtml .= "<li class='avatar avatar-sm pull-up'><a href='" . route('users.show', ['id' => $user->id]) . "' target='_blank' title='{$user->first_name} {$user->last_name}'><img src='" . ($user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></a></li>";
                }
            } else {
                $userHtml .= "<li class=''><a href='#' title='No assignees'><span class='fw-semibold'>No Assignees</span></a></li>";
            }
                $userHtml .= '</ul>';
                $createdByHtml = '<ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">';
                $createdByHtml .= "<li class='avatar avatar-sm pull-up'><a href='" . route('users.show', ['id' => $issue->creator->id]) . "' target='_blank' title='{$issue->creator->first_name} {$issue->creator->last_name}'><img src='" . ($issue->creator->photo ? asset('storage/' . $issue->creator->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></a></li>";
                $createdByHtml .= '</ul>';

                return [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'description' => Str::limit($issue->description, 512),
                'status' => $this->generateIssueStatus($issue->status),
                'users' => $userHtml,
                    'created_by' => $createdByHtml,
                    'created_at' => format_date($issue->created_at),
                    'updated_at' => format_date($issue->updated_at),
                    'actions' => $this->generateIssueActions($issue),
                ];
            });

        return response()->json([
            'rows' => $issues->items(),
            'total' => $totalIssues,
        ]);
    }

    /**
     * Generate action buttons for an issue.
     */
    private function generateIssueActions($issue)
    {
        $actions = '';
        $actions .= '<a href="javascript:void(0);" class="edit-project-issue" data-project-id ="' . $issue->project->id . '"
        data-id="' . $issue->id . '" title="' . get_label('update', 'Update') . '">' .
            '<i class="bx bx-edit mx-1"></i>' .
            '</a>';
        $actions .= '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $issue->id . '" data-type="projects/' . $issue->project->id . '/issues" data-table="project_issue_table">' .
            '<i class="bx bx-trash text-danger mx-1"></i>' .
            '</button>';

        return $actions;
    }

    /**
     * Generates a HTML badge for an issue status.
     *
     * @param string $status Issue status.
     *
     * @return string HTML badge.
     */
    public function generateIssueStatus($status)
    {
        switch ($status) {
            case 'open':
                $status = '<span class="badge bg-label-primary">' . get_label('open', 'Open') . '</span>';
                break;
            case 'in_progress':
                $status = '<span class="badge bg-label-info">' . get_label('in_progress', 'In Progress') . '</span>';
                break;
            case 'resolved':
                $status = '<span class="badge bg-label-success">' . get_label('resolved', 'Resolved') . '</span>';
                break;
            case 'closed':
                $status = '<span class="badge bg-label-danger">' . get_label('closed', 'Closed') . '</span>';
                break;
            default:
                $status = '<span class="badge bg-label-danger">' . get_label('unknown', 'Unknown') . '</span>';
                break;
        }
        return $status;
    }
}