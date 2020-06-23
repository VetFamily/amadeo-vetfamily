-- En tant que postgres
CREATE EXTENSION postgres_fdw;

-- Suppressions
DROP MATERIALIZED VIEW centrale_produit_encours;
DROP MATERIALIZED VIEW achats_autres;
DROP MATERIALIZED VIEW produit_valorisations;
DROP FOREIGN TABLE ed_produit_valorisations;
DROP MATERIALIZED VIEW produit_type;
DROP FOREIGN TABLE ed_produit_type;
DROP MATERIALIZED VIEW types;
DROP FOREIGN TABLE ed_types;
DROP MATERIALIZED VIEW espece_produit;
DROP FOREIGN TABLE ed_espece_produit;
DROP MATERIALIZED VIEW especes;
DROP FOREIGN TABLE ed_especes;
DROP MATERIALIZED VIEW familles_therapeutiques;
DROP FOREIGN TABLE ed_familles_therapeutiques;
DROP MATERIALIZED VIEW centrale_produit_tarifs;
DROP FOREIGN TABLE ed_centrale_produit_tarifs;
DROP MATERIALIZED VIEW centrale_produit_familles_interne;
DROP FOREIGN TABLE ed_centrale_produit_familles_interne;
DROP MATERIALIZED VIEW centrale_produit_denominations;
DROP FOREIGN TABLE ed_centrale_produit_denominations;
DROP MATERIALIZED VIEW centrale_laboratoire;
DROP FOREIGN TABLE ed_centrale_laboratoire;
DROP MATERIALIZED VIEW centrale_produit;
DROP FOREIGN TABLE ed_centrale_produit;
DROP MATERIALIZED VIEW centrales;
DROP FOREIGN TABLE ed_centrales;
DROP MATERIALIZED VIEW produits;
DROP MATERIALIZED VIEW produits_ref;
DROP FOREIGN TABLE ed_produits;
DROP MATERIALIZED VIEW laboratoires;
DROP FOREIGN TABLE ed_laboratoires;

drop USER MAPPING FOR PUBLIC SERVER "foreign_elia-digital_server";
DROP SERVER "foreign_elia-digital_server";

CREATE SERVER "foreign_elia-digital_server"
FOREIGN DATA WRAPPER postgres_fdw 
OPTIONS (host 'foreign_elia-digital_server', port '5432', dbname 'ed-produits');

CREATE USER MAPPING FOR PUBLIC SERVER "foreign_elia-digital_server"
OPTIONS (user 'readonly', password 'IbegTaj8');


-- Familles thérapeutiques
CREATE FOREIGN TABLE ed_familles_therapeutiques (
    id integer not NULL,
    classe1_code varchar(5) NOT NULL,
    classe1_nom varchar(100) NOT NULL,
    classe2_code varchar(5),
    classe2_nom varchar(100),
    classe3_code varchar(5),
    classe3_nom varchar(100),
    obsolete bool NOT NULL DEFAULT false
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'familles_therapeutiques');
ALTER FOREIGN TABLE ed_familles_therapeutiques OWNER TO vetfamily;

CREATE MATERIALIZED VIEW familles_therapeutiques
AS
(
    SELECT id, classe1_code, classe1_nom, classe2_code, classe2_nom, classe3_code, classe3_nom, obsolete
    FROM ed_familles_therapeutiques
)
WITH DATA;
ALTER MATERIALIZED VIEW familles_therapeutiques OWNER TO vetfamily;
CREATE INDEX familles_therapeutiques_id_index ON familles_therapeutiques USING btree (id);
CREATE INDEX familles_therapeutiques_classe1_code_index ON familles_therapeutiques USING btree (classe1_code);
CREATE INDEX familles_therapeutiques_classe1_nom_index ON familles_therapeutiques USING btree (classe1_nom);
CREATE INDEX familles_therapeutiques_classe2_code_index ON familles_therapeutiques USING btree (classe2_code);
CREATE INDEX familles_therapeutiques_classe2_nom_index ON familles_therapeutiques USING btree (classe2_nom);
CREATE INDEX familles_therapeutiques_classe3_code_index ON familles_therapeutiques USING btree (classe3_code);
CREATE INDEX familles_therapeutiques_classe3_nom_index ON familles_therapeutiques USING btree (classe3_nom);
CREATE INDEX familles_therapeutiques_obsolete_index ON familles_therapeutiques USING btree (obsolete);


