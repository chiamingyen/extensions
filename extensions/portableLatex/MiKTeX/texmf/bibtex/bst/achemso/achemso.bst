%%
%% This is file `achemso.bst',
%% generated with the docstrip utility.
%%
%% The original source files were:
%%
%% achemso.dtx  (with options: `bst')
%% ----------------------------------------------------------------
%% achemso --- Support for submissions to American Chemical Society
%%   journals
%% E-mail: joseph.wright@morningstar2.co.uk
%% Released under the LaTeX Project Public License v1.3c or later
%% See http://www.latex-project.org/lppl.txt
%% ----------------------------------------------------------------
%% 

ENTRY
  { address
    author
    booktitle
    chapter
    ctrl-use-title
    ctrl-etal-number
    ctrl-etal-truncate
    doi
    edition
    editor
    howpublished
    institution
    journal
    key
    note
    number
    organization
    pages
    publisher
    school
    series
    title
    type
    url
    volume
    year
  }
  {}
  { label
    extra.label
    short.list
  }

INTEGERS {
  before.all
  mid.sentence
  after.sentence
  after.block
  after.item
}

FUNCTION { init.state.consts } {
  #0 'before.all :=
  #1 'mid.sentence :=
  #2 'after.sentence :=
  #3 'after.block :=
  #4 'after.item :=
}

%% For true/false settings, #1 is regarded as true and #0 as false.
INTEGERS {
  etal.number
  etal.truncate
  is.use.title
}

