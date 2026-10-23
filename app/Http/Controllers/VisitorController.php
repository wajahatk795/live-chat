<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Visitor;
use App\Models\Website;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class VisitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.visitor.index');
    }

    /**
     * Get data for DataTables.
     */
    public function getdata()
    {
        $visitors = Visitor::with(['user', 'brand'])
            ->when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->select(['id', 'brand_id', 'company_id', 'created_at']);

        return DataTables::of($visitors)
            ->addIndexColumn()
            ->addColumn('brand_name', function ($visitor) {
                return $visitor->brand->name ?? '<span class="text-muted">N/A</span>';
            })
            ->addColumn('email', function ($visitor) {
                return $visitor->user->email ?? '<span class="text-muted">N/A</span>';
            })
            ->addColumn('phone', function ($visitor) {
                return $visitor->user->phone ?? '<span class="text-muted">N/A</span>';
            })
            ->addColumn('actions', function ($visitor) {
                $editAction = (int) auth()->user()->role === 2
                    ? '<a class="dropdown-item" href="' . route('admin.visitor.edit', $visitor->id) . '">
                            <i class="bx bx-edit me-2"></i> Edit
                        </a>'
                    : '';

                return '
                <div class="dropdown">
                    <button type="button"
                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow"
                        data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        ' . $editAction . '
                        <form action="' . route('admin.visitor.destroy', $visitor->id) . '" method="POST" style="display:inline;">
                            <input type="hidden" name="_token" value="' . csrf_token() . '">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm(\'Are you sure?\')">
                                <i class="bx bx-trash me-2"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            ';
            })
            ->rawColumns(['brand_name', 'email', 'phone', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_unless((int) auth()->user()->role === 2, 403, 'Administrators can view visitor profiles but cannot update them.');

        // Sirf visitor ka data lao with user
        $visitor = Visitor::with(['user'])->when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))->findOrFail($id);

        return view('admin.visitor.edit', compact('visitor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        abort_unless((int) auth()->user()->role === 2, 403, 'Administrators can view visitor profiles but cannot update them.');

        $visitor = Visitor::with('user')->when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $visitor->user->id
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],
            'address' => [
                'nullable',
                'string'
            ],
            'about' => [
                'nullable',
                'string'
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048'
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // User model find karo
            $user = User::findOrFail($visitor->user->id);

            // Image upload handling
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->image && file_exists(public_path($user->image))) {
                    unlink(public_path($user->image));
                }

                // Upload new image
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Create directory if not exists
                if (!file_exists(public_path('uploads/users'))) {
                    mkdir(public_path('uploads/users'), 0777, true);
                }

                $file->move(public_path('uploads/users'), $filename);
                $user->image = 'uploads/users/' . $filename;
            }

            // Update user fields
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->about = $request->about;
            $user->save();

            return redirect()->route('admin.visitor')
                ->with('success', 'User updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error updating user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
        $visitor = Visitor::when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))->findOrFail($id);
            $visitor->delete();

            return redirect()->route('admin.visitor')
                ->with('success', 'Visitor deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.visitor')
                ->with('error', 'Error deleting visitor: ' . $e->getMessage());
        }
    }
}
