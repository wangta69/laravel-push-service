<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreatePushsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 게시판 설정값 담을 테이블
        Schema::create('pushs', function(BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('uuid')->unsigned()->comment('user id');
            $table->string('device_id');
            $table->string('push_type')->comment('fcm firebase apns : ios, gcm : android');
            $table->string('push_token');
            $table->timestamps();
            $table->softDeletes();
        });

    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pushs');
    }
}