-- Laboratoires
CREATE FOREIGN TABLE ed_laboratoires (
	id integer not NULL,
    nom character varying(255),
    obsolete boolean
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'laboratoires');
ALTER FOREIGN TABLE ed_laboratoires OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW laboratoires
AS
(SELECT id, nom, obsolete FROM ed_laboratoires where id in (3, 6, 8, 10, 11, 16, 20, 23, 25, 40) or id between 57 and 241)
WITH DATA;
ALTER MATERIALIZED VIEW laboratoires OWNER TO vetfamily;
CREATE INDEX laboratoires_id_index ON laboratoires USING btree (id);
CREATE INDEX laboratoires_nom_index ON laboratoires USING btree (nom);
CREATE INDEX laboratoires_obsolete_index ON laboratoires USING btree (obsolete);

CREATE MATERIALIZED VIEW laboratoires_ref
AS
(SELECT id, nom, obsolete FROM ed_laboratoires)
WITH DATA;
ALTER MATERIALIZED VIEW laboratoires_ref OWNER TO vetfamily;
CREATE INDEX laboratoires_ref_id_index ON laboratoires_ref USING btree (id);
CREATE INDEX laboratoires_ref_nom_index ON laboratoires_ref USING btree (nom);
CREATE INDEX laboratoires_ref_obsolete_index ON laboratoires_ref USING btree (obsolete);



-- Centrales
CREATE FOREIGN TABLE ed_centrales (
	id integer not NULL,
    nom character varying(255),
    obsolete boolean
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'centrales');
ALTER FOREIGN TABLE ed_centrales OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW centrales
AS
SELECT id, nom, obsolete FROM ed_centrales where id in (11, 13)
WITH DATA;
ALTER MATERIALIZED VIEW centrales OWNER TO vetfamily;
CREATE INDEX centrales_id_index ON centrales USING btree (id);
CREATE INDEX centrales_nom_index ON centrales USING btree (nom);
CREATE INDEX centrales_obsolete_index ON centrales USING btree (obsolete);


-- Centrale_laboratoire
CREATE FOREIGN TABLE ed_centrale_laboratoire (
	id integer not NULL,
	nom_laboratoire varchar(255),
    laboratoire_id integer,
    centrale_id integer
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'centrale_laboratoire');
ALTER FOREIGN TABLE ed_centrale_laboratoire OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW centrale_laboratoire
AS
SELECT id, nom_laboratoire, laboratoire_id, centrale_id FROM ed_centrale_laboratoire
WITH DATA;
ALTER MATERIALIZED VIEW centrale_laboratoire OWNER TO vetfamily;
CREATE INDEX centrale_laboratoire_id_index ON centrale_laboratoire USING btree (id);
CREATE INDEX centrale_laboratoire_nom_laboratoire_index ON centrale_laboratoire USING btree (nom_laboratoire);
CREATE INDEX centrale_laboratoire_laboratoire_id_index ON centrale_laboratoire USING btree (laboratoire_id);
CREATE INDEX centrale_laboratoire_centrale_id_index ON centrale_laboratoire USING btree (centrale_id);



-- Centrale_produit
CREATE FOREIGN TABLE ed_centrale_produit (
	id integer not NULL,
	code_produit varchar(60),
	centrale_id integer,
	produit_id integer,
	obsolete boolean,
	date_creation date,
	date_obsolescence date
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'product_source_netherlands');
ALTER FOREIGN TABLE ed_centrale_produit OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW centrale_produit
AS
SELECT id, code_produit, centrale_id, produit_id, obsolete, date_creation, date_obsolescence FROM ed_centrale_produit
WITH DATA;
ALTER MATERIALIZED VIEW centrale_produit OWNER TO vetfamily;
CREATE INDEX centrale_produit_id_index ON centrale_produit USING btree (id);
CREATE INDEX centrale_produit_code_produit_index ON centrale_produit USING btree (code_produit);
CREATE INDEX centrale_centrale_id_index ON centrale_produit USING btree (centrale_id);
CREATE INDEX centrale_produit_produit_id_index ON centrale_produit USING btree (produit_id);
CREATE INDEX centrale_produit_obsolete_index ON centrale_produit USING btree (obsolete);
CREATE INDEX centrale_produit_date_creation_index ON centrale_produit USING btree (date_creation);
CREATE INDEX centrale_produit_date_obsolescence_index ON centrale_produit USING btree (date_obsolescence);


