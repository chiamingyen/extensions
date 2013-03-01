%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% arabrep.cls
% ArabTeX extension
% Arabic version of report.cls
% for use with ArabTeX Version 3.08 - 3.10
%
% Copyright (C) 1997 - 2003 by Klaus Lagally
% 16.05.1997
% 21.03.1999
% 26.04.2003
%
% reusing report.cls (and book.cls) of 19 Jun 1995
% reusing report.cls (and book.cls) of 26 May 1996
% patches for pagenumbering 25 March 1999
% 15.07.1999
% 21.04.2000	% new font handling
% 14.09.2001	% RTL footnotes, chapter names etc.
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

\NeedsTeXFormat {LaTeX2e}[1996/06/01]
\ProvidesClass {arabrep}[2003/04/26 v3.11 arabic report format]

\newif\if@mainmatter \@mainmattertrue
\newif\ifa@book \a@bookfalse
\newif\ifa@article \a@articlefalse
\DeclareOption {book}{\a@booktrue \@mainmattertrue } 
\DeclareOption {article}{\a@articletrue \@mainmattertrue } 
\DeclareOption* {\PassOptionsToClass {\CurrentOption}{report}}
\ProcessOptions
\LoadClass {report}[1996/05/26] % load the LaTeX standard class 

\ifa@book \@openrighttrue \fi
%\input{bk1\@ptsize.clo} % collision on \small !!

\ifa@article \@titlepagefalse \fi

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%\RequirePackage {arabtex}[1997/05/08] % load the ArabTeX processor
\RequirePackage {arabtex}[2003/04/26] % load the ArabTeX processor

\UsePackage {altxext} % load local patches for LaTeX

%    ****************************************
%    *         PAGE STYLES                  *
%    ****************************************

\if@twoside         % If two-sided printing.
 \def \ps@headings {% for arabic mode
	\let \@mkboth \markboth \def\@oddfoot{}\def\@evenfoot{}%
	\def \@evenhead {\edef \thep@ge {\nxp\RL{\thepage }}% left heading
		\a@normalfont \leftmark \hfil \thep@ge }%
	\def \@oddhead {\edef \thep@ge {\nxp\RL{\thepage }}% right heading
		\a@normalfont \thep@ge \hfil \rightmark }%
	\def \chaptermark ##1{\markboth {##1%
		\ifnum \c@secnumdepth >\m@ne
		\if@mainmatter
		\hskip 1em\relax \RL{\@chapapp \space \thechapter. }%
		\fi\fi }{}}%
	\def \sectionmark ##1{\markleft {##1\ifnum \c@secnumdepth >\@ne
		\hskip 1em\relax \RL{\thesection }\fi }}%
	\ifa@article
	\def \subsectionmark ##1{\markleft {##1\ifnum \c@secnumdepth >\@ne
		\hskip 1em\relax \RL{\thesubsection }\fi }}%
	\fi }
\else               % If one-sided printing.
 \def \ps@headings {% for arabic mode
	\let \@mkboth \markboth \def \@oddfoot{}\def \@evenfoot{}%
	\def \@oddhead {\edef \thep@ge {\nxp\RL{\thepage }}% right heading
		\a@normalfont \thep@ge \hfil \rightmark }%
	\def \chaptermark ##1{\markright {##1%
		\ifnum \c@secnumdepth >\m@ne
		\if@mainmatter
		\hskip 1em\relax \RL{\@chapapp\space \thechapter. }%
		\fi\fi }}}
\fi

\def \ps@plain {% for arabic mode
	\let \@mkboth\@gobbletwo \let\@oddhead\@empty \let\@evenhead\@empty
	\def \@oddfoot {\edef \thep@ge {\nxp\RL{\thepage }}% right heading
		\a@normalfont \hfil \thep@ge \hfil }%
	\let \@evenfoot\@oddfoot }
 
\def \ps@myheadings {% for arabic mode
	\let\@oddfoot \@empty \let\@evenfoot \@empty
	\def \@oddhead {\edef \thep@ge {\nxp\RL{\thepage }}% right heading
		\a@normalfont \thep@ge \hfil \rightmark }%
	\def \@evenhead {\edef \thep@ge {\nxp\RL{\thepage }}% left heading
		\a@normalfont \leftmark \hfil \thep@ge }%
	\let\@mkboth\@gobbletwo	\let\subsectionmark\@gobble
	\let\chaptermark\@gobble \let\sectionmark\@gobble }

%    ****************************************
%    *         TITLE AND ABSTRACT           *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\if@titlepage
  \newcommand\maketitle{\begin{titlepage}%
  \let\footnotesize\small
  \let\footnoterule\relax
  \null\vfil
  \vskip 60\p@
  \begin{center}%
    {\LARGE \@title \par}%
    \vskip 3em%
    {\large
     \lineskip .75em%
      \begin{tabular}[t]{c}%
        \@author
      \end{tabular}\par}%
      \vskip 1.5em%
    {\large \@date \par}%       % Set date in \large size.
  \end{center}\par
  \@thanks
  \vfil\null
  \end{titlepage}%
  \setcounter{footnote}{0}%
  \let\thanks\relax\let\maketitle\relax
  \gdef\@thanks{}\gdef\@author{}\gdef\@title{}}
