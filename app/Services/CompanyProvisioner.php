<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyProvisioner
{
    /**
     * Create a company with its default roles, admin user and trial
     * subscription (BRD 5.8: 7-day trial, max 3 branches).
     */
    public function provision(array $companyData, array $adminData): User
    {
        return DB::transaction(function () use ($companyData, $adminData) {
            $company = Company::create($companyData);

            $roles = $this->createDefaultRoles($company);

            $admin = User::create([
                ...$adminData,
                'company_id' => $company->id,
                'role_id' => $roles['admin']->id,
                'is_active' => true,
            ]);

            Subscription::create([
                'company_id' => $company->id,
                'plan' => 'trial',
                'status' => 'trialing',
                'branch_limit' => config('mapx.billing.trial_branch_limit'),
                'price_per_branch' => config('mapx.billing.price_per_branch'),
                'currency' => config('mapx.billing.currency'),
                'trial_ends_at' => now()->addDays(config('mapx.billing.trial_days')),
            ]);

            return $admin;
        });
    }

    /** @return array<string, Role> */
    public function createDefaultRoles(Company $company): array
    {
        $roles = [];
        foreach (config('mapx.role_templates') as $slug => $template) {
            $roles[$slug] = Role::create([
                'company_id' => $company->id,
                'name' => $template['name'],
                'slug' => $slug,
                'permissions' => $template['permissions'] === '*' ? ['*'] : $template['permissions'],
                'is_system' => true,
            ]);
        }

        return $roles;
    }
}
