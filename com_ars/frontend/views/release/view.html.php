<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

require_once( dirname(__FILE__).'/../view.html.php' );

class ArsViewRelease extends ArsViewBase
{
	function onDisplay()
	{
		// Add a breadcrumb if necessary
		$model = $this->getModel();

		$catModel = JModel::getInstance('Categories','ArsModel');
		$catModel->reset();
		$catModel->setId($model->item->category_id);
		$category = $catModel->getItem();

		$repoType = $category->type;
		
		require_once JPATH_COMPONENT.'/helpers/breadcrumbs.php';
		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($category->id, $category->title);
		ArsHelperBreadcrumbs::addRelease($model->item->id, $model->item->version);

		require_once JPATH_COMPONENT.'/helpers/html.php';

		$this->assignRef( 'category',	$category );


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
				$title = JText::_('ARS_VIEW_CATEGORY_TITLE');
			}


			$feed = 'index.php?option=com_ars&view=category&id='.$category->id.'&format=feed';
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
			$document->addHeadLink(JRoute::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(JRoute::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}
		
		// Cleanup for display
		$items	= $model->itemList;
		
		foreach ( $items as $item ) {
			$item->environments = ArsHelperHtml::getEnvironments( $item->environments );
		}
		
		$model->itemList = $items;
		
		$this->assign('cparams', $params);
	}
}