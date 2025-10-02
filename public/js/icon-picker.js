/**
 * Font Awesome Icon Picker (Version 7.1 - Final & Robust)
 *
 * Changes:
 * - Refactored to correctly handle multiple pickers on the same page.
 * - Uses a single set of event listeners for the modal, delegating to the active picker.
 * - This fixes the bug where search only worked for the last instance.
 */

// Create a single, shared promise for the icon data to prevent multiple fetches
let iconDataPromise = null;
const fetchIconData = () => {
    if (!iconDataPromise) {
        iconDataPromise = fetch('/json/icons.json')
            .then(response => {
                if (!response.ok) throw new Error('Network response for icons.json was not ok');
                return response.json();
            })
            .then(data => {
                return Object.entries(data).flatMap(([key, value]) =>
                    value.styles.map(style => ({
                        id: key, style: style, prefix: style === 'brands' ? 'fab' : (style === 'regular' ? 'far' : 'fas')
                    }))
                );
            });
    }
    return iconDataPromise;
};

class IconPicker {
    // Static property to track the currently active picker instance
    static activeInstance = null;

    constructor(triggerElement) {
        if (!triggerElement) return;

        this.triggerElement = triggerElement;
        this.input = triggerElement.querySelector('input');
        this.iconPreview = triggerElement.querySelector('[data-preview-for="' + this.input.id + '"]');
        this.placeholder = triggerElement.querySelector('.icon-picker-placeholder');
        this.clearButton = triggerElement.querySelector('.icon-picker-clear');
        this.modal = document.getElementById('iconPickerModal');
        this.icons = [];
        this.isLoaded = false;

        this.init();
    }

    init() {
        this.triggerElement.addEventListener('click', (e) => {
            if (this.clearButton && (e.target === this.clearButton || e.target.parentElement === this.clearButton)) {
                return;
            }
            this.open();
        });

        if (this.clearButton) {
            this.clearButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectIcon('');
            });
        }
        
        this.updateVisualState();
        
        // One-time setup for global modal events
        if (!IconPicker.eventsAttached) {
            const modal = document.getElementById('iconPickerModal');
            modal.querySelector('.close').addEventListener('click', () => IconPicker.closeActive());
            modal.querySelector('#iconSearch').addEventListener('input', () => IconPicker.filterActive());
            window.addEventListener('click', (event) => {
                if (event.target === modal) { IconPicker.closeActive(); }
            });
            IconPicker.eventsAttached = true;
        }
    }
    
    updateVisualState() {
        const hasValue = this.input.value.trim() !== '';
        if (this.placeholder) this.placeholder.style.display = hasValue ? 'none' : 'block';
        if (this.clearButton) this.clearButton.style.display = hasValue ? 'inline-flex' : 'none';
    }

    async open() {
        IconPicker.activeInstance = this;
        this.modal.style.display = 'block';
        this.modal.querySelector('#iconSearch').focus();

        if (!this.isLoaded) {
            await this.loadIcons();
        }

        this.modal.querySelector('#iconSearch').value = '';
        this.modal.querySelector('#iconGrid').innerHTML = '<div class="icon-picker-message">Skriv i søkefeltet for å finne ikoner.</div>';
    }

    async loadIcons() {
        try {
            this.modal.querySelector('#iconGrid').innerHTML = '<div class="icon-picker-message">Laster inn ikoner...</div>';
            this.icons = await fetchIconData();
            this.isLoaded = true;
            this.modal.querySelector('#iconGrid').innerHTML = '<div class="icon-picker-message">Klar til å søke.</div>';
        } catch (error) {
            this.modal.querySelector('#iconGrid').innerHTML = '<div class="icon-picker-message error"><strong>Feil:</strong> Kunne ikke laste <code>/json/icons.json</code>.</div>';
            console.error('Error loading Font Awesome icons:', error);
        }
    }
    
    selectIcon(iconClass) {
        this.input.value = iconClass;
        this.iconPreview.className = iconClass;
        this.updateVisualState();
        IconPicker.closeActive();
    }

    // Static methods that operate on the active instance
    static closeActive() {
        if (IconPicker.activeInstance) {
            IconPicker.activeInstance.modal.style.display = 'none';
            IconPicker.activeInstance = null;
        }
    }

    static filterActive() {
        if (!IconPicker.activeInstance) return;

        const instance = IconPicker.activeInstance;
        const query = instance.modal.querySelector('#iconSearch').value.toLowerCase().trim();
        const grid = instance.modal.querySelector('#iconGrid');

        if (!instance.isLoaded) return;

        if (query.length < 2) {
            grid.innerHTML = '<div class="icon-picker-message">Skriv minst 2 tegn for å søke.</div>';
            return;
        }

        const filtered = instance.icons.filter(icon => icon.id.includes(query));
        
        grid.innerHTML = '';
        if (filtered.length === 0) {
            grid.innerHTML = '<div class="icon-picker-message">Ingen ikoner matchet søket.</div>';
            return;
        }
        
        filtered.slice(0, 200).forEach(icon => {
            const iconElement = document.createElement('div');
            iconElement.className = 'icon-item';
            const fullClass = `${icon.prefix} fa-${icon.id}`;
            iconElement.innerHTML = `<i class="${fullClass}"></i>`;
            iconElement.title = icon.id;
            iconElement.addEventListener('click', () => {
                instance.selectIcon(fullClass);
            });
            grid.appendChild(iconElement);
        });
    }
}