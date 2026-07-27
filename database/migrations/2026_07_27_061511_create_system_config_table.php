<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_config', function (Blueprint $table) {
            $table->id('config_id')->comment('主键');
            $table->string('config_key', 50)->unique()->comment('配置键');
            $table->string('config_value', 100)->comment('配置值');
            $table->string('remark', 200)->nullable()->comment('备注说明');
        });

        // 初始化：报名总开关
        DB::table('system_config')->insert([
            'config_key'   => 'train_sign_switch',
            'config_value' => '1',
            'remark'       => '全局培训报名总开关',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};
