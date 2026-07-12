<script>
    const { createApp, ref, reactive, onMounted, watch, nextTick } = Vue

    createApp({
        setup() {
            // Set baseURL dari .env
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
            const loadingAgenda = ref(false)
            const listAgenda = ref([])
            const pagination = ref({})
            const searchAgendaValue = ref('')
            const listJabatan = ref([])
            const filterTanggal = ref('');
            const filterJabatanIds = ref([]);

            const calendarEl = ref(null)
            const calendar = ref(null)
            const selectedEvent = ref(null)

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
                    const response = await customAxios.get('home/agenda/show-all', {
                        params: {
                            sifat_agenda: 'publik',
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
                    const response = await customAxios.get('home/agenda/show-all', {
                        params: { search: searchAgendaValue.value, tanggal: filterTanggal.value, jabatan_ids: filterJabatanIds.value, sifat_agenda: 'publik' }
                    })
                    listAgenda.value = response.data
                } catch (error) {
                    console.error('Failed to fetch agenda:', error)
                } finally {
                    loadingAgenda.value = false
                }
            }

            async function getAllJabatan() {
                try {
                    const response = await customAxios.get('/jabatan/all?is_show_only=1');
                    listJabatan.value = response.data;
                } catch (error) {
                    console.error('Failed to fetch jabatan:', error);
                }
            }

            watch(filterTanggal, (newValue) => {
                if (newValue) {
                    searchAgenda();
                } else {
                    showAllAgenda();
                }
            });

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

            // memanggil api untuk plugin FullCalendar
            async function loadEvents(info, success, fail) {
                try {
                    const response = await customAxios.get('home/agenda/show-all', {
                        params: {
                            sifat_agenda: 'publik'
                        }
                    })
                    success(response.data)
                } catch (error) {
                    console.error('Failed to fetch agenda:', error)
                    fail(error)
                } finally {
                    loadingAgenda.value = false
                }
            }

            const initCalendar = () => {
                calendar.value = new FullCalendar.Calendar(calendarEl.value, {
                    height: 'auto',
                    expandRows: true,
                    themeSystem: 'bootstrap',
                    contentHeight: 'auto',
                    initialView: 'dayGridMonth',
                    initialDate: new Date(), // Set tanggal awal ke hari ini
                    timeZone: 'local',
                    navLinks: true, // can click day/week names to navigate views
                    eventDisplay: 'block',
                    headerToolbar: {
                        left: 'prevYear,prev,next,nextYear today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek,dayGridDay'
                    },
                    dayHeaderDidMount: function (info) {
                        info.el.style.cursor = 'pointer';

                        info.el.addEventListener('click', function () {
                            calendar.value.changeView('dayGridWeek', calendar.value.getDate());
                        });
                    },
                    events: loadAgendaEvents,
                    eventClick: function (info) {
                        tippy(info.el, {
                            content: `
                                    <div style="padding:8px">
                                        <div style="font-weight:bold; margin-bottom:6px">
                                            ${info.event.title}
                                        </div>

                                        <div>
                                            Jam ${info.event.start?.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit' })} s/d 
                                            ${info.event.end ? info.event.end.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit' }) : 'Selesai'}
                                        </div>

                                        <div>
                                            Keterangan :<br>
                                            ${info.event.extendedProps.kehadiran_text || '-'}
                                    </div>
                                `,
                            allowHTML: true,
                            trigger: 'manual',
                            interactive: true,
                            placement: 'bottom'
                        }).show();
                    },
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    eventContent: function (arg) {
                        // Ambil jam dari string datetime
                        let jamMulai = arg.event.start
                            ? arg.event.start.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                            : '';
                        let jamSelesai = arg.event.end
                            ? arg.event.end.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                            : 'Selesai';

                        return {
                            html: `
                                <div class="fc-event-custom d-flex align-items-center">
                                    <span class="fc-event-dot"></span>
                                    <span class="fc-event-time">${jamMulai} - ${jamSelesai}</span>
                                    <span class="fc-event-title">
                                        ${arg.event.title}
                                    </span>
                                </div>
                                `
                        }
                    }
                })

                calendar.value.render()

                // 🔥 PAKSA hitung ulang ukuran
                setTimeout(() => {
                    calendar.value.updateSize()
                }, 100)
            }

            function loadAgendaEvents(fetchInfo, successCallback, failureCallback) {
                customAxios.get('home/agenda/show-all', {
                    params: {
                        sifat_agenda: 'publik',
                    }
                })
                    .then(response => {
                        const events = [];
                        if (response.data.length >= 1) {
                            response.data.forEach(agenda => {

                                let startEventDate = `${agenda.tanggal_agenda}T${agenda.jam_mulai}`;
                                let endEventDate = '';
                                if (agenda.jam_selesai != null) {
                                    endEventDate = `${agenda.tanggal_agenda}T${agenda.jam_selesai}`;
                                } else {
                                    endEventDate = `${agenda.tanggal_agenda}T00:00:00`;
                                }

                                events.push({
                                    title: agenda.nama_agenda,
                                    start: startEventDate,
                                    end: endEventDate,
                                    url: '',
                                    place: agenda.tempat_agenda,
                                    kehadiran_text: agenda.kehadiran_text
                                });
                            });
                        }

                        successCallback(events);
                    })
                    .catch(error => {
                        console.error('Failed to fetch agenda for calendar:', error);
                        failureCallback(error);
                    });
            }

            onMounted(() => {
                showAllAgenda();
                getAllJabatan();

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

                initCalendar();
            });

            return {
                validation,
                loadingAgenda,
                listAgenda,
                pagination,
                searchAgendaValue,
                listJabatan,
                showAllAgenda,
                searchAgenda,
                getAllJabatan,
                moment,
                filterTanggal,
                bukaLink,
                filterJabatanIds,
                calendarEl,
                selectedEvent,
            }
        }
    }).mount('#app')

</script>