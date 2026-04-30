<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return User::with('roles', 'location')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'location_id' => 'required|exists:locations,id',
            'role' => 'required|exists:roles,name',
            'receive_alert' => 'boolean'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'location_id' => $request->location_id,
            'receive_alert' => $request->boolean('receive_alert', false)
        ]);

        $user->assignRole($request->role);

        return $user->load('roles', 'location');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'location_id' => 'required|exists:locations,id',
            'role' => 'required|exists:roles,name',
            'receive_alert' => 'boolean'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'location_id' => $request->location_id,
            'receive_alert' => $request->boolean('receive_alert', $user->receive_alert)
        ]);

        $user->syncRoles([$request->role]);

        return $user->load('roles', 'location');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string',
            'password'     => 'nullable|min:6|confirmed',
            'receive_alert'=> 'boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        
        if ($request->has('receive_alert')) {
            $user->receive_alert = $request->boolean('receive_alert');
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user->load('roles', 'location')
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json(['message' => 'Deleted']);
    }
}