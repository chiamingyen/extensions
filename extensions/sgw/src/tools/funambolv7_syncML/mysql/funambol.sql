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


create or replace view fnbl_simple_contacts_imp as
  select id as syncid,
		 floor(last_update/1000) as lastmodified,
		 userid,
		 status,
		 replace(body,'\r','\n') as description,
		 categories as category,
		 replace( replace( replace( replace( trim(
		   coalesce( coalesce(display_name, concat(last_name,' ',first_name)), company )
		   ) ,' ','_') ,',','_') ,'\'','_') ,'__','_') as contactid,
		 company,
		 coalesce(coalesce(last_name,company),display_name) as lastname,
		 first_name as firstname,
		 (select country from fnbl_pim_address where contact=c.id and type=1) as country,
		 (select state from fnbl_pim_address where contact=c.id and type=1) as state,
		 (select street from fnbl_pim_address where contact=c.id and type=1) as street,
		 (select city from fnbl_pim_address where contact=c.id and type=1) as city,
		 (select postal_code from fnbl_pim_address where contact=c.id and type=1) as zipcode,
		 nickname,
		 title,
		 department,
		 profession as degree,
		 coalesce(UNIX_TIMESTAMP(birthday), '0') as birthday,
		 job_title as position,
		 assistant as secretary,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=1) as phoneprivate,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=2) as faxprivate,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=3) as mobile,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=4) as email,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=6) as homepage,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=8) as skype,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=10) as phone,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=11) as fax,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=14) as pager,
		 (select value from fnbl_pim_contact_item where contact=c.id and type=16) as emailprivate
  from fnbl_pim_contact c;

create or replace view fnbl_simple_contacts_exp as
  select lastname as last_name,
		 firstname as first_name,
		 contactid as display_name,
		 description as body,
		 category as categories,
		 nickname,
		 title,
		 department,
		 degree as profession,
		 company,
		 secretary as assistant,
		 position as job_title,
		 0 as photo_type,		 
		 1 as importance,
		 (case when birthday != 0 then DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(0), interval birthday second),'%Y%m%d') else null end) as birthday,
		 (lastmodified*1000) as last_update,
		 (case when syncid is null then 'N' else 'U' end) as status,
		 (case when syncid is null then (id*(-1)) else syncid end) as syncid,
		 'DEFAULT_FOLDER' as folder,
		 id
  from simple_contacts;

create or replace view fnbl_simple_tasks_imp as
  select UNIX_TIMESTAMP(dstart) as begin,
		 UNIX_TIMESTAMP(dend)-43200 as ending,
		 replace(body,'\r','\n') as description,
		 categories as category,
		 subject,
		 location,
		 (case when completed is not null then 1 else 0 end) as closed,
		 FORMAT(percent_complete/100,2) as progress,
		 (case when importance = 1 then '5'
		 	   when importance = 5 then '3'
			   when importance = 9 then '1'
			   else ''
		 end) as priority,
		 floor(last_update/1000) as lastmodified,
		 userid,
		 status,
		 id as syncid
		
  from fnbl_pim_calendar
  where type=2;
 

create or replace view fnbl_simple_tasks_exp as
  select
		 FROM_UNIXTIME(begin+43200) as dstart,
		 FROM_UNIXTIME(ending+43200) as dend,
		 '2' as type,
		 '1' as all_day,
		 category as categories,
		 progress*100 as percent_complete,
		 description as body,
		 subject,
		 location,
		 (case when closed = 1 then FROM_UNIXTIME(lastmodified) else null end) as completed,
 		 (case when priority = '5' then 1
		 	   when priority = '4' then 3
		 	   when priority = '3' then 5
			   when priority = '2' then 7
			   when priority = '1' then 9
			   else null
		 end) as importance,
		 0 as duration,
		 0 as reminder,
		 0 as sensitivity,
		 -1 as rec_type,
		 
		 (lastmodified*1000) as last_update,
		 (case when syncid is null then 'N' else 'U' end) as status,
		 (case when syncid is null then (id*(-1)) else syncid end) as syncid,
		 'DEFAULT_FOLDER' as folder,
		 id
  from simple_tasks;


