<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('contact_list_source');
    }
    public function down()
    {
        // Recreate if needed for rollback, or leave empty if you're sure
        Schema::create('contact_list_source', function (Blueprint $table) {
            $table->foreignId('contact_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('source_id')->constrained()->onDelete('cascade');
            $table->primary(['contact_list_id', 'source_id']);
        });
    }
};
