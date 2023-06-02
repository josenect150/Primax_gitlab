class Message {
    id;
    detail;

    constructor(wrapperSelector, { id, ...detail }, isRecent = true) {
        this.id = id;
        this.detail = detail;
        this.create(document.querySelector(wrapperSelector), isRecent)
    }

    create(wrapperTarget, isRecent) {
        if (!wrapperTarget) return;
        let location = isRecent ? 'beforeend' : 'afterbegin';

        wrapperTarget.insertAdjacentHTML(location, `
            <div class="prmx-message prmx-message--${this.detail.user_message}">
                <span class="prmx-message__date">${this.detail.date}</span>
                <article class="prmx-message__content">
                    ${this.detail.message}
                </article>
            </div>
        `)
    }
}