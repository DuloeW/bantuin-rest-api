<?php

namespace App\Service\Skill;

use App\Models\Skill;
use App\Traits\ServiceResponse;
use Exception;

class SkillService
{
    use ServiceResponse;

    public function getAllSkills()
    {
        $skills = Skill::all();

        return $this->successPayload($skills, 'skills retrieved successfully');
    }

    public function getSkillById(string $id)
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return $this->errorPayload('skill not found', [], 404);
        }

        return $this->successPayload($skill, 'skill retrieved successfully');
    }

    public function getSkillByName(string $name)
    {
        $skill = Skill::where('name', $name)->first();

        if (!$skill) {
            return $this->errorPayload('skill not found', [], 404);
        }

        return $this->successPayload($skill, 'skill retrieved successfully');
    }

    public function searchSkillsByName(string $name)
    {
        $skills = Skill::where('name', 'like', '%' . $name . '%')->get();

        if ($skills->isEmpty()) {
            return $this->errorPayload('no skills found', [], 404);
        }

        return $this->successPayload($skills, 'skills retrieved successfully');
    }
}