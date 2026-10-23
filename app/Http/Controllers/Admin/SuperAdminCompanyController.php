<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Company;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminCompanyController extends Controller
{
    public function index(): View
    {
        return view('admin.super-admin.companies.index', [
            'companies' => Company::query()
                ->with(['users' => fn ($users) => $users->where('role', 1)->orderBy('id')])
                ->withCount([
                    'users as team_members_count' => fn ($users) => $users->whereIn('role', [1, 2]),
                    'brands',
                    'visitors',
                ])
                ->latest()->paginate(15),
            'companyCount' => Company::count(),
            'activeCount' => Company::where('is_active', true)->count(),
            'inactiveCount' => Company::where('is_active', false)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.super-admin.companies.form', ['company' => new Company()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCompany($request);

        $company = DB::transaction(function () use ($validated) {
            $admin = [
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => $validated['admin_password'],
                'role' => 1,
            ];
            unset($validated['admin_name'], $validated['admin_email'], $validated['admin_password']);

            $company = Company::create($validated);
            $company->users()->create($admin);

            return $company;
        });

        return redirect()->route('admin.super-admin.companies.show', $company)
            ->with('success', 'Company and its Admin account were created successfully.');
    }

    public function show(Company $company): View
    {
        $company->loadCount([
            'users as team_members_count' => fn ($users) => $users->whereIn('role', [1, 2]),
            'brands',
            'visitors',
        ]);
        $members = $company->users()->whereIn('role', [1, 2])->latest()->paginate(15);
        $brands = $company->brands()->withCount(['chats', 'formSubmissions'])->latest()->get();
        $brandIds = $brands->pluck('id');
        $chatCount = (int) $brands->sum('chats_count');
        $recentSubmissions = FormSubmission::with('brand')
            ->whereIn('brand_id', $brandIds)->latest()->take(10)->get();

        return view('admin.super-admin.companies.show', compact(
            'company', 'members', 'brands', 'chatCount', 'recentSubmissions'
        ));
    }

    public function impersonate(Request $request, Company $company, User $member): RedirectResponse
    {
        abort_unless($company->is_active, 403, 'This company is inactive.');
        abort_unless((int) $member->company_id === (int) $company->id && in_array((int) $member->role, [1, 2], true), 404);

        $request->session()->put([
            'super_admin_impersonator_id' => auth()->id(),
            'super_admin_company_id' => $company->id,
            'super_admin_impersonated_user_id' => $member->id,
        ]);
        Cookie::queue(cookie('super_admin_impersonator_id', (string) auth()->id(), 120));

        Auth::login($member);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'You are now viewing the company as ' . $member->isRole() . '.');
    }

    public function switchImpersonatedUser(Request $request, User $member): RedirectResponse
    {
        // The original id is session-bound and was set by the Super Admin-only entry route.
        $superAdmin = User::find($request->session()->get('super_admin_impersonator_id'));
        $companyId = $request->session()->get('super_admin_company_id');
        $company = $companyId ? Company::find($companyId) : null;

        abort_unless($superAdmin && $company && $company->is_active, 403);
        abort_unless((int) auth()->user()->company_id === (int) $company->id, 403);
        abort_unless((int) $member->company_id === (int) $company->id && (int) $member->role === 2, 404);

        $request->session()->put('super_admin_impersonated_user_id', $member->id);
        Auth::login($member);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'You are now viewing the company as Agent.');
    }

    public function visitors(Company $company): View
    {
        return view('admin.super-admin.companies.partials.visitors', [
            'visitors' => $company->visitors()
                ->with(['brand', 'user'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function conversations(Company $company): View
    {
        $brandIds = $company->brands()->select('id');

        return view('admin.super-admin.companies.partials.conversations', [
            'conversations' => Chat::query()
                ->with(['brand', 'main_visitor.user'])
                ->whereIn('brand_id', $brandIds)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function edit(Company $company): View
    {
        return view('admin.super-admin.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $this->validateCompany($request, $company);
        unset($validated['admin_name'], $validated['admin_email'], $validated['admin_password']);
        $company->update($validated);

        return redirect()->route('admin.super-admin.companies.show', $company)
            ->with('success', 'Company details were updated.');
    }

    public function toggle(Company $company): RedirectResponse
    {
        $company->update(['is_active' => !$company->is_active]);

        return back()->with('success', $company->is_active
            ? 'Company activated successfully.'
            : 'Company deactivated successfully.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()->route('admin.super-admin.companies.index')
            ->with('success', 'Company deleted. Its user, brand and visitor records are unassigned, not deleted.');
    }

    private function validateCompany(Request $request, ?Company $company = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/i', Rule::unique('companies', 'domain')->ignore($company?->id)],
            'subscription_plan' => ['required', 'in:starter,growth,business,enterprise'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if (!$company) {
            $rules += [
                'admin_name' => ['required', 'string', 'max:255'],
                'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'admin_password' => ['required', 'string', 'min:8'],
            ];
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $company ? $request->boolean('is_active') : true;

        return $validated;
    }
}
