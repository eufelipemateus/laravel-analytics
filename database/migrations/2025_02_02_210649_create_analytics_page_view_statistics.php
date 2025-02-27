<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_page_view_statistics', function (Blueprint $table) {
            $table->id();
            $table->timestamp('time_window')->index();
            $table->integer('page_views')->default(0);
            $table->string('page');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_page_view_statistics');
    }
};
