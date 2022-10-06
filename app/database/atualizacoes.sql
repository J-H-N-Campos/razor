--08/10/2022
CREATE TABLE api_call( 
      id  SERIAL    NOT NULL  , 
      class_name text   NOT NULL  , 
      method_name text   NOT NULL  , 
      fl_public boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      fl_restric_admin boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      class_url text   , 
      fl_register_parameters boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      fl_register_return boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      https_method text   , 
      permission text   , 
      fl_on boolean   NOT NULL    DEFAULT 'true NOT NULL', 
 PRIMARY KEY (id)) ; 

CREATE TABLE api_token( 
      id  SERIAL    NOT NULL  , 
      dt_register timestamp   NOT NULL  , 
      name text   NOT NULL  , 
      code text   NOT NULL  , 
      fl_on boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      permission text   , 
      due_date date   , 
      fl_renew boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      days_due text   , 
      dt_renew timestamp   , 
      reference text   , 
      code_encrypt text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE api_token_call( 
      id  SERIAL    NOT NULL  , 
      token_id integer   NOT NULL  , 
      call_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE bas_person( 
      id  SERIAL    NOT NULL  , 
      dt_register timestamp   NOT NULL  , 
      name text   NOT NULL  , 
      code text   , 
      email text   , 
      phone text   NOT NULL  , 
      description text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE bas_person_company( 
      person_id  SERIAL    NOT NULL  , 
      name_fantasy text   , 
      cnpj text   , 
      owner_id integer   , 
 PRIMARY KEY (person_id)) ; 

CREATE TABLE bas_person_individual( 
      person_id  SERIAL    NOT NULL  , 
      birth_date date   , 
      cpf text   , 
      genre char  (1)   , 
 PRIMARY KEY (person_id)) ; 

CREATE TABLE cad_operator( 
      id  SERIAL    NOT NULL  , 
      person_individual_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cad_product( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      fl_enable boolean   NOT NULL    DEFAULT true, 
      price float   NOT NULL  , 
      fl_product boolean   NOT NULL  , 
      qtd_time integer   , 
      description text   , 
      photo text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE fin_sale( 
      id  SERIAL    NOT NULL  , 
      dt_register_sale date   NOT NULL  , 
      status text   NOT NULL  , 
      dt_service timestamp   , 
      product_id integer   NOT NULL  , 
      person_id integer   NOT NULL  , 
      operator_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_group( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      fl_admin boolean   NOT NULL    DEFAULT 'false NOT NULL', 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_group_screen( 
      id  SERIAL    NOT NULL  , 
      screen_id integer   NOT NULL  , 
      group_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_menu( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      icon text   NOT NULL  , 
      sequence integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_screen( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      controller text   NOT NULL  , 
      icon text   NOT NULL  , 
      fl_view_menu boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      fl_public boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      menu_id integer   NOT NULL  , 
      fl_admin boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      helper text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_user( 
      id  SERIAL    NOT NULL  , 
      dt_register timestamp   NOT NULL  , 
      fl_on boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      code text   , 
      pip_code text   , 
      password text   , 
      fl_term boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      description text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_user_group( 
      id  SERIAL    NOT NULL  , 
      user_id integer   NOT NULL  , 
      group_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

 
  
 ALTER TABLE api_token_call ADD CONSTRAINT fk_api_token_call_1 FOREIGN KEY (call_id) references api_call(id); 
ALTER TABLE api_token_call ADD CONSTRAINT fk_api_token_call_2 FOREIGN KEY (token_id) references api_token(id); 
ALTER TABLE bas_person_company ADD CONSTRAINT fk_bas_person_company_1 FOREIGN KEY (owner_id) references bas_person(id); 
ALTER TABLE bas_person_company ADD CONSTRAINT fk_bas_person_company_2 FOREIGN KEY (person_id) references bas_person(id); 
ALTER TABLE bas_person_individual ADD CONSTRAINT fk_bas_person_individual_1 FOREIGN KEY (person_id) references bas_person(id); 
ALTER TABLE cad_operator ADD CONSTRAINT fk_cad_operator_1 FOREIGN KEY (person_individual_id) references bas_person_individual(person_id); 
ALTER TABLE fin_sale ADD CONSTRAINT fk_fin_sale_1 FOREIGN KEY (product_id) references cad_product(id); 
ALTER TABLE fin_sale ADD CONSTRAINT fk_fin_sale_2 FOREIGN KEY (person_id) references bas_person(id); 
ALTER TABLE fin_sale ADD CONSTRAINT fk_fin_sale_3 FOREIGN KEY (operator_id) references cad_operator(id); 
ALTER TABLE sys_group_screen ADD CONSTRAINT fk_sys_group_screen_1 FOREIGN KEY (group_id) references sys_group(id); 
ALTER TABLE sys_group_screen ADD CONSTRAINT fk_sys_group_screen_2 FOREIGN KEY (screen_id) references sys_screen(id); 
ALTER TABLE sys_screen ADD CONSTRAINT fk_sys_screen_1 FOREIGN KEY (menu_id) references sys_menu(id); 
ALTER TABLE sys_user ADD CONSTRAINT fk_sys_user_1 FOREIGN KEY (id) references bas_person(id); 
ALTER TABLE sys_user_group ADD CONSTRAINT fk_sys_user_group_1 FOREIGN KEY (group_id) references sys_group(id); 
ALTER TABLE sys_user_group ADD CONSTRAINT fk_sys_user_group_2 FOREIGN KEY (user_id) references sys_user(id); 

  
SELECT setval('api_call_id_seq', coalesce(max(id),0) + 1, false) FROM api_call;
SELECT setval('api_token_id_seq', coalesce(max(id),0) + 1, false) FROM api_token;
SELECT setval('api_token_call_id_seq', coalesce(max(id),0) + 1, false) FROM api_token_call;
SELECT setval('bas_person_id_seq', coalesce(max(id),0) + 1, false) FROM bas_person;
SELECT setval('bas_person_company_person_id_seq', coalesce(max(person_id),0) + 1, false) FROM bas_person_company;
SELECT setval('bas_person_individual_person_id_seq', coalesce(max(person_id),0) + 1, false) FROM bas_person_individual;
SELECT setval('cad_operator_id_seq', coalesce(max(id),0) + 1, false) FROM cad_operator;
SELECT setval('cad_product_id_seq', coalesce(max(id),0) + 1, false) FROM cad_product;
SELECT setval('fin_sale_id_seq', coalesce(max(id),0) + 1, false) FROM fin_sale;
SELECT setval('sys_group_id_seq', coalesce(max(id),0) + 1, false) FROM sys_group;
SELECT setval('sys_group_screen_id_seq', coalesce(max(id),0) + 1, false) FROM sys_group_screen;
SELECT setval('sys_menu_id_seq', coalesce(max(id),0) + 1, false) FROM sys_menu;
SELECT setval('sys_screen_id_seq', coalesce(max(id),0) + 1, false) FROM sys_screen;
SELECT setval('sys_user_id_seq', coalesce(max(id),0) + 1, false) FROM sys_user;
SELECT setval('sys_user_group_id_seq', coalesce(max(id),0) + 1, false) FROM sys_user_group;