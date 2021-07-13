<?php


use App\domain\RBAC\request\DeleteGroupRequest;
use App\domain\RBAC\request\GetGroupUsersRequest;
use App\domain\RBAC\request\GetLmsUsersRequest;
use App\domain\RBAC\request\RevokeUsersFromGroupRequest;
use App\domain\RBAC\request\SaveAclGroupPermissionRequest;
use App\domain\RBAC\request\SaveGroupRequest;
use App\domain\RBAC\permissions\RbacPermissionsEnum;


defined('BASEPATH') OR exit('No direct script access allowed');

require_once "BaseController.php";
/**
 */
class Rbac extends BaseController
{
    protected $accessPermissions = [
        'index' =>   [],

        //////////////  REST /////////////////
//        'getUsers' =>                       [self::ROLE_ADMIN],

    ];


    public function index()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::SEE_ALL) ){
            $this->action_not_allowed();
        }
        $this->renderView('rbac/rbac_list');
    }



    ///////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////     REST     /////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////

    public function getMyPermissions()
    {
        $permissions = Yii::$app->authManager->getAssignments(Yii::$app->user->id);
        $array = [];
        foreach ($permissions as $permission) {
            $array[] = $permission->roleName;
        }
        $this->return_json_success([
            'permissions' => $array,
        ]);
    }


    public function getElementsForAclTable()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::SEE_ALL) ){
            $this->action_not_allowed();
        }
        $groups = Yii::$app->authManager->getGroupsWithPermissionsArray();
        $permissions = Yii::$app->authManager->getPermissionsArray();

        $this->return_json_success([
            'groups' => $groups,
            'permissions' => $permissions,
        ]);
    }

    public function saveAclGroupPermission()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::MANAGE) ){
            $this->action_not_allowed();
        }
        $request = new SaveAclGroupPermissionRequest();

        if( !Yii::$app->authManager->setGroupPermission($request->group_id, $request->permission_key, $request->value) ){
            $this->return_json_error();
            return;
        }

        $this->return_json_success();
    }

    public function saveGroup()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::MANAGE_GROUPS) ){
            $this->action_not_allowed();
        }
        $request = new SaveGroupRequest();

        if( !Yii::$app->authManager->saveGroup($request->group_id, $request->group_name, $request->group_description) ){
            $this->return_json_error();
            return;
        }

        $this->return_json_success();
    }

    public function deleteGroup()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::MANAGE_GROUPS) ){
            $this->action_not_allowed();
        }
        $request = new DeleteGroupRequest();

        if( !Yii::$app->authManager->deleteGroup($request->group_id) ){
            $this->return_json_error();
            return;
        }

        $this->return_json_success();
    }


    public function getGroupUsers()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::SEE_ALL) ){
            $this->action_not_allowed();
        }
        $request = new GetGroupUsersRequest();
        $data = Yii::$app->authManager->getGroupUsers($request->group_id, $request->search);
        $this->return_json_success($data);
    }

    public function getLmsUsers()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::SEE_ALL) ){
            $this->action_not_allowed();
        }
        $request = new GetLmsUsersRequest();
        $data = Yii::$app->authManager->getLmsUsers($request->search);
        $this->return_json_success($data);
    }


    public function revokeUsersFromGroup()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::MANAGE_GROUPS) ){
            $this->action_not_allowed();
        }
        $request = new RevokeUsersFromGroupRequest();
        Yii::$app->authManager->revokeUsersFromGroup($request->group_id, $request->ids);
        $this->return_json_success();
    }
    public function assignUsersToGroup()
    {
        if( !Yii::$app->user->can(RbacPermissionsEnum::MANAGE_GROUPS) ){
            $this->action_not_allowed();
        }
        $request = new RevokeUsersFromGroupRequest();
        Yii::$app->authManager->assignUsersToGroup($request->group_id, $request->ids);
        $this->return_json_success();
    }



}
