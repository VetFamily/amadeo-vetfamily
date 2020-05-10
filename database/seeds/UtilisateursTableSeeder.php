<?php

use Illuminate\Database\Seeder;

class UtilisateursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertion des admin
        //DB::table('users')->insert(['name' => 'l.vieira', 'email' => 'laure.vieira@elia-digital.com', 'password' => bcrypt('Vatvotnac4')]);
        //DB::table('users')->insert(['name' => 'e.bertet', 'email' => 'elise.bertet@elia-digital.com', 'password' => bcrypt('ejNeatip7')]);

        // Insertion des utilisateurs
        //DB::table('users')->insert(['name' => 'v.pouillaude', 'email' => '', 'password' => bcrypt('sacJinrek5')]);
        //DB::table('users')->insert(['name' => 'l.nielsen', 'email' => 'nielsen.kolding@clinarcades.com', 'password' => bcrypt('oiwumCaj3')]);

        // Insertion des laboratoires
        /*DB::table('users')->insert(['name' => 'p-y.ruelland@audevard', 'email' => 'ruelland@audevard.com', 'password' => bcrypt('Iaf9ovIvA')]);
        DB::table('users')->insert(['name' => 'g.duvey@axience', 'email' => 'gerard.duvey@axience.fr', 'password' => bcrypt('scavPeth1')]);
        DB::table('users')->insert(['name' => 'p.carceller@bayer', 'email' => 'philippe.carceller@bayer.com', 'password' => bcrypt('ocOrwaged7')]);
        DB::table('users')->insert(['name' => 'j.collet@bimeda', 'email' => 'jcollet@bimeda.com', 'password' => bcrypt('Is1Gles4')]);
        DB::table('users')->insert(['name' => 'j.dormignies@biove', 'email' => 'julien.dormignies@labobiove.com', 'password' => bcrypt('TivEnIn4')]);
        DB::table('users')->insert(['name' => 'a.darnis@boehringer', 'email' => 'arnaud.darnis@boehringer-ingelheim.com', 'password' => bcrypt('NedGoborn3')]);
        //DB::table('users')->insert(['name' => 'boiron', 'email' => '', 'password' => bcrypt('CyolWat1')]);
        DB::table('users')->insert(['name' => 'l.megherbi@ceva', 'email' => 'laurent.megherbi@ceva.com', 'password' => bcrypt('OnyavWib8')]);
        DB::table('users')->insert(['name' => 'l.lartigau@ceva', 'email' => 'laurent.lartigau@ceva.com', 'password' => bcrypt('Idijkoaj7')]);
        DB::table('users')->insert(['name' => 'j.jarnier@coophavet', 'email' => 'jocelyne.jarnier@merial.com', 'password' => bcrypt('Richufwiv6')]);
        DB::table('users')->insert(['name' => 'b.vincent@coophavet', 'email' => 'bruno.vincent@merial.com', 'password' => bcrypt('tanecAt6')]);
        DB::table('users')->insert(['name' => 'm.dolet@dechra', 'email' => 'mario.dolet@dechra.com', 'password' => bcrypt('muOcEpBam3')]);
        DB::table('users')->insert(['name' => 'x.lefebvre@elanco', 'email' => 'lefebvre_xavier@elanco.com', 'password' => bcrypt('nomCyoc1')]);
        DB::table('users')->insert(['name' => 'j-m.bercu@elanco', 'email' => 'bercu_jean_michel@elanco.com', 'password' => bcrypt('DoogQuact0')]);
        DB::table('users')->insert(['name' => 'r.voisin@hills', 'email' => 'renaud_voisin@hillspet.com', 'password' => bcrypt('ThaHul4twa')]);
        DB::table('users')->insert(['name' => 'm.lech@hipra', 'email' => 'mathieu.lech@hipra.com', 'password' => bcrypt('Anrynph1')]);
        DB::table('users')->insert(['name' => 'm.koechlin@merial', 'email' => 'matthieu.koechlin@merial.com', 'password' => bcrypt('CoHoHeip4')]);
        DB::table('users')->insert(['name' => 'l.verriele@merial', 'email' => 'laurence.verriele@merial.com', 'password' => bcrypt('0DraHyimEy')]);
        DB::table('users')->insert(['name' => 'f.crolet@mplabo', 'email' => 'fabienne.crolet@mplabo.eu', 'password' => bcrypt('Beblaic4')]);
        DB::table('users')->insert(['name' => 'a.ramiere@mplabo', 'email' => 'antoine.ramiere@mplabo.eu', 'password' => bcrypt('reudIbUg6')]);
        DB::table('users')->insert(['name' => 'o.khelili@msd', 'email' => 'oussama.khelili@merck.com', 'password' => bcrypt('yishkOpt6')]);
        DB::table('users')->insert(['name' => 'f.allegre@nestlepurina', 'email' => 'florent.allegre@purina.nestle.com', 'password' => bcrypt('uckIojec8')]);
        DB::table('users')->insert(['name' => 'd.allary@osalia', 'email' => 'david.allary@osaliafrance.com', 'password' => bcrypt('VidawvOg5')]);
        DB::table('users')->insert(['name' => 'l.koenig@osalia', 'email' => 'laurence.koenig@osaliafrance.com', 'password' => bcrypt('WelzegHed9')]);
        DB::table('users')->insert(['name' => 'c.moyne-bressand@qalian', 'email' => 'cmoyne-bressand@qalian.com', 'password' => bcrypt('jehildAj4')]);
        DB::table('users')->insert(['name' => 'm.tricotteux@royalcanin', 'email' => 'mathieu.tricotteux@royalcanin.com', 'password' => bcrypt('joosvonEg5')]);
        DB::table('users')->insert(['name' => 'm.vasseur@savetis', 'email' => 'm.vasseur@savetis.com', 'password' => bcrypt('ojMin7dad1')]);
        DB::table('users')->insert(['name' => 'f.marchant@savetis', 'email' => 'f.marchant@savetis.com', 'password' => bcrypt('pejChowb7')]);
        DB::table('users')->insert(['name' => 'j.pouget@tvm', 'email' => 'j.pouget@tvm.fr', 'password' => bcrypt('FiephEtip9')]);
        DB::table('users')->insert(['name' => 'g.brizot@tvm', 'email' => 'g.brizot@tvm.fr', 'password' => bcrypt('Nanyang4')]);
        DB::table('users')->insert(['name' => 'l.robbiani@vetoquinol', 'email' => 'loic.robbiani@vetoquinol.com', 'password' => bcrypt('dedErciv8')]);
        DB::table('users')->insert(['name' => 'j-c.ollier@virbac', 'email' => 'jean-christophe.ollier@virbac.fr', 'password' => bcrypt('zu1liotU')]);
        DB::table('users')->insert(['name' => 'p.maudet@virbac', 'email' => 'pierrick.maudet@virbac.fr', 'password' => bcrypt('UjratojAj6')]);
        DB::table('users')->insert(['name' => 'f.estelle@zoetis', 'email' => 'fabrice.estelle@zoetis.com', 'password' => bcrypt('Konumlad5')]);
        DB::table('users')->insert(['name' => 'p.bellabouvier@zoetis', 'email' => 'patrick.bellabouvier@zoetis.com', 'password' => bcrypt('mobFepMic4')]);
        DB::table('users')->insert(['name' => 'b.hommais@zoetis', 'email' => 'bruno.hommais@zoetis.com', 'password' => bcrypt('ryxRyRaiv0')]);
        DB::table('users')->insert(['name' => 'h.legalludec@zoetis', 'email' => 'herve.legalludec@zoetis.com', 'password' => bcrypt('PoBli6twye')]);
        //DB::table('users')->insert(['name' => 'pommier', 'email' => '', 'password' => bcrypt('Rymjeyst6')]);*/

       /*  DB::table('users')->insert(['name' => 'sleaignel@vetoavenir.com', 'email' => 'sleaignel@vetoavenir.com', 'password' => bcrypt('Panle6Ced')]);
        DB::table('users')->insert(['name' => 'n.lestrat@chenevertconseil.com', 'email' => 'n.lestrat@chenevertconseil.com', 'password' => bcrypt('meuldArc3')]);
        DB::table('users')->insert(['name' => 'j.flori@chenevertconseil.com', 'email' => 'j.flori@chenevertconseil.com', 'password' => bcrypt('dehoojBec6')]);
        DB::table('users')->insert(['name' => 'f.larcher@chenevertconseil.com', 'email' => 'f.larcher@chenevertconseil.com', 'password' => bcrypt('Dic0okcu')]); */
        $id = DB::table('users')->insertGetId(['name' => 'y.juggoo', 'email' => '', 'password' => bcrypt('y.juggoo')]);
        DB::table('role_user')->insert(['user_id' => $id, 'role_id' => 1]);

    }
}
