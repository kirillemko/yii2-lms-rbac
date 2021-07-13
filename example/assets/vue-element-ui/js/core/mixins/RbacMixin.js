var RbacMixin = {
    data: {
        rbac_permissions: [],
    },
    methods: {
        loadRbacPermissions(){
            return this.sendRequest({
                url:'/pm/rbac/getMyPermissions'
            })
                .then(data => {
                    this.rbac_permissions = data.permissions
                })
        },
    },
    computed: {
        can(){
            return permission => {
                return this.rbac_permissions.indexOf(permission) !== -1;
            }
        }
    },
}