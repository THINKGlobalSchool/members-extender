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

.elgg-river-item .achievements-hover-badge-medium {
	-webkit-border-top-left-radius: 5px !important;
	-webkit-border-bottom-right-radius: 5px !important;
	-moz-border-radius-topleft: 5px !important;
	-moz-border-radius-bottomright: 5px !important;
	border-top-left-radius: 5px !important;
	border-bottom-right-radius: 5px !important;
    top: 0 !important;
}

/** Engagement Link **/
.members-view-engagment {
	display: block;
	text-align: right;
}

.elgg-menu-item-view-engagement,
.elgg-menu-item-view-engagement:hover {
	float: right !important;
	background: none repeat scroll 0% 0% transparent !important;
	border: 0px none !important;
}

.elgg-menu-item-view-engagement > a,
.elgg-menu-filter > li.elgg-menu-item-view-engagement.elgg-state-selected > a {
	color: #91131E !important;
	text-transform: none !important;
	font-family: "Lucida Grande",​Arial,​Tahoma,​Verdana,​sans-serif;
	font-size: 1.1em;
	background: none;
}
.elgg-menu-item-view-engagement > a:hover,
.elgg-menu-filter > li.elgg-menu-item-view-engagement > a:hover {
	color: #2D3F46 !important;
    text-decoration: underline;
    text-transform: none !important;
	background: none !important;
}

/** Engagement Table **/
.member-engagement-table {

}

.member-engagement-table th {
	font-weight: bold;
}

.member-engagement-table td.member-engagement-avatar {
	width: 25px;
}

.member-engagement-table td.member-engagement-link {

}

.member-engagement-table td.member-engagement-status.status-online {
	font-weight: bold;
	color: #347C17;
}

.member-engagement-table td.member-engagement-status.status-offline,
.member-engagement-table td.empty-value {
	color: #999;
	font-style: italic;
}

.member-engagement-table td.member-engagement-post {
	padding-top: 5px;
}

.member-engagement-table tbody tr > td {
	vertical-align: middle;
}



/** Admin area **/
.members-sub-categories-container {
	display: none;
}

.members-show-subcategories {
	display: block;
}

