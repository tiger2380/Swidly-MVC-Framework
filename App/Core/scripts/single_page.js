export default class SinglePage {
    #events = new Map();

    constructor(options = {}) {
        const defaults = {
            'onBeforeFetch': () => {
                console.log('before fetch');
            },
            'onAfterFetch': () => {
                console.log('after fetch');
            }
        }

        this.settings = {...defaults, ...options};

        Array.from(document.querySelectorAll('[data-sp-link]')).forEach((link => {
            this.#addClickListener(link);
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
                callback.apply(this. args);
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

    async #loadPage() {
        this.emit('beforeFetch');
        const path = location.pathname;

        const response = await fetch(path, {
            method: 'GET', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json'
            }
        });
        const jsonData = await response.json();
        jsonData.path = path;
        
        const app = document.getElementById('app');
        app.innerHTML = jsonData.data.content;
    
    
        Array.from(app.querySelectorAll('[data-sp-link]')).forEach((link => {
            this.#addClickListener(link);
        }));

        this.emit('afterFetch');
    }
}