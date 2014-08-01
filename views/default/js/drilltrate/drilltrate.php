<?php
/**
 * Drilltrate JS Lib
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
elgg.provide('elgg.drilltrate');

(function($) {
	$.fn.drilltrate = function(options) {
		var self = this,
		lastURL = null,
		drilltrate_id = this.attr('id');

		// Default config
		var defaults = {
			ajaxListUrl: null,
			defaultParams: {},
			ignoreQueryString: false,
			enableInfinite: false,
			disableHistory: false,
			context: null
		}

		// Merge options
		var options = $.extend(defaults, options);

		/** HOOKS **/

		/** END HOOKS **/

		/** UTILITIES **/

		// Helper to register elgg hooks
		function registerElggHook(name, type, handler) {
			elgg.register_hook_handler(name, type, handler);
		}

		/**
		 * Check if html5 storage is supported
		 */
		this.isLocalStorage = function() {
			try {
				return 'localStorage' in window && window['localStorage'] !== null;
			} catch (e) {
				return false;
			}
		}

		/** 
		 * Helper function to grab local params
		 */
		this.getLocalParams = function() {
			if (this.isLocalStorage()) {
				var params = localStorage.getItem(drilltrate_id + "_drilltrate_params");
				
				// Make sure we have a params array
				if (params) {
					params = deParam(params);

					var current_timestamp = new Date().getTime();

					// Check timestamp, we'll consider the stored params expired after 5 minutes
					if (current_timestamp > (parseFloat(params.timestamp) + (5 * 60000))) {
						return false;
					} else {
						return params;
					}
				} else {
					return false;
				}
			}
			return false;
		}

		/** 
		 * Helper function set local params
		 */
		this.setLocalParams = function(params) {
			if (this.isLocalStorage()) {
				params.timestamp = new Date().getTime();
				localStorage.setItem(drilltrate_id + "_drilltrate_params", $.param(params));
				return true;
			}
			return false;
		}

		/** END UTILITIES **/

		/**
		 * drilltrate list handler, responsible for populating the dashboard with content
		 * and pushing/popping state
		 *
		 * Usage:
		 * 
		 * Call this function with doPushState = true if you want to push a new state.
		 * Pass false to respond to popState events
		 *
		 * Elements in the dashboard have a data-param attribute, the value of the element is 
		 * used for the paramter
		 * ie: param = context, context = value
		 * 
		 * 
		 * @TODO: doPushState is a bit confusing in scenarios where we're not actually pushing state
		 * ie: history is disabled, we're still updating content
		 *
		 * @param  bool doPushState  Wether or not to push a new state (pass false for popState)
		 * @return void
		 */
		this.listHandler = function (doPushState) {

			// Get querystring, if available
			var query_index = window.location.href.indexOf('?');

			if (query_index != -1 && !options.ignoreQueryString) {
				var params = deParam(window.location.href.slice(query_index + 1));
				var base_url = window.location.href.slice(0, query_index);
			} else {
				// Use defaults
				var localParams = this.getLocalParams();
				if (localParams && options.disableHistory) {
					var params = localParams;
				} else {
					var params = deParam(options.defaultParams);
				}
				var base_url = window.location.href;
			}

			// If we're not pushing state
			if (!doPushState) {
				// Loop over available params
				$.each(params, function(idx, val) {
					// Get elements matching this param
					var $param_elements = self.find("[data-param='" + idx + "']");
					var $value_elements = self.find("[data-value='" + val + "']");

					// Deactive param elements
					$param_elements.each(function(idx) {
						$(this).parent().removeClass('elgg-state-selected');
						$(this).attr('data-drilltrate_active', 0);
					});

					// Activate elements
					$value_elements.each(function(idx) {
						$(this).parent().addClass('elgg-state-selected');
						$(this).attr('data-drilltrate_active', 1);

						// See if element is in a child menu
						var $child_menu = $('[data-parent-id="' + $(this).closest('li').attr('id') + '"]');
						if ($child_menu.length) {
							self.showChildMenu($child_menu);
						}
					});
				});
			} else {
				params = {};
				// We're pushing state (or updating) find active params
				self.find('[data-drilltrate_active=1]').each(function(idx) {
					// If this element has a value, and is enabled
					var value = $(this).data('value');
					params[$(this).data('param')] = value;
				});

				// If history isn't disabled
				if (!options.disableHistory) {
					// Push that state
					var stateUrl = base_url + "?" + $.param(params)
					history.pushState({'url': stateUrl, 'type': 'drilltrate_list_state'}, elgg.echo('drilltrate:title:dashboard'), stateUrl);
				} else {
					// Set local params (if possible)
					this.setLocalParams(params);
				}
			}

			// Show loader
			self.find('.drilltrate-content-container').html("<div class='elgg-ajax-loader'></div>");

			if (options.context) {
				params['page_context'] = options.context;
			}

			// Load data
			elgg.get(options.ajaxListUrl, {
				data: params,
				success: function(data) {
					// Load data
					self.find(".drilltrate-content-container").html(data);

					// Trigger a hook indicating that content has been loaded
					elgg.trigger_hook('content_loaded', 'drilltrate', {'data': data, 'container': self.find(".drilltrate-content-container")});

					// If infinite scroll is enabled, hide the pagination
					if (options.enableInfinite) {
						self.find('.elgg-pagination').hide();
					}
				},
				error: function() {
					// Show error on failure
					self.find(".drilltrate-content-container").html(elgg.echo('drilltrate:error:content'));
				}
			});

			lastURL = window.location.href;
		}

		/**
		 * Init Infinite Scroll
		 */
		this.initInifiniteScroll = function() {
			var loadingStarted = false;

			// Set up infinite scroll
			$(window).scroll(function(){
				if  (($(window).scrollTop() + 100) >= ($(document).height() - 100) - $(window).height()){

					// Get the last pagination item on the page
					var $last_pagination = self.find('.elgg-pagination li').last();

					// Hard code the container for now.. (the first ul)
					var $container = self.find('.drilltrate-content-container > ul:first-child');

					// Get classes
					var container_class = $container.attr('class');

					if ($last_pagination.length && !$last_pagination.hasClass('elgg-state-disabled')) {
					

						if (!loadingStarted) {
							loadingStarted = true;

							setTimeout(function() {
								var $loader = $(document.createElement('div')).attr('class', 'drilltrate-infinite-loader elgg-ajax-loader').hide();
								$container.closest('.elgg-`').append($loader);
								$loader.fadeIn();

								loadingStarted  = false;

								// Load data
								elgg.get($last_pagination.find('a').attr('href'), {
									data: {},
									success: function(data) {
										$loader.fadeOut().remove();

										$data = $(data);

										$items = $data.filter(function() {
											var $_this = $(this);
											return container_class.indexOf($_this.attr('class')) >= 0;
										}).children('li').hide();

										$pagination = $data.filter('.elgg-pagination');
										
										$container.parent().find('.elgg-pagination').replaceWith($pagination);

										$container.append($items);

										$items.fadeIn();

										// Trigger a hook for further action after new items are loaded
										elgg.trigger_hook('infinite_loaded', 'drilltrate', {'items': $items, 'container': $container});

										self.find('.elgg-pagination').hide();
									},
									error: function() {
										// Show error on failure
										elgg.register_error(elgg.echo('drilltrate:error:content'));
										$loader.fadeOut().remove();
									}
								});
							}, 1000);

						}
					}
				}
			});
		}

		/** Register elgg hooks **/

	 	//

	 	/**
	 	 * Display a child menu
	 	 */
	 	this.showChildMenu = function($menu) {
	 		$menu.show();
	 	}

	 	/**
	 	 * Hide a child menu
	 	 */
	 	this.hideChildMenu = function($menu) {
	 		$menu.hide();
	 	}


		/**
		 * Init new drilltrate menu
		 */ 
		this.init = function(config) {
			// If infinite scroll is enabled, init!
			if (options.enableInfinite) {
				this.initInifiniteScroll();
			}

			// Move child menus out of parent list items
			self.find('[data-parent-id]').each(function(idx) {
				$(this).insertAfter($('#' + $(this).data('parent-id')).parent());
			});

			// Bind menu links
			self.find('.drilltrate-menu-container li > a').live('click', function(event) {
				var $_this = $(this);

				self.find('[data-param="' + $(this).data('param') + '"]').each(function(idx) {

					// Deactivate children
					$('[data-parent-id="' + $(this).closest('li').attr('id') + '"] li > a').each(function (idx) {
						$(this).attr('data-drilltrate_active', 0);
						$(this).parent().removeClass('elgg-state-selected');
					});

					if (!$(this).is($_this)) {
						$(this).attr('data-drilltrate_active', 0);
						$(this).parent().removeClass('elgg-state-selected');

						// See if this item has children
						var $child_menu = $('[data-parent-id="' + $(this).closest('li').attr('id') + '"]');
						if ($child_menu.length) {
							self.hideChildMenu($child_menu);
						}
					} else {
						$(this).attr('data-drilltrate_active', 1);
						$(this).parent().addClass('elgg-state-selected');

						// See if this item has children
						var $child_menu = $('[data-parent-id="' + $(this).closest('li').attr('id') + '"]');
						if ($child_menu.length) {
							self.showChildMenu($child_menu);
						}
					}
				});

				self.listHandler(true);
				event.preventDefault();
			});
		

			// Handle sort order clicks
			self.find('.drilltrate-sort').live('click', function(event) {
				$(this).toggleClass('descending').toggleClass('ascending');

				if ($(this).hasClass('descending')) {
					$(this).html(elgg.echo('drilltrate:label:sortdesc'));
					$(this).val('ASC');
				} else if ($(this).hasClass('ascending')) {
					$(this).html(elgg.echo('drilltrate:label:sortasc'));
					$(this).val('DESC');
				}

				// Use the list handler
				self.listHandler(true);

				event.preventDefault();
			});

			// Init pagination
			self.find('.drilltrate-content-container .elgg-pagination a').live('click', function(event) {
				// Get link params
				var link_params = deParam($(this).attr('href').slice($(this).attr('href').indexOf('?') + 1));

				// Set data attribute and value of offset
				$(this).attr('data-param', 'offset').data('param', 'offset').attr('data-drilltrate_active', 1);
				$(this).data('value', link_params['offset']);

				// Use the trusty list handler with this element
				self.listHandler(true);
				event.preventDefault();
			});

			// If the content container is empty (first load, populate it from params)
			if (self.find('.drilltrate-content-container').is(':empty')) {
				this.listHandler(false);
			}

			// If we're allowing history
			if (!options.disableHistory) {
				// Add popstate event listener
				window.addEventListener("popstate", function(event) {
					if (elgg.trigger_hook('popstate', 'drilltrate', event)) {
						self.listHandler(false);
					}
				});
			}

		}

		this.init();
		return this;
	};
}(jQuery));