import SinglePage from "./single_page.js";

const options = {
    'onBeforeFetch': () => {
        const div = document.createElement('div');
        div.classList.add('loader');
        Object.assign(div.style, { width: '0%', height: '5px', backgroundColor: 'rgba(255, 0, 0, 0.8', top: '0', left: '0', position: 'fixed', zIndex: 10 });
        document.body.appendChild(div);

        const interval = setInterval(() => {
            let width = parseInt(div.style.width);
            if(width <= 80) {
                width += (Math.random() * 20);
                div.style.width = width +'%';
            } else {
                clearInterval(interval);
            }
        }, 1000);
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