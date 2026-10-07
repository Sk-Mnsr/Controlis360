import axios from 'axios';

const http = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

const TOKEN_HEADER = 'X-Encheres-Client-Token';

export async function requestClientCode(email) {
    const { data } = await http.post('/vente-encheres/client-auth/code', { email });
    return data;
}

export async function verifyClientCode(email, code) {
    const { data } = await http.post('/vente-encheres/client-auth/verify', { email, code });
    return data;
}

export async function fetchClientMe(token) {
    const { data } = await http.get('/vente-encheres/client-auth/me', {
        headers: { [TOKEN_HEADER]: token },
    });
    return data;
}

export async function logoutClient(token) {
    await http.delete('/vente-encheres/client-auth/logout', {
        headers: { [TOKEN_HEADER]: token },
    });
}