-- Centrale_produit_tarifs
CREATE FOREIGN TABLE ed_centrale_produit_tarifs (
	id integer not NULL,
	centrale_produit_id integer,
	qte_tarif numeric,
	qte_ug numeric,
	prix_unitaire_hors_promo numeric,
	prix_unitaire_promo numeric,
	date_debut_promo varchar(60),
	date_fin_promo varchar(60),
	date_creation date
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'centrale_produit_tarifs');
ALTER FOREIGN TABLE ed_centrale_produit_tarifs OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW centrale_produit_tarifs
AS
SELECT id, centrale_produit_id, qte_tarif, qte_ug, prix_unitaire_hors_promo, prix_unitaire_promo, date_debut_promo, date_fin_promo, date_creation FROM ed_centrale_produit_tarifs
WITH DATA;
ALTER MATERIALIZED VIEW centrale_produit_tarifs OWNER TO vetfamily;
CREATE INDEX centrale_produit_tarifs_id_index ON centrale_produit_tarifs USING btree (id);
CREATE INDEX centrale_produit_tarifs_centrale_produit_id_index ON centrale_produit_tarifs USING btree (centrale_produit_id);
CREATE INDEX centrale_produit_tarifs_qte_tarif_index ON centrale_produit_tarifs USING btree (qte_tarif);
CREATE INDEX centrale_produit_tarifs_qte_ug_index ON centrale_produit_tarifs USING btree (qte_ug);
CREATE INDEX centrale_produit_tarifs_prix_unitaire_hors_promo_index ON centrale_produit_tarifs USING btree (prix_unitaire_hors_promo);
CREATE INDEX centrale_produit_tarifs_prix_unitaire_promo_index ON centrale_produit_tarifs USING btree (prix_unitaire_promo);
CREATE INDEX centrale_produit_tarifs_date_debut_promo_index ON centrale_produit_tarifs USING btree (date_debut_promo);
CREATE INDEX centrale_produit_tarifs_date_fin_promo_index ON centrale_produit_tarifs USING btree (date_fin_promo);
CREATE INDEX centrale_produit_tarifs_date_creation_index ON centrale_produit_tarifs USING btree (date_creation);


-- Centrale_produit_familles_interne
CREATE FOREIGN TABLE ed_centrale_produit_familles_interne (
	id integer not NULL,
	centrale_produit_id integer,
    categorie varchar(20),
	famille varchar(20),
	sous_famille varchar(20),
	famille_commerciale varchar(20),
	date_creation date
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'centrale_produit_familles_interne');
ALTER FOREIGN TABLE ed_centrale_produit_familles_interne OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW centrale_produit_familles_interne
AS
SELECT id, centrale_produit_id, categorie, famille, sous_famille, famille_commerciale, date_creation FROM ed_centrale_produit_familles_interne
WITH DATA;
ALTER MATERIALIZED VIEW centrale_produit_familles_interne OWNER TO vetfamily;
CREATE INDEX centrale_produit_familles_interne_id_index ON centrale_produit_familles_interne USING btree (id);
CREATE INDEX centrale_produit_familles_interne_centrale_produit_id_index ON centrale_produit_familles_interne USING btree (centrale_produit_id);
CREATE INDEX centrale_produit_familles_interne_categorie_index ON centrale_produit_familles_interne USING btree (categorie);
CREATE INDEX centrale_produit_familles_interne_famille_index ON centrale_produit_familles_interne USING btree (famille);
CREATE INDEX centrale_produit_familles_interne_sous_famille_index ON centrale_produit_familles_interne USING btree (sous_famille);
CREATE INDEX centrale_produit_familles_interne_famille_commerciale_index ON centrale_produit_familles_interne USING btree (famille_commerciale);
CREATE INDEX centrale_produit_familles_interne_date_creation_index ON centrale_produit_familles_interne USING btree (date_creation);


