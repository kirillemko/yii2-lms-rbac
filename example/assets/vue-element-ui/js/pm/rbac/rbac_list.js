
new Vue({
    el: '#vueAppRbacList',
    delimiters: ['${', '}$'],
    mixins: [RequestMixin, DebounceMixin, RbacMixin],

    // async beforeCreate(){
    //     await this.loadRbacPermissions();
    //     console.log('sadfsdaf');
    // },

    data: {
        loading: false,
        groups: [],
        permissions: [],
        permissionsSearch: null,

        addGroupModal: {
            show: false,
            loading: false,
            group: {
                id: null,
                name: null,
                description:null,
            },
        },

        manageUsersModal:{
            show: false,
            loading:false,
            search: null,
            page: 1,
            groupId: null,
            nextPage: false,
            groupUsers: [],
            groupUsersTotal: null,
        },

        lmsUsersModal:{
            show: false,
            loading:false,
            search: null,
            page: 1,
            nextPage: false,
            users: [],
        }
    },

    mounted() {
        this.loadRbacPermissions();
        this.getTableData();
    },
    computed: {
        filteredPermissions() {
            if( !this.permissionsSearch ){
                return this.permissions;
            }
            return this.permissions.filter(permission => {
                return permission.name.toLowerCase().indexOf(this.permissionsSearch.toLowerCase()) > -1
            })
        },
    },
    watch: { },
    methods: {
        getTableData(){
            this.groups = [];
            this.permissions = [];

            this.sendRequest({
                url:'/pm/rbac/getElementsForAclTable'
            })
                .then(data => {
                    this.permissions = data.permissions;
                    this.groups = data.groups;
                })
        },

        permissionChanged(group, permissionKey){
            this.sendRequest({
                method:'post',
                url:'/pm/rbac/saveAclGroupPermission',
                data:{
                    group_id: group.id,
                    permission_key: permissionKey,
                    value: group.permissions[permissionKey],
                }

            })
                .then(data => {
                    this.$notify.success({
                        title: 'Сохранено',
                        message: 'Элемент успешно сохранен'
                    });
                })
        },

        updateGroupShow(groupId){
            if( !groupId ){
                this.addGroupModal.group.id = null;
                this.addGroupModal.group.name = null;
                this.addGroupModal.group.description = null;
            } else {
                this.addGroupModal.group.id = this.groups[groupId].id;
                this.addGroupModal.group.name = this.groups[groupId].name;
                this.addGroupModal.group.description = this.groups[groupId].description;
            }
            this.addGroupModal.show = true;
        },

        updateGroup(){
            this.sendRequest({
                loadingObject: this.addGroupModal,
                method:'post',
                url:'/pm/rbac/saveGroup',
                data:{
                    group_id: this.addGroupModal.group.id,
                    group_name: this.addGroupModal.group.name,
                    group_description: this.addGroupModal.group.description,
                }

            })
                .then(data => {
                    this.$notify.success({
                        title: 'Сохранено',
                        message: 'Группа успешно сохранена'
                    });
                })
                .finally(()=>{
                    this.getTableData();
                    this.addGroupModal.show = false;
                })
        },

        deleteGroup(groupId){
            this.sendRequest({
                loadingObject: this.addGroupModal,
                method:'post',
                url:'/pm/rbac/deleteGroup',
                data:{
                    group_id: this.addGroupModal.group.id,
                }

            })
                .then(data => {
                    this.$notify.success({
                        title: 'Сохранено',
                        message: 'Группа успешно удалена'
                    });
                })
                .finally(()=>{
                    this.getTableData();
                    this.addGroupModal.show = false;
                })
        },



        manageUsersModalShow(groupId){
            this.manageUsersModal.page = 1;
            this.manageUsersModal.groupUsersTotal = null;
            this.manageUsersModal.groupId = groupId;
            this.manageUsersModal.groupUsers = [];
            this.manageUsersModal.show = true;
            this.loadGroupUsers(groupId);
        },
        manageUsersModalLoadPage(page){
            this.manageUsersModal.groupUsers = [];
            if( page === 'next' ){
                this.manageUsersModal.page++;
            } else if( page === 'prev' ){
                this.manageUsersModal.page--;
            }
            this.loadGroupUsers();
        },
        manageUsersModalDebounceSearch(){
            this.manageUsersModal.page = 1;
            this.debounce(this.loadGroupUsers, 500)
        },
        loadGroupUsers(){
            this.sendRequest({
                loadingObject: this.manageUsersModal,
                method:'get',
                url:'/pm/rbac/getGroupUsers',
                params:{
                    group_id: this.manageUsersModal.groupId,
                    page: this.manageUsersModal.page,
                    search: this.manageUsersModal.search,
                }

            })
                .then(data => {
                    this.manageUsersModal.groupUsers = data.users;
                    this.manageUsersModal.groupUsersTotal = data.paginator.totalCount;
                    this.manageUsersModal.nextPage = (this.manageUsersModal.page * data.paginator.defaultPageSize) < data.paginator.totalCount;
                })
        },
        manageUsersModalDeleteSelectedUsers(){
            let ids = this.$refs.manageUsersModalUsersTable.selection.map(user=>user.id);
            this.sendRequest({
                loadingObject: this.manageUsersModal,
                method:'post',
                url:'/pm/rbac/revokeUsersFromGroup',
                data:{
                    group_id: this.manageUsersModal.groupId,
                    ids: ids,
                }

            })
                .then(data => {
                    this.$notify.success({
                        title: 'Сохранено',
                        message: 'Пользователи успешно удалены'
                    });
                    this.loadGroupUsers();
                })
        },






        lmsUsersModalShow(){
            this.lmsUsersModal.page = 1;
            this.lmsUsersModal.users = [];
            this.lmsUsersModal.show = true;
            this.loadLmsUsers();
        },
        lmsUsersModalLoadPage(page){
            this.lmsUsersModal.users = [];
            if( page === 'next' ){
                this.lmsUsersModal.page++;
            } else if( page === 'prev' ){
                this.lmsUsersModal.page--;
            }
            this.loadLmsUsers();
        },
        lmsUsersModalDebounceSearch(){
            this.lmsUsersModal.page = 1;
            this.debounce(this.loadLmsUsers, 500)
        },
        loadLmsUsers(){
            this.sendRequest({
                loadingObject: this.lmsUsersModal,
                method:'get',
                url:'/pm/rbac/getLmsUsers',
                params:{
                    page: this.lmsUsersModal.page,
                    search: this.lmsUsersModal.search,
                }

            })
                .then(data => {
                    this.lmsUsersModal.users = data.users;
                    this.lmsUsersModal.nextPage = (this.lmsUsersModal.page * data.paginator.defaultPageSize) < data.paginator.totalCount;
                })
        },
        addSelectedUsersToGroup(){
            let ids = this.$refs.lmsUsersModalUsersTable.selection.map(user=>user.id);
            this.sendRequest({
                loadingObject: this.lmsUsersModal,
                method:'post',
                url:'/pm/rbac/assignUsersToGroup',
                data:{
                    group_id: this.manageUsersModal.groupId,
                    ids: ids,
                }

            })
                .then(data => {
                    this.$notify.success({
                        title: 'Сохранено',
                        message: 'Пользователи успешно добавлены'
                    });
                    this.$refs.lmsUsersModalUsersTable.clearSelection();
                    this.loadGroupUsers();
                })
        }



    }
});