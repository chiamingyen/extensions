-- 
--  This is file `oberdiek.luacolor.lua',
--  generated with the docstrip utility.
-- 
--  The original source files were:
-- 
--  luacolor.dtx  (with options: `lua')
--  
--  This is a generated file.
--  
--  Copyright (C) 2007, 2009 by Heiko Oberdiek <oberdiek@uni-freiburg.de>
--  
--  This work may be distributed and/or modified under the
--  conditions of the LaTeX Project Public License, either
--  version 1.3 of this license or (at your option) any later
--  version. The latest version of this license is in
--     http://www.latex-project.org/lppl.txt
--  and version 1.3 or later is part of all distributions of
--  LaTeX version 2005/12/01 or later.
--  
--  This work has the LPPL maintenance status "maintained".
--  
--  This Current Maintainer of this work is Heiko Oberdiek.
--  
--  This work consists of the main source file luacolor.dtx
--  and the derived files
--     luacolor.sty, luacolor.pdf, luacolor.ins, luacolor.drv,
--     luacolor-test1.tex, luacolor-test2.tex, luacolor-test3.tex,
--     oberdiek.luacolor.lua, luacolor.lua.
--  
module("oberdiek.luacolor", package.seeall)
local ifpdf
if tonumber(tex.pdfoutput) > 0 then
  ifpdf = true
else
  ifpdf = false
end
local prefix
local prefixes = {
  dvips   = "color ",
  dvipdfm = "pdf:sc ",
  truetex = "textcolor:",
  pctexps = "ps::",
}
local patterns = {
  ["^color "]            = "dvips",
  ["^pdf: *begincolor "] = "dvipdfm",
  ["^pdf: *bcolor "]     = "dvipdfm",
  ["^pdf: *bc "]         = "dvipdfm",
  ["^pdf: *setcolor "]   = "dvipdfm",
  ["^pdf: *scolor "]     = "dvipdfm",
  ["^pdf: *sc "]         = "dvipdfm",
  ["^textcolor:"]        = "truetex",
  ["^ps::"]              = "pctexps",
}
local function info(msg, term)
  local target = "log"
  if term then
    target = "term and log"
  end
  texio.write_nl(target, "Package luacolor info: " .. msg .. ".")
  texio.write_nl(target, "")
end
function dvidetect()
  local v = tex.box[0]
  assert(v.id == node.id("hlist"))
  for v in node.traverse_id(node.id("whatsit"), v.list) do
    if v and v.subtype == 3 then -- special
      local data = v.data
      for pattern, driver in pairs(patterns) do
        if string.find(data, pattern) then
          prefix = prefixes[driver]
          tex.write(driver)
          return
        end
      end
      info("\\special{" .. data .. "}", true)
      return
    end
  end
  info("Missing \\special", true)
end
local map = {
  n = 0,
}
function get(color)
  local n = map[color]
  if not n then
    n = map.n + 1
    map.n = n
    map[n] = color
    map[color] = n
  end
  tex.write("" .. n)
end
local attribute
function setattribute(attr)
  attribute = attr
end
function process(box)
  local color = ""
  local list = tex.getbox(box)
  traverse(list, color)
end
local LIST = 1
local COLOR = 2
local type = {
  [node.id("hlist")] = LIST,
  [node.id("vlist")] = LIST,
  [node.id("rule")]  = COLOR,
  [node.id("glyph")] = COLOR,
  [node.id("disc")]  = COLOR,
}
local subtype = {
  [3] = COLOR, -- special
  [8] = COLOR, -- pdf_literal
}
local mode = 2 -- luatex.pdfliteral.direct
local WHATSIT = node.id("whatsit")
local SPECIAL = 3
local PDFLITERAL = 8
function traverse(list, color)
  if not list then
    return color
  end
  if type[list.id] ~= LIST then
    texio.write_nl("!!! Error: Wrong list type: " .. node.type(list.id))
    return color
  end
  local head = list.list
  for n in node.traverse(head) do
    local type = type[n.id]
    if type == LIST then
      color = traverse(n, color)
    elseif type == COLOR
           or (type == WHATSIT
               and subtype[n.subtype]) then
      local v = node.has_attribute(n, attribute)
      if v then
        local newColor = map[v]
        if newColor ~= color then
          color = newColor
          local newNode
          if ifpdf then
            newNode = node.new(WHATSIT, PDFLITERAL)
            newNode.mode = mode
            newNode.data = color
          else
            newNode = node.new(WHATSIT, SPECIAL)
            newNode.data = prefix .. color
          end
          if head == n then
            newNode.next = head
            local old_prev = head.prev
            head.prev = newNode
            head = newNode
            head.prev = old_prev
          else
            head = node.insert_before(head, n, newNode)
          end
        end
      end
    end
  end
  list.list = head
  return color
end
-- 
--  End of File `oberdiek.luacolor.lua'.
