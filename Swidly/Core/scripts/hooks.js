let _idx = 0;
let _hooks = [];

// create a use state hook
// @param initValue
// @return [state, setState]
export class UseState {
    constructor(initValue = null) {
        this.hookIdx = _idx; // Store the current index for this instance
        this.hiddenDiv = document.createElement('div');
        this.hiddenDiv.style.display = 'none';
        this.hiddenDiv.setAttribute('data-hook', this.hookIdx);
        document.body.appendChild(this.hiddenDiv);

        _hooks[this.hookIdx] = _hooks[this.hookIdx] || initValue;
        this.hiddenDiv.setAttribute('data-hook-value', _hooks[this.hookIdx]);

        this.setState = (newValue) => {
            if (typeof newValue === 'function') {
                _hooks[this.hookIdx] = newValue(_hooks[this.hookIdx]);
            } else {
                _hooks[this.hookIdx] = newValue;
                document.body.querySelector(`[data-hook="${this.hookIdx}"]`).setAttribute('data-hook-value', _hooks[this.hookIdx]);
            }
        };

        this.value = _hooks[this.hookIdx];
        _idx++;
    }

    get value() {
        return this._value;
    }

    set value(newValue) {
        document.body.querySelector(`[data-hook="${this.hookIdx}"]`).setAttribute('data-hook-value', _hooks[this.hookIdx]);
        this._value = newValue;
    }
}

