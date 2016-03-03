<?php

/**
 * @file autoattach.class.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * 
 * This addon automatically finds unattached images in documents and comments
 * and converts them into real attachments. This can be useful because
 * many users cannot distinguish between external images and real attachments,
 * but the website administrator must be careful because self-hosting all
 * images may result in copyright infringement.
 */
class XEAutoAttachAddon
{
	/**
	 * Addon configuration is cached here.
	 */
	protected static $config;
	
	/**
	 * Set the timeout for remote requests.
	 */
	protected static $image_timeout = 2;
	protected static $total_timeout = 20;
	
	/**
	 * Set addon configuration.
	 * 
	 * @param object $config
	 * @return void
	 */
	public static function setConfig($config)
	{
		self::$config = $config;
	}
	
	/**
	 * Process a document.
	 * 
	 * @param int $document_srl
	 * @return bool
	 */
	public static function procDocument($document_srl = 0, $get_fresh_object = false)
	{
		// Does the document exist?
		if (!$document_srl) return false;
		
		// Get the document.
		if ($get_fresh_object)
		{
			$output = executeQuery('addons.autoattach.getDocument', (object)array('document_srl' => $document_srl));
			if (!$output || !$output->data) return false;
			$oDocument = $output->data;
		}
		else
		{
			$oCachedDocument = getModel('document')->getDocument($document_srl);
			if (!$oCachedDocument || !$oCachedDocument->document_srl) return false;
			$oDocument = (object)array(
				'document_srl' => $oCachedDocument->get('document_srl'),
				'module_srl' => $oCachedDocument->get('module_srl'),
				'content' => $oCachedDocument->get('content'),
				'uploaded_count' => $oCachedDocument->get('uploaded_count'),
			);
		}
		
		// Check if the content has unattached images.
		$content = $oDocument->content;
		$images = self::getImages($content);
		if (!count($images)) return false;
		
		// Begin a transaction.
		$oDB = DB::getInstance();
		$oDB->begin();
		
		// Download and replace images.
		$count = self::replaceImages($content, $images, $oDocument->module_srl, $document_srl);
		if (!$count) return false;
		
		// Update the document.
		$output = executeQuery('addons.autoattach.updateDocument', (object)array(
			'content' => $content,
			'uploaded_count' => $oDocument->uploaded_count + $count,
			'document_srl' => $document_srl,
		));
		if (!$output)
		{
			$oDB->rollback();
			return false;
		}
		
		// Commit!
		$oDB->commit();
		return true;
	}
	
	/**
	 * Process a comment.
	 * 
	 * @param int $comment_srl
	 * @return bool
	 */
	public static function procComment($comment_srl = 0, $get_fresh_object = false)
	{
		// Does the comment exist?
		if (!$comment_srl) return false;
		
		// Get the comment.
		if ($get_fresh_object)
		{
			$output = executeQuery('addons.autoattach.getComment', (object)array('comment_srl' => $comment_srl));
			if (!$output || !$output->data) return false;
			$oComment = $output->data;
		}
		else
		{
			$oCachedComment = getModel('comment')->getComment($comment_srl);
			if (!$oCachedComment || !$oCachedComment->comment_srl) return false;
			$oComment = (object)array(
				'comment_srl' => $oCachedComment->get('comment_srl'),
				'module_srl' => $oCachedComment->get('module_srl'),
				'content' => $oCachedComment->get('content'),
				'uploaded_count' => $oCachedComment->get('uploaded_count'),
			);
		}
		
		// Check if the content has unattached images.
		$content = $oComment->content;
		$images = self::getImages($content);
		if (!count($images)) return false;
		
		// Begin a transaction.
		$oDB = DB::getInstance();
		$oDB->begin();
		
		// Download and replace images.
		$count = self::replaceImages($content, $images, $oComment->module_srl, $comment_srl);
		if (!$count) return false;
		
		// Update the comment.
		$output = executeQuery('addons.autoattach.updateComment', (object)array(
			'content' => $content,
			'uploaded_count' => $oComment->uploaded_count + $count,
			'comment_srl' => $comment_srl,
		));
		if (!$output)
		{
			$oDB->rollback();
			return false;
		}
		
		// Commit!
		$oDB->commit();
		return true;
	}
	
