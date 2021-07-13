var RequestMixin = {
    data: {
        loading: false,
    },
    methods: {
        sendRequest({url,method,loadingObject=this,params, data}){
            if( !url ) return;
            if(  loadingObject &&  typeof loadingObject.loading !== 'undefined' ){
                loadingObject.loading = true;
            }

            return new Promise((resolve, reject) => {
                axios({
                    method: method,
                    url: url,
                    data: data,
                    params: params
                })
                    .then((response) => {
                        if( response.data.success ){
                            resolve(response.data.data);
                        } else {
                            this.$notify.error({
                                title: 'Ошибка',
                                message: 'Что-то пошло не так'
                            });
                            reject(response.data.errors);
                        }
                    })
                    .catch((error) => {
                        this.$notify.error({
                            title: 'Ошибка',
                            message: 'Что-то пошло не так'
                        });
                        reject(error);
                    })
                    .finally(() => {
                        loadingObject.loading = false;
                    })
            });
        }
    }
}