////////////////////////////////////////////////////////////////////////
//
// lights.js
//
// example JavaScript for use with `3Djscript' option of \includemovie
//
// * Adds light sources around the object for better illumination.
//
////////////////////////////////////////////////////////////////////////
console.println(scene.lightScheme);
////first remove any scene lights
//for (var i = scene.lights.count - 1; i >= 0; i--) {
//  scene.lights.removeByIndex(i);
//}
//
////new lights
////from left
//l1 = scene.createLight();
//l1.color.set(1,1,1);
//l1.brightness = .4;
//l1.direction.set(1,0,0);
//
////from right
//l2 = scene.createLight();
//l2.color.set(1,1,1);
//l2.brightness = .4;
//l2.direction.set(-1,0,0);
//
////from bottom
//l3 = scene.createLight();
//l3.color.set(1,1,1);
//l3.brightness = .4;
//l3.direction.set(0,0,1);
//
////from top
//l4 = scene.createLight();
//l4.color.set(1,1,1);
//l4.brightness = .4;
//l4.direction.set(0,0,-1);
//
////from front
//l5 = scene.createLight();
//l5.color.set(1,1,1);
//l5.brightness = .4;
//l5.direction.set(0,1,0);
//
////from back
//l6 = scene.createLight();
//l6.color.set(1,1,1);
//l6.brightness = .4;
//l6.direction.set(0,-1,0);
