#!/bin/sh

archbin=false
tdsarchive=false

while [ $# -gt 0 ]; do
    case $1 in
	--prefix)
	    if [ $# -lt 2 ]; then
		echo "$0: argument expected after --prefix" >&2
		exit 1
	    else
		prefix=$2
		shift 2
	    fi
	    ;;
	--prefix=*)
	    prefix=${1#--prefix=}
	    shift 1
	    ;;
	--archbin)
	    archbin=true
	    shift 1
	    ;;
        --tdsarchive)
	    tdsarchive=true
	    shift 1
	    ;;
	--bindir)
	    if [ $# -lt 2 ]; then
		echo "$0: argument expected after --bindir" >&2
		exit 1
	    else
		bindir=$2
		shift 2
	    fi
	    ;;
	--bindir=*)
	    bindir=${1#--bindir=}
	    shift 1
	    ;;
	--mandir)
	    if [ $# -lt 2 ]; then
		echo "$0: argument expected after --mandir" >&2
		exit 1
	    else
		mandir=$2
		shift 2
	    fi
	    ;;
	--mandir=*)
	    mandir=${1#--mandir=}
	    shift 1
	    ;;
	--texmf)
	    if [ $# -lt 2 ]; then
		echo "$0: argument expected after --texmf" >&2
		exit 1
	    else
		texmf=$2
		shift 2
	    fi
	    ;;
	--texmf=*)
	    texmf=${1#--texmf=}
	    shift 1
	    ;;
	--version|-v)
	    echo "install.sh v0.1"
	    echo "Copyright (c) Markus Kohm 2002"
	    exit 0
	    ;;
	--help|-h)
	    echo "Usage: install.sh [OPTIONS]"
	    echo
	    echo "Installs SplitIndex for all unix like environments."
	    echo
	    echo "OPTIONS:"
	    echo "--prefix=<DIR>    install binaries at <DIR>/bin and manuals at <DIR>/man"
	    echo "                  (default: /usr/local)"
	    echo "--bindir=<DIR>    install binaries at <DIR>"
	    echo "--archbin         install binaries at arch depending directories at bindir"
	    echo "--mandir=<DIR>    install manuals at <DIR>"
	    echo "--texmf=<DIR>     install packages and TeX programs at <DIR>/tex/latex/splitindex,"
	    echo "                  documentation (dvi and pdf) at <DIR>/doc/latex/splitindex and"
	    echo "                  sources at <DIR>/source/latex/splitindex"
	    echo "                  (default: \$TEXMFLOCAL if you are root and \$HOMETEXMF if"
	    echo "                  you are not root)"
	    exit 0
	    ;;
	--dist)
	    mkdir -p splitidx
	    cp -L splitindex splitindex-Linux-i386
	    cp -L splitindex.tex splitindex.pl splitindex.c splitindex.java \
		  splitindex.class splitindex.exe splitidx.dtx splitidx.ins \
		  splitindex-Linux-i386 splitindex-OpenBSD-i386 \
		  splitindex.1 install.txt manifest.txt install.sh \
		  README splitidx
	    tar jcvf splitidx-`date +%F`.tar.bz2 splitidx
	    cd splitidx
	    rm -r chroot
	    ./install.sh --texmf ../chroot/texmf --tdsarchive
	    cd ..
	    rm -r splitidx
	    cd chroot/texmf
	    zip -r ../../splitindex-`date +%F`.tds.zip *
	    cd ../..
	    find chroot/texmf/ | \
		sed 's/chroot\//+-/g;s/[a-z0-9-]*\//-/g'
	    mkdir chroot/splitindex
	    cp -R -p -L chroot/texmf/source/latex/splitindex/* chroot/splitindex
	    cp -R -p -L chroot/texmf/doc/latex/splitindex/* chroot/splitindex
	    cd chroot
	    zip -r ../splitindex-`date +%F`.CTAN.zip splitindex
	    cd ..
	    rm -r chroot
	    exit 0
	    ;;
	*)
	    echo "unkown option \`$1'" >&2
	    echo "Try \`./install.sh --help' for more information." >&2
	    exit 1;
	    ;;
    esac
done

case `uname -s -m` in
    OpenBSD*i?86*)
	cp -pf splitindex-OpenBSD-i386 splitindex
	splitindex=splitindex
	;;
    Linux*i?86*)
	cp -pf splitindex-Linux-i386 splitindex
	splitindex=splitindex
	;;
    CYGWIN*i?86*)
	splitindex=splitindex.exe
	;;
    *)
	if ! ./splitindex -V; then
	    echo 'Os '`uname -s -m`' not supported!'
	    echo 'Trying to compile the source:'
	    gcc -O3 -Wall -o splitindex splitindex.c || \
		gcc -O3 -Wall -DNO_LONGOPT -o splitindex splitindex.c || \
		echo 'You have to compile splitindex.c by your own!'
	fi
	if ./splitindex -V; then
	    splitindex=splitindex
	    cp -p splitindex splitindex-`uname -s`-`uname -m`
	fi
	;;
esac

if [ -z "$prefix" ]; then
    prefix=/usr/local
fi
if [ -z "$bindir" ]; then
    bindir=$prefix/bin
fi
if [ -z "$mandir" ]; then
    mandir=$prefix/man
fi
if [ -z "$texmf" ]; then
    if [ "r$USER" = "rroot" ]; then
	texmf=`kpsexpand '$TEXMFLOCAL'`
    else
	texmf=`kpsexpand '$HOMETEXMF'`
    fi
    if [ -z "$texmf" ]; then
	echo "kpsexpand '$TEXMFLOCAL' or kpsexpand '$HOMETEXMF' failed!" >&2
	echo "You have to use option --texmf=<DIR>." >&2
	echo "This error is fatal!" >&2
	exit 1
    fi
fi

latex splitidx.ins

latex splitidx.dtx
latex splitidx.dtx
mkindex splitidx
latex splitidx.dtx

pdflatex splitidx.dtx
pdflatex splitidx.dtx
mkindex splitidx
pdflatex splitidx.dtx

install -v -m 755 -d $texmf/doc/latex/splitindex
install -v -m 755 -d $texmf/tex/latex/splitindex
install -v -m 755 -d $texmf/source/latex/splitindex

if $tdsarchive; then
    install -v -m 755 -d $texmf/scripts/splitindex/perl
    install -v -m 755 splitindex.pl $texmf/scripts/splitindex/perl
    install -v -m 644 splitindex.1 $texmf/doc/latex/splitindex
elif $archbin; then
    install -v -m 755 -d $bindir
    install -v -m 755 -d $bindir/i386-linux
    install -v -m 755 -d $bindir/i386-openbsd
    install -v -m 755 -d $bindir/i386-cygwin
    install -v -m 755 -d $bindir/any
    install -v -m 755 splitindex-Linux-i386 $bindir/i386-linux/splitindex
    install -v -m 755 splitindex-OpenBSD-i386 $bindir/i386-openbsd/splitindex
    install -v -m 755 splitindex.exe $bindir/i386-cygwin/splitindex.exe
    install -v -m 755 splitindex.pl $bindir/any
    install -v -m 644 splitindex.class $bindir/any
    install -v -m 755 -d $mandir/man1
    install -v -m 644 splitindex.1 $mandir/man1
else
    install -v -m 755 -d $bindir
    install -v -m 755 $splitindex splitindex.pl $bindir
    install -v -m 644 splitindex.class $bindir
fi

install -v -m 644 splitindex.tex splitidx.sty $texmf/tex/latex/splitindex

install -v -m 644 README install.txt splitidx.pdf $texmf/doc/latex/splitindex

install -v -m 644 \
        splitindex.1 splitindex.c splitindex.java splitindex.class \
        splitindex.tex\
        README splitidx.dtx splitidx.ins manifest.txt install.txt \
        $texmf/source/latex/splitindex
install -v -m 755 \
       install.sh \
       splitindex.pl splitindex.exe \
       splitindex-Linux-i386 splitindex-OpenBSD-i386 \
       $texmf/source/latex/splitindex
