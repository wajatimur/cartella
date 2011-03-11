
ALTER TABLE auth_accountperm ADD COLUMN bitmask bit(32);
ALTER TABLE auth_groupperm ADD COLUMN bitmask bit(32);
ALTER TABLE docmgr.dm_object_perm ADD COLUMN bitmask bit(8);

ALTER TABLE docmgr.dm_object ALTER COLUMN status DROP NOT NULL;

CREATE TABLE docmgr.dm_locks (
object_id integer NOT NULL,
owner text,
token text,
timeout integer,
created integer,
scope integer,
depth integer,
uri text,
account_id integer,
account_name text
);


ALTER TABLE docmgr.dm_locks OWNER TO postgres;

--
-- Name: dm_locks_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace:
--

CREATE INDEX dm_locks_object_id_idx ON docmgr.dm_locks USING btree (object_id);

CREATE TABLE docmgr.dm_locktoken (
object_id integer NOT NULL, 
account_id integer NOT NULL,
token text NOT NULL
);


ALTER TABLE docmgr.dm_locktoken OWNER TO postgres;

--
-- Name: dm_locktoken_account_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace:
--

CREATE INDEX dm_locktoken_account_id_idx ON docmgr.dm_locktoken USING btree (account_id);


--
-- Name: dm_locktoken_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace:
--

CREATE INDEX dm_locktoken_object_id_idx ON docmgr.dm_locktoken USING btree (object_id);

--
-- Name: dm_properties; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace:
--

CREATE TABLE docmgr.dm_properties (
object_id integer NOT NULL,
data text
);

ALTER TABLE docmgr.dm_savesearch RENAME TO dm_search;

CREATE INDEX dm_search_object_id_idx ON docmgr.dm_search USING btree(object_id);

DROP VIEW docmgr.dm_view_objects;

CREATE VIEW docmgr.dm_view_objects AS
 SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object.filesize, dm_object.object_type, dm_object.token, dm_object.last_modified, dm_object.modified_by, dm_object.hidden, dm_object_parent.object_id, dm_object_parent.parent_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset, dm_object_perm.bitmask, dm_dirlevel.level1, dm_dirlevel.level2
FROM docmgr.dm_object
 LEFT JOIN docmgr.dm_object_parent ON dm_object.id = dm_object_parent.object_id
LEFT JOIN docmgr.dm_object_perm ON dm_object.id = dm_object_perm.object_id
 LEFT JOIN docmgr.dm_dirlevel ON dm_object.id = dm_dirlevel.object_id;

DROP VIEW docmgr.dm_view_perm;
CREATE VIEW docmgr.dm_view_perm AS
  SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object_perm.object_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset, dm_object_perm.bitmask
     FROM docmgr.dm_object
        LEFT JOIN docmgr.dm_object_perm ON dm_object.id = dm_object_perm.object_id;
        

ALTER TABLE auth_accounts ADD COLUMN digest_hash TEXT;

