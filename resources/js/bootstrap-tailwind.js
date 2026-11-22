
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


/**
 * Bootstrap 5.3-compatible Modal
 *
 * Lightweight vanilla JavaScript implementation.
 * Does not require Bootstrap or Popper.js.
 */
class Modal {
    static instances = new WeakMap();
    static activeInstance = null;
    static bodyLocked = false;
    static originalBodyPadding = '';

    constructor(element) {
        this.element = element;
        this.backdrop = null;
        this.isTransitioning = false;
        this.transitionToken = 0;
        this.previousFocus = null;

        Modal.instances.set(element, this);
    }

    // --------------------------------------------------
    // Instance methods
    // --------------------------------------------------

    show() {
        ++this.transitionToken;
        this.isTransitioning = false;

        if (this.element.classList.contains('show')) {
            return;
        }

        // Only one modal can be open at a time.
        if (Modal.activeInstance && Modal.activeInstance !== this) {
            Modal.activeInstance._hideImmediately();
        }

        this.previousFocus = document.activeElement;
        Modal.activeInstance = this;

        this.lockBody();

        this.element.style.display = 'block';
        this.element.removeAttribute('aria-hidden');
        this.element.setAttribute('aria-modal', 'true');
        this.element.setAttribute('role', 'dialog');

        if (!this.element.hasAttribute('tabindex')) {
            this.element.setAttribute('tabindex', '-1');
        }

        this.createBackdrop();

        // Force browser reflow before starting the transition.
        this.element.offsetHeight;

        requestAnimationFrame(() => {
            this.element.classList.add('show');
            this.backdrop?.classList.add('show');

            this.focusInitialElement();
        });
    }

    hide() {
        if (!this.element.classList.contains('show')) {
            return;
        }

        if (this.isTransitioning) {
            return;
        }

        this.isTransitioning = true;

        const token = ++this.transitionToken;

        this.element.classList.remove('show');
        this.backdrop?.classList.remove('show');

        this.element.setAttribute('aria-hidden', 'true');
        this.element.removeAttribute('aria-modal');

        const transitionElement = this.element.querySelector('.modal-dialog') || this.element;
        this.waitForTransition(transitionElement).then(() => {
            if (token !== this.transitionToken) {
                return;
            }

            this.completeHide();
        });
    }

    toggle() {
        this.element.classList.contains('show')
            ? this.hide()
            : this.show();
    }

    dispose() {
        this._hideImmediately();

        Modal.instances.delete(this.element);

        this.element = null;
        this.backdrop = null;
        this.previousFocus = null;
    }

    // --------------------------------------------------
    // Backdrop
    // --------------------------------------------------

    createBackdrop() {
        const backdropOption = this.element.getAttribute('data-bs-backdrop');

        if (backdropOption === 'false') {
            return;
        }

        this.backdrop = document.createElement('div');
        this.backdrop.className = 'modal-backdrop fade';

        document.body.appendChild(this.backdrop);

        this.backdrop.offsetHeight;
    }

    // --------------------------------------------------
    // Focus management
    // --------------------------------------------------

