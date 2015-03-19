<?php
/**
 * Drilltrate CSS
 *
 * @package Members-Extender
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Jeff Tilson
 * @copyright THINK Global School 2010 - 2015
 * @link http://www.thinkglobalschool.org/
 * 
 */
?>

.drilltrate-menu-container {
    width: auto;
}

.drilltrate-menu-container ul {
	position: relative;
}

.drilltrate-menu-container > ul > li label { 
	margin-right: 7px;
}

.drilltrate-menu-container > ul > li {
	display: inline-block;
}

.drilltrate-menu-main.elgg-menu-filter, 
.drilltrate-menu-container .elgg-child-menu.elgg-menu-filter {}

.drilltrate-menu-container .elgg-child-menu.elgg-menu-filter {
	clear: both;
}

.drilltrate-menu-main.elgg-menu-filter li {}

.drilltrate-menu-container .elgg-child-menu li {
	padding: 3px 10px 0 0;
}

.drilltrate-menu-container .elgg-child-menu li.elgg-state-selected a {
	text-decoration: underline;
	font-weight: bold;
	color: #555;
}

.drilltrate-menu-main.elgg-menu-filter li:last-child,
.drilltrate-menu-container .elgg-child-menu.elgg-menu-filter li:last-child {}

.drilltrate-menu-main.elgg-menu-filter li a, 
.drilltrate-menu-container .elgg-child-menu.elgg-menu-filter li a {}

.drilltrate-menu-container ul.elgg-child-menu {
	display: none;
}

.drilltrate-menu-main ul.elgg-child-menu {
}

.drilltrate-menu-container  li input {
	font-size: 90%;
	height: 24px;
	width: 92px;
	border: 1px solid #AAAAAA;
}

ul.drilltrate-menu-extras {
	border-top: 1px dotted #CCC;
	overflow: auto;
}

ul.drilltrate-menu-advanced {
	border-top: 1px dotted #CCC;
	padding-top: 4px;
	display: none;
}

ul.drilltrate-menu-extras > li {
	float: left;
	margin-bottom: 0 !important;
}

ul.drilltrate-menu-extras > li.elgg-menu-item-sort {
	float: right;
}

.drilltrate-show-advanced.advanced-off:after,
.drilltrate-sort.descending:after {
	content: " ▼";
	font-size: 9px;
	text-decoration: none;
}

.drilltrate-show-advanced.advanced-on:after,
.drilltrate-sort.ascending:after  {
	content: " ▲";
	text-decoration: none;
}

.drilltrate-content-container {
	margin-bottom: 40px;
	margin-top: 10px;
}

.drilltrate-content-container .elgg-ajax-loader {
	margin-top: 20px;
}

span.drilltrate-clear-icon {
	position: relative;
}

span.drilltrate-clear-icon span {
	position: absolute;
	display: block;
	top: 4px;
	right: 5px;
	width: 9px;
	height: 9px;
	background: url(<?php echo elgg_get_site_url(); ?>mod/tgstheme/_graphics/x-sprite.png) no-repeat 0 0;
	cursor: pointer;
	display: none;
}

span.drilltrate-clear-icon span:hover {
	background-position: 0px -11px;
}

span.drilltrate-clear-icon input {
	padding-right: 16px;
}

/** Fix for typeaheadtags **/
.drilltrate-menu-container .elgg-input-tags-parent {
	display: inline-block;
	vertical-align: middle;
}

.drilltrate-menu-container .elgg-input-tags-parent .as-selections {
	padding: 1px;
}

.drilltrate-menu-container .elgg-input-tags {
	height: 22px;
}

.drilltrate-menu-container .elgg-input-tags-parent ul.as-selections li.as-selection-item.typeaheadtags-help-button {
	height: 12px;
	margin: 2px 4px 0 2px;
	padding: 3px !important;
	text-align: center;
	width: 12px;
	font-size: 10px;
}

.drilltrate-menu-container .elgg-input-tags-parent ul.as-selections li.as-selection-item,
.drilltrate-menu-container .elgg-input-tags-parent ul.as-selections li.as-original input {
	font-size: 11px;
	padding-top: 2px;
	padding-bottom: 2px;
	line-height: 14px;
}

.drilltrate-menu-container .typeaheadtags-help-container {
	max-width: 550px;
}

.drilltrate-infinite-loader {
	bottom: 10px;
    left: 50%;
    position: absolute;
    width: 100px;
    margin-left: -50px;
}

/** Widget styles **/
.elgg-widget-content .drilltrate-menu-container {
	background: #FFF;
	padding: 0;
}

.elgg-widget-content .drilltrate-menu-container > ul > li label {
	display: inline-block;
	min-width: 36px;
}