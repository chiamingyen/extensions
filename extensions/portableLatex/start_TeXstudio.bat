@echo off

rem ## Find first free drive letter
rem ## for %%a in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do CD %%a: 1>> nul 2>&1 & if errorlevel 1 set freedrive=%%a

rem ## set Disk=%freedrive% (y drive for eclipse IDE)
set Disk=s

rem ## Having decided which drive letter to use create the disk
subst %Disk%: "MiKTeX"

rem ## Save drive letter to file. Used by stop bat 
rem ## (set /p dummy=%Disk%) > data\drive.txt <nul

rem ## Set variable paths


start %Disk%:\TeXstudio2.3\texstudio.exe

