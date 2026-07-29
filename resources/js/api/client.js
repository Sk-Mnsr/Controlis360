import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

function isAccountDisabledError(error) {
    const status = error.response?.status;
    if (status !== 403) {
        return false;
    }

    const payload = error.response?.data ?? {};
    const errors = payload.errors ?? payload.data ?? payload;
    const subCode = errors?.sub_code?.[0] ?? payload?.sub_code?.[0];

    return Boolean(errors?.activated || subCode === '001');
}

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('userToken');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 || isAccountDisabledError(error)) {
            localStorage.removeItem('userToken');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    },
);

export default api;
