<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeOrganizationsInzynierFkToContacts extends Migration
{
    public function up()
    {
        DB::statement("
            SET @fk := (
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'organizations'
                  AND COLUMN_NAME = 'inzynier_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            );
        ");

        DB::statement("
            SET @sql := IF(
                @fk IS NULL,
                'SELECT 1',
                CONCAT('ALTER TABLE organizations DROP FOREIGN KEY ', @fk)
            );
        ");

        DB::statement("PREPARE stmt FROM @sql;");
        DB::statement("EXECUTE stmt;");
        DB::statement("DEALLOCATE PREPARE stmt;");

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreign('inzynier_id', 'organizations_inzynier_id_foreign')
                ->references('id')
                ->on('contacts')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down()
    {
        DB::statement("
            SET @fk := (
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'organizations'
                  AND COLUMN_NAME = 'inzynier_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            );
        ");

        DB::statement("
            SET @sql := IF(
                @fk IS NULL,
                'SELECT 1',
                CONCAT('ALTER TABLE organizations DROP FOREIGN KEY ', @fk)
            );
        ");

        DB::statement("PREPARE stmt FROM @sql;");
        DB::statement("EXECUTE stmt;");
        DB::statement("DEALLOCATE PREPARE stmt;");

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreign('inzynier_id', 'organizations_inzynier_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }
}
