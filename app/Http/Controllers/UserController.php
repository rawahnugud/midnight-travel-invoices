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
            'email' => 'nullable|email',
            'password' => ['required', 'string', Password::defaults()],
            'role' => 'required|in:admin,staff,viewer',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64', Rule::unique('users')->ignore($user->id)],
            'email' => 'nullable|email',
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => 'required|in:admin,staff,viewer',
        ]);

        if ($user->id === $request->user()->id && ($validated['role'] ?? null) !== 'admin') {
            return redirect()->route('users.index')->with('error', 'You cannot demote yourself.');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete yourself.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
