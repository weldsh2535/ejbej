<?php


namespace App\Http\Controllers;

use App\Http\Requests\User\BulkDeleteUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     * Users list.
     *
     * @tags Users
     */

    public function index(Request $request)
    {
       
    }

    /**
     * Create User.
     *
     * @tags Users
     */
    public function store(StoreUserRequest $request)
    {
       
    }

    /**
     * Show User.
     *
     * @tags Users
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['roles.permissions'])->findOrFail($id);
        $this->authorize('view', $user);

        return $this->resourceResponse(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Update User.
     *
     * @tags Users
     */
    public function update(UpdateUserRequest $request, int $id)
    {

    }

    /**
     * Delete User.
     *
     * @tags Users
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        if ($user->id === Auth::id()) {
            return $this->errorResponse('You cannot delete yourself', 400);
        }

        $user->delete();

        $this->logAction('User Deleted', $user);

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Bulk Delete Users.
     *
     * @tags Users
     */
    public function bulkDelete(BulkDeleteUserRequest $request)
    {
        
    }
}
