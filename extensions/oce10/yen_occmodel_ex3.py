#!/usr/bin/python2
# -*- coding: utf-8 -*-
import sys
from math import *

from geotools import *
from gltools import *
from occmodel import *
from occmodelviewer import Viewer, viewer

e1 = Edge().createLine(start = (0.,0.,0.), end = (1.,1.,0.))
e1 = Edge().createArc3P(start = (1.,0.,0.), end = (-1.,0.,0.), pnt = (0.,1.,0.))
e1 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 1.)
# Bezier
start = Vertex(0.,0.,0.)
end = Vertex(1.,0.,0.)
pnts = ((0.,2.,0.), (1.,1.5,0.))
e1 = Edge().createBezier(start,end,pnts)
# Spline
start = Vertex(0.,0.,0.)
end = Vertex(1.,0.,0.)
pnts = ((0.,2.,0.), (5.,1.5,0.))
e1 = Edge().createSpline(start,end,pnts)
# face
e1 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 1.)
f1 = Face().createConstrained(e1, ((0.,.5,.25),))
# face edge sequence
start = Vertex(1.,0.,0.)
end = Vertex(-1.,0.,0.)
e1 = Edge().createLine(end,start)

pnt = (0.,1.,0.)
e2 = Edge().createArc3P(start,end,pnt)

w1 = Wire().createWire((e1,e2))
f1 = Face().createFace(w1)
# polygon
pnts = ((0.,0.,0.), (0.,2.,0.), (1.,2.,0.), (1.,0.,0.))
f1 = Face().createPolygonal(pnts)
# sphere
solid = Solid()
solid.createSphere((1.,2.,3.),.5)
# box
solid = Solid().createBox((0.,0.,0.),(100.,100.,100.))
# cylinder
solid = Solid().createCylinder((0.,0.,0.),(0.,0.,1.), 1.)
# cone
solid = Solid().createCone((0.,0.,0.),(0.,0.,1.), 1., 2.)
# boolean union
s1 = Solid().createSphere((0.,0.,0.),.5)
s2 = Solid().createSphere((.25,0.,0.),.5)
s1.fuse(s2)
# boolean difference
s1 = Solid().createSphere((0.,0.,0.),.5)
s2 = Solid().createSphere((.25,0.,0.),.5)
s1.cut(s2)
# boolean intersection
s1 = Solid().createSphere((0.,0.,0.),.5)
s2 = Solid().createSphere((.25,0.,0.),.5)
s1.common(s2)
# extrude
pnts = (
    (0.,0.,0.),
    (0.,2.,0.),
    (5.,1.5,0.),
    (0.,0.,0.)
)
e1 = Edge().createSpline(points = pnts)
face = Face().createFace(e1)

solid = Solid().extrude(face, (0.,0.,0.), (0.,0.,5.))
# revolve
e1 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 1.)
face = Face().createFace(e1)

solid = Solid().revolve(face, (0.,2.,0.), (1.,2.,0.), pi/2.)
# loaf
e1 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 1.)
e2 = Edge().createEllipse(center=(0.,0.,5.),normal=(0.,0.,1.), rMajor = 2.0, rMinor=1.0)
e3 = Edge().createCircle(center=(0.,0.,10.),normal=(0.,0.,1.),radius = 1.0)
solid = Solid().loft((e1,e2,e3))
# pipe
e1 = Edge().createArc((0.,0.,0.),(2.,0.,2.),(2.,0.,0.))
e2 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 1.)
f1 = Face().createFace(e2)
solid = Solid().pipe(f1, e1)
print(solid.volume())
#print(dir(Viewer))
w1 = Wire().createPolygon((
    (0.,0.,0.),
    (0.,0.,1.),
    (0.75,0.,1.),
    (0.75,0.,0.)),
    close = False
)
e1 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 0.2)
mysolid = Solid().sweep(w1, e1)
print(mysolid.volume())
#coding: utf-8
# 使用 occmodel 模組, 間接擷取 Open CASCADE 函式庫
#from occmodel import *
# 建立三個 circles, 然後 loft 成實體, 印出體積後
# 最後再轉成 loft.stp STEP 檔案
第一個圓 = Edge().createCircle(center=(0.,0.,0.),normal=(0.,0.,1.),radius = 1.2)
第二個圓 = Edge().createCircle(center=(0.,0.,5.),normal=(0.,0.,1.),radius = 1.5)
第三個圓 = Edge().createCircle(center=(0.,0.,10.),normal=(0.,0.,1.),radius = 1.0)
solid = Solid().loft((第一個圓,第二個圓,第三個圓), True)
print (solid.volume())
# 特別注意轉出 loft.stp 的用法
Tools.writeSTEP(bytes('loft.stp'.encode("utf-8")),solid)
