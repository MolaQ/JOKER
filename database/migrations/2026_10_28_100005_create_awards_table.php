<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wyróżnienia i certyfikaty jako osobne wpisy z logo (wcześniej jedno pole tekstowe w ustawieniach SEO).
 * Wpisy z dotychczasowego pola przechodzą do tabeli; znane certyfikaty dostają swoje logo.
 */
return new class extends Migration
{
    private const DEFAULT = "Złoty certyfikat Programu Certyfikacji Akademii Siatkarskich (Fundacja Siatkarska Polska, 2026)\nBrązowy Ośrodek PZPS 2024/25, 2025/26 i 2026 (Certyfikacja Młodzieżowych Ośrodków Siatkarskich PZPS)";

    private const LOGOS = [
        'Akademii Siatkarskich' => '/img/wyroznienia/certyfikacja-akademii-siatkarskich.png',
        'Brązowy Ośrodek PZPS' => '/img/wyroznienia/brazowy-osrodek-pzps.png',
    ];

    public function up(): void
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('note')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $stored = DB::table('settings')->where('key', 'seo_awards')->value('value');
        $lines = array_filter(array_map('trim', preg_split('/\R/u', (string) ($stored ?? self::DEFAULT))));

        foreach (array_values($lines) as $index => $line) {
            [$name, $note] = preg_match('/^(.*\S)\s*\(([^()]*)\)\s*$/u', $line, $parts) ? [$parts[1], $parts[2]] : [$line, null];
            $logo = collect(self::LOGOS)->first(fn (string $path, string $phrase) => str_contains($line, $phrase));

            DB::table('awards')->insert([
                'name' => mb_substr($name, 0, 255),
                'note' => $note !== null ? mb_substr($note, 0, 255) : null,
                'logo_path' => $logo,
                'display_order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