FUNCTION { default.etal.number } { #15 }

FUNCTION { default.etal.truncate } { #0 }

FUNCTION { default.is.use.title } { #0 }

FUNCTION { add.comma } { ", " * }

FUNCTION { add.colon } {  ": " * }

FUNCTION { add.semicolon } { "; " * }

INTEGERS { separate.by.semicolon }

FUNCTION { init.separate.by.semicolon }
  { #1 'separate.by.semicolon := }

FUNCTION { add.comma.or.semicolon } {
  #1 separate.by.semicolon = {
    add.semicolon
  }{
    add.comma
  }
  if$
}

STRINGS { s t }

INTEGERS { output.state }

FUNCTION { output.nonnull } {
  's :=
  output.state mid.sentence = {
    add.comma write$
  }{
    output.state after.block = {
      add.semicolon write$
      newline$
      "\newblock " write$
    }{
      output.state before.all = {
        write$
      }{
        output.state after.item = {
          " " * write$
        }{
          add.period$ " " * write$
        }
        if$
      }
      if$
    }
    if$
    mid.sentence 'output.state :=
  }
  if$
  s
}

FUNCTION { output } {
  duplicate$ empty$ {
    pop$
  }{
    output.nonnull
  }
  if$
}

FUNCTION { output.check } {
  't :=
  duplicate$ empty$ {
    pop$ "Empty " t * " in " * cite$ * warning$
  }{
    output.nonnull
  }
  if$
}

FUNCTION { new.block } {
  output.state before.all = {
    skip$
  }{
    after.block 'output.state :=
  }
  if$
}

FUNCTION {new.sentence}
{ output.state after.block =
    'skip$
    { output.state before.all =
        'skip$
        { after.sentence 'output.state := }
      if$
    }
  if$
}

INTEGERS { would.add.period.textlen }

FUNCTION {would.add.period}
{ duplicate$
  add.period$
  text.length$
  'would.add.period.textlen :=
  duplicate$
  text.length$
  would.add.period.textlen =
    { #0 }
    { #1 }
  if$
}

FUNCTION {fin.entry}
{ would.add.period
    { "\relax" * write$ newline$
      "\mciteBstWouldAddEndPuncttrue" write$ newline$
      "\mciteSetBstMidEndSepPunct{\mcitedefaultmidpunct}"
      write$ newline$
      "{\mcitedefaultendpunct}{\mcitedefaultseppunct}\relax"
    }
    { "\relax" * write$ newline$
      "\mciteBstWouldAddEndPunctfalse" write$ newline$
      "\mciteSetBstMidEndSepPunct{\mcitedefaultmidpunct}"
      write$ newline$
      "{}{\mcitedefaultseppunct}\relax"
    }
  if$
  write$
  newline$
  "\EndOfBibitem" write$
}

FUNCTION {not}
{   { #0 }
    { #1 }
  if$
}

FUNCTION {and}
{   'skip$
    { pop$ #0 }
  if$
}

FUNCTION {or}
{   { pop$ #1 }
    'skip$
  if$
}

FUNCTION {field.or.null}
{ duplicate$ empty$
    { pop$ "" }
    'skip$
  if$
}

FUNCTION {emphasize}
{ duplicate$ empty$
    { pop$ "" }
    { "\emph{" swap$ * "}" * }
  if$
}

FUNCTION {boldface}
{ duplicate$ empty$
    { pop$ "" }
    { "\textbf{" swap$ * "}" * }
  if$
}

FUNCTION {paren}
{ duplicate$ empty$
    { pop$ "" }
    { "(" swap$ * ")" * }
  if$
}

FUNCTION {bbl.and}
{ "and" }

FUNCTION {bbl.chapter}
{ "Chapter" }

FUNCTION {bbl.editor}
{ "Ed." }

FUNCTION {bbl.editors}
{ "Eds." }

FUNCTION {bbl.edition}
{ "ed." }

FUNCTION {bbl.etal}
{ "et~al." }

FUNCTION {bbl.in}
{ "In" }

FUNCTION {bbl.inpress}
{ "in press" }

FUNCTION {bbl.msc}
{ "M.Sc.\ thesis" }

FUNCTION {bbl.page}
{ "p" }

FUNCTION {bbl.pages}
{ "pp" }

FUNCTION {bbl.phd}
{ "Ph.D.\ thesis" }

FUNCTION {bbl.submitted}
{ "submitted for publication" }

FUNCTION {bbl.techreport}
{ "Technical Report" }

FUNCTION {bbl.version}
{ "version" }

FUNCTION {bbl.volume}
{ "Vol." }

FUNCTION {bbl.first}
{ "1st" }

FUNCTION {bbl.second}
{ "2nd" }

FUNCTION {bbl.third}
{ "3rd" }

FUNCTION {bbl.fourth}
{ "4th" }

FUNCTION {bbl.fifth}
{ "5th" }

FUNCTION {bbl.st}
{ "st" }

FUNCTION {bbl.nd}
{ "nd" }

FUNCTION {bbl.rd}
{ "rd" }

FUNCTION {bbl.th}
{ "th" }

FUNCTION {eng.ord}
{ duplicate$ "1" swap$ *
  #-2 #1 substring$ "1" =
     { bbl.th * }
     { duplicate$ #-1 #1 substring$
       duplicate$ "1" =
         { pop$ bbl.st * }
         { duplicate$ "2" =
             { pop$ bbl.nd * }
             { "3" =
                 { bbl.rd * }
                 { bbl.th * }
               if$
             }
           if$
          }
       if$
     }
   if$
}

FUNCTION{is.a.digit}
{ duplicate$ "" =
    {pop$ #0}
    {chr.to.int$ #48 - duplicate$
     #0 < swap$ #9 > or not}
  if$
}

FUNCTION{is.a.number}
{
  { duplicate$ #1 #1 substring$ is.a.digit }
    {#2 global.max$ substring$}
  while$
  "" =
}

FUNCTION {extract.num}
{ duplicate$ 't :=
  "" 's :=
  { t empty$ not }
  { t #1 #1 substring$
    t #2 global.max$ substring$ 't :=
    duplicate$ is.a.number
      { s swap$ * 's := }
      { pop$ "" 't := }
    if$
  }
  while$
  s empty$
    'skip$
    { pop$ s }
  if$
}

FUNCTION {chr.to.value}
{ chr.to.int$ #48 -
  duplicate$ duplicate$
  #0 < swap$ #9 > or
    { #48 + int.to.chr$
      " is not a number..." *
      warning$
     pop$ #0
    }
    {}
  if$
}

%% Some tricks from "Tame the BeaST" to convert a string
%% to a number
INTEGERS { a b }

FUNCTION {mult}
{ 'a :=
  'b :=
  b #0 <
    {#-1 #0 b - 'b :=}
    {#1}
  if$
  #0
  {b #0 >}
    { a +
      b #1 - 'b :=
    }
  while$
  swap$
    'skip$
    {#0 swap$ -}
    if$
}

FUNCTION {str.to.int.aux}
{ {duplicate$ empty$ not}
    { swap$ #10 mult 'a :=
      duplicate$ #1 #1 substring$
      chr.to.value a +
      swap$
     #2 global.max$ substring$
    }
  while$
  pop$
}

FUNCTION {str.to.int}
{ duplicate$ #1 #1 substring$ "-" =
    {#1 swap$ #2 global.max$ substring$}
    {#0 swap$}
  if$
  #0 swap$ str.to.int.aux
  swap$
    {#0 swap$ -}
    {}
  if$
}

FUNCTION {bibinfo.check}
{ swap$
  duplicate$ missing$
    { pop$ pop$
      ""
    }
    { duplicate$ empty$
        {
          swap$ pop$
        }
        { swap$
          pop$
        }
      if$
    }
  if$
}

FUNCTION {convert.edition}
{ extract.num "l" change.case$ 's :=
  s "first" = s "1" = or
    { bbl.first 't := }
    { s "second" = s "2" = or
        { bbl.second 't := }
        { s "third" = s "3" = or
            { bbl.third 't := }
            { s "fourth" = s "4" = or
                { bbl.fourth 't := }
                { s "fifth" = s "5" = or
                    { bbl.fifth 't := }
                    { s #1 #1 substring$ is.a.number
                        { s eng.ord 't := }
                        { edition 't := }
                      if$
                    }
                  if$
                }
              if$
            }
          if$
        }
      if$
    }
  if$
  t
}

FUNCTION {tie.or.space.connect}
{ duplicate$ text.length$ #3 <
    { "~" }
    { " " }
  if$
  swap$ * *
}

FUNCTION {space.connect}
{ " " swap$ * * }

INTEGERS {
  current.name
  remaining.names
  total.names
}

STRINGS {
  formatted.name
  names.list
}

FUNCTION { format.names.loop } {
  { remaining.names #0 > } {
    names.list current.name "{vv~}{ll,}{~f.}{,~jj}" format.name$
      'formatted.name :=
    current.name #1 > {
      formatted.name "others," = {
        bbl.etal space.connect
        remaining.names #1 'remaining.names :=
      }{
        add.comma.or.semicolon formatted.name *
      }
      if$
    }{
      formatted.name
    }
    if$
    current.name #1 + 'current.name :=
    remaining.names #1 - 'remaining.names :=
  }
  while$
}

FUNCTION { format.names.all } {
  total.names 'remaining.names :=
  format.names.loop
}

FUNCTION { format.names.etal } {
  etal.truncate #1 = {
    etal.number 'remaining.names :=
  }{
    #1 'remaining.names :=
  }
  if$
  format.names.loop
  formatted.name "others," = {
  }{
    bbl.etal space.connect
  }
  if$
}

FUNCTION { format.names } {
  'names.list :=
  #1 'current.name :=
  names.list num.names$ 'total.names :=
  etal.number #0 = {
    format.names.all
  }{
    total.names etal.number > {
      format.names.etal
    }{
      format.names.all
    }
    if$
  }
  if$
}

INTEGERS { author.or.editor }

FUNCTION {format.authors}
{ author empty$
    { "" }
    { #1 'author.or.editor :=
        #1 'separate.by.semicolon :=
      author format.names
    }
  if$
}

FUNCTION {format.editors}
{ editor empty$
    { "" }
    { #2 'author.or.editor :=
        #0 'separate.by.semicolon :=
      editor format.names
      add.comma
      editor num.names$ #1 >
        { bbl.editors }
        { bbl.editor }
      if$
      *
    }
  if$
}

INTEGERS { nameptr numnames namesleft }

FUNCTION {n.separate.multi}
{ 't :=
  ""
  #0 'numnames :=
  t text.length$ #4 > t is.a.number and
    {
      { t empty$ not }
      { t #-1 #1 substring$ is.a.number
          { numnames #1 + 'numnames := }
          { #0 'numnames := }
        if$
        t #-1 #1 substring$ swap$ *
        t #-2 global.max$ substring$ 't :=
        numnames #4 =
          { duplicate$ #1 #1 substring$ swap$
            #2 global.max$ substring$
            "," swap$ * *
            #1 'numnames :=
          }
          'skip$
        if$
      }
      while$
    }
    { t swap$ * }
  if$
}

FUNCTION {format.bvolume}
{ volume empty$
    { "" }
    { bbl.volume volume tie.or.space.connect }
  if$
}

FUNCTION {format.title.noemph}
{ 't :=
  t empty$
    { "" }
    { t }
  if$
}

FUNCTION {format.title}
{ 't :=
  t empty$
    { "" }
    { t emphasize }
  if$
}

%% The add.title function only does anything if the appropriate
%% flag is set.
FUNCTION {add.title}
{ is.use.title
    { title format.title.noemph "title" output.check
      new.sentence }
    'skip$
  if$
}

FUNCTION {format.number.series}
{ volume empty$
    { number empty$
       { series field.or.null }
       { series empty$
         { "There is a number but no series in " cite$ * warning$ }
         { series number space.connect }
       if$
       }
      if$
    }
    { "" }
  if$
}

FUNCTION {format.url}
{ url empty$
    { "" }
    { new.sentence "\url{" url * "}" * }
  if$
}

FUNCTION {format.full.names}
{'s :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
    { namesleft #0 > }
    { s nameptr
      "{vv~}{ll}" format.name$ 't :=
      nameptr #1 >
        {
          namesleft #1 >
            { ", " * t * }
            {
              numnames #2 >
                { "," * }
                'skip$
              if$
              t "others" =
                { bbl.etal * }
                { bbl.and space.connect t space.connect }
              if$
            }
          if$
        }
        't
      if$
      nameptr #1 + 'nameptr :=
      namesleft #1 - 'namesleft :=
    }
  while$
}

FUNCTION {author.editor.full}
{ author empty$
    { editor empty$
        { "" }
        { editor format.full.names }
      if$
    }
    { author format.full.names }
  if$
}

FUNCTION {author.full}
{ author empty$
    { "" }
    { author format.full.names }
  if$
}

FUNCTION {editor.full}
{ editor empty$
    { "" }
    { editor format.full.names }
  if$
}

FUNCTION {make.full.names}
{ type$ "book" =
  type$ "inbook" =
  or
    'author.editor.full
    { type$ "proceedings" =
        'editor.full
        'author.full
      if$
    }
  if$
}

FUNCTION {output.bibitem}
{ newline$
  "\bibitem[" write$
  label write$
  ")" make.full.names duplicate$ short.list =
     { pop$ }
     { * }
   if$
  "]{" * write$
  cite$ write$
  "}" write$
  newline$
  ""
  before.all 'output.state :=
}

FUNCTION {n.dashify}
{ 't :=
  ""
    { t empty$ not }
    { t #1 #1 substring$ "-" =
    { t #1 #2 substring$ "--" = not
        { "--" *
          t #2 global.max$ substring$ 't :=
        }
        {   { t #1 #1 substring$ "-" = }
        { "-" *
          t #2 global.max$ substring$ 't :=
        }
          while$
        }
      if$
    }
    { t #1 #1 substring$ *
      t #2 global.max$ substring$ 't :=
    }
      if$
    }
  while$
}

FUNCTION {format.date}
{ year empty$
    { "" }
    { year boldface }
  if$
}


FUNCTION {format.bdate}
{ year empty$
    { "There's no year in " cite$ * warning$ }
    'year
  if$
}

FUNCTION {either.or.check}
{ empty$
    'pop$
    { "Can't use both " swap$ * " fields in " * cite$ * warning$ }
  if$
}

FUNCTION {format.edition}
{ edition duplicate$ empty$
    'skip$
    { convert.edition
      bbl.edition bibinfo.check
      " " * bbl.edition *
    }
  if$
}

INTEGERS { multiresult }

FUNCTION {multi.page.check}
{ 't :=
  #0 'multiresult :=
    { multiresult not
      t empty$ not
      and
    }
    { t #1 #1 substring$
      duplicate$ "-" =
      swap$ duplicate$ "," =
      swap$ "+" =
      or or
        { #1 'multiresult := }
        { t #2 global.max$ substring$ 't := }
      if$
    }
  while$
  multiresult
}

FUNCTION {format.pages}
{ pages empty$
    { "" }
    { pages multi.page.check
      { bbl.pages pages n.dashify tie.or.space.connect }
      { bbl.page pages tie.or.space.connect }
    if$
    }
  if$
}

FUNCTION {format.pages.required}
{ pages empty$
    { ""
      "There are no page numbers for " cite$ * warning$
      output
    }
    { pages multi.page.check
      { bbl.pages pages n.dashify tie.or.space.connect }
      { bbl.page pages tie.or.space.connect }
    if$
    }
  if$
}

FUNCTION {format.pages.nopp}
{ pages empty$
    { ""
      "There are no page numbers for " cite$ * warning$
      output
    }
    { pages multi.page.check
      { pages n.dashify space.connect }
      { pages space.connect }
    if$
    }
  if$
}

FUNCTION {format.pages.patent}
{ pages empty$
    { "There is no patent number for " cite$ * warning$ }
    { pages multi.page.check
      { pages n.dashify }
      { pages n.separate.multi }
      if$
    }
  if$
}

FUNCTION {format.vol.pages}
{ volume emphasize field.or.null
  duplicate$ empty$
    { pop$ format.pages.required }
    { add.comma pages n.dashify * }
  if$
}

FUNCTION {format.chapter.pages}
{ chapter empty$
    'format.pages
    { type empty$
    { bbl.chapter }
    { type "l" change.case$ }
      if$
      chapter tie.or.space.connect
      pages empty$
    'skip$
    { add.comma format.pages * }
      if$
    }
  if$
}

FUNCTION {format.title.in}
{ 's :=
  s empty$
    { "" }
    { editor empty$
      { bbl.in s format.title space.connect }
      { bbl.in s format.title space.connect
        add.semicolon format.editors *
      }
    if$
    }
  if$
}

FUNCTION {format.pub.address}
{ publisher empty$
    { "" }
    { address empty$
        { publisher }
        { publisher add.colon address *}
      if$
    }
  if$
}

FUNCTION {format.school.address}
{ school empty$
    { "" }
    { address empty$
        { school }
        { school add.colon address *}
      if$
    }
  if$
}

FUNCTION {format.organization.address}
{ organization empty$
    { "" }
    { address empty$
        { organization }
        { organization add.colon address *}
      if$
    }
  if$
}

FUNCTION {format.version}
{ edition empty$
    { "" }
    { bbl.version edition tie.or.space.connect }
  if$
}

FUNCTION {empty.misc.check}
{ author empty$ title empty$ howpublished empty$
  year empty$ note empty$ url empty$
  and and and and and
    { "all relevant fields are empty in " cite$ * warning$ }
    'skip$
  if$
}

FUNCTION {empty.doi.note}
{ doi empty$ note empty$ and
    { "Need either a note or DOI for " cite$ * warning$ }
    'skip$
  if$
}

FUNCTION {format.thesis.type}
{ type empty$
    'skip$
    { pop$
      type emphasize
    }
  if$
}

FUNCTION {article}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  add.title
  journal emphasize "journal" output.check
  after.item 'output.state :=
  format.date "year" output.check
  volume empty$
    { ""
      format.pages.nopp output
    }
    { format.vol.pages output }
  if$
  note output
  fin.entry
}

FUNCTION {book}
{ output.bibitem
  author empty$
    { booktitle empty$
        { title format.title "title" output.check }
        { booktitle format.title "booktitle" output.check }
      if$
      format.edition output
      new.block
      editor empty$
        { "Need either an author or editor for " cite$ * warning$ }
        { "" format.editors * "editor" output.check }
      if$
    }
    { format.authors output
      after.item 'output.state :=
      "author and editor" editor either.or.check
      booktitle empty$
        { title format.title "title" output.check }
        { booktitle format.title "booktitle" output.check }
      if$
      format.edition output
    }
  if$
  new.block
  format.number.series output
  new.block
  format.pub.address "publisher" output.check
  format.bdate "year" output.check
  new.block
  format.bvolume output
  pages empty$
    'skip$
    { format.pages output }
  if$
  note output
  fin.entry
}

FUNCTION {booklet}
{ output.bibitem
  format.authors output
  after.item 'output.state :=
  title format.title "title" output.check
  howpublished output
  address output
  format.date output
  note output
  fin.entry
}

FUNCTION {inbook}
{ output.bibitem
  author empty$
    { title format.title "title" output.check
      format.edition output
      new.block
      editor empty$
      { "Need at least an author or an editor for " cite$ * warning$ }
      { "" format.editors * "editor" output.check }
    if$
    }
    { format.authors output
      after.item 'output.state :=
      title format.title.in "title" output.check
      format.edition output
    }
  if$
  new.block
  format.number.series output
  new.block
  format.pub.address "publisher" output.check
  format.bdate "year" output.check
  new.block
  format.bvolume output
  format.chapter.pages "chapter and pages" output.check
  note output
  fin.entry
}

FUNCTION {incollection}
{ output.bibitem
  author empty$
    { booktitle format.title "booktitle" output.check
      format.edition output
      new.block
      editor empty$
        { "Need at least an author or an editor for " cite$ * warning$ }
        { "" format.editors * "editor" output.check }
      if$
    }
    { format.authors output
      after.item 'output.state :=
      title empty$
        'skip$
        { title format.title.noemph output }
      if$
      after.sentence 'output.state :=
      booktitle format.title.in "booktitle" output.check
      format.edition output
    }
  if$
  new.block
  format.number.series output
  new.block
  format.pub.address "publisher" output.check
  format.bdate "year" output.check
  new.block
  format.bvolume output
  format.chapter.pages "chapter and pages" output.check
  note output
  fin.entry
}

FUNCTION {inpress}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  journal emphasize "journal" output.check
  doi empty$
    {  bbl.inpress output }
    {  after.item 'output.state :=
       format.date output
       "DOI:" doi tie.or.space.connect output
    }
  if$
  note output
  fin.entry
}

FUNCTION {inproceedings}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  title empty$
    'skip$
    { title format.title.noemph output
      after.sentence 'output.state :=
    }
  if$
  booktitle format.title output
  address output
  format.bdate "year" output.check
  pages empty$
    'skip$
    { new.block
      format.pages output }
  if$
  note output
  fin.entry
}

FUNCTION {manual}
{ output.bibitem
  format.authors output
  after.item 'output.state :=
  title format.title "title" output.check
  format.version output
  new.block
  format.organization.address output
  format.bdate output
  note output
  fin.entry
}

FUNCTION {mastersthesis}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  bbl.msc format.thesis.type output
  format.school.address "school" output.check
  format.bdate "year" output.check
  note output
  fin.entry
}

FUNCTION {misc}
{ output.bibitem
  format.authors output
  after.item 'output.state :=
  title empty$
    'skip$
    { title format.title output }
  if$
  howpublished output
  year output
  format.url output
  note output
  fin.entry
  empty.misc.check
}

FUNCTION {patent}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  journal "journal" output.check
  after.item 'output.state :=
  format.pages.patent "pages" output.check
  format.bdate "year" output.check
  note output
  fin.entry
}

FUNCTION {phdthesis}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  bbl.phd format.thesis.type output
  format.school.address "school" output.check
  format.bdate "year" output.check
  note output
  fin.entry
}

FUNCTION {proceedings}
{ output.bibitem
  title format.title.noemph "title" output.check
  address output
  format.bdate "year" output.check
  pages empty$
    'skip$
    { new.block
      format.pages output }
  if$
  note output
  fin.entry
}

FUNCTION {techreport}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  title format.title "title" output.check
  new.block
  type empty$
    'bbl.techreport
    'type
  if$
  number empty$
    'skip$
    { number tie.or.space.connect }
  if$
  output
  format.pub.address output
  format.bdate "year" output.check
  pages empty$
    'skip$
    { new.block
      format.pages output }
  if$
  note output
  fin.entry
}

FUNCTION {unpublished}
{ output.bibitem
  format.authors "author" output.check
  after.item 'output.state :=
  journal empty$
    'skip$
    { journal emphasize "journal" output.check }
  if$
  doi empty$
    {  note output }
    {  after.item 'output.state :=
       format.date output
       "DOI:" doi tie.or.space.connect output
    }
  if$
  fin.entry
  empty.doi.note
}

%% Convert the strings "yes" or "no" to #1 or #0 respectively
FUNCTION {yes.no.to.int}
{ "l" change.case$ duplicate$
    "yes" =
    { pop$  #1 }
    { duplicate$ "no" =
        { pop$ #0 }
        { "unknown Boolean " quote$ * swap$ * quote$ *
          " in " * cite$ * warning$
          #0
        }
      if$
    }
  if$
}

%% Using the same mechanism as in IEEEtrans, control of
%% output can be achieved using a special entry type.
FUNCTION { Control } {
  ctrl-use-title
  empty$ {
    skip$
  }{
    ctrl-use-title
    yes.no.to.int
    'is.use.title :=
  }
  if$
  ctrl-etal-number
  empty$ {
    skip$
  }{
    ctrl-etal-number
    str.to.int
    'etal.number :=
  }
  if$
  ctrl-etal-truncate
  empty$ {
    skip$
  }{
    ctrl-etal-truncate
    yes.no.to.int
    'etal.truncate :=
  }
  if$
}

FUNCTION {conference} {inproceedings}

FUNCTION {other} {patent}

FUNCTION {default.type} {misc}

MACRO {jan} {"Jan."}
MACRO {feb} {"Feb."}
MACRO {mar} {"Mar."}
MACRO {apr} {"Apr."}
MACRO {may} {"May"}
MACRO {jun} {"June"}
MACRO {jul} {"July"}
MACRO {aug} {"Aug."}
MACRO {sep} {"Sept."}
MACRO {oct} {"Oct."}
MACRO {nov} {"Nov."}
MACRO {dec} {"Dec."}

%% The ACS journals by CODEN
MACRO {achre4} {"Acc.\ Chem.\ Res."}
MACRO {acbcct} {"ACS Chem.\ Biol."}
MACRO {ancac3} {"ACS Nano"}
MACRO {ancham} {"Anal.\ Chem."}
MACRO {bichaw} {"Biochemistry"}
MACRO {bcches} {"Bioconjugate Chem."}
MACRO {bomaf6} {"Biomacromolecules"}
MACRO {bipret} {"Biotechnol.\ Prog."}
MACRO {crtoec} {"Chem.\ Res.\ Toxicol."}
MACRO {chreay} {"Chem.\ Rev."}
MACRO {cmatex} {"Chem.\ Mater."}
MACRO {cgdefu} {"Cryst.\ Growth Des."}
MACRO {enfuem} {"Energy Fuels"}
MACRO {esthag} {"Environ.\ Sci.\ Technol."}
MACRO {iechad} {"Ind.\ Eng.\ Chem.\ Res."}
MACRO {inoraj} {"Inorg.\ Chem."}
MACRO {jafcau} {"J.~Agric.\ Food Chem."}
MACRO {jceaax} {"J.~Chem.\ Eng.\ Data"}
MACRO {jcisd8} {"J.~Chem.\ Inf.\ Model."}
MACRO {jctcce} {"J.~Chem.\ Theory Comput."}
MACRO {jcchff} {"J. Comb. Chem."}
MACRO {jmcmar} {"J. Med. Chem."}
MACRO {jnprdf} {"J. Nat. Prod."}
MACRO {joceah} {"J.~Org.\ Chem."}
MACRO {jpcafh} {"J.~Phys.\ Chem.~A"}
MACRO {jpcbfk} {"J.~Phys.\ Chem.~B"}
MACRO {jpccck} {"J.~Phys.\ Chem.~C"}
MACRO {jprobs} {"J.~Proteome Res."}
MACRO {jacsat} {"J.~Am.\ Chem.\ Soc."}
MACRO {langd5} {"Langmuir"}
MACRO {mamobx} {"Macromolecules"}
MACRO {mpohbp} {"Mol.\ Pharm."}
MACRO {nalefd} {"Nano Lett."}
MACRO {orlef7} {"Org.\ Lett."}
MACRO {oprdfk} {"Org.\ Proc.\ Res.\ Dev."}
MACRO {orgnd7} {"Organometallics"}

READ

FUNCTION {initialize.controls}
{ default.is.use.title 'is.use.title :=
  default.etal.number 'etal.number :=
}

EXECUTE {initialize.controls}

INTEGERS { len }

FUNCTION {chop.word}
{ 's :=
  'len :=
  s #1 len substring$ =
    { s len #1 + global.max$ substring$ }
    's
  if$
}

FUNCTION {format.lab.names}
{ 's :=
  s #1 "{vv~}{ll}" format.name$
  s num.names$ duplicate$
  #2 >
    { pop$ bbl.etal space.connect }
    { #2 <
        'skip$
        { s #2 "{ff }{vv }{ll}{ jj}" format.name$ "others" =
            { bbl.etal space.connect }
            { bbl.and space.connect s #2 "{vv~}{ll}" format.name$ space.connect }
          if$
        }
      if$
    }
  if$
}

FUNCTION {author.key.label}
{ author empty$
    { key empty$
        { cite$ #1 #3 substring$ }
        'key
      if$
    }
    { author format.lab.names }
  if$
}

FUNCTION {author.editor.key.label}
{ author empty$
    { editor empty$
        { key empty$
            { cite$ #1 #3 substring$ }
            'key
          if$
        }
        { editor format.lab.names }
      if$
    }
    { author format.lab.names }
  if$
}

FUNCTION {author.key.organization.label}
{ author empty$
    { key empty$
        { organization empty$
            { cite$ #1 #3 substring$ }
            { "The " #4 organization chop.word #3 text.prefix$ }
          if$
        }
        'key
      if$
    }
    { author format.lab.names }
  if$
}

FUNCTION {editor.key.organization.label}
{ editor empty$
    { key empty$
        { organization empty$
            { cite$ #1 #3 substring$ }
            { "The " #4 organization chop.word #3 text.prefix$ }
          if$
        }
        'key
      if$
    }
    { editor format.lab.names }
  if$
}

FUNCTION {calc.short.authors}
{ type$ "book" =
  type$ "inbook" =
  or
    'author.editor.key.label
    { type$ "proceedings" =
        'editor.key.organization.label
        { type$ "manual" =
            'author.key.organization.label
            'author.key.label
          if$
        }
      if$
    }
  if$
  'short.list :=
}

FUNCTION { calc.label } {
  calc.short.authors
  short.list
  "("
  *
  year duplicate$ empty$
  short.list key field.or.null = or
     { pop$ "" }
     'skip$
  if$
  *
  'label :=
}

ITERATE { calc.label }

STRINGS {
  longest.label
  last.label
  next.extra
}

INTEGERS {
  longest.label.width
  last.extra.num
  number.label
}

FUNCTION { initialize.longest.label } {
  "" 'longest.label :=
  #0 int.to.chr$ 'last.label :=
  "" 'next.extra :=
  #0 'longest.label.width :=
  #0 'last.extra.num :=
  #0 'number.label :=
}

FUNCTION {forward.pass}
{ last.label label =
    { last.extra.num #1 + 'last.extra.num :=
      last.extra.num int.to.chr$ 'extra.label :=
    }
    { "a" chr.to.int$ 'last.extra.num :=
      "" 'extra.label :=
      label 'last.label :=
    }
  if$
  number.label #1 + 'number.label :=
}

EXECUTE { initialize.longest.label }

ITERATE { forward.pass }

FUNCTION { begin.bib } {
  preamble$ empty$ {
    skip$
  }{
    preamble$ write$ newline$
  }
  if$
  "\providecommand*{\mcitethebibliography}{\thebibliography}"
  write$ newline$
  "\csname @ifundefined\endcsname{endmcitethebibliography}"
  write$ newline$
  "{\let\endmcitethebibliography\endthebibliography}{}"
  write$ newline$
  "\begin{mcitethebibliography}{"  number.label int.to.str$  * "}" *
  write$ newline$
  "\providecommand*{\natexlab}[1]{#1}"
  write$ newline$
  "\providecommand*{\mciteSetBstSublistMode}[1]{}"
  write$ newline$
  "\providecommand*{\mciteSetBstMaxWidthForm}[2]{}"
  write$ newline$
  "\providecommand*{\mciteBstWouldAddEndPuncttrue}"
  write$ newline$
  "  {\def\EndOfBibitem{\unskip.}}"
  write$ newline$
  "\providecommand*{\mciteBstWouldAddEndPunctfalse}"
  write$ newline$
  "  {\let\EndOfBibitem\relax}"
  write$ newline$
  "\providecommand*{\mciteSetBstMidEndSepPunct}[3]{}"
  write$ newline$
  "\providecommand*{\mciteSetBstSublistLabelBeginEnd}[3]{}"
  write$ newline$
  "\providecommand*{\EndOfBibitem}{}"
  write$ newline$
  "\mciteSetBstSublistMode{f}"
  write$ newline$
  "\mciteSetBstMaxWidthForm{subitem}{(\alph{mcitesubitemcount})}"
  write$ newline$
  "\mciteSetBstSublistLabelBeginEnd{\mcitemaxwidthsubitemform\space}"
  write$ newline$
  "{\relax}{\relax}"
  write$ newline$
}

EXECUTE { begin.bib }

EXECUTE { init.state.consts }

EXECUTE { init.separate.by.semicolon }

ITERATE { call.type$ }

FUNCTION { end.bib } {
  newline$
  "\end{mcitethebibliography}" write$ newline$
}

EXECUTE { end.bib }
%% 
%% Originally developed by Mats Dahlgren
%% Copyright (C) 1996-1998 by Mats Dahlgren
%% Copyright (C) 2008-2010 by
%%   Joseph Wright <joseph.wright@morningstar2.co.uk>
%% 
%% Part of this bundle is derived from cite.sty, to which the
%% following license applies:
%%   Copyright (C) 1989-2009 by Donald Arseneau
%%   These macros may be freely transmitted, reproduced, or
%%   modified provided that this notice is left intact.
%% 
%% It may be distributed and/or modified under the conditions of
%% the LaTeX Project Public License (LPPL), either version 1.3c of
%% this license or (at your option) any later version.  The latest
%% version of this license is in the file:
%% 
%%    http://www.latex-project.org/lppl.txt
%% 
%% This work is "maintained" (as per LPPL maintenance status) by
%%   Joseph Wright.
%% 
%% This work consists of the file  achemso.dtx
%%           and the derived files achemso.cls,
%%                                 achemso.ins,
%%                                 achemso.pdf,
%%                                 achemso.sty,
%%                                 achemso-demo.bib,
%%                                 achemso-demo.tex,
%%                                 natmove.sty and
%%                                 a number of configuration files.
%% 
%%
%% End of file `achemso.bst'.
