/**
 * Bootstrap-compatible dropdown and modal behaviour.
 * Lightweight vanilla JavaScript; no Bootstrap JS or Popper.js dependency.
 *
 * This is a focused compatibility layer, not a complete Bootstrap API.
 */

const DROPDOWN_SELECTOR = '[data-bs-toggle="dropdown"]';
const DROPDOWN_CONTAINER_SELECTOR = '.dropdown, .dropup, .dropend, .dropstart';
const DROPDOWN_MENU_SELECTOR = '.dropdown-menu';
const DROPDOWN_ITEM_SELECTOR = '.dropdown-item';

const MODAL_SELECTOR = '.modal';
const MODAL_TOGGLE_SELECTOR = '[data-bs-toggle="modal"]';
const MODAL_DISMISS_SELECTOR = '[data-bs-dismiss="modal"]';
const MODAL_BACKDROP_CLASS = 'modal-backdrop';

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'area[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'iframe',
    'object',
    'embed',
    '[contenteditable="true"]',
    '[tabindex]:not([tabindex="-1"])'
].join(',');

/**
 * Bootstrap-compatible dropdown controller.
 */
class Dropdown {
    static instances = new WeakMap();
    static initialized = false;

    constructor(toggle) {
        this.toggle = toggle;
        this.dropdown = toggle.closest(DROPDOWN_CONTAINER_SELECTOR);
        this.menu = this.dropdown?.querySelector(DROPDOWN_MENU_SELECTOR) ?? null;

        if (!this.dropdown || !this.menu) {
            return;
        }

        Dropdown.instances.set(toggle, this);

        if (!this.toggle.hasAttribute('aria-expanded')) {
            this.toggle.setAttribute('aria-expanded', String(this.isOpen()));
        }
    }

    show() {
        if (!this.menu || this.isOpen()) {
            return;
        }

        Dropdown.closeAll(this.menu);
        this.menu.classList.add('show');
        this.toggle.setAttribute('aria-expanded', 'true');
    }

    hide() {
        if (!this.menu || !this.isOpen()) {
            return;
        }

        this.menu.classList.remove('show');
        this.toggle.setAttribute('aria-expanded', 'false');
    }

    toggleMenu() {
        if (this.isOpen()) {
            this.hide();
            return;
        }

        this.show();
    }

    isOpen() {
        return Boolean(this.menu?.classList.contains('show'));
    }

    static getInstance(toggle) {
        return Dropdown.instances.get(toggle) ?? null;
    }

    static getOrCreateInstance(toggle) {
        return Dropdown.getInstance(toggle) ?? new Dropdown(toggle);
    }

    static close(menu) {
        menu.classList.remove('show');

        const toggle = menu
            .closest(DROPDOWN_CONTAINER_SELECTOR)
            ?.querySelector(DROPDOWN_SELECTOR);

        toggle?.setAttribute('aria-expanded', 'false');
    }

    static closeAll(exceptMenu = null) {
        document.querySelectorAll(`${DROPDOWN_MENU_SELECTOR}.show`).forEach((menu) => {
            if (menu !== exceptMenu) {
                Dropdown.close(menu);
            }
        });
    }

    static init() {
        if (Dropdown.initialized) {
            return;
        }

        Dropdown.initialized = true;

        // Delegation also supports dropdowns inserted after page initialization.
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const toggle = event.target.closest(DROPDOWN_SELECTOR);

            if (toggle) {
                event.preventDefault();
                Dropdown.getOrCreateInstance(toggle).toggleMenu();
                return;
            }

            const item = event.target.closest(DROPDOWN_ITEM_SELECTOR);

            if (item) {
                const menu = item.closest(DROPDOWN_MENU_SELECTOR);

                if (menu) {
                    Dropdown.close(menu);
                }

                return;
            }

            if (!event.target.closest(DROPDOWN_CONTAINER_SELECTOR)) {
                Dropdown.closeAll();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            const openMenu = document.querySelector(`${DROPDOWN_MENU_SELECTOR}.show`);

            if (!openMenu) {
                return;
            }

            const toggle = openMenu
                .closest(DROPDOWN_CONTAINER_SELECTOR)
                ?.querySelector(DROPDOWN_SELECTOR);

            Dropdown.close(openMenu);
            toggle?.focus();
        });
    }
}

/**
 * Bootstrap-compatible modal controller.
 */
class Modal {
    static instances = new WeakMap();
    static activeInstance = null;
    static initialized = false;
    static bodyLocked = false;
    static originalBodyPaddingRight = '';

    constructor(element) {
        if (!(element instanceof Element) || !element.matches(MODAL_SELECTOR)) {
            throw new TypeError('Modal requires an element with the .modal class.');
        }

        this.element = element;
        this.backdrop = null;
        this.isTransitioning = false;
        this.transitionToken = 0;
        this.previousFocus = null;
        this.isDisposed = false;

        Modal.instances.set(element, this);
    }

