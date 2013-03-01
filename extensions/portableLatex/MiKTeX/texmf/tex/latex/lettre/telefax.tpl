% This file is part of the "lettre" package.
% This package is distributed under the terms of the LaTeX Project 
% Public License (LPPL) described in the file lppl.txt.
%
% Denis Mégevand - Observatoire de Genève.
%
% Ce fichier fait partie de la distribution du paquetage "lettre".
% Ce paquetage est distribué sous les termes de la licence publique 
% du projet LaTeX (LPPL) décrite dans le fichier lppl.txt.

\documentclass[10pt|11pt|12pt,twoside,leqno,fleqn,%
               francais|romand||allemand|anglais|americain,%
               origdate]{lettre} 
%\usepackage{french}
%\usepackage{babel}
%
\begin{document}
%
% Declaration du fichier de defauts
% =================================
%
% Permet d'ecrire des telefax personalises
% sans repreciser a chaque fois les parametres de l'expediteur
%
%\institut{fichier}
%
% Declaration du destinataire et environnement
% ============================================
%
\begin{telefax}{numero}{Destinataire \\
                        Adresse \\ 
                        no, rue \\
                        NPA Lieu }
%
% Parametre obligatoire
% =====================
%
\name{Nom de l'expediteur}
%
% Parametres facultatifs de l'entete  % (defauts)
% ===============================================
%
%\address{Adresse d'expedition}       % (     OBSERVATOIRE DE    )
%                                     % (         GENEVE         )
%                                     % (                        )
%                                     % (    CH-1290 Sauverny    )
%\psobs                               % ( Logo de l'Observatoire )
%\detailledaddress                    % (         Suisse         )
%
%\lieu{Se met devant la date}         % (Sauverny, )
%\nolieu
%\date{date fixe}                     % (date courante)
%\nodate
%
% Parametre de mise en page           % (defauts)
% ==============================================
%
%\marge{largeur}                      % (15mm)
%
% Parametres facultatifs              % (defauts)
% ==============================================
%
%\pagestyle{empty|headings}           % ( plain par defaut )
%\francais|\anglais|   %\_______________(\francais)
%\americain|\allemand  %/
%
%\addpages{nombre}                    % ()
%
%\location{Expediteur}                % (\name)
%\signature{signature}                % (\name)
%\secondsignature{signature}          % ()
%\thirdsignature{signature}           % ()
%
%\telephone{No de tel expediteur}     % (    +41(22) 755 26 11    )
%\fax{numero}                         % (    +41(22) 755 39 83    )
%\email{adresse}                      % (                         )
%\telex{numero}                       % (                         )
%
%\basedepage{texte}                   % ()
%\username{nom d'utilisateur}         % ()
%\internet{adresse RFC 822}           % ()
%\ccitt{adresse X400}                 % ()
%\bitnet{adresse bitnet}              % ()
%\telepac{numero telepac}             % ()
%\decnet{numero decnet}               % ()
%\internetobs                         % ([username@]scsun.unige.ch)
%\ccittobs                            % ([S=username;]OU=scsun;O=unige;%
%                                     %   PRMD=switch;ADMD=arcom;C=ch)
%
%\conc{Sujet du message}              % ()
%
% Corps du fax
% ============
%
\opening{Cher Ami,}
%
 Texte du message
%
\closing{Salutations}
%
% Paragraphes supplementaires
% ===========================
%
%\ps{label}{texte du post-scriptum}
%\encl{annexes separees par des \\}
%\cc{destinataires de copies conformes separes par des \\}
%
\end{telefax}
%
\end{document}
