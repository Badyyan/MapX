<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * FR-3: company admins create custom roles from the predefined
 * permission catalog (config/mapx.php).
 */
class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'roles' => Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get(),
            'permissionCatalog' => config('mapx.permissions'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Role::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'is_system' => false,
        ]);

        AuditLog::record('role.created', null, ['name' => $data['name']]);

        return back()->with('success', __('Role created.'));
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->is_system && $role->slug === 'admin', 403, __('The admin role cannot be modified.'));

        $role->update($this->validated($request));
        AuditLog::record('role.updated', $role);

        return back()->with('success', __('Role updated.'));
    }

    public function destroy(Role $role)
    {
        abort_if($role->is_system, 403, __('System roles cannot be deleted.'));

        if ($role->users()->exists()) {
            return back()->with('error', __('Reassign this role\'s members before deleting it.'));
        }

        $role->delete();
        AuditLog::record('role.deleted', $role);

        return back()->with('success', __('Role deleted.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:'.implode(',', config('mapx.permissions'))],
        ]);

        return ['name' => $data['name'], 'permissions' => array_values($data['permissions'])];
    }
}
