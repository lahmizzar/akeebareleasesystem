<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$model = $this->getModel();

$base_folder = rtrim(JURI::base(), '/');
if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        

?>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" id="option" value="com_ars" />
	<input type="hidden" name="view" id="view" value="categories" />
	<input type="hidden" name="task" id="task" value="display" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />
<table class="adminlist">
	<thead>
		<tr>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ) + 1; ?>);" />
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'LBL_CATEGORIES_TITLE', 'title', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="160">
				<?php echo JHTML::_('grid.sort', 'LBL_CATEGORIES_VGROUP', 'vgroup_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="160">
				<?php echo JHTML::_('grid.sort', 'LBL_CATEGORIES_TYPE', 'type', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'Ordering', 'ordering', $this->lists->order_Dir, $this->lists->order); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<th width="150">
				<?php if(version_compare(JVERSION,'1.6.0','ge')):?>
				<?php echo JHTML::_('grid.sort', 'JFIELD_ACCESS_LABEL', 'access', $this->lists->order_Dir, $this->lists->order); ?>
				<?php else: ?>
				<?php echo JHTML::_('grid.sort', 'ACCESS', 'access', $this->lists->order_Dir, $this->lists->order); ?>
				<?php endif; ?>
			</th>
			<th width="80">
				<?php if(version_compare(JVERSION,'1.6.0','ge')):?>
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order); ?>
				<?php else: ?>
				<?php echo JHTML::_('grid.sort', 'PUBLISHED', 'published', $this->lists->order_Dir, $this->lists->order); ?>
				<?php endif; ?>
			</th>
			<?if(version_compare(JVERSION, '1.6.0', 'ge')):?>
			<th>
				<?php echo JHTML::_('grid.sort', 'JFIELD_LANGUAGE_LABEL', 'language',$this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<?endif;?>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="text" name="title" id="title"
					value="<?php echo $this->escape($this->lists->fltTitle);?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.title.value='';this.form.submit();">
					<?php echo JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<?php echo ArsHelperSelect::vgroups($this->lists->fltVgroups, 'vgroup', array('onchange'=>'this.form.submit();')); ?>
			</td>
			<td>
				<?php echo ArsHelperSelect::categorytypes($this->lists->fltType, 'type', array('onchange'=>'this.form.submit();')) ?>
			</td>
			<td></td>
			<td></td>
			<td>
				<?php echo ArsHelperSelect::published($this->lists->fltPublished, 'published', array('onchange'=>'this.form.submit();')) ?>
			</td>
			<?if(version_compare(JVERSION, '1.6.0', 'ge')):?>
			<td></td>
			<?endif;?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="20">
				<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php if($count = count($this->items)): ?>
		<?php
			$i = 0;
			$m = 1;
			foreach($this->items as $item):
			$m = 1 - $m;

			$model->reset();
			$model->setId($item->id);
			$checkedout = $model->isCheckedOut();

			$ordering = $this->lists->order == 'ordering';

			// This is a stupid requirement of JHTML. Go figure!
			switch($item->access) {
				case 0: $item->groupname = JText::_('public'); break;
				case 1: $item->groupname = JText::_('registered'); break;
				case 2: $item->groupname = JText::_('special'); break;
			}

			$icon = $base_folder.'/media/com_ars/icons/' . (empty($item->groups) ? 'unlocked_16.png' : 'locked_16.png');
		?>
		<tr class="row<?php echo $m?>">
			<td>
				<?php echo JHTML::_('grid.id', $i, $item->id, $checkedout); ?>
			</td>
			<td>
				<a href="index.php?option=com_ars&view=categories&task=edit&id=<?php echo (int)$item->id ?>">
					<?php echo htmlentities($item->title, ENT_COMPAT, 'UTF-8') ?>
				</a>
				<br/>
				<span class="cat-directory-name"><?php echo htmlentities($item->directory, ENT_COMPAT, 'UTF-8') ?></span>
			</td>
			<td>
				<?php echo $item->vgroup_id ? $this->vgroups[$item->vgroup_id] : '&mdash;'; ?>
			</td>
			<td>
				<span class="category-type-<?php echo $item->type ?>">
				<?php echo JText::_('LBL_CATEGORIES_TYPE_'.  strtoupper($item->type)); ?>
				</span>
			</td>
			<td class="order">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<td>
				<img src="<?php echo $icon ?>" width="16" height="16" align="left" />
				<span class="ars-access">
				&nbsp; 
				<?php if(version_compare(JVERSION,'1.6.0','ge')):?>
				<?php echo ArsHelperSelect::renderaccess($item->access); ?>
				<?php else: ?>
				<?php echo JHTML::_('grid.access', $item, $i); ?>
				<?php endif; ?>
				</span>
			</td>
			<td>
				<?php echo JHTML::_('grid.published', $item, $i); ?>
			</td>
			<?if(version_compare(JVERSION, '1.6.0', 'ge')):?>
			<td><?php echo $item->language ?></td>
			<?endif;?>
		</tr>
	<?php
			$i++;
			endforeach;
	?>
	<?php else : ?>
		<tr>
			<td colspan="10" align="center"><?php echo JText::_('LBL_ARS_NOITEMS') ?></td>
		</tr>
	<?php endif ?>
	</tbody>
</table>

</form>