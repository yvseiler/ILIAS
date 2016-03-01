<?php
require_once 'Services/Repository/classes/class.ilObjectPluginAccess.php';

class ilObjBuildingBlockPoolAccess extends ilObjectPluginAccess {
	const TABLE_TITLE = "rep_obj_bbpool";

	/**
	* Checks wether a user may invoke a command or not
	* (this method is called by ilAccessHandler::checkAccess)
	*
	* Please do not check any preconditions handled by
	* ilConditionHandler here. Also don't do usual RBAC checks.
	*
	* @param        string        $a_cmd                command (not permission!)
	* @param        string        $a_permission        permission
	* @param        int                $a_ref_id                reference id
	* @param        int                $a_obj_id                object id
	* @param        int                $a_user_id                user id (default is current user)
	*
	* @return        boolean                true, if everything is ok
	*/
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "") {
		global $ilUser, $ilAccess;

		/*
		* This Routine is called within ilAccess::checkAccessOfUser::doStatusCheck.
		* We rely on standart ilAccess::checkAccessOfUser procedure, i.e. return true here, except when the object is offline,
		* then redirect to read-permission check.
		*/

		if ($a_user_id == "") {
			$a_user_id = $ilUser->getId();
		}
		switch ($a_permission) {
			case "read":
			case "visible":
				if (!static::checkOnline($a_obj_id) &&
						(!$ilAccess->checkAccessOfUser($a_user_id, "write", "", $a_ref_id) &&
							!$ilAccess->checkAccessOfUser($a_user_id, "edit_building_blocks", "", $a_ref_id)
						)
					)
				{
					return false;
				}
				break;
		}

		return true;
	}

	/**
	* Check online status of object
	*/
	static public function checkOnline($a_id) {
		global $ilDB;

		$set = $ilDB->query("SELECT is_online FROM ".static::TABLE_TITLE
			." WHERE obj_id = ".$ilDB->quote($a_id, "integer")
			);

		$rec  = $ilDB->fetchAssoc($set);
		return (boolean) $rec["is_online"];
	}
}