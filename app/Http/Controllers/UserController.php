<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('users.index', [
            'users' => User::where('company_id', $request->user()->company_id)->with('role')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('company_id', $request->user()->company_id)],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $user = User::create([
            ...$data,
            'company_id' => $request->user()->company_id,
            'branch_ids' => $data['branch_ids'] ?? null,
            'locale' => app()->getLocale(),
        ]);

        AuditLog::record('user.created', $user);

        return back()->with('success', __('Team member added.'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->company_id === $request->user()->company_id, 404);

        $data = $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')->where('company_id', $request->user()->company_id)],
            'is_active' => ['nullable', 'boolean'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        if ($user->id === $request->user()->id && ! $request->boolean('is_active', true)) {
            return back()->with('error', __('You cannot deactivate your own account.'));
        }

        $user->update([
            'role_id' => $data['role_id'],
            'is_active' => $request->boolean('is_active', true),
            'branch_ids' => $data['branch_ids'] ?? null,
        ]);

        AuditLog::record('user.updated', $user);

        return back()->with('success', __('Team member updated.'));
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->company_id === $request->user()->company_id, 404);

        if ($user->id === $request->user()->id) {
            return back()->with('error', __('You cannot remove your own account.'));
        }

        $user->delete();
        AuditLog::record('user.deleted', $user);

        return back()->with('success', __('Team member removed.'));
    }
}
