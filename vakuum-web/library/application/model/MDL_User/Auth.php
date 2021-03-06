<?php
require_once('Common.php');

/**
 * User Authentication Model Class
 *
 * @package User Authentication
 */
class MDL_User_Auth extends MDL_User_Common
{
	/**
	 * Do user authentication and set Session
	 *
	 * @return boolean whether succeeded to login
	 */
	public static function login($user_info)
	{
		//Compute the hash code of the password submited by user
		$user_password_hash = self::passwordEncrypt($user_info['user_password']);

		//Do datebase query to get the hash code of the password
		$db = BFL_Database :: getInstance();
		$stmt = $db->factory('select `user_id`,`user_password` from '.DB_TABLE_USER.' WHERE `user_name` = :user_name');
		$stmt->bindParam(':user_name', $user_info['user_name']);
		$stmt->execute();
		$rs = $stmt->fetch();
		if (empty($rs))
			throw new MDL_Exception_User_Passport(MDL_Exception_User_Passport::INVALID_USER_NAME);

		if ($rs['user_password'] != $user_password_hash)
			throw new MDL_Exception_User_Passport(MDL_Exception_User_Passport::INVALID_USER_PASSWORD);

		//Set user session
		MDL_ACL::getInstance()->setUser(new MDL_User($rs['user_id']));
	}

	/**
	 * Logout and clear current session
	 */
	public static function logout()
	{
		MDL_ACL::getInstance()->resetSession();
	}

	public static function getLoginedUserInformation()
	{
		$acl = MDL_ACL::getInstance();
		$user_id = $acl->getUser()->getID();
		if ($user_id !=0)
		{
			try
			{
				$user = MDL_User_Detail::getUser($user_id);

				BFL_Register :: setVar('personal',$user);
				if (isset($user['identity']))
					$acl->setIdentity($user['identity']);

				if (isset($user['preference']))
				{
					$preference = BFL_XML::XML2Array($user['preference']);
					BFL_Register::setVar('user_preference',$preference);
				}
			}
			catch(MDL_Exception_User $e)
			{
				if ($e->testDesc(MDL_Exception_User::FIELD_USER,MDL_Exception_User::INVALID_USER_ID))
				{

					$acl->resetSession();
					$acl->initialize(SESSION_PREFIX,'guest');
				}
				else
				{
					throw $e;
				}
			}
		}
	}
}