<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace kirillemko\yiilmsrbac;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\rbac\Assignment;
use yii\rbac\BaseManager;


class LMSDbManager extends BaseManager
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbManager object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db = 'db';

    /**
     * @var array Массив id ролей, который нельзя удалить или модифицировать
     */
    public $systemRoleIds = [];

    /**
     * @var array Массив свойств, которые нужно вернуть при поиске людей
     */
    public $userFieldsToSend = ['id', 'email'];
    /**
     * @var array Массив свойств, пок оторым производим поиск людей
     */
    public $userSearchFields = ['email', 'last_name', 'first_name', 'midle_name'];

    /**
     * @var array ID админ группы, для которой создать первоначальные пермишенны в миграции
     */
    public $adminGroupIdForInitMigration = 1;

    public $permissionTable = '{{%permissions}}';
    public $groupsTable = '{{%groups}}';
    public $usersGroupsTable = '{{%users_groups}}';
    public $groupPermissionsTable = '{{%groups_permissions}}';


    /**
     * @var CacheInterface|array|string the cache used to improve RBAC performance. This can be one of the following:
     *
     * - an application component ID (e.g. `cache`)
     * - a configuration array
     * - a [[\yii\caching\Cache]] object
     *
     * When this is not set, it means caching is not enabled.
     *
     * Note that by enabling RBAC cache, all auth items, rules and auth item parent-child relationships will
     * be cached and loaded into memory. This will improve the performance of RBAC permission check. However,
     * it does require extra memory and as a result may not be appropriate if your RBAC system contains too many
     * auth items. You should seek other RBAC implementations (e.g. RBAC based on Redis storage) in this case.
     *
     * Also note that if you modify RBAC items, rules or parent-child relationships from outside of this component,
     * you have to manually call [[invalidateCache()]] to ensure data consistency.
     *
     * @since 2.0.3
     */
    public $cache;
    /**
     * @var string the key used to store RBAC data in cache
     * @see cache
     * @since 2.0.3
     */
    public $cacheKey = 'rbac';




    private $_checkAccessPermissions = [];

    /**
     * Initializes the application component.
     * This method overrides the parent implementation by establishing the database connection.
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
        if ($this->cache !== null) {
            $this->cache = Instance::ensure($this->cache, 'yii\caching\CacheInterface');
        }
    }


    /**
     * {@inheritdoc}
     */
    public function checkAccess($userId, $permissionName, $params = [])
    {
        if (isset($this->_checkAccessPermissions[(string) $userId])) {
            $permissions = $this->_checkAccessPermissions[(string) $userId];
        } else {
            $permissions = $this->getAssignmentsFromCache($userId);
            if( !is_array($permissions) ){
                $permissions = $this->getAssignments($userId);
            }
            $this->_checkAccessPermissions[(string) $userId] = $permissions;
        }

        if ($this->hasNoAssignments($permissions)) {
            return false;
        }

        Yii::debug("Checking permission: $permissionName", __METHOD__);

        if (isset($this->_checkAccessPermissions[(string) $userId][$permissionName]) || in_array($permissionName, $this->defaultRoles)) {
            return true;
        }

        return false;
    }




    /**
     * {@inheritdoc}
     * The roles returned by this method include the roles assigned via [[$defaultRoles]].
     */
    public function getRolesByUser($userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }
        $query = (new Query())
            ->from(['ug' => $this->usersGroupsTable])
            ->where(['user_id' => (string) $userId]);

        $roles = $this->getDefaultRoleInstances();
        foreach ($query->all($this->db) as $row) {
            $roles[$row['name']] = $this->createRole($row['name']);
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildRoles($roleName)
    {
        $role = $this->getRole($roleName);
        if ($role === null) {
            throw new InvalidArgumentException("Role \"$roleName\" not found.");
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionsByRole($roleName)
    {
        $query = (new Query())
            ->from(['g' => $this->groupsTable])
            ->innerJoin(['gp' => $this->groupPermissionsTable], 'g.id=gp.group_id')
            ->innerJoin(['p' => $this->permissionTable], 'gp.permission_key=p.key')
            ->where(['g.name' => $roleName]);

        $permissions = [];
        foreach ($query->all($this->db) as $row) {
            $permissions[$row['permission_key']] = new Assignment([
                'roleName' => $row['permission_key']
            ]);
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionsByUser($userId)
    {
        return [];
    }




    /**
     * {@inheritdoc}
     */
    public function getAssignment($roleName, $userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return null;
        }

        $row = (new Query())
            ->from(['ug' => $this->usersGroupsTable])
            ->innerJoin(['gp' => $this->groupPermissionsTable], 'ug.group_id=gp.group_id')
            ->where(['ug.user_id' => (string) $userId, 'gp.permission_key' => $roleName])
            ->one();

        if ( !$row ) {
            return null;
        }

        return new Assignment([
            'roleName' => $row['permission_key'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignments($userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        $query = (new Query())
            ->from(['ug' => $this->usersGroupsTable])
            ->innerJoin(['gp' => $this->groupPermissionsTable], 'ug.group_id=gp.group_id')
            ->innerJoin(['p' => $this->permissionTable], 'gp.permission_key=p.key')
            ->where(['user_id' => (string) $userId]);

        $assignments = [];
        foreach ($query->all($this->db) as $row) {
            $assignments[$row['permission_key']] = new Assignment([
                'roleName' => $row['permission_key'],
            ]);
        }

        return $assignments;
    }







//    public function invalidateCache($userId)
//    {
//        if ($this->cache !== null) {
//            $this->cache->delete($this->cacheKey . $userId);
//            $this->_checkAccessPermissions[(string) $userId] = null;
//        }
//    }

    public function getAssignmentsFromCache($user_id)
    {
        if (!$user_id || !$this->cache instanceof CacheInterface) {
            return null;
        }

        $permissions = $this->cache->get($this->cacheKey . $user_id);
        if( $permissions ) {
            return $permissions;
        }

        $permissions = $this->getAssignments($user_id);
        if( $permissions ){
            $this->cache->set($this->cacheKey . $user_id, $permissions);
        }

        return $permissions;
    }




    /**
     * Check whether $userId is empty.
     * @param mixed $userId
     * @return bool
     */
    private function isEmptyUserId($userId)
    {
        return !isset($userId) || $userId === '';
    }











    public function getGroupsWithPermissionsArray()
    {
        $groups = (new Query())
            ->from(['g' => $this->groupsTable])
            ->leftJoin(['gp' => $this->groupPermissionsTable], 'g.id=gp.group_id')
            ->all($this->db);

        $groupsToReturn = [];
        foreach ($groups as $group) {
            if( !isset($groupsToReturn[$group['id']]) ){
                $groupsToReturn[$group['id']] = [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'permissions' => [],
                    'deletable' => !in_array($group['id'], $this->systemRoleIds)
                ];
            }
            if( $group['permission_key'] ){
                $groupsToReturn[$group['id']]['permissions'][$group['permission_key']] = !!$group['permission_key'];
            }
        }

        return $groupsToReturn;
    }

    public function getPermissionsArray()
    {

        $permissions = (new Query())
            ->from(['p' => $this->permissionTable])
            ->all($this->db);

        foreach ($permissions as &$permission) {
            try{
                $permission['name'] = \Yii::t('RBAC', $permission['name']);
            } catch (\Exception $e){

            }
        }

        return $permissions;
    }

    public function setGroupPermission($groupId, $permissionKey, $value)
    {

        if( $value ){
            return !!$this->db->createCommand()
                ->insert($this->groupPermissionsTable, [
                    'group_id' => $groupId,
                    'permission_key' => $permissionKey
                ])->execute();
        } else {
            $this->db->createCommand()
                ->delete($this->groupPermissionsTable, [
                    'group_id' => $groupId,
                    'permission_key' => $permissionKey
                ])->execute();
            return true;
        }
    }

    public function saveGroup($groupId, $groupName, $groupDesc)
    {
        if( !$groupName || !$groupDesc ){
            return false;
        }

        if( !$groupId ){
            return !!$this->db->createCommand()
                ->insert($this->groupsTable, [
                    'name' => $groupName,
                    'description' => $groupDesc
                ])->execute();
        }

        if( in_array($groupId, $this->systemRoleIds) ){
            return false;
        }

        return !!$this->db->createCommand()
            ->update($this->groupsTable, [
                'name' => $groupName,
                'description' => $groupDesc
            ], ['id' => $groupId])->execute();

    }

    public function deleteGroup($groupId)
    {
        if( in_array($groupId, $this->systemRoleIds) ){
            return false;
        }

        $this->db->createCommand()
            ->delete($this->groupsTable, [
                'id' => $groupId
            ])->execute();

        return true;
    }


    public function getGroupUsers($groupId, $search=null, $paginate=true)
    {
        $usersGroups = (new Query())
            ->from($this->usersGroupsTable)
            ->where(['group_id' => $groupId])
            ->all();

        $userIds = array_map(function($row){ return $row['user_id'];}, $usersGroups);

        $query = Yii::$app->user->identityClass::find()
            ->andWhere(['IN' , 'id', $userIds]);

        return $this->prepareUsersToSend($query, $search, $paginate);
    }

    public function getLmsUsers($search=null, $paginate=true)
    {
        $query = Yii::$app->user->identityClass::find();
        return $this->prepareUsersToSend($query, $search, $paginate);
    }

    private function prepareUsersToSend(ActiveQuery $query, $search=null, $paginate=true)
    {
        if( $search ){
            $search = explode(' ', $search);
            $conditions = ['or'];
            foreach( $search as $searchWord ) {
                foreach ($this->userSearchFields as $userSearchField) {
                    $conditions[] = ['like', $userSearchField, $searchWord];
                }
            }
            $query = $query->andWhere($conditions);
        }

        $paginator = null;
        if( $paginate ){
            $paginator = new Pagination(['totalCount' => $query->count()]);
            $query = $query
                ->offset($paginator->offset)
                ->limit($paginator->limit);
        }

        $users = $query ->all();


        $toReturn = [];
        foreach ($users as $user) {
            $toReturn[] = $user->getAttributes($this->userFieldsToSend);
        }

        return [
            'paginator' => $paginator,
            'users' => $toReturn
        ];
    }

    public function revokeUsersFromGroup($groupId, $userIds)
    {
        $this->db->createCommand()
            ->delete($this->usersGroupsTable, [
                'AND',
                ['group_id' => $groupId],
                ['in', 'user_id', $userIds]
            ])->execute();
        return true;
    }

    public function assignUsersToGroup($groupId, $userIds)
    {
        foreach ($userIds as $userId) {
            try {
                $this->db->createCommand()
                    ->insert($this->usersGroupsTable, [
                        'group_id' => $groupId,
                        'user_id' => $userId
                    ])->execute();
            } catch(\Exception $e){

            }
        }
        return true;
    }








    protected function getItem($name) { throw new InvalidConfigException("Method not supported"); }
    protected function addItem($item) { throw new InvalidConfigException("Method not supported"); }
    protected function removeItem($item) { throw new InvalidConfigException("Method not supported"); }
    protected function updateItem($name, $item) { throw new InvalidConfigException("Method not supported"); }
    protected function addRule($rule) { throw new InvalidConfigException("Method not supported"); }
    protected function updateRule($name, $rule) { throw new InvalidConfigException("Method not supported"); }
    protected function removeRule($rule) { throw new InvalidConfigException("Method not supported"); }
    protected function getItems($type) { throw new InvalidConfigException("Method not supported"); }
    public function getRule($name) { throw new InvalidConfigException("Method not supported"); }
    public function getRules() { throw new InvalidConfigException("Method not supported"); }
    public function canAddChild($parent, $child) { throw new InvalidConfigException("Method not supported"); }
    public function addChild($parent, $child) { throw new InvalidConfigException("Method not supported"); }
    public function removeChild($parent, $child) { throw new InvalidConfigException("Method not supported"); }
    public function removeChildren($parent) { throw new InvalidConfigException("Method not supported"); }
    public function hasChild($parent, $child) { throw new InvalidConfigException("Method not supported"); }
    public function getChildren($name) { throw new InvalidConfigException("Method not supported"); }
    public function assign($role, $userId) { throw new InvalidConfigException("Method not supported"); }
    public function revoke($role, $userId) { throw new InvalidConfigException("Method not supported"); }
    public function revokeAll($userId) { throw new InvalidConfigException("Method not supported"); }
    public function removeAll() { throw new InvalidConfigException("Method not supported"); }
    public function removeAllPermissions() { throw new InvalidConfigException("Method not supported"); }
    public function removeAllRoles() { throw new InvalidConfigException("Method not supported"); }
    public function removeAllRules() { throw new InvalidConfigException("Method not supported"); }
    public function removeAllAssignments() { throw new InvalidConfigException("Method not supported"); }
    public function getUserIdsByRole($roleName) { throw new InvalidConfigException("Method not supported"); }
}
