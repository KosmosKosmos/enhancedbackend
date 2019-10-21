<?php namespace KosmosKosmos\EnhancedBackend\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateMenusTable extends Migration
{
    public function up()
    {
        Schema::create('kosmoskosmos_enhancedbackend_menus', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kosmoskosmos_enhancedbackend_menus');
    }
}
