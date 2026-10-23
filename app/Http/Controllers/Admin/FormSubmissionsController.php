<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormSubmission;
use App\Models\Brand;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;

class FormSubmissionsController extends Controller
{
    /**
     * Show the Form Submissions page.
     */
    public function index()
    {
        // Brand dropdown for filter UI – data fetched via AJAX for the table.
        $brands = Brand::when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))->pluck('name', 'id');
        return view('admin.form-submissions.index', compact('brands'));
    }

    /**
     * DataTables endpoint – returns JSON for the submissions table.
     */
    public function getFormSubmissions(Request $request)
    {
        $user_id = auth()->user()->id;
        $user = User::with('auth_brands')->where('id', $user_id)->first();
        $brand_ids = $user->auth_brands->pluck('id')->toArray();

        if (auth()->user()->company_id) {
            $companyBrandIds = Brand::where('company_id', auth()->user()->company_id)->pluck('id');
            $query = FormSubmission::with('brand')->whereIn('brand_id', $companyBrandIds)->latest();
        } elseif (!auth()->user()->isAdministrator()) {
            $query = FormSubmission::with('brand')->whereIn('brand_id', $brand_ids)->latest();
        } else {
            $query = FormSubmission::with('brand')->latest();
        }

        // Filters (same as previous implementation)
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('form_action')) {
            $query->where('form_action', 'like', '%' . $request->form_action . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('brand', function (FormSubmission $row) {
                return optional($row->brand)->name ?? '-';
            })
            ->addColumn('actions', function (FormSubmission $row) {
                return view('admin.form-submissions.partials.actions', ['submission' => $row])->render();
            })
            ->editColumn('created_at', function (FormSubmission $row) {
                return $row->created_at->format('Y-m-d H:i');
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show a single form submission.
     */
    public function show($id)
    {
        $submission = FormSubmission::with('brand')
            ->when(auth()->user()->company_id, function ($query, $companyId) {
                $query->whereHas('brand', fn ($brands) => $brands->where('company_id', $companyId));
            })
            ->findOrFail($id);
        return view('admin.form-submissions.show', compact('submission'));
    }
}