\else
 \newcommand\maketitle{\par
  \begingroup
    \renewcommand\thefootnote{\@fnsymbol\c@footnote}%
    \def\@makefnmark{\hb@xt@\z@{$\m@th^{\@thefnmark}$\hss}}%
    \long\def\@makefntext##1{\parindent 1em\noindent
            \hb@xt@1.8em{\hss\@makefnmark}##1}%
    \if@twocolumn
      \ifnum \col@number=\@ne
        \@maketitle
      \else
        \twocolumn[\@maketitle]%
      \fi
    \else
      \newpage
      \global\@topnum\z@   % Prevents figures from going at top of page.
      \@maketitle
    \fi
    \thispagestyle{plain}\@thanks
  \endgroup
  \setcounter{footnote}{0}%
  \let\thanks\relax
  \let\maketitle\relax\let\@maketitle\relax
  \gdef\@thanks{}\gdef\@author{}\gdef\@title{}}

 \def\@maketitle{%
  \newpage
  \null
  \vskip 2em%
  \begin{center}%
    {\LARGE \@title \par}%
    \vskip 1.5em%
    {\large
      \lineskip .5em%
      \begin{tabular}[t]{c}%
        \@author
      \end{tabular}\par}%
    \vskip 1em%
    {\large \@date}%
  \end{center}%
  \par
  \vskip 1.5em}
\fi

\else %============= arabart.cls ==================================
\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *             UNITS                    *
%    ****************************************

\ifa@book
\newcommand\frontmatter
	{\cleardoublepage \@mainmatterfalse \pagenumbering{Abjad}}
%	{\cleardoublepage \@mainmatterfalse \pagenumbering{Abj}}
\newcommand\mainmatter
	{\cleardoublepage \@mainmattertrue \pagenumbering{arabic}}
%	{\cleardoublepage \@mainmattertrue \pagenumbering{arab}}
\newcommand\backmatter
	{\if@openright \cleardoublepage \else \clearpage
	\fi \@mainmatterfalse }
\fi

\ifa@article
\renewcommand\thesection {\@arabic\c@section}
\fi

%\allowarab \@chapapp

%    ****************************************
%    *             PARTS                    *
%    ****************************************

\renewcommand \thepart {\@arabic\c@part } 

\def \a@c@part {\a@@par
\ifa@article \addvspace{4ex}\@afterindentfalse
\else   \cleardoublepage \thispagestyle{plain}%
	\if@twocolumn \onecolumn \@tempswatrue
	\else \@tempswafalse
	\fi
	\hbox{}\vfil
\fi \secdef \a@part \a@spart }

\ifa@article

