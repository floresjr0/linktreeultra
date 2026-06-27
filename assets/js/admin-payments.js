document.addEventListener('DOMContentLoaded', () => {
    const settingsForm = document.getElementById('settings-form');
    const proofModal = document.getElementById('proof-modal');
    const reviewModal = document.getElementById('review-modal');
    const reviewForm = document.getElementById('review-form');

    settingsForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(settingsForm);
        formData.append('action', 'save_settings');
        const res = await fetch(BASE_URL + '/admin/payments.php?action=save_settings', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Payment settings saved!');
            location.reload();
        } else {
            alert(data.error || 'Failed to save settings.');
        }
    });

    document.querySelectorAll('.btn-view-proof').forEach(img => {
        img.addEventListener('click', () => {
            document.getElementById('proof-full').src = img.dataset.src;
            proofModal.hidden = false;
        });
    });

    document.getElementById('proof-close')?.addEventListener('click', () => { proofModal.hidden = true; });
    document.getElementById('proof-backdrop')?.addEventListener('click', () => { proofModal.hidden = true; });

    function openReview(id, decision) {
        document.getElementById('review-id').value = id;
        document.getElementById('review-decision').value = decision;
        document.getElementById('review-note').value = '';
        document.getElementById('duration-group').hidden = decision !== 'approve';
        document.getElementById('review-title').textContent = decision === 'approve' ? 'Approve Payment' : 'Reject Payment';
        document.getElementById('review-note-label').textContent = decision === 'approve' ? 'Note to user (optional)' : 'Reason for rejection (required)';
        document.getElementById('review-note').required = decision === 'reject';
        document.getElementById('review-submit').textContent = decision === 'approve' ? 'Approve' : 'Reject';
        document.getElementById('review-submit').className = decision === 'approve' ? 'btn btn-success' : 'btn btn-danger';
        reviewModal.hidden = false;
    }

    document.querySelectorAll('.btn-approve').forEach(btn => {
        btn.addEventListener('click', () => openReview(btn.dataset.id, 'approve'));
    });
    document.querySelectorAll('.btn-reject').forEach(btn => {
        btn.addEventListener('click', () => openReview(btn.dataset.id, 'reject'));
    });

    document.getElementById('review-cancel')?.addEventListener('click', () => { reviewModal.hidden = true; });
    document.getElementById('review-backdrop')?.addEventListener('click', () => { reviewModal.hidden = true; });

    reviewForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const decision = document.getElementById('review-decision').value;
        const formData = new FormData(reviewForm);
        formData.append('action', decision);
        const res = await fetch(BASE_URL + '/admin/payments.php?action=' + decision, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(decision === 'approve' ? 'Payment approved!' : 'Payment rejected.');
            location.reload();
        } else {
            alert(data.error || 'Action failed.');
        }
    });
});