    show() {
        if (this.isDisposed || this.element.classList.contains('show')) {
            return;
        }

        this.cancelPendingOperations();

        // Preserve the original focus target when switching between modals.
        if (Modal.activeInstance && Modal.activeInstance !== this) {
            this.previousFocus = Modal.activeInstance.previousFocus;
            Modal.activeInstance.hideImmediately();
        } else {
            this.previousFocus = document.activeElement;
        }

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

        // Force layout before applying the visible state so CSS transitions run.
        this.forceReflow(this.backdrop);

        this.element.classList.add('show');
        this.backdrop?.classList.add('show');
        this.focusInitialElement();
    }

    forceReflow(element) {
        return element.offsetHeight;
    }

    hide() {
        if (
            this.isDisposed ||
            !this.element.classList.contains('show') ||
            this.isTransitioning
        ) {
            return;
        }

        this.isTransitioning = true;
        const transitionToken = ++this.transitionToken;

        this.element.classList.remove('show');
        this.backdrop?.classList.remove('show');

        this.element.setAttribute('aria-hidden', 'true');
        this.element.removeAttribute('aria-modal');

        const completeHide = () => {
            if (transitionToken !== this.transitionToken || this.isDisposed) {
                return;
            }

            this.completeHide();
        };

        Promise.all([
            Modal.waitForTransition(this.element),
            Modal.waitForTransition(this.backdrop)
        ]).then(completeHide, completeHide);
    }

    toggle() {
        if (this.element.classList.contains('show')) {
            this.hide();
            return;
        }

        this.show();
    }

    dispose() {
        if (this.isDisposed) {
            return;
        }

        this.hideImmediately();
        Modal.instances.delete(this.element);

        this.element = null;
        this.backdrop = null;
        this.previousFocus = null;
        this.isDisposed = true;
    }

    createBackdrop() {
        if (
            this.backdrop ||
            this.element.dataset.bsBackdrop === 'false'
        ) {
            return;
        }

        this.backdrop = document.createElement('div');
        this.backdrop.className = `${MODAL_BACKDROP_CLASS} fade`;
        document.body.appendChild(this.backdrop);

        // Apply the initial backdrop state before adding .show.
        this.forceReflow(this.backdrop);
    }

    focusableElements() {
        return Array.from(this.element.querySelectorAll(FOCUSABLE_SELECTOR)).filter((element) => {
            return (
                !element.hasAttribute('disabled') &&
                element.getAttribute('aria-hidden') !== 'true' &&
                element.getClientRects().length > 0
            );
        });
    }

    focusInitialElement() {
        const autofocusElement = this.element.querySelector('[autofocus]');
        const focusableElements = this.focusableElements();

        if (autofocusElement && focusableElements.includes(autofocusElement)) {
            autofocusElement.focus();
            return;
        }

        if (focusableElements.length > 0) {
            focusableElements[0].focus();
            return;
        }

        this.element.focus();
    }

    trapFocus(event) {
        const focusableElements = this.focusableElements();

        if (focusableElements.length === 0) {
            event.preventDefault();
            this.element.focus();
            return;
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements.at(-1);

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
            return;
        }

        if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }

    lockBody() {
        if (Modal.bodyLocked) {
            return;
        }

        const scrollbarWidth =
            window.innerWidth - document.documentElement.clientWidth;

        Modal.originalBodyPaddingRight = document.body.style.paddingRight;

        const currentPadding = Number.parseFloat(
            window.getComputedStyle(document.body).paddingRight
        ) || 0;

        if (scrollbarWidth > 0) {
            document.body.style.paddingRight = `${currentPadding + scrollbarWidth}px`;
        }

        document.body.classList.add('modal-open');
        Modal.bodyLocked = true;
    }

    unlockBody() {
        if (!Modal.bodyLocked) {
            return;
        }

        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = Modal.originalBodyPaddingRight;
        Modal.bodyLocked = false;
    }

    completeHide() {
        this.element.style.display = 'none';
        this.backdrop?.remove();
        this.backdrop = null;
        this.isTransitioning = false;

        if (Modal.activeInstance === this) {
            Modal.activeInstance = null;
            this.unlockBody();
            this.restoreFocus();
        }
    }

    hideImmediately() {
        this.cancelPendingOperations();

        this.element.classList.remove('show');
        this.element.style.display = 'none';
        this.element.setAttribute('aria-hidden', 'true');
        this.element.removeAttribute('aria-modal');

        this.backdrop?.remove();
        this.backdrop = null;
        this.isTransitioning = false;

        if (Modal.activeInstance === this) {
            Modal.activeInstance = null;
            this.unlockBody();
        }
    }

