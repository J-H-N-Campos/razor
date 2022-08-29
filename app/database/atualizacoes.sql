--08/10/2021
GRANT SELECT, UPDATE, INSERT, DELETE ON api_call TO razor;
ALTER TABLE api_call OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON api_token TO razor;
ALTER TABLE api_token OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON api_token_call TO razor;
ALTER TABLE api_token_call OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON bas_person TO razor;
ALTER TABLE bas_person OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON bas_person_company TO razor;
ALTER TABLE bas_person_company OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON bas_person_individual TO razor;
ALTER TABLE bas_person_individual OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_group TO razor;
ALTER TABLE sys_group OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_group_screen TO razor;
ALTER TABLE sys_group_screen OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_menu TO razor;
ALTER TABLE sys_menu OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_screen TO razor;
ALTER TABLE sys_screen OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_user TO razor;
ALTER TABLE sys_user OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_user_access TO razor;
ALTER TABLE sys_user_access OWNER TO razor;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_user_group TO razor;
ALTER TABLE sys_user_group OWNER TO razor;