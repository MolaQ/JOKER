<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Podstrony w dziale: menu z boku (na telefonie „W tym dziale”), podstrony w grupach
        // (np. Programy i projekty → KLUB PRO 2026), własne kolory, baner i wyróżnione liczby.
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('pages')->nullOnDelete();
                $table->boolean('in_menu')->default(true)->after('display_order');
                $table->boolean('footer_link')->default(false)->after('in_menu');
                $table->boolean('image_is_banner')->default(false)->after('image_path');
                $table->string('theme_primary', 9)->nullable()->after('image_is_banner');
                $table->string('theme_accent', 9)->nullable()->after('theme_primary');
                $table->json('highlights')->nullable()->after('theme_accent');
            }
        });

        // Ukrytą podstronę (projekt przed publikacją) widzi administrator i szef.
        DB::table('role_permissions')->insertOrIgnore(['role' => UserRole::Boss->value, 'permission' => Permission::PagePreview->value]);
        Cache::forget('role_permissions');

        // Projekt zakładki KLUB PRO 2026 dla klubu, który już ma swoje podstrony. Ukryty do akceptacji.
        if (DB::table('pages')->where('slug', 'o-klubie')->exists() && ! DB::table('pages')->where('slug', 'programy-i-projekty')->exists()) {
            $order = (int) DB::table('pages')->max('display_order') + 1;
            $parentId = DB::table('pages')->insertGetId([
                'title' => 'Programy i projekty',
                'slug' => 'programy-i-projekty',
                'lead' => 'Programy, w których bierze udział klub, i projekty, które realizujemy.',
                'is_published' => false,
                'display_order' => $order,
                'in_menu' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('pages')->insert([
                'parent_id' => $parentId,
                'title' => 'KLUB PRO 2026',
                'slug' => 'klub-pro-2026',
                'lead' => 'UKS SMS Joker Piła drugi rok z rzędu znalazł się w gronie beneficjentów rządowego Programu wspierania klubów sportowych - KLUB PRO, realizowanego przez Ministerstwo Sportu i Turystyki.',
                'content' => '<p>Program skierowany jest do klubów osiągających wysokie wyniki w szkoleniu dzieci i młodzieży. Podstawą kwalifikacji są rezultaty sportowe uzyskane w ramach Systemu Sportu Młodzieżowego (SSM). W edycji 2026 uwzględniono dorobek punktowy klubów z lat 2023 - 2025, natomiast wysokość możliwego dofinansowania uzależniono od średniej liczby zawodników uczestniczących we współzawodnictwie sportowym.</p>'
                    .'<p>Operatorem krajowym programu jest Fundacja LOTTO im. Haliny Konopackiej. Przedsięwzięcie finansowane jest ze środków Funduszu Rozwoju Kultury Fizycznej, których dysponentem jest Minister Sportu i Turystyki, oraz środków własnych Fundacji LOTTO.</p>'
                    .'<p>W ramach edycji 2026 UKS SMS Joker Piła otrzymał dofinansowanie w wysokości 110&nbsp;000&nbsp;zł, przeznaczone na wspieranie procesu szkolenia młodzieży, w szczególności zakup sprzętu sportowego, udział w zawodach, zapewnienie odpowiednich warunków treningowych oraz wsparcie pracy kadry szkoleniowej. Całkowita wartość zadania to 116&nbsp;099&nbsp;zł.</p>'
                    .'<p>Udział w programie KLUB PRO stanowi potwierdzenie wysokiego poziomu szkolenia sportowego prowadzonego przez nasz Klub i pozwala na dalsze doskonalenie warunków rozwoju młodych siatkarzy.</p>',
                'image_path' => '/img/programy/klub-pro-2026.jpg',
                'image_is_banner' => true,
                'theme_primary' => '#1e2a8a',
                'theme_accent' => '#ff6b1a',
                'highlights' => json_encode([
                    ['value' => '110 000 zł', 'label' => 'dofinansowania w edycji 2026'],
                    ['value' => '116 099 zł', 'label' => 'całkowita wartość zadania'],
                    ['value' => '2. rok', 'label' => 'z rzędu w programie'],
                ], JSON_UNESCAPED_UNICODE),
                'is_published' => false,
                'display_order' => 1,
                'in_menu' => true,
                'footer_link' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', Permission::PagePreview->value)->delete();
        DB::table('permission_user')->where('permission', Permission::PagePreview->value)->delete();
        Cache::forget('role_permissions');

        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['in_menu', 'footer_link', 'image_is_banner', 'theme_primary', 'theme_accent', 'highlights']);
        });
    }
};
