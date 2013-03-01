/////////////////////////////////////////////////////////////////////
//
// turntable.js
//
// JavaScript for use with `3Djscript' option of \includemovie
//
// * Greatly improves the rotational behaviour of the 3D object,
//   prevents it from tilting to the side while dragging the mouse.
//   This is achieved by suppressing the rolling of the camera about
//   its optical axis.
//
/////////////////////////////////////////////////////////////////////

// maximum pitch (degrees from horizontal) of the camera
var max_alpha = 88;
var min_beta = 90 - max_alpha; // the complement
var cos_min_beta = Math.cos(min_beta * Math.PI/180);
var tan_min_beta = Math.tan(min_beta * Math.PI/180);

var camera = scene.cameras.getByIndex(0);
camera.axis_up = camera.up.subtract(camera.position);
camera.axis_up.normalize();

//updates the vertical axis of rotation whenever a predefined view
//is selected from the drop down list in the 3D toolbar
var cameraEventHandler = new CameraEventHandler();
cameraEventHandler.onEvent = function (e) {
  camera.axis_up = camera.up.subtract(camera.position);
  camera.axis_up.normalize();
}
runtime.addEventHandler(cameraEventHandler);

//suppresses camera rolling and limits camera pitch
var mouseEventHandler = new MouseEventHandler();
mouseEventHandler.onMouseMove = true;
mouseEventHandler.onEvent = function (e) {
  runtime.setCurrentTool(runtime.TOOL_NAME_ROTATE);
  var c2c = camera.position.subtract(camera.targetPosition);
  var roo = c2c.length;
  c2c.normalize();
  cos_beta = c2c.dot(camera.axis_up); //cos of enclosed angle

  //correct the camera position if it is too high or too low
  if(Math.abs(cos_beta) > cos_min_beta) {
    //auxiliary vectors a & b
    var a = camera.axis_up.scale(cos_beta);
    var b = c2c.subtract(a);
    b.normalize();
    b.scaleInPlace(tan_min_beta * a.length);

    c2c.set(a.add(b));
    c2c.normalize();

    camera.position.set(camera.targetPosition.add(c2c.scale(roo)));
    cos_beta = c2c.dot(camera.axis_up);
  }

  //suppress rolling
  camera.up.set(
    camera.position.add(camera.axis_up).add(c2c.scale(-cos_beta))
  );
};
runtime.addEventHandler(mouseEventHandler);