-- Centrale_produit_denominations
CREATE FOREIGN TABLE ed_centrale_produit_denominations (
	id integer not NULL,
	centrale_produit_id integer,
	nom varchar(255),
	date_creation date
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'centrale_produit_denominations');
ALTER FOREIGN TABLE ed_centrale_produit_denominations OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW centrale_produit_denominations
AS
SELECT id, centrale_produit_id, nom, date_creation FROM ed_centrale_produit_denominations
WITH DATA;
ALTER MATERIALIZED VIEW centrale_produit_denominations OWNER TO vetfamily;
CREATE INDEX centrale_produit_denominations_id_index ON centrale_produit_denominations USING btree (id);
CREATE INDEX centrale_produit_denominations_centrale_produit_id_index ON centrale_produit_denominations USING btree (centrale_produit_id);
CREATE INDEX centrale_produit_denominations_nom_index ON centrale_produit_denominations USING btree (nom);
CREATE INDEX centrale_produit_denominations_date_creation_index ON centrale_produit_denominations USING btree (date_creation);


-- Especes
CREATE FOREIGN TABLE ed_especes (
	id integer not NULL,
    nom character varying(255),
    obsolete boolean
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'specie_netherlands');
ALTER FOREIGN TABLE ed_especes OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW especes
AS
SELECT id, nom, obsolete FROM ed_especes
WITH DATA;
ALTER MATERIALIZED VIEW especes OWNER TO vetfamily;
CREATE INDEX especes_id_index ON especes USING btree (id);
CREATE INDEX especes_nom_index ON especes USING btree (nom);
CREATE INDEX especes_obsolete_index ON especes USING btree (obsolete);


-- Espece_produit
CREATE FOREIGN TABLE ed_espece_produit (
	id integer not NULL,
	produit_id integer,
	espece_id integer
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'espece_produit');
ALTER FOREIGN TABLE ed_espece_produit OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW espece_produit
AS
SELECT id, produit_id, espece_id FROM ed_espece_produit
WITH DATA;
ALTER MATERIALIZED VIEW espece_produit OWNER TO vetfamily;
CREATE INDEX espece_produit_id_index ON espece_produit USING btree (id);
CREATE INDEX espece_produit_produit_id_index ON espece_produit USING btree (produit_id);
CREATE INDEX espece_produit_espece_id_index ON espece_produit USING btree (espece_id);



-- Produits
CREATE FOREIGN TABLE ed_produits (
	id integer not NULL,
    code_gtin character varying(60),
    code_gtin_autre character varying(60),
    denomination character varying(255),
    conditionnement character varying(255),
    laboratoire_id integer,
    obsolete boolean,
    invisible boolean,
    valo_volume numeric,
    unite_valo_volume character varying(100),
    famille_therapeutique_id int4
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'product_netherlands');
ALTER FOREIGN TABLE ed_produits OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW produits
AS
SELECT ed_produits.id, code_gtin, code_gtin_autre, denomination, conditionnement, laboratoire_id, ed_produits.obsolete, invisible, valo_volume, unite_valo_volume, famille_therapeutique_id FROM ed_produits join laboratoires on laboratoires.id = ed_produits.laboratoire_id
WITH DATA;
ALTER MATERIALIZED VIEW produits OWNER TO vetfamily;
CREATE INDEX produits_id_index ON produits USING btree (id);
CREATE INDEX produits_code_gtin_index ON produits USING btree (code_gtin);
CREATE INDEX produits_code_gtin_autre_index ON produits USING btree (code_gtin_autre);
CREATE INDEX produits_denomination_index ON produits USING btree (denomination);
CREATE INDEX produits_conditionnement_index ON produits USING btree (conditionnement);
CREATE INDEX produits_laboratoire_id_index ON produits USING btree (laboratoire_id);
CREATE INDEX produits_obsolete_index ON produits USING btree (obsolete);
CREATE INDEX produits_invisible_index ON produits USING btree (invisible);
CREATE INDEX produits_valo_volume_index ON produits USING btree (valo_volume);
CREATE INDEX produits_unite_valo_volume_index ON produits USING btree (unite_valo_volume);
CREATE INDEX produits_famille_therapeutique_id_index ON produits USING btree (famille_therapeutique_id);

