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
	// Show/Hide user gallery hover info
	$('.members-gallery-avatar').hover(elgg.membersextender.hoverGalleryAvatar, function() {
		var $hoveravatar = $(this).data('hoveravatar');
		if ($hoveravatar) {
			$hoveravatar.fadeOut();
		}		
	});
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
	if (params.sender.closest('div.elgg-avatar-medium').length > 0) {
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
elgg.register_hook_handler('setPopupLocation', 'achievements', elgg.membersextender.positionAchievements);
