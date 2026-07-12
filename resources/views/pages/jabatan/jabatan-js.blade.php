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
            const loadingJabatan = ref(false)
            const listJabatan = ref([])
            const searchJabatanValue = ref('')

            const postJabatan = reactive({
                nama_jabatan: '',
                deskripsi: '',
                is_show: true,
            })

            const editJabatanData = reactive({
                id: '',
                nama_jabatan: '',
                deskripsi: '',
                is_show: true,
            })

            async function showAllJabatan() {
                loadingJabatan.value = true
                try {
                    const response = await customAxios.get('/jabatan/show-all')
                    listJabatan.value = response.data;
                    // Inisialisasi ulang Sortable setelah data di-render
                    setTimeout(initSortable, 200);
                } catch (error) {
                    console.error('Failed to fetch jabatan:', error)
                } finally {
                    loadingJabatan.value = false
                }
            }

            async function searchJabatan() {
                loadingJabatan.value = true
                try {
                    const response = await customAxios.get('/jabatan/show-all', {
                        params: { search: searchJabatanValue.value }
                    })
                    listJabatan.value = response.data
                } catch (error) {
                    console.error('Failed to fetch jabatan:', error)
                } finally {
                    loadingJabatan.value = false
                }
            }

            function storeJabatan() {
                if (!postJabatan.nama_jabatan.trim()) {
                    swalError("Nama jabatan wajib diisi");
                    return;
                }
                customAxios.post('/jabatan/store', {
                    nama_jabatan: postJabatan.nama_jabatan,
                    deskripsi: postJabatan.deskripsi,
                    is_show: postJabatan.is_show,
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#add-jabatan-modal').modal('hide');
                        postJabatan.nama_jabatan = '';
                        postJabatan.deskripsi = '';
                        showAllJabatan();
                    } else {
                        swalError(response.data.message);
                    }
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            async function editJabatan(id) {
                $('#edit-jabatan-modal').modal('show');
                try {
                    const response = await customAxios.get('/jabatan/' + id);
                    editJabatanData.id = response.data.id;
                    editJabatanData.nama_jabatan = response.data.nama_jabatan;
                    editJabatanData.deskripsi = response.data.deskripsi;
                    editJabatanData.is_show = !!response.data.is_show;
                } catch (error) {
                    console.error('Failed to fetch jabatan:', error);
                }
            }

            function updateJabatan() {
                customAxios.put('/jabatan/' + editJabatanData.id, {
                    nama_jabatan: editJabatanData.nama_jabatan,
                    deskripsi: editJabatanData.deskripsi,
                    is_show: editJabatanData.is_show,
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#edit-jabatan-modal').modal('hide');
                        showAllJabatan();
                    } else {
                        swalError(response.data.message);
                    }
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            function deleteJabatan(id) {
                swal({
                    title: 'Apa kamu yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                })
                    .then((willDelete) => {
                        if (willDelete) {
                            customAxios.delete('/jabatan/' + id).then((response) => {
                                if (response.data.status == true) {
                                    swalSuccess(response.data.message);
                                    showAllJabatan();
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

            function initSortable() {
                const tbody = document.getElementById('jabatan-table-body');
                if (tbody) {
                    // Destroy instance jika sudah ada
                    if (tbody._sortable) {
                        tbody._sortable.destroy();
                    }
                    tbody._sortable = Sortable.create(tbody, {
                        animation: 150,
                        handle: '.handle',
                        onEnd: function (evt) {
                            const ids = Array.from(tbody.children).map(tr => tr.getAttribute('data-id'));
                            customAxios.post('/jabatan/update-order', { ids })
                                .then(response => {
                                    if (response.data.status) {
                                        swalSuccess('Urutan jabatan berhasil diupdate');
                                        showAllJabatan();
                                    } else {
                                        swalError('Gagal update urutan');
                                    }
                                })
                                .catch(() => swalError('Gagal update urutan'));
                        }
                    });
                }
            }

            onMounted(() => {
                showAllJabatan();
            });

            return {
                validation,
                loadingJabatan,
                listJabatan,
                searchJabatanValue,
                postJabatan,
                editJabatanData,
                showAllJabatan,
                searchJabatan,
                storeJabatan,
                editJabatan,
                updateJabatan,
                deleteJabatan,
            }
        }
    }).mount('#app')
</script>