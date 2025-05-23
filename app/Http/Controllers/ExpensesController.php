<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Workspace;
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
class ExpensesController extends Controller
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
    public function index(Request $request)
    {
        $expenses = $this->workspace->expenses();
        if (!isAdminOrHasAllDataAccess()) {
            $expenses = $expenses->where(function ($query) {
                $query->where('expenses.created_by', isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id)
                    ->orWhere('expenses.user_id', $this->user->id);
            });
        }
        $expenses = $expenses->count();
        $expense_types = $this->workspace->expense_types;
        $users = $this->workspace->users;
        return view('expenses.list', ['expenses' => $expenses, 'expense_types' => $expense_types, 'users' => $users]);
    }
    public function expense_types(Request $request)
    {
        $expense_types = $this->workspace->expense_types();
        $expense_types = $expense_types->count();
        return view('expenses.expense_types', ['expense_types' => $expense_types]);
    }
    public function store(Request $request)
    {
        // Validate the request data
        $formFields = $request->validate([
            'title' => 'required|unique:expenses,title', // Validate the title
            'expense_type_id' => 'required',
            'user_id' => 'required',
            'amount' => ['required', 'regex:/^\d+(\.\d+)?$/'],
            'expense_date' => 'required',
            'note' => 'nullable'
        ], [
            'user_id.required' => 'The user field is required.',
            'expense_type_id.required' => 'The expense type field is required.'
        ]);
        $expense_date = $request->input('expense_date');
        $formFields['expense_date'] = format_date($expense_date, false, app('php_date_format'), 'Y-m-d');
        $formFields['workspace_id'] = $this->workspace->id;
        $formFields['created_by'] = isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id;
        if ($exp = Expense::create($formFields)) {
            return response()->json(['error' => false, 'message' => 'Expense created successfully.', 'id' => $exp->id]);
        } else {
            return response()->json(['error' => true, 'message' => 'Expense couldn\'t created.']);
        }
    }
    public function store_expense_type(Request $request)
    {
        // Validate the request data
        $formFields = $request->validate([
            'title' => 'required|unique:expense_types,title', // Validate the type
            'description' => 'nullable'
        ]);
        $formFields['workspace_id'] = $this->workspace->id;
        if ($et = ExpenseType::create($formFields)) {
            Session::flash('message', 'Expense type created successfully.');
            return response()->json(['error' => false, 'message' => 'Expense type created successfully.', 'id' => $et->id, 'title' => $et->type, 'type' => 'expense_type']);
        } else {
            return response()->json(['error' => true, 'message' => 'Expense type couldn\'t created.']);
        }
    }
    public function list()
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $type_id = (request('type_id')) ? request('type_id') : "";
        $user_id = (request('user_id')) ? request('user_id') : "";
        $exp_date_from = (request('date_from')) ? request('date_from') : "";
        $exp_date_to = (request('date_to')) ? request('date_to') : "";
        $where = ['expenses.workspace_id' => $this->workspace->id];
        $expenses = Expense::select(
            'expenses.*',
            DB::raw('CONCAT(users.first_name, " ", users.last_name) AS user_name'),
            'expense_types.title as expense_type'
        )
            ->leftJoin('users', 'expenses.user_id', '=', 'users.id')
        ->leftJoin('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id');
        if (!isAdminOrHasAllDataAccess()) {
            $expenses = $expenses->where(function ($query) {
                $query->where('expenses.created_by', isClient() ? 'c_' . $this->user->id : 'u_' . $this->user->id)
                    ->orWhere('expenses.user_id', $this->user->id);
            });
        }
        if ($type_id) {
            $where['expense_type_id'] = $type_id;
        }
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        if ($exp_date_from && $exp_date_to) {
            $expenses = $expenses->whereBetween('expenses.expense_date', [$exp_date_from, $exp_date_to]);
        }
        if ($search) {
            $expenses = $expenses->where(function ($query) use ($search) {
                $query->where('expenses.title', 'like', '%' . $search . '%')
                    ->orWhere('amount', 'like', '%' . $search . '%')
                    ->orWhere('expenses.note', 'like', '%' . $search . '%')
                    ->orWhere('expenses.id', 'like', '%' . $search . '%');
            });
        }
        $expenses->where($where);
        $total = $expenses->count();
        $canCreate = checkPermission('create_expenses');
        $canEdit = checkPermission('edit_expenses');
        $canDelete = checkPermission('delete_expenses');
        $expenses = $expenses->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(function ($expense) use ($canEdit, $canDelete, $canCreate) {
                if (strpos($expense->created_by, 'u_') === 0) {
                    // The ID corresponds to a user
                    $creator = User::find(substr($expense->created_by, 2)); // Remove the 'u_' prefix
                } elseif (strpos($expense->created_by, 'c_') === 0) {
                // The ID corresponds to a client
                $creator = Client::find(substr($expense->created_by, 2)); // Remove the 'c_' prefix
                }
                if ($creator !== null) {
                    $creator = $creator->first_name . ' ' . $creator->last_name;
                } else {
                    $creator = '-';
            }
            $actions = '';
            if ($canEdit) {
                $actions .= '<a href="javascript:void(0);" class="edit-expense" data-bs-toggle="modal" data-id="' . $expense->id . '" title="' . get_label('update', 'Update') . '" class="card-link"><i class="bx bx-edit mx-1"></i></a>';
            }
            if ($canDelete) {
                $actions .= '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $expense->id . '" data-type="expenses">' .
                '<i class="bx bx-trash text-danger mx-1"></i>' .
                '</button>';
            }
            if ($canCreate) {
                $actions .= '<a href="javascript:void(0);" class="duplicate" data-id="' . $expense->id . '" data-title="' . $expense->title . '" data-type="expenses" title="' . get_label('duplicate', 'Duplicate') . '">' .
                '<i class="bx bx-copy text-warning mx-2"></i>' .
                '</a>';
            }
            $actions = $actions ?: '-';
                return [
                    'id' => $expense->id,
                    'user_id' => $expense->user_id,
                    'user' => $expense->user_name,
                    'title' => $expense->title,
                    'expense_type_id' => $expense->expense_type_id,
                    'expense_type' => $expense->expense_type,
                    'amount' => format_currency($expense->amount),
                    'expense_date' => format_date($expense->expense_date),
                    'note' => $expense->note,
                    'created_by' => $creator,
                'created_at' => format_date($expense->created_at, true),
                'updated_at' => format_date($expense->updated_at, true),
                'actions' => $actions
                ];
            });
        return response()->json([
            "rows" => $expenses->items(),
            "total" => $total,
        ]);
    }
    public function expense_types_list()
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $expense_types = $this->workspace->expense_types();
        if ($search) {
            $expense_types = $expense_types->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }
        $total = $expense_types->count();
        $expense_types = $expense_types->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(
                fn ($expense_types) => [
                    'id' => $expense_types->id,
                    'title' => $expense_types->title,
                    'description' => $expense_types->description,
                'created_at' => format_date($expense_types->created_at),
                'updated_at' => format_date($expense_types->updated_at),
                ]
            );
        return response()->json([
            "rows" => $expense_types->items(),
            "total" => $total,
        ]);
    }
    public function get($id)
    {
        $exp = Expense::findOrFail($id);
        return response()->json(['exp' => $exp]);
    }
    public function get_expense_type($id)
    {
        $et = ExpenseType::findOrFail($id);
        return response()->json(['et' => $et]);
    }
    public function update(Request $request)
    {
        // Validate the request data
        $formFields = $request->validate([
            'id' => 'required',
            'title' => 'required|unique:expenses,title,' . $request->id,
            'expense_type_id' => 'required',
            'user_id' => 'required',
            'amount' => ['required', 'regex:/^\d+(\.\d+)?$/'],
            'expense_date' => 'required',
            'note' => 'nullable'
        ], [
            'user_id.required' => 'The user field is required.',
            'expense_type_id.required' => 'The expense type field is required.'
        ]);
        $expense_date = $request->input('expense_date');
        $formFields['expense_date'] = format_date($expense_date, false, app('php_date_format'), 'Y-m-d');
        $exp = Expense::findOrFail($request->id);
        if ($exp->update($formFields)) {
            return response()->json(['error' => false, 'message' => 'Expense updated successfully.', 'id' => $exp->id]);
        } else {
            return response()->json(['error' => true, 'message' => 'Expense couldn\'t updated.']);
        }
    }
    public function update_expense_type(Request $request)
    {
        $formFields = $request->validate([
            'id' => ['required'],
            'title' => 'required|unique:expense_types,title,' . $request->id,
            'description' => 'nullable',
        ]);
        $et = ExpenseType::findOrFail($request->id);
        if ($et->update($formFields)) {
            return response()->json(['error' => false, 'message' => 'Expense type updated successfully.', 'id' => $et->id, 'type' => 'expense_type']);
        } else {
            return response()->json(['error' => true, 'message' => 'Expense type couldn\'t updated.']);
        }
    }
    public function destroy($id)
    {
        $exp = Expense::findOrFail($id);
        DeletionService::delete(Expense::class, $id, 'Expense');
        return response()->json(['error' => false, 'message' => 'Expense deleted successfully.', 'id' => $id, 'title' => $exp->title]);
    }
    public function delete_expense_type($id)
    {
        $et = ExpenseType::findOrFail($id);
        if ($et->expenses()->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'Cannot delete this expense type as it is associated with one or more expenses.'
            ]);
        }
        DeletionService::delete(ExpenseType::class, $id, 'Expense type');
        return response()->json(['error' => false, 'message' => 'Expense type deleted successfully.', 'id' => $id, 'title' => $et->title, 'type' => 'expense_type']);
    }
    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:expenses,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $deletedIds = [];
        $deletedTitles = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $exp = Expense::findOrFail($id);
            $deletedIds[] = $id;
            $deletedTitles[] = $exp->title;
            DeletionService::delete(Expense::class, $id, 'Expense');
        }
        return response()->json(['error' => false, 'message' => 'Expense(s) deleted successfully.', 'id' => $deletedIds, 'titles' => $deletedTitles, 'type' => 'expense']);
    }
    public function delete_multiple_expense_type(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:expense_types,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $assignedExpenseTypes = [];
        $deletableExpenseTypes = [];
        foreach ($ids as $id) {
            $et = ExpenseType::findOrFail($id);
            // Check if the expense type is associated with any expenses
            if ($et->expenses()->exists()) {
                $assignedExpenseTypes[] = $et->title;
            } else {
                // Add to deletable list if not assigned to any expense
                $deletableExpenseTypes[] = $et;
            }
        }
        // If there are assigned expense types, return an error message
        if (count($assignedExpenseTypes) > 0) {
            return response()->json([
                'error' => true,
                'message' => 'The following expense type(s) are assigned to expenses and cannot be deleted: ' . implode(', ', $assignedExpenseTypes),
            ]);
        }
        // Proceed with deletion of deletable expense types
        foreach ($deletableExpenseTypes as $expenseType) {
            try {
                DeletionService::delete(ExpenseType::class, $expenseType->id, 'Expense type');
            } catch (\Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => 'An error occurred while deleting expense types: ' . $e->getMessage(),
                ], 500);
            }
        }
        // If no errors, return success message
        return response()->json([
            'error' => false,
            'message' => 'Expense type(s) deleted successfully.',
        ]);
    }
    public function duplicate($id)
    {
        // Use the general duplicateRecord function
        $title = (request()->has('title') && !empty(trim(request()->title))) ? request()->title : '';
        $duplicated = duplicateRecord(Expense::class, $id, [], $title);
        if (!$duplicated) {
            return response()->json(['error' => true, 'message' => 'Expense duplication failed.']);
        }
        if (request()->has('reload') && request()->input('reload') === 'true') {
            Session::flash('message', 'Expense duplicated successfully.');
        }
        return response()->json(['error' => false, 'message' => 'Expense duplicated successfully.', 'id' => $id]);
    }
}