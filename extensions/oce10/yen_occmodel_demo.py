from occmodelviewer import viewer
from occmodel import *

solid = Solid()
solid.createCylinder((0.,0.,0.),(0.,0.,1.), 1.)
print(solid.centreOfMass())

view = viewer(interactive = True)
view.add(solid)
view.redraw()
#viewer(solid)
#viewer((solid,face,edge))