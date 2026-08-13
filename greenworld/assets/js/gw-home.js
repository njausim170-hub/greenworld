/* GreenWorld Wellness - homepage interactions (hero carousel + featured scroller). */
( function () {
	'use strict';

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) { fn(); }
		else { document.addEventListener( 'DOMContentLoaded', fn ); }
	}

	function toArray( list ) { return Array.prototype.slice.call( list ); }

	function initHero( root ) {
		var slides = toArray( root.querySelectorAll( '[data-gw-hero-slide]' ) );
		var dots   = toArray( root.querySelectorAll( '[data-gw-hero-dot]' ) );
		if ( slides.length < 2 ) { return; }
		var idx = 0, timer = null, delay = 6000;

		function show( n ) {
			idx = ( n + slides.length ) % slides.length;
			slides.forEach( function ( s, i ) {
				var on = ( i === idx );
				s.classList.toggle( 'is-active', on );
				s.setAttribute( 'aria-hidden', on ? 'false' : 'true' );
			} );
			dots.forEach( function ( d, i ) {
				d.classList.toggle( 'is-active', i === idx );
				d.setAttribute( 'aria-selected', i === idx ? 'true' : 'false' );
			} );
		}
		function next() { show( idx + 1 ); }
		function prev() { show( idx - 1 ); }
		function start() { stop(); timer = setInterval( next, delay ); }
		function stop() { if ( timer ) { clearInterval( timer ); timer = null; } }

		var np = root.querySelector( '[data-gw-hero-next]' );
		if ( np ) { np.addEventListener( 'click', function () { next(); start(); } ); }
		var pp = root.querySelector( '[data-gw-hero-prev]' );
		if ( pp ) { pp.addEventListener( 'click', function () { prev(); start(); } ); }
		dots.forEach( function ( d ) {
			d.addEventListener( 'click', function () {
				show( parseInt( d.getAttribute( 'data-gw-hero-dot' ), 10 ) || 0 );
				start();
			} );
		} );

		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );

		var sx = null;
		root.addEventListener( 'touchstart', function ( e ) { sx = e.touches[ 0 ].clientX; }, { passive: true } );
		root.addEventListener( 'touchend', function ( e ) {
			if ( sx === null ) { return; }
			var dx = e.changedTouches[ 0 ].clientX - sx;
			if ( Math.abs( dx ) > 40 ) { if ( dx < 0 ) { next(); } else { prev(); } start(); }
			sx = null;
		}, { passive: true } );

		show( 0 );
		start();
	}

	function initScroller( root ) {
		var track = root.querySelector( '.products' );
		if ( ! track ) { return; }
		function step() {
			var card = track.querySelector( 'li.product' );
			var w = card ? card.getBoundingClientRect().width + 18 : 260;
			var per = Math.max( 1, Math.floor( track.clientWidth / w ) );
			return w * per;
		}
		toArray( root.querySelectorAll( '[data-gw-scroll]' ) ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var dir = btn.getAttribute( 'data-gw-scroll' ) === 'prev' ? -1 : 1;
				track.scrollBy( { left: dir * step(), behavior: 'smooth' } );
			} );
		} );
	}

	ready( function () {
		toArray( document.querySelectorAll( '[data-gw-hero]' ) ).forEach( initHero );
		toArray( document.querySelectorAll( '[data-gw-scroller]' ) ).forEach( initScroller );
	} );
} )();
