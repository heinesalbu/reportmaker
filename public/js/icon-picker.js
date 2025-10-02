/**
 * Font Awesome Icon Picker (Version 6 - Visual Polish)
 *
 * Changes:
 * - Hides the input field's text value for a cleaner look.
 * - Manages a separate placeholder element for better UX.
 * - Added a clear button to remove the selected icon.
 */
class IconPicker {
    constructor(triggerElement) {
        if (!triggerElement) return;

        this.triggerElement = triggerElement;
        this.input = triggerElement.querySelector('input');
        this.iconPreview = triggerElement.querySelector('[data-preview-for="' + this.input.id + '"]');
        this.placeholder = triggerElement.querySelector('.icon-picker-placeholder');
        this.clearButton = triggerElement.querySelector('.icon-picker-clear');

        this.modal = document.getElementById('iconPickerModal');
        this.searchBox = this.modal.querySelector('#iconSearch');
        this.iconGrid = this.modal.querySelector('#iconGrid');
        this.closeButton = this.modal.querySelector('.close');
        
        this.icons = [];
        this.isLoaded = false;

        this.init();
    }

    init() {
        this.triggerElement.addEventListener('click', (e) => {
            // Do not open modal if clear button was clicked
            if (this.clearButton && e.target === this.clearButton) {
                return;
            }
            this.open();
        });

        this.closeButton.addEventListener('click', () => this.close());
        this.searchBox.addEventListener('input', () => this.filterIcons());

        if (this.clearButton) {
            this.clearButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent modal from opening
                this.selectIcon(''); // Select an empty icon
            });
        }
        
        window.addEventListener('click', (event) => {
            if (event.target === this.modal) { this.close(); }
        });
        
        this.updateVisualState();
    }
    
    updateVisualState() {
        if (!this.input) return;
        const hasValue = this.input.value.trim() !== '';
        
        if (this.placeholder) {
            this.placeholder.style.display = hasValue ? 'none' : 'inline';
        }
        if (this.clearButton) {
            this.clearButton.style.display = hasValue ? 'inline-flex' : 'none';
        }
    }

    // ... open(), close(), loadIcons(), renderIcons(), filterIcons() remain the same ...
    async open() {
        this.modal.style.display = 'block';
        this.searchBox.focus();
        if (!this.isLoaded) { await this.loadIcons(); }
        this.searchBox.value = '';
        this.iconGrid.innerHTML = '<div class="icon-picker-message">Skriv i søkefeltet for å finne ikoner.</div>';
    }
    close() { this.modal.style.display = 'none'; }
    async loadIcons() {
        try {
            this.iconGrid.innerHTML = '<div class="icon-picker-message">Laster inn ikoner...</div>';
            const response = await fetch('/json/icons.json');
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            this.icons = Object.entries(data).flatMap(([key, value]) =>
                value.styles.map(style => ({
                    id: key, style: style, prefix: style === 'brands' ? 'fab' : (style === 'regular' ? 'far' : 'fas')
                }))
            );
            this.isLoaded = true;
            this.iconGrid.innerHTML = '<div class="icon-picker-message">Klar til å søke.</div>';
        } catch (error) {
            this.iconGrid.innerHTML = '<div class="icon-picker-message error"><strong>Feil:</strong> Kunne ikke laste <code>/json/icons.json</code>.</div>';
            console.error('Error loading Font Awesome icons:', error);
        }
    }
    renderIcons(iconsToRender) {
        this.iconGrid.innerHTML = '';
        if (iconsToRender.length === 0) {
            this.iconGrid.innerHTML = '<div class="icon-picker-message">Ingen ikoner matchet søket.</div>';
            return;
        }
        const cappedIcons = iconsToRender.slice(0, 200);
        cappedIcons.forEach(icon => {
            const iconElement = document.createElement('div');
            iconElement.className = 'icon-item';
            const fullClass = `${icon.prefix} fa-${icon.id}`;
            iconElement.innerHTML = `<i class="${fullClass}"></i>`;
            iconElement.title = icon.id;
            iconElement.addEventListener('click', () => { this.selectIcon(fullClass); });
            this.iconGrid.appendChild(iconElement);
        });
    }
    filterIcons() {
        const query = this.searchBox.value.toLowerCase().trim();
        if (query.length < 2) {
            this.iconGrid.innerHTML = '<div class="icon-picker-message">Skriv minst 2 tegn for å søke.</div>';
            return;
        }
        const filtered = this.icons.filter(icon => icon.id.includes(query));
        this.renderIcons(filtered);
    }
    
    selectIcon(iconClass) {
        if (this.input) { this.input.value = iconClass; }
        if (this.iconPreview) { this.iconPreview.className = iconClass; }
        this.updateVisualState();
        this.close();
    }
}