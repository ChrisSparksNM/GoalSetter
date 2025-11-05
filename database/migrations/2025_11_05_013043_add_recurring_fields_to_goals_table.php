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
        Schema::table('goals', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['none', 'weekly', 'biweekly', 'monthly'])->default('none');
            $table->date('start_date')->nullable();
            $table->integer('recurrence_count')->default(1); // How many times to repeat
            $table->date('next_due_date')->nullable(); // For recurring goals
            $table->unsignedBigInteger('parent_goal_id')->nullable(); // Link to original recurring goal
            $table->foreign('parent_goal_id')->references('id')->on('goals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropForeign(['parent_goal_id']);
            $table->dropColumn([
                'is_recurring',
                'recurrence_type', 
                'start_date',
                'recurrence_count',
                'next_due_date',
                'parent_goal_id'
            ]);
        });
    }
};
