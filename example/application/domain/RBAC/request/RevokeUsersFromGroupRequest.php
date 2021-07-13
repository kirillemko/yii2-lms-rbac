<?php

namespace App\domain\RBAC\request;


use kirillemko\yci\models\request\RequestModel;

class RevokeUsersFromGroupRequest extends RequestModel
{

    public $group_id;
    public $ids;

    public function rules() : array
    {
        return [
            [['group_id', 'ids'], 'required'],
            [['group_id'], 'integer'],
            [['ids'], 'each', 'rule' => ['integer']],
        ];
    }

}