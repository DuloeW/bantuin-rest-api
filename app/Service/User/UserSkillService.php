<?php

namespace App\Service\User;

use App\Models\User;
use App\Models\Skill;
use App\Traits\ServiceResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class UserSkillService
{
    use ServiceResponse;

    /**
     * Get user's skills.
     */
    public function index(string $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            $skills = $user->skills()->get(['id', 'title']);

            return $this->successPayload($skills, 'User skills retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('User not found', [], 404);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [], 500);
        }
    }

    /**
     * Add a skill to user.
     */
    public function store(string $skillId, string $userId): array
    {
        try {
            $user = User::findOrFail($userId);
            $skill = Skill::findOrFail($skillId);

            // Check if user already has this skill
            $exists = $user->skills()->where('skills.id', $skillId)->exists();
            if ($exists) {
                return $this->errorPayload('Skill already added', [], 400);
            }

            $user->skills()->attach($skillId);

            $skills = $user->skills()->get(['id', 'title']);

            return $this->successPayload($skills, 'Skill added successfully', 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('User or Skill not found', [], 404);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [], 500);
        }
    }

    /**
     * Remove a skill from user.
     */
    public function destroy(string $skillId, string $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            // Check if user actually has this skill
            $exists = $user->skills()->where('skills.id', $skillId)->exists();
            if (!$exists) {
                return $this->errorPayload('Skill not found on this user profile', [], 404);
            }

            $user->skills()->detach($skillId);

            $skills = $user->skills()->get(['id', 'title']);

            return $this->successPayload($skills, 'Skill removed successfully');
        } catch (ModelNotFoundException $e) {
            return $this->errorPayload('User not found', [], 404);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [], 500);
        }
    }
}
