<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthRepository
{
    public function __construct(
        private User $model
    ) {}

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return $this->model->create($data);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
