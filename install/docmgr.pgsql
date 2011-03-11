--
-- PostgreSQL database dump
--

-- COMMENTED OUT
-- SET statement_timeout = 0;
-- SET client_encoding = 'LATIN1';
-- SET standard_conforming_strings = off;
-- SET check_function_bodies = false;
-- SET client_min_messages = warning;
-- SET escape_string_warning = off;

-- Create our Schemas
CREATE SCHEMA addressbook;
CREATE SCHEMA docmgr;
CREATE SCHEMA logger;
CREATE SCHEMA modlet;
CREATE SCHEMA task;


-- Create our plpgsql language and functions
CREATE PROCEDURAL LANGUAGE plpgsql;


SET search_path = docmgr, pg_catalog;

--
-- Name: getobjfrompath(text); Type: FUNCTION; Schema: docmgr; Owner: postgres
--

CREATE FUNCTION getobjfrompath(path text) RETURNS integer
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE arr text[];
DECLARE parent integer;
DECLARE i integer;

BEGIN

     arr := string_to_array(path,'/');
     parent := 0;

     FOR i IN array_lower(arr,1)+1 .. array_upper(arr,1) LOOP

		SELECT INTO parent object_id FROM docmgr.dm_view_objects WHERE parent_id=parent AND name=arr[i];

     END LOOP;

     RETURN parent;

END;
$$;


--
-- Name: getobjpath(integer, text); Type: FUNCTION; Schema: docmgr; Owner: postgres
--

