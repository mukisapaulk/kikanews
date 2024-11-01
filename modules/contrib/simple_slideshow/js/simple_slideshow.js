/**
 * @file
 * Defines Javascript behaviors for the simple_slideshow module.
 */
 
var elms = document.getElementsByClassName( 'splide' );

for ( var i = 0; i < elms.length; i++ ) {
  new Splide( elms[ i ] ).mount();
}
