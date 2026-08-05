<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\SysAdmin;
use App\Models\SysUser;
use App\Models\TrainCourse;
use App\Models\TrainSign;
use App\Models\TrainHomework;
use App\Models\HomeworkSubmit;
use App\Models\SystemConfig;

class WJCAdminSeeder extends Seeder
{
    public function run(): void
    {
        // ===== 管理员 =====
        $admin1 = SysAdmin::firstOrCreate(
            ['admin_name' => 'superadmin'],
            [
                'password'  => Hash::make('Admin@123'),
                'real_name' => '超级管理员',
                'phone'     => '13900139000',
                'email'     => 'admin@lab.com',
                'status'    => 1,
            ]
        );

        SysAdmin::firstOrCreate(
            ['admin_name' => 'disabled'],
            [
                'password'  => Hash::make('Admin@123'),
                'real_name' => '已禁用管理员',
                'phone'     => '13900139001',
                'status'    => 0,
            ]
        );

        // ===== 学员用户 =====
        $user1 = SysUser::firstOrCreate(
            ['username' => 'stu001'],
            [
                'password'   => Hash::make('Pass@123'),
                'real_name'  => '张三',
                'phone'      => '13800138001',
                'email'      => 'zhangsan@test.com',
                'grade'      => '2023级',
                'major'      => '计算机科学',
                'college'    => '计算机与软件学院',
                'student_id' => '202301001',
                'status'     => 1,
            ]
        );

        $user2 = SysUser::firstOrCreate(
            ['username' => 'stu002'],
            [
                'password'   => Hash::make('Pass@123'),
                'real_name'  => '李四',
                'phone'      => '13800138002',
                'email'      => 'lisi@test.com',
                'grade'      => '2023级',
                'major'      => '电子信息',
                'college'    => '电子信息工程学院',
                'student_id' => '202302001',
                'status'     => 1,
            ]
        );

        $user3 = SysUser::firstOrCreate(
            ['username' => 'stu003'],
            [
                'password'   => Hash::make('Pass@123'),
                'real_name'  => '王五',
                'phone'      => '13800138003',
                'email'      => 'wangwu@test.com',
                'grade'      => '2022级',
                'major'      => '自动化',
                'college'    => '自动化学院',
                'student_id' => '202203001',
                'status'     => 1,
            ]
        );

        // ===== 培训课程 =====
        $course1 = TrainCourse::firstOrCreate(
            ['course_name' => '嵌入式系统实训'],
            [
                'course_desc' => '讲解单片机硬件开发基础与项目实战',
                'cover_img'   => 'https://img.example.com/course1.jpg',
                'start_time'  => '2026-08-01 09:00:00',
                'end_time'    => '2026-09-30 17:00:00',
                'max_sign'    => 50,
                'status'      => 1,
                'create_admin'=> $admin1->admin_id,
            ]
        );

        $course2 = TrainCourse::firstOrCreate(
            ['course_name' => 'Python 数据分析'],
            [
                'course_desc' => '从入门到实战，掌握 Pandas/NumPy',
                'cover_img'   => 'https://img.example.com/course2.jpg',
                'start_time'  => '2026-08-15 09:00:00',
                'end_time'    => '2026-10-15 17:00:00',
                'max_sign'    => 30,
                'status'      => 1,
                'create_admin'=> $admin1->admin_id,
            ]
        );

        $course3 = TrainCourse::firstOrCreate(
            ['course_name' => '机器学习基础（已下架）'],
            [
                'course_desc' => '已结课，不再开放报名',
                'cover_img'   => '',
                'start_time'  => '2026-03-01 09:00:00',
                'end_time'    => '2026-06-30 17:00:00',
                'max_sign'    => 40,
                'status'      => 0,
                'create_admin'=> $admin1->admin_id,
            ]
        );

        // ===== 报名记录 =====
        TrainSign::firstOrCreate(
            ['user_id' => $user1->user_id, 'course_id' => $course1->course_id],
            [
                'sign_info'    => '本人有C语言基础，希望参与实训',
                'status' => 1,
                'sign_time'    => '2026-07-24 10:00:00',
            ]
        );

        TrainSign::firstOrCreate(
            ['user_id' => $user2->user_id, 'course_id' => $course1->course_id],
            [
                'sign_info'    => '学过51单片机',
                'status' => 1,
                'sign_time'    => '2026-07-24 11:00:00',
            ]
        );

        TrainSign::firstOrCreate(
            ['user_id' => $user3->user_id, 'course_id' => $course2->course_id],
            [
                'sign_info'    => null,
                'status' => 1,
                'sign_time'    => '2026-07-25 09:00:00',
            ]
        );

        TrainSign::firstOrCreate(
            ['user_id' => $user1->user_id, 'course_id' => $course2->course_id],
            [
                'sign_info' => '对数据分析感兴趣',
                'status'    => 1,
                'sign_time' => '2026-07-20 14:00:00',
            ]
        );

        TrainSign::firstOrCreate(
            ['user_id' => $user2->user_id, 'course_id' => $course2->course_id],
            [
                'sign_info' => '',
                'status'    => 1,
                'sign_time' => '2026-07-20 15:00:00',
            ]
        );

        // ===== 作业 =====
        $hw1 = TrainHomework::firstOrCreate(
            ['homework_title' => '第一章：LED点亮代码', 'course_id' => $course1->course_id],
            [
                'homework_content' => '完成LED点亮代码编写，要求使用寄存器操作方式',
                'deadline'         => '2026-08-10 23:59:59',
                'create_time'      => '2026-08-01 14:30:00',
            ]
        );

        TrainHomework::firstOrCreate(
            ['homework_title' => '第二章：按键中断实验', 'course_id' => $course1->course_id],
            [
                'homework_content' => '编写外部中断处理程序，实现按键控制LED',
                'deadline'         => '2026-08-20 23:59:59',
                'create_time'      => '2026-08-08 09:00:00',
            ]
        );

        // ===== 作业提交 =====
        HomeworkSubmit::firstOrCreate(
            ['user_id' => $user1->user_id, 'homework_id' => $hw1->homework_id],
            [
                'submit_content' => '已完成LED点亮，代码见附件',
                'submit_file'    => 'homework_files/led_code.zip',
                'submit_time'    => '2026-08-03 15:00:00',
            ]
        );

        // ===== 系统配置：报名开关 =====
        SystemConfig::updateOrCreate(
            ['config_key' => 'train_sign_switch'],
            ['config_value' => '1', 'remark' => '全局培训报名总开关']
        );
    }
}
