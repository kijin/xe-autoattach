<?php

if (!defined('__XE__')) exit();

/**
 * Add attachments after inserting or updating a new document or comment.
 */
if ($called_position === 'after_module_proc' && preg_match('/^proc[A-Z][a-z0-9_]+Insert(Document|Comment)$/', $this->act, $matches))
{
	include_once dirname(__FILE__) . '/autoattach.class.php';
	XEAutoAttachAddon::setConfig($addon_info);
	
	if (strtolower($matches[1]) === 'document')
	{
		if ($addon_info->new_documents !== 'N')
		{
			XEAutoAttachAddon::procDocument($this->get('document_srl'), true);
		}
	}
	else
	{
		if ($addon_info->new_comments !== 'N')
		{
			XEAutoAttachAddon::procComment($this->get('comment_srl'), true);
		}
	}
}

/**
 * Add attachments before viewing an old document with missing attachments.
 */
if ($called_position === 'before_module_proc' && $addon_info->old_documents === 'Y' && preg_match('/^disp[A-Z][a-z0-9_]+Content(?:View)?$/', $this->act))
{
	include_once dirname(__FILE__) . '/autoattach.class.php';
	XEAutoAttachAddon::setConfig($addon_info);
	
	if (Context::get('document_srl'))
	{
		XEAutoAttachAddon::procDocument(Context::get('document_srl'));
	}
}
