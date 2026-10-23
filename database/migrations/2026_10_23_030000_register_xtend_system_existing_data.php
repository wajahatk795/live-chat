<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();
            $company = DB::table('companies')->where('name', 'Xtend-System')->first();

            if (!$company) {
                $companyId = DB::table('companies')->insertGetId([
                    'name' => 'Xtend-System',
                    'email' => 'admin@example.com',
                    'subscription_plan' => 'starter',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $companyId = $company->id;
            }

            DB::table('users')->where('email', 'admin@example.com')->update([
                'company_id' => $companyId,
                'updated_at' => $now,
            ]);

            // Existing brands are the company's installed chat workspaces.
            DB::table('brand')->whereNull('company_id')->update([
                'company_id' => $companyId,
                'updated_at' => $now,
            ]);

            $brandIds = DB::table('brand')->where('company_id', $companyId)->pluck('id');

            DB::table('visitors')->whereNull('company_id')->whereIn('brand_id', $brandIds)->update([
                'company_id' => $companyId,
                'updated_at' => $now,
            ]);

            $teamUserIds = DB::table('brand_users')
                ->whereIn('brand_id', $brandIds->map(fn ($id) => (string) $id))
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            DB::table('users')->whereIn('role', ['1', '2'])->whereIn('id', $teamUserIds)->update([
                'company_id' => $companyId,
                'updated_at' => $now,
            ]);

            $visitorIds = DB::table('visitors')->where('company_id', $companyId)->pluck('id');
            DB::table('users')->whereIn('visitor_id', $visitorIds)->update([
                'company_id' => $companyId,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        $company = DB::table('companies')->where('name', 'Xtend-System')->first();
        if (!$company) {
            return;
        }

        DB::transaction(function () use ($company) {
            DB::table('users')->where('company_id', $company->id)->update(['company_id' => null]);
            DB::table('visitors')->where('company_id', $company->id)->update(['company_id' => null]);
            DB::table('brand')->where('company_id', $company->id)->update(['company_id' => null]);
            DB::table('companies')->where('id', $company->id)->delete();
        });
    }
};
