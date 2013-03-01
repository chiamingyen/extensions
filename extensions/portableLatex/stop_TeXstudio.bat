@echo off
rem ## Get drive letter
rem ## SET /P Disk=<data\drive.txt

rem ## del data\drive.txt
set Disk=s

rem ## Kill drive
subst %Disk%: /D
