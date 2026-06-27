document.addEventListener('DOMContentLoaded', () => {

    /* ── Tab navigation (sidebar + top tab bar + plan banner links) ─────────── */
    function switchTab(tab) {
        document.querySelectorAll('[data-tab]').forEach(n => n.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(t => t.classList.remove('active'));
        document.querySelectorAll(`[data-tab="${tab}"]`).forEach(n => n.classList.add('active'));
        document.getElementById('tab-' + tab)?.classList.add('active');
    }

    document.querySelectorAll('[data-tab]').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(item.dataset.tab);
        });
    });

    if (typeof IS_EXPIRED !== 'undefined' && IS_EXPIRED) {
        switchTab('premium');
    }

    /* ── Modal ──────────────────────────────────────────────────────────────── */
    const modal      = document.getElementById('link-modal');
    const linkForm   = document.getElementById('link-form');
    const modalTitle = document.getElementById('modal-title');

    function openModal(editData = null) {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (editData) {
            modalTitle.textContent = 'Edit Link';
            linkForm.querySelector('[name="id"]').value       = editData.id;
            linkForm.querySelector('[name="title"]').value    = editData.title;
            linkForm.querySelector('[name="url"]').value      = editData.url;
            linkForm.querySelector('[name="platform"]').value = editData.platform || 'custom';
        } else {
            modalTitle.textContent = 'Add Link';
            linkForm.reset();
            linkForm.querySelector('[name="id"]').value = '';
        }
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        linkForm.reset();
        linkForm.querySelector('[name="id"]').value = '';
    }

    // Use delegated click on document for add-link buttons so both
    // the header button and the empty-state button are always caught,
    // regardless of which one is in the DOM at any given moment.
    document.addEventListener('click', (e) => {
        if (!CAN_ADD_LINK) return;
        if (e.target.closest('#btn-add-link') || e.target.closest('#btn-add-link-empty')) {
            openModal();
        }
    });

    document.getElementById('modal-cancel')?.addEventListener('click', closeModal);
    document.getElementById('modal-close')?.addEventListener('click', closeModal);
    document.getElementById('modal-backdrop')?.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    // Platform select auto-fills the title field if it's still empty
    document.getElementById('link-platform')?.addEventListener('change', (e) => {
        const titleInput = linkForm.querySelector('[name="title"]');
        if (!titleInput.value && PLATFORMS[e.target.value]) {
            titleInput.value = PLATFORMS[e.target.value].label;
        }
    });

    /* ── Link form submit (create / update) ────────────────────────────────── */
    linkForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id     = linkForm.querySelector('[name="id"]').value;
        const action = id ? 'update' : 'create';
        const formData = new FormData(linkForm);
        formData.append('action', action);

        try {
            const res  = await fetch(BASE_URL + '/api/links.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to save link.');
            }
        } catch (err) {
            alert('Network error while saving the link.');
        }
    });

    /* ── Link list actions: edit / delete / archive / restore ──────────────── */
    // Delegated on the container so it keeps working even if cards are
    // re-rendered without a full page reload in the future.
    const linksList = document.getElementById('links-list');

    linksList?.addEventListener('click', async (e) => {

        // ── Edit ──────────────────────────────────────────────────────────────
        const editBtn = e.target.closest('.btn-edit');
        if (editBtn) {
            const card = editBtn.closest('.link-card');
            openModal({
                id:       editBtn.dataset.id,
                title:    card.dataset.title    || '',
                url:      card.dataset.url      || '',
                platform: card.dataset.platform || 'custom',
            });
            return;
        }

        // ── Delete ────────────────────────────────────────────────────────────
        const deleteBtn = e.target.closest('.btn-delete');
        if (deleteBtn) {
            if (!confirm('Delete this link permanently?')) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', deleteBtn.dataset.id);
            try {
                const res  = await fetch(BASE_URL + '/api/links.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    deleteBtn.closest('.link-card').remove();
                    // Show empty state if no cards remain
                    if (!linksList.querySelector('.link-card')) {
                        linksList.innerHTML = `
                            <div class="empty-state">
                                <h3>No links yet</h3>
                                <p>Add your social profiles, websites, or any URL you want to share.</p>
                                <button type="button" class="btn btn-primary" id="btn-add-link-empty">+ Add your first link</button>
                            </div>`;
                    }
                } else {
                    alert(data.error || 'Failed to delete link.');
                }
            } catch (err) {
                alert('Network error while deleting the link.');
            }
            return;
        }

        // ── Archive / Restore ─────────────────────────────────────────────────
        // btn-archive  → we want to archive   → send archived = 1
        // btn-restore  → we want to restore   → send archived = 0
        const archiveBtn = e.target.closest('.btn-archive, .btn-restore');
        if (archiveBtn) {
            const archived = archiveBtn.classList.contains('btn-archive') ? 1 : 0;
            const formData = new FormData();
            formData.append('action', 'archive');
            formData.append('id', archiveBtn.dataset.id);
            formData.append('archived', archived);
            try {
                const res  = await fetch(BASE_URL + '/api/links.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to update link.');
                }
            } catch (err) {
                alert('Network error while updating the link.');
            }
        }
    });

    /* ── Profile form ──────────────────────────────────────────────────────── */
    document.getElementById('profile-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_profile');
        try {
            const res  = await fetch(BASE_URL + '/api/profile.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert('Profile updated!');
                location.reload();
            } else {
                alert(data.error || 'Failed to update profile.');
            }
        } catch (err) {
            alert('Network error while updating the profile.');
        }
    });

    /* ── Theme: live preview ─────────────────────────────────────────────────── */
    function updateThemePreview() {
        const preview = document.getElementById('theme-preview');
        if (!preview) return;
        const bgColor   = document.querySelector('[name="bg_color"]')?.value;
        const textColor = document.querySelector('[name="text_color"]')?.value;
        const btnColor  = document.querySelector('[name="button_color"]')?.value;
        const btnTColor = document.querySelector('[name="button_text_color"]')?.value;
        if (bgColor)   preview.style.background = bgColor;
        if (textColor) preview.style.color      = textColor;
        document.querySelectorAll('#preview-btn, #preview-btn2').forEach(el => {
            if (btnColor)  el.style.background = btnColor;
            if (btnTColor) el.style.color      = btnTColor;
        });
    }

    // Color picker → live preview + sync hex field
    document.querySelectorAll('#theme-form input[type="color"]').forEach(input => {
        input.addEventListener('input', () => {
            const hex = input.closest('.color-input-wrap')?.querySelector('.color-hex');
            if (hex) hex.value = input.value;
            updateThemePreview();
        });
    });

    // Hex text field → sync color picker + live preview
    document.querySelectorAll('.color-hex').forEach(hex => {
        hex.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) {
                const picker = hex.closest('.color-input-wrap')?.querySelector('input[type="color"]');
                if (picker) picker.value = hex.value;
                updateThemePreview();
            }
        });
    });

    // Run once on load so the preview matches saved colors immediately
    updateThemePreview();

    /* ── Theme presets ───────────────────────────────────────────────────────── */
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const theme = THEMES[btn.dataset.theme];
            if (!theme) return;

            document.querySelector('[name="theme"]').value             = btn.dataset.theme;
            document.querySelector('[name="bg_color"]').value          = theme.bg;
            document.querySelector('[name="text_color"]').value        = theme.text;
            document.querySelector('[name="button_color"]').value      = theme.btn;
            document.querySelector('[name="button_text_color"]').value = theme.btn_text ?? '#ffffff';

            // Sync all hex fields to match their paired color picker
            document.querySelectorAll('.color-input-wrap').forEach(wrap => {
                const picker = wrap.querySelector('input[type="color"]');
                const hex    = wrap.querySelector('.color-hex');
                if (picker && hex) hex.value = picker.value;
            });

            updateThemePreview();

            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    /* ── Theme form submit ───────────────────────────────────────────────────── */
    document.getElementById('theme-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_theme');
        try {
            const res  = await fetch(BASE_URL + '/api/profile.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert('Theme saved!');
            } else {
                alert(data.error || 'Failed to save theme.');
            }
        } catch (err) {
            alert('Network error while saving the theme.');
        }
    });

    /* ── Mark admin messages as read ─────────────────────────────────────────── */
    if (document.querySelector('.alerts')) {
        fetch(BASE_URL + '/api/profile.php', {
            method: 'POST',
            body: (() => { const f = new FormData(); f.append('action', 'mark_messages_read'); return f; })(),
        });
    }

    /* ── Payment proof upload ─────────────────────────────────────────────────── */
    document.getElementById('payment-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'submit');
        try {
            const res = await fetch(BASE_URL + '/api/payments.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert('Payment proof submitted! Please wait for admin verification.');
                location.reload();
            } else {
                alert(data.error || 'Failed to submit payment proof.');
            }
        } catch (err) {
            alert('Network error while submitting payment proof.');
        }
    });

});