<?php

namespace App\Http\Requests\Admin;

use App\Enums\GroupStatus;
use App\Enums\WeekDay;
use App\Models\StudyGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
{
    public function rules(): array
    {
        $group = $this->group();
        $branchId = $group?->branch_id ?? $this->integer('branch_id');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('study_groups', 'name')->where('branch_id', $branchId)->ignore($group),
            ],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('branch_user', 'user_id')->where('branch_id', $branchId)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'capacity' => ['required', 'integer', 'min:'.max(1, $group?->students()->count() ?? 0), 'max:100'],
            'status' => ['required', Rule::enum(GroupStatus::class)],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => [Rule::enum(WeekDay::class)],
            'schedule_time' => ['nullable', 'date_format:H:i'],
        ];

        // A group's branch is fixed once it is created.
        if (! $group) {
            $rules['branch_id'] = ['required', 'integer', Rule::in($this->user()->availableBranchIds())];
        }

        return $rules;
    }

    public function messages(): array
    {
        $enrolled = $this->group()?->students()->count() ?? 0;

        return [
            'capacity.min' => $enrolled > 1
                ? "Sig‘im guruhdagi o‘quvchilar sonidan ({$enrolled}) kam bo‘lishi mumkin emas."
                : 'Sig‘im kamida 1 bo‘lishi kerak.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'guruh nomi',
            'branch_id' => 'filial',
            'course_id' => 'kurs',
            'teacher_id' => 'o‘qituvchi',
            'starts_on' => 'boshlanish sanasi',
            'ends_on' => 'tugash sanasi',
            'capacity' => 'sig‘im',
            'status' => 'holat',
            'schedule_time' => 'dars vaqti',
        ];
    }

    /**
     * Attributes ready for StudyGroup::create() / update().
     */
    public function payload(): array
    {
        $data = $this->validated();
        $days = array_values($data['schedule_days'] ?? []);
        $time = $data['schedule_time'] ?? null;

        return [
            'name' => $data['name'],
            'course_id' => $data['course_id'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'capacity' => $data['capacity'],
            'status' => GroupStatus::from($data['status']),
            'schedule' => $days || $time ? ['days' => $days, 'time' => $time] : null,
        ];
    }

    private function group(): ?StudyGroup
    {
        return $this->route('studyGroup');
    }
}
