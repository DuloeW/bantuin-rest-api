<?php

namespace App\Http\Controllers\Api\Skill;

use App\Filament\Resources\Skills\SkillResource;
use App\Http\Controllers\Controller;
use App\Service\Skill\SkillService;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    protected SkillService $skillService;

    public function __construct(SkillService $skillService)
    {
        $this->skillService = $skillService;
    }

    public function getAllSkills()
    {
        $skills = $this->skillService->getAllSkills();
        return response()->json($skills);
    }

    public function getSkillById(string $id)
    {
        $skill = $this->skillService->getSkillById($id);
        return response()->json($skill);
    }

    public function getSkillByName(string $name)
    {
        $skill = $this->skillService->getSkillByName($name);
        return response()->json($skill);
    }

    public function searchSkillsByName(string $name)
    {
        $skills = $this->skillService->searchSkillsByName($name);
        return response()->json($skills);
    }
}
