<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZmianyKadroweTable extends Migration
{
    /**
     * Rejestr zmian pobytów na budowach do obsłużenia przez kadry —
     * lista do odhaczenia, nie strumień powiadomień.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zmiany_kadrowe', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contact_id')->index();
            $table->string('typ', 20)->index();

            $table->integer('organization_from_id')->unsigned()->nullable();
            $table->integer('organization_to_id')->unsigned()->nullable();

            $table->date('old_start')->nullable();
            $table->date('old_end')->nullable();
            $table->date('new_start')->nullable();
            $table->date('new_end')->nullable();

            // Zmiany z jednego przeniesienia trafiają do wspólnej paczki.
            $table->string('paczka', 40)->nullable()->index();

            $table->string('status', 20)->default('nowa')->index();
            $table->unsignedInteger('changed_by')->nullable();
            $table->unsignedInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->text('uwagi')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('changed_by')->references('id')->on('users');
            $table->foreign('handled_by')->references('id')->on('users');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zmiany_kadrowe');
    }
}
