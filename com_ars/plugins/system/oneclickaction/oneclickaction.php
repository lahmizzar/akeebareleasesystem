<?php
/**
 * @package AkeebaBackup
 * @subpackage OneClickAction
 * @copyright Copyright (c)2011-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 * @since 3.3
 */

defined('_JEXEC') or die();

// PHP version check
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	$version = '5.0.0'; // all bets are off!
}
if(!version_compare($version, '5.0.0', '>=')) return;

jimport('joomla.application.plugin');

class plgSystemOneclickaction extends JPlugin
{
	/**
	 * Handles the onAfterInitialise event in Joomla!, logging in the user using
	 * the one time password and forwarding him to the action URL
	 */
	public function onAfterInitialise()
	{
		$app = JFactory::getApplication();
		
		// Only fire in administrator requests
		if(in_array($app->getName(),array('administrator','admin'))) {
			// Make sure it's an OneClickAction request
			$otp = JRequest::getCmd('oneclickaction','');
			if(empty($otp)) return;
			
			// Check that we do have a table!
			self::_checkInstallation();
			
			// Perform expiration control
			self::_expirationControl();
			
			// Make sure this OTP exists
			$db = JFactory::getDBO();
			if(version_compare(JVERSION, '1.6.0', 'ge')) {
				$sql = $db->getQuery(true)
					->select('*')
					->from($db->nq('#__oneclickaction_actions'))
					->where($db->nq('otp').' = '.$db->q($otp));
			} else {
				$sql = 'SELECT * FROM `#__oneclickaction_actions` WHERE `otp` = '.$db->quote($otp);
			}
			$db->setQuery($sql);
			$oca = $db->loadObject();
			if(empty($oca)) return;
			
			// Login the user
			$user = JFactory::getUser($oca->userid);
			jimport( 'joomla.user.authentication');
			$app = JFactory::getApplication();
			$authenticate = JAuthentication::getInstance();
			$response = new JAuthenticationResponse();
			if(defined('JAUTHENTICATE_STATUS_SUCCESS')) {
				$response->status = JAUTHENTICATE_STATUS_SUCCESS;
			} else {
				$response->status = JAuthentication::STATUS_SUCCESS;
			}
			$response->type = 'joomla';
			$response->username = $user->username;
			$response->email = $user->email;
			$response->fullname = $user->name;
			$response->error_message = '';
			
			JPluginHelper::importPlugin('user');
			$options = array();
			
			if(version_compare(JVERSION,'2.5.0','lt')) {
				$session = JFactory::getSession();
				$session->fork();
			}
			
			jimport('joomla.user.helper');
			$results = $app->triggerEvent('onLoginUser', array((array)$response, $options));
			
			if(version_compare(JVERSION,'1.7.0','ge')) {
				JFactory::getSession()->set('user', $user);
			}
			
			// Delete all similar OCA records
			if(version_compare(JVERSION, '1.6.0', 'ge')) {
				$sql = $db->getQuery(true)
					->delete($db->nq('#__oneclickaction_actions'))
					->where($db->nq('actionurl').' = '.$db->q($oca->actionurl));
			} else {
				$sql = 'DELETE FROM `#__oneclickaction_actions` WHERE `actionurl` = '.$db->quote($oca->actionurl);
			}
			$db->setQuery($sql);
			$db->query();
			
			// Forward to the requested URL
			$app->redirect($oca->actionurl);
			$app->close();
		}
	}
	
	public function onOneClickActionEnabled()
	{
		return true;
	}
	
	/**
	 * Adds a new action URL and returns an one time password to access it. This
	 * is meant to be callable directly.
	 * 
	 * @param int $userid The user ID to log in when the generated OTP is used
	 * @param string $actionurl The (relative) URL to redirect to, e.g. 'index.php?option=com_foobar'
	 * @param int $expireIn For how many seconds is this OTP valid. Default: 86400 (24 hours)
	 */
	public static function addAction($userid, $actionurl, $expireIn = 86400)
	{
		self::_checkInstallation();
		self::_expirationControl();
		
		$db = JFactory::getDBO();
		
		// Check that the action does not already exist
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$sql = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->nq('#__oneclickaction_actions'))
				->where($db->nq('actionurl').' = '.$db->q($actionurl))
				->where($db->nq('userid').' = '.$db->q($userid));
		} else {
			$db->setQuery('SELECT COUNT(*) FROM `#__oneclickaction_actions` WHERE `actionurl` = '.$db->quote($actionurl).' AND `userid` = '.$db->quote($userid));
		}
		$actionsCount = $db->loadResult();
		if($actionsCount) return '';
		
		// Create a randomized OTP
		jimport('joomla.user.helper');
		$expire = gmdate('Y-m-d H:i:s', time() + (int)$expireIn);
		$otp = JUserHelper::genRandomPassword(64);
		$otp = strtoupper($otp);
		
		// Insert the OTP and action to the database
		$object = (object)array(
			'userid'	=> $userid,
			'actionurl'	=> $actionurl,
			'otp'		=> $otp,
			'expiry'	=> $expire,
		);
		$db->insertObject('#__oneclickaction_actions', $object);
		
		
		// If a DB error occurs, return null
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			try {
				$db->query();
			} catch (Exception $e) {
				return null;
			}
		} else {
			$db->query();
			if($db->getErrorNum()) return null;
		}
		
		// All OK, return the OTP
		return $otp;
	}
	
	/**
	 * Checks that the installation is complete, i.e. the table is created.
	 */
	private static function _checkInstallation()
	{
		if(!$this->isMySQL()) return false;
		
		// @todo Move the SQL to the plugin package and do not run this on Joomla! 1.6 or later
		$db = JFactory::getDBO();
		$db->setQuery('DESCRIBE #__oneclickaction_actions');
		$test = $db->loadResult();
		if(is_null($test) || ($db->getError())) {
			$sql = <<<ENDSQL
CREATE TABLE `#__oneclickaction_actions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) unsigned NOT NULL,
  `actionurl` varchar(4000) NOT NULL,
  `otp` char(64) NOT NULL,
  `expiry` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
ENDSQL;
			$db->setQuery($sql);
			$result = $db->query();
			return $result;
		}
		return true;
	}
	
	private static function _expirationControl()
	{
		$db = JFactory::getDBO();
		
		$now = gmdate('Y-m-d H:i:s');
		$now = $db->quote($now);
		
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$sql = $db->getQuery(true)
				->delete($db->nq('#__oneclickaction_actions'))
				->where($db->nq('expiry').' <= '.$now);
		} else {
			$sql = 'DELETE FROM `#__oneclickaction_actions` WHERE `expiry` <= '.$now;
		}
		$db->setQuery($sql);
		$db->query();
	}
	
	private function isMySQL()
	{
		$db = JFactory::getDbo();
		return strtolower(substr($db->name, 0, 5)) == 'mysql';
	}
}