<?php

/**
 * @file autoattach.addon.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * 
 * This addon automatically finds unattached images in documents and comments
 * and converts them into real attachments. This can be useful because
 * many users cannot distinguish between external images and real attachments,
 * but the website administrator must be careful because self-hosting all
 * images may result in copyright infringement.
 */
if (!defined('__XE__')) exit();

/**
 * Add attachments after inserting or updating a new document or comment.
 */
if ($called_position === 'after_module_proc' && preg_match('/^proc[A-Z][a-z0-9_]+Insert(Document|Comment)$/', $this->act, $matches))
{
	if (strtolower($matches[1]) === 'document')
	{
		if ($addon_info->new_documents !== 'N')
		{
			include_once dirname(__FILE__) . '/autoattach.class.php';
			XEAutoAttachAddon::setConfig($addon_info);
			XEAutoAttachAddon::procDocument($this->get('document_srl'), true);
		}
	}
	else
	{
		if ($addon_info->new_comments !== 'N')
		{
			include_once dirname(__FILE__) . '/autoattach.class.php';
			XEAutoAttachAddon::setConfig($addon_info);
			XEAutoAttachAddon::procComment($this->get('comment_srl'), true);
		}
	}
}

/**
 * Add attachments before viewing an old document with missing attachments.
 */
if ($called_position === 'before_module_proc' && $addon_info->old_documents === 'Y' && preg_match('/^disp[A-Z][a-z0-9_]+Content(?:View)?$/', $this->act))
{
	if (Context::get('document_srl'))
	{
		include_once dirname(__FILE__) . '/autoattach.class.php';
		XEAutoAttachAddon::setConfig($addon_info);
		XEAutoAttachAddon::procDocument(Context::get('document_srl'));
	}
}
