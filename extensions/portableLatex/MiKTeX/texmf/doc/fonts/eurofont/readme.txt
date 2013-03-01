File readme.txt
Part of the eurofont package
Copyright 1998 Rowland McDonnell
email: rebecca@astrid.u-net.com

The eurofont package consists of the following files:

adobeuro
  readme.txt
  tfmfiles
    zpeub.tfm
    zpeubi.tfm
    zpeubis.tfm
    zpeubit.tfm
    zpeubs.tfm
    zpeubt.tfm
    zpeur.tfm
    zpeuri.tfm
    zpeuris.tfm
    zpeurit.tfm
    zpeurs.tfm
    zpeurt.tfm
eurofont.dtx
eurofont.ins
marvosym
  readme.txt
  source
    fmvr8x.afm
    mvrit.tex
  tfmfiles
    original
      fmvr8x.tfm
      fmvri8x.tfm
    yandy
      fmvr8x.tfm
      fmvri8x.tfm
readme.txt

To install the package, do the following:

Run LaTeX on eurofont.ins

Put eurofont.sty, eurofont.cfg, and all the files ending in .fd into a 
directory on your tex-inputs search path.

Run LaTeX on eurofont.dtx and print out the documentation.

Read at least the introductory sections of the documentation.

If you want to use Adobe's Eurofonts, put the files zpeub.tfm - 
zpeurt.tfm (all the files in the adobeuro/tfm/ directory) into a directory 
on your tex-fonts search path.  The file dvidrive.txt has 
information on how to configure your dvi driver to use Adobe's Eurofonts.

If you want to use the Marvosym fount, read the eurofont package 
documentation. The file dvidrive.txt has information on how to configure 
your dvi driver to use the marvosym fount.

Read the documentation so you can set up the package to meet your needs.


FONTS CONTAINING EURO SYMBOLS
=============================

Aside from the Text Companion founts which accompany the EC founts (the T1 
encoded version of the standard Computer Modern founts), Metafont euro 
symbols are included in two other founts that I know of, both available 
from CTAN. The following urls retrieve the packages from my nearest CTAN 
mirror:

ftp://ftp.tex.ac.uk/tex-archive/macros/latex/contrib/supported/china2e.zip
ftp://ftp.tex.ac.uk/tex-archive/fonts/eurosym.zip

(other CTAN sites are listed at the end of this document)

There are PostScript Type 1 founts containing euro symbols: Adobe's 
Eurofonts, a set of 12 founts providing seriffed, sanserif, and monospaced 
euro symbols in medium upright, italic, bold, and bold italic version; and 
the Marvosym fount, which has three euro symbols, very similar to the 
medium upright seriffed, sanserif, and monospaced euro symbols from 
Adobe.

Adobe's Eurofonts are available (September 1998) in a Mac version 
from here:

ftp://ftp.adobe.com/pub/adobe/type/mac/all/eurofont.sea.hqx
ftp://ftp-pac.adobe.com/pub/adobe/type/mac/all/eurofont.sea.hqx

For Textures users on Macs, take the installer from CTAN:

systems/mac/textures/contrib/IdealFonts/EuroDefs.sit.hqx
systems/mac/textures/contrib/IdealFonts/README.IF

You should still download Adobe's Eurofonts separately.

And in a version suitable for MS-Windows PCs and Unix from here:

ftp://ftp.adobe.com/pub/adobe/type/win/all/eurofont.exe
ftp://ftp-pac.adobe.com/pub/adobe/type/win/all/eurofont.exe

these files are self-extracting archives on MS-Windows computers which 
can be decompressed on Unix computers with the unzip command.

The marvosym fount is available from CTAN; this url retrieves the marvosym 
fount from my nearest CTAN site (November 1998):

ftp://ftp.tex.ac.uk/tex-archive/fonts/psfonts/marvosym.zip


CTAN SITES -- JANUARY 1999.
===========================

The three CTAN primary sites:

ctan.tug.org (in Massachusetts, USA)
ftp.dante.de (in Deutschland)
ftp.tex.ac.uk (in England)

this url can point you to your nearest CTAN site - there are many more 
than the three above:

http://www.ucc.ie/cgi-bin/ctan


