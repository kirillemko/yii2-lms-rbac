<?php

namespace App\domain\RBAC\request;


use kirillemko\yci\models\request\RequestModel;

class DeleteGroupRequest extends RequestModel
{

    public $group_id;

    public function rules() : array
    {
        return [
            [['group_id'], 'required'],
            [['group_id'], 'integer'],
        ];
    }

}