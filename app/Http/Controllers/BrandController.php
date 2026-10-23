<?php

namespace App\Http\Controllers;

use App\Jobs\TrainBrandAiJob;
use App\Models\Brand;
use App\Models\BrandUser;
use App\Models\ChatSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brand = $this->brandsQuery()->with('user')->get();
        foreach ($brand as $item) {
            $item->logo = asset($item->logo);
        }
        return view('admin.brand.index', compact('brand'));
    }

    public function getdata()
    {
        $brand = $this->brandsQuery()->select(['id', 'domain', 'url', 'status']);

        return DataTables::of($brand)
            ->addIndexColumn()

            ->addColumn('status', function ($brand) {

                $checked = $brand->status == 1 ? 'checked' : '';

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox"
                            class="form-check-input toggle-status"
                            data-id="' . $brand->id . '"
                            ' . $checked . '>
                    </div>
                ';
            })

            ->addColumn('actions', function ($brand) {
                return view('admin.brand.partials.actions', compact('brand'))->render();
            })

            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function changeStatus(Request $request)
    {
        $brand = $this->brandsQuery()->findOrFail($request->id);

        $brand->status = $request->status;
        $brand->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    }

    public function create()
    {
        $users = User::where('role', 2)->when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))->get();
        return view('admin.brand.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string', 'max:255', 'url'],
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!https?:\/\/)(?!www\.)([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/',
                'unique:brand,domain'
            ],
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {

            $verifyToken = Str::random(32);

            $brand = Brand::create(array_merge($validator->validated(), [
                'verify_token' => $verifyToken,
                'company_id' => auth()->user()->company_id,
            ]));

            DB::commit();

            // 🤖 Auto-train AI on website link
            TrainBrandAiJob::dispatch($brand);

            return redirect()->route('admin.brand.install', $brand->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function install($id)
    {
        $brand = $this->brandsQuery()->findOrFail($id);
        $script = $this->installScript($brand);

        return view('admin.brand.install', compact('brand', 'script'));
    }


    public function chatSettingsStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|integer|exists:brand,id',
            'chat_enabled' => 'required|in:0,1',
            'primary_color' => [
                'required',
                'string',
                'max:255',
                'regex:/^#[a-fA-F0-9]{6}$/'
            ],
            'popup_delay' => 'required|integer|min:0|max:3600',
            'sound_enabled' => 'required|in:0,1',
            'welcome_message' => 'nullable|string|max:500',
            'offline_message' => 'nullable|string|max:500',
            'chat_position' => 'required|string|in:left,right,top,bottom',
            'ai_enabled' => 'nullable|in:0,1',
            'ai_provider' => 'nullable|string',
            'ai_model' => 'nullable|string',
            'ai_prompt' => 'nullable|string',
            'website_knowledge' => 'nullable|string',
            'ai_handoff_delay' => 'nullable|integer|min:10|max:3600',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $this->brandsQuery()->findOrFail($request->brand_id);

        ChatSetting::updateOrCreate(
            ['brand_id' => $request->brand_id],
            [
                'chat_enabled' => $request->chat_enabled,
                'primary_color' => $request->primary_color,
                'popup_delay' => $request->popup_delay,
                'sound_enabled' => $request->sound_enabled,
                'welcome_message' => $request->welcome_message,
                'offline_message' => $request->offline_message,
                'chat_position' => $request->chat_position,
                'ai_enabled' => $request->ai_enabled ?? 0,
                'ai_provider' => $request->ai_provider ?? 'ollama',
                'ai_model' => $request->ai_model ?? 'llama3',
                'ai_prompt' => $request->ai_prompt,
                'website_knowledge' => $request->website_knowledge,
                'ai_handoff_delay' => $request->ai_handoff_delay ?? 60,
            ]
        );

        return redirect()->route('admin.brand')->with('success', 'Chat settings saved successfully!');
    }

    public function edit($id)
    {
        $brand = $this->brandsQuery()->findOrFail($id);

        // 🔥 Sirf visitor_id = 2 wale users
        $users = User::where('role', 2)->when(auth()->user()->company_id, fn ($query, $companyId) => $query->where('company_id', $companyId))->get();

        $selectedUserIds = DB::table('brand_users')
            ->where('brand_id', $brand->id)
            ->pluck('user_id')
            ->toArray();

        $chatSettings = ChatSetting::where('brand_id', $brand->id)->first();
        $script = $this->installScript($brand);

        return view('admin.brand.edit', compact(
            'brand',
            'users',
            'selectedUserIds',
            'chatSettings',
            'script'
        ));
    }

    private function installScript(Brand $brand): string
    {
        $scriptUrl = rtrim(config('app.url'), '/') . '/widget/widget.js';

        return '<!--Start of Live Chat -->
    <script src="' . $scriptUrl . '?brand=' . $brand->id . '"></script>
    <!-- End of Live Chat -->';
    }


    public function update(Request $request, $id)
    {
        $brand = $this->brandsQuery()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array',
            'user_ids.*' => [
                'required',
                Rule::exists('users', 'id')->when(auth()->user()->company_id, fn ($rule) => $rule->where('company_id', auth()->user()->company_id)),
            ],
            'email' => 'required|email|max:255',
            'url' => [
                'required',
                'string',
                'max:255',
                'url'
            ],
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!https?:\/\/)(?!www\.)([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/',
                Rule::unique('brand', 'domain')->ignore($brand->id)
            ],
            'allowed_ips' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        DB::beginTransaction();

        try {
            // Update brand
            $brand->update($validated);

            // Delete existing user assignments
            BrandUser::where('brand_id', (string) $id)->delete();

            // Insert new user assignments
            foreach ($request->user_ids as $userId) {
                BrandUser::create([
                    'brand_id' => (string) $brand->id,
                    'user_id' => (string) $userId,
                ]);
            }

            DB::commit();

            // 🤖 Auto-train/re-sync AI on updated website link
            TrainBrandAiJob::dispatch($brand);

            return redirect()->route('admin.brand')
                ->with('success', 'Brand updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Error updating brand: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function resyncAi($id)
    {
        $brand = $this->brandsQuery()->findOrFail($id);
        TrainBrandAiJob::dispatch($brand);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'AI website training started in background!'
            ]);
        }

        return back()->with('success', 'AI website training started in background!');
    }

    public function destroy(Brand $brand)
    {
        $this->brandsQuery()->findOrFail($brand->id);
        ChatSetting::where('brand_id', $brand->id)->delete();
        $brand->delete();

        return redirect()->route('admin.brand')->with('success', 'Brand deleted successfully!');
    }

    private function brandsQuery()
    {
        return Brand::query()->when(
            auth()->user()->company_id && !auth()->user()->isSuperAdmin(),
            fn ($query) => $query->where('company_id', auth()->user()->company_id)
        );
    }
}