    restoreFocus() {
        if (this.previousFocus?.isConnected) {
            this.previousFocus.focus();
        }

        this.previousFocus = null;
    }

    cancelPendingOperations() {
        ++this.transitionToken;
        this.isTransitioning = false;
    }

    handleStaticBackdrop() {
        const dialog = this.element.querySelector('.modal-dialog') || this.element;

        if (this.element.classList.contains('modal-static')) {
            return;
        }

        this.element.classList.add('modal-static');

        const removeStaticClass = () => {
            this.element.classList.remove('modal-static');
        };

        Modal.waitForTransition(dialog).then(
            removeStaticClass,
            removeStaticClass
        );
    }

    static getInstance(element) {
        return Modal.instances.get(element) ?? null;
    }

    static getOrCreateInstance(element) {
        return Modal.getInstance(element) ?? new Modal(element);
    }

    static waitForTransition(element) {
        if (!element) {
            return Promise.resolve();
        }

        const style = window.getComputedStyle(element);
        const durations = Modal.parseCssTimes(style.transitionDuration);
        const delays = Modal.parseCssTimes(style.transitionDelay);
        const transitionCount = Math.max(durations.length, delays.length);

        let maxDuration = 0;

        for (let index = 0; index < transitionCount; index += 1) {
            const duration = durations[index % durations.length] || 0;
            const delay = delays[index % delays.length] || 0;

            maxDuration = Math.max(maxDuration, duration + delay);
        }

        if (maxDuration <= 0) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            let timeoutId;

            const finish = () => {
                element.removeEventListener('transitionend', onTransitionEnd);
                window.clearTimeout(timeoutId);
                resolve();
            };

            const onTransitionEnd = (event) => {
                if (event.target === element) {
                    finish();
                }
            };

            timeoutId = window.setTimeout(finish, maxDuration + 50);
            element.addEventListener('transitionend', onTransitionEnd);
        });
    }

    static parseCssTimes(value) {
        return value.split(',').map((time) => {
            const trimmedTime = time.trim();
            const numericValue = Number.parseFloat(trimmedTime);

            if (Number.isNaN(numericValue)) {
                return 0;
            }

            return trimmedTime.endsWith('ms')
                ? numericValue
                : numericValue * 1000;
        });
    }

    static handleBackdropClick(modal) {
        const instance = Modal.getInstance(modal);

        if (!instance || Modal.activeInstance !== instance) {
            return;
        }

        const backdropOption = modal.dataset.bsBackdrop;

        if (backdropOption === 'static') {
            instance.handleStaticBackdrop();
            return;
        }

        if (backdropOption !== 'false') {
            instance.hide();
        }
    }

    static init() {
        if (Modal.initialized) {
            return;
        }

        Modal.initialized = true;

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const trigger = event.target.closest(MODAL_TOGGLE_SELECTOR);

            if (trigger) {
                event.preventDefault();

                const target = trigger.dataset.bsTarget
                    || trigger.getAttribute('href');

                if (target?.startsWith('#')) {
                    const modal = document.getElementById(target.slice(1));

                    if (modal?.matches(MODAL_SELECTOR)) {
                        Modal.getOrCreateInstance(modal).show();
                    }
                }

                return;
            }

            const dismiss = event.target.closest(MODAL_DISMISS_SELECTOR);

            if (dismiss) {
                const modal = dismiss.closest(MODAL_SELECTOR);

                if (modal) {
                    Modal.getOrCreateInstance(modal).hide();
                }

                return;
            }

            if (event.target.classList.contains(MODAL_BACKDROP_CLASS)) {
                const activeModal = Modal.activeInstance?.element;

                if (activeModal) {
                    Modal.handleBackdropClick(activeModal);
                }

                return;
            }

            if (event.target.matches(MODAL_SELECTOR)) {
                Modal.handleBackdropClick(event.target);
            }
        });

        document.addEventListener('keydown', (event) => {
            const instance = Modal.activeInstance;

            if (!instance) {
                return;
            }

            if (event.key === 'Escape') {
                if (instance.element.dataset.bsKeyboard === 'false') {
                    return;
                }

                event.preventDefault();
                instance.hide();
                return;
            }

            if (event.key === 'Tab') {
                instance.trapFocus(event);
            }
        });

        // Keep keyboard focus inside the active modal.
        document.addEventListener('focusin', (event) => {
            const instance = Modal.activeInstance;

            if (!instance || instance.element.contains(event.target)) {
                return;
            }

            instance.focusInitialElement();
        });
    }
}

// Install delegated event handlers once when this module is loaded.
Dropdown.init();
Modal.init();

export { Dropdown, Modal };
