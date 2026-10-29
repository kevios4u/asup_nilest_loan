document.addEventListener('DOMContentLoaded', () => {
    const navButton = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.main-nav');
    if (navButton && nav) {
        navButton.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            navButton.setAttribute('aria-expanded', String(isOpen));
            navButton.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
            navButton.querySelector('ion-icon')?.setAttribute('name', isOpen ? 'close-outline' : 'menu-outline');
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.parentElement.querySelector('[data-password-input]');
        if (!input) {
            return;
        }
        button.addEventListener('click', () => {
            const isVisible = input.type === 'password';
            input.type = isVisible ? 'text' : 'password';
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(isVisible));
            button.querySelector('ion-icon')?.setAttribute('name', isVisible ? 'eye-off-outline' : 'eye-outline');
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Are you sure?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-request-form]').forEach((form) => {
        const typeInput = form.querySelector('[data-request-type]');
        const contribution = form.querySelector('[data-field="proposed_contribution"]');
        const amount = form.querySelector('[data-field="amount"]');
        const contributionTypes = ['contribution_increase', 'contribution_reduction'];
        const amountTypes = ['withdrawal'];
        const updateFields = () => {
            const type = typeInput.value;
            const showContribution = contributionTypes.includes(type);
            const showAmount = amountTypes.includes(type);
            const contributionInput = contribution.querySelector('input');
            const amountInput = amount.querySelector('input');

            contribution.hidden = !showContribution;
            amount.hidden = !showAmount;
            contributionInput.required = showContribution;
            amountInput.required = showAmount;
            contributionInput.disabled = !showContribution;
            amountInput.disabled = !showAmount;
        };
        typeInput.addEventListener('change', updateFields);
        updateFields();
    });
});
