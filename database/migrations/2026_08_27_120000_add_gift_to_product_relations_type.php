<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const OLD_TYPES = ['complementary', 'upsell'];

    /** @var list<string> */
    private const NEW_TYPES = ['complementary', 'upsell', 'gift'];

    public function up(): void
    {
        $this->replaceTypeConstraint(self::NEW_TYPES);
    }

    public function down(): void
    {
        DB::table('product_relations')->where('type', 'gift')->delete();
        $this->replaceTypeConstraint(self::OLD_TYPES);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function replaceTypeConstraint(array $allowed): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $enum = implode("', '", $allowed);
            DB::statement(
                "ALTER TABLE product_relations MODIFY COLUMN type ENUM('{$enum}') NOT NULL DEFAULT 'complementary'"
            );

            return;
        }

        if ($driver === 'pgsql') {
            $this->dropTypeCheckConstraint();
            $values = implode(', ', array_map(fn (string $value) => $this->quote($value), $allowed));
            DB::statement(
                'ALTER TABLE product_relations ADD CONSTRAINT product_relations_type_check CHECK ("type" IN ('.$values.'))'
            );

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE product_relations DROP CONSTRAINT IF EXISTS product_relations_type_check');
            $values = implode(', ', array_map(fn (string $value) => $this->quote($value), $allowed));
            DB::statement(
                'ALTER TABLE product_relations ADD CONSTRAINT product_relations_type_check CHECK ("type" IN ('.$values.'))'
            );
        }
    }

    private function dropTypeCheckConstraint(): void
    {
        $constraints = DB::select(
            "select c.conname as name
             from pg_constraint c
             join pg_class t on c.conrelid = t.oid
             where t.relname = 'product_relations'
               and c.contype = 'c'
               and pg_get_constraintdef(c.oid) like '%type%'"
        );

        foreach ($constraints as $constraint) {
            DB::statement('ALTER TABLE product_relations DROP CONSTRAINT IF EXISTS "'.$constraint->name.'"');
        }
    }

    private function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
};
