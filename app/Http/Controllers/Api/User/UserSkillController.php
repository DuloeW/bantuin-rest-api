<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Service\User\UserSkillService;
use App\Traits\ServiceResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSkillController extends Controller
{
    use ServiceResponse;

    protected UserSkillService $userSkillService;

    public function __construct(UserSkillService $userSkillService)
    {
        $this->userSkillService = $userSkillService;
    }

    /**
     * GET /user/skills
     * Lihat semua skill milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $result = $this->userSkillService->index(auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }

    /**
     * POST /user/skills
     * Tambah skill baru ke user yang sedang login.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'skill_id' => ['required', 'string', 'exists:skills,id'],
        ]);

        $result = $this->userSkillService->store($data['skill_id'], auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }

    /**
     * DELETE /user/skills/{skillId}
     * Hapus skill dari user yang sedang login.
     */
    public function destroy(string $skillId): JsonResponse
    {
        $result = $this->userSkillService->destroy($skillId, auth('sanctum')->id());

        return response()->json($result, $result['code']);
    }
}