    getFocusableElements() {
        return Array.from(
            this.element.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), ' +
                'select:not([disabled]), textarea:not([disabled]), ' +
                '[tabindex]:not([tabindex="-1"])'
            )
        ).filter((element) => {
            return element.getAttribute('aria-hidden') !== 'true' &&
                element.getClientRects().length > 0;
        });
    }

    focusInitialElement() {
        const focusable = this.getFocusableElements();

        if (focusable.length) {
            focusable[0].focus();
        } else {
            this.element.focus();
        }
    }

    trapFocus(event) {
        const focusable = this.getFocusableElements();

        if (!focusable.length) {
            event.preventDefault();
            this.element.focus();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    // --------------------------------------------------
    // Body scroll management
    // --------------------------------------------------

    lockBody() {
        if (Modal.bodyLocked) {
            return;
        }

        const scrollbarWidth =
            window.innerWidth - document.documentElement.clientWidth;

        Modal.originalBodyPadding = document.body.style.paddingRight;

        const currentPadding = parseFloat(
            getComputedStyle(document.body).paddingRight
        ) || 0;

        if (scrollbarWidth > 0) {
            document.body.style.paddingRight =
                `${currentPadding + scrollbarWidth}px`;
        }

        document.body.classList.add('modal-open');

        Modal.bodyLocked = true;
    }

    unlockBody() {
        if (!Modal.bodyLocked) {
            return;
        }

        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = Modal.originalBodyPadding;

        Modal.bodyLocked = false;
    }

    // --------------------------------------------------
    // Transition handling
    // --------------------------------------------------

    waitForTransition(element) {
        return new Promise((resolve) => {
            if (!element || !element.classList.contains('fade')) {
                resolve();
                return;
            }

            const style = getComputedStyle(element);

            const durations = style.transitionDuration
                .split(',')
                .map(value => parseFloat(value) * (value.includes('ms') ? 1 : 1000));

            const delays = style.transitionDelay
                .split(',')
                .map(value => parseFloat(value) * (value.includes('ms') ? 1 : 1000));

            const duration = Math.max(
                ...durations.map((value, index) => {
                    return value + (delays[index] || delays[0] || 0);
                }),
                0
            );

            if (duration === 0) {
                resolve();
                return;
            }

            let completed = false;

            const finish = () => {
                if (completed) {
                    return;
                }

                completed = true;
                element.removeEventListener('transitionend', onTransitionEnd);
                clearTimeout(timeout);
                resolve();
            };

            const onTransitionEnd = (event) => {
                if (event.target === element) {
                    finish();
                }
            };

            const timeout = setTimeout(finish, duration + 50);

            element.addEventListener('transitionend', onTransitionEnd);
        });
    }

    // --------------------------------------------------
    // Hide and cleanup
    // --------------------------------------------------

    completeHide() {
        this.element.style.display = 'none';

        this.backdrop?.remove();
        this.backdrop = null;

        this.isTransitioning = false;

        if (Modal.activeInstance === this) {
            Modal.activeInstance = null;
            this.unlockBody();

            if (this.previousFocus?.isConnected) {
                this.previousFocus.focus();
            }
        }
    }

    _hideImmediately() {
        ++this.transitionToken;

        this.element.classList.remove('show');
        this.element.style.display = 'none';

        this.element.setAttribute('aria-hidden', 'true');
        this.element.removeAttribute('aria-modal');

        this.backdrop?.remove();
        this.backdrop = null;

        this.isTransitioning = false;

        if (Modal.activeInstance === this) {
            Modal.activeInstance = null;
        }
    }

    handleStaticBackdrop() {
        const dialog =
            this.element.querySelector('.modal-dialog') || this.element;

        this.element.classList.add('modal-static');

        this.waitForTransition(dialog).then(() => {
            this.element.classList.remove('modal-static');
        });
    }

    // --------------------------------------------------
    // Static methods
    // --------------------------------------------------

    static getInstance(element) {
        return Modal.instances.get(element) || null;
    }

    static getOrCreateInstance(element) {
        return Modal.getInstance(element) || new Modal(element);
    }

    static init() {
        if (Modal.initialized) {
            return;
        }

        Modal.initialized = true;

        // Open and dismiss modal.
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest(
                '[data-bs-toggle="modal"]'
            );

            if (trigger) {
                event.preventDefault();

                const selector = trigger.getAttribute('data-bs-target')
                    || trigger.getAttribute('href');

                if (selector?.startsWith('#')) {
                    const element = document.querySelector(selector);

                    if (element?.classList.contains('modal')) {
                        Modal.getOrCreateInstance(element).show();
                    }
                }

                return;
            }

            const dismiss = event.target.closest(
                '[data-bs-dismiss="modal"]'
            );

            if (dismiss) {
                const element = dismiss.closest('.modal');

                if (element) {
                    Modal.getOrCreateInstance(element).hide();
                }
            }
        });

        // Backdrop click.
        document.addEventListener('click', (event) => {
            const modal = event.target.closest('.modal');

            if (!modal || event.target !== modal) {
                return;
            }

            const instance = Modal.getInstance(modal);

            if (!instance) {
                return;
            }

            const backdropOption =
                modal.getAttribute('data-bs-backdrop');

            if (backdropOption === 'static') {
                instance.handleStaticBackdrop();
            } else if (backdropOption !== 'false') {
                instance.hide();
            }
        });

        // Keyboard handling.
        document.addEventListener('keydown', (event) => {
            const instance = Modal.activeInstance;

            if (!instance) {
                return;
            }

            if (event.key === 'Escape') {
                const keyboardOption =
                    instance.element.getAttribute('data-bs-keyboard');

                if (keyboardOption === 'false') {
                    return;
                }

                event.preventDefault();
                instance.hide();
            }

            if (event.key === 'Tab') {
                instance.trapFocus(event);
            }
        });

        // Prevent focus from escaping the active modal.
        document.addEventListener('focusin', (event) => {
            const instance = Modal.activeInstance;

            if (!instance || instance.element.contains(event.target)) {
                return;
            }

            instance.focusInitialElement();
        });
    }
}

Modal.initialized = false;

// Initialize modal event handlers.
Modal.init();
export { Modal };