CREATE FUNCTION getobjpath(objid integer, path text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE result text;
DECLARE tempresult text;
BEGIN
     IF path = '' THEN
         result := objid::text;
     ELSE
         result := path;
     END IF;

     IF objid <> '0' THEN
		SELECT parent_id INTO tempresult FROM docmgr.dm_object_parent WHERE object_id=objid LIMIT 1;
	     result := result || ',' || tempresult::text;
		result := docmgr.getobjpath(tempresult::integer,result);

	END IF;

	RETURN result;

END;
$$;


--
-- Name: getobjpathname(integer, text); Type: FUNCTION; Schema: docmgr; Owner: postgres
--

CREATE FUNCTION getobjpathname(objid integer, path text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE result text;
DECLARE rec record;

BEGIN
     IF path = '' THEN
         result := '';
     ELSE
         result := path;
     END IF;

     IF objid <> '0' THEN
		SELECT name,parent_id INTO rec FROM docmgr.dm_view_objects WHERE id=objid LIMIT 1;

	        IF result = '' THEN
                   result := rec.name;
                ELSE 
                   result := rec.name || '/' || result;
                END IF;

		result := docmgr.getobjpathname(rec.parent_id,result);

     ELSE 
         result := '/' || result;

	END IF;

	RETURN result;

END;
$$;

CREATE FUNCTION docmgr.path_to_id(path text) RETURNS text
    LANGUAGE plpgsql IMMUTABLE
    AS $$
DECLARE arr text[];
DECLARE parent integer;
DECLARE i integer;
DECLARE parentstr text;

BEGIN

     arr := string_to_array(path,'/');
     parent := 0;
    parentstr := 0;

     FOR i IN array_lower(arr,1)+1 .. array_upper(arr,1) LOOP

	    SELECT INTO parent object_id FROM docmgr.dm_view_objects WHERE parent_id=parent AND name=arr[i];

	   SELECT INTO parentstr (parentstr || ',' || parent);
	
     END LOOP;

     RETURN parentstr;

END;
$$;  


SET search_path = addressbook, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: contact; Type: TABLE; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE TABLE contact (
    id integer NOT NULL,
    first_name text,
    middle_name text,
    last_name text,
    address text,
    address2 text,
    city text,
    state character varying(2),
    zip character varying(10),
    country text,
    home_phone numeric,
    home_fax numeric,
    work_phone numeric,
    work_fax numeric,
    mobile numeric,
    pager numeric,
    email text,
    prefix text,
    suffix text,
    letter_salutation text,
    envelope_salutation text,
    website text,
    company_name text,
    last_modified timestamp without time zone,
    work_ext text
);


--
-- Name: contact_account; Type: TABLE; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE TABLE contact_account (
    contact_id integer NOT NULL,
    account_id integer NOT NULL,
    account_name text
);


--
-- Name: contact_id_seq; Type: SEQUENCE; Schema: addressbook; Owner: postgres
--

CREATE SEQUENCE contact_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: contact_id_seq; Type: SEQUENCE OWNED BY; Schema: addressbook; Owner: postgres
--

ALTER SEQUENCE contact_id_seq OWNED BY contact.id;


--
-- Name: contact_id_seq; Type: SEQUENCE SET; Schema: addressbook; Owner: postgres
--

SELECT pg_catalog.setval('contact_id_seq', 1, true);


--
-- Name: view_contact; Type: VIEW; Schema: addressbook; Owner: postgres
--

CREATE VIEW view_contact AS
    SELECT contact.id, contact.first_name, contact.middle_name, contact.last_name, contact.address, contact.address2, contact.city, contact.state, contact.zip, contact.country, contact.home_phone, contact.home_fax, contact.work_phone, contact.work_fax, contact.mobile, contact.pager, contact.email, contact.prefix, contact.suffix, contact.letter_salutation, contact.envelope_salutation, contact.website, contact.company_name, contact.last_modified, contact.work_ext, contact_account.account_id, contact_account.account_name FROM (contact LEFT JOIN contact_account ON ((contact.id = contact_account.contact_id)));


SET search_path = docmgr, pg_catalog;

--
-- Name: dm_alert; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_alert (
    id integer DEFAULT nextval(('docmgr.dm_alert_id_seq'::text)::regclass) NOT NULL,
    object_id integer,
    account_id integer,
    alert_type text
);


--
-- Name: dm_alert_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_alert_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_alert_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_alert_id_seq OWNED BY dm_alert.id;


--
-- Name: dm_alert_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_alert_id_seq', 1, false);


--
-- Name: dm_bookmark; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_bookmark (
    id integer NOT NULL,
    object_id integer NOT NULL,
    account_id integer NOT NULL,
    name text NOT NULL,
    chroot boolean DEFAULT false,
    expandable boolean DEFAULT true,
    protected boolean DEFAULT false
);


--
-- Name: dm_bookmark_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_bookmark_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_bookmark_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_bookmark_id_seq OWNED BY dm_bookmark.id;


--
-- Name: dm_bookmark_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_bookmark_id_seq', 1, false);


--
-- Name: dm_dirlevel; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_dirlevel (
    object_id integer,
    level1 smallint,
    level2 smallint
);


--
-- Name: dm_discussion; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_discussion (
    id integer DEFAULT nextval(('docmgr.dm_discussion_id_seq'::text)::regclass) NOT NULL,
    object_id bigint NOT NULL,
    header text,
    account_id bigint NOT NULL,
    content text,
    owner bigint NOT NULL,
    time_stamp timestamp without time zone
);


--
-- Name: dm_discussion_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_discussion_id_seq
    START WITH 1
    INCREMENT BY 1
    MAXVALUE 2147483647
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_discussion_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_discussion_id_seq', 1, false);


--
-- Name: dm_document; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_document (
    id integer DEFAULT nextval(('docmgr.dm_document_id_seq'::text)::regclass) NOT NULL,
    object_id bigint NOT NULL,
    version bigint DEFAULT 1 NOT NULL,
    modify timestamp without time zone NOT NULL,
    object_owner bigint NOT NULL,
    notes text
);


--
-- Name: dm_document_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_document_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_document_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_document_id_seq OWNED BY dm_document.id;


--
-- Name: dm_document_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_document_id_seq', 1, false);


--
-- Name: dm_email_anon; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_email_anon (
    object_id integer,
    pin text,
    link_encoded text,
    date_expires timestamp without time zone,
    account_id integer,
    notify text,
    dest_email text
);


--
-- Name: dm_file_history; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_file_history (
    id integer DEFAULT nextval(('docmgr.dm_file_history_id_seq'::text)::regclass) NOT NULL,
    object_id bigint NOT NULL,
    version bigint DEFAULT 1 NOT NULL,
    modify timestamp without time zone NOT NULL,
    object_owner bigint NOT NULL,
    notes text,
    md5sum text,
    size numeric DEFAULT 0,
    name text,
    custom_version text
);


--
-- Name: dm_file_history_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_file_history_id_seq
    START WITH 1
    INCREMENT BY 1
    MAXVALUE 2147483647
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_file_history_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_file_history_id_seq', 1, false);


--
-- Name: dm_index; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_index (
    object_id integer NOT NULL,
    idxtext text,
    idxfti tsvector
);


--
-- Name: dm_index_queue; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_index_queue (
    id integer DEFAULT nextval(('docmgr.dm_index_queue_id_seq'::text)::regclass) NOT NULL,
    object_id integer,
    account_id integer,
    notify_user boolean,
    create_date timestamp without time zone
);


--
-- Name: dm_index_queue_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_index_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_index_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_index_queue_id_seq OWNED BY dm_index_queue.id;


--
-- Name: dm_index_queue_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_index_queue_id_seq', 1, false);


--
-- Name: dm_keyword; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_keyword (
    object_id integer NOT NULL,
    field1 text,
    field2 text,
    field3 text,
    field4 text,
    field5 text,
    field6 text
);


CREATE TABLE dm_locks (
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


--
-- Name: dm_locks_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_locks_object_id_idx ON dm_locks USING btree (object_id);

CREATE TABLE dm_locktoken (
    object_id integer NOT NULL,
    account_id integer NOT NULL,
    token text NOT NULL
);


--
-- Name: dm_locktoken_account_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_locktoken_account_id_idx ON dm_locktoken USING btree (account_id);


--
-- Name: dm_locktoken_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_locktoken_object_id_idx ON dm_locktoken USING btree (object_id);






--
-- Name: dm_object; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_object (
    id integer DEFAULT nextval(('docmgr.dm_object_id_seq'::text)::regclass) NOT NULL,
    name text NOT NULL,
    summary text,
    create_date timestamp without time zone,
    object_owner integer,
    status smallint,
    status_date timestamp without time zone,
    status_owner integer,
    version integer DEFAULT 1 NOT NULL,
    reindex smallint DEFAULT 0,
    filesize text,
    object_type text,
    token text,
    last_modified timestamp without time zone,
    modified_by integer,
    hidden boolean DEFAULT false,
    protected boolean DEFAULT false
);


--
-- Name: dm_object_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_object_id_seq
    START WITH 1
    INCREMENT BY 1
    MAXVALUE 2147483647
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_object_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_object_id_seq', 1, false);


--
-- Name: dm_object_log; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_object_log (
    object_id integer,
    account_id integer,
    log_time timestamp without time zone,
    log_type text,
    log_data text
);


--
-- Name: dm_object_parent; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_object_parent (
    object_id integer,
    parent_id integer,
		account_id integer,
	share boolean default false,
	workflow_id integer default 0
);


--
-- Name: dm_object_perm; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_object_perm (
    object_id integer NOT NULL,
    account_id integer,
    group_id integer,
    bitset smallint,
		bitmask bit(8),
	share boolean default false,
	workflow_id integer default 0
);


--
-- Name: dm_object_related; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_object_related (
    object_id integer NOT NULL,
    related_id integer NOT NULL
);


--
-- Name: dm_object_type_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_object_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_object_type_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_object_type_id_seq', 1, false);

--
-- Name: dm_properties; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_properties (
    object_id integer NOT NULL,
    data text
);


--
-- Name: dm_saveroute; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_saveroute (
    id integer DEFAULT nextval(('docmgr.dm_saveroute_id_seq'::text)::regclass) NOT NULL,
    account_id integer,
    name text
);


--
-- Name: dm_saveroute_data; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_saveroute_data (
    account_id integer,
    task_type text,
    task_notes text,
    date_due integer,
    sort_order smallint,
    save_id integer
);


--
-- Name: dm_saveroute_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_saveroute_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_saveroute_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_saveroute_id_seq OWNED BY dm_saveroute.id;


--
-- Name: dm_saveroute_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_saveroute_id_seq', 1, false);


--
-- Name: dm_search; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_search (
    object_id integer NOT NULL,
		params text
);

--
-- Name: dm_subscribe; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_subscribe (
    object_id integer,
    account_id integer,
    send_email boolean,
    event_type text,
    send_file boolean
);


--
-- Name: dm_tag; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_tag (
    id integer NOT NULL,
    name text NOT NULL,
    account_id integer
);


--
-- Name: dm_tag_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_tag_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_tag_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_tag_id_seq OWNED BY dm_tag.id;


--
-- Name: dm_tag_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_tag_id_seq', 1, false);


--
-- Name: dm_tag_link; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_tag_link (
    tag_id integer NOT NULL,
    object_id integer NOT NULL
);


--
-- Name: dm_task; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_task (
    account_id integer,
    task_id integer,
    alert_type text,
    date_due timestamp without time zone
);


--
-- Name: dm_workflow; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_workflow (
    id integer DEFAULT nextval(('docmgr.dm_workflow_id_seq'::text)::regclass) NOT NULL,
    object_id integer NOT NULL,
    absolute_due timestamp without time zone,
    date_complete timestamp without time zone,
    status text,
    account_id integer,
    date_create timestamp without time zone,
    email_notify boolean,
		expire_notify boolean
);


--
-- Name: dm_workflow_route; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_workflow_route (
    id integer DEFAULT nextval(('docmgr.dm_workflow_route_id_seq'::text)::regclass) NOT NULL,
    workflow_id integer NOT NULL,
    account_id integer NOT NULL,
    task_type text,
    date_due timestamp without time zone,
    date_complete timestamp without time zone,
    status text,
    sort_order smallint,
    comment text,
    task_notes text
);


--
-- Name: dm_task_view; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_task_view AS
    SELECT dm_task.account_id, dm_task.task_id, dm_task.alert_type, dm_workflow.object_id, dm_workflow_route.id AS route_id, dm_workflow_route.date_due, dm_workflow_route.task_notes, dm_object.name FROM (((dm_task LEFT JOIN dm_workflow_route ON ((dm_task.task_id = dm_workflow_route.id))) LEFT JOIN dm_workflow ON ((dm_workflow_route.workflow_id = dm_workflow.id))) LEFT JOIN dm_object ON ((dm_workflow.object_id = dm_object.id)));


--
-- Name: dm_thumb_queue; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_thumb_queue (
    id integer DEFAULT nextval(('docmgr.dm_thumb_queue_id_seq'::text)::regclass) NOT NULL,
    object_id integer,
    account_id integer,
    notify_user boolean,
    create_date timestamp without time zone
);


--
-- Name: dm_thumb_queue_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_thumb_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_thumb_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_thumb_queue_id_seq OWNED BY dm_thumb_queue.id;


--
-- Name: dm_thumb_queue_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_thumb_queue_id_seq', 1, false);


--
-- Name: dm_url; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_url (
    object_id integer NOT NULL,
    url text NOT NULL
);


--
-- Name: dm_view_alert; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_alert AS
    SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_alert.id AS alert_id, dm_alert.object_id, dm_alert.account_id, dm_alert.alert_type FROM dm_object, dm_alert WHERE (dm_object.id = dm_alert.object_id);


--
-- Name: dm_view_collections; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW docmgr.dm_view_collections AS
SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, 
dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object.hidden, dm_object_parent.object_id, 
dm_object_parent.parent_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset,dm_object_perm.bitmask
   FROM docmgr.dm_object
   LEFT JOIN docmgr.dm_object_parent ON dm_object.id = dm_object_parent.object_id
   LEFT JOIN docmgr.dm_object_perm ON dm_object.id = dm_object_perm.object_id
  WHERE dm_object.object_type = 'collection'::text;

--
-- Name: dm_view_full_search; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_full_search AS
    SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object.filesize, dm_object.object_type, dm_object.token, dm_object.last_modified, dm_object.modified_by, dm_index.idxfti, dm_dirlevel.level1, dm_dirlevel.level2 FROM ((dm_object LEFT JOIN dm_index ON ((dm_object.id = dm_index.object_id))) LEFT JOIN dm_dirlevel ON ((dm_object.id = dm_dirlevel.object_id)));


--
-- Name: dm_view_keyword; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_keyword AS
    SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.filesize, dm_keyword.object_id, dm_keyword.field1, dm_keyword.field2, dm_keyword.field3, dm_keyword.field4, dm_keyword.field5, dm_keyword.field6, dm_object_parent.parent_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset FROM (((dm_object LEFT JOIN dm_keyword ON ((dm_object.id = dm_keyword.object_id))) LEFT JOIN dm_object_parent ON ((dm_object.id = dm_object_parent.object_id))) LEFT JOIN dm_object_perm ON ((dm_object.id = dm_object_perm.object_id)));


--
-- Name: dm_view_objects; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_objects AS
    SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object.filesize, dm_object.object_type, dm_object.token, dm_object.last_modified, dm_object.modified_by, dm_object.hidden, dm_object_parent.object_id, dm_object_parent.parent_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset,bitmask, dm_dirlevel.level1, dm_dirlevel.level2 FROM (((dm_object LEFT JOIN dm_object_parent ON ((dm_object.id = dm_object_parent.object_id))) LEFT JOIN dm_object_perm ON ((dm_object.id = dm_object_perm.object_id))) LEFT JOIN dm_dirlevel ON ((dm_object.id = dm_dirlevel.object_id)));


--
-- Name: dm_view_perm; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_perm AS
 SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object_perm.object_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset, dm_object_perm.bitmask
   FROM dm_object
   LEFT JOIN dm_object_perm ON dm_object.id = dm_object_perm.object_id;


--
-- Name: dm_view_related; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_related AS
    SELECT dm_object_related.object_id, dm_object_related.related_id, dm_object.name, dm_object.object_type FROM (dm_object_related LEFT JOIN dm_object ON ((dm_object_related.related_id = dm_object.id)));


--
-- Name: dm_view_search; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_search AS
    SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.filesize, dm_object.last_modified, dm_index.idxfti FROM (dm_index LEFT JOIN dm_object ON ((dm_index.object_id = dm_object.id)));


--
-- Name: dm_view_webdav; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_webdav AS
    SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object_parent.object_id, dm_object_parent.parent_id, (SELECT dm_file_history.id FROM dm_file_history WHERE (dm_file_history.object_id = dm_object.id) ORDER BY dm_file_history.version DESC LIMIT 1) AS file_id FROM dm_object, dm_object_parent WHERE ((dm_object.id = dm_object_parent.object_id) AND ((dm_object.object_type = 'collection'::text) OR (dm_object.object_type = 'file'::text)));


--
-- Name: dm_view_workflow; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW dm_view_workflow AS
    SELECT dm_workflow_route.id, dm_workflow_route.workflow_id, dm_workflow_route.account_id, dm_workflow_route.task_type, dm_workflow_route.date_due AS relative_due, dm_workflow_route.date_complete, dm_workflow_route.status, dm_workflow_route.sort_order, dm_workflow_route.comment, dm_workflow.object_id FROM (dm_workflow_route LEFT JOIN dm_workflow ON ((dm_workflow_route.workflow_id = dm_workflow.id)));


--
-- Name: dm_workflow_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_workflow_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_workflow_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_workflow_id_seq OWNED BY dm_workflow.id;


--
-- Name: dm_workflow_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_workflow_id_seq', 1, false);


--
-- Name: dm_workflow_route_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE dm_workflow_route_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: dm_workflow_route_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE dm_workflow_route_id_seq OWNED BY dm_workflow_route.id;


--
-- Name: dm_workflow_route_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('dm_workflow_route_id_seq', 1, false);


--
-- Name: keyword; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE keyword (
    id integer NOT NULL,
    name text NOT NULL,
    type text NOT NULL,
    required boolean DEFAULT false NOT NULL
);


--
-- Name: keyword_collection; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE keyword_collection (
    keyword_id integer NOT NULL,
    parent_id integer NOT NULL
);


--
-- Name: keyword_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE keyword_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: keyword_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE keyword_id_seq OWNED BY keyword.id;


--
-- Name: keyword_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('keyword_id_seq', 1, false);


--
-- Name: keyword_option; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE keyword_option (
    id integer NOT NULL,
    name text NOT NULL,
    keyword_id integer NOT NULL
);


--
-- Name: keyword_option_id_seq; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE keyword_option_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: keyword_option_id_seq; Type: SEQUENCE OWNED BY; Schema: docmgr; Owner: postgres
--

ALTER SEQUENCE keyword_option_id_seq OWNED BY keyword_option.id;


--
-- Name: keyword_option_id_seq; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('keyword_option_id_seq', 1, false);


--
-- Name: keyword_value; Type: TABLE; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE TABLE keyword_value (
    object_id integer NOT NULL,
    keyword_id integer NOT NULL,
    keyword_value text,
    data_type text
);



--
-- Name: level1; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE level1
    START WITH 1
    INCREMENT BY 1
    MAXVALUE 16
    NO MINVALUE
    CACHE 1
    CYCLE;


--
-- Name: level1; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('level1', 1, false);


--
-- Name: level2; Type: SEQUENCE; Schema: docmgr; Owner: postgres
--

CREATE SEQUENCE level2
    START WITH 1
    INCREMENT BY 1
    MAXVALUE 256
    NO MINVALUE
    CACHE 1
    CYCLE;


--
-- Name: level2; Type: SEQUENCE SET; Schema: docmgr; Owner: postgres
--

SELECT pg_catalog.setval('level2', 1, false);


--
-- Name: view_keyword_collection; Type: VIEW; Schema: docmgr; Owner: postgres
--

CREATE VIEW view_keyword_collection AS
    SELECT keyword.id, keyword.name, keyword.type, keyword.required, keyword_collection.parent_id FROM (keyword LEFT JOIN keyword_collection ON ((keyword.id = keyword_collection.keyword_id)));


SET search_path = logger, pg_catalog;

--
-- Name: logs; Type: TABLE; Schema: logger; Owner: postgres; Tablespace: 
--

CREATE TABLE logs (
    id integer NOT NULL,
    message text,
    level smallint,
    category text,
    sql text,
    log_timestamp timestamp without time zone,
    ip_address text,
    user_id integer,
    user_login text,
    post_data text,
    get_data text,
    child_location_id integer
);


--
-- Name: logs_id_seq; Type: SEQUENCE; Schema: logger; Owner: postgres
--

CREATE SEQUENCE logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: logs_id_seq; Type: SEQUENCE OWNED BY; Schema: logger; Owner: postgres
--

ALTER SEQUENCE logs_id_seq OWNED BY logs.id;


--
-- Name: logs_id_seq; Type: SEQUENCE SET; Schema: logger; Owner: postgres
--

SELECT pg_catalog.setval('logs_id_seq', 1, true);


SET search_path = modlet, pg_catalog;

--
-- Name: rssfeed; Type: TABLE; Schema: modlet; Owner: postgres; Tablespace: 
--

CREATE TABLE rssfeed (
    name text,
    account_id integer,
    url text,
    container text
);


--
-- Name: tasks; Type: TABLE; Schema: modlet; Owner: postgres; Tablespace: 
--

CREATE TABLE tasks (
    name text,
    account_id integer NOT NULL,
    daterange text,
    container text
);


SET search_path = public, pg_catalog;

--
-- Name: auth_accountperm; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE auth_accountperm (
    account_id integer NOT NULL,
    bitset integer DEFAULT 0 NOT NULL,
		bitmask bit(32),
    enable boolean DEFAULT true NOT NULL,
    locked_time timestamp without time zone,
    failed_logins integer DEFAULT 0 NOT NULL,
    failed_logins_locked boolean DEFAULT false NOT NULL,
    last_success_login timestamp without time zone DEFAULT '1970-01-01 00:00:00'::timestamp without time zone NOT NULL,
    setup boolean DEFAULT false,
    last_activity timestamp without time zone
);


--
-- Name: auth_accounts; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE auth_accounts (
    id integer NOT NULL,
    login text NOT NULL,
    password text NOT NULL,
		digest_hash text,
    first_name text,
    last_name text,
    email text,
    phone text
);


--
-- Name: auth_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE auth_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: auth_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE auth_accounts_id_seq OWNED BY auth_accounts.id;


--
-- Name: auth_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('auth_accounts_id_seq', 2, true);


--
-- Name: auth_grouplink; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE auth_grouplink (
    accountid integer NOT NULL,
    groupid integer NOT NULL
);


--
-- Name: auth_groupperm; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE auth_groupperm (
    group_id integer NOT NULL,
    bitset integer DEFAULT 0 NOT NULL,
		bitmask bit(32)
);


--
-- Name: auth_groups; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE auth_groups (
    id integer NOT NULL,
    name text NOT NULL
);


--
-- Name: auth_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE auth_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: auth_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE auth_groups_id_seq OWNED BY auth_groups.id;


--
-- Name: auth_groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('auth_groups_id_seq', 1, true);


--
-- Name: auth_settings; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE auth_settings (
    account_id integer NOT NULL,
    language text,
    home_directory integer,
    editor text
);


--
-- Name: dashboard; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE dashboard (
    account_id integer NOT NULL,
    display_column smallint NOT NULL,
    sort_order smallint NOT NULL,
    module text NOT NULL,
    modlet text NOT NULL,
    container_id text
);


--
-- Name: db_version; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE db_version (
    version integer NOT NULL
);


--
-- Name: dm_object_type; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE dm_object_type (
    id integer DEFAULT nextval(('"docmgr.dm_object_type_id_seq"'::text)::regclass) NOT NULL,
    name text NOT NULL
);


--
-- Name: group_dashboard; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE group_dashboard (
    group_id integer NOT NULL,
    display_column smallint NOT NULL,
    sort_order smallint NOT NULL,
    module text NOT NULL,
    modlet text NOT NULL,
    container_id text
);


--
-- Name: imagenum_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE imagenum_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: imagenum_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('imagenum_seq', 1, false);


--
-- Name: state; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE state (
    id integer NOT NULL,
    abbr text NOT NULL,
    name text
);


--
-- Name: state_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE state_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: state_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE state_id_seq OWNED BY state.id;


--
-- Name: state_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('state_id_seq', 1, false);


SET search_path = task, pg_catalog;

--
-- Name: docmgr_task; Type: TABLE; Schema: task; Owner: postgres; Tablespace: 
--

CREATE TABLE docmgr_task (
    task_id integer NOT NULL,
    object_id integer NOT NULL,
    route_id integer,
    workflow_id integer
);


--
-- Name: task; Type: TABLE; Schema: task; Owner: postgres; Tablespace: 
--

CREATE TABLE task (
    id integer NOT NULL,
    title text NOT NULL,
    notes text,
    priority smallint DEFAULT 2,
    date_due date,
    completed boolean DEFAULT false,
    due boolean DEFAULT false,
    date_completed timestamp without time zone,
    created_by integer,
    created_date timestamp without time zone,
    modified_by integer,
    modified_date timestamp without time zone,
    task_type text,
    idxfti tsvector
);


--
-- Name: task_account; Type: TABLE; Schema: task; Owner: postgres; Tablespace: 
--

CREATE TABLE task_account (
    task_id integer NOT NULL,
    account_id integer NOT NULL
);


--
-- Name: task_id_seq; Type: SEQUENCE; Schema: task; Owner: postgres
--

CREATE SEQUENCE task_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: task_id_seq; Type: SEQUENCE OWNED BY; Schema: task; Owner: postgres
--

ALTER SEQUENCE task_id_seq OWNED BY task.id;


--
-- Name: task_id_seq; Type: SEQUENCE SET; Schema: task; Owner: postgres
--

SELECT pg_catalog.setval('task_id_seq', 1, true);


--
-- Name: task_role; Type: TABLE; Schema: task; Owner: postgres; Tablespace: 
--

CREATE TABLE task_role (
    task_id integer NOT NULL,
    role_id integer NOT NULL,
    child_location_id integer
);


--
-- Name: view_docmgr_task; Type: VIEW; Schema: task; Owner: postgres
--

CREATE VIEW view_docmgr_task AS
    SELECT task.id, task.title, task.notes, task.priority, task.date_due, task.completed, task.due, task.date_completed, task.created_by, task.created_date, task.modified_by, task.modified_date, task.task_type, task.idxfti, docmgr_task.task_id, docmgr_task.object_id, docmgr_task.route_id, docmgr_task.workflow_id FROM (task LEFT JOIN docmgr_task ON ((task.id = docmgr_task.task_id)));


--
-- Name: view_task_complete; Type: VIEW; Schema: task; Owner: postgres
--

CREATE VIEW view_task_complete AS
    SELECT task.id, task.title, task.notes, task.priority, task.date_due, task.completed, task.due, task.date_completed, task.created_by, task.created_date, task.modified_by, task.modified_date, task.task_type, task_account.account_id, task_role.role_id, task_role.child_location_id FROM ((task LEFT JOIN task_account ON ((task.id = task_account.task_id))) LEFT JOIN task_role ON ((task.id = task_role.task_id)));


--
-- Name: view_tasks; Type: VIEW; Schema: task; Owner: postgres
--

CREATE VIEW view_tasks AS
    SELECT task.id, task.title, task.notes, task.priority, task.date_due, task.completed, task.due, task.date_completed, task.created_by, task.created_date, task.modified_by, task.modified_date, task.task_type, task_account.account_id, task_role.role_id, task_role.child_location_id FROM ((task LEFT JOIN task_account ON ((task.id = task_account.task_id))) LEFT JOIN task_role ON ((task.id = task_role.task_id)));


SET search_path = addressbook, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: addressbook; Owner: postgres
--

ALTER TABLE contact ALTER COLUMN id SET DEFAULT nextval('contact_id_seq'::regclass);


SET search_path = docmgr, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: docmgr; Owner: postgres
--

ALTER TABLE dm_bookmark ALTER COLUMN id SET DEFAULT nextval('dm_bookmark_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: docmgr; Owner: postgres
--

ALTER TABLE dm_tag ALTER COLUMN id SET DEFAULT nextval('dm_tag_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: docmgr; Owner: postgres
--

ALTER TABLE keyword ALTER COLUMN id SET DEFAULT nextval('keyword_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: docmgr; Owner: postgres
--

ALTER TABLE keyword_option ALTER COLUMN id SET DEFAULT nextval('keyword_option_id_seq'::regclass);


SET search_path = logger, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: logger; Owner: postgres
--

ALTER TABLE logs ALTER COLUMN id SET DEFAULT nextval('logs_id_seq'::regclass);


SET search_path = public, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE auth_accounts ALTER COLUMN id SET DEFAULT nextval('auth_accounts_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE auth_groups ALTER COLUMN id SET DEFAULT nextval('auth_groups_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE state ALTER COLUMN id SET DEFAULT nextval('state_id_seq'::regclass);


SET search_path = task, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: task; Owner: postgres
--

ALTER TABLE task ALTER COLUMN id SET DEFAULT nextval('task_id_seq'::regclass);

SET search_path = public, pg_catalog;

--
-- Data for Name: auth_accountperm; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO auth_accountperm (account_id, bitset, enable, locked_time, failed_logins, failed_logins_locked, last_success_login, setup, last_activity,bitmask) 
VALUES
('1','1','t',NOW(),'0','f','2009-10-10 20:48:16.134557','f','2009-10-10 20:48:16','00000000000000000000000000000001');


--
-- Data for Name: auth_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO auth_accounts (id, login, password, digest_hash, first_name, last_name, email, phone) 
VALUES
('1','admin','21232f297a57a5a743894a0e4a801fc3','87fd274b7b6c01e48d7c2f965da8ddf7','Administrator','Account','','');

--
-- Data for Name: auth_grouplink; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO auth_grouplink (accountid, groupid) 
VALUES
('1','1');


--
-- Data for Name: auth_groupperm; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO auth_groupperm (group_id, bitset,bitmask) 
VALUES
('1','1','00000000000000000000000000000001');

INSERT INTO auth_groupperm (group_id, bitset,bitmask) 
VALUES
('0','0','00000000000000000000110000011000');

--
-- Data for Name: auth_groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO auth_groups (id, name) 
VALUES
('1','Admin');

--
-- Data for Name: state; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO state (id,abbr,name) VALUES ('1','AK','Alaska');
INSERT INTO state (id,abbr,name) VALUES ('2','AL','Alabama');
INSERT INTO state (id,abbr,name) VALUES ('3','AS','American Samoa');
INSERT INTO state (id,abbr,name) VALUES ('4','AZ','Arizona');
INSERT INTO state (id,abbr,name) VALUES ('5','AR','Arkansas');
INSERT INTO state (id,abbr,name) VALUES ('6','CA','California');
INSERT INTO state (id,abbr,name) VALUES ('7','CO','Colorado');
INSERT INTO state (id,abbr,name) VALUES ('8','CT','Connecticut');
INSERT INTO state (id,abbr,name) VALUES ('9','DE','Delaware');
INSERT INTO state (id,abbr,name) VALUES ('10','DC','District of Columbia');
INSERT INTO state (id,abbr,name) VALUES ('12','FL','Florida');
INSERT INTO state (id,abbr,name) VALUES ('13','GA','Georgia');
INSERT INTO state (id,abbr,name) VALUES ('14','GU','Guam');
INSERT INTO state (id,abbr,name) VALUES ('15','HI','Hawaii');
INSERT INTO state (id,abbr,name) VALUES ('16','ID','Idaho');
INSERT INTO state (id,abbr,name) VALUES ('17','IL','Illinois');
INSERT INTO state (id,abbr,name) VALUES ('18','IN','Indiana');
INSERT INTO state (id,abbr,name) VALUES ('19','IA','Iowa');
INSERT INTO state (id,abbr,name) VALUES ('20','KS','Kansas');
INSERT INTO state (id,abbr,name) VALUES ('21','KY','Kentucky');
INSERT INTO state (id,abbr,name) VALUES ('22','LA','Louisiana');
INSERT INTO state (id,abbr,name) VALUES ('23','ME','Maine');
INSERT INTO state (id,abbr,name) VALUES ('24','MH','Marshall Islands');
INSERT INTO state (id,abbr,name) VALUES ('25','MD','Maryland');
INSERT INTO state (id,abbr,name) VALUES ('26','MA','Massachusetts');
INSERT INTO state (id,abbr,name) VALUES ('27','MI','Michigan');
INSERT INTO state (id,abbr,name) VALUES ('28','MN','Minnesota');
INSERT INTO state (id,abbr,name) VALUES ('29','MS','Mississippi');
INSERT INTO state (id,abbr,name) VALUES ('30','MO','Missouri');
INSERT INTO state (id,abbr,name) VALUES ('31','MT','Montana');
INSERT INTO state (id,abbr,name) VALUES ('32','NE','Nebraska');
INSERT INTO state (id,abbr,name) VALUES ('33','NV','Nevada');
INSERT INTO state (id,abbr,name) VALUES ('34','NH','New Hampshire');
INSERT INTO state (id,abbr,name) VALUES ('35','NJ','New Jersey');
INSERT INTO state (id,abbr,name) VALUES ('36','NM','New Mexico');
INSERT INTO state (id,abbr,name) VALUES ('37','NY','New York');
INSERT INTO state (id,abbr,name) VALUES ('38','NC','North Carolina');
INSERT INTO state (id,abbr,name) VALUES ('39','ND','North Dakota');
INSERT INTO state (id,abbr,name) VALUES ('40','MP','Northern Mariana Islands');
INSERT INTO state (id,abbr,name) VALUES ('41','OH','Ohio');
INSERT INTO state (id,abbr,name) VALUES ('42','OK','Oklahoma');
INSERT INTO state (id,abbr,name) VALUES ('43','OR','Oregon');
INSERT INTO state (id,abbr,name) VALUES ('44','PW','Palau');
INSERT INTO state (id,abbr,name) VALUES ('45','PA','Pennsylvania');
INSERT INTO state (id,abbr,name) VALUES ('46','PR','Puerto Rico');
INSERT INTO state (id,abbr,name) VALUES ('47','RI','Rhode Island');
INSERT INTO state (id,abbr,name) VALUES ('48','SC','South Carolina');
INSERT INTO state (id,abbr,name) VALUES ('49','SD','South Dakota');
INSERT INTO state (id,abbr,name) VALUES ('50','TN','Tennessee');
INSERT INTO state (id,abbr,name) VALUES ('51','TX','Texas');
INSERT INTO state (id,abbr,name) VALUES ('52','UT','Utah');
INSERT INTO state (id,abbr,name) VALUES ('53','VT','Vermont');
INSERT INTO state (id,abbr,name) VALUES ('54','VI','Virgin Islands');
INSERT INTO state (id,abbr,name) VALUES ('55','VA','Virginia');
INSERT INTO state (id,abbr,name) VALUES ('56','WA','Washington');
INSERT INTO state (id,abbr,name) VALUES ('57','WV','West Virginia');
INSERT INTO state (id,abbr,name) VALUES ('58','WI','Wisconsin');
INSERT INTO state (id,abbr,name) VALUES ('59','WY','Wyoming');
INSERT INTO state (id,abbr,name) VALUES ('60','AE','Armed Forces Africa');
INSERT INTO state (id,abbr,name) VALUES ('62','AE','Armed Forces Canada');
INSERT INTO state (id,abbr,name) VALUES ('63','AE','Armed Forces Europe');
INSERT INTO state (id,abbr,name) VALUES ('64','AE','Armed Forces Middle East');
INSERT INTO state (id,abbr,name) VALUES ('65','AP','Armed Forces Pacific');
INSERT INTO state (id,abbr,name) VALUES ('11','FM','Federated state of Micronesia');
INSERT INTO state (id,abbr,name) VALUES ('61','AA','Armed Forces Americas');

SET search_path = docmgr, pg_catalog;

--
-- Name: dm_discussion_pkey; Type: CONSTRAINT; Schema: docmgr; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY dm_discussion
    ADD CONSTRAINT dm_discussion_pkey PRIMARY KEY (id);


--
-- Name: dm_file_history_pkey; Type: CONSTRAINT; Schema: docmgr; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY dm_file_history
    ADD CONSTRAINT dm_file_history_pkey PRIMARY KEY (id);


--
-- Name: dm_index_pkey; Type: CONSTRAINT; Schema: docmgr; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY dm_index
    ADD CONSTRAINT dm_index_pkey PRIMARY KEY (object_id);


SET search_path = addressbook, pg_catalog;

--
-- Name: contact_account_account_id_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_account_account_id_idx ON contact_account USING btree (account_id);


--
-- Name: contact_account_contact_id_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_account_contact_id_idx ON contact_account USING btree (contact_id);


--
-- Name: contact_address_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_address_idx ON contact USING btree (lower(address));


--
-- Name: contact_city_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_city_idx ON contact USING btree (lower(city));


--
-- Name: contact_first_name_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_first_name_idx ON contact USING btree (lower(first_name));


--
-- Name: contact_id_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX contact_id_idx ON contact USING btree (id);


--
-- Name: contact_last_name_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_last_name_idx ON contact USING btree (lower(last_name));


--
-- Name: contact_middle_name_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_middle_name_idx ON contact USING btree (lower(middle_name));


--
-- Name: contact_state_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_state_idx ON contact USING btree (state);


--
-- Name: contact_zip_idx; Type: INDEX; Schema: addressbook; Owner: postgres; Tablespace: 
--

CREATE INDEX contact_zip_idx ON contact USING btree (zip);


SET search_path = docmgr, pg_catalog;

--
-- Name: dm_dirlevel_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_dirlevel_object_id_idx ON dm_dirlevel USING btree (object_id);


--
-- Name: dm_discussion_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX dm_discussion_id_key ON dm_discussion USING btree (id);


--
-- Name: dm_discussion_object_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_discussion_object_id_key ON dm_discussion USING btree (object_id);


--
-- Name: dm_file_history_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX dm_file_history_id_key ON dm_file_history USING btree (id);


--
-- Name: dm_file_history_object_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_file_history_object_id_key ON dm_file_history USING btree (object_id);


--
-- Name: dm_keyword_field_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_keyword_field_idx ON dm_keyword USING btree (field1, field2, field3, field4, field5, field6);


--
-- Name: dm_object_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX dm_object_id_key ON dm_object USING btree (id);


--
-- Name: dm_object_log_object_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_log_object_id_key ON dm_object_log USING btree (object_id);


--
-- Name: dm_object_object_type_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_object_type_idx ON dm_object USING btree (object_type);


--
-- Name: dm_object_parent_search_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_parent_search_key ON dm_object_parent USING btree (object_id, parent_id);


--
-- Name: dm_object_perm_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_perm_id_key ON dm_object_perm USING btree (object_id);


--
-- Name: dm_object_related_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_related_object_id_idx ON dm_object_related USING btree (object_id);


--
-- Name: dm_object_related_related_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_related_related_id_idx ON dm_object_related USING btree (related_id);


--
-- Name: dm_object_search_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_object_search_key ON dm_object USING btree (name, summary);


--
-- Name: dm_search_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_search_idx ON dm_search USING btree (object_id);

--
-- Name: dm_subscribe_info_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_subscribe_info_key ON dm_subscribe USING btree (object_id, account_id);


--
-- Name: dm_tag_account_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_tag_account_id_idx ON dm_tag USING btree (account_id);


--
-- Name: dm_tag_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_tag_id_idx ON dm_tag USING btree (id);


--
-- Name: dm_tag_link_tag_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_tag_link_tag_id_idx ON dm_tag_link USING btree (tag_id);


--
-- Name: dm_tag_object_id_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_tag_object_id_idx ON dm_tag_link USING btree (object_id);


--
-- Name: dm_task_account_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_task_account_id_key ON dm_task USING btree (account_id);


--
-- Name: dm_url_object_id; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_url_object_id ON dm_url USING btree (object_id);


--
-- Name: dm_workflow_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX dm_workflow_id_key ON dm_workflow USING btree (id);


--
-- Name: dm_workflow_object_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX dm_workflow_object_id_key ON dm_workflow USING btree (object_id);


--
-- Name: dm_workflow_route_id_key; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX dm_workflow_route_id_key ON dm_workflow_route USING btree (id);


--
-- Name: idxfti_idx; Type: INDEX; Schema: docmgr; Owner: postgres; Tablespace: 
--

CREATE INDEX idxfti_idx ON dm_index USING gin (idxfti);


SET search_path = public, pg_catalog;

--
-- Name: auth_accountperm_pkey; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX auth_accountperm_pkey ON auth_accountperm USING btree (account_id);


--
-- Name: auth_accounts_pkey; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX auth_accounts_pkey ON auth_accounts USING btree (id);


--
-- Name: dashboard_account_id_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX dashboard_account_id_idx ON dashboard USING btree (account_id);


--
-- Name: dashboard_module_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX dashboard_module_idx ON dashboard USING btree (module);


--
-- Name: dashboard_sort_order_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX dashboard_sort_order_idx ON dashboard USING btree (sort_order);


--
-- Name: group_dashboard_account_id_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX group_dashboard_account_id_idx ON group_dashboard USING btree (group_id);


--
-- Name: group_dashboard_module_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX group_dashboard_module_idx ON group_dashboard USING btree (module);


--
-- Name: group_dashboard_sort_order_idx; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX group_dashboard_sort_order_idx ON group_dashboard USING btree (sort_order);


SET search_path = task, pg_catalog;

--
-- Name: task_account_account_id_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_account_account_id_idx ON task_account USING btree (account_id);


--
-- Name: task_account_task_id_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_account_task_id_idx ON task_account USING btree (task_id);


--
-- Name: task_completed_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_completed_idx ON task USING btree (completed);


--
-- Name: task_date_due_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_date_due_idx ON task USING btree (date_due);


--
-- Name: task_id_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_id_idx ON task USING btree (id);


--
-- Name: task_idxfti_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_idxfti_idx ON task USING gist (idxfti);


--
-- Name: task_notes_lower_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_notes_lower_idx ON task USING btree (lower(notes));


--
-- Name: task_role_child_location_id_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_role_child_location_id_idx ON task_role USING btree (child_location_id);


--
-- Name: task_role_role_id_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_role_role_id_idx ON task_role USING btree (role_id);


--
-- Name: task_role_task_id_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_role_task_id_idx ON task_role USING btree (task_id);


--
-- Name: task_title_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_title_idx ON task USING btree (title);


--
-- Name: task_title_lower_idx; Type: INDEX; Schema: task; Owner: postgres; Tablespace: 
--

CREATE INDEX task_title_lower_idx ON task USING btree (lower(title));


SET search_path = docmgr, pg_catalog;

--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_alert
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_discussion
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_file_history
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_index
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_keyword
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_object_parent
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_object_perm
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_subscribe
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_url
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_workflow
    ADD CONSTRAINT "$1" FOREIGN KEY (object_id) REFERENCES dm_object(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_workflow_route
    ADD CONSTRAINT "$1" FOREIGN KEY (workflow_id) REFERENCES dm_workflow(id);


--
-- Name: $1; Type: FK CONSTRAINT; Schema: docmgr; Owner: postgres
--

ALTER TABLE ONLY dm_task
    ADD CONSTRAINT "$1" FOREIGN KEY (task_id) REFERENCES dm_workflow_route(id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

CREATE TABLE docmgr.object_link (
    object_id integer NOT NULL,
    link text NOT NULL,
    account_id integer NOT NULL,
    created timestamp without time zone,
    expires timestamp without time zone
);


CREATE UNIQUE INDEX object_link_idx ON docmgr.object_link USING btree (link);

CREATE INDEX contact_email_idx ON addressbook.contact USING btree (lower(email));

CREATE TABLE docmgr.object_view (
object_id integer not null,
account_id integer not null,
view text default 'list'
);

CREATE INDEX object_view_object_object_id_idx ON docmgr.object_view USING btree(object_id);

CREATE INDEX object_view_object_account_id_idx ON docmgr.object_view USING btree(account_id);



CREATE OR REPLACE FUNCTION docmgr.get_all_paths( objid integer ) RETURNS SETOF text AS $$
DECLARE
	path TEXT;
	res RECORD;
BEGIN
	FOR res IN SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id=objid ORDER BY parent_id LOOP
	IF res.parent_id<>'0' THEN
		SELECT INTO path objid || ',' || docmgr.getobjpath( res.parent_id,'');
	ELSE
		SELECT INTO path objid || ',0';
	END IF;
  RETURN NEXT path;
	END LOOP;
END;
$$
LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION docmgr.get_all_pathnames( objid integer ) RETURNS SETOF text AS $$
DECLARE
	path TEXT;
	res RECORD;
	objname TEXT;
BEGIN

	SELECT INTO objname name FROM docmgr.dm_object WHERE id=objid;
	FOR res IN SELECT parent_id FROM docmgr.dm_object_parent WHERE object_id=objid ORDER BY parent_id LOOP
		IF res.parent_id<>'0' THEN
			SELECT INTO path docmgr.getobjpathname( res.parent_id,'') || '/' || objname;
		ELSE
			SELECT INTO path '/' || objname;
		END IF;
    RETURN NEXT path;
	END LOOP;
END;
$$
LANGUAGE 'plpgsql';

SET search_path = public, pg_catalog;

INSERT INTO db_version VALUES ('2010101001');

INSERT INTO group_dashboard VALUES ('0','1','1','home','bkmodlet','bkmodlet1');
INSERT INTO group_dashboard VALUES ('0','1','2','home','taskmodlet','taskmodlet3');
INSERT INTO group_dashboard VALUES ('0','2','1','home','currentsubscribe','currentsubscribe2');
INSERT INTO group_dashboard VALUES ('0','2','2','home','subscribealert','subscribealert4');



CREATE VIEW docmgr.dm_view_parent AS
SELECT docmgr.dm_object_parent.*,dm_object.name,
dm_object.object_type FROM docmgr.dm_object_parent
LEFT JOIN docmgr.dm_object ON dm_object_parent.object_id=dm_object.id;


CREATE TABLE docmgr.dm_share (
              object_id integer NOT NULL,
              account_id integer not null,
              share_account_id integer not null,
              bitmask text);

-- make the Users directory and give everyone permissions to make home folders in it
INSERT INTO docmgr.dm_object 
(name,create_date,version,last_modified,modified_by,object_type,object_owner,protected) 
VALUES 
('Users',NOW(),'1',NOW(),'1','collection','1','t');
INSERT INTO docmgr.dm_object_perm (object_id,group_id,bitmask) VALUES ('1','0','00000100');
INSERT INTO docmgr.dm_object_parent (object_id,parent_id) VALUES ('1','0');

-- Queue for indexing
INSERT INTO docmgr.dm_index_queue (object_id,account_id,create_date) VALUES ('1','1',NOW());

CREATE VIEW docmgr.dm_view_colsearch AS
 SELECT dm_object.id, dm_object.name, dm_object.summary, dm_object.object_type, dm_object.create_date, dm_object.object_owner, dm_object.status, dm_object.status_date, dm_object.status_owner, dm_object.version, dm_object.reindex, dm_object.hidden, dm_object_parent.object_id, dm_object_parent.parent_id, dm_object_perm.account_id, dm_object_perm.group_id, dm_object_perm.bitset, dm_object_perm.bitmask
   FROM docmgr.dm_object
   LEFT JOIN docmgr.dm_object_parent ON dm_object.id = dm_object_parent.object_id
   LEFT JOIN docmgr.dm_object_perm ON dm_object.id = dm_object_perm.object_id
  WHERE dm_object.object_type = 'collection' OR dm_object.object_type='search';