export function useState(initValue = null) {
    const hiddenDiv = document.createElement('div');
    hiddenDiv.style.display = 'none';
    hiddenDiv.setAttribute('data-hook', _idx);
    document.body.appendChild(hiddenDiv);

    _hooks[_idx] = _hooks[_idx] || initValue;
    hiddenDiv.setAttribute('data-hook-value', _hooks[_idx]);

    const setState = (newValue) => {
        if (typeof newValue === 'function') {
            _hooks[_idx] = newValue(_hooks[_idx]);
        } else {
            _hooks[_idx] = newValue;
        }
    _idx++;

    return [value, setState];
}

// create a use effect hook
// @param callback
// @param dependencies
export function useEffect(callback, dependencies = []) {
    const oldDependencies = _hooks[_idx];
    let hasChanged = true;

    if (oldDependencies) {
        hasChanged = dependencies.some((dep, index) => {
            return !Object.is(dep, oldDependencies[index]);
        });
    }

    if (hasChanged) {
        callback();
        _hooks[_idx] = dependencies;
    }

    _idx++;
}

// create a use layout effect hook
// @param callback
// @param dependencies
export function useLayoutEffect(callback, dependencies = []) {
    const oldDependencies = _hooks[_idx];
    let hasChanged = true;

    if (oldDependencies) {
        hasChanged = dependencies.some((dep, index) => {
            return !Object.is(dep, oldDependencies[index]);
        });
    }

    if (hasChanged) {
        callback();
        _hooks[_idx] = dependencies;
    }

    _idx++;
}

// create a memoized value hook
// @param callback
// @param dependencies
export function useMemo(callback, dependencies = []) {
    const oldDependencies = _hooks[_idx];
    let hasChanged = true;
    if (oldDependencies) {
        hasChanged = dependencies.some((dep, index) => {
            return !Object.is(dep, oldDependencies[index]);
        });
    }

    if (hasChanged) {
        _hooks[_idx] = callback();
    }

    _idx++;

    return _hooks[_idx - 1];
}

// create a memoized callback hook
// @param callback
// @param dependencies
export function useCallback(callback, dependencies = []) {
    const oldDependencies = _hooks[_idx];
    let hasChanged = true;
    if (oldDependencies) {
        hasChanged = dependencies.some((dep, index) => {
            return !Object.is(dep, oldDependencies[index]);
        });
    }

    if (hasChanged) {
        _hooks[_idx] = callback;
    }

    _idx++;

    return _hooks[_idx - 1];
}

// create a reference hook
// @param value - initial value
// @returns {object} - reference object with a current property
export function useRef(value = null) {
    const ref = {
        current: value
    };

    _idx++;

    return ref;
}

/**
 * Hook to access context value
 * @param {*} context - The context object created by createContext
 * @returns {*} - The current context value
 */
export function useContext(context) {
    const value = context._currentValue;
    _idx++;

    return value;
}

// create a use effect hook
// @param callback
// @param dependencies
export function useReducer(reducer, initialState) {
    const [state, setState] = useState(initialState);

    const dispatch = (action) => {
        const newState = reducer(state, action);
        setState(newState);
    };

    _idx++;

    return [state, dispatch];
}

// create a use effect hook
// @param callback
// @param dependencies
export function useImperativeHandle(ref, createHandle, dependencies = []) {
    const oldDependencies = _hooks[_idx];
    let hasChanged = true;

    if (oldDependencies) {
        hasChanged = dependencies.some((dep, index) => {
            return !Object.is(dep, oldDependencies[index]);
        });
    }

    if (hasChanged) {
        ref.current = createHandle();
        _hooks[_idx] = dependencies;
    }

    _idx++;
}

useReducer((state, action) => {
    switch (action.type) {
        case 'increment':
            return state + 1;
        case 'decrement':
            return state - 1;
        default:
            throw new Error();
    }
}, 0);

/**
 * Create a watchable state with callback notification
 * @param {Object} initialState - Initial state object
 * @returns {Object} - State proxy and methods
 */
export function createWatchableState(initialState = {}) {
    const listeners = new Map();
    let currentWatcher = null;

    // Tracks dependencies automatically
    function watch(fn) {
        function wrapperFn() {
            currentWatcher = wrapperFn;
            const result = fn();
            currentWatcher = null;
            return result;
        }

        // Execute once to collect dependencies
        return wrapperFn();
    }

    // Add property listener
    function addListener(property, fn) {
        if (!listeners.has(property)) {
            listeners.set(property, new Set());
        }
        listeners.get(property).add(fn);
    }

    // Create reactive state
    const state = new Proxy(initialState, {
        get(target, property) {
            if (currentWatcher) {
                addListener(property, currentWatcher);
            }
            return target[property];
        },
        set(target, property, value) {
            if (value !== target[property]) {
                target[property] = value;

                // Notify all listeners
                const propertyListeners = listeners.get(property);
                if (propertyListeners) {
                    propertyListeners.forEach(listener => {
                        setTimeout(() => listener(), 0); // Async to avoid cascade
                    });
                }
            }
            return true;
        }
    });

    return { state, watch };
}
/*
// Example usage
const { state, watch } = createWatchableState({ count: 0, name: 'John' });

// This function will re-run when state.count changes
watch(() => {
    console.log(`The count is now: ${state.count}`);
    document.getElementById('counter').textContent = state.count;
});

// This one watches name
watch(() => {
    console.log(`Name changed to: ${state.name}`);
    document.getElementById('name').textContent = state.name;
});

// Changing state triggers the watchers
document.getElementById('increment').addEventListener('click', () => {
    state.count++; // This triggers the first watcher
});

document.getElementById('changeName').addEventListener('click', () => {
    state.name = 'Jane'; // This triggers the second watcher
});*/

/**
 * Create a form state tracker that monitors dirty fields
 * @param {Object} initialValues - Initial form values
 * @returns {Object} - Form state with tracking capabilities
 */
export function createFormState(initialValues = {}) {
    // Clone the initial values to prevent reference issues
    const initial = JSON.parse(JSON.stringify(initialValues));

    // Track which fields have been modified
    const dirtyFields = new Set();

    // Create proxy to track changes
    const formState = new Proxy({...initialValues}, {
        set(target, property, value) {
            // Check if value is different from initial value
            if (JSON.stringify(value) !== JSON.stringify(initial[property])) {
                dirtyFields.add(property);
            } else {
                // Value is back to initial, no longer dirty
                dirtyFields.delete(property);
            }

            // Set the value
            target[property] = value;
            return true;
        }
    });

    // Return state and helper functions
    return {
        // The reactive form state
        formState,

        // Check if a specific field is dirty
        isDirty: (field) => dirtyFields.has(field),

        // Check if any field is dirty
        isFormDirty: () => dirtyFields.size > 0,

        // Get all dirty field names
        getDirtyFields: () => Array.from(dirtyFields),

        // Reset a specific field to its initial value
        resetField: (field) => {
            formState[field] = initial[field];
            dirtyFields.delete(field);
        },

        // Reset all fields to initial values
        resetForm: () => {
            Object.keys(initial).forEach(key => {
                formState[key] = initial[key];
            });
            dirtyFields.clear();
        },

        // Mark all fields as clean (useful after saving)
        markAsClean: () => {
            Object.keys(formState).forEach(key => {
                initial[key] = formState[key];
            });
            dirtyFields.clear();
        }
    };
}

// Display dirty state in UI
export function updateDirtyIndicators() {
    // Show which fields are dirty
    document.querySelectorAll('input').forEach(input => {
        const fieldName = input.id;
        const indicator = document.querySelector(`#${fieldName}-indicator`);
        if (indicator) {
            indicator.style.display = isDirty(fieldName) ? 'inline' : 'none';
        }
    });

    // Enable/disable save button based on form dirty state
    const saveButton = document.getElementById('save-button');
    if (saveButton) {
        saveButton.disabled = !isFormDirty();
    }

    // Show list of modified fields (for debugging)
    const dirtyList = document.getElementById('dirty-fields-list');
    if (dirtyList) {
        dirtyList.textContent = getDirtyFields().join(', ');
    }
}

export class useSinglePage {
    #events = new Map();
    #elements = {
        app: null,
        sidebar: null
    };
    #selectors = {
        spLink: '[data-sp-link]',
        spForm: '[data-sp-form]',
    };

    /**
     * Create a new single page application handler
     * @param {Object} options - Configuration options
     */
    constructor(options = {}) {
        const defaults = {
            onBeforeFetch: () => console.log('before fetch'),
            onAfterFetch: () => console.log('after fetch'),
            delimiter: '|',
            appElementId: 'app',
            sidebarElementId: 'sidebar'
        };

        this.settings = { ...defaults, ...options };
        this.titleDelimiter = ` ${this.settings.delimiter} `;

        // Cache DOM elements
        this.#elements.app = document.getElementById(this.settings.appElementId);
        this.#elements.sidebar = document.getElementById(this.settings.sidebarElementId);

        // Initialize event listeners
        this.#initEventListeners();
        this.#setupHistoryListener();

        // Register event handlers
        this.on('beforeFetch', this.settings.onBeforeFetch);
        this.on('afterFetch', this.settings.onAfterFetch);

        // Load initial page
        this.loadPage();

        // Emit initialization event
        this.emit('onInit');
    }

    /**
     * Initialize all event listeners for interactive elements
     */
    #initEventListeners() {
        this.#attachListenersToElements(document.querySelectorAll(this.#selectors.spLink), this.#addClickListener);
        this.#attachListenersToElements(document.querySelectorAll(this.#selectors.spForm), this.#addSubmitListener);
    }

    /**
     * Helper method to attach listeners to a collection of elements
     * @param {NodeList} elements - Elements to attach listeners to
     * @param {Function} listenerFn - Listener function to attach
     */
    #attachListenersToElements(elements, listenerFn) {
        Array.from(elements).forEach(element => listenerFn.call(this, element));
    }

    /**
     * Setup history state listener
     */
    #setupHistoryListener() {
        window.addEventListener('popstate', (event) => {
            const state = event.state || {};
            this.loadPage(state.isMenuLink || false);
        });
    }

    /**
     * Register an event handler
     * @param {string} name - Event name
     * @param {Function} callback - Event handler callback
     * @returns {useSinglePage} - Returns this for chaining
     */
    on(name, callback) {
        if (!this.#events.has(name)) {
            this.#events.set(name, []);
        }

        this.#events.get(name).push(callback);
        return this;
    }

    /**
     * Emit an event with arguments
     * @param {string} evt - Event name
     * @param {...any} args - Arguments to pass to event handlers
     * @returns {useSinglePage} - Returns this for chaining
     */
    emit(evt, ...args) {
        if (this.#events.has(evt)) {
            const callbacks = this.#events.get(evt);
            callbacks.forEach(callback => {
                callback.apply(this, args);
            });
        }
        return this;
    }

    /**
     * Add click event listener to regular page links
     * @param {HTMLElement} element - Link element
     */
    #addClickListener(element) {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            const path = event.currentTarget.getAttribute('href');
            history.pushState({ isMenuLink: false }, null, path);
            this.loadPage();
        });
    }

    /**
     * Add submit event listener to forms
     * @param {HTMLFormElement} element - Form element
     */
    #addSubmitListener(element) {
        element.addEventListener('submit', async (event) => {
            event.preventDefault();
            const path = event.currentTarget.getAttribute('action');
            const formData = new FormData(event.currentTarget);

            try {
                const response = await this.fetchData(path, formData, 'POST');
                this.emit('formSubmitted', response);
            } catch (error) {
                this.emit('formError', error);
            }
        });
    }

    /**
     * Load a page or menu item
     * @param {boolean} isMenuItem - Whether this is a menu item
     * @returns {Promise<void>}
     */
    async loadPage(isMenuItem = false) {
        try {
            this.emit('beforeFetch');

            // Reset hooks index if using React-like hooks
            if (typeof _idx !== 'undefined') {
                _idx = 0;
            }

            const path = location.pathname === '/dashboard' ? '/dashboard/home' : location.pathname;
            const response = await this.fetchData(path);
            response.path = path;

            // Update content based on what was requested
            if (isMenuItem && this.#elements.sidebar) {
                this.#elements.sidebar.innerHTML = response.data.content;
                this.#refreshEventListeners(this.#elements.sidebar, 'menu');
            } else if (this.#elements.app) {
                this.#elements.app.innerHTML = response.data.content;
                this.#refreshEventListeners(this.#elements.app, 'content');
            }

            // Update page title
            this.#updatePageTitle(response.data.title);

            this.emit('afterFetch', response);
        } catch (error) {
            this.emit('fetchError', error);
            console.error('Failed to load page:', error);
        }
    }

    /**
     * Refresh event listeners for newly added elements
     * @param {HTMLElement} container - Container element
     * @param {string} type - Type of refresh ('menu' or 'content')
     */
    #refreshEventListeners(container, type) {
        if (type === 'content' || type === 'both') {
            this.#attachListenersToElements(container.querySelectorAll(this.#selectors.spLink), this.#addClickListener);
            this.#attachListenersToElements(container.querySelectorAll(this.#selectors.spForm), this.#addSubmitListener);
        }
    }

    /**
     * Update the page title
     * @param {string|null} newTitle - New title to set
     */
    #updatePageTitle(newTitle) {
        const baseTitle = this.#resetTitle();
        document.title = newTitle
            ? `${newTitle}${this.titleDelimiter}${baseTitle}`
            : baseTitle;
    }

    /**
     * Reset and get the base title
     * @returns {string} - Base title
     */
    #resetTitle() {
        let currentTitle = document.title;
        const delimiterPos = currentTitle.lastIndexOf(this.titleDelimiter);

        if (delimiterPos >= 0) {
            currentTitle = currentTitle.slice(delimiterPos + this.titleDelimiter.length);
        }

        return currentTitle;
    }

    /**
     * Fetch data from the server
     * @param {string} path - URL path
     * @param {Object|FormData} data - Data to send
     * @param {string} requestType - HTTP method (GET, POST, etc.)
     * @returns {Promise<Object>} - Response data
     */
    async fetchData(path, data = {}, requestType = 'GET') {
        const options = {
            method: requestType.toUpperCase(),
            cache: 'no-cache',
        };

        if (requestType.toLowerCase() === 'post') {
            if (!(data instanceof FormData)) {
                data = JSON.stringify(data);
                options.headers = {
                    'Content-Type': 'application/json'
                };
            }
            options.body = data;
        } else if (requestType.toLowerCase() === 'get') {
            options.headers = {
                'Content-Type': 'application/json'
            };
        }

        try {
            const request = new Request(path, options);
            const response = await fetch(request);

            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            this.emit('fetchError', error);
            throw error;
        }
    }
}

