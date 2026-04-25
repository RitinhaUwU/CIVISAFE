<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'sometimes|integer',
            'search' => 'nullable|string'
        ]);

        $types = QueryBuilder::for(User::class)
            ->allowedFilters(
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('name', 'ILIKE', "%{$value}%");
                    });
                }),
            )
            ->paginate($request->input('per_page', 10))
            ->appends($request->query());

        return UserResource::collection($types);
    }

    public function store(UserCreateRequest $request){
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'mobile' => $data['mobile'],
            'locked' => $data['locked'],
        ]);

        $user->assignRole($data['role']);

        return new UserResource($user);
    }

    public function show(User $user){
        return new UserResource($user);
    }

    public function update(UserUpdateRequest $request, User $user){
        $data = $request->validated();

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return new UserResource($user->fresh());
    }

    public function destroy(User $user){
        $user->delete();

        return response()->json();
    }
}
