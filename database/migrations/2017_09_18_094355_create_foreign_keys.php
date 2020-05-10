<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class CreateForeignKeys extends Migration {

	public function up()
	{
		Schema::table('centrale_clinique', function(Blueprint $table) {
			$table->foreign('centrale_id')->references('id')->on('centrales')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('clinique_id')->references('id')->on('cliniques')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('produits', function(Blueprint $table) {
			$table->foreign('laboratoire_id')->references('id')->on('laboratoires')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('centrale_produit', function(Blueprint $table) {
			$table->foreign('centrale_id')->references('id')->on('centrales')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('produit_id')->references('id')->on('produits')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('achats', function(Blueprint $table) {
			$table->foreign('centrale_clinique_id')->references('id')->on('centrale_clinique')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('produit_id')->references('id')->on('produits')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('achats_erreurs', function(Blueprint $table) {
			$table->foreign('centrale_id')->references('id')->on('centrales')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('achats_maj', function(Blueprint $table) {
			$table->foreign('centrale_id')->references('id')->on('centrales')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('produit_type', function(Blueprint $table) {
			$table->foreign('produit_id')->references('id')->on('produits')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('type_id')->references('id')->on('types')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('espece_produit', function(Blueprint $table) {
			$table->foreign('produit_id')->references('id')->on('produits')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('espece_id')->references('id')->on('especes')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('role_user', function(Blueprint $table) {
			$table->foreign('role_id')->references('id')->on('roles')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('user_id')->references('id')->on('users')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('clinique_id')->references('id')->on('cliniques')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('laboratoire_id')->references('id')->on('laboratoires')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('categories', function(Blueprint $table) {
			$table->foreign('laboratoire_id')->references('id')->on('laboratoires')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('categorie_produit', function(Blueprint $table) {
			$table->foreign('categorie_id')->references('id')->on('categories')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('produit_id')->references('id')->on('produits')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
		Schema::table('categorie_espece', function(Blueprint $table) {
			$table->foreign('categorie_id')->references('id')->on('categories')
						->onDelete('restrict')
						->onUpdate('restrict');
			$table->foreign('espece_id')->references('id')->on('especes')
						->onDelete('restrict')
						->onUpdate('restrict');
		});
	}

	public function down()
	{
		Schema::table('centrale_clinique', function(Blueprint $table) {
			$table->dropForeign('centrale_clinique_centrale_id_foreign');
			$table->dropForeign('centrale_clinique_clinique_id_foreign');
		});
		Schema::table('produits', function(Blueprint $table) {
			$table->dropForeign('produits_laboratoire_id_foreign');
		});
		Schema::table('centrale_produit', function(Blueprint $table) {
			$table->dropForeign('centrale_produit_centrale_id_foreign');
			$table->dropForeign('centrale_produit_produit_id_foreign');
		});
		Schema::table('achats', function(Blueprint $table) {
			$table->dropForeign('achats_centrale_clinique_id_foreign');
			$table->dropForeign('achats_produit_id_foreign');
		});
		Schema::table('achats_erreurs', function(Blueprint $table) {
			$table->dropForeign('achats_erreurs_centrale_id_foreign');
		});
		Schema::table('achats_maj', function(Blueprint $table) {
			$table->dropForeign('achats_maj_centrale_id_foreign');
		});
		Schema::table('produit_type', function(Blueprint $table) {
			$table->dropForeign('produit_type_produit_id_foreign');
			$table->dropForeign('produit_type_type_id_foreign');
		});
		Schema::table('espece_produit', function(Blueprint $table) {
			$table->dropForeign('espece_produit_produit_id_foreign');
			$table->dropForeign('espece_produit_espece_id_foreign');
		});
		Schema::table('role_user', function(Blueprint $table) {
			$table->dropForeign('role_user_role_id_foreign');
			$table->dropForeign('role_user_user_id_foreign');
			$table->dropForeign('role_user_clinique_id_foreign');
			$table->dropForeign('role_user_laboratoire_id_foreign');
		});
		Schema::table('categories', function(Blueprint $table) {
			$table->dropForeign('categories_laboratoire_id_foreign');
		});
		Schema::table('categorie_produit', function(Blueprint $table) {
			$table->dropForeign('categorie_produit_categorie_id_foreign');
			$table->dropForeign('categorie_produit_produit_id_foreign');
		});
		Schema::table('categorie_espece', function(Blueprint $table) {
			$table->dropForeign('categorie_espece_categorie_id_foreign');
			$table->dropForeign('categorie_espece_espece_id_foreign');
		});
	}
}