\def \a@part [#1]#2{% numbered part
\ifnum \c@secnumdepth > \m@ne 
	\refstepcounter {part}%
	\addcontentsline {toc}{part}{\protect\RL 
		{\protect\abjad {\thepart}\hspace {1em} #1 }}%
\else	\addcontentsline {toc}{part}{\protect\RL {#1}}%
\fi
\begingroup \let \end \a@@par \a@parindent \z@ \a@@raggedright
	\a@normalfont \bfseries \interlinepenalty \@M 
\ifnum \c@secnumdepth >\m@ne
	\Large \set@arabfont 
	\test@token \partname \abjad{\thepart } \end \nobreak
\fi
	\huge \set@arabfont \test@token #2 \end 
	\markboth {}{}
\endgroup
\nobreak \vskip 3ex
\a@afterheading \test@token }

\def \a@spart #1{% unnumbered part
\begingroup \let \end \a@@par \a@@raggedright \a@parindent \z@ 
	\interlinepenalty \@M
	\a@normalfont \huge \bffamily \set@arabfont \test@token #1 \end
\endgroup
\nobreak \vskip 3ex
\a@afterheading \test@token }

\else % report or book

\def \a@part [#1]#2{% numbered part
	\ifnum \c@secnumdepth > \ifa@article \m@ne \else -2 \fi
		\refstepcounter {part}%
		\addcontentsline {toc}{part}{\protect\RL 
			{\protect\abjad {\thepart}\hspace {1em} #1 }}%
	\else	\addcontentsline {toc}{part}{\protect\RL {#1}}%
	\fi
	\markboth{}{}
\begingroup \let \end \a@@par \a@@centering \interlinepenalty \@M
	\a@normalfont \bfseries
	\ifnum \c@secnumdepth > -2\relax
		\huge \set@arabfont 
		\test@token \partname \abjad{\thepart } \end
		\vskip 20\p@
	\fi
	\Huge \set@arabfont \test@token #2 \end 
\endgroup
\@endpart \test@token }

\def \a@spart #1{% unnumbered part
\begingroup \let \end \a@@par \a@@centering \interlinepenalty \@M
	\a@normalfont \Huge \bfseries \set@arabfont \test@token #1 \end
\endgroup
\@endpart \test@token }

\fi % article, report or book

\def\@part[#1]#2{% \part outside Arabic text
\begin{arabtext}
\a@part[\LR{#1}]{\LR{#2}}
\end{arabtext}}

\def\@spart#1{%	\part* outside Arabic text
\begin{arabtext}
\a@spart {\LR{#1}}
\end{arabtext}}

%    ****************************************
%    *             CHAPTERS                 *
%    ****************************************


\ifa@article \else  % report or book
\allowarab \thechapter

\newcommand \a@c@chapter {\a@@par
	\if@openright \cleardoublepage \else \clearpage \fi
	\thispagestyle {plain}%
	\global\@topnum \z@ \@afterindentfalse
	\secdef \a@chapter \a@schapter }

\def \a@chapter [#1]#2{% numbered chapter
	\ifnum \c@secnumdepth >\m@ne
		\if@mainmatter
			\refstepcounter {chapter}%
			\typeout {\@chapapp \space \thechapter.}%
			\addcontentsline {toc}{chapter}%
			{\protect\numberline {\thechapter }\protect\RL {#1}}%
		\else \addcontentsline {toc}{chapter}{\protect\RL {#1}}%
		\fi		     
	\else \addcontentsline {toc}{chapter}{\protect\RL {#1}}%
	\fi
\chaptermark{\RL {#1}}%
\addtocontents {lof}{\protect\addvspace {10\p@}}%
\addtocontents {lot}{\protect\addvspace {10\p@}}%
	\if@twocolumn \@topnewpage [\a@makechapterhead {#2}]%
	\else \a@makechapterhead {#2}\a@afterheading
	\fi 
\tracingmacros 0
\test@token }

\def\a@makechapterhead #1{% make heading with number
	\null \vskip 50\p@ 
\begingroup \let \end \a@@par \a@@raggedright \interlinepenalty\@M
	\a@normalfont \bfseries
	\ifnum \c@secnumdepth >\m@ne
	\if@mainmatter
		\edef \thechapter {\@chapapp \space \thechapter . }%
		\huge \set@arabfont \test@token {\thechapter } \end 
		\nobreak \vskip 20\p@
	\fi\fi
	\Huge \set@arabfont \test@token #1 \end 
	\nobreak \vskip 40\p@
\endgroup }

\def\a@schapter #1{% unnumbered chapter
	\if@twocolumn \@topnewpage [\a@makeschapterhead {#1}]%
	\else \a@makeschapterhead {#1}\a@afterheading
	\fi 
\test@token }

\def\a@makeschapterhead #1{%
\null \vskip 50\p@ 
\begingroup \let \end \a@@par \a@@raggedright \interlinepenalty\@M
	\a@normalfont \Huge \bfseries \set@arabfont \test@token #1 \end
\endgroup
\nobreak \vskip 40\p@ }

\fi % report or book

%*********************************************************************
%*********************************************************************
%************************* rest not yet adapted **********************
%*********************************************************************
%*********************************************************************

%    ****************************************
%    *             SECTIONS                 *
%    ****************************************

\def \a@c@section {\a@startsection 
{section}{1}{\z@}{-3.5ex plus-1ex minus -.2ex}{2.3ex plus.2ex}%
%{\reset@font \Large\setnashbf }}
{\a@normalfont \Large \bfseries }}

\def \a@c@subsection {\a@startsection
{subsection}{2}{\z@}{-3.25ex plus-1ex minus-.2ex}{1.5ex plus.2ex}%
{\a@normalfont \large \bfseries }}

\def \a@c@subsubsection {\a@startsection
{subsubsection}{3}{\z@}{-3.25ex plus -1ex minus-.2ex}{1.5ex plus.2ex}%
{\a@normalfont \normalsize \bfseries }}

\def \a@c@paragraph {\a@startsection
{paragraph}{4}{\z@}{3.25ex plus1ex minus.2ex}{-1em}%
{\a@normalfont \normalsize \bfseries }}
			     
\def\a@c@subparagraph {\a@startsection 
{subparagraph}{5}{\parindent}{3.25ex plus1ex minus .2ex}{-1em}%
{\a@normalfont \normalsize \bfseries }}
 
%    ****************************************
%    *                LISTS                 *
%    ****************************************

\at@arabtext {% switch to Arabic terms
	\def\theenumii{\arabic{enumii}}
	\def\theenumiii{\arabic{enumiii}}
	\def\theenumiv{\arabic{enumiv}} 
%
	\def\labelenumi{\theenumi\space-} 
	\def\labelenumii{\abj@d{\theenumii}-}
	\def\labelenumiii{\theenumiii\nospace(}
	\def\labelenumiv{\abj@d{\theenumiv}\nospace)}
%
	\def\labelitemi{$\circ$}
	\def\labelitemii{$-$}
	\def\labelitemiii{$\star$}
	\def\labelitemiv{$+$}
}

%    ****************************************
%    *         OTHER ENVIRONMENTS           *
%    ****************************************

% DESCRIPTION

\def \descriptionlabel #1{\setnashbf \RL{#1}\hskip \labelsep }
\def \descriptionlabel #1{\bfseries \RL{#1}\hskip \labelsep }

% THEOREM
%%%%%%%%%%%%% NOT IMPLEMENTED 

% EQUATION and EQNARRAY
%%%%%%%%%%%%% NOT IMPLEMENTED 

% TITLEPAGE
%%%%%%%%%%%%% NOT IMPLEMENTED 

% TABBING
%%%%%%%%%%%%% NOT IMPLEMENTED 

% MINIPAGE
%%%%%%%%%%%%% NOT IMPLEMENTED 

% FRAMEBOX
%%%%%%%%%%%%% NOT IMPLEMENTED 

%    ****************************************
%    *             ABSTRACT                 *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\ifa@book
\else
\if@titlepage
  \newenvironment{abstract}{%
      \titlepage
      \null\vfil
      \begin{center}%
        \bfseries \abstractname
        \@endparpenalty\@M
      \end{center}}%
     {\par\vfil\null\endtitlepage}
\else
  \newenvironment{abstract}{%
      \if@twocolumn
        \section*{\abstractname}%
      \else
        \small
        \begin{center}%
          {\bfseries \abstractname\vspace{-.5em}\vspace{\z@}}%
        \end{center}%
        \quotation
      \fi}
      {\if@twocolumn\else\endquotation\fi}
\fi
\fi

\else %============= arabart.cls ==================================

\ifa@book
\else
\if@titlepage
\iffalse  
\newenvironment{abstract}{%
      \titlepage
      \null\vfil
      \begin{center}%
        \bfseries \abstractname
        \@endparpenalty\@M
      \end{center}}%
     {\par\vfil\null\endtitlepage}
\fi
\else

\def \a@c@abstract{%
\if@twocolumn \def \next 
	{\a@c@section*{\abstractname}}%
\else 	\def \next 
	{\small
	\begingroup \center
%		\setnashbf \abstractname 
		\a@normalfont \bfseries \abstractname 
		\vspace{-.5em}\vspace{\z@}%
	\endcenter \endgroup
        \a@c@quotation }%
\fi 
	\def \endabstract
	{\if@twocolumn\else\endquotation\fi}%
\next }

\fi
\fi

\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *         OTHER ENVIRONMENTS           *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\newenvironment{verse}
               {\let\\=\@centercr
                \list{}{\itemsep      \z@
                        \itemindent   -1.5em%
                        \listparindent\itemindent
                        \rightmargin  \leftmargin
                        \advance\leftmargin 1.5em}%
                \item[]}
               {\endlist}

\newenvironment{quotation}
               {\list{}{\listparindent 1.5em%
                        \itemindent    \listparindent
                        \rightmargin   \leftmargin
                        \parsep        \z@ \@plus\p@}%
                \item[]}
               {\endlist}

\newenvironment{quote}
               {\list{}{\rightmargin\leftmargin}%
                \item[]}
               {\endlist}

\else %============= arabart.cls ==================================

\def \a@c@verse {%
%\tracingmacros 1
\let \endverse \a@@endtrivlist
\a@@list{}{\itemsep\z@ \itemindent -1.5em \listparindent \itemindent
    \rightmargin\leftmargin \advance\leftmargin 1.5em}\a@@item[]}

\def \a@c@quotation {%
%\tracingmacros 1
\a@@list{}{\listparindent 1.5em \itemindent\listparindent
    \rightmargin\leftmargin \parsep \z@ plus\p@}\a@@item[]}

\def \a@c@quote {%
%\tracingmacros 1
\let \endquote \a@@endtrivlist
\a@@list{}{\rightmargin\leftmargin}\a@@item[]}

\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *              TITLE PAGE              *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\newenvironment{titlepage}
{% \titlepage
	\ifa@book
      \cleardoublepage
	\fi
      \if@twocolumn
        \@restonecoltrue\onecolumn
      \else
        \@restonecolfalse\newpage
      \fi
      \thispagestyle{empty}%
      \if@compatibility
        \setcounter{page}{0}
      \else
	\ifa@book \else
        \setcounter{page}{1}%
	\fi
      \fi }%
{% \endtitlepage
	\if@restonecol\twocolumn \else \newpage \fi
	\ifa@book \else
        \setcounter{page}{1}%
	\fi }

\else %============= arabart.cls ==================================
\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *              APPENDIX                *
%    ****************************************

\def\a@c@appendix{%\par
	\setcounter{section}{0}%
	\renewcommand\@chapapp {\appendixname }%
\ifa@article \renewcommand\thesection {\@Abjad \c@section }%
	\setcounter{subsection}{0}%
\else \renewcommand\thechapter {\@Abjad \c@chapter }%
	\setcounter{chapter}{0}%
\fi \test@token }

%    ****************************************
%    *         FIGURES AND TABLES           *
%    ****************************************

\ifa@article
	\renewcommand\theequation{\@arabic\c@equation}
	\renewcommand\thefigure{\@arabic\c@figure}
	\renewcommand\thetable{\@arabic\c@table}
\fi

\def\fnum@figure {\figurename \space \thefigure }

\def\fnum@table {\tablename \space \thetable }

\long\def \a@makecaption #1#2{%
  \vskip\abovecaptionskip
  \sbox\@tempboxa{#2 : #1}%
  \ifdim \wd\@tempboxa >\hsize
    #2 : #1\par
  \else
    \global \@minipagefalse
    \hb@xt@\hsize{\hfil\box\@tempboxa\hfil}%
  \fi
  \vskip\belowcaptionskip }

\let \@makecaption \a@makecaption

\long\def \a@caption #1[#2]#3{\par 
	\addcontentsline {\csname ext@#1\endcsname}{#1}
	{\protect\numberline {\csname the#1\endcsname}\protect #2}%
\begingroup \@parboxrestore \normalsize
	\edef \reserved@a {\csname fnum@#1\endcsname }%
	\@makecaption {\xpa \RL \xpa {\reserved@a }}{#3}\par
\endgroup}

\let \@caption \a@caption 

\long\def \a@@caption #1[#2]#3{\par 
	\addcontentsline {\csname ext@#1\endcsname}{#1}
	{\protect\numberline {\csname the#1\endcsname}\protect\RL {#2}}%
\begingroup \@parboxrestore \normalsize
	\edef \reserved@a {\csname fnum@#1\endcsname }%
	\@makecaption {\xpa \RL \xpa {\reserved@a }}{\RL {#3}}\par
\endgroup \test@token }

\at@arabtext {\let \@caption \a@@caption }

\let \a@c@caption \caption

%    ****************************************
%    *         TABLE OF CONTENTS, ETC.      *
%    ****************************************

\ifa@article \setcounter{tocdepth}{3}
\else 	\setcounter{tocdepth}{2}
\fi

\setcounter{tocdepth}{5} % for test only

\def \a@c@tableofcontents {\a@t@ble {\contentsname }{toc}}
%\def \a@c@tableofcontents {\a@t@ble {\RL{\contentsname }}{toc}}

\def \a@c@starttoc #1{\endarabtext 
\the\arabtext@hook 
\@starttoc {#1}\arabtext }
 
%\def \a@t@ble #1#2{% \tableofcontents etc.
%	\a@c@section *{#1}\@mkboth {#1}{#1}\starttoc {#2}}

%\def \a@c@@mkboth #1#2{\@mkboth {\RL{#1}}{\RL{#2}}\test@token }

\def \a@l@section #1#2{% for arabic mode
\ifnum \c@tocdepth >\z@
	\addpenalty {\@secpenalty }\addvspace {1.0em plus\p@ }
	\if@twocolumn \@tempdima 1.0em \else \@tempdima 1.5em \fi 
	\a@@lsection {#1}{#2}{\a@normalfont \bfseries }%
\fi }

\def \a@l@part #1#2{% for arabic mode
\ifnum \c@tocdepth >-2\relax
	\addpenalty {-\@highpenalty }\addvspace {2.25em plus\p@ }
	\if@twocolumn \@tempdima 2.0em \else \@tempdima 3.0em \fi 
	\a@@lsection {#1}{#2}{\a@normalfont \large \bfseries }%
\fi }

\def \a@l@chapter #1#2{% for arabic mode
\ifnum \c@tocdepth >\m@ne
	\addpenalty {-\@highpenalty }\addvspace {1.0em plus\p@ }
	\if@twocolumn \@tempdima 1.0em \else \@tempdima 1.5em \fi 
	\a@@lsection {#1}{#2}{\a@normalfont \bfseries }%
      	\penalty\@highpenalty
\fi }

\at@arabtext {% switch to Arabic terms
	\let \l@part \a@l@part
	\let \l@section \a@l@section
	\let \l@chapter \a@l@chapter
}

\if@twocolumn
	\def\l@subsection{\@dottedtocline{2}{1.0em}{1.5em}}
	\def\l@subsubsection{\@dottedtocline{3}{2.5em}{2.3em}}
	\def\l@paragraph{\@dottedtocline{4}{4.8em}{3.1em}}
	\def\l@subparagraph{\@dottedtocline{5}{7.9em}{3.9em}}
	\def\@tocrmarg {1.55em}
\fi

\def \a@c@listoffigures {\a@t@ble {\listfigurename }{lof}}

\def \a@c@listoftables {\a@t@ble {\listtablename }{lot}}

%    ****************************************
%    *             BIBLIOGRAPHY             *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\newdimen\bibindent
\bibindent=1.5em

\newenvironment{thebibliography}[1]
%     {\chapter*{\bibname
     {\section*{\bibname
        \@mkboth{\MakeUppercase\bibname}{\MakeUppercase\bibname}}%
      \list{\@biblabel{\@arabic\c@enumiv}}%
           {\settowidth\labelwidth{\@biblabel{#1}}%
            \leftmargin\labelwidth
            \advance\leftmargin\labelsep
            \@openbib@code
            \usecounter{enumiv}%
            \let\p@enumiv\@empty
            \renewcommand\theenumiv{\@arabic\c@enumiv}}%
      \sloppy\clubpenalty4000\widowpenalty4000%
      \sfcode`\.=\@m}
     {\def\@noitemerr
       {\@latex@warning{Empty `thebibliography' environment}}%
      \endlist}

%\newcommand\newblock{\hskip .11em\@plus.33em\@minus.07em}
%\let\@openbib@code\@empty

\else %============= arabart.cls ==================================
\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *              THE INDEX               *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\newcommand\newblock{}

\newenvironment{theindex}
               {\if@twocolumn
                  \@restonecolfalse
                \else
                  \@restonecoltrue
                \fi
                \columnseprule \z@
                \columnsep 35\p@
                \twocolumn[\@makeschapterhead{\indexname}]%
                \@mkboth{\MakeUppercase\indexname}%
                        {\MakeUppercase\indexname}%
                \thispagestyle{plain}\parindent\z@
                \parskip\z@ \@plus .3\p@\relax
                \let\item\@idxitem}
               {\if@restonecol\onecolumn\else\clearpage\fi}

\newcommand\@idxitem{\hangindent 40\p@}
\newcommand\subitem{\par\hangindent 40\p@ \hspace*{20\p@}}
\newcommand\subsubitem{\par\hangindent 40\p@ \hspace*{30\p@}}
\newcommand\indexspace{\par \vskip 10\p@ \@plus5\p@ \@minus3\p@\relax}

\else %============= arabart.cls ==================================
\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *             FOOTNOTES                *
%    ****************************************

% Default definition
\def\@makefnmark{\hbox{$^{\@thefnmark}\m@th$}}

\def \footnoterule{%
  \kern-3\p@
  \hbox to \columnwidth{\hskip .6\columnwidth \hrulefill }
  \kern2.6\p@}

\RLfootnotes

% see also "alatex.sty"

%    ****************************************
%    *      LANGUAGE DEPENDENT NAMES        *
%    ****************************************

\iffalse %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%

\newcommand\contentsname{Contents}
\newcommand\listfigurename{List of Figures}
\newcommand\listtablename{List of Tables}
\newcommand\bibname{Bibliography}
\newcommand\refname{References}
\newcommand\indexname{Index}
\newcommand\figurename{Figure}
\newcommand\tablename{Table}
\newcommand\partname{Part}
\newcommand\chaptername{Chapter}
\newcommand\appendixname{Appendix}
\newcommand\abstractname{Abstract}

\else %============= arabart.cls ==================================

% tableofcontents = al-mu.htawayAtu
% listoffigures = qA'imaTu al-.suwaru
% listoftables = qA'imaTu al-^gadAwilu
% listofpictures = qA'imaTu al-rusUmu
% bibliography = al-marA^gi`u
% references = al-ma.sAdiru
% index = al-fihrisu (al-fihristu)
% chapter = al-fa.slu
% appendix = al-mul.haqu
% abstract = al-mula_h_ha.su
% attachments = al-murfaqAtu
% number = al-raqmu

%\at@arabtext {% switch to Arabic terms
	\def\bibname        {\a@tok(al-marAGiPu)}
	\def\refname        {\a@tok(al-maSAdiru)}
	\def\indexname      {\a@tok(al-fihristu)}
	\def\figurename     {\a@tok(SUraTuN)}
	\def\tablename      {\a@tok(GadwaluN)}
	\def\partname       {\a@tok(al-GuzQzu)}
	\def\chaptername    {\a@tok(al-faSlu)}
	\def\appendixname   {\a@tok(al-mulHaqu)}
	\def\abstractname   {\a@tok(al-mulaXXaSu)}
%}

\allowarab \contentsname	
\allowarab \listfigurename 
\allowarab \listtablename  	
\allowarab \bibname  
\allowarab \refname  
\allowarab \indexname  
\allowarab \figurename  
\allowarab \tablename  
\allowarab \partname  
\allowarab \chaptername		
\allowarab \appendixname
\allowarab \abstractname

\fi %%%%%%%%%%%%%%%% article.cls %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

%    ****************************************
%    *           INITIALIZATION             *
%    ****************************************

% Default initializations

\ifa@book \pagestyle{headings}
\else 	  \pagestyle{plain}
\fi

\pagenumbering{arabic}      % Arabic page numbers

%    ****************************************
%    *       SIZE DEPENDENT PATCHES         *
%    ****************************************

\input {asize1\@ptsize.clo}

\UsePackage {arabext} % load local patches for ArabTeX

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

\gdef \contentsname{Contents}
\gdef \listfigurename{List of Figures}
\gdef \listtablename{List of Tables}

\at@arabtext {% switch to Arabic terms
	\def\contentsname   {\a@tok(al-muHtawayAtu)}
	\def\listfigurename {\a@tok(qAQAimaTu aS-Suwari)}
	\def\listtablename  {\a@tok(qAQAimaTu al-GadAwili)}
	\def\chaptername    {\a@tok(al-faSlu)}
}

\ifx \a@part \undefined \else

% tableofcontents = al-mu.htawayAtu
% listoffigures = qA'imaTu al-.suwaru
% listoftables = qA'imaTu al-^gadAwilu
% listofpictures = qA'imaTu al-rusUmu
% bibliography = al-marA^gi`u
% references = al-ma.sAdiru
% index = al-fihrisu (al-fihristu)
% chapter = al-fa.slu
% appendix = al-mul.haqu
% abstract = al-mula_h_ha.su
% attachments = al-murfaqAtu
% number = al-raqmu

\def\bibname        {\stdcode{al-marA^gi`u}}
\def\refname        {\stdcode{al-ma.sAdiru}}
\def\indexname      {\stdcode{al-fihristu}}
\def\figurename     {\stdcode{.sUraTuN}}
\def\tablename      {\stdcode{^gadwaluN}}
\def\partname       {\stdcode{al-^guz'u}}
\def\chaptername    {\stdcode{al-fa.slu}}
\def\appendixname   {\stdcode{al-mul.haqu}}
\def\abstractname   {\stdcode{al-mula_h_ha.su}}

\at@arabtext {% switch to Arabic terms
\def\contentsname   {\stdcode{al-mu.htawayAtu}}
\def\listfigurename {\stdcode{qA'imaTu al-.suwari}}
\def\listtablename  {\stdcode{qA'imaTu al-^gadAwili}}
}
\fi

\def\a@makechapterhead #1{% make heading with number
	\null \vskip 50\p@ 
\begingroup \let \end \a@@par \a@@raggedright \interlinepenalty\@M
	\a@normalfont \bfseries
	\ifnum \c@secnumdepth >\m@ne
	\if@mainmatter
		\edef \thechapter {\thechapter }%
		\huge \set@arabfont \test@token \@chapapp {\thechapter} \end 
		\nobreak \vskip 20\p@
	\fi\fi
	\Huge \set@arabfont \test@token #1 \end 
	\nobreak \vskip 40\p@
\endgroup }

\allowarab \@chapapp

\def\a@c@appendix{%\par
	\setcounter{section}{0}%
	\renewcommand\@chapapp {\appendixname }%
\ifa@article \renewcommand\thesection {\@abjad \c@section }%
	\setcounter{subsection}{0}%
\else \renewcommand\thechapter {\@abjad \c@chapter }%
	\setcounter{chapter}{0}%
\fi \test@token }

\def \a@chapter [#1]#2{% numbered chapter
\ifnum \c@secnumdepth >\m@ne
	\if@mainmatter
		\refstepcounter {chapter}%
		\typeout {\@chapapp \space \thechapter.}%
		\addcontentsline {toc}{chapter}%
		{\protect\numberline 
		{\protect\RL{\protect\stdcode{\thechapter }}}%
		\protect\RL {#1}}%
	\else \addcontentsline {toc}{chapter}{\protect\RL {#1}}%
	\fi		     
\else \addcontentsline {toc}{chapter}{\protect\RL {#1}}%
\fi
\chaptermark{\RL {#1}}%
\addtocontents {lof}{\protect\addvspace {10\p@}}%
\addtocontents {lot}{\protect\addvspace {10\p@}}%
\if@twocolumn \@topnewpage [\a@makechapterhead {#2}]%
\else 	\a@makechapterhead {#2}\a@afterheading
\fi \test@token }

\long\def \a@@caption #1[#2]#3{\par 
	\addcontentsline {\csname ext@#1\endcsname}{#1}
	{\protect\numberline {\csname the#1\endcsname}\protect\RL {#2}}%
\begingroup \@parboxrestore \normalsize
	\def \protect {\nxp\protect\nxp}
	\edef \thecaption {\csname fnum@#1\endcsname }%
	\let \protect \relax
	\@makecaption {\RL {\thecaption }}{\RL {#3}}\par
\endgroup \test@token }

\long\def \a@makecaption #1#2{%
  \vskip\abovecaptionskip
  \sbox\@tempboxa{\RL{#1: #2}}%
  \ifdim \wd\@tempboxa >\hsize
\begin{arabtext}    #1: #2
\end{arabtext}
  \else
    \global \@minipagefalse
    \hb@xt@\hsize{\hfil\box\@tempboxa\hfil}%
  \fi
  \vskip\belowcaptionskip }

\allowarab \caption
\allowarab \thecaption

\at@arabtext {\let \@caption \a@@caption 
\let \@makecaption \a@makecaption }

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

\def \a@l@section #1#2{% Text, Pageno
% for arabic mode
\ifnum \c@tocdepth >\z@
	\addpenalty {\@secpenalty }%
	\addvspace {1.0em plus\p@ }%
%	\@tempdima 1.5em 
	\@tempdima 1.0em 
%	\@tempdima \if@twocolumn 1.0em \else 1.5em \fi \relax
	\a@@lsection {#1}{#2}{\a@normalfont \bfseries }%
\fi }

\def \a@l@part #1#2{% Text, Pageno
% for arabic mode
\ifnum \c@tocdepth >-2\relax
	\addpenalty {-\@highpenalty }%
	\addvspace {2.25em plus\p@ }%
	\@tempdima 2.0em 
%	\@tempdima \if@twocolumn 2.0em \else 3.0em \fi \relax
	\a@@lsection {#1}{#2}{\a@normalfont \large \bfseries }%
\fi }

\def \a@l@chapter #1#2{% Text, Pageno
% for arabic mode
\ifnum \c@tocdepth >\m@ne
	\addpenalty {-\@highpenalty }%
	\addvspace {1.0em plus\p@ }%
%	\@tempdima 1.5em 
	\@tempdima 1.0em 
%	\@tempdima \if@twocolumn 1.0em \else 1.5em \fi \relax
	\a@@lsection {#1}{#2}{\a@normalfont \bfseries }%
      	\penalty\@highpenalty
\fi }

\def \a@x@sect {% arabic section TOC formats if article
	\def\a@l@subsection{\@dottedtocline{2}{1.0em}{1.5em}}
	\def\a@l@subsubsection{\@dottedtocline{3}{2.5em}{2.1em}}
	\def\a@l@paragraph{\@dottedtocline{4}{4.6em}{2.8em}}
	\def\a@l@subparagraph{\@dottedtocline{5}{9.4em}{3.4em}}
}

\def \a@y@sect {% arabic section TOC formats if report or book
	\def\a@l@section{\@dottedtocline{1}{1.0em}{1.5em}}
	\def\a@l@subsection{\@dottedtocline{2}{2.5em}{2.1em}}
	\def\a@l@subsubsection{\@dottedtocline{3}{4.6em}{2.8em}}
	\def\a@l@paragraph{\@dottedtocline{4}{9.4em}{3.4em}}
	\def\a@l@subparagraph{\@dottedtocline{5}{23.8em}{4.1em}}
}

\def \a@xy@sect {% arabic section TOC formats
\ifa@article \a@x@sect \else \a@y@sect \fi }

\def \a@c@tableofcontents {\a@xy@sect % define arabic section TOC formats
\a@t@ble {\contentsname }{toc}}

% CAUTION: \a@xy@sect must be executed before expanding \the\arabtex@hook
%          and prior to \@starttoc within \a@t@ble, see arabrep.cls

\def\a@l@figure{\@dottedtocline{1}{1.0em}{1.5em}}
\def\a@l@table{\@dottedtocline{1}{1.0em}{1.5em}}

\at@arabtext {% switch to Arabic terms, execute from asect::a@t@ble
	\let \l@part \a@l@part
	\let \l@chapter \a@l@chapter
	\let \l@section \a@l@section
	\let \l@subsection \a@l@subsection
	\let \l@subsubsection \a@l@subsubsection
	\let \l@paragraph \a@l@paragraph
	\let \l@subparagraph \a@l@subparagraph
	\let \l@figure \a@l@figure
	\let \l@table  \a@l@table
}

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

% restore LaTeX version, delete \a@caption!

\long\def\@caption#1[#2]#3{%
  \par
  \addcontentsline{\csname ext@#1\endcsname}{#1}%
    {\protect\numberline{\csname the#1\endcsname}{\ignorespaces #2}}%
  \begingroup
    \@parboxrestore
    \if@minipage
      \@setminipage
    \fi
    \normalsize
    \@makecaption{\csname fnum@#1\endcsname}{\ignorespaces #3}\par
  \endgroup}

\long\def \a@caption #1[#2]#3{\par 
	\addcontentsline {\csname ext@#1\endcsname}{#1}
	{\protect\numberline {\csname the#1\endcsname}\protect #2}%
\begingroup \@parboxrestore \normalsize
	\protected@edef \reserved@a {\csname fnum@#1\endcsname }%
	\xpa\@makecaption \xpa{\xpa\RL \xpa{\reserved@a }}{#3}\par
\endgroup}

%\let \@caption \a@caption 

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% end of file arabrep.cls
\endinput
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

