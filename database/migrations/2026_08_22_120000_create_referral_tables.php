<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('referral_partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('referral_applications')->nullOnDelete();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->string('payout_details')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('referral_partners')->cascadeOnDelete();
            $table->foreignId('lead_id')->unique()->constrained('leads')->cascadeOnDelete();
            $table->unsignedInteger('amount_cents')->default(15000);
            $table->string('status', 32)->default('eligible')->index();
            $table->timestamp('eligible_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'referral_partner_id')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->foreignId('referral_partner_id')
                    ->nullable()
                    ->after('utm_campaign')
                    ->constrained('referral_partners')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('phone_clicks') && ! Schema::hasColumn('phone_clicks', 'referral_partner_id')) {
            Schema::table('phone_clicks', function (Blueprint $table): void {
                $table->foreignId('referral_partner_id')
                    ->nullable()
                    ->after('utm_campaign')
                    ->constrained('referral_partners')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('site_visits') && ! Schema::hasColumn('site_visits', 'referral_partner_id')) {
            Schema::table('site_visits', function (Blueprint $table): void {
                $table->foreignId('referral_partner_id')
                    ->nullable()
                    ->after('utm_campaign')
                    ->constrained('referral_partners')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['leads', 'phone_clicks', 'site_visits'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'referral_partner_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->dropConstrainedForeignId('referral_partner_id');
                });
            }
        }

        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referral_partners');
        Schema::dropIfExists('referral_applications');
    }
};
