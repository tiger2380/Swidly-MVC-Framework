class UseState {
    _state = [];

    constructor(initValue = null) {
        this._state[0] = initValue;
    }

    get state() {
        return this._state;
    }

    setState(value) {
       this.state[0] = value;
    }

    create_UUID(){
        var dt = new Date().getTime();
        var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = (dt + Math.random()*16)%16 | 0;
            dt = Math.floor(dt/16);
            return (c=='x' ? r :(r&0x3|0x8)).toString(16);
        });
        return uuid;
    }
};

export function useState(initValue = null) {
    let state = new UseState(initValue);

    return [
        state.state,
        state.setState.bind(state)
    ]
}

export default class SinglePage {
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
                this.#loadPage('/');
            } else {
                this.#loadPage(state.path);
            }
        });

        this.#loadPage();

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
            this.#loadPage();
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

    async #loadPage() {
        this.emit('beforeFetch');
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