create or replace view fnbl_simple_calendar_imp as
  select id as syncid,
		 floor(last_update/1000) as lastmodified,
		 userid,
		 status,
		 replace(body,'\r','\n') as description,
		 categories as category,
		 subject,
		 location,
		 UNIX_TIMESTAMP(dstart) as begin,
		 (case when all_day = '1' then UNIX_TIMESTAMP(dend)-86400 else UNIX_TIMESTAMP(dend) end) as ending,
		 all_day as allday,
 		 (case when importance = 1 then '5'
		 	   when importance = 5 then '3'
			   when importance = 9 then '1'
			   else ''
		 end) as priority,
		 coalesce(UNIX_TIMESTAMP(dstart) - UNIX_TIMESTAMP(reminder_time), 0) as reminder,
		 (case when rec_type = 0 then 'days'
			   when rec_type = 1 then 'weeks'
   			   when rec_type = 2 then 'months'
			   when rec_type = 5 then 'years'
		  	   else ''
		 end) as recurrence,
		 (case when rec_interval = 0 then 1 else rec_interval end) as repeatinterval,
		 (case when rec_occurrences < 0 then 0 else rec_occurrences end) as repeatcount,
		 coalesce(UNIX_TIMESTAMP(concat(
			substr(rec_end_date_pattern,1,4),'-',
			substr(rec_end_date_pattern,5,2),'-',
			substr(rec_end_date_pattern,7,2),' ',
			substr(rec_end_date_pattern,10,2),':',
			substr(rec_end_date_pattern,12,2),':',
			substr(rec_end_date_pattern,14,2)
		 )),0) as repeatuntil,
		 coalesce((select concat('|',GROUP_CONCAT(UNIX_TIMESTAMP(occurrence_date) SEPARATOR '|'),'|') from fnbl_pim_calendar_exception where calendar=fnbl_pim_calendar.id), '0') as repeatexcludes

  from fnbl_pim_calendar
  where type=1;

  
create or replace view fnbl_simple_calendar_exp as
  select
		 (case when allday = '1' then FROM_UNIXTIME(begin+43200) else FROM_UNIXTIME(begin) end) as dstart,
		 FROM_UNIXTIME(ending) as dend,
		 allday as all_day,
		 '1' as type,
		 description as body,
		 subject,
		 location,
		 0 as duration,
		 category as categories,
 		 (case when priority = '5' then 1
		 	   when priority = '4' then 3
		 	   when priority = '3' then 5
			   when priority = '2' then 7
			   when priority = '1' then 9
			   else null
		 end) as importance,
		 (case when recurrence = 'days' then 0
			   when recurrence = 'weeks' then 1
   			   when recurrence = 'months' then 2
			   when recurrence = 'years' then 5
		 	   else -1
		 end) as rec_type,
		 (case when recurrence != '' then FROM_UNIXTIME(begin, '%Y%m%dT%H%i%s') else null end) as rec_start_date_pattern,
		 (case when repeatuntil != 0 then FROM_UNIXTIME(repeatuntil, '%Y%m%dT%H%i%sZ') else '' end) as rec_end_date_pattern,
		 (case when repeatuntil != 0 then 0 else 1 end) as rec_no_end_date,
		 repeatinterval as rec_interval,
		 (case when repeatcount = 0 then -1 else repeatcount end) as rec_occurrences,
		 (case when reminder != 0 then FROM_UNIXTIME(begin - reminder) else null end) as reminder_time,
		 repeatexcludes as rec_exceptions,
		 0 as rec_day_of_week_mask,
		 0 as rec_day_of_month,
		 0 as rec_month_of_year,
/*
		 (case when recurrence = 'weeks' then FROM_UNIXTIME(begin,'%w') else 0 end) as rec_day_of_week_mask,
		 (case when recurrence = 'months' or recurrence = 'years' then FROM_UNIXTIME(begin,'%e') else 0 end) as rec_day_of_month,
		 (case when recurrence = 'years' then FROM_UNIXTIME(begin,'%c') else 0 end) as rec_month_of_year,
*/
		 0 as reminder_options,
		 0 as reminder_repeat_count,
		 0 as sensitivity,
		 'null' as mileage,
		 (lastmodified*1000) as last_update,
		 (case when syncid is null then 'N' else 'U' end) as status,
		 (case when syncid is null then (id*(-1)-1) else syncid end) as syncid,
		 'DEFAULT_FOLDER' as folder,
		 id
  from simple_calendar;

  
/* map table simple_notes to SIF-N format */
create or replace view fnbl_simple_notes_exp as
  select
		 title as subject,
  		 (case when content != '' then content else title end) as textdescription,
		 category as categories,
		 (case when bgcolor = '#DDDDFF' then '0'
			   when bgcolor = '#CCFFCC' then '1'
			   when bgcolor = '#FFDDFF' then '2'
			   when bgcolor = '#FFFFDD' then '3'
			   when bgcolor = '#FFFFFF' then '4'
			   else ''
		 end) as color,
		 '166' as height,
		 '200' as width,
		 '260' as leftmargin,
		 '260' as top,
		 (lastmodified*1000) as last_update,
		 (case when syncid is null then 'N' else 'U' end) as status,
		 (case when syncid is null then (id*(-1)) else syncid end) as syncid,
		 'DEFAULT_FOLDER' as folder,
		 id
  from simple_notes;

  
create or replace view fnbl_simple_notes_imp as
  select id as syncid,
		 floor(last_update/1000) as lastmodified,
		 userid,
		 status,
		 (case when subject != '' then subject else replace(textdescription,'\r','\n') end) as title,
		 categories as category,
		 replace(textdescription,'\r','\n') as content,
		 (case when color = 0 then '#DDDDFF'
			   when color = 1 then '#CCFFCC'
			   when color = 2 then '#FFDDFF'
			   when color = 3 then '#FFFFDD'
			   when color = 4 then '#FFFFFF'
			   else ''
		 end) as bgcolor
  from fnbl_pim_note;