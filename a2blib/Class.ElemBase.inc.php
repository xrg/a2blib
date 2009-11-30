<?php
/* Copyright 2006-2009 P. Christeas <p_christ@hol.gr>, LGPL 2
*/
	/** Base class for page elements
	In a form page, all elements must extend this class, so that
	some standard functions are available */

abstract class ElemBase {
	abstract function Render();
	
	// stub functions..
	function RenderHead() {

	}
	
	function RenderHeadSpecial($rmode, &$robj) {

	}
	
	public function RenderSpecial($rmode, &$robj) {
	}

	/** Called \b before any html is produced. It can so return a string,
	    url, that will redirect to another page */
	function PerformAction(){
	}
	
};


?>