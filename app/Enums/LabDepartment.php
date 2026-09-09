<?php

namespace App\Enums;

/**
 * 部门枚举
 *
 * 对应 sys_admin.department（int）与各表 lab_id（string）的映射。
 * 数据隔离统一以 lab_id 为准，department 为其冗余的 int 标识。
 */
enum LabDepartment: string
{
    case SOFTWARE = 'software';
    case AI = 'ai';
    case COORD_RESEARCH = 'coord_research';
    case OPERATIONS = 'operations';

    public function departmentId(): int
    {
        return match ($this) {
            self::SOFTWARE       => 1,
            self::AI             => 2,
            self::COORD_RESEARCH => 3,
            self::OPERATIONS     => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SOFTWARE       => '软件开发实验室',
            self::AI             => '人工智能实验室',
            self::COORD_RESEARCH => '协研发展部',
            self::OPERATIONS     => '综合运营部',
        };
    }

    public static function fromDepartmentId(?int $id): ?self
    {
        return match ($id) {
            1 => self::SOFTWARE,
            2 => self::AI,
            3 => self::COORD_RESEARCH,
            4 => self::OPERATIONS,
            default => null,
        };
    }

    /**
     * 全部部门列表（供前端下拉等）
     *
     * @return array<int, array{labId: string, department: int, label: string}>
     */
    public static function labels(): array
    {
        return array_map(
            fn (self $d) => ['labId' => $d->value, 'department' => $d->departmentId(), 'label' => $d->label()],
            self::cases()
        );
    }
}
