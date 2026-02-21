/**
 * Reusable Modal System
 *
 * Usage:
 *   Modal.alert('Title', 'Message');
 *   Modal.confirm('Title', 'Are you sure?', () => { ... });
 *   Modal.confirmDanger('Delete Item', 'This cannot be undone.', () => { ... });
 */

const Modal = (function() {
    let modalOverlay = null;
    let currentResolve = null;

    function init() {
        if (modalOverlay) return;

        modalOverlay = document.createElement('div');
        modalOverlay.className = 'modal-overlay';
        modalOverlay.id = 'global-modal';
        modalOverlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3 id="modal-title"></h3>
                    <button type="button" class="modal-close" id="modal-close-btn">&times;</button>
                </div>
                <div class="modal-body" id="modal-body"></div>
                <div class="modal-footer" id="modal-footer"></div>
            </div>
        `;
        document.body.appendChild(modalOverlay);

        // Close on overlay click
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                close(false);
            }
        });

        // Close button
        document.getElementById('modal-close-btn').addEventListener('click', () => {
            close(false);
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
                close(false);
            }
        });
    }

    function show(title, body, buttons) {
        init();
        document.getElementById('modal-title').textContent = title;
        document.getElementById('modal-body').innerHTML = body;

        const footer = document.getElementById('modal-footer');
        footer.innerHTML = '';

        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.textContent = btn.text;
            button.className = btn.class || 'btn btn-secondary';
            button.addEventListener('click', () => {
                close(btn.value);
                if (btn.callback) btn.callback();
            });
            footer.appendChild(button);
        });

        modalOverlay.classList.add('active');

        // Focus the primary button
        const primaryBtn = footer.querySelector('.btn-danger, .btn:not(.btn-secondary)');
        if (primaryBtn) {
            setTimeout(() => primaryBtn.focus(), 100);
        }
    }

    function close(value) {
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
        }
        if (currentResolve) {
            currentResolve(value);
            currentResolve = null;
        }
    }

    return {
        /**
         * Show an alert modal (replacement for window.alert)
         */
        alert: function(title, message) {
            return new Promise((resolve) => {
                currentResolve = resolve;
                show(title, `<p>${message}</p>`, [
                    { text: 'OK', class: 'btn', value: true }
                ]);
            });
        },

        /**
         * Show a confirmation modal (replacement for window.confirm)
         */
        confirm: function(title, message, onConfirm) {
            return new Promise((resolve) => {
                currentResolve = resolve;
                show(title, `<p>${message}</p>`, [
                    { text: 'Cancel', class: 'btn btn-secondary', value: false },
                    { text: 'Confirm', class: 'btn', value: true, callback: onConfirm }
                ]);
            });
        },

        /**
         * Show a danger confirmation modal (for delete actions)
         */
        confirmDanger: function(title, message, onConfirm) {
            return new Promise((resolve) => {
                currentResolve = resolve;
                show(title, `<p>${message}</p>`, [
                    { text: 'Cancel', class: 'btn btn-secondary', value: false },
                    { text: 'Delete', class: 'btn btn-danger', value: true, callback: onConfirm }
                ]);
            });
        },

        /**
         * Show a custom modal with arbitrary body and buttons
         */
        custom: function(title, bodyHtml, buttons) {
            return new Promise((resolve) => {
                currentResolve = resolve;
                show(title, bodyHtml, buttons);
            });
        },

        /**
         * Close the modal programmatically
         */
        close: function() {
            close(false);
        }
    };
})();

/**
 * Helper to attach confirm dialog to forms
 * Usage: <form data-confirm="Are you sure?">
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handle data-confirm on forms
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (form.dataset.confirmed === 'true') {
                form.dataset.confirmed = '';
                return true;
            }

            e.preventDefault();
            const message = form.dataset.confirm;
            const title = form.dataset.confirmTitle || 'Confirm';
            const isDanger = form.dataset.confirmDanger !== undefined;

            const confirmFn = isDanger ? Modal.confirmDanger : Modal.confirm;
            confirmFn(title, message, () => {
                form.dataset.confirmed = 'true';
                form.submit();
            });
        });
    });

    // Handle data-confirm on buttons (for forms with multiple submit buttons)
    document.querySelectorAll('button[data-confirm]').forEach(button => {
        button.addEventListener('click', function(e) {
            if (button.dataset.confirmed === 'true') {
                button.dataset.confirmed = '';
                return true;
            }

            e.preventDefault();
            const message = button.dataset.confirm;
            const title = button.dataset.confirmTitle || 'Confirm';
            const isDanger = button.dataset.confirmDanger !== undefined;

            const confirmFn = isDanger ? Modal.confirmDanger : Modal.confirm;
            confirmFn(title, message, () => {
                button.dataset.confirmed = 'true';
                button.click();
            });
        });
    });
});
