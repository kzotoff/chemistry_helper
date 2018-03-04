/*

Zoom-resolution information box. Also shows smallest device which can fit it
(i.e., 1300px-wide window can fit into 1366x768 display but cannot fit into 1280x800)

requires no frameworks, pure javascript, except draggable option (needs jQuery and jQueryUI)

usage: just put link in your head section and tune up refresh speed and device type

*/

var resolutionIndicatorType     = 'normal';  // normal, mobile_portrait, mobile_landscape, *
var resolutionIndicatorInterval = 100;       // msec

(function(type, interval) {

	// resolution list to check against
	var resolutions = {
		svga640    : { type : 'normal',           x:      640, y:      480, 'caption': 'VGA' },
		svga800    : { type : 'normal',           x:      800, y:      600, 'caption': 'SVGA' },
		svga1024   : { type : 'normal',           x:     1024, y:      768, 'caption': 'XGA' },
		svga1280   : { type : 'normal',           x:     1280, y:     1024, 'caption': 'SXGA' },
		svga1400   : { type : 'normal',           x:     1400, y:     1050, 'caption': 'SXGA+' },
		svga1600   : { type : 'normal',           x:     1600, y:     1200, 'caption': 'UXGA' },
		svga2048   : { type : 'normal',           x:     2048, y:     1536, 'caption': 'QXGA' },

		wide1280   : { type : 'normal',           x:     1280, y:      800, 'caption': 'wide notebooks' },
		wide1366   : { type : 'normal',           x:     1366, y:      768, 'caption': 'wide 18&quot;' },
		wide1440   : { type : 'normal',           x:     1440, y:      900, 'caption': 'wide 19&quot;' },
		wide1600   : { type : 'normal',           x:     1600, y:      900, 'caption': 'wide 20&quot;' },

		fullhd     : { type : 'normal',           x:     1920, y:     1080, 'caption': 'FullHD' },
		fullhd2    : { type : 'normal',           x:     1920, y:     1200, 'caption': 'FullHD/WUXGA' },

		mac13ret   : { type : 'normal',           x:     2560, y:     1600, 'caption': 'Macbook 13&quot; retina' },
		mac15ret   : { type : 'normal',           x:     2880, y:     1800, 'caption': 'Macbook 15&quot; retina' },
		mac5k      : { type : 'normal',           x:     5120, y:     2880, 'caption': 'iMac 5k' },
		uhd8k      : { type : 'normal',           x:     7680, y:     4320, 'caption': 'UltraHD 8k' },

		mobile320v : { type : 'mobile_portrait',  x:      240, y:      320, 'caption': 'mobile QVGA, portrait' },
		mobile320h : { type : 'mobile_landscape', x:      320, y:      240, 'caption': 'mobile QVGA, landscape' },

		mobile800v : { type : 'mobile_portrait',  x:      480, y:      800, 'caption': 'mobile WVGA, portrait' },
		mobile800h : { type : 'mobile_landscape', x:      800, y:      480, 'caption': 'mobile WVGA, landscape' },

		mobile960v : { type : 'mobile_portrait',  x:      540, y:      960, 'caption': 'mobile high, portrait' },
		mobile960h : { type : 'mobile_landscape', x:      960, y:      540, 'caption': 'mobile high, landscape' },

		iphone4v   : { type : 'mobile_portrait',  x:      640, y:      960, 'caption': 'iPhone 4 portrait' },
		iphone4h   : { type : 'mobile_landscape', x:      960, y:      640, 'caption': 'iPhone 4 landscape' },

		iphone5v   : { type : 'mobile_portrait',  x:      640, y:     1136, 'caption': 'iPhone 5 portrait' },
		iphone5h   : { type : 'mobile_landscape', x:     1136, y:      640, 'caption': 'iPhone 5 landscape' },

		iphone6v   : { type : 'mobile_portrait',  x:      750, y:     1334, 'caption': 'iPhone 6 portrait' },
		iphone6h   : { type : 'mobile_landscape', x:     1334, y:      750, 'caption': 'iPhone 6 landscape' },

		iphone6pv  : { type : 'mobile_portrait',  x:     1080, y:     1920, 'caption': 'iPhone 6+ portrait' },
		iphone6ph  : { type : 'mobile_landscape', x:     1920, y:     1080, 'caption': 'iPhone 6+ landscape' },

		ipad3v     : { type : 'mobile_portrait',  x:     1536, y:     2048, 'caption': 'iPad 3/4 portrait' },
		ipad3h     : { type : 'mobile_landscape', x:     2048, y:     1536, 'caption': 'iPad 3/4 landscape' },

		infinity   : { type : 'normal',           x: Infinity, y: Infinity, 'caption': 'where you got it?' }
	}

	// zoom it!
	function makeItBigger(target) {
		target.target.style.fontSize = '4rem';
		setTimeout(function() { target.target.style.fontSize = '1rem'; }, 5000);
	}

	// check if box exists, create if not
	function checkBox() {
		if (document.body == null) {
			return false;
		}

		if (document.getElementById('resolution_indicator') == null) {
			var div = document.createElement('div');
			div.innerHTML = 'loading';
			div.setAttribute('id', 'resolution_indicator');
			document.body.appendChild(div);
			with (div.style) {
				position        = 'fixed';
				top             = '4rem';
				right           = '2rem';
				padding         = '0.2rem 0.3rem';
				backgroundColor = 'rgba(200,200,200,0.7)';
				color           = 'white';
				fontSize        = '1rem';
				fontWeight      = 'bold';
				cursor          = 'pointer';
				textAlign       = 'center';
			}

			// make it really big when clicked (useful on high resolutions or small screens)
			div.addEventListener('click', makeItBigger);

			// if jQuery and jQuery.draggable is connected, also make it draggable
			if ((typeof(jQuery) == 'function') && (typeof(jQuery.fn.draggable) == 'function')) {
				jQuery('#resolution_indicator').draggable({ start : function() { jQuery('#resolution_indicator').css('right', 'auto'); } });
			}
		}

		return true;
	}

	// data refresh function
	function refresh(type) {

		if (!checkBox()) {
			return;
		}

		var w = window.innerWidth  ? window.innerWidth  : document.documentElement.clientWidth;
		var h = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight;

		var closest = resolutions.infinity;

		for (test in resolutions) {
			if ((resolutions[test].type == type || type == '*') && (resolutions[test].x >= w) && (resolutions[test].x < closest.x)) {
				closest = resolutions[test];
			}
		}
		document.getElementById('resolution_indicator').innerHTML =
			w +'x'+h + '<br />' +
			closest.caption +
			(closest.x < Infinity ? ' (' + closest.x + 'x' + closest.y + ')' : '');

	}

	// default values
	if ((typeof(interval) == 'undefined') || (parseInt(interval) == 0)) {
		interval = 500;
	}
	if (typeof(type) == 'undefined') {
		type = '*';
	}

	// launch!
	setInterval(function() { refresh(type); }, interval);


})(resolutionIndicatorType, resolutionIndicatorInterval);
