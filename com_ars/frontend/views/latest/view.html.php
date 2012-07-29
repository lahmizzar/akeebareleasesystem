<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once( dirname(__FILE__).'/../view.html.php' );

class ArsViewLatest extends ArsViewBase
{
	function onDisplay()
	{
		require_once JPATH_COMPONENT.'/helpers/html.php';
		require_once JPATH_COMPONENT.'/helpers/router.php';

		// Load visual group definitions
		$vgModel = JModel::getInstance('Vgroups','ArsModel');
		$vgModel->setState('frontend',1);
		$raw = $vgModel->getItemList(true);
		$vgroups = array('0' => '');
		if(!empty($raw)) foreach($raw as $r) {
			$vgroups[$r->id] = $r->title;
		}
		$this->assign('vgroups', $vgroups);
		
		// Add RSS links
		$app = JFactory::getApplication();
		$params = $app->getPageParameters('com_ars');
		$show_feed = $params->get('show_feed_link');
		if($show_feed)
		{
			if ($params->get('show_page_title', 1))
			{
				$title = $params->get('page_title');
			}
			else
			{
				$title = JText::_('ARS_VIEW_BROWSE_TITLE');
			}


			$feed = 'index.php?option=com_ars&view=browse&format=feed';
			$rss = array(
				'type' => 'application/rss+xml',
				'title' => $title.' (RSS)'
			);
			$atom = array(
				'type' => 'application/atom+xml',
				'title' => $title.' (Atom)'
			);
			// add the links
			$document = JFactory::getDocument();
			$document->addHeadLink(AKRouter::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(AKRouter::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}
	}
}