(function (Drupal, once) {
    'use strict';

    let timerMessageRequest, timerChatRequest;
    let currentChat, currentChatId, messageLimits = { old: 0, next: 0 };
    let thereIsOldMessages = true, thereIsNewMessages = false;

    Drupal.behaviors.chatFunctionality = {
        attach: function (context, settings) {

            // FIRST REQUEST
            once('chat-functionality', 'body').forEach(elm => {
                const getAndInjectChats = (id, type) => {
                    getOpenChats(id, type)
                        .then(response => {
                            let { data: chats } = response;
                            chats.forEach((userChat, index) => {
                                if (index === 0) currentChatId = userChat.id;
                                new Chat(userChat.phone, {
                                    wrapperSelector: '.prmx-chat-list__wrapper',
                                    userInfo: userChat,
                                    location: id ? 'afterbegin' : 'beforeend',
                                    markup(detail) {
                                        return `
                                        <div class="prmx-chat-card">
                                            <figure class="prmx-chat-card__avatar">
                                                <img src="${settings.core_whatsapp}" alt="" loading="lazy" height="50" width="50">
                                            </figure>
                                            <strong class="prmx-chat-card__name">${detail.phone}</strong>
                                            <span class="prmx-chat-card__date">${detail.date}</span>
                                        </div>
                                        `
                                    },
                                    on: {
                                        select: (elm, obj) => {
                                            initializeState(obj.id);
                                            toggleChats(elm.querySelector('.prmx-chat-card'), 'enable');
                                        }
                                    }
                                })
                            })
                        })
                        .catch(error => console.log(error))
                }

                getAndInjectChats();

                if (timerChatRequest) clearTimeout(timerChatRequest)
                timerChatRequest = setTimeout(function requestAgain() {
                    getAndInjectChats(currentChatId, 'next');
                    timerChatRequest = setTimeout(requestAgain, 10000)
                }, 10000);

                // Observer targets to get previous or next messages
                const target = document.querySelector('.prmx-chat-user__dispatch-prev')
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            if (thereIsOldMessages) {
                                if (timerMessageRequest) clearInterval(timerMessageRequest)
                                timerMessageRequest = setTimeout(() => {
                                    getNewMessages(currentChat, messageLimits.old, 'old')
                                }, 300);
                            }
                        }
                        else if (timerMessageRequest) clearInterval(timerMessageRequest)
                    })
                }, { threshold: .9 })

                if (target) observer.observe(target)
            });

            // HANDLER CHAT LIST
            let secTimerMessageRequest;
            once('pending-chat-list', '.prmx-chat-list').forEach(elm => {
                document.querySelector('.prmx-chat-list').addEventListener('click', (e) => {
                    let clicked = e.target;
                    if (currentChat && clicked.closest('.prmx-chat-list__item')) {

                        let wrapperMessages = document.querySelector('#messagesList')
                        if (wrapperMessages) wrapperMessages.innerHTML = ''

                        toggleClass(document.querySelector('.prmx-chat-header'), 'enable', 'prmx-chat-header--disabled');
                        toggleClass(document.querySelector('.prmx-chat-user'), 'enable', 'prmx-chat-user--disabled');

                        const requestMessages = () => {
                            getChatMessages(currentChat)
                                .then(response => {
                                    let { data: messages } = response;
                                    messages.forEach((message, index) => {
                                        if (index === 0) messageLimits = { ...messageLimits, old: parseInt(message.id) };
                                        if (index === messages.length - 1) messageLimits = { ...messageLimits, next: parseInt(message.id) };
                                        new Message('#messagesList', message)
                                    })
                                    scrollMessageContainer(wrapperMessages.offsetHeight);
                                })
                                .catch(error => console.log(error))
                        }

                        requestMessages();

                        if (secTimerMessageRequest) clearTimeout(secTimerMessageRequest)
                        secTimerMessageRequest = setTimeout(function requestAgain() {
                            if (currentChat) {
                                getNewMessages(currentChat, messageLimits.next, 'next');
                                secTimerMessageRequest = setTimeout(requestAgain, 5000)
                            }
                        }, 5000);
                    }
                })
            })

            // HANDLER CLOSE CHAT
            once('close-chat-btn', '#closeChat').forEach(elm => {
                document.querySelector('#closeChat').addEventListener('click', () => {
                    closeChat(currentChat)
                        .then(response => {
                            initializeState();
                            toggleChats(document.querySelector('.prmx-chat-card--active'), 'remove');

                            toggleClass(document.querySelector('.prmx-chat-header'), 'disable', 'prmx-chat-header--disabled');
                            toggleClass(document.querySelector('.prmx-chat-user'), 'disable', 'prmx-chat-user--disabled');
                        })
                        .catch(error => console.log(error))
                })
            })

            // HANDLER INPUT MESSAGE
            let sending = false;
            once('field-message', '#fieldMessage').forEach(elm => {
                const getAndSendMessage = () => {
                    alertMessage('remove');
                    scrolled = false;

                    if (sending) return; sending = true;
                    let { message, input } = getInputMessage();

                    sendMessage(currentChat, message)
                        .then((response) => {
                            input.value = ''
                            sending = false;
                        })
                        .then(() => {
                            if (secTimerMessageRequest) clearTimeout(secTimerMessageRequest)
                            secTimerMessageRequest = setTimeout(function requestAgain() {
                                if (currentChat) {
                                    getNewMessages(currentChat, messageLimits.next, 'next');
                                    secTimerMessageRequest = setTimeout(requestAgain, 5000)
                                }
                            });
                        })
                        .catch((error) => console.log(error))
                }

                document.querySelector('#fieldMessage #message').addEventListener('keyup', (e) => {
                    if (e.keyCode == 13) getAndSendMessage();
                })

                document.querySelector('#fieldMessage #messageSubmit').addEventListener('click', () => {
                    getAndSendMessage();
                })
            })
        }
    };

    async function getOpenChats(id = '', type = '') {
        let params = `id=${id}&type=${type}`;
        try {
            let response = await fetch(`/conversation/advises/data?${params}`);
            return response.json();
        } catch (error) {
            return 'Fallo:' + error
        }
    }

    async function getChatMessages(phone, id = '', type = '') {
        let params = `phone=${phone}&id=${id}&type=${type}`;
        try {
            let response = await fetch(`/conversation/advises/data/message?${params}`);
            return response.json();
        } catch (error) {
            return 'Fallo:' + error
        }
    }

    async function closeChat(phone) {
        try {
            let response = await fetch(`/conversation/advises/closed?phone=${phone}`);
            return response.json();
        } catch (error) {
            return 'Fallo:' + error
        }
    }

    async function sendMessage(phone, message) {
        if (!message) return;
        try {
            let response = await fetch(`/conversation/advises/data/message/send`, {
                method: 'POST',
                body: JSON.stringify({ phone: phone, message: message })
            });
            return response.json();
        } catch (error) {
            return 'Fallo:' + error
        }
    }

    function initializeState(phone) {
        currentChat = phone || ''
        thereIsOldMessages = true;
        messageLimits = { old: 0, next: 0 };
    }

    function getInputMessage() {
        const inputMessage = document.querySelector('#fieldMessage #message');
        if (inputMessage) return { message: inputMessage.value, input: inputMessage }
    }

    function scrollMessageContainer(translate = 0) {
        const wrapper = document.querySelector('.prmx-chat-user__messages');
        wrapper?.scrollTo({ top: translate - wrapper.offsetHeight })
    }

    function alertMessage(action) {
        switch (action) {
            case 'create':
                if (!document.querySelector('#newMessages')) {
                    document.querySelector('#messagesList')?.insertAdjacentHTML('beforeend', `<div id="newMessages">Tienes nuevos mensajes</div>`);
                }
                break;
            case 'remove':
                document.querySelector('#newMessages')?.remove()
                break;
        }
    }

    function toggleClass(target, type, nwClass) {
        if (!target) return;
        switch (type) {
            case 'enable':
                target.classList.remove(nwClass);
                break;
            case 'disable':
                if (!target.classList.contains(nwClass)) target.classList.add(nwClass);
                break;
        }
    }

    function toggleChats(target, type) {
        if (!target) return;
        const main_class = 'prmx-chat-card';

        switch (type) {
            case 'enable':
                toggleChats(document.querySelector(`.${main_class}--active`), 'disable')
                target.classList.add(`${main_class}--active`);
                break;
            case 'disable':
                target.classList.remove(`${main_class}--active`);
                break;
            case 'remove':
                target.parentNode.remove();
                break;
        }
    }

    let scrolled = false;
    function getNewMessages(phone, id, type) {
        getChatMessages(phone, id, type)
            .then(response => {
                let { data: messages } = response;
                switch (type) {
                    case 'old':
                        if (messages.length === 0) thereIsOldMessages = false;
                        messages.reverse().forEach((message, index) => {
                            let messageId = parseInt(message.id);
                            if (index === 0 && messageLimits.next < messageId) messageLimits = { ...messageLimits, next: messageId };
                            if (index === messages.length - 1 && messageLimits.old > messageId) messageLimits = { ...messageLimits, old: messageId };
                            new Message('#messagesList', message, false)
                        })
                        break;
                    case 'next':
                        let previousSize = document.querySelector('#messagesList').offsetHeight;
                        let isChatbotMessage = messages.every(item => item.user_message === 'chatbot');

                        messages.forEach((message, index) => {
                            let messageId = parseInt(message.id);
                            if (index === 0 && !isChatbotMessage) alertMessage('create');
                            if (index === 0 && messageLimits.old > messageId) messageLimits = { ...messageLimits, old: messageId };
                            if (index === messages.length - 1 && messageLimits.next < messageId) messageLimits = { ...messageLimits, next: messageId };
                            new Message('#messagesList', message)
                        })
                        if (messages.length !== 0 && isChatbotMessage) scrollMessageContainer(document.querySelector('#messagesList').offsetHeight);
                        else if (messages.length !== 0 && !scrolled) {
                            scrolled = true;
                            scrollMessageContainer(previousSize + document.querySelector('#newMessages').offsetHeight + 20);
                        }
                        break;
                }
            })
            .catch(error => console.log(error))
    }

}(Drupal, once));