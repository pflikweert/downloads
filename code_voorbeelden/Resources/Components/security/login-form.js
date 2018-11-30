import appEvents from '../app-events/app-events.js';
import { RegistrationEvent, LoginEvent,RawEvent } from '../tracking/tracking.js';
import { listElements } from '../dom/dom.js';

const selector = '[data-user-login-form]';
const buttonSelector = '[data-login-button]';
const showSelector = '[data-show-icon]';
const formType = 'data-form-type';
const buttonType = 'data-button-type';
export const isSupported = (typeof document.createElement('input').checkValidity === 'function');

export default {
    enhance,
    enhanceWithin,
    // enhancePassword,
    isSupported
}

var interval =setInterval(function() {
    if(document.readyState === 'complete') {
        var elementphno = document.querySelector(".success-message");
        if(elementphno != null){
            new RegistrationEvent('Email').send();
            clearInterval(interval);
            }else{
            clearInterval(interval);
        }
    }
}, 100);

appEvents.on('user-login-form:enhance', event => enhance(event.element));
// appEvents.on('data-user-register-form:enhance', event => enhancePassword(event.element));

export function enhance(form) {
    const formButtons = form.querySelectorAll(buttonSelector);

    formButtons.forEach((formButton) => formButton.addEventListener('click', (event) => {
        var form_button = event.currentTarget;
        if (form_button.getAttribute(formType) == 'login') {
            new LoginEvent(form_button.getAttribute(buttonType)).send();
            new RawEvent('Authentication','Need to login', 'Organic').send();
        } else if (form_button.getAttribute(formType) == 'registration') {
            //This the original Registration Event at the button press if needed please re enable here.
            //new RegistrationEvent(form_button.getAttribute(buttonType)).send();
        }
    }));

    // tabs function
    enhanceHashlinks(document);

    var icon = document.querySelector('#show-icon');
    icon.addEventListener('click', showHidePassword);

    // set the type of password field reg form only
    function showHidePassword(event) {
        var pwdField = document.querySelector('.reg-pwd');
        if(pwdField.getAttribute('type') == 'password') {
            pwdField.setAttribute('type', 'text');
            icon.querySelector('[data-icon-show]').style.display = 'block';
            icon.querySelector('[data-icon-hide]').style.display = 'none';
        } else {
            pwdField.setAttribute('type', 'password');
            icon.querySelector('[data-icon-show]').style.display = 'none';
            icon.querySelector('[data-icon-hide]').style.display = 'block';
        }
    }

    return form.setAttribute('data-enhanced', true);
}

export function enhanceHashlinks(doc) {
    var hashLinks = doc.querySelectorAll("a[href^='#tab-']");

    if (location.hash) {
        // Unactive tabs and hide
        var selectedHash = false;
        for (var i = 0, len = hashLinks.length; i < len; i++) {
            doc.getElementById(hashLinks[i].getAttribute('data-content')).style.display = "none";
            hashLinks[i].setAttribute('class', '');
            doc.querySelector('[data-user-'+hashLinks[i].getAttribute('data-content')+'-form]').setAttribute('id','');
            if (hashLinks[i].hash == location.hash) {
                // Make selected active
                doc.getElementById(hashLinks[i].getAttribute('data-content')).style.display = "block";
                hashLinks[i].setAttribute('class', 'active');
                // add id to active form
                doc.querySelector('[data-user-'+hashLinks[i].getAttribute('data-content')+'-form]').setAttribute('id','login-form');
                selectedHash = true;
            }
        }
        if (selectedHash == false && hashLinks[0]) {
            hashLinks[0].setAttribute('class', 'active');
            doc.querySelector('[data-user-'+hashLinks[0].getAttribute('data-content')+'-form]').setAttribute('id','login-form');
        }
    } else {
        // add id to sign in form
        doc.querySelector('[data-user-login-form]').setAttribute('id','login-form');
    }


    [].forEach.call(hashLinks, function (link, hashLinks) {
        if (link.getAttribute('class') != 'active') {
            doc.querySelector('[data-user-'+link.getAttribute('data-content')+'-form]').setAttribute("id", "");
            doc.getElementById(link.getAttribute('data-content')).style.display = "none";
        }
        link.addEventListener("click", function (event) {
            event.preventDefault();
            history.pushState({}, "", link.href);
            history.pushState({}, "", link.href);
            history.back();

            // Unactive tabs and hide
            hashLinks = doc.querySelectorAll("a[href^='#tab-']");
            for (var i = 0, len = hashLinks.length; i < len; i++) {
                doc.getElementById(hashLinks[i].getAttribute('data-content')).style.display = "none";
                doc.querySelector('[data-user-'+hashLinks[i].getAttribute('data-content')+'-form]').setAttribute("id", "");
                hashLinks[i].setAttribute('class', '');
            }
            // Make selected active
            doc.getElementById(link.getAttribute('data-content')).style.display = "block";
            link.setAttribute('class', 'active');
            // add id to active form
            doc.querySelector('[data-user-'+link.getAttribute('data-content')+'-form]').setAttribute("id", "login-form");
        });
    });
}



export function enhanceWithin(context, variant) {
    if (!isSupported) { return []; }
    const elements = listElements(context, selector);
    return elements.map(element => enhance(element, variant));
}