	/**
	 * Get images from HTML content.
	 * 
	 * @param string $content
	 * @return array
	 */
	protected static function getImages($content)
	{
		// Compile the list of except domains.
		if ($except_domains = self::$config->except_domains)
		{
			$except_domains = array_map('trim', explode(',', $except_domains));
		}
		else
		{
			$except_domains = array();
		}
		if ($default_url = Context::getDefaultUrl())
		{
			$except_domains[] = parse_url($default_url, PHP_URL_HOST);
		}
		$except_domains[] = $_SERVER['HTTP_HOST'];
		$except_domains = array_unique($except_domains);
		
		// Convert the list of except domains into a regular expression.
		$except_domains_regexp = array();
		foreach ($except_domains as $domain)
		{
			$except_domains_regexp[] = str_replace('\*\.', '[a-z0-9-]+\.', preg_quote($domain, '@'));
		}
		$except_domains_regexp = '@^https?://(' . implode('|', $except_domains_regexp) . ')/@i';
		
		// Find all images.
		if (preg_match_all('@<img\s[^>]+?src=(\'[^\']+?\'|"[^"]+?")[^>]+?>@i', $content, $matches, PREG_SET_ORDER))
		{
			$result = array();
			foreach ($matches as $match)
			{
				$image_url = htmlspecialchars_decode(trim($match[1], '\'"'));
				if (!preg_match('@^https?://@i', $image_url) || preg_match($except_domains_regexp, $image_url))
				{
					continue;
				}
				$result[] = array(
					'full_tag' => $match[0],
					'image_url_html' => trim($match[1], '\'"'),
					'image_url' => $image_url,
				);
			}
			return $result;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Replace images in HTML content.
	 * 
	 * @param string $content
	 * @param array $images
	 * @param int $module_srl
	 * @param int $target_srl
	 * @return bool
	 */
	protected static function replaceImages(&$content, $images, $module_srl, $target_srl)
	{
		// Count the time and the number of successful replacements.
		$start_time = microtime(true);
		$count = 0;
		
		// Loop over all images.
		foreach ($images as $image_info)
		{
			// Attempt to download the image.
			$temp_path = _XE_PATH_ . 'files/cache/autoattach/' . md5($image_info['image_url'] . microtime() . mt_rand());
			$status = FileHandler::getRemoteFile($image_info['image_url'], $temp_path, null, self::$image_timeout);
			if (!$status)
			{
				FileHandler::removeFile($temp_path);
				continue;
			}
			
			// Guess the correct filename and extension.
			$temp_name = self::cleanFilename($image_info['image_url']);
			if (preg_match('/^[0-9a-f]{32}$/', $temp_name))
			{
				$temp_name .= '.' . self::guessExtension($temp_path);
			}
			
			// Register as attachment.
			$oFile = getController('file')->insertFile(array(
				'name' => $temp_name,
				'tmp_name' => $temp_path,
			), $module_srl, $target_srl, 0, true);
			if (!$oFile)
			{
				FileHandler::removeFile($temp_path);
				continue;
			}
			
			// Update the content.
			$uploaded_filename = $oFile->get('uploaded_filename');
			$new_tag = str_replace($image_info['image_url_html'], htmlspecialchars($uploaded_filename), $image_info['full_tag']);
			$content = str_replace($image_info['full_tag'], $new_tag, $content);
			$count++;
			
			// If this is taking too long, stop now and try again later.
			if (microtime(true) - $start_time > self::$total_timeout)
			{
				break;
			}
		}
		
		// Update all files to be valid.
		getController('file')->setFilesValid($target_srl);
		
		// Return the count.
		return $count;
	}
	
	/**
	 * Clean a filename.
	 * 
	 * @param string $filename
	 * @return string
	 */
	protected static function cleanFilename($filename)
	{
		if (preg_match('@[^\\\\/\\?=]+\.(gif|jpe?g|png|bmp|svg)\b@i', urldecode($filename), $matches))
		{
			return $matches[0];
		}
		else
		{
			return md5($image_info['image_url'] . microtime() . mt_rand());
		}
	}
	
	/**
	 * Check the file type and return an appropriate extension.
	 * 
	 * @param string $filename
	 * @param string $default
	 * @return string
	 */
	protected static function guessExtension($filename, $default = 'jpg')
	{
		$image_info = @getimagesize($filename);
		if (!$image_info) return $default;
		
		switch ($image_info['mime'])
		{
			case 'image/gif': return 'gif';
			case 'image/jpeg': return 'jpg';
			case 'image/png': return 'png';
			case 'image/x-ms-bmp': return 'bmp';
			default: return $default;
		}
	}
}