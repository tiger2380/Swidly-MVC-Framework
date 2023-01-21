import SinglePage, { useState } from "./single_page.js";

const options = {
    'onBeforeFetch': () => {
        if(document.querySelector('.loader')) {
            document.querySelector('.loader').remove();
        }

        const div = document.createElement('div');
        div.classList.add('loader');
        Object.assign(div.style, { width: '2%', height: '5px', backgroundColor: 'rgba(255, 0, 0, 0.8', top: '0', left: '0', position: 'fixed', zIndex: 10, transition: 'all 0.3s linear' });
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
            document.querySelector('.loader').style.width = '100%';
            setTimeout(() => {
                if(document.querySelector('.loader')) {
                    document.querySelector('.loader').remove();
                }
            }, 1000);
        }
    },
    'delimiter': '🐅'
};

window.addEventListener('DOMContentLoaded', () => {
    window.SinglePage = new SinglePage(options);

    window.SinglePage.on('formSubmitted', (response) => {
       const {data} = JSON.parse(response);
       
       alert(data.message);
    });
});