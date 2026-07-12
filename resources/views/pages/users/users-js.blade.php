<script>
    const { createApp, ref, reactive, onMounted } = Vue

    createApp({
        setup(props) {
            const BASE_URL = "{{ env('APP_URL') }}";
            const customAxios = axios.create({
                baseURL: BASE_URL,
                withCredentials: true,
                xsrfCookieName: 'XSRF-TOKEN',
                xsrfHeaderName: 'X-XSRF-TOKEN'
            });

            const token = document.querySelector('meta[name="csrf-token"]');

            // Set header CSRF token untuk semua request Axios
            if (token) {
                customAxios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
            }
            
            const validation = ref([])
            const loadingUser = ref(false)
            const listUser = ref([])
            const searchUserValue = ref('')

            const postUser = reactive({
                userName: '',
                userUserName: '',
                userEmail: '',
                userPassword: '',
                is_active: true,
                role: '',
            })

            const editUserData = reactive({
                userId: '',
                userName: '',
                userUserName: '',
                userEmail: '',
                userPassword: '',
                is_active: true,
                role: '',
            })

            async function showAllUser() {
                loadingUser.value = true
                try {
                    const response = await customAxios.get('/users/show-all')
                    listUser.value = response.data
                } catch (error) {
                    console.error('Failed to fetch users:', error)
                } finally {
                    loadingUser.value = false
                }
            }

            async function searchUser() {
                loadingUser.value = true
                try {
                    const response = await customAxios.get('/users/show-all', {
                        params: { search: searchUserValue.value }
                    })
                    listUser.value = response.data
                } catch (error) {
                    console.error('Failed to fetch users:', error)
                } finally {
                    loadingUser.value = false
                }
            }

            function storeUser() {
                if (!postUser.userName.trim() || !postUser.userEmail.trim() || !postUser.userPassword.trim()) {
                    swalError("Semua field wajib diisi");
                    return;
                }
                customAxios.post('/users/store', {
                    userName: postUser.userName,
                    userUserName: postUser.userUserName,
                    userEmail: postUser.userEmail,
                    userPassword: postUser.userPassword,
                    is_active: postUser.is_active,
                    role: postUser.role,
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#add-user-modal').modal('hide');
                        postUser.userName = '';
                        postUser.userUserName = '';
                        postUser.userEmail = '';
                        postUser.userPassword = '';
                        showAllUser();
                    } else {
                        swalError(response.data.message);
                    }
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            async function editUser(userId) {
                $('#edit-user-modal').modal('show');
                try {
                    const response = await customAxios.get('/users/' + userId);
                    editUserData.userId = response.data.id;
                    editUserData.userName = response.data.name;
                    editUserData.userUserName = response.data.username;
                    editUserData.userEmail = response.data.email;
                    editUserData.is_active = !!response.data.is_active;
                    editUserData.userPassword = '';
                    // Ambil role pertama (jika ada)
                    editUserData.role = response.data.roles && response.data.roles.length > 0
                        ? response.data.roles[0].name
                        : '';
                } catch (error) {
                    console.error('Failed to fetch user:', error);
                }
            }

            function updateUser() {
                customAxios.put('/users/' + editUserData.userId, {
                    userName: editUserData.userName,
                    userUserName: editUserData.userUserName,
                    userEmail: editUserData.userEmail,
                    userPassword: editUserData.userPassword,
                    is_active: editUserData.is_active,
                    role: editUserData.role,
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#edit-user-modal').modal('hide');
                        showAllUser();
                    } else {
                        swalError(response.data.message);
                    }
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            function deleteUser(userId) {
                swal({
                    title: 'Apa kamu yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                })
                    .then((willDelete) => {
                        if (willDelete) {
                            customAxios.delete('/users/' + userId).then((response) => {
                                if (response.data.status == true) {
                                    swalSuccess(response.data.message);
                                    showAllUser();
                                } else {
                                    swalError(response.data.message);
                                }
                            }).catch(error => {
                                validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                                swalError(error);
                            });
                        }
                    });
            }

            const listRole = ref([]);

            async function getAllRole() {
                try {
                    const response = await customAxios.get('/roles/show-all');
                    listRole.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch roles:', error);
                }
            }

            onMounted(() => {
                showAllUser();
                getAllRole();
            });

            return {
                validation,
                loadingUser,
                listUser,
                searchUserValue,
                postUser,
                editUserData,
                showAllUser,
                searchUser,
                storeUser,
                editUser,
                updateUser,
                deleteUser,
                listRole,
                getAllRole,
            }
        }
    }).mount('#app')
</script>