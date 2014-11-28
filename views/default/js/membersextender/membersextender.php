<?php
/**
 * Members-Extender JS Library
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 * 
 */
?>
//<script>
elgg.provide('elgg.membersextender');

// Init function
elgg.membersextender.init = function() {
	// Init hover
	elgg.membersextender.initAvatarHover();

	// Handle members tab display toggle in admin area
	if ($('select[name="display_members_tab"]').length != 0) {
		if ($('select[name="display_members_tab"]').val() == 0) {
			$('input[name="member_subcategories"], input[name="member_subcategories[]"]').attr('DISABLED', 'DISABLED');
		}

		$('select[name="display_members_tab"]').change(function(event) {
			if ($(this).val() == 1) {
				$('.members-sub-categories-container').addClass('members-show-subcategories');
				$('input[name="member_subcategories"], input[name="member_subcategories[]"]').removeAttr('DISABLED');
			} else {
				$('.members-sub-categories-container').removeClass('members-show-subcategories');
				$('input[name="member_subcategories"], input[name="member_subcategories[]"]').attr('DISABLED', 'DISABLED');
			}
		});
	}

	// Init custom members navigation
	$('.members-extender-main-nav').live('click', elgg.membersextender.mainNavClick);
}

// Init chart.js
elgg.membersextender.initCharts = function() {
	$('.post-chart').each(function(idx) {
		var context = this.getContext("2d");
		
		this.style.width='100%';
		this.width  = this.offsetWidth;

		var data = {
		    labels: $(this).data('labels'),
		    datasets: [
		        {
		            fillColor: "rgba(130,21,26,0.8)",
		            strokeColor: "rgba(130,21,26,0.8)",
		            highlightFill: "rgba(130,21,26,1)",
		            data: $(this).data('values')
		        }
		    ]
		};

		var chart = new Chart(context).Bar(data, elgg.membersextender.activity_options);
	});
}

// Chart.js options 
elgg.membersextender.activity_options = {
	scaleShowLabels: false,
	scaleShowGridLines: false,
	barShowStroke: false,
	barValueSpacing: 2,
	barDatasetSpacing: 1,
	tooltipFontSize: 12,
	// New option because apparently hiding the scale doesn't hide the lines..
	hideTheDamnYScale: true
};

// Init avatar hover
elgg.membersextender.initAvatarHover = function() {
	// Show/Hide user gallery hover info
	$('.members-gallery-avatar').hover(elgg.membersextender.hoverGalleryAvatar, function() {
		var $hoveravatar = $(this).data('hoveravatar');
		if ($hoveravatar) {
			$hoveravatar.fadeOut();
		}		
	});
}

// Main nav click
elgg.membersextender.mainNavClick = function(event) {
	event.preventDefault();
}

// Members gallery hover event
elgg.membersextender.hoverGalleryAvatar = function(event) {
	var $hoveravatar = $(this).data('hoveravatar') || null;

	if (!$hoveravatar) {
		var $hoveravatar = $(this).parent().find('.members-gallery-hover');
		$(this).data('hoveravatar', $hoveravatar);
	}

	$hoveravatar.css({"width": $(this).width() + 'px', "height": $(this).height() + 'px'}).fadeIn('fast').position({
		my: "left top",
		at: "left top",
		of: $(this)
	}).appendTo($(this));

	event.preventDefault();
}

elgg.membersextender.positionAchievements = function(hook, type, params, options) {
	var $avatar = params.sender.closest('div.elgg-avatar-medium');
	if ($avatar.length > 0 && $avatar.closest('.elgg-list-river').length == 0) {
		$avatar = params.avatar;
		$_this = params.sender
		$menu = params.menu;

		var offset = $avatar.offset();
		var top = offset.top + $avatar.height() + 'px';
		var left = offset.left + 'px';
	
		$menu.appendTo('body')
			.css('position', 'absolute')
			.css("top", top)
			.css("left", left)
			.fadeIn('normal');

		return false;
	}
	return true;
}

elgg.register_hook_handler('init', 'system', elgg.membersextender.init);
elgg.register_hook_handler('generic_populated', 'modules', elgg.membersextender.init);
elgg.register_hook_handler('content_loaded', 'drilltrate', elgg.membersextender.initAvatarHover);
elgg.register_hook_handler('setPopupLocation', 'achievements', elgg.membersextender.positionAchievements);
