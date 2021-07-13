<?php

namespace App\domain\RBAC\request;


use kirillemko\yci\models\request\RequestModel;

class SaveGroupRequest extends RequestModel
{

    public $group_id;
    public $group_name;
    public $group_description;

    public function rules() : array
    {
        return [
            [['group_name', 'group_description'], 'required'],
            [['group_id'], 'integer'],
        ];
    }

}