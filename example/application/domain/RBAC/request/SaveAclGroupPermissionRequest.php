<?php

namespace App\domain\RBAC\request;


use kirillemko\yci\models\request\RequestModel;

class SaveAclGroupPermissionRequest extends RequestModel
{

    public $group_id;
    public $permission_key;
    public $value;

    public function rules() : array
    {
        return [
            [['group_id', 'permission_key', 'value', 'value'], 'required'],
            [['group_id'], 'integer'],
            [['value'], 'boolean'],
        ];
    }

}