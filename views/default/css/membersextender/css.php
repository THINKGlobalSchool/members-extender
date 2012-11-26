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
	z-index: 3;
}

.members-gallery-hover .members-gallery-hover-name {
	font-weight: bold;
	font-size: 1.4em;
	padding: 5px;
	display: block;
}

.members-gallery-hover .members-gallery-hover-name a,
.members-gallery-hover .members-gallery-hover-name a:hover,
.members-gallery-hover .members-gallery-hover-name a:active {
	color: #FFFFFF;
	text-decoration: none;
}