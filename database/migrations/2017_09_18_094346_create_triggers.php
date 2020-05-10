<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTriggers extends Migration {

	public function up()
	{
		\DB::unprepared("
	        CREATE OR REPLACE FUNCTION update_modified_column()	
			RETURNS TRIGGER AS $$
			BEGIN
			    NEW.updated_at = now();
			    RETURN NEW;	
			END;
			$$ language 'plpgsql';
        ");

        \DB::unprepared("
        	CREATE TRIGGER update_user_timestamp BEFORE UPDATE ON users FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_achat_timestamp BEFORE UPDATE ON achats FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_centrale_clinique_timestamp BEFORE UPDATE ON centrale_clinique FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_centrale_produit_timestamp BEFORE UPDATE ON centrale_produit FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_centrale_timestamp BEFORE UPDATE ON centrales FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_clinique_timestamp BEFORE UPDATE ON cliniques FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_espece_produit_timestamp BEFORE UPDATE ON espece_produit FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_espece_timestamp BEFORE UPDATE ON especes FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_laboratoire_timestamp BEFORE UPDATE ON laboratoires FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_produit_type_timestamp BEFORE UPDATE ON produit_type FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_produit_timestamp BEFORE UPDATE ON produits FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
        \DB::unprepared("
        	CREATE TRIGGER update_type_timestamp BEFORE UPDATE ON types FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
	}

	public function down()
	{
		\DB::unprepared('DROP TRIGGER update_user_timestamp;');
		\DB::unprepared('DROP TRIGGER update_achat_timestamp;');
		\DB::unprepared('DROP TRIGGER update_centrale_clinique_timestamp;');
		\DB::unprepared('DROP TRIGGER update_centrale_produit_timestamp;');
		\DB::unprepared('DROP TRIGGER update_centrale_timestamp;');
		\DB::unprepared('DROP TRIGGER update_clinique_timestamp;');
		\DB::unprepared('DROP TRIGGER update_espece_produit_timestamp;');
		\DB::unprepared('DROP TRIGGER update_espece_timestamp;');
		\DB::unprepared('DROP TRIGGER update_laboratoire_timestamp;');
		\DB::unprepared('DROP TRIGGER update_produit_type_timestamp;');
		\DB::unprepared('DROP TRIGGER update_produit_timestamp;');
		\DB::unprepared('DROP TRIGGER update_type_timestamp;');
		\DB::unprepared('DROP FUNCTION update_modified_column;');
	}
}