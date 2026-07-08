import api from '../api/client';

export function attachmentFileName(path) {
    if (!path) return 'fichier';
    if (typeof path === 'object' && path !== null) {
        return path.name || path.path?.split('/').pop() || 'fichier';
    }
    return String(path).split('/').pop();
}

export function attachmentPath(path) {
    if (!path) return '';
    if (typeof path === 'object' && path !== null) {
        return path.path ?? '';
    }
    return String(path);
}

export function isPreviewableAttachment(path) {
    const value = attachmentPath(path);
    const extension = value.split('.').pop()?.toLowerCase() ?? '';
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'].includes(extension);
}

async function fetchAttachmentBlob(path) {
    const response = await api.get('/attachments/download', {
        params: { path: attachmentPath(path) },
        responseType: 'blob',
    });

    return response.data;
}

export async function downloadAttachment(path, downloadName = null) {
    const storagePath = attachmentPath(path);
    if (!storagePath) return;

    const blob = await fetchAttachmentBlob(storagePath);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = downloadName || attachmentFileName(path);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

export async function previewAttachment(path) {
    const storagePath = attachmentPath(path);
    if (!storagePath) return;

    const blob = await fetchAttachmentBlob(storagePath);
    const url = window.URL.createObjectURL(blob);
    window.open(url, '_blank', 'noopener,noreferrer');
    window.setTimeout(() => window.URL.revokeObjectURL(url), 60_000);
}
