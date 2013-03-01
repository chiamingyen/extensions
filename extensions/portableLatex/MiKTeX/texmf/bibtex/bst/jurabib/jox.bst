%%
%% This is file `jox.bst',
%% generated with the docstrip utility.
%%
%% The original source files were:
%%
%% jurabib.dtx  (with options: `joxbst')
%% ----------------------------------------
%% BibTeX style for use with the jurabib package version v0.61beta
%% Copyright (C) 1999-2006 Jens Berger (http://www.jurabib.org)
%% See jurabib.ins  for the copyright details.
%% 
ENTRY
  { address
    author
    booktitle
    chapter
    edition
    editor
    howpublished
    institution
    journal
    key
    month
    note
    number
    organization
    pages
    publisher
    school
    series
    stitle
    title
    type
    volume
    year
%%
    pagestype
    url
    urldate
    volumetitle
    juratitle
    shorttitle
    juraauthor
    shortauthor
    howcited
    ssedition
    language
    titleaddon
    booktitleaddon
    editortype
    sortkey
    annote
    gender
    translator
    flanguage
    oyear
    oaddress
    opublisher
    pseudonym
    volformat
    noed
  }
  { repeat-authors }
  { label dummy.label extra.label sort.label short.list current-authors }

INTEGERS { output.state before.all mid.sentence }

FUNCTION {init.state.consts}
{ #0 'before.all :=
  #1 'mid.sentence :=
}

STRINGS { s t u v w x }

FUNCTION {output.nonnull}
{ 's :=
  output.state mid.sentence =
    { ", " * write$ }
    { write$ mid.sentence 'output.state := }
  if$
  s
}

FUNCTION {output}
{ duplicate$ empty$
    'pop$
    'output.nonnull
  if$
}

FUNCTION {output.check}
{ 't :=
  duplicate$ empty$
     { pop$ "empty " t * " in " * cite$ * warning$ }
     'output.nonnull
  if$
}

FUNCTION {format.endnote}
{ note empty$
      { "" }
      { "  \jbendnote {" note * "}" * }
  if$
}

FUNCTION {format.howcited}{ before.all 'output.state := "} {\bibhowcited}" format.endnote * * }

INTEGERS { nameptr namesleft numnames }

FUNCTION {format.name.sig}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { s nameptr "{ff}{ vv}{ ll}{ jj}" format.name$ 't :=
      nameptr #1 >
        { namesleft #1 >
            { "sssss" * t * }
            {  t "others" =
                { " et al." * }
                { "" * t * }
              if$
            }
          if$
        }
        't
      if$
    nameptr #1 + 'nameptr :=
    namesleft #1 -  'namesleft :=
  }
  while$
}

FUNCTION {format.snd.editors}
{ editor empty$
   { "" * }
   { author empty$
        { editortype empty$
             { editor num.names$ #1 >
                 { "\snded {\sndeditorsname}" * }
                 { "\snded {\sndeditorname}" * }
               if$
             }
             { "\snded {(" editortype * ")}" * * }
          if$
        }
        'skip$
    if$
   }
  if$
}

FUNCTION {count.authors.or.editors}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { "" 't :=
      nameptr #1 >
        { namesleft #1 >
            { "" * }
            { "s" * } % more than one author/editor, append `s'
          if$
        }
        't
      if$
    nameptr #1 + 'nameptr :=
    namesleft #1 - 'namesleft :=
  }
  while$
}

FUNCTION {set.after.author.sep}
{ before.all 'output.state :=
  author missing$
     { editor missing$
          { organization missing$
                 { "} {empty} {} {" }
                 { "} {" organization "} {org} {" * * }
            if$
          }
          { "} {" editor format.name.sig * "} {ed" editor count.authors.or.editors "} {" * * * }
      if$
     }
     { "} {" author format.name.sig * "} {au" author count.authors.or.editors "} {" * * * }
  if$
  gender empty$
     { "sexless} {" * }
     { gender "} {" * * }
  if$
  author empty$
      { format.snd.editors }
      'skip$
  if$
  "" *
}

FUNCTION {format.note}
{ note empty$
      'skip$
      { output.state before.all =
          { "\jbnote {}{" * note * "}" * }
          { " \jbnote {1}{" * note * "}" * }
       if$
      }
  if$
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

FUNCTION {non.stop}
{ duplicate$
   "}" *
   #-1 #1 substring$ "." =
}

FUNCTION {field.or.null}
{ duplicate$ empty$
    { pop$ "" }
    'skip$
  if$
}

FUNCTION {capitalize}
{ "u" change.case$ "t" change.case$ }

FUNCTION {space.word}
{ " " swap$ * " " * }

FUNCTION {bbl.edition}
{ "\editionname {} " }

FUNCTION {bbl.vol}
{ "\volname {}" }

FUNCTION {bbl.volume}
{ "\volumename {}" }

FUNCTION {bbl.of}
{ " \volumeofname {} " }

FUNCTION {bbl.number}
{ "\numbername" }

FUNCTION {bbl.in}
{ "\bibBTsep {}" }

FUNCTION {bbl.in.series}
{ "\inseriesname {}" }

FUNCTION {bbl.chapter}
{ "\bibchaptername {}" }

FUNCTION {bbl.techrep}
{ "\technicalreportname {}" }

FUNCTION {bbl.mthesis}
{ "\mastersthesisname {}" }

FUNCTION {bbl.phdthesis}
{ "\phdthesisname {}"}

MACRO {jan} {"\janname"}
MACRO {feb} {"\febname"}
MACRO {mar} {"\marname"}
MACRO {apr} {"\aprname"}
MACRO {may} {"\mayname"}
MACRO {jun} {"\junname"}
MACRO {jul} {"\julname"}
MACRO {aug} {"\augname"}
MACRO {sep} {"\sepname"}
MACRO {oct} {"\octname"}
MACRO {nov} {"\novname"}
MACRO {dec} {"\decname"}

FUNCTION {checked.emph.format}
{ type$ "book" = type$ "inbook" = type$ "proceedings" = type$ "inproceedings" = or or or
      { "\bibtfont {" }
      { "\bibapifont {"  }
  if$
}

FUNCTION {emphasize}
{ duplicate$ empty$
    { pop$ "" }
    { checked.emph.format swap$ * "}" * }
  if$
}

FUNCTION {b.emphasize}
{ duplicate$ empty$
    { pop$ "" }
    { "\bibbtfont {" swap$ * "}" * }
  if$
}

FUNCTION {write.url}
{ url empty$
      { urldate empty$
            'skip$
            { "there's an urldate, but no url in " cite$ * warning$ }
        if$
      }
      { type$ "www" =
           'skip$
           { ", " * }
        if$
        title empty$ output.state before.all = and
            { "" * mid.sentence 'output.state := }
            { " " * }
        if$
        type$ "www" =
            { year empty$
                  'skip$
                  { year " " * * }
              if$
            }
            'skip$
        if$
        urldate empty$
                { "\jburluse {" cite$ * "}" * * }
                { "\jburluse {" cite$ * "}\bibbudcsep {}\urldatecomment " * urldate * * }
        if$
      }
  if$
}

FUNCTION {lastnamemarkup}
{ duplicate$ empty$
     { pop$ "" }
     { "\bibnf {" swap$ * "}" * }
  if$
}

FUNCTION {editorlastnamemarkup}
{ duplicate$ empty$
     { pop$ "" }
     { author empty$
         { "\biblenf {" swap$ * "}" * }
         { "\bibenf {" swap$ * "}" * }
       if$
     }
  if$
}

FUNCTION {translatorlastnamemarkup}
{ duplicate$ empty$
     { pop$ "" }
     { "\bibtnf {" swap$ * "}" * }
  if$
}

FUNCTION {othermarkup}
{ duplicate$ empty$
       { pop$ " {}" }
       { " {" swap$ * "}" * }
  if$
}

FUNCTION {indexmarkup}
{ duplicate$ empty$
       { pop$ "{}" }
       { "{" swap$ * "}" * }
  if$
}

FUNCTION {format.names.for.index}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { s nameptr "{ll}" format.name$ 't := t indexmarkup 't :=
    s nameptr "{ff}" format.name$ indexmarkup 'u :=
    s nameptr "{f.}" format.name$ indexmarkup 'v :=
    s nameptr "{vv}" format.name$ indexmarkup 'w :=
    s nameptr "{jj}" format.name$ indexmarkup 'x :=
    t u * v * w * x * 't :=
    t "{others}{}{}{}{}" =
      'skip$
      { nameptr #1 >
          { namesleft #1 >
               { "; " * t * }
               {
                 t "others" =
                    'skip$
                    { "; " * t * }
                 if$
                }
            if$
          }
          't
       if$
      }
    if$
    nameptr #1 + 'nameptr :=
    namesleft #1 -  'namesleft :=
  }
  while$
}

FUNCTION {format.names}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { s nameptr "{ll}" format.name$ 't := t lastnamemarkup 't :=
    s nameptr "{ff}" format.name$ othermarkup 'u :=
    s nameptr "{f.}" format.name$ othermarkup 'v :=
    s nameptr "{vv}" format.name$ othermarkup 'w :=
    s nameptr "{jj}" format.name$ othermarkup 'x :=
    t u * v * w * x * 't :=
     nameptr #1 >
      { namesleft #1 >
%%% change '#3' for other et al. behavior
          { numnames #3 >
                { "" * }
                { numnames #3 <
                     { "\Bibbtasep " * t * }
                     { "\Bibbfsasep " * t * }
                  if$
                }
            if$
          }
          { t "\bibnf {others} {} {} {} {}" =
%%% change '#3' for other et al. behavior
            numnames #3 > or
               { "\bibimfont {\Bibetal}" * }
               { numnames #3 <
                   { "\Bibbtasep " * t * }
                   { "\Bibbstasep " * t * }
                 if$
               }
            if$
          }
        if$
      }
     't
    if$
    nameptr #1 + 'nameptr :=
    namesleft #1 -  'namesleft :=
  }
  while$
}

FUNCTION {format.editor.names}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { s nameptr "{ll}" format.name$ 't := t editorlastnamemarkup 't :=
    s nameptr "{ff}" format.name$ othermarkup 'u :=
    s nameptr "{f.}" format.name$ othermarkup 'v :=
    s nameptr "{vv}" format.name$ othermarkup 'w :=
    s nameptr "{jj}" format.name$ othermarkup 'x :=
    t u * v * w * x * 't :=
     nameptr #1 >
      { namesleft #1 >
%%% change '#3' for other et al. behavior
          { numnames #3 >
                { "" * }
                { numnames #3 <
                     { "\Bibbtasep " * t * }
                     { "\Bibbfsasep " * t * }
                  if$
                }
            if$
          }
          { t "\bibenf {others} {} {} {} {}" =
            t "\biblenf {others} {} {} {} {}" = or
            t "\bibenf {others} {} {} {} {} \editorsname" = or
            t "\biblenf {others} {} {} {} {} \editorsname" = or
%%% change '#3' for other et al. behavior
            numnames #3 > or
               { "\bibimfont {\Bibetal}" * }
               { numnames #3 <
                   { "\Bibbtasep " * t * }
                   { "\Bibbstasep " * t * }
                 if$
               }
            if$
          }
        if$
      }
     't
    if$
    nameptr #1 + 'nameptr :=
    namesleft #1 -  'namesleft :=
  }
  while$
}

FUNCTION {format.translator.names}
{ 's :=
  #1 'nameptr :=
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { s nameptr "{ll}" format.name$ 't := t translatorlastnamemarkup 't :=
    s nameptr "{ff}" format.name$ othermarkup 'u :=
    s nameptr "{f.}" format.name$ othermarkup 'v :=
    s nameptr "{vv}" format.name$ othermarkup 'w :=
    s nameptr "{jj}" format.name$ othermarkup 'x :=
    t u * v * w * x * 't :=
     nameptr #1 >
      { namesleft #1 >
%%% change '#3' for other et al. behavior
          { numnames #3 >
                { "" * }
                { numnames #3 <
                     { "\Bibbtasep " * t * }
                     { "\Bibbfsasep " * t * }
                  if$
                }
            if$
          }
          { t "\bibtnf {others} {} {} {} {}" =
%%% change '#3' for other et al. behavior
            numnames #3 > or
               { "\bibimfont {\Bibetal}" * }
               { numnames #3 <
                   { "\Bibbtasep " * t * }
                   { "\Bibbstasep " * t * }
                 if$
               }
            if$
          }
        if$
      }
     't
    if$
    nameptr #1 + 'nameptr :=
    namesleft #1 -  'namesleft :=
  }
  while$
}

FUNCTION {format.authors}
{ author empty$
    { pseudonym empty$
        { "" }
        { "[" pseudonym format.names * "]" * }
      if$
    }
    { author format.names
      pseudonym empty$
        'skip$
        { " [" * pseudonym format.names * "]" * }
      if$
    }
  if$
}

FUNCTION {format.editors}
{ editor empty$
    { pseudonym empty$
         { "" }
         { "[" pseudonym format.editor.names * "]" * }
      if$
    }
    { editor format.editor.names
      pseudonym empty$
        'skip$
         { " [" * pseudonym format.editor.names * "]" * }
      if$
      noed missing$
          { editor num.names$ #1 >
                 { editortype empty$
                      { "\bibedformat {\editorsname}" * }
                      { "\bibedformat {" * editortype * "}" * }
                   if$
                 }
                 { editortype empty$
                      { "\bibedformat {\editorname}" * }
                      { "\bibedformat {" * editortype * "}" * }
                   if$
                 }
            if$
          }
          { noed 's :=
            s "1" =
                 'skip$
                 { editor num.names$ #1 >
                        { editortype empty$
                             { "\bibedformat {\editorsname}" * }
                             { "\bibedformat {" * editortype * "}" * }
                          if$
                        }
                        { editortype empty$
                             { "\bibedformat {\editorname}" * }
                             { "\bibedformat {" * editortype * "}" * }
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

FUNCTION {format.in.editors}
{ editor empty$
    { pseudonym empty$
        'skip$
          { "[" pseudonym format.editor.names * "]" * }
      if$
    }
    { editor format.editor.names
      pseudonym empty$
        'skip$
         { " [" * pseudonym format.editor.names * "]" * }
      if$
      noed missing$
          { editor num.names$ #1 >
                 { editortype empty$
                      { " \bibedinformat {\editorsname}" * }
                      { " \bibedinformat {" * editortype * "}" * }
                   if$
                 }
                 { editortype empty$
                      { " \bibedinformat {\editorname}" * }
                      { " \bibedinformat {" * editortype * "}" * }
                   if$
                 }
            if$
          }
          { noed 's :=
            s "1" =
                 'skip$
                 { editor num.names$ #1 >
                        { editortype empty$
                             { " \bibedinformat {\editorsname}" * }
                             { " \bibedinformat {" * editortype * "}" * }
                          if$
                        }
                        { editortype empty$
                             { " \bibedinformat {\editorname}" * }
                             { " \bibedinformat {" * editortype * "}" * }
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

FUNCTION {format.editors.last}
{ editor empty$
   { "" before.all 'output.state := }
   { author empty$ not
        { " \edbyname{} " editor format.editor.names * }
        { "" before.all 'output.state := }
    if$
   }
  if$
}

FUNCTION {format.beditors.last}
{ editor empty$
    { "" before.all 'output.state := }
    { " \edbyname{} " editor format.editor.names * }
  if$
}

FUNCTION {fin.entry}
{ format.howcited
  " \jbdoitem {" *
    author empty$
      'skip$
      { author format.names.for.index * }
    if$
    "} {"
    editor empty$
      'skip$
      { editor format.names.for.index * }
    if$
    "} {" *
    organization empty$
      'skip$
      { "{" organization * "}{}{}{}{}" * * }
    if$
    "}" * * write$
  annote missing$
    { " \bibAnnoteFile {" cite$ * "}" * write$ newline$ }
    { " \bibAnnote {" annote * "}" * write$ newline$ }
  if$
}

FUNCTION {format.titleaddon}
{ title empty$
    'skip$
    { titleaddon empty$
         'skip$
         { ", \bibtafont{" * titleaddon "}" * * }
     if$
    }
  if$
}

FUNCTION {format.booktitleaddon}
{ booktitle empty$
    'skip$
    { booktitleaddon empty$
        'skip$
         { ", \bibbtafont{" * booktitleaddon "}" * * }
     if$
    }
  if$
}

FUNCTION {format.title}
{ before.all 'output.state :=
  title empty$
    { "" }
    { title checked.emph.format swap$ * non.stop
       { before.all 'output.state := "}" * }
       { before.all 'output.state := "}" * }
      if$
    }
  if$
}

FUNCTION {journalnamemarkup}
{ duplicate$ empty$
    { pop$ "" }
    { "\bibJTsep \bibjtfont {" swap$ * "}\ajtsep {}" * }
  if$
}

FUNCTION {seriesformat}
{ duplicate$ empty$
    { pop$ "" }
    { "\bibsnfont {" swap$ * "}" * }
  if$
}

FUNCTION {is.num}
{ chr.to.int$
  duplicate$ "0" chr.to.int$ < not
  swap$ "9" chr.to.int$ > not and
}

FUNCTION {extract.num}
{ duplicate$ 't :=
  "" 's :=
  { t empty$ not }
  { t #1 #1 substring$
    t #2 global.max$ substring$ 't :=
    duplicate$ is.num
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

FUNCTION {convert.edition.superscript}
{ edition extract.num "l" change.case$ 's :=
  s "first" = s "1" = or s "erste" = or
    { "1" 't := }
    { s "second" = s "2" = or s "zweite" = or
        { "2" 't := }
        { s "third" = s "3" = or s "dritte" = or
            { "3" 't := }
            { s "fourth" = s "4" = or s "vierte" = or
                { "4" 't := }
                { s "fifth" = s "5" = or s "fuenfte" = or
                    { "5" 't := }
                    { s "sixth" = s "6" = or s "sechste" = or
                        { "6" 't := }
                        { s "seventh" = s "7" = or s "siebte" = or s "siebente" = or
                            { "7" 't := }
                            { s "eighth" = s "8" = or s "achte" = or
                                { "8" 't := }
                                { s "nineth" = s "9" = or s "neunte" = or
                                      { "9" 't := }
                                      {  s #1 #1 substring$ is.num
                                           { s "" * 't := }
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

FUNCTION {output.bibitem.fst}
{ newline$
  "\backrefparscanfalse" write$
  newline$
  "\bibitem[{" write$
  shortauthor empty$
    { juraauthor empty$
       { author empty$
          { editor empty$
              { organization empty$
                   { "empty" write$ }
                   { organization write$ }
                if$
              }
              { label write$ }
            if$
          }
          { label write$ }
         if$
       }
       { juraauthor write$ }
     if$
    }
    { shortauthor write$ }
  if$
  "}%" write$
  newline$
  shorttitle empty$
    { juratitle empty$
        { journal empty$
            { "         {}%" write$ }
            { title empty$
                  { key empty$
                       { "         {}%" write$ }
                       { "         {" key * "}%" * write$ }
                    if$
                  }
                  { "         {" title * "}%" * write$ }
              if$
            }
          if$
        }
        { "         {" juratitle * "}%" * write$ }
      if$
    }
    { "         {" shorttitle * "}%" * write$ }
  if$
  newline$
  repeat-authors
    { "         {{1}" write$ }
    { "         {{0}" write$ }
  if$
  howcited empty$
    { "{}" write$ }
    { "{" howcited * "}" * write$ }
  if$
  type$ empty$
    { "{}" write$ }
    { "{" type$ * "}" * write$ }
  if$
  year empty$
    { "{}" write$ }
    { "{" year * write$
      extra.label "" =
          'skip$
          { "\el {" write$ extra.label write$ "}" write$ }
      if$
      "}" write$
    }
  if$
  edition empty$
    { "{}" write$ }
    { "{" convert.edition.superscript extract.num * "}" * write$ }
  if$
  ssedition empty$
    { "{}" write$ }
    { "{" ssedition * "}" * write$ }
  if$
  language empty$
    { "{}" write$ }
    { "{" language * "}" * write$ }
  if$
  pages empty$
    { "{}%" write$ }
    { "{" write$ pages n.dashify write$ "}%" write$ }
  if$
  newline$
}

FUNCTION {output.bibitem.snd}
{ publisher empty$
    { address empty$
         { year empty$
               { "          {" write$ }
               { "          {" write$
                            year write$
                            extra.label "" =
                              'skip$
                              { "\el {" write$ extra.label write$ "}" write$ }
                            if$
               }
           if$
         }
         { year empty$
               { "          {" write$ address write$ }
               { "          {" write$ address write$ "\bibbdsep {} " write$
                            year write$
                            extra.label "" =
                              'skip$
                              { "\el {" write$ extra.label write$ "}" write$ }
                            if$
               }
           if$
         }
      if$
    }
    { address empty$
        { year empty$
              { "          {" write$ publisher write$ }
              { "          {" write$ publisher write$ "\bibbdsep {} " write$
                            year write$
                            extra.label "" =
                              'skip$
                              { "\el {" write$ extra.label write$ "}" write$ }
                            if$
              }
          if$
        }
        { year empty$
              { "          {" write$ address write$ "\bpubaddr {} " write$ publisher write$ }
              { "          {" write$ address write$ "\bpubaddr {} " write$ publisher write$ "\bibbdsep {} " write$
                            year write$
                            extra.label "" =
                              'skip$
                              { "\el {" write$ extra.label write$ "}" write$ }
                            if$
              }
          if$
        }
      if$
    }
  if$
  "}}%" write$ newline$
  "         {" write$
  title empty$
    { "{}%" write$ newline$ }
    { "{" title * "}%" * write$ newline$ }
  if$
  gender empty$
    { "          {}" write$ }
    { "          {" gender * "}" * write$ }
  if$
  oyear empty$
    { "{}" write$ }
    { "{" oyear * "}" * write$ }
  if$
  author empty$
    { editor empty$
         { "{4}" write$ }% author and editor empty
         { "{3}" write$ }% author empty, editor not
      if$
    }
    { editor empty$
         { "{2}" write$ }% editor empty, author not
         { "{1}" write$ }% both author and editor given
      if$
    }
  if$
  pagestype empty$
    { "{}" write$ }
    { "{" pagestype * "}" * write$ }
  if$
  "{}{}{}{}}%" write$ newline$
  "        ]{" write$
  cite$ write$
  "}" write$
  url empty$
    'skip$
    { newline$ " \jburldef {" write$ cite$ write$ "}{" write$ url write$ "}%" write$ }
  if$
  newline$
  " \jbbibargs {" write$
  ""
  before.all 'output.state :=
}

FUNCTION {output.bibitem}{ output.bibitem.fst output.bibitem.snd }

FUNCTION {word.in}
{ bbl.in
  " " * }

FUNCTION {add.extra.label}
{extra.label "" =
   'skip$
   { "\bibel {" extra.label * "}" * * }
 if$
}

FUNCTION {format.date}
{ year empty$
    'skip$
    { output.state before.all =
            { "" * mid.sentence 'output.state := }
            { " " * }
      if$
      "\dateldelim {}" *
      month empty$
          'skip$
          { month "{} " * * }
      if$
      year * add.extra.label "\daterdelim {}" *
    }
  if$
}

FUNCTION {format.btitle}
{ before.all 'output.state :=
  title emphasize
}

FUNCTION {format.booktitle}
{ before.all 'output.state :=
  booktitle b.emphasize
}

FUNCTION {format.booklet.title}
{ before.all 'output.state :=
  title emphasize
}

FUNCTION {tie.or.space.connect}
{ duplicate$ text.length$ #3 <
    { "~" }
    { " " }
  if$
  swap$ * *
}

FUNCTION {either.or.check}
{ empty$
    'pop$
    { "can't use both " swap$ * " fields in " * cite$ * warning$ }
  if$
}

FUNCTION {format.bvolume}
{ volume empty$
    { " " }
    {
      volumetitle empty$
        { crossref missing$
                     { bbl.volume volume tie.or.space.connect }
                     { bbl.vol volume tie.or.space.connect }
                 if$
        }
        {
          crossref missing$
              { bbl.volume volume tie.or.space.connect }
              { bbl.vol volume tie.or.space.connect }
          if$
          ": \bibvtfont {" * volumetitle "}" * *
        }
      if$
    }
  if$
}

FUNCTION {format.bvolume.btitle.reversed}
{ volume empty$
    { title empty$
        'skip$
        {
          before.all 'output.state :=
          title emphasize
        }
      if$
    }
    { volumetitle empty$
        { title empty$
             'skip$
             {
                before.all 'output.state :=
                title emphasize
             }
          if$
        }
        { "\bibvtfont {" volumetitle * "}" * *
          bbl.volume volume tie.or.space.connect
          title empty$
            'skip$
            { bbl.of title emphasize * * }
          if$
        }
      if$
    }
  if$
}

FUNCTION {format.bvolume.btitle}
{ volformat missing$
     {
       format.btitle "title" output.check format.bvolume output
     }
     { volformat 's :=
       s "reversed" =
            { volumetitle empty$
                 {
                   "volformat given but no volumetitle in " cite$ * warning$
                   format.bvolume.btitle.reversed output
                 }
                 {
                   volume empty$
                        { "there's a volumetitle but no volume in " cite$ * warning$ * }
                        'skip$
                   if$
                   title empty$
                     {
                        "volformat given but no title in " cite$ * warning$
                     }
                     {
                        format.bvolume.btitle.reversed output
                     }
                   if$
                 }
              if$
            }
            {
                format.btitle "title" output.check format.bvolume output
            }
       if$
     }
  if$
}

FUNCTION {format.bvolume.booktitle.reversed}
{ volume empty$
    { booktitle empty$
        'skip$
        {
          before.all 'output.state :=
          booktitle b.emphasize
        }
      if$
    }
    { volumetitle empty$
        { booktitle empty$
             'skip$
             {
                before.all 'output.state :=
                bbl.volume volume tie.or.space.connect
                booktitle empty$
                    'skip$
                    { bbl.of booktitle b.emphasize * * }
                if$
             }
          if$
        }
        { "\bibvtfont {" volumetitle * "}" * *
          bbl.volume volume tie.or.space.connect
          booktitle empty$
            'skip$
            { bbl.of booktitle b.emphasize * * }
          if$
        }
      if$
    }
  if$
}

FUNCTION {format.in.editor.booktitle.volume.voltitle}
{ editor empty$
      { chapter empty$
           { ", " * }
           { " " *  }
        if$
        word.in *
      }
      { chapter empty$
           { ", " * }
           { " " *  }
        if$
        word.in format.in.editors * ", " * *
      }
  if$
  volformat missing$
     {
       format.booktitle "booktitle" output.check
       format.bvolume output
       format.booktitleaddon
     }
     { volformat 's :=
       s "reversed" =
            { volumetitle empty$
                 {
                    "volformat given but no volumetitle in " cite$ * warning$
                    format.bvolume.booktitle.reversed output
                    format.booktitleaddon
                 }
                 {
                   volume empty$
                        { "there's a volumetitle but no volume in " cite$ * warning$ * }
                        'skip$
                   if$
                   booktitle empty$
                     {
                        "volformat given but no booktitle in " cite$ * warning$
                     }
                     {
                        format.bvolume.booktitle.reversed output
                        format.booktitleaddon
                     }
                   if$
                 }
              if$
            }
            {  format.booktitle "booktitle" output.check
               format.bvolume output
               format.booktitleaddon
            }
       if$
     }
 if$
}


FUNCTION {format.number.series.book.old}
{ number empty$
   { series seriesformat field.or.null }
   { series empty$
       { "there's a number but no series in " cite$ * warning$ number }
       { series seriesformat number tie.or.space.connect }
     if$
   }
 if$
}

FUNCTION {format.number.series.book}
{ volume empty$
  { number empty$
     { series empty$
          { "" }
          { " \numberandseries {}{" series * "}" * }
       if$
     }
     { series empty$
         { " \numberandseries {" number * "}{}" * "There's a number but no series in " cite$ * warning$ }
         { " \numberandseries {" number * "}{" * series "}" * * }
       if$
     }
    if$
   }
   { series empty$
      { "" }
      { type$ "proceedings" =
          { " \numberandseries {}{" series * "}" * } % { " (" series * ")" * }
          { "" }
        if$
      }
     if$
   }
  if$
}

FUNCTION {format.number.series}
{ volume empty$
    { number empty$
        { series seriesformat field.or.null }
        { output.state mid.sentence =
            { bbl.number }
            { bbl.number capitalize }
          if$
          number tie.or.space.connect
          series empty$
            { "there's a number but no series in " cite$ * warning$ }
            { bbl.in.series space.word * series seriesformat * }
          if$
        }
      if$
    }
    { type$ "incollection" = crossref missing$ and
        { format.number.series.book }
        { "" }
      if$
   }
   if$
}

FUNCTION {first.ed} { "\firstedname" }
FUNCTION {second.ed}{ "\secondedname" }
FUNCTION {third.ed} { "\thirdedname" }
FUNCTION {fourth.ed}{ "\fourthedname" }
FUNCTION {fifth.ed} { "\fifthedname" }
FUNCTION {th.ed}    { "\thedname" }

FUNCTION {convert.edition}
{ edition extract.num "l" change.case$ 's :=
  s "first" = s "1" = or
  { first.ed 't := }
  { s "second" = s "2" = or
    { second.ed 't := }
    { s "third" = s "3" = or
      { third.ed 't := }
      { s "fourth" = s "4" = or
        { fourth.ed 't := }
        { s "fifth" = s "5" = or
          { fifth.ed 't := }
          { s #1 #1 substring$ is.num
            { s th.ed * 't := }
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

FUNCTION {format.edition}
{ edition empty$
    { "" }
    { convert.edition "~\editionname {}" * }
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
    { before.all 'output.state :=
      "\jbPages {" pages n.dashify * "}" *
    }
  if$
}

FUNCTION {format.journal.pages}
{ pages empty$
    'skip$
    { duplicate$ empty$
        { pop$ format.pages }
        { " \jbartPages {" pages n.dashify * "}" * * }
      if$
    }
  if$
}

FUNCTION {format.vol.num.pages}
{ before.all 'output.state :=
  volume empty$
    { year empty$ not
         { "there's a year but no volume in " cite$ * warning$ }
         'skip$
      if$
      number empty$
          { "" }
          { "there's a number but no volume in " cite$ * warning$
            type$ "periodical" =
               { "~\per" * }
               { "~\art" * }
            if$
            "volnumformat {}{" number * "}" *
          }
      if$
    }
    { type$ "periodical" =
          { "~\per" * }
          { "~\art" * }
      if$
      number empty$
          { "volnumformat {" volume * "}{}" * }
          { "volnumformat {" volume * "}{" number * "}" * * }
      if$
    }
  if$
  year empty$
    { month empty$ not
         { "there's a month but no year in " cite$ * warning$ }
         'skip$
      if$
    }
    { type$ "periodical" =
         { " \per" * }
         { " \art" * }
      if$
      month empty$
        { "yearformat {" year * add.extra.label "}" * * }
        { "yearformat {" month * "{} " * year * add.extra.label "}" * * }
      if$
    }
  if$
}

FUNCTION {format.chapter.inbook.incoll}
{ chapter empty$
    { "" }
    { before.all 'output.state :=
      type empty$
        { ", " bbl.chapter * }
        { ", " type * "l" change.case$ }
      if$
      chapter tie.or.space.connect
    }
  if$
}

FUNCTION {format.chapter.pages}
{ chapter empty$
    'format.pages
    { type empty$
        { before.all 'output.state := ", " bbl.chapter * }
        { ", " type * "l" change.case$ }
      if$
      chapter tie.or.space.connect
      pages empty$
        'skip$
        { format.pages * }
      if$
    }
  if$
}

FUNCTION {format.in.ed.booktitle}
{ booktitle empty$
    { "" }
    { editor empty$
        { word.in booktitle b.emphasize format.booktitleaddon * }
        { word.in format.in.editors * ", " * booktitle b.emphasize format.booktitleaddon * }
      if$
    }
  if$
}

FUNCTION {format.thesis.type}
{ type empty$
    'skip$
    { pop$
      type "t" change.case$
    }
  if$
}

FUNCTION {format.tr.number}
{ type empty$
    { bbl.techrep }
    'type
  if$
  number empty$
    { "t" change.case$ }
    { number tie.or.space.connect }
  if$
}

FUNCTION {format.article.crossref}
{ " \protect\jbartcrossrefchecked {" crossref * "}" * }

FUNCTION {format.book.crossref}
{ type$ "book" =
    { volume empty$
        { "empty volume in " cite$ * "'s crossref of " * crossref * warning$
          "\protect\jbcrossrefchecked {" crossref * "}" * }
        { "\protect\jbcrossrefchecked[" volume * "]{" * crossref "}" * * }
      if$
    }
    { "\protect\jbcrossrefchecked {" crossref * "}" * }
  if$
}

FUNCTION {format.incoll.inproc.crossref}
{ " \protect\jbincollcrossref {" crossref * "}" *
  editor empty$
    { "{}" * }
    { "{1}" * }
  if$
}

FUNCTION {oaddress.opublisher.oyear.format}
{ type$ "book" =
     { oyear empty$
            { opublisher empty$
                 { oaddress empty$
                     'skip$
                     { oaddress "\osep {} " * * }
                   if$
                 }
                 { oaddress empty$
                     { opublisher "\osep {} " * * }
                     { oaddress "\bpubaddr {} " * opublisher "\osep {} " * * * }
                   if$
                 }
              if$
            }
            { year empty$
                 { "oyear without year in " cite$ * warning$ }
                 'skip$
              if$
              opublisher empty$
                 { oaddress empty$
                     { oyear * "\osep {} \reprintname {}, " * }
                     { oaddress "\bibbdsep {} " * oyear "\osep {} " * * * }
                   if$
                 }
                 { oaddress empty$
                     { opublisher * "\bibbdsep {} " * oyear "\osep {} " * * }
                     { oaddress "\bpubaddr {} " * opublisher "\bibbdsep {} " * * oyear "\osep {} " * * * }
                   if$
                 }
              if$
            }
       if$
     }
     'skip$
  if$
}

FUNCTION {format.publisher}
{ year empty$ month empty$ not and
        { "there's a month but no year in " cite$ * warning$ }
        'skip$
  if$
  address empty$ publisher empty$ year empty$ and and
     'skip$
     { " \apyformat {" * }
  if$
  oaddress.opublisher.oyear.format
  address empty$
    { publisher empty$
          { year empty$
                'skip$
                { month empty$
                     { year * add.extra.label }
                     { month "{} " * year * * add.extra.label }
                  if$
                }
            if$
          }
          { year empty$
                { month empty$
                     { "\jbpublisher{" publisher "}" * * }
                      'skip$
                  if$
                }
                { month empty$
                     { "\jbpublisher{" publisher * "}\bibbdsep {} " * year * * add.extra.label }
                     { "\jbpublisher{" publisher * "}\bibbdsep {} " * month "{} " * year * * * add.extra.label }
                  if$
                }
            if$
          }
      if$
    }
    { publisher empty$
          { year empty$
                { address * }
                { month empty$
                     { address "\bibbdsep {} " * year * * add.extra.label }
                     { address "\bibbdsep {} " * month "{} " * year * * * add.extra.label }
                  if$
                }
            if$
          }
          { year empty$
                { month empty$
                     { address "\jbpublisher{\bpubaddr {} " * publisher * "}" * * }
                      'skip$
                  if$
                }
                { month empty$
                     { address "\jbpublisher{\bpubaddr {} " * publisher "}\bibbdsep {} " * year * * * add.extra.label }
                     { address "\jbpublisher{\bpubaddr {} " * publisher "}\bibbdsep {} " * month "{} " * year * * * * add.extra.label }
                  if$
                }
            if$
          }
      if$
    }
  if$
  address empty$ publisher empty$ year empty$ and and
     'skip$
     { "}" * }
  if$
}

FUNCTION {format.publisher.manual}
{ year empty$ month empty$ not and
        { "there's a month but no year in " cite$ * warning$ }
        'skip$
  if$
  address empty$ organization empty$ year empty$ and and
     'skip$
     { " \apyformat {" * }
  if$
  address empty$
    { organization empty$
          { year empty$
                'skip$
                { month empty$
                     { year * add.extra.label }
                     { month "{} " * year * * add.extra.label }
                  if$
                }
            if$
          }
          {
            year empty$
                { month empty$
                     { organization * }
                      'skip$
                  if$
                }
                { month empty$
                     { organization "\bibbdsep {} " * year * * add.extra.label }
                     { organization "\bibbdsep {} " * month "{} " * year * * * add.extra.label }
                  if$
                }
            if$
          }
      if$
    }
    { organization empty$
          { year empty$
                { address * }
                { month empty$
                     { address "\bibbdsep {} " * year * * add.extra.label }
                     { address "\bpubaddr {} " * month "{} " * year * * * add.extra.label }
                  if$
                }
            if$
          }
          { year empty$
                { month empty$
                     { address "\bpubaddr {} " * organization * * }
                      'skip$
                  if$
                }
                { month empty$
                     { address "\bpubaddr {} " * organization "\bibbdsep {} " * year * * * add.extra.label }
                     { address "\bpubaddr {} " * organization "\bibbdsep {} " * month "{} " * year * * * * add.extra.label }
                  if$
                }
            if$
          }
      if$
     }
  if$
  address empty$ organization empty$ year empty$ and and
     'skip$
     { "}" * }
  if$
}

FUNCTION {format.publisher.techreport}
{ year empty$ month empty$ not and
        { "there's a month but no year in " cite$ * warning$ }
        'skip$
  if$
  address empty$ institution empty$ year empty$ and and
     'skip$
     { " \apyformat {" * }
  if$
  address empty$
    {
      institution empty$
          {
            year empty$
                'skip$
                { month empty$
                     { year * add.extra.label }
                     { month "{} " * year * * add.extra.label }
                  if$
                }
            if$
          }
          {
            year empty$
                { month empty$
                     { institution * }
                      'skip$
                  if$
                }
                { month empty$
                     { institution "\bibbdsep {} " * year * * add.extra.label }
                     { institution "\bibbdsep {} " * month "{} " * year * * * add.extra.label }
                  if$
                }
            if$
          }
      if$
    }
    { institution empty$
          { year empty$
                { address * }
                { month empty$
                     { address "\bibbdsep {} " * year * * add.extra.label }
                     { address "\bpubaddr {} " * month "{} " * year * * * add.extra.label }
                  if$
                }
            if$
          }
          { year empty$
                { month empty$
                     { address "\bpubaddr {} " * institution * * }
                      'skip$
                  if$
                }
                { month empty$
                     { address "\bpubaddr {} " * institution "\bibbdsep {} " * year * * * add.extra.label }
                     { address "\bpubaddr {} " * institution "\bibbdsep {} " * month "{} " * year * * * * add.extra.label }
                  if$
                }
            if$
          }
      if$
    }
  if$
  address empty$ institution empty$ year empty$ and and
     'skip$
     { "}" * }
  if$
}

FUNCTION {format.edby.editors}
{ author empty$
    'skip$
    { editor empty$
         'skip$
         { "\edbyname {} " editor format.editor.names * output }
      if$
    }
  if$
}

FUNCTION {make.translators}
{ translator empty$
    { flanguage empty$
        { "" }
        { "\translator {}{" flanguage "l" change.case$ * "}{1}" * }
      if$
    }
    { "\translator {" translator format.translator.names * "}" *
      flanguage empty$
        { "{}{1}" * }
        { "{" * flanguage "l" change.case$ * "}{1}" * }
      if$
    }
  if$
}

FUNCTION {article}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.title "title" output.check
  format.titleaddon
  crossref missing$
    { journal journalnamemarkup
      "journal" output.check
      format.vol.num.pages output
      write.url
      format.journal.pages
    }
    { format.article.crossref output.nonnull
      format.pages output
    }
  if$
  format.note
  fin.entry
}

FUNCTION {periodical} {article}

FUNCTION {review}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.title "title" output.check
  format.titleaddon
  crossref missing$
    { journal journalnamemarkup
      "journal" output.check
      format.vol.num.pages output
      write.url
      format.journal.pages
    }
    { format.article.crossref output.nonnull
      format.pages output
    }
  if$
  format.note
  fin.entry
}

FUNCTION {book}
{ output.bibitem
  author empty$
    { editor empty$ not
        { format.editors output.nonnull }
        { "neither author nor editor in " cite$ * warning$ }
      if$
    }
    { format.authors output.nonnull }
  if$
  set.after.author.sep output
  crossref missing$
    { format.bvolume.btitle
      format.editors.last output.nonnull
      make.translators output
      format.titleaddon
      format.number.series.book output
      format.edition output
      format.pages output
      format.publisher
      write.url
      format.note
    }
    { format.btitle "title" output.check
      format.book.crossref output.nonnull
    }
  if$
  fin.entry
}

FUNCTION {booklet}
{ output.bibitem
  format.authors output
  set.after.author.sep output
  format.btitle "title" output.check
  format.titleaddon
  howpublished output
  format.publisher
  format.pages output
  write.url
  format.note
  fin.entry
}

FUNCTION {inbook}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  crossref missing$
    { format.bvolume.btitle
      format.titleaddon
      make.translators output
      format.number.series output
      format.edition output
      format.publisher
      write.url
      format.chapter.inbook.incoll output
      format.pages output
      format.note
    }
    { format.btitle "title" output.check
      format.chapter.inbook.incoll output
      format.incoll.inproc.crossref output.nonnull
      format.bvolume output
      format.pages output
    }
  if$
  fin.entry
}

FUNCTION {incollection}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  crossref missing$
    { format.btitle "title" output.check
      format.titleaddon
      format.chapter.inbook.incoll output
      format.in.editor.booktitle.volume.voltitle
      make.translators output
      format.number.series.book output
      format.edition output
      format.publisher
      write.url
      format.note
      format.pages output
    }
    { format.title "title" output.check
      format.chapter.inbook.incoll output
      format.incoll.inproc.crossref output.nonnull
      format.bvolume output
      format.pages output
    }
  if$
  fin.entry
}

FUNCTION {inproceedings}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.title "title" output.check
  format.titleaddon
  crossref missing$
    { format.in.ed.booktitle "booktitle" output.check
      format.bvolume output
      format.number.series output
      publisher empty$
        { organization output
          format.publisher
        }
        { organization output
          format.publisher
        }
      if$
      write.url
      format.note
      format.pages output
    }
    { format.incoll.inproc.crossref output.nonnull
      format.pages output
    }
  if$
  fin.entry
}

FUNCTION {conference} { inproceedings }

FUNCTION {manual}
{ output.bibitem
  author empty$
    { organization empty$
        'skip$
        { "\bibnf{" organization * "} {} {} {} {}" * output.nonnull }
      if$
    }
    { format.authors output }
  if$
  set.after.author.sep output
  format.btitle "title" output.check
  format.titleaddon
  format.edition output
  format.publisher.manual
  write.url
  format.note
  fin.entry
}

FUNCTION {mastersthesis}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.btitle "title" output.check
  format.titleaddon
  bbl.mthesis format.thesis.type output.nonnull
  school "school" output.check
  format.publisher
  write.url
  format.note
  fin.entry
}

FUNCTION {misc}
{ output.bibitem
  format.authors output
  set.after.author.sep output
  format.btitle output
  format.titleaddon
  howpublished output
  format.date
  write.url
  format.note
  fin.entry
}

FUNCTION {phdthesis}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.btitle "title" output.check
  format.titleaddon
  bbl.phdthesis format.thesis.type output.nonnull
  school "school" output.check
  format.publisher
  write.url
  format.note
  fin.entry
}

FUNCTION {proceedings}
{ output.bibitem
  format.editors output
  set.after.author.sep output
  format.btitle "title" output.check
  format.titleaddon
  format.number.series.book output
  organization output
  format.publisher
  write.url
  format.note
  fin.entry
}

FUNCTION {techreport}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.title "title" output.check
  format.tr.number output.nonnull
  format.publisher.techreport
  write.url
  format.note
  fin.entry
}

FUNCTION {unpublished}
{ output.bibitem
  format.authors "author" output.check
  set.after.author.sep output
  format.title "title" output.check
  format.titleaddon
  format.date
  write.url
  format.note
  fin.entry
}

FUNCTION {www}
{ output.bibitem
  format.authors output.nonnull
  set.after.author.sep output
  format.title output.nonnull
  write.url
  format.note
  fin.entry
}

FUNCTION {default.type} { misc }

READ

FUNCTION {sortify}
{ purify$
  "l" change.case$
}

INTEGERS { len }

FUNCTION {chop.word}
{ 's :=
  'len :=
  s #1 len substring$ =
    { s len #1 + global.max$ substring$ }
    's
  if$
}

INTEGERS { first-char-pos }

FUNCTION {is.a.char}
{ duplicate$ "-" =
    { #1 }
    { duplicate$ "~" =
        { #1 }
        { duplicate$ purify$ = }
      if$
    }
  if$
}

FUNCTION {find.first.char}
{
  #1 'first-char-pos :=
    { duplicate$ first-char-pos #1 substring$
      duplicate$ is.a.char not
      swap$ "" = not and
    }
    { first-char-pos #1 + 'first-char-pos := }
  while$
  pop$
  first-char-pos
}

FUNCTION {capitalize.von.part}
{
  duplicate$ find.first.char 'first-char-pos :=
  duplicate$ "u" change.case$ #1 first-char-pos substring$
  swap$ first-char-pos #1 + global.max$ substring$ *
}

FUNCTION {format.lab.names}
{ 's :=                                    % for two: (Jones/de~Bruijn)
  s #1 "{vv~}{ll}" format.name$ capitalize.von.part % for three: (Jones/de~Bruijn/Smith)
  s num.names$ duplicate$                  % for more: (Jones et~al.)
  #2 <
  'pop$                                    % for just one person
  { duplicate$ duplicate$
    #2 >
    swap$ s swap$ "{ff }{vv }{ll}{ jj}" format.name$ "others" =
    or
    { pop$ "\Bibetal" * }                  % for 4 or more, or last is `others'
    { #2 =
      {  "\jbbtasep " * s #2 "{vv~}{ll}" format.name$ capitalize.von.part * } % for (exactly) two
      { "\jbbfsasep " * s #2 "{vv~}{ll}" format.name$ capitalize.von.part *   % for three
        "\jbbstasep " * s #3 "{vv~}{ll}" format.name$ capitalize.von.part *
      }
      if$
    }
    if$
  }
  if$
  year missing$
      'skip$
     { "\jbdy {" year * "}" * * }% dummy year to prevent from `1993a' and `2001b' for works by the same author
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

FUNCTION {editor.key.label}
{ editor empty$
    { key empty$
        { cite$ #1 #3 substring$ }
        'key
      if$
    }
    { editor format.lab.names }
  if$
}

FUNCTION {calc.label}
{ type$ "book" = type$ "inbook" = or type$ "incollection" = or
  'author.editor.key.label
  { type$ "proceedings" =
    'editor.key.label
    'author.key.label
    if$
  }
  if$
  'label :=
}

FUNCTION {calc.dummy.label}
{ type$ "book" = type$ "inbook" = or type$ "incollection" = or type$ "commented" = or
  'author.editor.key.label
  { type$ "proceedings" =
    'editor.key.label
    'author.key.label
    if$
  }
  if$
  'dummy.label :=
}

FUNCTION {sort.format.names}
{ 's :=
  #1 'nameptr :=
  ""
  s num.names$ 'numnames :=
  numnames 'namesleft :=
  { namesleft #0 > }
  { nameptr #1 >
  { "   " * }
  'skip$
    if$
    s nameptr "{ll{ }}{  ff{ }}{ vv{ }}{  jj{ }}" format.name$ 't :=
    nameptr numnames = t "\bibnf {others} {} {} {} {}" = and
  { "\Bibetal" * }
  { t sortify * }
    if$
    nameptr #1 + 'nameptr :=
    namesleft #1 - 'namesleft :=
  }
  while$
}

FUNCTION {sort.format.title}
{ 't :=
  "A " #2
    "An " #3
      "The " #4 t chop.word
    chop.word
  chop.word
  sortify
  #1 global.max$ substring$
}

FUNCTION {author.sort}
{ author empty$
    { pseudonym empty$
          { key empty$
               { "to sort, need author, pseudonym or key in " cite$ * warning$ "" }
               { key sortify }
            if$
          }
          { pseudonym sort.format.names }
       if$
    }
    { author sort.format.names }
  if$
}

FUNCTION {author.editor.sort}
{ author empty$
    { editor empty$
        { pseudonym empty$
             { key empty$
                  { "to sort, need author, editor, pseudonym or key in " cite$ * warning$ "" }
                  { key sortify }
               if$
             }
             { pseudonym sort.format.names }
          if$
        }
        { editor sort.format.names }
      if$
    }
    { author sort.format.names }
  if$
}

FUNCTION {editor.sort}
{ editor empty$
    { pseudonym empty$
         { key empty$
              { "to sort, need author, editor, pseudonym or key in " cite$ * warning$ "" }
              { key sortify }
           if$
         }
         { pseudonym sort.format.names }
      if$
    }
    { editor sort.format.names }
  if$
}

FUNCTION {presort}
{ calc.label
  calc.dummy.label
  dummy.label sortify " " *
  sortkey empty$
     { type$ "book" =
         type$ "incollection" =    or
           type$ "inbook" =        or
            'author.editor.sort
            { type$ "proceedings" =
                'editor.sort
                'author.sort
              if$
            }
        if$
     }
     { sortkey sort.format.names }
  if$
  "    "
  *
  year field.or.null sortify
  *
  "    "
  *
  title field.or.null
  sort.format.title
  *
   #1 entry.max$ substring$
   'sort.label :=
   sort.label *
   #1 entry.max$ substring$
   'sort.key$ :=
}

ITERATE {presort}


SORT


STRINGS { last.dummy.label next.extra }

INTEGERS { last.extra.num number.label }

FUNCTION {forward.pass}
{ last.dummy.label dummy.label =
    { last.extra.num #1 + 'last.extra.num :=
      last.extra.num int.to.chr$ 'extra.label :=
    }
    { "a" chr.to.int$ 'last.extra.num :=
      "" 'extra.label :=
      dummy.label 'last.dummy.label :=
    }
  if$
  number.label #1 + 'number.label :=
}

FUNCTION {reverse.pass}
{ next.extra "b" =
    { "a" 'extra.label := }
    'skip$
  if$
  extra.label 'next.extra :=
  extra.label
  duplicate$ empty$
    'skip$
    { "" swap$ * }
  if$
  'extra.label :=
  dummy.label extra.label * 'dummy.label :=
}

ITERATE {forward.pass}

REVERSE {reverse.pass}

FUNCTION {bib.sort.order} { sort.label  'sort.key$ := }

ITERATE {bib.sort.order}

SORT

FUNCTION {set.current.authors}
{ author empty$
  { editor empty$
    { organization empty$
      { cite$ 'current-authors := }
      { organization 'current-authors := }
      if$
    }
    { editor format.names #1 entry.max$ substring$ 'current-authors := }
    if$
  }
  { author format.names #1 entry.max$ substring$ 'current-authors := }
  if$
}

STRINGS { previous-authors }

FUNCTION {initialize.previous.authors} { "" 'previous-authors := }

FUNCTION {mark.repeat.authors}
{ current-authors previous-authors =
  { #1 'repeat-authors := }
  'skip$
  if$
  current-authors 'previous-authors :=
}

ITERATE {set.current.authors}

EXECUTE {initialize.previous.authors}

ITERATE {mark.repeat.authors}

EXECUTE {initialize.previous.authors}

REVERSE {mark.repeat.authors}

FUNCTION {begin.bib}
{ preamble$ empty$
    'skip$
    { preamble$ write$ newline$ }
  if$
  "%" write$ newline$
  "% This bibliography was produced by using jox.bst" write$ newline$
  "%" write$ newline$
  "\begin{thebibliography}{}" write$ newline$
}

EXECUTE {begin.bib}

EXECUTE {init.state.consts}

ITERATE {call.type$}

FUNCTION {end.bib}
{ newline$
  "\end{thebibliography}" write$ newline$
}

EXECUTE {end.bib}
%% 
%%
%% End of file `jox.bst'.
