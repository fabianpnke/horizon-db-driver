<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('horizon-db-driver.connection');
    }

    public function up(): void
    {
        Schema::create('horizon_jobs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('connection');
            $table->string('queue');
            $table->string('name');
            $table->string('status')->index();
            $table->longText('payload');
            $table->longText('exception')->nullable();
            $table->longText('context')->nullable();
            $table->longText('retried_by')->nullable();
            $table->double('reserved_at')->nullable();
            $table->double('completed_at')->nullable()->index();
            $table->double('failed_at')->nullable()->index();
            $table->double('created_at')->index();
            $table->double('updated_at');
            $table->unsignedInteger('delay')->default(0);
            $table->boolean('monitored')->default(false);
        });

        Schema::create('horizon_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->index();
            $table->string('job_id')->index();
            $table->double('created_at');
            $table->unsignedInteger('expires_at')->nullable()->index();

            $table->unique(['tag', 'job_id']);
        });

        Schema::create('horizon_monitored_tags', function (Blueprint $table) {
            $table->string('tag')->primary();
        });

        Schema::create('horizon_supervisors', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('master');
            $table->unsignedInteger('pid');
            $table->string('status');
            $table->longText('processes');
            $table->longText('options');
            $table->unsignedInteger('expires_at');
            $table->unsignedInteger('updated_at')->index();
        });

        Schema::create('horizon_master_supervisors', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedInteger('pid');
            $table->string('status');
            $table->longText('supervisors');
            $table->unsignedInteger('expires_at');
            $table->unsignedInteger('updated_at')->index();
        });

        Schema::create('horizon_processes', function (Blueprint $table) {
            $table->id();
            $table->string('master')->index();
            $table->string('process_id');
            $table->unsignedInteger('recorded_at');

            $table->unique(['master', 'process_id']);
        });

        Schema::create('horizon_metrics', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('type')->index();
            $table->unsignedBigInteger('throughput')->default(0);
            $table->double('runtime')->default(0);
        });

        Schema::create('horizon_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('type')->index();
            $table->unsignedInteger('taken_at')->index();
            $table->longText('snapshot');
        });

        Schema::create('horizon_metric_meta', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
        });

        Schema::create('horizon_command_queue', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('command');
            $table->longText('options');
            $table->double('created_at');
        });

        Schema::create('horizon_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedInteger('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horizon_jobs');
        Schema::dropIfExists('horizon_tags');
        Schema::dropIfExists('horizon_monitored_tags');
        Schema::dropIfExists('horizon_supervisors');
        Schema::dropIfExists('horizon_master_supervisors');
        Schema::dropIfExists('horizon_processes');
        Schema::dropIfExists('horizon_metrics');
        Schema::dropIfExists('horizon_metric_snapshots');
        Schema::dropIfExists('horizon_metric_meta');
        Schema::dropIfExists('horizon_command_queue');
        Schema::dropIfExists('horizon_locks');
    }
};
