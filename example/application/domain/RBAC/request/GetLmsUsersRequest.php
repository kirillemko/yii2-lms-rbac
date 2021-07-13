<?php

namespace App\domain\RBAC\request;


use kirillemko\yci\models\request\RequestModel;

class GetLmsUsersRequest extends RequestModel
{

    public $search;

    public function rules() : array
    {
        return [
            [['search'], 'string', 'max' => 128],
        ];
    }

}