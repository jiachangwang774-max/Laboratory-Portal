<?php

namespace App\Support;

/**
 * 部门 / 实验室 统一映射
 *
 * department(int) 存于 sign_application.department / sys_admin.department，
 * lab_id(string)  存于各表的 lab_id 字段，用于数据隔离。
 *
 * 所有涉及「部门 ↔ lab_id ↔ 名称」的转换都从这里取，避免散落各处硬编码。
 */
class Department
{
    /**
     * department => [lab_id, 名称]
     */
    public const MAP = [
        1 => ['lab_id' => 'software',       'name' => '软件开发实验室'],
        2 => ['lab_id' => 'ai',             'name' => '人工智能算法实验室'],
        3 => ['lab_id' => 'coord_research', 'name' => '协研发展部'],
        4 => ['lab_id' => 'operations',     'name' => '综合运营部'],
    ];

    public const DEFAULT_LAB_ID = 'software';

    /**
     * 部门ID → lab_id
     */
    public static function labId(int $department): string
    {
        return self::MAP[$department]['lab_id'] ?? self::DEFAULT_LAB_ID;
    }

    /**
     * 部门ID → 名称
     */
    public static function name(int $department): string
    {
        return self::MAP[$department]['name'] ?? '';
    }

    /**
     * lab_id → 部门ID
     */
    public static function id(string $labId): int
    {
        foreach (self::MAP as $id => $item) {
            if ($item['lab_id'] === $labId) {
                return $id;
            }
        }

        return 1;
    }

    /**
     * lab_id → 名称
     */
    public static function nameByLabId(string $labId): string
    {
        foreach (self::MAP as $item) {
            if ($item['lab_id'] === $labId) {
                return $item['name'];
            }
        }

        return self::MAP[1]['name'];
    }
}
