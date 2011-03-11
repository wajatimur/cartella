CREATE TABLE docmgr.object_link (
object_id integer NOT NULL,
link text NOT NULL,
account_id integer NOT NULL,
created timestamp without time zone,
expires timestamp without time zone
);

CREATE UNIQUE INDEX object_link_idx ON docmgr.object_link USING btree (link);

CREATE INDEX contact_email_idx ON addressbook.contact USING btree (lower(email));

