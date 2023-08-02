<?php

use Devront\AdvancedStatistics\AdvancedStatistics;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create(app(AdvancedStatistics::class)->getTableName(), function (Blueprint $table) {
            if (app(AdvancedStatistics::class)->isUsingUuids()) {
                $table->uuid('id')->primary();

                $table->string('owner_id')->index();
                $table->string('owner_type')->index();
            } else {
                $table->id();

                $table->morphs('owner');
            }

            $table->tinyInteger('type');
            $table->string('timeframe');
            $table->float('value', 10, 2)->default(0);
            $table->date('from_date');
            $table->date('to_date');
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(app(AdvancedStatistics::class)->getTableName());
    }
};
