let _idx = 0;
let _hooks = [];

// create a use state hook
// @param initValue
// @return [state, setState]
export class UseState() {
    constructor(initValue = null) {
        this.hiddenDiv = document.createElement('div');
        this.hiddenDiv.style.display = 'none';
        this.hiddenDiv.setAttribute('data-hook', _idx);
        document.body.appendChild(this.hiddenDiv);

        _hooks[_idx] = _hooks[_idx] || initValue;
        this.hiddenDiv.setAttribute('data-hook-value', _hooks[_idx]);

        this.setState = (newValue) => {
            if (typeof newValue === 'function') {
                _hooks[_idx] = newValue(_hooks[_idx]);
            } else {
                _hooks[_idx] = newValue;
                document.body.querySelector(`[data-hook="${_idx}"]`).setAttribute('data-hook-value', _hooks[_idx]);
            }
        };

        this.value = _hooks[_idx];
        _idx++;
    }

    get value() {
        return this._value;
    }

    set value(newValue) {
        document.body.querySelector(`[data-hook="${_idx}"]`).setAttribute('data-hook-value', _hooks[_idx]);
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
            document.body.querySelector(`[data-hook="${_idx}"]`).setAttribute('data-hook-value', _hooks[_idx]);
        }
    };

    const value = _hooks[_idx];
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

// create a use effect hook
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

// create a use effect hook
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

// create a use effect hook
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

// create a use effect hook
// @param callback
// @param dependencies
export function useRef(value = null) {
    const ref = {
        current: value
    };

    _idx++;

    return ref;
}

/**
 * 
 * @param {*} context 
 * @returns 
 */
export function useContext(context) {
    _idx++;

    return context;
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


export class useSinglePage {
    #events = new Map();

    constructor(options = {}) {
        const defaults = {
            'onBeforeFetch': () => {
                console.log('before fetch');
            },
            'onAfterFetch': () => {
                console.log('after fetch');
            },
            'delimiter': '|',
        }

        this.settings = {...defaults, ...options};

        Array.from(document.querySelectorAll('[data-sp-link]')).forEach((link => {
            this.#addClickListener(link);
        }));

        Array.from(document.querySelectorAll('[data-sp-form]')).forEach((form => {
            this.#addSubmitListener(form);
        }));
        
        window.addEventListener('popstate', (event) => {
            const state = JSON.parse(event.state);
            
            if(null === state) {
                this.loadPage('/');
            } else {
                this.loadPage(state.path);
            }
        });

        this.loadPage();

        this.titleDelimiter = ' ' + this.settings.delimiter + ' ';

        this.on('beforeFetch', this.settings.onBeforeFetch);
        this.on('afterFetch', this.settings.onAfterFetch);

        this.emit('onInit');
    }

    on(name, callback) {
        let event = null;
        if(!this.#events.has(name)) {
            this.#events.set(name, []);
        }
        
        event = this.#events.get(name);
        
        event.push(callback);
    }

    emit() {
        const args = Array.prototype.slice.call(arguments, 0);
        const evt = args.shift();

        if(this.#events.has(evt)) {
            const callbacks = this.#events.get(evt);
            callbacks.forEach(callback => {
                callback.apply(this, args);
            });
        }
    }

    #addClickListener(element) {
        element.addEventListener('click', (event) => {
            event.preventDefault();
    
            const path = event.target.getAttribute('href');
            history.pushState(null, null, path);
            this.loadPage();
        })
    }

    async #addSubmitListener(element) {
        element.addEventListener('submit', async (event) => {
            event.preventDefault();
            const path = event.target.getAttribute('action');
            const formData = new FormData(event.target);

            const response = await this.fetchData(path, formData, 'POST');

            this.emit('formSubmitted', JSON.stringify(response));
        });
    }

    async loadPage() {
        this.emit('beforeFetch');
        _idx = 0;
        const path = location.pathname;

        const response = await this.fetchData(path);
        response.path = path;
        
        const app = document.getElementById('app');
        app.innerHTML = response.data.content;

        if(response.data.title) {
            let currentTitle = this.#resetTitle();
            
            document.title = response.data.title + this.titleDelimiter + currentTitle;
        } else {
            document.title = this.#resetTitle();
        }
    
    
        Array.from(app.querySelectorAll('[data-sp-link]')).forEach((link => {
            this.#addClickListener(link);
        }));

        Array.from(app.querySelectorAll('[data-sp-form]')).forEach((form => {
            this.#addSubmitListener(form);
        }));


        this.emit('afterFetch');
    }

    #resetTitle() {
        let currentTitle = document.title;
        let delimiter = +currentTitle.lastIndexOf(this.titleDelimiter);

        if (delimiter >= 0) {
            currentTitle = currentTitle.slice(delimiter + 3, currentTitle.length);
        }

        return currentTitle;
    }

    async fetchData(path, data = {}, requestType = 'GET') {
        const options = {
            method: requestType.toUpperCase(),
            cache: 'no-cache',
        };

        if ('post' === requestType.toLowerCase()) {
            if (!data instanceof FormData) {
                data = JSON.stringify(data);
            }
            options.body = data;
        } else {
            options.headers = {
                'Content-Type': 'application/json'
            };
        }

        const request = new Request(path, options);
        const response = await fetch(request);
        
        return await response.json();
    }

    #addPadding(string, length) {
        return string.padStart(length, ' ').padEnd(length, ' ');
    }
}