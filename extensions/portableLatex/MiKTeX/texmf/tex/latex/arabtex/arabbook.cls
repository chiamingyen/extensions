%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% arabbook.cls
% ArabTeX extension
% Arabic version of book.cls
% for use with ArabTeX Version 3.04
%
% Copyright (C) 1995 by Klaus Lagally
% 21.05.96
%
% reusing arabrep.cls of 18 May 1996
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

\NeedsTeXFormat {LaTeX2e}[1995/06/01]
\ProvidesClass {arabbook}[1996/05/21 v0.01 arabic book format]

\DeclareOption* {\PassOptionsToClass {\CurrentOption}{arabrep}}
\ProcessOptions
\LoadClass [book]{arabrep}[1996/05/18] % load the Arabic report class 

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% end of file arabbook.cls
\endinput
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
