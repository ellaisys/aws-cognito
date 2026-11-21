
/**
 * Bootstrap-compatible dropdown behaviour.
 * Requires the corresponding dropdown CSS.
 */

class Dropdown {
    static instances = new WeakMap();

    constructor(toggle) {
        this.toggle = toggle;
        this.dropdown = toggle.closest('.dropdown, .dropup, .dropend, .dropstart');
        this.menu = this.dropdown?.querySelector('.dropdown-menu');

        if (!this.dropdown || !this.menu) {
            return;
        }

        Dropdown.instances.set(toggle, this);

        this.toggle.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggleMenu();
        });
    }

    toggleMenu() {
        this.isOpen() ? this.hide() : this.show();
    }

    show() {
        // Close other open dropdowns first.
        document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
            if (menu !== this.menu) {
                menu.classList.remove('show');

                const toggle = menu.closest('.dropdown, .dropup, .dropend, .dropstart')
                    ?.querySelector('[data-bs-toggle="dropdown"]');

                toggle?.setAttribute('aria-expanded', 'false');
            }
        });

        this.menu.classList.add('show');
        this.toggle.setAttribute('aria-expanded', 'true');
    }

    hide() {
        this.menu.classList.remove('show');
        this.toggle.setAttribute('aria-expanded', 'false');
    }

    isOpen() {
        return this.menu.classList.contains('show');
    }

    static init() {
        document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((toggle) => {
            if (!Dropdown.instances.has(toggle)) {
                new Dropdown(toggle);
            }
        });
    }
}

// Initialize dropdowns.
document.addEventListener('DOMContentLoaded', () => {
    Dropdown.init();
});

// Close dropdowns when clicking outside.
document.addEventListener('click', (event) => {
    document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
        const dropdown = menu.closest('.dropdown, .dropup, .dropend, .dropstart');

        if (!dropdown?.contains(event.target)) {
            menu.classList.remove('show');

            dropdown?.querySelector('[data-bs-toggle="dropdown"]')
                ?.setAttribute('aria-expanded', 'false');
        }
    });
});

// Close dropdowns on Escape.
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
        menu.classList.remove('show');

        const toggle = menu.closest('.dropdown, .dropup, .dropend, .dropstart')
            ?.querySelector('[data-bs-toggle="dropdown"]');

        toggle?.setAttribute('aria-expanded', 'false');
        toggle?.focus();
    });
});

// Close dropdown after selecting an item.
document.addEventListener('click', (event) => {
    if (!event.target.closest('.dropdown-item')) {
        return;
    }

    const dropdown = event.target.closest('.dropdown, .dropup, .dropend, .dropstart');

    dropdown?.querySelector('.dropdown-menu')?.classList.remove('show');

    dropdown?.querySelector('[data-bs-toggle="dropdown"]')
        ?.setAttribute('aria-expanded', 'false');
});
