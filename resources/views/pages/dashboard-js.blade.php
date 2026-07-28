<script>
    const { createApp, ref, reactive, onMounted, watch, nextTick } = Vue

    createApp({
        setup() {
            // Use the current browser origin so local ports/proxies do not depend on APP_URL.
            const BASE_URL = window.location.origin;
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
            const loadingAgenda = ref(false)
            const listAgenda = ref([])
            const pagination = ref({})
            const searchAgendaValue = ref('')
            const listJabatan = ref([])
            const filterTanggal = ref('');
            const filterJabatanIds = ref([]);
            const agendaCameraVideo = ref(null);
            const agendaCameraCanvas = ref(null);

            const uploadDocument = reactive({
                file: null,
                fileName: '',
                imagePreview: '',
                loading: false,
                cameraActive: false,
                cameraStream: null,
                reviewReady: false,
                warnings: [],
                review: emptyImportedAgendaReview(),
            })

            const postAgenda = reactive({
                nama_agenda: '',
                tanggal_agenda: moment().format('YYYY-MM-DD'),
                jam_mulai: '',
                jam_selesai: '',
                tempat_agenda: '',
                pakaian: '',
                sifat_agenda: 'publik',
                is_done: false,
                jabatan_ids: [],
                kehadiran: [''],
            })

            const editAgendaData = reactive({
                id: '',
                nama_agenda: '',
                tanggal_agenda: '',
                jam_mulai: '',
                jam_selesai: '',
                tempat_agenda: '',
                pakaian: '',
                sifat_agenda: 'publik',
                is_done: false,
                jabatan_ids: [],
                kehadiran: [''],
            })

            const editLinkDokData = reactive({
                id: '',
                nama_agenda: '',
                links: [{ nama_link: '', url: '' }],
            });

            async function showAllAgenda(resetFilterTanggal = false, resetFilterJabatan = false, page = 1) {
                loadingAgenda.value = true;

                if (resetFilterTanggal) {
                    filterTanggal.value = '';
                }

                if (resetFilterJabatan) {
                    filterJabatanIds.value = [];
                    $('#filter-jabatan').val(null).trigger('change');
                }

                try {
                    const response = await customAxios.get('/agenda/show-all', {
                        params: {
                            is_paginate: 1,
                            page
                        }
                    })
                    listAgenda.value = response.data.data
                    pagination.value = response.data
                } catch (error) {
                    console.error('Failed to fetch agenda:', error)
                } finally {
                    loadingAgenda.value = false
                }
            }

            async function searchAgenda() {
                loadingAgenda.value = true
                try {
                    const response = await customAxios.get('/agenda/show-all', {
                        params: { search: searchAgendaValue.value, tanggal: filterTanggal.value, jabatan_ids: filterJabatanIds.value }
                    })
                    listAgenda.value = response.data
                } catch (error) {
                    console.error('Failed to fetch agenda:', error)
                } finally {
                    loadingAgenda.value = false
                }
            }

            function storeAgenda() {
                customAxios.post('/agenda/store', postAgenda)
                    .then((response) => {
                        if (response.data.status) {
                            swalSuccess(response.data.message);
                            $('#add-agenda-modal').modal('hide');
                            Object.assign(postAgenda, {
                                nama_agenda: '',
                                tanggal_agenda: '',
                                jam_mulai: '',
                                jam_selesai: '',
                                tempat_agenda: '',
                                pakaian: '',
                                sifat_agenda: 'publik',
                                is_done: false,
                                jabatan_ids: [],
                                kehadiran: [''],
                            });
                            showAllAgenda();
                        } else {
                            swalError(response.data.message);
                        }
                    }).catch(error => {
                        validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                        swalError(error);
                    });
            }

            function selectAgendaDocument(event) {
                const file = event.target.files[0] || null;
                resetAgendaDocumentImport();

                if (!file) {
                    return;
                }

                uploadDocument.file = file;
                uploadDocument.fileName = file.name;

                if (file.type.startsWith('image/')) {
                    uploadDocument.imagePreview = URL.createObjectURL(file);
                }
            }

            function openAgendaFilePicker() {
                document.getElementById('agenda-document-file-input')?.click();
            }

            async function startAgendaCamera() {
                resetAgendaDocumentImport(false);

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    swalError('Kamera tidak tersedia di browser ini.');
                    return;
                }

                try {
                    uploadDocument.cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 2560 },
                            height: { ideal: 1440 },
                        },
                        audio: false,
                    });
                    uploadDocument.cameraActive = true;
                    await nextTick();

                    if (agendaCameraVideo.value) {
                        agendaCameraVideo.value.srcObject = uploadDocument.cameraStream;
                    }
                } catch (error) {
                    swalError('Kamera gagal dibuka.');
                    console.error(error);
                }
            }

            function stopAgendaCamera() {
                if (uploadDocument.cameraStream) {
                    uploadDocument.cameraStream.getTracks().forEach(track => track.stop());
                }

                uploadDocument.cameraStream = null;
                uploadDocument.cameraActive = false;

                if (agendaCameraVideo.value) {
                    agendaCameraVideo.value.srcObject = null;
                }
            }

            function captureAgendaPhoto() {
                const video = agendaCameraVideo.value;
                const canvas = agendaCameraCanvas.value;

                if (!video || !canvas || !video.videoWidth || !video.videoHeight) {
                    swalError('Kamera belum siap.');
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        swalError('Foto gagal diambil.');
                        return;
                    }

                    if (uploadDocument.imagePreview) {
                        URL.revokeObjectURL(uploadDocument.imagePreview);
                    }

                    const fileName = 'foto-agenda-' + moment().format('YYYYMMDD-HHmmss') + '.jpg';
                    uploadDocument.file = new File([blob], fileName, { type: 'image/jpeg' });
                    uploadDocument.fileName = fileName;
                    uploadDocument.imagePreview = URL.createObjectURL(uploadDocument.file);
                    stopAgendaCamera();
                }, 'image/jpeg', 0.92);
            }

            function processAgendaDocument() {
                if (!uploadDocument.file) {
                    swalError('Pilih berkas atau ambil foto terlebih dahulu.');
                    return;
                }

                const formData = new FormData();
                formData.append('document', uploadDocument.file);
                uploadDocument.loading = true;

                customAxios.post('/agenda/import-document', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                }).then((response) => {
                    if (response.data.status) {
                        uploadDocument.review = normalizeImportedAgendaReview(response.data.parsed || {});
                        uploadDocument.warnings = response.data.warnings || [];
                        uploadDocument.reviewReady = true;
                        swalSuccess(response.data.message);
                    } else {
                        swalError(response.data.message);
                    }
                }).catch(error => {
                    let message = error.response?.data?.message || 'Dokumen gagal diproses.';
                    const errors = error.response?.data?.errors || {};

                    if (Object.keys(errors).length > 0) {
                        message = Object.values(errors).flat().join('\n');
                    }

                    validation.value = error.response?.data?.errors || [message];
                    swalError(message);
                }).finally(() => {
                    uploadDocument.loading = false;
                });
            }

            function saveImportedAgenda() {
                uploadDocument.loading = true;
                customAxios.post('/agenda/import-document/store', uploadDocument.review)
                    .then((response) => {
                        if (response.data.status) {
                            swalSuccess(response.data.message);
                            $('#upload-agenda-document-modal').modal('hide');
                            resetAgendaDocumentImport();
                            showAllAgenda();
                        } else {
                            swalError(response.data.message);
                        }
                    }).catch(error => {
                        let message = error.response?.data?.message || 'Agenda gagal disimpan.';
                        const errors = error.response?.data?.errors || {};

                        if (Object.keys(errors).length > 0) {
                            message = Object.values(errors).flat().join('\n');
                        }

                        validation.value = error.response?.data?.errors || [message];
                        swalError(message);
                    }).finally(() => {
                        uploadDocument.loading = false;
                    });
            }

            function resetAgendaDocumentImport(stopCamera = true) {
                if (stopCamera) {
                    stopAgendaCamera();
                }

                if (uploadDocument.imagePreview) {
                    URL.revokeObjectURL(uploadDocument.imagePreview);
                }

                uploadDocument.file = null;
                uploadDocument.fileName = '';
                uploadDocument.imagePreview = '';
                uploadDocument.reviewReady = false;
                uploadDocument.warnings = [];
                uploadDocument.review = emptyImportedAgendaReview();

                const input = document.getElementById('agenda-document-file-input');
                if (input) {
                    input.value = '';
                }
            }

            function emptyImportedAgendaReview() {
                return {
                    nama_agenda: '',
                    tanggal_agenda: moment().format('YYYY-MM-DD'),
                    jam_mulai: '',
                    jam_selesai: '',
                    tempat_agenda: '',
                    pakaian: '',
                    sifat_agenda: 'publik',
                    is_done: 0,
                    kehadiran: [''],
                };
            }

            function normalizeImportedAgendaReview(data) {
                return {
                    ...emptyImportedAgendaReview(),
                    ...data,
                    jam_mulai: data.jam_mulai || '',
                    jam_selesai: data.jam_selesai || '',
                    pakaian: data.pakaian || '',
                    is_done: Number(data.is_done || 0),
                    kehadiran: Array.isArray(data.kehadiran) && data.kehadiran.length > 0 ? data.kehadiran : [''],
                };
            }

            async function editAgenda(id) {
                $('#edit-agenda-modal').modal('show');
                try {
                    const response = await customAxios.get('/agenda/' + id);
                    Object.assign(editAgendaData, {
                        id: response.data.id,
                        nama_agenda: response.data.nama_agenda,
                        tanggal_agenda: response.data.tanggal_agenda,
                        jam_mulai: response.data.jam_mulai,
                        jam_selesai: response.data.jam_selesai,
                        tempat_agenda: response.data.tempat_agenda,
                        pakaian: response.data.pakaian,
                        sifat_agenda: response.data.sifat_agenda,
                        is_done: Number(response.data.is_done),
                        jabatan_ids: response.data.jabatans ? response.data.jabatans.map(j => j.id) : [],
                        kehadiran: response.data.kehadiran ? JSON.parse(response.data.kehadiran) : [''],
                    });

                    $('#select-edit-jabatan').val(editAgendaData.jabatan_ids).trigger('change');
                } catch (error) {
                    console.error('Failed to fetch agenda:', error);
                }
            }

            function updateAgenda() {
                customAxios.put('/agenda/' + editAgendaData.id, editAgendaData)
                    .then((response) => {
                        if (response.data.status) {
                            swalSuccess(response.data.message);
                            $('#edit-agenda-modal').modal('hide');
                            showAllAgenda();
                        } else {
                            swalError(response.data.message);
                        }
                    }).catch(error => {
                        validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                        swalError(error);
                    });
            }

            function deleteAgenda(id) {
                swal({
                    title: 'Apa kamu yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                })
                    .then((willDelete) => {
                        if (willDelete) {
                            customAxios.delete('/agenda/' + id).then((response) => {
                                if (response.data.status) {
                                    swalSuccess(response.data.message);
                                    showAllAgenda();
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

            async function getAllJabatan() {
                try {
                    const response = await customAxios.get('/jabatan/all?is_show_only=1');
                    listJabatan.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch jabatan:', error);
                }
            }

            function editLinkDok(id) {
                $('#edit-link-dok-modal').modal('show');
                customAxios.get('/agenda/' + id + '/links')
                    .then((response) => {
                        editLinkDokData.id = response.data.agenda.id;
                        editLinkDokData.nama_agenda = response.data.agenda.nama_agenda || '';
                        // editLinkDok.links = response.data.links || [''];
                        editLinkDokData.links = (response.data.agenda.links && response.data.agenda.links.length > 0)
                            ? response.data.agenda.links.map(l => ({
                                nama_link: l.nama_link || '',
                                url: l.url || ''
                            }))
                            : [{ nama_link: '', url: '' }];
                    })
                    .catch(error => {
                        swalError('Gagal mengambil data link dokumentasi.');
                        console.error(error);
                    });
            }

            function updateLinkDok() {
                // Hapus link kosong sebelum kirim
                const linksToSend = editLinkDokData.links.filter(link =>
                    link.url.trim() !== '' || link.nama_link.trim() !== ''
                );
                customAxios.put('/agenda/' + editLinkDokData.id + '/links', { links: linksToSend })
                    .then((response) => {
                        if (response.data.status) {
                            swalSuccess(response.data.message);
                            $('#edit-link-dok-modal').modal('hide');
                            showAllAgenda();
                        } else {
                            swalError(response.data.message);
                        }
                    }).catch(error => {
                        validation.value = error.response?.data?.errors || ['Terjadi kesalahan.'];
                        swalError(error);
                    });
            }

            watch(filterTanggal, (newValue) => {
                if (newValue) {
                    searchAgenda();
                } else {
                    showAllAgenda();
                }
            });

            function tambahLink(idx) {
                const link = editLinkDokData.links[idx];
                if (!link.nama_link || !link.url) {
                    swal({
                        title: 'Lengkapi Data!',
                        text: 'Nama link dan URL harus diisi sebelum menambah link baru.',
                        icon: 'warning',
                        button: 'OK'
                    });
                    return;
                } else if (!isRealLink(link.url) && link.url != '') {
                    swal({
                        title: 'Link tidak valid!',
                        text: 'Silakan masukkan URL yang benar, contoh: https://contoh.com',
                        icon: 'error',
                        button: 'OK'
                    });
                    return;
                }

                editLinkDokData.links.push({ nama_link: '', url: '' });
            }

            function bukaLink(url) {
                // Regex sederhana untuk validasi URL
                const urlPattern = /^(https?:\/\/)[^\s$.?#].[^\s]*$/i;
                if (isRealLink(url)) {
                    window.open(url, '_blank');
                } else {
                    swal({
                        title: 'Link tidak valid!',
                        text: 'Silakan masukkan URL yang benar, contoh: https://contoh.com',
                        icon: 'error',
                        button: 'OK'
                    });
                }
            }

            function isRealLink(url) {
                // Regex sederhana untuk validasi URL
                const urlPattern = /^(https?:\/\/)[^\s$.?#].[^\s]*$/i;
                if (!urlPattern.test(url)) {
                    return false;
                } else {
                    return true;
                }
            }

            onMounted(() => {
                showAllAgenda();
                getAllJabatan();

                $('#select-jabatan').select2({
                    width: '100%',
                    dropdownParent: $('#add-agenda-modal')
                }).off('change').on('change', function () {
                    postAgenda.jabatan_ids = $(this).val() ? $(this).val().map(Number) : [];
                });

                $('#select-jabatan').val(postAgenda.jabatan_ids).trigger('change');

                $('#select-edit-jabatan').select2({
                    width: '100%',
                    dropdownParent: $('#edit-agenda-modal')
                }).off('change').on('change', function () {
                    editAgendaData.jabatan_ids = $(this).val() ? $(this).val().map(Number) : [];
                });

                $('#filter-jabatan').val(null).trigger('change');

                $('#filter-jabatan').select2({
                    width: '100%',
                    dropdownParent: $('.main-content'),
                    placeholder: 'Pilih Jabatan',
                    allowClear: true // agar bisa clear pilihan
                }).off('change').on('change', function () {
                    filterJabatanIds.value = $(this).val() ? $(this).val().map(Number) : [];
                    searchAgenda(); // panggil filter setiap kali select berubah
                });

                $('#upload-agenda-document-modal').on('hidden.bs.modal', function () {
                    resetAgendaDocumentImport();
                });
            });

            function exportPdf() {
                let url = BASE_URL + '/agenda/export-pdf';

                if (filterTanggal.value) {
                    url += '?tanggal=' + encodeURIComponent(filterTanggal.value);
                    window.open(url, '_blank');
                } else {
                    swalError('Tanggal tidak boleh kosong!');
                }
            }

            return {
                validation,
                loadingAgenda,
                listAgenda,
                pagination,
                searchAgendaValue,
                postAgenda,
                editAgendaData,
                agendaCameraVideo,
                agendaCameraCanvas,
                listJabatan,
                showAllAgenda,
                searchAgenda,
                storeAgenda,
                editAgenda,
                updateAgenda,
                deleteAgenda,
                uploadDocument,
                selectAgendaDocument,
                openAgendaFilePicker,
                startAgendaCamera,
                stopAgendaCamera,
                captureAgendaPhoto,
                processAgendaDocument,
                saveImportedAgenda,
                resetAgendaDocumentImport,
                getAllJabatan,
                moment,
                filterTanggal,
                exportPdf,
                editLinkDokData,
                editLinkDok,
                updateLinkDok,
                tambahLink,
                bukaLink,
                filterJabatanIds,
            }
        }
    }).mount('#app')
</script>
