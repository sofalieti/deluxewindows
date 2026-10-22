<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array<string, callable(Blueprint): mixed>
     */
    private function columns(): array
    {
        return [
            'qualification_window_count' => fn (Blueprint $t) => $t->string('qualification_window_count', 20)->nullable(),
            'qualification_project_type' => fn (Blueprint $t) => $t->string('qualification_project_type', 30)->nullable(),
            'qualification_property_type' => fn (Blueprint $t) => $t->string('qualification_property_type', 30)->nullable(),
            'qualification_zip' => fn (Blueprint $t) => $t->string('qualification_zip', 10)->nullable(),
            'qualification_decision_maker' => fn (Blueprint $t) => $t->boolean('qualification_decision_maker')->nullable(),
            'bing_qualified_at' => fn (Blueprint $t) => $t->timestamp('bing_qualified_at')->nullable(),
            'bing_qualified_status' => fn (Blueprint $t) => $t->string('bing_qualified_status', 20)->nullable()->index(),
            'bing_qualified_conversion_name' => fn (Blueprint $t) => $t->string('bing_qualified_conversion_name')->nullable(),
            'bing_qualified_msclkid' => fn (Blueprint $t) => $t->string('bing_qualified_msclkid')->nullable()->index(),
            'bing_qualified_sent_at' => fn (Blueprint $t) => $t->timestamp('bing_qualified_sent_at')->nullable(),
            'bing_qualified_error' => fn (Blueprint $t) => $t->text('bing_qualified_error')->nullable(),
            'bing_qualified_attempts' => fn (Blueprint $t) => $t->unsignedSmallInteger('bing_qualified_attempts')->default(0),
        ];
    }

    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            foreach ($this->columns() as $name => $define) {
                if (! Schema::hasColumn('leads', $name)) {
                    $define($table);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            foreach (array_keys($this->columns()) as $name) {
                if (Schema::hasColumn('leads', $name)) {
                    if (in_array($name, ['bing_qualified_status', 'bing_qualified_msclkid'], true)) {
                        $table->dropIndex('leads_'.$name.'_index');
                    }
                    $table->dropColumn($name);
                }
            }
        });
    }
};
