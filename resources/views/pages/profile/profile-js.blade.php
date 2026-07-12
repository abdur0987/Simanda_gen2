<script>
    const { createApp, ref, reactive, onMounted } = Vue

    createApp({
        setup() {
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
            
            const profileData = reactive({
                name: '{{ Auth::user()->name }}',
                email: '{{ Auth::user()->email }}',
                password: ''
            });
            const loadingProfile = ref(false);

            function updateProfile() {
                loadingProfile.value = true;
                customAxios.post('/profile/update', {
                    name: profileData.name,
                    email: profileData.email,
                    password: profileData.password
                })
                    .then(response => {
                        if (response.data.status) {
                            swal({
                                title: 'Berhasil!',
                                text: response.data.message,
                                icon: 'success',
                                button: 'OK'
                            }).then(() => {
                                window.location.reload(); // refresh halaman setelah OK
                            });
                            profileData.password = '';
                        } else {
                            swal({
                                title: 'Gagal!',
                                text: response.data.message,
                                icon: 'error',
                                button: 'OK'
                            });
                        }
                    })
                    .catch(() => {
                        swal({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat update profile.',
                            icon: 'error',
                            button: 'OK'
                        });
                    })
                    .finally(() => {
                        loadingProfile.value = false;
                    });
            }

            return {
                profileData,
                loadingProfile,
                updateProfile
            }
        }
    }).mount('#app')
</script>