<?php
/**
 * Drilltrate Utilities
 *
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2014
 * @link http://www.thinkglobalschool.com/
 * 
 */
?>
//<script>
elgg.provide('elgg.drilltrate.drilltrate');

// Fix for goofy chrome 'empty' states, from: http://stackoverflow.com/a/18126524/1202510   
(function() {
// There's nothing to do for older browsers ;)
if (!window.addEventListener)
    return;
var blockPopstateEvent = true;
window.addEventListener("load", function() {
    // The timeout ensures that popstate-events will be unblocked right
    // after the load event occured, but not in the same event-loop cycle.
    setTimeout(function(){ blockPopstateEvent = false; }, 0);
}, false);
window.addEventListener("popstate", function(evt) {
    if (blockPopstateEvent && document.readyState=="complete") {
        evt.preventDefault();
        evt.stopImmediatePropagation();
    }
}, false);
})();

// Check if string starts with str
String.prototype.startsWith = function(str){
return (this.indexOf(str) === 0);
}

// Deparam function (from: https://gist.github.com/dancrew32/1163405)
function deParam (params, coerce) {
var obj = {};
var coerce_types = { 'true': !0, 'false': !1, 'null': null };
var decode = decodeURIComponent;

// Iterate over all name=value pairs.
$.each(params.replace(/\+/g, ' ').split('&'), function(j, v){
		var param = v.split( '=' ),
		key = decode(param[0]),
		val,
		cur = obj,
		i = 0,

		// If key is more complex than 'foo', like 'a[]' or 'a[b][c]', split it
		// into its component parts.
		keys = key.split(']['),
		keys_last = keys.length - 1;

		// If the first keys part contains [ and the last ends with ], then []
		// are correctly balanced.
		if (/\[/.test(keys[0]) && /\]$/.test(keys[keys_last])) {
		// Remove the trailing ] from the last keys part.
		keys[keys_last] = keys[keys_last].replace(/\]$/, '');

		// Split first keys part into two parts on the [ and add them back onto
		// the beginning of the keys array.
		keys = keys.shift().split('[').concat(keys);

		keys_last = keys.length - 1;
		} else {
			// Basic 'foo' style key.
			keys_last = 0;
		}

		// Are we dealing with a name=value pair, or just a name?
		if (param.length === 2) {
			val = decode(param[1]);

			// Coerce values.
			if (coerce) {
				val = val && !isNaN(val)            ? +val              // number
					: val === 'undefined'             ? undefined         // undefined
					: coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
					: val;                                                // string
			}

			if (keys_last) {
				// Complex key, build deep object structure based on a few rules:
				// * The 'cur' pointer starts at the object top-level.
				// * [] = array push (n is set to array length), [n] = array if n is 
				//   numeric, otherwise object.
				// * If at the last keys part, set the value.
				// * For each keys part, if the current level is undefined create an
				//   object or array based on the type of the next keys part.
				// * Move the 'cur' pointer to the next level.
				// * Rinse & repeat.
				for ( ; i <= keys_last; i++) {
					key = keys[i] === '' ? cur.length : keys[i];
					cur = cur[key] = i < keys_last
						? cur[key] || (keys[i+1] && isNaN( keys[i+1] ) ? {} : [])
						: val;
				}

			} else {
				// Simple key, even simpler rules, since only scalars and shallow
				// arrays are allowed.

				if ($.isArray(obj[key])) {
					// val is already an array, so push on the next value.
					obj[key].push(val);

				} else if (obj[key] !== undefined) {
					// val isn't an array, but since a second value has been specified,
					// convert val into an array.
					obj[key] = [obj[key], val];

				} else {
					// val is a scalar.
					obj[key] = val;
				}
			}

		} else if (key) {
			// No value was defined, so set something meaningful.
			obj[key] = coerce
				? undefined
				: '';
		}
});

return obj;
}