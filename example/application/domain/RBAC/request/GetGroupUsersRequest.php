<?php

namespace App\domain\RBAC\request;


use kirillemko\yci\models\request\RequestModel;

class GetGroupUsersRequest extends RequestModel
{

    public $group_id;
    public $search;

    public function rules() : array
    {
        return [
            [['group_id'], 'required'],
            [['group_id'], 'integer'],
            [['search'], 'string', 'max' => 128],
        ];
    }

}