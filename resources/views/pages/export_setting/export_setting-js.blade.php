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
            const loadingExportSetting = ref(false)

            const postExportSetting = reactive({
                ttdNamaJabatan: '',
                ttdNamaLengkap: '',
            })

            async function showExportSetting() {
                loadingExportSetting.value = true
                try {
                    const response = await customAxios.get('/export-setting/show')
                    postExportSetting.ttdNamaJabatan = response.data.ttd_nama_jabatan || '';
                    postExportSetting.ttdNamaLengkap = response.data.ttd_nama_lengkap || '';
                } catch (error) {
                    console.error('Failed to fetch export settings:', error)
                } finally {
                    loadingExportSetting.value = false
                }
            }

            function storeExportSetting() {
                if (!postExportSetting.ttdNamaJabatan.trim() || !postExportSetting.ttdNamaLengkap.trim()) {
                    swalError("Semua field wajib diisi");
                    return;
                }
                // console.log('Posting export setting:', postExportSetting.ttdNamaJabatan, postExportSetting.ttdNamaLengkap);
                customAxios.post('/export-setting/store', {
                    ttd_nama_jabatan: postExportSetting.ttdNamaJabatan,
                    ttd_nama_lengkap: postExportSetting.ttdNamaLengkap,
                }).then((response) => {
                    if (response.data.status == true) {
                        swalSuccess(response.data.message);
                        $('#add-export-setting-modal').modal('hide');
                        postExportSetting.ttdNamaJabatan = '';
                        postExportSetting.ttdNamaLengkap = '';
                        showExportSetting();
                    } else {
                        swalError(response.data.message);
                    }
                }).catch(error => {
                    validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                    swalError(error);
                });
            }

            onMounted(() => {
                showExportSetting();
            });

            return {
                validation,
                loadingExportSetting,
                postExportSetting,
                showExportSetting,
                storeExportSetting,
            }
        }
    }).mount('#app')
</script>