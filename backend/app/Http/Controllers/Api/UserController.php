<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // GET /api/users
    public function index()
    {
        return User::all();
    }

    // POST /api/users
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            // thêm các trường role, phone, address nếu cần
        ]);
        $data['password'] = bcrypt($data['password']);
        return User::create($data);
    }

    // GET /api/users/{user}
    public function show($id)
    {
        return User::findOrFail($id);
    }

    // PUT/PATCH /api/users/{user}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:6',
        ]);
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        $user->update($data);
        return $user;
    }

    // DELETE /api/users/{user}
    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