CREATE MATERIALIZED VIEW produits_ref
AS
SELECT id, code_gtin, code_gtin_autre, denomination, conditionnement, laboratoire_id, obsolete, invisible, valo_volume, unite_valo_volume, famille_therapeutique_id FROM ed_produits
WITH DATA;
ALTER MATERIALIZED VIEW produits_ref OWNER TO vetfamily;
CREATE INDEX produits_ref_id_index ON produits_ref USING btree (id);
CREATE INDEX produits_ref_code_gtin_index ON produits_ref USING btree (code_gtin);
CREATE INDEX produits_ref_code_gtin_autre_index ON produits_ref USING btree (code_gtin_autre);
CREATE INDEX produits_ref_denomination_index ON produits_ref USING btree (denomination);
CREATE INDEX produits_ref_conditionnement_index ON produits_ref USING btree (conditionnement);
CREATE INDEX produits_ref_laboratoire_id_index ON produits_ref USING btree (laboratoire_id);
CREATE INDEX produits_ref_obsolete_index ON produits_ref USING btree (obsolete);
CREATE INDEX produits_ref_invisible_index ON produits_ref USING btree (invisible);
CREATE INDEX produits_ref_valo_volume_index ON produits_ref USING btree (valo_volume);
CREATE INDEX produits_ref_unite_valo_volume_index ON produits_ref USING btree (unite_valo_volume);
CREATE INDEX produits_ref_famille_therapeutique_id_index ON produits_ref USING btree (famille_therapeutique_id);


-- Types
CREATE FOREIGN TABLE ed_types (
	id integer not NULL,
    nom character varying(255),
    obsolete boolean
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'type_netherlands');
ALTER FOREIGN TABLE ed_types OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW types
AS
SELECT id, nom, obsolete FROM ed_types
WITH DATA;
ALTER MATERIALIZED VIEW types OWNER TO vetfamily;
CREATE INDEX types_id_index ON types USING btree (id);
CREATE INDEX types_nom_index ON types USING btree (nom);
CREATE INDEX types_obsolete_index ON types USING btree (obsolete);


-- Produit_type
CREATE FOREIGN TABLE ed_produit_type (
	id integer not NULL,
    produit_id integer,
    type_id integer
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'produit_type');
ALTER FOREIGN TABLE ed_produit_type OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW produit_type
AS
SELECT id, produit_id, type_id FROM ed_produit_type
WITH DATA;
ALTER MATERIALIZED VIEW produit_type OWNER TO vetfamily;
CREATE INDEX produit_type_id_index ON produit_type USING btree (id);
CREATE INDEX produit_type_produit_id_index ON produit_type USING btree (produit_id);
CREATE INDEX produit_type_type_id_index ON produit_type USING btree (type_id);


-- Produit_valorisations
CREATE FOREIGN TABLE ed_produit_valorisations (
	id integer not NULL,
    produit_id integer,
    valo_euro numeric,
    date_debut date,
    date_fin date
)
SERVER "foreign_elia-digital_server"
OPTIONS (schema_name 'public', table_name 'produit_valorisations');
ALTER FOREIGN TABLE ed_produit_valorisations OWNER TO vetfamily;
    
CREATE MATERIALIZED VIEW produit_valorisations
AS
SELECT id, produit_id, valo_euro, date_debut, date_fin FROM ed_produit_valorisations
WITH DATA;
ALTER MATERIALIZED VIEW produit_valorisations OWNER TO vetfamily;
CREATE INDEX produit_valorisations_id_index ON produit_valorisations USING btree (id);
CREATE INDEX produit_valorisations_produit_id_index ON produit_valorisations USING btree (produit_id);
CREATE INDEX produit_valorisations_valo_euro_index ON produit_valorisations USING btree (valo_euro);
CREATE INDEX produit_valorisations_date_debut_index ON produit_valorisations USING btree (date_debut);
CREATE INDEX produit_valorisations_date_fin_index ON produit_valorisations USING btree (date_fin);


-- Achats_autres
CREATE MATERIALIZED VIEW achats_autres
TABLESPACE pg_default
AS 
(
    SELECT a.produit_id, a.centrale_clinique_id, a.date, sum(a.qte_gratuite_complet) AS qte_gratuite_complet, sum(a.qte_payante_complet) AS qte_payante_complet, sum(a.ca_complet) AS ca_complet
    FROM achats a
    JOIN produits_ref p ON p.id = a.produit_id
    WHERE p.laboratoire_id != 56
	and ((p.laboratoire_id = 40)
		or (p.invisible is true AND a.obsolete IS false)
		or (p.laboratoire_id not in (select id from laboratoires) AND a.obsolete IS false))
    GROUP BY a.produit_id, a.centrale_clinique_id, a.date, a.obsolete
)
WITH DATA;
ALTER MATERIALIZED VIEW achats_autres OWNER TO vetfamily;
CREATE INDEX achats_autres_produit_id_index ON achats_autres USING btree (produit_id);
CREATE INDEX achats_autres_centrale_clinique_id_index ON achats_autres USING btree (centrale_clinique_id);
CREATE INDEX achats_autres_date_index ON achats_autres USING btree (date);
CREATE INDEX achats_autres_qte_gratuite_complet_index ON achats_autres USING btree (qte_gratuite_complet);
CREATE INDEX achats_autres_qte_payante_complet_index ON achats_autres USING btree (qte_payante_complet);
CREATE INDEX achats_autres_ca_complet_index ON achats_autres USING btree (ca_complet);



