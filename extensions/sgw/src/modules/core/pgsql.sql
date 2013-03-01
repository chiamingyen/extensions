	/**************************************************************************\
	* Simple Groupware 0.743                                                   *
	* http://www.simple-groupware.de                                           *
	* Copyright (C) 2002-2012 by Thomas Bley                                   *
	* ------------------------------------------------------------------------ *
	*  This program is free software; you can redistribute it and/or           *
	*  modify it under the terms of the GNU General Public License Version 2   *
	*  as published by the Free Software Foundation; only version 2            *
	*  of the License, no later version.                                       *
	*                                                                          *
	*  This program is distributed in the hope that it will be useful,         *
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of          *
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
	*  GNU General Public License for more details.                            *
	*                                                                          *
	*  You should have received a copy of the GNU General Public License       *
	*  Version 2 along with this program; if not, write to the Free Software   *
	*  Foundation, Inc., 59 Temple Place - Suite 330, Boston,                  *
	*  MA  02111-1307, USA.                                                    *
	\**************************************************************************/


CREATE or REPLACE FUNCTION "instr" (varchar,varchar) RETURNS integer AS '
    SELECT position($2 in $1);
' LANGUAGE 'sql';

CREATE or REPLACE FUNCTION "concat" (text,text) RETURNS text AS '
    SELECT $1 || $2;
' LANGUAGE 'sql';

CREATE or REPLACE FUNCTION "concat" (text,text,text) RETURNS text AS '
    SELECT $1 || $2 || $3;
' LANGUAGE 'sql';


CREATE or REPLACE FUNCTION "from_unixtime" (numeric) RETURNS timestamp AS '
    SELECT TIMESTAMP ''epoch'' + $1 * INTERVAL ''1 second'';
' LANGUAGE 'sql';


CREATE or REPLACE FUNCTION "from_unixtime" (numeric,varchar) RETURNS varchar AS '
    SELECT to_char(TIMESTAMP ''epoch'' + $1 * INTERVAL ''1 second'', $2);
' LANGUAGE 'sql';

CREATE or REPLACE FUNCTION "unix_timestamp" () RETURNS numeric AS '
  SELECT floor(extract(epoch FROM now()))::numeric;
' LANGUAGE 'sql';

CREATE or REPLACE FUNCTION "unix_timestamp" (varchar) RETURNS numeric AS '
  SELECT floor(extract(epoch FROM $1::timestamp))::numeric;
' LANGUAGE 'sql';

CREATE or REPLACE FUNCTION "unix_timestamp" (timestamp with time zone) RETURNS numeric AS '
  SELECT floor(extract(epoch FROM $1))::numeric;
' LANGUAGE 'sql';


create or replace view show_full_columns as 
(select
column_name as "Field",
data_type||' ('||coalesce(numeric_precision::varchar,'')||coalesce(character_maximum_length::varchar,'')||')' as "Type",
is_nullable as "Null",
'' as "Key",
column_default as "Default",
'' as "Extra",
'' as "Privileges",
table_name
from information_schema.columns
order by column_name);


create or replace view show_processlist as
(select
procpid as "Id",
usename as "User",
'' as "Host",
datname as db,
current_query as "Command",
backend_start as "Time",
'' as "State",
'' as "Info"
from pg_stat_activity);


create or replace view show_table_status as
(select
relname as "Name",
reltuples as "Rows",
'' as "Create_time",
'' as "Update_time",
'' as "Check_time",
(pg_relation_size(table_name)/((select reltuples from pg_class where relname = table_name)+1))::int as "Avg_row_length",
pg_relation_size(table_name) as "Data_length",
pg_total_relation_size(table_name)-pg_relation_size(table_name) as "Index_length",
0 as "Data_free"
from information_schema.tables, pg_class
where
table_name = relname and
table_name like 'simple_%' and
relname like 'simple_%'
order by relname);


create or replace view show_index as
(select
tablename as "Table",
(instr(indexdef,'UNIQUE')<1)::bool::int as "Non_unique",
indexname as "Key_name",
'' as "Seq_in_index",
substring(indexdef,'\\(([a-z,_ ]{2,})\\)') as "Column_name",
'' as "Collation",
0 as "Cardinality",
'' as "Sub_part",
'' as "Packed",
'' as "Null",
'' as "Index_type",
'' as "Comment",
tablename
from pg_indexes
order by indexname);


/* PostgreSQL cannot alter sgs-tables if views are present*/

drop view if exists fnbl_simple_contacts_imp;

drop view if exists fnbl_simple_contacts_exp;

drop view if exists fnbl_simple_tasks_imp;

drop view if exists fnbl_simple_tasks_exp;

drop view if exists fnbl_simple_calendar_imp;

drop view if exists fnbl_simple_calendar_exp;

drop view if exists fnbl_simple_notes_exp;

drop view if exists fnbl_simple_notes_imp;

CREATE or REPLACE FUNCTION rank(tsvector, tsquery)
	RETURNS float4
	AS 'ts_rank_tt'
	LANGUAGE INTERNAL
        RETURNS NULL ON NULL INPUT IMMUTABLE;
		
CREATE or REPLACE FUNCTION tsearch2()
	RETURNS trigger
	AS '$libdir/tsearch2', 'tsa_tsearch2'
	LANGUAGE C;