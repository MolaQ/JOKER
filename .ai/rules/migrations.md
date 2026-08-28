---
paths:
  - 'database/migrations/**'
---

# Migrations

## Column type changes must use Schema::change(), never raw driver-specific SQL
Tests run against SQLite (see phpunit.xml) while dev/prod use MySQL. Never use `DB::statement('ALTER TABLE ... MODIFY ...')` (MySQL-only syntax) for column changes. Laravel 12 supports native `$table->column()->nullable()->change()` cross-driver without needing doctrine/dbal - always prefer it.
Also: when dropping a composite unique index that includes a foreign-keyed column (e.g. `unique(['team_id','season_id'])`), MySQL requires a replacement index covering that FK column first (`$table->index('team_id')`) or the drop fails with "needed in a foreign key constraint".
