ALTER TABLE docmgr.dm_workflow ADD COLUMN expire_notify BOOLEAN;
ALTER TABLE docmgr.dm_savesearch RENAME COLUMN xmlparm TO params;


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
