<?php
// 报名名额单行表（既作报名人数上限配置，也作为并发报名时的行锁锚点）
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sign_quota', function (Blueprint $table) {
            $table->tinyInteger('id')->primary()->comment('单行名额表，恒为1');
            $table->integer('limit_count')->default(300)->comment('报名人数上限');
            $table->string('remark', 200)->nullable()->comment('备注');
            $table->dateTime('create_time')->useCurrent()->comment('创建时间');
            $table->dateTime('update_time')->useCurrent()->useCurrentOnUpdate()->comment('更新时间');
        });

        // 初始化唯一名额行：报名上限 300 人
        DB::table('sign_quota')->insert([
            'id'          => 1,
            'limit_count' => 300,
            'remark'      => '报名人数上限',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sign_quota');
    }
};
