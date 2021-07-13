<?php

namespace App\domain\RBAC\permissions;

class RbacPermissionsEnum
{
    const prefix = 'permissions.';

    const SEE_ALL =         self::prefix . 'see_all';
    const MANAGE =          self::prefix . 'manage';
    const MANAGE_GROUPS =   self::prefix . 'manage_groups';

}