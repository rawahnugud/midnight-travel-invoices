<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('username')->get();
        return view('users.list', ['users' => $users]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:64|unique:users,username',
            'email' => 'nullable|email|max:255',
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => 'required|in:admin,staff,viewer',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')->with('success', __('messages.user_created'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'username' => ['required', 'string', 'max:64', Rule::unique('users')->ignore($user->id)],
            'email' => 'nullable|email|max:255',
            'role' => 'required|in:admin,staff,viewer',
        ];
        if ($request->filled('password') || $request->filled('password_confirmation')) {
            $rules['password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        if ($user->id === $request->user()->id && ($validated['role'] ?? null) !== 'admin') {
            return redirect()->route('users.index')->with('error', __('messages.cannot_demote_self'));
        }

        $passwordChanged = isset($validated['password']);
        if ($passwordChanged) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        $msg = __('messages.user_updated');
        if ($passwordChanged) {
            $msg .= ' ' . __('messages.user_password_set');
        }

        return redirect()->route('users.index')->with('success', $msg);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('users.index')->with('error', __('messages.cannot_delete_self'));
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', __('messages.user_deleted'));
    }
}
