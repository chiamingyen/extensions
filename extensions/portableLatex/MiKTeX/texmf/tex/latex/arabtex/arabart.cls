%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% arabart.cls
% ArabTeX extension
% Arabic version of article.cls
% for use with ArabTeX Version 3.04
%
% Copyright (C) 1995 by Klaus Lagally
% 21.05.96
%
% reusing arabrep.cls of 21 May 1996
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

\NeedsTeXFormat {LaTeX2e}[1995/06/01]
\ProvidesClass {arabart}[1996/05/21 v0.01 arabic article format]

\DeclareOption* {\PassOptionsToClass {\CurrentOption}{arabrep}}
\ProcessOptions
\LoadClass [article]{arabrep}[1996/05/21] % load the Arabic report class 

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% end of file arabart.cls
\endinput
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

