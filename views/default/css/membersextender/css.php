<?php
/**
 * Members-Extender CSS
 * 
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2012
 * @link http://www.thinkglobalschool.com/
 * 
 */
?>
/** Members Gallery Tweaks **/
.members-gallery-hover {
	display: none;
	height: auto;
	z-index: 2;
	background-color: rgba(0,0,0,.7);
	position: absolute;
	cursor: pointer;
}

.elgg-avatar > .elgg-icon-hover-menu  {
	z-index: 4;
}

.members-gallery-hover .members-gallery-hover-name {
	font-weight: bold;
	font-size: 1.1em;
	width: 90px;
	padding: 5px;
	display: block;
	color: #FFFFFF;
	text-decoration: none;
}

/** Member hover location **/
.members-gallery-hover .members-gallery-hover-location {
	color: #FFFFFF;
	padding-left: 5px;
	padding-right: 5px;
	width: 90px;
	display: block;
	font-size: 0.8em;
	font-style: italic;
}

.members-gallery-hover .members-gallery-hover-location a,
.members-gallery-hover .members-gallery-hover-location a:hover, {
	color: #FFFFFF !important;
	text-decoration: none;
}

/** Fix achievements hover icon **/
.achievements-hover-badge-medium {
    border-bottom-right-radius: 0 !important;
    border-top-left-radius: 0 !important;
    border-top-right-radius: 5px;
    bottom: 0;
    top: auto !important;
	z-index: 4;
}