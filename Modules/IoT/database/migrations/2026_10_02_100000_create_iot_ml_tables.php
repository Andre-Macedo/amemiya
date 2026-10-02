<?php

declare(strict_types=1);

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
        // 1. Tabela de Datasets Curados
        Schema::create('iot_ml_datasets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('type')->default('supervised_xgboost'); // supervised_xgboost, unsupervised_iforest
            $table->foreignUlid('target_machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('collecting'); // collecting, ready, archived
            $table->json('class_distribution')->nullable(); // {"saudavel": 120, "desbalanceamento": 45}
            $table->unsignedInteger('total_samples')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->unique(['tenant_id', 'slug']);
        });

        // 2. Tabela de Rajadas de Amostras Densas (Bursts de 5-10s)
        Schema::create('iot_ml_bursts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignUlid('dataset_id')->nullable()->constrained('iot_ml_datasets')->nullOnDelete();
            $table->foreignUlid('device_log_id')->nullable()->constrained('iot_device_logs')->nullOnDelete();
            $table->foreignUlid('node_id')->nullable()->constrained('iot_nodes')->nullOnDelete();
            $table->foreignUlid('machine_id')->nullable()->constrained('machines')->nullOnDelete();

            $table->string('session_id')->nullable(); // Identificador de ensaio/corrida (Group Split)
            $table->string('origin')->default('drawer_triaged'); // bench_guided, drawer_triaged, maintenance_baseline
            $table->integer('rpm')->nullable();
            $table->decimal('rms_global', 10, 4)->nullable();
            $table->unsignedInteger('windows_count')->default(1);

            // Predição original do modelo
            $table->string('predicted_label')->nullable();
            $table->decimal('predicted_confidence', 5, 4)->nullable();

            // Rótulo Real (Ground Truth) validado a posteriori
            $table->string('ground_truth_label')->nullable(); // saudavel, desbalanceamento, folga_mecanica, falha_rolamento, falso_positivo
            $table->boolean('is_validated')->default(false);
            $table->foreignUlid('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            // Vetor de features médio (36 features) e caminho do arquivo compactado (Parquet/JSON)
            $table->json('features_summary')->nullable();
            $table->string('burst_storage_path')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'dataset_id']);
            $table->index(['tenant_id', 'is_validated']);
            $table->index(['tenant_id', 'ground_truth_label']);
            $table->index(['tenant_id', 'session_id']);
        });

        // 3. Tabela de Registro de Modelos Treinados (Model Registry)
        Schema::create('iot_ml_models', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignUlid('dataset_id')->nullable()->constrained('iot_ml_datasets')->nullOnDelete();
            $table->string('name');
            $table->string('model_type'); // xgboost_cloud, iforest_edge
            $table->string('version'); // v1.0.0, v2.1.0
            $table->string('target_device')->default('cloud'); // cloud, edge_esp32
            $table->string('status')->default('candidate'); // in_production, shadow, candidate, deprecated

            $table->string('artifact_path')->nullable(); // caminho para o .json ou .h
            $table->json('metrics')->nullable(); // {"accuracy": 0.98, "f1_score": 0.97}
            $table->timestamp('deployed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'model_type']);
            $table->unique(['tenant_id', 'name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_ml_models');
        Schema::dropIfExists('iot_ml_bursts');
        Schema::dropIfExists('iot_ml_datasets');
    }
};
