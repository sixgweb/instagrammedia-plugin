<?php

namespace Sixgweb\InstagramMedia\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * CreateMediaTable Migration
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::create('sixgweb_instagrammedia_media', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_id')->unique()->index();
            $table->enum('media_type', ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM']);
            $table->text('media_url');
            $table->text('thumbnail_url')->nullable();
            $table->text('permalink')->nullable();
            $table->text('caption')->nullable();
            $table->timestamp('timestamp')->nullable()->index();
            $table->string('username', 100)->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::dropIfExists('sixgweb_instagrammedia_media');
    }
};