/**
 * FilePreview - A robust utility for handling file previews before upload
 * @param {Object} options - Configuration options
 * @param {string|HTMLElement} options.fileInput - File input element or selector
 * @param {string|HTMLElement} options.previewContainer - Container element or selector for previews
 * @param {boolean} options.multiple - Whether to allow multiple file selection (default: false)
 * @param {boolean} options.dragDrop - Whether to enable drag and drop functionality (default: false)
 * @param {string} options.dragDropSelector - Selector for drag and drop zone (required if dragDrop is true)
 * @param {Function} options.onFileAdded - Callback when file is added (receives file object)
 * @param {Function} options.onFileRemoved - Callback when file is removed (receives fileId)
 * @param {Object} options.previewStyles - Custom CSS styles for preview elements
 * @param {Array} options.allowedTypes - Array of allowed MIME types (default: all)
 * @param {number} options.maxFileSize - Maximum file size in bytes (default: no limit)
 * @returns {Object} - Public methods and properties for the FilePreview instance
 */
export function FilePreview(options) {
    // Default configuration
    const config = {
        fileInput: null,
        previewContainer: null,
        multiple: false,
        dragDrop: false,
        dragDropSelector: null,
        onFileAdded: null,
        onFileRemoved: null,
        previewStyles: {},
        allowedTypes: [],
        maxFileSize: 0, // 0 means no limit
        ...options
    };

    // Store selected files
    const selectedFiles = new Map();

    // DOM Elements
    const fileInput = typeof config.fileInput === 'string' ?
        document.querySelector(config.fileInput) : config.fileInput;

    const previewContainer = typeof config.previewContainer === 'string' ?
        document.querySelector(config.previewContainer) : config.previewContainer;

    let dragDropZone = null;
    if (config.dragDrop && config.dragDropSelector) {
        dragDropZone = typeof config.dragDropSelector === 'string' ?
            document.querySelector(config.dragDropSelector) : config.dragDropSelector;
    }

    // Validation
    if (!fileInput) {
        throw new Error('FilePreview: File input element not found');
    }

    if (!previewContainer) {
        throw new Error('FilePreview: Preview container element not found');
    }

    if (config.dragDrop && !dragDropZone) {
        throw new Error('FilePreview: Drag and drop zone not found');
    }

    // Configure file input
    fileInput.multiple = config.multiple;

    // Apply default styling to preview container
    previewContainer.style.display = 'flex';
    previewContainer.style.flexWrap = 'wrap';
    previewContainer.style.gap = '10px';
    previewContainer.style.marginTop = '10px';

    // Apply custom styles
    Object.assign(previewContainer.style, config.previewStyles);

    /**
     * Initialize the file preview functionality
     */
    function init() {
        // Set up file input change handler
        fileInput.addEventListener('change', handleFileSelection);

        // Set up drag and drop if enabled
        if (config.dragDrop && dragDropZone) {
            setupDragAndDrop();
        }
    }

    /**
     * Set up drag and drop event handlers
     */
    function setupDragAndDrop() {
        // Add styling to drag drop zone
        dragDropZone.style.border = '2px dashed #ccc';
        dragDropZone.style.borderRadius = '8px';
        dragDropZone.style.padding = '20px';
        dragDropZone.style.textAlign = 'center';
        dragDropZone.style.cursor = 'pointer';
        dragDropZone.style.transition = 'all 0.3s';

        // Set up event listeners
        dragDropZone.addEventListener('click', () => fileInput.click());

        dragDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dragDropZone.style.borderColor = '#3498db';
            dragDropZone.style.backgroundColor = '#f8f9fa';
        });

        dragDropZone.addEventListener('dragleave', () => {
            dragDropZone.style.borderColor = '#ccc';
            dragDropZone.style.backgroundColor = '';
        });

        dragDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dragDropZone.style.borderColor = '#ccc';
            dragDropZone.style.backgroundColor = '';

            if (e.dataTransfer.files.length > 0) {
                handleFiles(e.dataTransfer.files);
            }
        });
    }

    /**
     * Handle file selection from input
     * @param {Event} e - Change event from file input
     */
    function handleFileSelection(e) {
        handleFiles(e.target.files);
        // Reset file input so change event works if same file is selected again
        fileInput.value = '';
    }

    /**
     * Process files from input or drag and drop
     * @param {FileList} files - Files selected by the user
     */
    function handleFiles(files) {
        // If multiple is false, clear previous files
        if (!config.multiple) {
            clearPreviews();
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            // Check file type if allowedTypes is specified
            if (config.allowedTypes.length > 0 && !isFileTypeAllowed(file)) {
                notifyError(`File type not allowed: ${file.type}`);
                continue;
            }

            // Check file size if maxFileSize is specified
            if (config.maxFileSize > 0 && file.size > config.maxFileSize) {
                notifyError(`File too large: ${formatBytes(file.size)}. Maximum allowed: ${formatBytes(config.maxFileSize)}`);
                continue;
            }

            addFilePreview(file);
        }
    }

    /**
     * Check if file type is allowed
     * @param {File} file - File to check
     * @returns {boolean} - Whether file type is allowed
     */
    function isFileTypeAllowed(file) {
        return config.allowedTypes.some(type => {
            if (type.endsWith('/*')) {
                // Handle wildcard types like image/*
                const category = type.split('/')[0];
                return file.type.startsWith(`${category}/`);
            }
            return file.type === type;
        });
    }

    /**
     * Show error notification
     * @param {string} message - Error message
     */
    function notifyError(message) {
        console.error(message);
        // You could implement a toast notification here
    }

    /**
     * Add file preview to container
     * @param {File} file - File to preview
     */
    function addFilePreview(file) {
        const fileId = `file-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;

        // Store the file in our collection
        selectedFiles.set(fileId, file);

        // Call onFileAdded callback if provided
        if (typeof config.onFileAdded === 'function') {
            config.onFileAdded(file);
        }

        // Create preview container
        const previewItem = document.createElement('div');
        previewItem.style.width = '200px';
        previewItem.style.border = '1px solid #ddd';
        previewItem.style.borderRadius = '5px';
        previewItem.style.overflow = 'hidden';
        previewItem.style.position = 'relative';
        previewItem.dataset.fileId = fileId;

        // Add remove button if multiple files allowed
        if (config.multiple || selectedFiles.size <= 1) {
            const removeBtn = document.createElement('div');
            removeBtn.style.position = 'absolute';
            removeBtn.style.top = '5px';
            removeBtn.style.right = '5px';
            removeBtn.style.background = 'rgba(255,255,255,0.7)';
            removeBtn.style.borderRadius = '50%';
            removeBtn.style.width = '25px';
            removeBtn.style.height = '25px';
            removeBtn.style.lineHeight = '25px';
            removeBtn.style.textAlign = 'center';
            removeBtn.style.cursor = 'pointer';
            removeBtn.innerHTML = '×';
            removeBtn.addEventListener('click', () => removeFile(fileId));
            previewItem.appendChild(removeBtn);
        }

        // Create preview based on file type
        if (file.type.match('image.*')) {
            createImagePreview(file, previewItem);
        } else if (file.type === 'application/pdf') {
            createPDFPreview(file, previewItem);
        } else if (file.type.match('video.*')) {
            createVideoPreview(file, previewItem);
        } else if (file.type.match('audio.*')) {
            createAudioPreview(file, previewItem);
        } else {
            createGenericPreview(file, previewItem);
        }

        // Add filename
        const fileNameDiv = document.createElement('div');
        fileNameDiv.style.padding = '5px';
        fileNameDiv.style.textOverflow = 'ellipsis';
        fileNameDiv.style.overflow = 'hidden';
        fileNameDiv.style.whiteSpace = 'nowrap';
        fileNameDiv.style.backgroundColor = '#f5f5f5';
        fileNameDiv.textContent = file.name;
        previewItem.appendChild(fileNameDiv);

        // Add file info tooltip
        previewItem.title = `${file.name} (${formatBytes(file.size)})`;

        // Add to preview container
        previewContainer.appendChild(previewItem);
    }

    /**
     * Create image preview
     * @param {File} file - Image file
     * @param {HTMLElement} container - Container element
     */
    function createImagePreview(file, container) {
        const img = document.createElement('img');
        img.style.width = '100%';
        img.style.height = '150px';
        img.style.objectFit = 'cover';

        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);

        container.appendChild(img);
    }

    /**
     * Create PDF preview
     * @param {File} file - PDF file
     * @param {HTMLElement} container - Container element
     */
    function createPDFPreview(file, container) {
        const previewFrame = document.createElement('iframe');
        previewFrame.style.width = '100%';
        previewFrame.style.height = '150px';
        previewFrame.style.border = 'none';

        const reader = new FileReader();
        reader.onload = (e) => {
            previewFrame.src = e.target.result;
        };
        reader.readAsDataURL(file);

        container.appendChild(previewFrame);
    }

    /**
     * Create video preview
     * @param {File} file - Video file
     * @param {HTMLElement} container - Container element
     */
    function createVideoPreview(file, container) {
        const video = document.createElement('video');
        video.style.width = '100%';
        video.style.height = '150px';
        video.controls = true;

        const reader = new FileReader();
        reader.onload = (e) => {
            video.src = e.target.result;
        };
        reader.readAsDataURL(file);

        container.appendChild(video);
    }

    /**
     * Create audio preview
     * @param {File} file - Audio file
     * @param {HTMLElement} container - Container element
     */
    function createAudioPreview(file, container) {
        const audioContainer = document.createElement('div');
        audioContainer.style.height = '150px';
        audioContainer.style.display = 'flex';
        audioContainer.style.alignItems = 'center';
        audioContainer.style.justifyContent = 'center';
        audioContainer.style.backgroundColor = '#f1f1f1';

        const audio = document.createElement('audio');
        audio.controls = true;
        audio.style.width = '90%';

        const reader = new FileReader();
        reader.onload = (e) => {
            audio.src = e.target.result;
        };
        reader.readAsDataURL(file);

        audioContainer.appendChild(audio);
        container.appendChild(audioContainer);
    }

    /**
     * Create generic file preview with icon
     * @param {File} file - Any file
     * @param {HTMLElement} container - Container element
     */
    function createGenericPreview(file, container) {
        const iconContainer = document.createElement('div');
        iconContainer.style.height = '150px';
        iconContainer.style.display = 'flex';
        iconContainer.style.alignItems = 'center';
        iconContainer.style.justifyContent = 'center';
        iconContainer.style.backgroundColor = '#f1f1f1';
        iconContainer.style.color = '#666';
        iconContainer.style.fontSize = '24px';

        iconContainer.innerHTML = getIconForFileType(file.type);

        container.appendChild(iconContainer);
    }

    /**
     * Get an icon representation for a file type
     * @param {string} fileType - MIME type
     * @returns {string} - Icon representation
     */
    function getIconForFileType(fileType) {
        const fileTypeMap = {
            'application/pdf': '📄 PDF',
            'application/msword': '📝 DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝 DOCX',
            'application/vnd.ms-excel': '📊 XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊 XLSX',
            'text/plain': '📄 TXT',
            'text/csv': '📊 CSV',
            'application/json': '{ } JSON',
            'application/zip': '📦 ZIP',
            'application/x-rar-compressed': '📦 RAR'
        };

        // Check exact matches first
        if (fileTypeMap[fileType]) {
            return fileTypeMap[fileType];
        }

        // Check by category
        if (fileType.startsWith('audio/')) return '🎵 Audio';
        if (fileType.startsWith('video/')) return '🎬 Video';
        if (fileType.startsWith('image/')) return '🖼️ Image';
        if (fileType.startsWith('text/')) return '📄 Text';

        // Default icon
        return '📂 File';
    }

    /**
     * Remove a file from the preview
     * @param {string} fileId - ID of the file to remove
     */
    function removeFile(fileId) {
        const file = selectedFiles.get(fileId);

        // Remove from collection
        selectedFiles.delete(fileId);

        // Remove from DOM
        const previewElement = previewContainer.querySelector(`[data-file-id="${fileId}"]`);
        if (previewElement) {
            previewContainer.removeChild(previewElement);
        }

        // Call onFileRemoved callback if provided
        if (typeof config.onFileRemoved === 'function' && file) {
            config.onFileRemoved(fileId, file);
        }
    }

    /**
     * Clear all previews
     */
    function clearPreviews() {
        // Clear the file collection
        selectedFiles.forEach((file, fileId) => {
            if (typeof config.onFileRemoved === 'function') {
                config.onFileRemoved(fileId, file);
            }
        });

        selectedFiles.clear();

        // Clear the preview container
        while (previewContainer.firstChild) {
            previewContainer.removeChild(previewContainer.firstChild);
        }
    }

    /**
     * Format bytes to human-readable size
     * @param {number} bytes - Size in bytes
     * @param {number} decimals - Decimal places (default: 2)
     * @returns {string} - Formatted size string
     */
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Get all selected files
     * @returns {Array} - Array of selected files
     */
    function getFiles() {
        return Array.from(selectedFiles.values());
    }

    /**
     * Get files as FormData object for easy upload
     * @param {string} fieldName - Name of the file field (default: 'files')
     * @returns {FormData} - FormData object with files added
     */
    function getFormData(fieldName = 'files') {
        const formData = new FormData();

        selectedFiles.forEach((file) => {
            if (config.multiple) {
                formData.append(fieldName + '[]', file);
            } else {
                formData.append(fieldName, file);
            }
        });

        return formData;
    }

    // Initialize
    init();

    // Return public API
    return {
        addFile: (file) => addFilePreview(file),
        removeFile,
        clearPreviews,
        getFiles,
        getFormData,
        getCount: () => selectedFiles.size
    };
}

/**
 * Creates and displays a customizable dialog
 *
 * @param {string} title - The title of the dialog
 * @param {string} message - The message to display in the dialog
 * @param {boolean} showConfirmButton - Whether to show a confirm button
 * @param {boolean} showCancelButton - Whether to show a cancel button
 * @param {string} confirmButtonText - Text for the confirm button
 * @param {string} cancelButtonText - Text for the cancel button
 * @returns {Promise} A promise that resolves with true if confirmed, false if canceled
 */
export function useShowDialog(title, message, showConfirmButton = true, showCancelButton = false, confirmButtonText = 'OK', cancelButtonText = 'Cancel') {
    return new Promise((resolve) => {
        // Create dialog element
        const dialog = document.createElement('dialog');
        dialog.className = 'custom-dialog fancy-scrollbar';

        // Create dialog content
        const dialogContent = document.createElement('div');
        dialogContent.className = 'dialog-content';

        const focusedInput = document.createElement('input');
        focusedInput.setAttribute('type', 'hidden');
        focusedInput.setAttribute('autoFocus', true);
        dialogContent.appendChild(focusedInput);

        // Add title if provided
        if (title) {
            const titleElement = document.createElement('h3');
            titleElement.className = 'dialog-title';
            titleElement.textContent = title;
            dialogContent.appendChild(titleElement);
        }

        // Add message
        const messageElement = document.createElement('p');
        messageElement.className = 'dialog-message';
        messageElement.innerHTML = message;
        dialogContent.appendChild(messageElement);

        // Create button container
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'dialog-buttons';

        // Add confirm button if needed
        if (showConfirmButton) {
            const confirmButton = document.createElement('button');
            confirmButton.className = 'btn btn-primary dialog-confirm border-radius';
            confirmButton.textContent = confirmButtonText;
            confirmButton.tabIndex = -1;
            confirmButton.addEventListener('click', () => {
                dialog.close();
                resolve(true);
            });
            buttonContainer.appendChild(confirmButton);
        }

        // Add cancel button if needed
        if (showCancelButton) {
            const cancelButton = document.createElement('button');
            cancelButton.className = 'btn btn-outline-secondary dialog-cancel border-radius';
            cancelButton.textContent = cancelButtonText;
            cancelButton.tabIndex = -1;
            cancelButton.addEventListener('click', () => {
                dialog.close();
                resolve(false);
            });
            buttonContainer.appendChild(cancelButton);
        }

        // Assemble dialog
        dialogContent.appendChild(buttonContainer);
        dialog.appendChild(dialogContent);

        // Add dialog to the document
        document.body.appendChild(dialog);

        // Add some basic styling
        const style = document.createElement('style');
        style.textContent = `
            .custom-dialog {
                padding: 1.5rem;
                border-radius: 0.5rem;
                border: 1px solid rgba(255, 255, 255, 0.4);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                max-width: 450px;
                width: 100%;
                background-color: inherit;
                color: whitesmoke;
            }
            .dialog-content {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            .dialog-title {
                margin: 0;
                padding-bottom: 0.9rem;
                border-bottom: 1px solid hsl(0deg 0% 100% / 30%);
            }
            .dialog-message {
                margin: 0;
            }
            .dialog-buttons {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
                margin-top: 0.5rem;
            }
            .custom-dialog::backdrop {
                background-color: rgba(0, 0, 0, 0.5);
            }
        `;
        dialog.appendChild(style);

        dialog.inert = true;
        // Show the dialog
        dialog.showModal();
        dialog.inert = false;

        // Handle ESC key and clicking outside to cancel
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            if (showCancelButton) {
                dialog.close();
                resolve(false);
            }
        });

        // Clean up the dialog when it's closed
        dialog.addEventListener('close', () => {
            document.body.removeChild(dialog);
        });
    });
}