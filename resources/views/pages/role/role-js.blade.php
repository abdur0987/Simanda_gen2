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
            
            //state validation
            const validation = ref([])

            const post = reactive({
                roleName: '',
            })

            const loadingRole = ref(false);
            const loadingPermission = ref(false);

            function storeRole() {
                let postRoleName = post.roleName;

                if (!post.roleName.trim()) {
                    swalError("Nama role tidak boleh kosong");
                    return;
                } else {
                    customAxios.post('/roles/store', {
                        roleName: postRoleName
                    }).then((response) => {

                        if (response.data.status == true) {
                            swalSuccess(response.data.message);
                            $('#add-modal').modal('hide');
                            post.roleName = '';
                        } else {
                            swalError(response.data.message);
                        }

                        showAll();

                    }).catch(error => {
                        console.log(error)
                        //assign state validation with error 
                        validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];

                        swalError(error);
                    })
                }

            }

            let listRole = ref([]);

            async function showAll() {
                loadingRole.value = true;
                try {
                    const response = await customAxios.get('roles/show-all');
                    listRole.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch roles:', error);
                } finally {
                    loadingRole.value = false;
                }
            }

            const edit = reactive({
                roleId: '',
                roleName: '',
            })

            async function editRole(roleId) {
                $('#edit-modal').modal('show');

                try {
                    const response = await customAxios.get('roles/' + roleId);
                    edit.roleId = response.data.id;
                    edit.roleName = response.data.name;
                } catch (error) {
                    console.error('Failed to fetch roles:', error);
                }
            }

            function updateRole() {
                let editRoleId = edit.roleId;
                let editRoleName = edit.roleName;

                customAxios.put('/roles/' + editRoleId, {
                    roleName: editRoleName
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#edit-modal').modal('hide');
                    } else {
                        swalError(response.data.message);
                    }

                    showAll();

                }).catch(error => {
                    console.log(error)
                    //assign state validation with error 
                    validation.value = error
                    swalError(error);
                })
            }

            function deleteRole(roleId) {
                swal({
                    title: 'Apa kamu yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                })
                    .then((willDelete) => {
                        if (willDelete) {
                            customAxios.delete('/roles/' + roleId).then((response) => {
                                if (response.data.status == true) {
                                    swalSuccess(response.data.message);
                                } else {
                                    swalError(response.data.message);
                                }

                                showAll();

                            }).catch(error => {
                                console.log(error)
                                //assign state validation with error 
                                validation.value = error

                                swalError(error);
                            })
                        }
                    });
            }

            const searchValue = ref('');

            async function searchRole() {
                loadingRole.value = true;

                try {
                    const response = await customAxios.get('/roles/show-all', {
                        params: { search: searchValue.value }
                    });
                    listRole.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch roles:', error);
                } finally {
                    loadingRole.value = false;
                }
            }

            // Permission State
            const postPermission = reactive({
                permissionName: '',
            });
            const editPermissionData = reactive({
                permissionId: '',
                permissionName: '',
            });
            const listPermission = ref([]);
            const searchPermissionValue = ref('');

            // Show All Permissions
            async function showAllPermission() {
                loadingPermission.value = true;
                try {
                    const response = await customAxios.get('/permissions/show-all');
                    listPermission.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch permissions:', error);
                } finally {
                    loadingPermission.value = false;
                }
            }

            // Store Permission
            function storePermission() {
                if (!postPermission.permissionName.trim()) {
                    swalError("Nama permission tidak boleh kosong");
                    return;
                }
                customAxios.post('/permissions/store', {
                    permissionName: postPermission.permissionName
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#add-permission-modal').modal('hide');
                        postPermission.permissionName = '';
                    } else {
                        swalError(response.data.message);
                    }
                    showAllPermission();
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            // Edit Permission
            async function editPermission(permissionId) {
                $('#edit-permission-modal').modal('show');
                try {
                    const response = await customAxios.get('/permissions/' + permissionId);
                    editPermissionData.permissionId = response.data.id;
                    editPermissionData.permissionName = response.data.name;
                } catch (error) {
                    console.error('Failed to fetch permission:', error);
                }
            }

            // Update Permission
            function updatePermission() {
                customAxios.put('/permissions/' + editPermissionData.permissionId, {
                    permissionName: editPermissionData.permissionName
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#edit-permission-modal').modal('hide');
                    } else {
                        swalError(response.data.message);
                    }
                    showAllPermission();
                    showAll();
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            // Delete Permission
            function deletePermission(permissionId) {
                swal({
                    title: 'Apa kamu yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                })
                    .then((willDelete) => {
                        if (willDelete) {
                            customAxios.delete('/permissions/' + permissionId).then((response) => {
                                if (response.data.status == true) {
                                    swalSuccess(response.data.message);
                                } else {
                                    swalError(response.data.message);
                                }
                                showAllPermission();
                            }).catch(error => {
                                validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                                swalError(error);
                            });
                        }
                    });
            }

            // Search Permission
            async function searchPermission() {
                loadingPermission.value = true;
                try {
                    const response = await customAxios.get('/permissions/show-all', {
                        params: { search: searchPermissionValue.value }
                    });
                    listPermission.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch permissions:', error);
                } finally {
                    loadingPermission.value = false;
                }
            }

            const selectedRoleId = ref('');
            const selectedPermissionId = ref('');

            function openAssignPermission(role) {
                selectedRoleId.value = role.id;
                selectedPermissionId.value = '';
                $('#assign-permission-modal').modal('show');
            }

            async function assignPermissionToRole() {
                if (!selectedPermissionId.value) {
                    swalError("Pilih permission terlebih dahulu");
                    return;
                }
                try {
                    const response = await customAxios.post(`/roles/${selectedRoleId.value}/assign-permission`, {
                        permission_id: selectedPermissionId.value
                    });
                    if (response.data.status) {
                        swalSuccess(response.data.message);
                        $('#assign-permission-modal').modal('hide');
                        showAll(); // refresh role list
                    } else {
                        swalError(response.data.message);
                    }
                } catch (error) {
                    swalError("Gagal menambah permission");
                }
            }

            async function removePermissionFromRole(roleId, permissionId) {
                swal({
                    title: 'Yakin hapus permission dari role?',
                    text: 'Permission akan dihapus dari role ini.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        customAxios.post(`/roles/${roleId}/remove-permission`, {
                            permission_id: permissionId
                        }).then((response) => {
                            if (response.data.status) {
                                swalSuccess(response.data.message);
                                showAll();
                            } else {
                                swalError(response.data.message);
                            }
                        }).catch(error => {
                            swalError('Gagal menghapus permission dari role');
                        });
                    }
                });
            }

            // Call showAllPermission on component mount
            onMounted(() => {
                showAll();
                showAllPermission();
            });

            return {
                validation,
                post,
                storeRole,
                listRole,
                showAll,
                edit,
                editRole,
                updateRole,
                deleteRole,
                searchValue,
                searchRole,
                postPermission,
                editPermissionData,
                listPermission,
                showAllPermission,
                storePermission,
                editPermission,
                updatePermission,
                deletePermission,
                searchPermissionValue,
                searchPermission,
                loadingRole,
                loadingPermission,
                openAssignPermission,
                assignPermissionToRole,
                selectedRoleId,
                selectedPermissionId,
                removePermissionFromRole,
            }
        }
    }).mount('#app')
</script>