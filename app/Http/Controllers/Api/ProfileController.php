<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return new ProfileResource($request->user());
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new ProfileResource($user);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $rules = [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];

        if ($user->password_set_at) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        
        $request->user()->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
