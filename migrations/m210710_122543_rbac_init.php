<?php

use kirillemko\yiilmsrbac\LMSDbManager;
use yii\base\InvalidConfigException;


class m210710_122543_rbac_init extends \yii\db\Migration
{

    /**
     * @throws yii\base\InvalidConfigException
     * @return LMSDbManager
     */
    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof LMSDbManager) {
            throw new InvalidConfigException('You should configure LMS "authManager" component to use database before executing this migration.');
        }

        return $authManager;
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $authManager = $this->getAuthManager();
        $this->db = $authManager->db;

        $this->createTable($authManager->permissionTable, [
            'key' => $this->string(64)->notNull(),
            'name' => $this->string(128)->notNull(),
            'PRIMARY KEY ([[key]])',
        ], 'ENGINE=InnoDB');

        $this->createTable($authManager->groupPermissionsTable, [
            'group_id' => 'mediumint(8) unsigned not null',
            'permission_key' => $this->string(64)->notNull(),
            'PRIMARY KEY ([[group_id]], [[permission_key]])',
            'FOREIGN KEY ([[group_id]]) REFERENCES ' . $authManager->groupsTable . ' ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            'FOREIGN KEY ([[permission_key]]) REFERENCES ' . $authManager->permissionTable . ' ([[key]]) ON DELETE CASCADE ON UPDATE CASCADE'
        ], 'ENGINE=InnoDB');


        $this->batchInsert($authManager->permissionTable,
            ['key', 'name'],
            [
                ['permissions.see_all', 'Permissions. See'],
                ['permissions.manage', 'Permissions. Manage'],
                ['permissions.manage_groups', 'Permissions. Manage groups']
            ]
        );
        $this->batchInsert($authManager->groupPermissionsTable,
            ['group_id', 'permission_key'],
            [
                [$authManager->adminGroupIdForInitMigration, 'permissions.see_all'],
                [$authManager->adminGroupIdForInitMigration, 'permissions.manage'],
                [$authManager->adminGroupIdForInitMigration, 'permissions.manage_groups']
            ]
        );


    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $authManager = $this->getAuthManager();
        $this->db = $authManager->db;

        $this->dropTable($authManager->groupPermissionsTable);
        $this->dropTable($authManager->permissionTable);
    }

}
