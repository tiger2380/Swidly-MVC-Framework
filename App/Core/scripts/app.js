import SinglePage from "./single_page.js";

const options = {
    'onBeforeFetch': () => {
        const div = document.createElement('div');
        div.classList.add('loader');
        Object.assign(div.style, { width: '100%', height: '5px', backgroundColor: 'red', top: '0', left: '0', position: 'fixed', zIndex: 10 });

        document.body.appendChild(div);
    },
    'onAfterFetch': () => {
        if(document.querySelector('.loader')) {
            document.querySelector('.loader').remove();
        }
    }
};

window.addEventListener('DOMContentLoaded', () => {
    window.SinglePage = new SinglePage(options);
});