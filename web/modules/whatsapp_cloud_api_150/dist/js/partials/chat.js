class Chat {
    id;
    detail;
    options;
    wrapperTarget;

    constructor(id, { wrapperSelector, userInfo, ...options }) {
        this.id = id;
        this.detail = userInfo;
        this.options = options;
        this.wrapperTarget = document.querySelector(wrapperSelector);
        if (this.wrapperTarget) this.init();
    }

    init() {
        let { on } = this.options;
        this.select = on.select || this.select;
        this.print();
    }

    print() {
        let { markup, location } = this.options;
        if (!markup) return;

        let item = document.createElement('li');
        item.classList.add('prmx-chat-list__item'); item.chatDetail = this;

        item.addEventListener('click', (e) => this.select(e.currentTarget, this))
        item.insertAdjacentHTML('beforeend', markup(this.detail))
        this.wrapperTarget.insertAdjacentElement(location, item)
    }

    select(e) {
        console.log('seleccionado', e);
    }
}