CREATE MATERIALIZED VIEW centrale_produit_encours
AS
(
	select distinct cp.id as centrale_produit_id, cpd_mois.nom as denomination_mois, cpd_mois.date_creation as denomination_mois_date, cpt_mois.prix as prix_unitaire_mois, cpt_mois.date_creation as prix_unitaire_mois_date, cpd_max.nom as denomination_max, cpd_max.date_creation as denomination_max_date, cpt_max.prix as prix_unitaire_max, cpt_max.date_creation as prix_unitaire_max_date
	from produits p
	join centrale_produit cp ON cp.produit_id = p.id
	join centrales ce on ce.id = cp.centrale_id and ce.obsolete is false
	left join (select distinct cp.id as cp_id, cpd.nom, cpd.date_creation from centrale_produit cp join centrale_produit_denominations cpd on cpd.centrale_produit_id = cp.id and cpd.date_creation = date_trunc('MONTH',now())::DATE) cpd_mois on cpd_mois.cp_id = cp.id
	left join (select distinct cp.id as cp_id, cpd.nom, cpd.date_creation from (select cp.id, MAX(cpd.date_creation) as date_max from centrale_produit cp join centrale_produit_denominations cpd on cpd.centrale_produit_id = cp.id group by cp.id) cp join centrale_produit_denominations cpd on cpd.centrale_produit_id = cp.id and cpd.date_creation = cp.date_max) cpd_max on cpd_max.cp_id = cp.id
	left join (select distinct cp.id as cp_id, max(cpt.prix_unitaire_hors_promo) as prix, cpt.date_creation from centrale_produit cp join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and cpt.date_creation = date_trunc('MONTH',now())::DATE group by cp.id, cpt.date_creation) cpt_mois on cpt_mois.cp_id = cp.id
	left join (select distinct cp.id as cp_id, max(cpt.prix_unitaire_hors_promo) as prix, cpt.date_creation from (select cp.id, MAX(cpt.date_creation) as date_max from centrale_produit cp join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id group by cp.id) cp join centrale_produit_tarifs cpt on cpt.centrale_produit_id = cp.id and cpt.date_creation = cp.date_max group by cp.id, cpt.date_creation) cpt_max on cpt_max.cp_id = cp.id
)
WITH DATA;
ALTER MATERIALIZED VIEW centrale_produit_encours OWNER TO vetfamily;
CREATE INDEX centrale_produit_encours_centrale_produit_id_index ON centrale_produit_encours USING btree (centrale_produit_id);
CREATE INDEX centrale_produit_encours_denomination_mois_index ON centrale_produit_encours USING btree (denomination_mois);
CREATE INDEX centrale_produit_encours_denomination_mois_date_index ON centrale_produit_encours USING btree (denomination_mois_date);
CREATE INDEX centrale_produit_encours_denomination_max_index ON centrale_produit_encours USING btree (denomination_max);
CREATE INDEX centrale_produit_encours_denomination_max_date_index ON centrale_produit_encours USING btree (denomination_max_date);
CREATE INDEX centrale_produit_encours_prix_unitaire_mois_index ON centrale_produit_encours USING btree (prix_unitaire_mois);
CREATE INDEX centrale_produit_encours_prix_unitaire_mois_date_index ON centrale_produit_encours USING btree (prix_unitaire_mois_date);
CREATE INDEX centrale_produit_encours_prix_unitaire_max_index ON centrale_produit_encours USING btree (prix_unitaire_max);
CREATE INDEX centrale_produit_encours_prix_unitaire_max_sdate_index ON centrale_produit_encours USING btree (prix_unitaire_max_date);
