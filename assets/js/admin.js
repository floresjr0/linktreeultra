document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('message-modal');
    const messageForm = document.getElementById('message-form');

    function openMessageModal(userId, username) {
        document.getElementById('msg-user-id').value = userId;
        document.getElementById('msg-user-name').textContent = '@' + username;
        modal.hidden = false;
    }

    function closeModal() {
        modal.hidden = true;
        messageForm.reset();
    }

    document.querySelectorAll('.btn-message').forEach(btn => {
        btn.addEventListener('click', () => openMessageModal(btn.dataset.id, btn.dataset.name));
    });

    document.getElementById('msg-cancel')?.addEventListener('click', closeModal);
    modal?.querySelector('.modal-backdrop')?.addEventListener('click', closeModal);

    messageForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(messageForm);
        formData.append('action', 'send_message');
        const res = await fetch(BASE_URL + '/admin/?action=send_message', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Message sent!');
            closeModal();
        } else {
            alert(data.error || 'Failed to send message');
        }
    });

    async function adminAction(action, id, extra = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('id', id);
        for (const [k, v] of Object.entries(extra)) formData.append(k, v);
        const res = await fetch(BASE_URL + '/admin/?action=' + action, { method: 'POST', body: formData });
        return res.json();
    }

    document.querySelectorAll('.btn-ban').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Ban this user? They will not be able to log in.')) return;
            const data = await adminAction('ban', btn.dataset.id, { banned: 1 });
            if (data.success) location.reload();
        });
    });

    document.querySelectorAll('.btn-unban').forEach(btn => {
        btn.addEventListener('click', async () => {
            const data = await adminAction('ban', btn.dataset.id, { banned: 0 });
            if (data.success) location.reload();
        });
    });

    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Permanently delete this user and all their links?')) return;
            const data = await adminAction('delete_user', btn.dataset.id);
            if (data.success) btn.closest('tr').remove();
        });
    });
});
